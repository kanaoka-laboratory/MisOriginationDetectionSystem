<?php
function GetRIPE($start, $end){
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$end_ts = strtotime($end);
	// 終了時間がUTCで未来になるのを防ぐ
	$end_max_ts = strtotime('9 hours ago');
	if($end_ts > $end_max_ts) $end_ts = $end_max_ts;
	// タイムスタンプが8時間おきのあたい出ない場合はエラー
	if(!in_array(date('His',$ts), ['000000','080000','160000'], true)){
		showLog('illegal start date', true);
		exit(1);
	}

	// 実行内容の表示
	showLog(date('Y-m-d H:i', $ts) . '〜' . date('Y-m-d H:i', $end_ts) . 'のフルルート情報を取得します');

	// 8時間ごとに時間をずらしながら実行
	while($ts<=$end_ts){
		// URL等の作成
		$ripe = MakeRIPEDownloadParam($ts);
		
		// ファイルをDL
		showLog('downloading: '.date('Y-m-d H:i',$ts)." ({$ripe['url']})");
		if(downloadFile($ripe['url'], $ripe['gz'])){
			// DLに成功したらbgpdumpに展開
			showLog("extracting bgpdump: {$ripe['gz']} > {$ripe['bgpdump']}");
			shell_exec("/usr/bin/python script/mrt2bgpdump.py {$ripe['gz']} > {$ripe['bgpdump']} &");
		}else{
			// 一時的に1分戻す
			$ts -= 60;
			$ripe = MakeRIPEDownloadParam($ts);
			// ファイルをDL
			showLog('failed, retry: '.date('Y-m-d H:i',$ts)." ({$ripe['url']})");
			if(downloadFile($ripe['url'], $ripe['gz'])){
				// DLに成功したらbgpdumpに展開
				showLog("extracting bgpdump: {$ripe['gz']} > {$ripe['bgpdump']}");
				shell_exec("/usr/bin/python script/mrt2bgpdump.py {$ripe['gz']} > {$ripe['bgpdump']} &");
			}else{
				// DLに失敗したらログ出力だけ
				showLog('download failed');
			}
			// 一時的に遡った分未来へ戻る．Back to the future!
			$ts += 60;
		}
		$ts += 60*60*8;
	}
}

function MakeRIPEDownloadParam($ts){
	$Ymd_Hi = date('Ymd.Hi',$ts);
	$url = "http://data.ris.ripe.net/rrc00/".date('Y.m', $ts)."/bview.$Ymd_Hi.gz";
	$file_gz = RIPE_FULL_GZ."bview.$Ymd_Hi.gz";
	$file_bgpdump = RIPE_FULL_BGPDUMP."$Ymd_Hi.bgpdump.txt";
	return array('url'=>$url, 'gz'=>$file_gz, 'bgpdump'=>$file_bgpdump);
}
?>
