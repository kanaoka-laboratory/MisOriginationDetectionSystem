<?php
//==================== 初期設定 ====================//
// 引数の処理
if(!isset($argv[1])) exit('usage: php detectionSystem.php Ymd.Hi'.PHP_EOL.'example: php detectionSystem.php 20180601.0800'.PHP_EOL);
if(!preg_match('/^\d{8}\.\d{2}00$/',$argv[1])) exit('invalid datetime format: '.$argv[1].PHP_EOL);
// プログラムのカレントディレクトリを変更
chdir(dirname(__FILE__));
// 設定ファイル読み込み
require_once('config.php');
// 関数などの読み込み
foreach(glob('import/*.php') as $filename) require_once($filename);
// TimezoneをUTCに設定
date_default_timezone_set('UTC');
// 実行時間測定用プログラムタイマーのリセット
resetProgramTimer('実行開始');

//==================== グローバル変数の宣言 ====================//
// MySQLへのコネクション
$mysqli = new mymysqli();
// フルルートの格納先配列
$prev_network_list = array();
$next_network_list = array();

//==================== MySQLから変更前データを取得 ====================//
// 更新前データはMySQLから読み込む
showLog('変更前データの読み込み開始');
getPrevFullRouteFromDB($prev_network_list);
showLog('変更前データの読み込み完了');

//==================== RIPEから変更後の経路情報を取得してbgpdump形式に変換 ====================//
// このプログラムはcronによってUTC 1:00（JCT 10:00）に起動する

// RIPEのURLを作成（00:00分のデータ）
$next_ts = strtotime($argv[1]);
$next_dl_filename = 'bview.'.date('Ymd', $next_ts).'.0000.gz';
$next_bgpdump_filename = date('Ymd', $next_ts).'.0000.bgpdump.txt';
$mysql_datetime = date('Y-m-d H:i:s', $next_ts);

// DLして変換
showLog('変更後データのダウンロード開始');
if(downloadFile('http://data.ris.ripe.net/rrc00/'.date('Y.m', $next_ts).'/'.$next_dl_filename, DIR_RIPE_DL.$next_dl_filename)){
	showLog('変更後データのダウンロード完了');
	// 変換処理
	showLog('変更後データのbgpdumpへの変換開始');
	if(is_file(DIR_RIPE_BGPDUMP.$next_bgpdump_filename))
		showLog('既にファイルが存在しています');
	else
		shell_exec('/usr/bin/python mrt2bgpdump.py '.DIR_RIPE_DL.$next_dl_filename.' > '.DIR_RIPE_BGPDUMP.$next_bgpdump_filename);
	showLog('変更後データのbgpdumpへの変換完了');
}else{
	showLog('後日データのダウンロードに失敗しました');
	exit;
}
//==================== 変更後のデータをそれぞれ読み込んで配列に格納 ====================//
showLog('変更後データの読み込み開始');
$conflict_exception_list = getFullRouteFromBgpdump(DIR_RIPE_BGPDUMP.$next_bgpdump_filename, $next_network_list);
showLog('変更後データの読み込み完了');
// 衝突例外情報をMySQLに保存
showLog('衝突例外情報保存開始');
insertConflictExceptionToDB($conflict_exception_list);
showLog('衝突例外情報保存完了');

//==================== network_listのソート ====================//
ksort($prev_network_list['v4']);
ksort($prev_network_list['v6']);
ksort($next_network_list['v4']);
ksort($next_network_list['v6']);

//==================== 変更検出 ====================//
// 個人メモ：変更後情報を読み込んだあとは，衝突検知の処理と，変更前経路取得・変更検知の処理は並列で動かせるはず
// 変更情報を抽出
showLog('変更情報抽出開始');
$update_list = detectUpdate($prev_network_list, $next_network_list);
showLog('変更情報抽出完了');
// 変更情報をMySQLに保存
showLog('変更情報保存開始');
insertFullRouteUpdateToDB($update_list, $next_network_list);
showLog('変更情報保存完了');

//==================== 衝突検出 ====================//
// IPの重複確認を総当たりで行う
showLog('衝突検知開始');
$conflict_list = detectConflict($next_network_list);
showLog('衝突検知完了');
// 衝突情報をMySQLに保存
showLog('衝突情報保存開始');
insertConflictToDB($conflict_list);
showLog('衝突情報保存完了');

// Mis-Originationの可能性がある経路をフィルタ

// 出力（Slack，owncloudなど）

// 後処理（生データを圧縮・作業ファイルを削除）

?>
