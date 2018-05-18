<?php
//==================== 初期設定 ====================//
// プログラムのカレントディレクトリを変更
chdir(dirname(__FILE__));
// 設定ファイル読み込み
require_once('config.php');
// 関数などの読み込み
foreach(glob('import/*.php') as $filename) require_once($filename);

// var_dump(detectIpPrefixOverlap("172.18.32.0/20", "172.18.11.0/24"));

var_dump(downloadFile('http://kougen.iobb.net/index.php', 'index.php'));

?>
