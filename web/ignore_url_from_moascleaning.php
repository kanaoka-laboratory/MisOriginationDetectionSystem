<?php
// POSTのときだけ実行
if($_SERVER['REQUEST_METHOD']!=='POST') exit('{"error": "true"}');

//==================== 初期設定 ====================//
chdir('..');
// 設定ファイル読み込み
require_once('config.php');
// 関数などの読み込み
foreach(glob('import/*.php') as $filename) require_once($filename);
$mysqli = new mymysqli();
//================================================//

// jsonとして出力する
header('content-type: application/json; charset=utf-8');
$json = array('error'=>false);

try{
	// asn
	if(!isset($_POST['url'])) throw new Exception("url not set");
	$url = $_POST['url'];
    
    // 入力値をjsonに保存
	$json['url'] = $url;
	$json['query'] = "insert into MOASCleaningIgnore (url) values ('$url')";
    // 実行
    $mysqli->query($json['query']);
	// エラー処理
	if($mysqli->error) throw new Exception($mysqli->error);
}catch(Exception $e){
	$json['error'] = $e->getMessage();
}

// $json['error'] = 'debug';
echo json_encode($json);
?>
