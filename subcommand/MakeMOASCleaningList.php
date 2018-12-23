<?php
function MakeMOASCleaningList($start, $end = null, $whitelist_name = null){
	global $mysqli;

	if($end===null) $end = $start;
	if($whitelist_name===null) $whitelist_name = 'main';
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);

	$ASCountry = null;
	$data = array();
	// 出力ファイルのファイル名準備
	$output_filename = FILTER_SUSPICIOUS_ADVERTISEMENT.$whitelist_name.'/MOASCleaningList_'.date('Ymd.Hi',$ts).'_'.date('Ymd.Hi',$ts_end).'.csv';

	//==================== 5分毎にずらしながら実行 ====================//
	for(;$ts<=$ts_end;$ts+=5*60){
		// 日付が更新されたら，その日のASと国の紐付けを取得
		if($ASCountry===null || date('Hi',$ts)==='0000')
			$ASCountry = $mysqli->getASCountry($ts);

		// FilterSuspiciousAdvertisementの出力の読み込み
		$filename = FILTER_SUSPICIOUS_ADVERTISEMENT.$whitelist_name.'/'.date('Y.m',$ts).'/'.date('Ymd.Hi',$ts).'.csv';
		$file = date('Ymd.Hi',$ts).'.csv';
		// 1行ずつ読み込み
		$fp = fopen($filename, 'r');
		fgets($fp);
		while(($row = fgets($fp))!==false){
			// FilterSuspiciousAdvertisementからのデータ
			list($ip_prefix,$asn,$adv_type,$conf_ip_prefix,$conf_asn,$conf_type) = explode(',', rtrim($row));
			// $asn_ccの取得
			if(64512<=$asn && $asn<=65534) $asn_cc = 'Private';
			elseif(isset($ASCountry[$asn])) $asn_cc = $ASCountry[$asn][ASCOUNTRY_COUNTRY];
			else $asn_cc = 'unknown';
			// $conf_asn_ccの取得
			$conf_asn_cc = array();
			foreach (explode('/',$conf_asn) as $conf_asn2) {
				if(64512<=$conf_asn2 && $conf_asn2<=65534) $conf_asn_cc[] = 'Private';
				elseif(isset($ASCountry[$conf_asn2])) $conf_asn_cc[] = $ASCountry[$conf_asn2][ASCOUNTRY_COUNTRY];
				else $conf_asn_cc[] = 'unknown';
			}
			$conf_asn_cc = implode('/', $conf_asn_cc);
			// 重複を削除しながら$dataに追加
			$data["$adv_type,$conf_type,$ip_prefix,$conf_ip_prefix,$asn,$conf_asn,$asn_cc,$conf_asn_cc"] = $file;
		}
		fclose($fp);
	}
	
	// 出力
	$fp = fopen($output_filename, 'w');
	fwrite($fp, 'adv_type,conf_type,ip_prefix,conf_ip_prefix,asn,conf_asn,asn_cc,conf_asn_cc,file'.PHP_EOL);
	foreach ($data as $key => $value){
		fwrite($fp, "$key,$value".PHP_EOL);
	}
}
?>
