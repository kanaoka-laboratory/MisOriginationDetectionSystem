<?php
function FilterSuspiciousAdvertisementSummary($start, $end = null, $whitelist_name = null){
	if($end===null) $end = $start;
	if($whitelist_name===null) $whitelist_name = 'main';
	// ホワイトリストが存在しない場合は終了
	if(!is_dir(FILTER_SUSPICIOUS_ADVERTISEMENT.$whitelist_name))
		showLog("ホワイトリストが存在しません：$whitelist_name", true);
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);

	showLog(date('Y/m/d H:i',$ts).'〜'.date('Y/m/d H:i',$ts_end).'の各conflict_typeごとのAdvertisementの数を集計');
	$filename = FILTER_SUSPICIOUS_ADVERTISEMENT."$whitelist_name/summary_".date('Ymd.Hi',$ts).'_'.date('Ymd.Hi',$ts_end).'.csv';
	$fp_out = fopen($filename, 'w');
	// タイトル行の出力
	fwrite($fp_out, 'date,SUSPICIOUS,PRIVATE_ASN,NEARBY_COUNTRY,US_DOD,AKAMAI'.PHP_EOL);
	for(; $ts<=$ts_end; $ts+=60*5){
		// それぞれのtypeのカウント用配列
		$count = array(	CONFLICT_TYPE_SUSPICIOUS=>0,	CONFLICT_TYPE_PRIVATE_ASN=>0,	CONFLICT_TYPE_NEARBY_COUNTRY=>0,
						CONFLICT_TYPE_US_DOD=>0,		CONFLICT_TYPE_AKAMAI=>0);
		// ファイルオープン
		$Y_m = date('Y.m', $ts);
		$Ymd_Hi = date('Ymd.Hi', $ts);
		$fp = fopen(FILTER_SUSPICIOUS_ADVERTISEMENT."$whitelist_name/$Y_m/$Ymd_Hi.csv", 'r');
		// タイトル行をスキップ
		fgets($fp);
		// 1行ずつ読み込んで$countの該当するtypeを+1
		while (($row=fgets($fp))!==false) {
			// ip_prefix,asn,type,conflict_ip_prefix,conflict_asn,conflict_type
			$rowinfo = explode(',', rtrim($row));
			$count[$rowinfo[5]]++;
		}
		fclose($fp);
		// 結果の出力をしてファイルクローズ
		fwrite($fp_out, date('Y/m/d H:i', $ts).','.implode(',', $count).PHP_EOL);
	}
	fclose($fp_out);
}
?>
