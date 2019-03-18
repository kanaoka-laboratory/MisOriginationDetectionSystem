<?php
function ExtractPHPDataFromBGPScanner($rc, $start, $end = null){
	if(!isset(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	if($end===null) $end = $start;
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);

	// 8時間ごとに時間をずらしながら実行
	for(;$ts <= $ts_end; $ts += 60*60*8){
		// URL等の作成
		$filename = MakeFilenames($rc, $ts);
		// bgpscannerファイルがない場合はエラーを表示してスキップ
		if(!is_file($filename['fullroute_bgpscanner'])){
			showLog("file not found: {$filename['fullroute_bgpscanner']}");
			continue;
		}
		// 必要な情報を抽出して出力
		showLog("extracting: {$filename['fullroute_bgpscanner']} > {$filename['fullroute_phpdata']}");
		$network_list = getFullRouteFromBGPScanner($filename['fullroute_bgpscanner']);
		file_put_contents($filename['fullroute_phpdata'], serialize($network_list));
	}
}
?>
