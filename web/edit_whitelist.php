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
$json = array('error'=>false);

try{
	// asn
	if(!isset($_POST['asn'])) throw new Exception("asn not set");
	$asn = (int)$_POST['asn'];
	// conf_asn
	if(!isset($_POST['conf_asn'])) throw new Exception("conf_asn not set");
	if(is_array($_POST["conf_asn"])) $conf_asn_list = $_POST['conf_asn'];
	else $conf_asn_list = array($_POST['conf_asn']);
	// conflict_type
	if(!isset($_POST['conflict_type'])) throw new Exception("conflict_type not set");
	$conflict_type = (int)$_POST['conflict_type'];
	// two_way（'true', 'false', '0', '1'あたりの値で受け取る）
	$two_way = false;
	if(isset($_POST["two_way"]) && $_POST["two_way"]!=="false")
		$two_way = (boolean)$_POST['two_way'];

	// 入力値をjsonに保存
	$json['asn'] = $asn;
	$json['conf_asn'] = $conf_asn;
	$json['conflict_type'] = $conflict_type;
	$json['two_way'] = $two_way;
	$json['query'] = "";

	// conf_asn全てに対してforeach
	foreach($conf_asn_list as $conf_asn){
		// ホワイトリストに追加（ASを完全に信頼/危険視: AKAMAI，US_DoD，DDoS軽減，非存在AS）
		if(in_array($conflict_type, array(12,13,15,53), true)){
			$query = "insert into ConflictAsnWhiteList (conflict_type,asn,conflict_asn) values ($conflict_type,$asn,0)";
		}// ホワイトリストに追加
		elseif($conflict_type>1){
			$query = "insert into ConflictAsnWhiteList (conflict_type,asn,conflict_asn) values ($conflict_type,$asn,$conf_asn)";
			if($two_way) $query.=",($conflict_type,$conf_asn,$asn)";
		}// ホワイトリストの無効化
		elseif($conflict_type==1){
			$query = "update ConflictAsnWhiteList set disabled=current_timestamp where asn=$asn and conflict_asn=$conf_asn";
			if($two_way) $query.=" or asn=$conf_asn and conflict_asn=$asn";
		}
		$mysqli->query($query);
		$json['query'] .= $query.PHP_EOL;
	}

	if($mysqli->error) throw new Exception($mysqli->error);

	// ホワイトリストを再適用（suspicious_idがあるときは指定）
	ReApplyWhitelist(isset($_POST["suspicious_id"])?(int)$_POST["suspicious_id"]:null);
}catch(Exception $e){
	$json['error'] = $e->getMessage();
}

// $json['error'] = 'debug';
echo json_encode($json);
?>
