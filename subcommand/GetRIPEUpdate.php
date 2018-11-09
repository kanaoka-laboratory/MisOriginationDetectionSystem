<?php
function GetRIPEUpdate($start, $end = null){
	if($end===null) $end = $start;
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$end_ts = strtotime($end);

	// 実行内容の表示
	showLog(date('Y-m-d H:i', $ts) . '〜' . date('Y-m-d H:i', $end_ts) . 'の更新経路情報を取得します');

	// 5分ごとに時間をずらしながら実行
	while($ts <= $end_ts){
		// URL等の作成
		$ripe = MakeRIPEUpdateParam($ts);
		$failed_count = 0;
		while($failed_count<3){
			try{
				// ファイルをDL
				showLog('downloading: '.date('Y-m-d H:i',$ts)." ({$ripe['url']})");
				if(!downloadFile($ripe['url'], $ripe['gz'])) throw new Exception();

				// DLに成功したらbgpdumpに展開
				showLog("extracting bgpdump: {$ripe['gz']} > {$ripe['bgpdump']}");
				system("/usr/bin/python script/mrt2bgpdump.py {$ripe['gz']} > {$ripe['bgpdump']} 2>&1", $return_var);
				if($return_var!==0) throw new Exception();

				// 終了
				break;
			}catch(Exception $e){
				$failed_count++;
				showLog("failed($failed_count)");
			}
		}
		$ts += 60*5;
	}
}
?>
