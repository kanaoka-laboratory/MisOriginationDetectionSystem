<?php
//==================== 時間測定用 ====================//
// プログラムの開始時間（epocタイムスタンプ）を保存しておく変数
$log__start_time = 0;
$log__fp = null;

//------------ ログを取り始める ------------//
function startLogging($subcommand){
	global $log__start_time, $log__fp;
	// TimezoneをJSTに設定
	date_default_timezone_set('Asia/Tokyo');
	// プログラムタイマーの初期化
	$log__start_time = time();

	// ログの出力先を指定
	$date = date('Ymd_His');
	$log__fp = fopen("log/$subcommand/$date.log", 'w');
}
//------------ ログを表示する ------------//
// フォーマットは以下の通り
// 'Y/m/d_H:i:s[spent_time] message
function showLog($message, $is_error = false){
	global $log__start_time, $log__fp;
	// 経過時間を計算
	$spent_time = time()-$log__start_time;
	$hour = str_pad((int)($spent_time/3600), 2, '0', STR_PAD_LEFT);
	$min_sec = gmdate('i:s', $spent_time);
	// ログの行を作成
	$log = date('Y/m/d_H:i:s')."[$hour:$min_sec] $message".PHP_EOL;
	
	// ログを出力
	if(LOG_STDOUT) echo $log;
	fwrite($log__fp, $log);

	// エラーの場合はプログラムを終了
	if($is_error){
		fclose($log__fp);
		exit(1);
	}
}

function endLogging(){
	// 終了メッセージの表示
	showLog('Complete executing');
	// ログを閉じる
	global $log__fp;
	fclose($log__fp);
}
?>
