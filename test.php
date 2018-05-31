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



// 経路情報の取得
getFullRouteFromBgpdump(DIR_RIPE_BGPDUMP.'20180525_0000.bgpdump.txt', $network_list);
// ログ出力
showLog('bgpdumpの読み込み完了');

// 衝突検知
detectConflict($network_list);
// ログ出力
showLog('衝突検知完了');


?>
