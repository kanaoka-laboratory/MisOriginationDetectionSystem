<?php
//==================== 初期設定 ====================//
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
getPrevFullRouteFromDB($prev_network_list);
// ログ出力
showLog('変更後データの読み込み完了');

//==================== RIPEから変更後の経路情報を取得してbgpdump形式に変換 ====================//
// このプログラムはcronによってUTC 1:00（JCT 10:00）に起動する

// RIPEのURLを作成（00:00分のデータ）
$next_ts = time();
$prev_ts = $next_ts - 60*60*24;
$prev_dl_filename = 'bview.'.date('Ymd', $prev_ts).'.0000.gz';
$next_dl_filename = 'bview.'.date('Ymd', $next_ts).'.0000.gz';
$prev_bgpdump_filename = date('Ymd', $prev_ts).'_0000.bgpdump.txt';
$next_bgpdump_filename = date('Ymd', $next_ts).'_0000.bgpdump.txt';

// DLして変換
if(downloadFile('http://data.ris.ripe.net/rrc00/'.date('Y.m', $next_ts).'/'.$next_dl_filename, DIR_RIPE_DL.$next_dl_filename)){
	// ログ出力
	showLog('変更後データのダウンロード完了');
	// 変換処理
	shell_exec('/usr/bin/python mrt2bgpdump.py '.DIR_RIPE_DL.$next_dl_filename.' > '.DIR_RIPE_BGPDUMP.$next_bgpdump_filename);
	// ログ出力
	showLog('変更後データのbgpdumpへの変換完了');
}else{
	exit('後日データのダウンロードに失敗しました');
}
//==================== 変更後のデータをそれぞれ読み込んで配列に格納 ====================//
getFullRouteFromBgpdump(DIR_RIPE_BGPDUMP.$next_bgpdump_filename, $next_network_list);
// ログ出力
showLog('変更後データの読み込み完了');

// 個人メモ：変更後情報を読み込んだあとは，衝突検知の処理と，変更前経路取得・変更検知の処理は並列で動かせるはず
// 変更情報を抽出
$update_route = detectUpdate($prev_network_list, $next_network_list);

// IPの重複確認を総当たりで行う
$conflict_route = detectConflict($next_network_list);
// ログ出力
showLog('衝突検知完了');

// Mis-Originationの可能性がある経路をフィルタ

// 出力（MySQL）


// 出力（Slack，owncloudなど）

// 後処理（生データを圧縮・作業ファイルを削除）

?>
