<?php
// プログラムのカレントディレクトリを変更
chdir(__DIR__);
// 現状の表示
$maintenance = trim(file_get_contents("maintenance.ini"));

$command = isset($argv[1])? $argv[1]: "";
switch($command){
//------------ 停止 ------------//
case "stop":
    if($maintenance==="true"){
        echo "すでにメンテナンスモードになっています" . PHP_EOL;
    }else{
        mods_stop();
    }
    break;

//------------ 開始 ------------//
case "start":
    if($maintenance!=="true"){
        echo "メンテナンスモードではありません" . PHP_EOL;
    }else{
        mods_start();
    }
    break;

//------------ リセット ------------//
case "reset":
    mods_stop();
    mods_start();
    break;

//------------ その他 ------------//
default:
    echo "----------------------------------" . PHP_EOL;
    echo "現在のメンテナンスモード：" . $maintenance . PHP_EOL;
    echo "----------------------------------" . PHP_EOL;
    echo "stop    現在実行中の処理をすべて終了し，メンテナンスモードに入る" . PHP_EOL;
    echo "start   メンテナンスモードを解除し，処理を再開する" . PHP_EOL;
    echo "reset   stop，startする（DBの不整合が解消される：不慮のOS再起動後等に実行）" . PHP_EOL;
}

function mods_stop(){
    // メンテナンスモードに入る
    file_put_contents("maintenance.ini", "true");
    echo "メンテナンスモードに入りました" . PHP_EOL;
    
    // 実行中のMODS/bgpscannerプロセスをすべて殺す
    $kill_cmd = 'ps aux | grep -e "MODS.php" -e "bgpscanner" | grep -v "grep" | grep -v "/bin/" | awk \'{print "kill", $2}\' | bash';
    shell_exec($kill_cmd);
    
    // DBの実行中フラグをすべてオフにする
    require_once("config.php");
    require_once("import/mysqli.php");
    $mysqli = new mymysqli();
    $mysqli->query("update CronProgress set processing=false");
    echo "すべての実行中の処理を終了しました" . PHP_EOL;
}

function mods_start(){
    file_put_contents("maintenance.ini", "false");
    echo "メンテナンスモードを解除しました" . PHP_EOL;
}