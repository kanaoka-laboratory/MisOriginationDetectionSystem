<?php
//==================== mymysqli class ====================//
class mymysqli extends mysqli{
	function __construct(){
		// MySQLの資格情報を取得
		$filename='mysql_credential.ini';
		if(!is_readable($filename)){
			showLog('MySQLの資格情報を読み込めません');
			exit;
		}
		list($user, $pass) = explode("\n", file_get_contents($filename));
		
		//MySQLへ接続
		parent::__construct('localhost', $user, $pass, 'MisOriginationDetectionSystem');
		if($this->connect_error){
			showLog('MySQLの接続に失敗しました：'.$this->connect_error);
			exit;
		}

		//文字コード指定
		$this->set_charset('utf8mb4');
	}
}

//==================== functions ====================//
//==================== RouteInfoV4(6)テーブルから，前回のフルルート情報を取得する ====================//
// network_list: 格納先の配列（参照渡し）
function getPrevFullRouteFromDB(&$network_list){
	// 代入先を初期化
	$network_list = array('v4'=>array(), 'v6'=>array());

	// MySQLから変更前情報を取得して$network_listに格納
	global $mysqli;
	foreach(array('v4','v6') as $ip_proto){
		$result = $mysqli->query('select id,asn,route from RouteInfo'.$ip_proto);
		while($row = $result->fetch_assoc()){
			$network_list[$ip_proto][$row['asn']][$row['route']] = (int)$row['id']);
		}
		$result->close();
	}
}

?>