<?php
require_once('subcommand/GetRIPEUpdate.php');
require_once('subcommand/AnalyseAdvertisementUpdate.php');
function CronRIPEUpdate(){
	//------------ 他プロセスが処理中かどうかの確認 ------------//
	// CRON_RIPE_FULLを読み込み
	list($date_completed, $date_extracted, $processing) = explode("\n", file_get_contents(CRON_RIPE_UPDATE));
	// 他プロセスで処理中の場合終了
	if($processing!=='') showLog('他プロセスで実行中', true);
	
	//------------ 次の時間で404チェック ------------//
	// DLするファイルのタイムスタンプ作成，$date_extractedを更新
	$ts_download = strtotime("$date_extracted +5 minutes");
	$date_extracted = date('Y-m-d H:i', $ts_download);
	// 404チェック
	$ripe = MakeRIPEUpdateParam($ts_download);
	fclose(fopen($ripe['url'], 'r', false, stream_context_create(array('http'=>array('ignore_errors'=>true)))));
	if(substr($http_response_header[0], 9, 3)==='404') showLog('ダウンロードできるファイルがありません（404 Not Found）', true);
	
	//------------ processingに変更 ------------//
	showLog('実行開始');
	file_put_contents(CRON_RIPE_UPDATE, 'processing', FILE_APPEND);

	//------------ DL，bgpdumpの抽出 ------------//
	GetRIPEUpdate($date_extracted);
	// 展開に失敗したら終了．この段階ではprocessingの状態なので手動で直すまでcronの処理は止まる
	if(!is_file($ripe['bgpdump'])) showLog('DLまたはbgpdumpの抽出に失敗', true);
	showLog('DL，bgpdumpの抽出完了');
	
	//------------ データが揃っていれば変更検出 ------------//
	// 実験可能なlatestなタイムスタンプを求める
	$date_fullroute_completed = explode("\n", file_get_contents(CRON_RIPE_FULL))[0];
	$ts_max = strtotime("$date_fullroute_completed +8 hours")-60*5;
	// 実験を行いたいoldestなタイムスタンプを求める
	$ts = strtotime("$date_completed +5 minutes");
	
	//------------ 実験可能なデータはない ------------//
	// 2行目（$date_extracted）を更新して3行目（processing）を空行にする
	if($ts > $ts_max){
		file_put_contents(CRON_RIPE_UPDATE, "$date_completed\n$date_extracted\n");
		showLog('変更検出の対象となる8時間おきのフルルートのデータがまだ揃っていません', true);
	}

	//------------ 変更検出 ------------//
	// フルルートが処理できるタイムスタンプとBGPDUMPが展開済みのタイムスタンプのうち小さい方をts_maxとする
	$ts_max = ($ts_max>$ts_download)? $ts_download: $ts_max;
	// $ts〜$ts_maxまでの変更検出（date(string)とts(int)の変換が多いな，，減らせないもんかね，，，）
	// ログは呼び出し先関数内にお任せ
	$date_max = date('Y-m-d H:i', $ts_max);
	AnalyseAdvertisementUpdate(date('Y-m-d H:i', $ts), $date_max);

	//------------ 終了処理 ------------//
	showLog('変更検出完了');
	file_put_contents(CRON_RIPE_UPDATE, "$date_max\n$date_extracted\n");
}
?>
