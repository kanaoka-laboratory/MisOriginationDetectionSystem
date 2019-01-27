<?php
function MakeMOASCleaningList($start, $end = null, $whitelist_name = null){
	if($end===null) $end = $start;
	if($whitelist_name===null) $whitelist_name = 'main';
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);

	$data = array();
	$data_filtered = array();
	// 出力ファイルのファイル名準備
	$output_filename = FILTER_SUSPICIOUS_ADVERTISEMENT.$whitelist_name.'/'.'MOASCleaningList_'.date('Ymd.Hi',$ts).'_'.date('Ymd.Hi',$ts_end).'.csv';
	$output_filename_filtered = AppendStrToFilename($output_filename, '_filtered');

	//==================== 5分毎にずらしながら実行 ====================//
	for(;$ts<=$ts_end;$ts+=5*60){
		 // FilterSuspiciousAdvertisementの出力の読み込み
		$filename = FILTER_SUSPICIOUS_ADVERTISEMENT.$whitelist_name.'/'.date('Y.m',$ts).'/'.date('Ymd.Hi',$ts).'.csv';
		$file = date('Ymd.Hi',$ts).'.csv';
		// 1行ずつ読み込み
		$fp = fopen($filename, 'r');
		fgets($fp);
		while(($row = fgets($fp))!==false){
			$row = rtrim($row);
			//------------ data: 完全一致の重複を削除（MOASCleaningにおける生データ） ------------//
			// 重複を削除しながら$dataに追加
			$data[$row] = $file;
			//------------ data_filtered: AS番号・国のみが重複するデータを削除 ------------//
			list($adv_type, $conf_type, $ip_prefix, $conf_ip_prefix, $asn, $conf_asn, $asn_cc, $conf_asn_cc) = explode(',', $row);
			// 5-1（MoreSpesificなadvertisementでAS番号が相違，ホワイトリストに引っかからない）または
			// 3-1（ExactMatch  なadvertisementでAS番号が相違，ホワイトリストに引っかからない）のみを対象にする
			if($adv_type!=='5' && $adv_type!=='3') continue;
			if($conf_type!=='1') continue;
			$key = "$asn,$conf_asn,$asn_cc,$conf_asn_cc";
			// 重複を削除しながら$data_remove_duplicateに追加し，重複数をカウント
			if(isset($data_filtered[$key]))
				$data_filtered[$key]['count']++;
			else
				$data_filtered[$key] = array('row'=>"$row,$file", 'count'=>1);
		}
		fclose($fp);
	}
	
	//------------ dataの出力 ------------//
	$fp = fopen($output_filename, 'w');
	fwrite($fp, 'adv_type,conf_type,ip_prefix,conf_ip_prefix,asn,conf_asn,asn_cc,conf_asn_cc,file'.PHP_EOL);
	foreach ($data as $row => $file){
		fwrite($fp, "$row,$file".PHP_EOL);
	}
	fclose($fp);
	//------------ data_remove_duplicateの出力 ------------//
	$fp = fopen($output_filename_filtered, 'w');
	fwrite($fp, 'adv_type,conf_type,ip_prefix,conf_ip_prefix,asn,conf_asn,asn_cc,conf_asn_cc,file,count'.PHP_EOL);
	foreach($data_filtered as $rowdata){
		fwrite($fp, "{$rowdata['row']},{$rowdata['count']}".PHP_EOL);
	}
}
?>
