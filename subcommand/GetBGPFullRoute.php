<?php
function GetBGPFullRoute($rc, $start, $end = null){
	if(!isset(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	if($end===null) $end = $start;
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$end_ts = strtotime($end);

	// 実行内容の表示
	showLog($rc.'から'.date('Y-m-d H:i', $ts).'〜'.date('Y-m-d H:i', $end_ts).'のフルルート情報を取得します');

	// 8時間ごとに時間をずらしながら実行
	$error = array();
	while($ts <= $end_ts){
		// URL等の作成
		$filename = MakeFilenames($rc, $ts);
		$failed_count = 0;
		// 展開に成功するか3回失敗するまでDL・展開を繰り返す
		while($failed_count < 3){
			try{
				// ファイルをDL
				showLog('downloading: '.date('Y-m-d H:i',$ts)." ({$filename['fullroute_url']})");
				if(!downloadFile($filename['fullroute_url'], $filename['fullroute_dl'])){
					// 一時的に1分戻す（$ts2）
					$ts2 = $ts-60;
					$filename2 = MakeFilenames($rc, $ts2);
					// ファイルをDL
					showLog('failed, retry: '.date('Y-m-d H:i',$ts2)." ({$filename2['fullroute_url']})");
					if(!downloadFile($filename2['fullroute_url'], $filename['fullroute_dl'])) throw new Exception();
				}

				// DLに成功したらbgpscannerで展開
				showLog("extracting mrt: {$filename['fullroute_dl']} > {$filename['fullroute_bgpscanner']}");
				system("/usr/bin/bgpscanner -o {$filename['fullroute_bgpscanner']} {$filename['fullroute_dl']} 2>&1", $return_var);
				if($return_var!==0) throw new Exception();

				// ネットワークリストへの変換
				showLog("extracting PHP network list: {$filename['fullroute_bgpscanner']} > {$filename['fullroute_phpdata']}");
				$network_list = getFullRouteFromBGPScanner($filename['fullroute_bgpscanner']);
				file_put_contents($filename['fullroute_phpdata'], serialize($network_list));

				// 終了
				break;
			}catch(Exception $e){
				$failed_count++;
				showLog("failed($failed_count)");
				if($failed_count===3) $error[] = date('Y-m-d H:i', $ts);
			}
		}
		$ts += 60*60*8;
	}

	// 失敗した日付のリストを返す
	return $error;
}
?>
