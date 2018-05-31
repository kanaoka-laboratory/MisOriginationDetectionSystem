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

//==================== RIPEから変更前（＝前日）と変更後（＝今日）の経路情報を取得してbgpdump形式に変換 ====================//
// このプログラムはcronによってUTC 1:00（JCT 10:00）に起動する

// RIPEのURLを作成（00:00分のデータ）
$next_ts = time();
$prev_ts = $next_ts - 60*60*24;
$prev_dl_filename = 'bview.'.date('Ymd', $prev_ts).'.0000.gz';
$next_dl_filename = 'bview.'.date('Ymd', $next_ts).'.0000.gz';
$prev_bgpdump_filename = date('Ymd', $prev_ts).'_0000.bgpdump.txt';
$next_bgpdump_filename = date('Ymd', $next_ts).'_0000.bgpdump.txt';

// 変更前データは前日に変更後データとしてDL済みのはずだが，ない場合はDLして変換
if(!file_exists(DIR_RIPE_DL.$prev_dl_filename)){
	if(downloadFile('http://data.ris.ripe.net/rrc00/'.date('Y.m', $prev_ts).'/'.$prev_dl_filename, DIR_RIPE_DL.$prev_dl_filename)){
		// ログ出力
		showLog('更新前データのダウンロード完了');
		// 変換処理
		shell_exec('/usr/bin/python mrt2bgpdump.py '.DIR_RIPE_DL.$prev_dl_filename.' > '.DIR_RIPE_BGPDUMP.$prev_bgpdump_filename);
		// ログ出力
		showLog('更新前データのbgpdumpへの変換完了');
	}else{
		exit('前日データのダウンロードに失敗しました');
	}
	
}

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
//==================== 変更前と後のデータをそれぞれ読み込んで配列に格納 ====================//
// 格納先配列の宣言
$prev_network_list = array();
$next_network_list = array();
// データの読み込み
getFullRouteFromBgpdump(DIR_RIPE_BGPDUMP.$prev_bgpdump_filename, $prev_network_list);
// ログ出力
showLog('変更後データの読み込み完了');
getFullRouteFromBgpdump(DIR_RIPE_BGPDUMP.$next_bgpdump_filename, $next_network_list);
// ログ出力
showLog('変更後データの読み込み完了');


// IPの重複確認を総当たりで行う
detectConflict($next_network_list);
// ログ出力
showLog('衝突検知完了');

// Mis-Originationの可能性がある経路をフィルタ

// 出力（ファイル）

// 出力（Slack，owncloudなど）

// 後処理（生データを圧縮・作業ファイルを削除）

?>
