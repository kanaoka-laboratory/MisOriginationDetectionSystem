<?php
//==================== このファイルはimport内のfunctionなどの動作確認用です ====================//
//==================== 初期設定 ====================//
// プログラムのカレントディレクトリを変更
chdir(dirname(__DIR__, 1));
// 設定ファイル読み込み
require_once('config.php');
// 関数などの読み込み
foreach(glob('import/*.php') as $filename) require_once($filename);
// MySQL接続
$mysqli = new mymysqli();
//------------ subcommandのインポート（必要なら） ------------//
// require_once("subcommand/$subcommand.php");
echo '開始'.PHP_EOL;
//==================== ここから ====================//

$network_list = getFullRouteFromBgpdump($argv[1]);
file_put_contents($argv[2], serialize($network_list));



//==================== ゴミ置き場 ====================//
exit ('終了'.PHP_EOL);
//------------ ここから ------------//

?>
