<?php
function GetBGPUpdate($rc, $start, $end = null){
	if(!isset(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	if($end===null) $end = $start;
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$end_ts = strtotime($end);

	// 実行内容の表示
	showLog($rc.'から'.date('Y-m-d H:i', $ts).'〜'.date('Y-m-d H:i', $end_ts).'の更新経路情報を取得します');

	// 5分ごとに時間をずらしながら実行
	$error = array();
	while($ts <= $end_ts){
		// URL等の作成
		$filename = MakeFilenames($rc, $ts);
		$failed_count = 0;
		// 展開に成功するか3回失敗するまでDL・展開を繰り返す
		while($failed_count<3){
			try{
				// ファイルをDL
				showLog('downloading: '.date('Y-m-d H:i',$ts)." ({$filename['update_url']})");
				if(!downloadFile($filename['update_url'], $filename['update_dl'])) throw new Exception();

				// DLに成功したらbgpscannerで展開
				showLog("extracting mrt: {$filename['update_dl']} > {$filename['update_bgpscanner']}");
				system("/usr/bin/bgpscanner -o {$filename['update_bgpscanner']} {$filename['update_dl']} 2>&1", $return_var);
				if($return_var!==0) throw new Exception();

				// 終了
				break;
			}catch(Exception $e){
				$failed_count++;
				showLog("failed($failed_count)");
				if($failed_count===3) $error[] = date('Y-m-d H:i', $ts);
			}
		}
		$ts += 60*5;
	}

	// 失敗した日付のリストを返す
	return $error;
}
?>
