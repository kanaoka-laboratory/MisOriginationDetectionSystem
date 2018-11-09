<?php
require_once('subcommand/GetRIPE.php');
// require_once('subcommand/TrackOriginExactChangedPrefix2.php');
require_once('subcommand/TrackOriginIncludeChangedPrefix2.php');

function CronRIPEFull(){
	// CRON_RIPE_FULLを読み込み
	list($date, $processing) = explode("\n", file_get_contents(CRON_RIPE_FULL));
	// 他プロセスで処理中の場合終了
	if($processing!=='') showLog('他プロセスで実行中', true);
	
	// DLするファイルのタイムスタンプ作成，$dateを更新
	$ts = strtotime("$date +8 hours");
	$date = date('Y-m-d H:i', $ts);
	// 日本標準時の時差を考慮して，tsが今より未来なら終了
	if($ts > strtotime('now -9 hours')) showLog('まだ次のフルルートがダンプされる時間ではありません', true);
	// 404チェック
	$ripe = MakeRIPEParam($ts);
	fclose(fopen($ripe['url'], 'r', false, stream_context_create(array('http'=>array('ignore_errors'=>true)))));
	if(substr($http_response_header[0], 9, 3)==='404') showLog('ダウンロードできるファイルがありません（404 Not Found）', true);

	// CRON_RIPE_FULLの2行目を実行中に変更
	showLog('実行開始');
	file_put_contents(CRON_RIPE_FULL, 'processing', FILE_APPEND);

	// DL，bgpdump・PHPDataの抽出
	GetRIPE($date);
	// 展開に失敗したら終了．この段階ではprocessingの状態なので手動で直すまでcronの処理は止まる
	if(!is_file($ripe['phpdata']))
		showLog('DLまたはbgpdump・PHPDataの抽出に失敗', true);

	
	// CRON_RIPE_FULLの1行目の時間を更新，2行目を空に
	showLog('DL，bgpdump・PHPData抽出完了');
	file_put_contents(CRON_RIPE_FULL, "$date\n");

	showLog('ExactMatchによる変更抽出・IPプレフィックスの追跡実験開始');
	TrackOriginExactChangedPrefix2($date);
	showLog('IncludeMatchによる変更抽出・IPプレフィックスの追跡実験開始');
	TrackOriginIncludeChangedPrefix2($date);
}
?>
