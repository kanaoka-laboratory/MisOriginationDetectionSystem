<?php
//==================== 初期設定 ====================//
// プログラムのカレントディレクトリを変更
chdir(dirname(__FILE__));
// 設定ファイル読み込み
require_once('config.php');
// 関数などの読み込み
foreach(glob('import/*.php') as $filename) require_once($filename);

//==================== RIPEから変更前（＝前日）と変更後（＝今日）の経路情報を取得（mrt形式） ====================//
// このプログラムはcronによってUTC 1:00（JCT 10:00）に起動する
// TimezoneをUTCに設定
date_default_timezone_set('UTC');
// RIPEのURLを作成（00:00分のデータ）
$today_ts = time();
$yesterday_ts = $today_ts-60*60*24;
$prev_filename = 'bview.'.date('Ymd', $yesterday_ts).'.0000.gz';
$next_filename = 'bview.'.date('Ymd', $today_ts).'.0000.gz';
$prev_url = 'http://data.ris.ripe.net/rrc00/'.date('Y.m', $yesterday_ts).'/'.$prev_filename;
$next_url = 'http://data.ris.ripe.net/rrc00/'.date('Y.m', $today_ts).'/'.$next_filename;
// 変更前データは前日に変更後データとしてDL済みのはずだが，ない場合はDL
if(!file_exists(DIRNAME_RIPE_DL.$prev_filename))
	downloadFile($prev_url, $prev_filename);
// 作業フォルダにダウンロード
downloadFile($next_url, $next_filename);

//==================== mrtを変換（サードパーティーのpythonスクリプトを利用） ====================//


// 変更前と後のデータをそれぞれ読み込んで配列（？）に格納

// IPの重複確認を総当たりで行う

// Mis-Originationの可能性がある経路をフィルタ

// 出力（ファイル）

// 出力（Slack，owncloudなど）

// 後処理（生データを圧縮・作業ファイルを削除）

?>
