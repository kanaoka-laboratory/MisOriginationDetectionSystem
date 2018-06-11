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
$mysql_datetime = '2018-05-28 00:00:00';

//==================== グローバル変数の宣言 ====================//
// MySQLへのコネクション
$mysqli = new mymysqli();
// フルルートの格納先配列
$prev_network_list = array();
$next_network_list = array();

//==================== MySQLから変更前データを取得 ====================//
// 更新前データはMySQLから読み込む
getPrevFullRouteFromDB($prev_network_list);
showLog('変更前データの読み込み完了');


//========================================//
// var_dump(detectIpPrefixOverlap("172.18.32.0/20", "172.18.11.0/24"));

// var_dump(downloadFile('http://kougen.iobb.net/index.php', 'index.php'));


// function dumpMemory()
// {
//     static $initialMemoryUse = null;

//     if ( $initialMemoryUse === null )
//     {
//         $initialMemoryUse = memory_get_usage();
//     }

//     var_dump(number_format(memory_get_usage() - $initialMemoryUse));
// }
// dumpMemory();
// $arr = array();
// for($i=0;$i<100000;$i++) $arr[$i] = "HelloWorld";
// dumpMemory();
// $arr = array();
// 'VdumpMemory();





//==================== 変更後のデータをそれぞれ読み込んで配列に格納 ====================//
getFullRouteFromBgpdump(DIR_RIPE_BGPDUMP.'20180528_0000.bgpdump.test.txt', $next_network_list);
// getFullRouteFromBgpdump(DIR_RIPE_BGPDUMP.'update.txt', $network_list);
showLog('変更後データの読み込み完了');

//==================== 変更検出 ====================//
// 個人メモ：変更後情報を読み込んだあとは，衝突検知の処理と，変更前経路取得・変更検知の処理は並列で動かせるはず
// 変更情報を抽出
$update_route = detectUpdate($prev_network_list, $next_network_list);
var_dump($update_route);
showLog('変更情報抽出完了');
// 変更情報をMySQLに保存
insertFullRouteUpdateToDB($update_route, $next_network_list);
showLog('変更情報保存完了');

//==================== 衝突検出 ====================//
// IPの重複確認を総当たりで行う
$conflict_route = detectConflict($next_network_list);
showLog('衝突検知完了');
var_dump($conflict_route);
// // 衝突情報をMySQLに保存
// insertConflictToDB($conflict_route);


?>
