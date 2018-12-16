<?php
// テキスト形式のホワイトリストは FILTER_SUSPICIOUS_ADVERTISEMENT の中に hogehoge.txt の形式で保存
// 同じディレクトリに hogehoge/ ディレクトリを作成する
function FilterSuspiciousAdvertisement($start, $end = null, $whitelist_name = null){
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
		// ファイルポインタの取得
		$Y_m = date('Y.m', $ts);
		$Ymd_Hi = date('Ymd.Hi', $ts);
		$fp = fopen(ANALYSE_ADVERTISEMENT_UPDATE_RESULT."$Y_m/$Ymd_Hi.csv", 'r');
		if(!is_dir(FILTER_SUSPICIOUS_ADVERTISEMENT."$whitelist_name/$Y_m")) mkdir(FILTER_SUSPICIOUS_ADVERTISEMENT."$whitelist_name/$Y_m");
		$fp_out = fopen(FILTER_SUSPICIOUS_ADVERTISEMENT."$whitelist_name/$Y_m/$Ymd_Hi.csv", 'w');
		// タイトル行
		fgets($fp);	// タイトル行読み込みスキップ
		fwrite($fp_out, 'ip_prefix,asn,type,conflict_ip_prefix,conflict_asn,conflict_type'.PHP_EOL);	// 固定行を出力
		// 1行ずつ読み込み
		while(($row=fgets($fp))!==false){
			$row = rtrim($row);
			// typeが5のときだけ該当する行を出力（$rowinfo[0]=ip_prefix, [1]=asn, [2]=type, [3]=conflict_ip_prefix, [4]=conflict_asn）
			$rowinfo = explode(',', $row);
			if((int)$rowinfo[2]===5){
				//------------ asnの取得 ------------//
				$asn1 = $rowinfo[1];
				$asn2 = $rowinfo[4];
				//------------ conflict_typeの決定 ------------//
				$conflict_type = null;
				// 少なくとも片方がプライベートAS番号
				if((64512<=$asn1 && $asn1<=65534) || (64512<=$asn2 && $asn2<=65534))
					$conflict_type = CONFLICT_TYPE_PRIVATE_ASN;
				// mainホワイトリストで照合
				elseif($whitelist_name==='main')
					;	// 未作成
				// ファイル形式のホワイトリストで照合
				else
					$conflict_type = CheckAsnAgainstWhitelistFromFile($whitelist, $asn1, $asn2);
				//------------ 出力 ------------//
				fwrite($fp_out, "$row,$conflict_type".PHP_EOL);
			}
		}
	}
	var_dump($whitelist);
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
