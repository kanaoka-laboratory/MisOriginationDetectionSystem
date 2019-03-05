<?php
// POSTのときだけ実行
if($_SERVER['REQUEST_METHOD']!=='POST') exit;

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
$json = array('error', false);

// asnを変換
$asn = isset($_POST['asn'])? (int)$_POST['asn']: 0;
$conf_asn = isset($_POST['conf_asn'])? (int)$_POST['conf_asn']: 0;
$whitelist = isset($_POST['whitelist'])? (int)$_POST['whitelist']: null;
$two_way = isset($_POST['two_way'])? (boolean)$_POST['two_way']: false;
// 値が不正
if($asn===0 || $conf_asn===0) exit(json_encode(array('error','invalid asn value')));
if($whitelist===null) exit(json_encode(array('error', 'invalid whitelist type')));
// 入力値をjsonに保存
$json['asn'] = $asn;
$json['conf_asn'] = $conf_asn;
$json['whitelist'] = $whitelist;
$json['two_way'] = $two_way;

// ホワイトリストに追加
if($whitelist>=10){
	$query = "insert into ConflictAsnWhiteList (conflict_type,asn,conflict_asn) values ($whitelist,$asn,$conf_asn)";
	if($two_way) $query.=",($whitelist,$conf_asn,$asn)";
}// ホワイトリストの無効化
else{
	$query = "update ConflictAsnWhiteList set disabled=current_timestamp where asn=$asn and conflict_asn=$conf_asn";
	if($two_way) $query.=" or asn=$conf_asn and conflict_asn=$asn";
}
$json['query'] = $query;
$mysqli->query($query);

if($mysqli->error) $json['error'] = $mysqli->error;

// $json['error'] = 'debug';
echo json_encode($json);
?>
