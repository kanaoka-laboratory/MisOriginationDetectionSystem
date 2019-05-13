<?php
// テキスト形式のホワイトリストは FILTER_SUSPICIOUS_ADVERTISEMENT の中に hogehoge.txt の形式で保存
// 同じディレクトリに hogehoge/ ディレクトリを作成する
function FilterSuspiciousBGPUpdate($rc = null){
	if($rc!==null && !isset(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	global $mysqli;

	// pre_conflict_typeがnull（AnalyseBGPUpdate直後）の行を取得（一度に最大10000件）
	$additional_where = $rc!==null? "and rc='$rc'": "";
	$result = $mysqli->query("select update_id,asn,conflict_asn,date_update from PrefixConflictedUpdate ".
														"where suspicious_id is null $additional_where limit 10000");
	// 1行ずつ処理
	$as_country_list = array();
	while($update = $result->fetch_assoc()){
		//------------ 個別の変数に格納 ------------//
		$update_id = $update["update_id"];
		$asn = $update["asn"];
		$conflict_asn = $update["conflict_asn"];

		showLog("処理中：$update_id");

		//------------ ASと国の紐付けデータを取得 ------------//
		$date = substr($update["date_update"], 0, 10);
		if(!isset($as_country_list[$date])) $as_country_list[$date] = $mysqli->GetASCountry($date);
		$as_country = $as_country_list[$date];

		//------------ asn_ccの決定 ------------//
		if(64512<=$asn && $asn<=65534) $asn_cc = '-P';
		elseif(isset($as_country[$asn])) $asn_cc = $as_country[$asn][ASCOUNTRY_COUNTRY];
		else $asn_cc = '-X';

		// 全てのconflict_typeより小さい値（-1）で初期化
		$conflict_type = -1;
		// MOAS等の場合のため$asn2をforeach
		// MOAS内でconflict_typeが違う場合，値が大きい方を採用（PRIVATE < SUSPICIOUS < WHITELIST < BLACKLIST）
		$conflict_asn_cc = array();
		$conflict_asn_whois = array();
		foreach(explode('/', $conflict_asn) as $asn2){
			//------------ conf_asn_ccを求める ------------//
			if(64512<=$asn2 && $asn2<=65534) $asn2_cc = '-P';
			elseif(isset($as_country[$asn2])) $asn2_cc = $as_country[$asn2][ASCOUNTRY_COUNTRY];
			else $asn2_cc = '-X';

			//------------ conflict_typeの決定 ------------//
			// 少なくとも片方がプライベートAS番号
			if($asn_cc==='-P' || $asn2_cc==='-P') $new_conflict_type = CONFLICT_TYPE_PRIVATE_ASN;
			// 同じ国（ただしどちらも国籍不明である場合は除く）
			elseif($asn_cc===$asn2_cc && $asn_cc!=='-X') $new_conflict_type = CONFLICT_TYPE_SAME_COUNTRY;
			// 国単位のホワイトリストでの検証
			elseif(($type = $mysqli->VerifyConflictCountryWhiteList($asn_cc, $asn2_cc))!==null) $new_conflict_type = $type;
			// AS単位のホワイトリストでの検証
			elseif(($type = $mysqli->VerifyConflictAsnWhiteList($asn, $asn2_cc))!==null) $new_conflict_type = $type;
			// 怪しい
			else $new_conflict_type = CONFLICT_TYPE_SUSPICIOUS;

			// $new_conflict_typeがそれまでの$conflict_typeよりも大きかった場合値を更新
			if($new_conflict_type > $conflict_type) $conflict_type = $new_conflict_type;
			// $asn2_ccを保存
			$conflict_asn_cc[] = $asn2_cc;
		}
		$conflict_asn_cc = implode('/', $conflict_asn_cc);

		//------------ whois情報の取得 ------------//
		$asn_whois = "-";
		$conflict_asn_whois = "-";
		// CONFLICT_TYPE_SUSPICIOUSの場合はちゃんと取得
		if($conflict_type===CONFLICT_TYPE_SUSPICIOUS){
			$asn_whois = ($whois = GetWhoisAS($asn))!==null? $mysqli->real_escape_string($whois["name"]): "unknown";
			$conflict_asn_whois = array();
			foreach(explode('/', $conflict_asn) as $asn2)
				$conflict_asn_whois[] = ($whois = GetWhoisAS($asn2))!==null? $whois["name"]: "unknown";
			$conflict_asn_whois = $mysqli->real_escape_string(implode("/", $conflict_asn_whois));
		}

		// whois情報が関わってくるのでここに記述
		// 未割り当てASNからの攻撃
		if($asn_cc==='-X' && ($whois==='-' || $whois==='unknown'))
			$conflict_type = CONFLICT_TYPE_BLACKLIST_UNASSIGNED_ASN;
		// 未割り当てASN単体への攻撃（未割り当てASNからの攻撃に対する防御）
		elseif($conflict_asn_cc==='-X' && ($conflict_asn_whois==='-' || $conflict_asn_whois==='unknown'))
			$conflict_type = CONFLICT_TYPE_WHITELIST_DEFENSIVE_UPDATE;

		//------------ Suspicious_idの取得Updateの登録 ------------//
		$suspicious_id='null';
		$result2 = $mysqli->query("select suspicious_id from SuspiciousAsnSet where ".
						"asn=$asn and conflict_asn='$conflict_asn' and asn_cc='$asn_cc' and conflict_asn_cc='$conflict_asn_cc'")->fetch_assoc();

		if($result2){
			$suspicious_id = $result2["suspicious_id"];
		}else{
			$date_detection = (new DateTime("now", new DateTimeZone('UTC')))->format("Y-m-d H:i:s");
			$mysqli->query("insert into SuspiciousAsnSet (conflict_type,asn,conflict_asn,asn_cc,conflict_asn_cc,asn_whois,conflict_asn_whois,date_detection) ".
					"values($conflict_type,$asn,'$conflict_asn','$asn_cc','$conflict_asn_cc','$asn_whois','$conflict_asn_whois','$date_detection')");
			$suspicious_id = $mysqli->insert_id;
		}
		
		//------------ アップデート ------------//
		if($suspicious_id>0)
			$mysqli->query("update PrefixConflictedUpdate set suspicious_id=$suspicious_id where update_id=$update_id");
		else
			showLog("suspicious_idの取得に失敗しました，スキップします");
	}
}
?>
