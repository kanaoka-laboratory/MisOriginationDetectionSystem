<?php
function GetRIPEUpdate($start, $end){
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$end_ts = strtotime($end);
	// 終了時間がUTCで未来になるのを防ぐ
	$end_max_ts = strtotime('9 hours ago');
	if($end_ts > $end_max_ts) $end_ts = $end_max_ts;
	// タイムスタンプが8時間おきのあたい出ない場合はエラー
	if((int)date('is',$ts) % 500 !== 0){
		showLog('illegal start date', true);
		exit(1);
	}

	// 実行内容の表示
	showLog(date('Y-m-d H:i', $ts) . '〜' . date('Y-m-d H:i', $end_ts) . 'の更新経路情報を取得します');

	// 8時間ごとに時間をずらしながら実行
	while($ts<=$end_ts){
		// URL等の作成
		$Y_m = date('Y.m', $ts);
		$Ymd_Hi = date('Ymd.Hi',$ts);
		$url = "http://data.ris.ripe.net/rrc00/$Y_m/updates.$Ymd_Hi.gz";
		$file_gz = RIPE_UPDATE_GZ."$Y_m/updates.$Ymd_Hi.gz";
		$file_bgpdump = RIPE_UPDATE_BGPDUMP."$Y_m/$Ymd_Hi.bgpdump.txt";
		// DL先ディレクトリがなかった場合は作成
		if(!is_dir(RIPE_UPDATE_GZ.$Y_m)) mkdir(RIPE_UPDATE_GZ.$Y_m);
		if(!is_dir(RIPE_UPDATE_BGPDUMP.$Y_m)) mkdir(RIPE_UPDATE_BGPDUMP.$Y_m);

		// ファイルをDL
		showLog('downloading: '.date('Y-m-d H:i',$ts)." ($url)");
		if(downloadFile($url, $file_gz)){
			// DLに成功したらbgpdumpに展開
			showLog("extracting bgpdump: $file_gz > $file_bgpdump");
			shell_exec("/usr/bin/python script/mrt2bgpdump.py $file_gz > $file_bgpdump &");
		}else{
			// DLに失敗したらログ出力だけ
			showLog('download failed');
		}
		$ts += 60*5;
	}
}
?>
