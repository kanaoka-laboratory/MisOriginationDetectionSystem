<?php
function ExtractPHPDataFromBGPDUMP($start, $end = null){
	if($end===null) $end = $start;
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);

	// 8時間ごとに時間をずらしながら実行
	for(;$ts <= $ts_end; $ts += 60*60*8){
		// URL等の作成
		$ripe = MakeRIPEParam($ts);
		// bgpdumpファイルがない場合はエラーを表示してスキップ
		if(!is_file($ripe['bgpdump'])){
			showLog("file not found: {$ripe['bgpdump']}");
			continue;
		}
		// 必要な情報を抽出して出力
		showLog("extracting: {$ripe['bgpdump']} > {$ripe['phpdata']}");
		$network_list = getFullRouteFromBgpdump($ripe['bgpdump']);
		file_put_contents($ripe['phpdata'], serialize($network_list));
	}
}
?>
