<?php
function MakeMOASCleaningList($start, $end = null, $whitelist_name = null){
	if($end===null) $end = $start;
	if($whitelist_name===null) $whitelist_name = 'main';
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);

	$data = array();
	// 出力ファイルのファイル名準備
	$output_filename = FILTER_SUSPICIOUS_ADVERTISEMENT.$whitelist_name.'/MOASCleaningList_'.date('Ymd.Hi',$ts).'_'.date('Ymd.Hi',$ts_end).'.csv';

	//==================== 5分毎にずらしながら実行 ====================//
	for(;$ts<=$ts_end;$ts+=5*60){
		 // FilterSuspiciousAdvertisementの出力の読み込み
		$filename = FILTER_SUSPICIOUS_ADVERTISEMENT.$whitelist_name.'/'.date('Y.m',$ts).'/'.date('Ymd.Hi',$ts).'.csv';
		$file = date('Ymd.Hi',$ts).'.csv';
		// 1行ずつ読み込み
		$fp = fopen($filename, 'r');
		fgets($fp);
		while(($row = fgets($fp))!==false){
			// 重複を削除しながら$dataに追加
			$data[rtrim($row)] = $file;
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
