<?php
// テキスト形式のホワイトリストは FILTER_SUSPICIOUS_ADVERTISEMENT の中に hogehoge.txt の形式で保存
// 同じディレクトリに hogehoge/ ディレクトリを作成する
function FilterSuspiciousAdvertisement($start, $end = null, $whitelist_name = null){
	global $mysqli;
	if($end===null) $end = $start;
	if($whitelist_name===null) $whitelist_name = 'main';
	// ホワイトリストが存在しない場合は終了
	if(!is_dir(FILTER_SUSPICIOUS_ADVERTISEMENT.$whitelist_name))
		showLog("ホワイトリストが存在しません：$whitelist_name", true);
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);
	
	// 実行内容の表示
	showLog(date('Y-m-d H:i', $ts) . '〜' . date('Y-m-d H:i', $ts_end) . ' のハイジャックの可能性があるAdvertisementを分類');
	
	// ホワイトリストの読み込み
	if($whitelist_name!=='main'){
		showLog("ホワイトリストの読み込み：$whitelist_name");
		$whitelist = CreateWhitelistFromFile($whitelist_name);
	}else{
		showLog("利用するホワイトリスト：$whitelist_name");
	}

	//==================== 5分毎にずらしながら実行 ====================//
	for(; $ts<=$ts_end; $ts+=60*5){
		// 日付が更新されたら，その日のASと国の紐付けを取得
		if(!isset($ASCountry) || date('Hi',$ts)==='0000')
			$ASCountry = $mysqli->getASCountry($ts);

		// ファイルポインタの取得
		$Y_m = date('Y.m', $ts);
		$Ymd_Hi = date('Ymd.Hi', $ts);
		showLog(ANALYSE_ADVERTISEMENT_UPDATE_RESULT."$Y_m/$Ymd_Hi.csv の読み込み");
		$fp = fopen(ANALYSE_ADVERTISEMENT_UPDATE_RESULT."$Y_m/$Ymd_Hi.csv", 'r');
		if(!is_dir(FILTER_SUSPICIOUS_ADVERTISEMENT."$whitelist_name/$Y_m")) mkdir(FILTER_SUSPICIOUS_ADVERTISEMENT."$whitelist_name/$Y_m");
		$fp_out = fopen(FILTER_SUSPICIOUS_ADVERTISEMENT."$whitelist_name/$Y_m/$Ymd_Hi.csv", 'w');
		// タイトル行
		fgets($fp);	// タイトル行読み込みスキップ
		fwrite($fp_out, 'adv_type,conf_type,ip_prefix,conf_ip_prefix,asn,conf_asn,asn_cc,conf_asn_cc'.PHP_EOL);	// 固定行を出力
		// 1行ずつ読み込み
		while(($row=fgets($fp))!==false){
			// adv_typeが3か5のときだけ該当する行を処理
			list($ip_prefix, $asn, $adv_type, $conf_ip_prefix, $conf_asn) = explode(',', rtrim($row));
			if($adv_type==3 || $adv_type==5){
				// 全てのconflict_typeより小さい値（-1）で初期化
				$conf_type = -1;
				
				// asn_ccを求める
				if(64512<=$asn && $asn<=65534) $asn_cc = 'Private';
				elseif(isset($ASCountry[$asn])) $asn_cc = $ASCountry[$asn][ASCOUNTRY_COUNTRY];
				else $asn_cc = 'unknown';
			
				// MOAS等の場合のため$asn2をforeach
				// MOAS内でconflict_typeが違う場合，値が大きい方を採用（PRIVATE_ASN < SUSPICIOUS < WHITELIST）
				$conf_asn_cc = array();
				foreach(explode('/', $conf_asn) as $asn2){
					// conf_asn_ccを求める
					if(64512<=$asn2 && $asn2<=65534) $asn2_cc = 'Private';
					elseif(isset($ASCountry[$asn2])) $asn2_cc = $ASCountry[$asn2][ASCOUNTRY_COUNTRY];
					else $asn2_cc = 'unknown';

					//------------ conflict_typeの決定 ------------//
					// 少なくとも片方がプライベートAS番号
					if($asn_cc==='Private' || $asn2_cc==='Private') $new_conflict_type = CONFLICT_TYPE_PRIVATE_ASN;
					// 同じ国（ただしどちらも国籍不明である場合は除く）
					elseif($asn_cc===$asn2_cc && $asn_cc!=='unknown') $new_conflict_type = CONFLICT_TYPE_SAME_COUNTRY;
					// 国単位のホワイトリストでの検証（CONNECTED_BY_LAND）
					elseif(($type = $mysqli->VerifyConflictCountryWhiteList($asn_cc, $asn2_cc))!==null) $new_conflict_type = $type;
					// mainホワイトリストで照合
					elseif($whitelist_name==='main') $new_conflict_type=CONFLICT_TYPE_SUSPICIOUS;
					// ファイル形式のホワイトリストで照合
					else $new_conflict_type = CheckAsnAgainstWhitelistFromFile($whitelist, $asn, $asn2);

					// $new_conflict_typeがそれまでの$conflict_typeよりも大きかった場合値を更新
					if($new_conflict_type > $conf_type) $conf_type = $new_conflict_type;
					// $asn2_ccを保存
					$conf_asn_cc[] = $asn2_cc;
				}
				$conf_asn_cc = implode('/', $conf_asn_cc);
				//------------ 出力 ------------//
				fwrite($fp_out, "$adv_type,$conf_type,$ip_prefix,$conf_ip_prefix,$asn,$conf_asn,$asn_cc,$conf_asn_cc".PHP_EOL);
			}
		}
	}
}

function CreateWhitelistFromFile($whitelist_name){
	$conflict_type = null;
	$whitelist = array();
	$fp = fopen(FILTER_SUSPICIOUS_ADVERTISEMENT.$whitelist_name.'.txt', 'r');
	// 1行ずつ読み込み
	while(($row=fgets($fp)) !== false){
		if($row==="\n") continue;
		$asn = explode(',', rtrim($row));
		// 第1項目が'TYPE'の場合はタイトルなので conflict_type を取得
		if($asn[0]==='TYPE') $conflict_type = $asn[1];
		// ホワイトリストのデータ行
		else $whitelist[$asn[0]][$asn[1]] = $conflict_type;	
	}
	fclose($fp);
	// ホワイトリストを返す
	return $whitelist;
}

// ファイルを元にしたホワイトリスト判定
function CheckAsnAgainstWhitelistFromFile($whitelist, $asn1, $asn2){
	// ホワイトリスト内にあれば，そのconflict_typeを返す
	if(isset($whitelist['any'][$asn1])) return $whitelist['any'][$asn1];
	if(isset($whitelist['any'][$asn2])) return $whitelist['any'][$asn2];
	if(isset($whitelist[$asn1][$asn2])) return $whitelist[$asn1][$asn2];
	if(isset($whitelist[$asn2][$asn1])) return $whitelist[$asn2][$asn1];
	// ホワイトリストになければsuspicious
	else return CONFLICT_TYPE_SUSPICIOUS;
}
?>
