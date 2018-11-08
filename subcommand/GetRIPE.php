<?php
function GetRIPE($start, $end = null){
	if($end===null) $end = "$start +1 minute";
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$end_ts = strtotime($end);
	// 終了時間がUTCで未来になるのを防ぐ
	$end_max_ts = strtotime('9 hours ago');
	if($end_ts > $end_max_ts) $end_ts = $end_max_ts;
	// タイムスタンプが8時間おきのあたい出ない場合はエラー
	if(!in_array(date('His',$ts), ['000000','080000','160000'], true))
		showLog('illegal start date', true);

	// 実行内容の表示
	showLog(date('Y-m-d H:i', $ts) . '〜' . date('Y-m-d H:i', $end_ts) . 'のフルルート情報を取得します');

	// 8時間ごとに時間をずらしながら実行
	while($ts < $end_ts){
		// URL等の作成
		$ripe = MakeRIPEDownloadParam($ts);
		$failed_count = 0;
		// 展開に成功するか3回失敗するまでDL・展開を繰り返す
		while($failed_count < 3){
			try{
				// ファイルをDL
				showLog('downloading: '.date('Y-m-d H:i',$ts)." ({$ripe['url']})");
				if(!downloadFile($ripe['url'], $ripe['gz'])){
					// 一時的に1分戻す（$ts2）
					$ts2 = $ts-60;
					$ripe2 = MakeRIPEDownloadParam($ts2);
					// ファイルをDL
					showLog('failed, retry: '.date('Y-m-d H:i',$ts2)." ({$ripe2['url']})");
					if(!downloadFile($ripe2['url'], $ripe['gz']))
						throw new Exception();
				}

				// DLに成功したらbgpdumpに展開
				showLog("extracting bgpdump: {$ripe['gz']} > {$ripe['bgpdump']}");
				system("/usr/bin/python script/mrt2bgpdump.py {$ripe['gz']} > {$ripe['bgpdump']} 2>&1", $return_var);
				if($return_var!==0) throw new Exception();

				// ネットワークリストへの変換
				showLog("extracting PHP network list: {$ripe['bgpdump']} > {$ripe['phpdata']}");
				$network_list = getFullRouteFromBgpdump($ripe['bgpdump']);
				file_put_contents($ripe['phpdata'], serialize($network_list));

				// 終了
				break;
			}catch(Exception $e){
				$failed_count++;
				showLog("failed($failed_count)");
			}
		}
		$ts += 60*60*8;
	}
}

function MakeRIPEDownloadParam($ts){
	$Ymd_Hi = date('Ymd.Hi', $ts);
	$url = "http://data.ris.ripe.net/rrc00/".date('Y.m', $ts)."/bview.$Ymd_Hi.gz";
	$file_gz = RIPE_FULL_GZ."bview.$Ymd_Hi.gz";
	$file_bgpdump = RIPE_FULL_BGPDUMP."$Ymd_Hi.bgpdump.txt";
	$file_phpdata = RIPE_FULL_PHPDATA."$Ymd_Hi.dat";
	return array('url'=>$url, 'gz'=>$file_gz, 'bgpdump'=>$file_bgpdump, 'phpdata'=>$file_phpdata);
}
?>
