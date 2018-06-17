<?php
//==================== mymysqli class ====================//
class mymysqli extends mysqli{
	function __construct(){
		// MySQLの資格情報を取得
		$filename='mysql_credential.ini';
		if(!is_readable($filename)){
			exit('MySQLの資格情報を読み込めません'.PHP_EOL);
		}
		list($user, $pass) = explode("\n", file_get_contents($filename));
		
		//MySQLへ接続
		parent::__construct('localhost', $user, $pass, 'MisOriginationDetectionSystem');
		if($this->connect_error){
			exit('MySQLの接続に失敗しました：'.$this->connect_error);
		}

		//文字コード指定
		$this->set_charset('utf8mb4');
	}

	function query($query, $resultmode = NULL){
		$result = parent::query($query, $resultmode);
		if($this->errno>0){
			echo $this->errno.': '.$this->error.' ('.$query.')'.PHP_EOL;
		}
		return $result;
	}
}

//==================== functions ====================//
//==================== RouteInfoV4(6)テーブルから，前回のフルルート情報を取得する ====================//
// network_list: 格納先の配列（参照渡し）
function getPrevFullRouteFromDB(&$network_list){
	global $mysqli;
	
	// 代入先を初期化
	$network_list = array('v4'=>array(), 'v6'=>array());

	// MySQLから変更前情報を取得して$network_listに格納
	foreach(array('v4','v6') as $ip_proto){
		$result = $mysqli->query('select id,asn,route from RouteInfo'.$ip_proto);
		while($row = $result->fetch_assoc()){
			$network_list[$ip_proto][$row['asn']][$row['route']] = (int)$row['id'];
		}
		$result->close();
	}
}

//==================== フルルートから抽出した変更情報をDBに反映 ====================//
function insertFullRouteUpdateToDB($update_list, $next_network_list){
	global $mysqli;
	global $mysql_datetime;

	foreach(array('v4','v6') as $ip_proto){
		//------------ delete ------------//
		$mysqli->begin_transaction();
		foreach($update_list[$ip_proto]['delete'] as $route){
			$mysqli->query("delete from RouteInfo$ip_proto where asn={$route[UPDATE_ROUTE_ASN]} and route='{$route[UPDATE_ROUTE_ROUTE]}'");
		}
		$mysqli->commit();
		
		//------------ insert(add) ------------//
		// v4のinsert時にint型のip_minとip_maxをクォートで囲うことになるが，動作に問題はない
		$mysqli->begin_transaction();
		foreach($update_list[$ip_proto]['add'] as $route){
			// (id, asn, route, ip_min, ip_max)
			$mysqli->query("insert into RouteInfo$ip_proto values".
					"(null, {$route[UPDATE_ROUTE_ASN]}, '{$route[UPDATE_ROUTE_ROUTE]}', '{$route[UPDATE_ROUTE_IP_MIN]}', '{$route[UPDATE_ROUTE_IP_MAX]}')");
		}
		$mysqli->commit();
		
		//------------ update ------------//
		$mysqli->begin_transaction();
		foreach($update_list[$ip_proto]['update'] as $asn => $null){
			// 広告IPが違う値に変更された
			if(isset($next_network_list[$ip_proto][$asn])){
				$united_route = implode(',', array_keys($next_network_list[$ip_proto][$asn]));
				// (id, asn, date_update, UPDATE_ROUTEd)
				$mysqli->query("insert into DetectedUpdateHistory$ip_proto values(null, $asn, '$mysql_datetime', '$united_route')");
			}// そのASが消えた（経路の広告を1つもしなくなった）
			else{
				$mysqli->query("insert into DetectedUpdateHistory$ip_proto values(null, $asn, '$mysql_datetime', null)");
			}
		}
		$mysqli->commit();
	}
}

//==================== 衝突情報をDBに保存 ====================//
function insertConflictToDB($conflict_list){
	global $mysqli;
	global $mysql_datetime;

	foreach(array('v4','v6') as $ip_proto){
		$mysqli->begin_transaction();
		foreach($conflict_list[$ip_proto] as $as_set){
			// (conflict_id, asn1, asn2, date_conflict)
			$mysqli->query("insert into ConflictHistory$ip_proto values(null, $as_set, '$mysql_datetime')");
		}
		$mysqli->commit();
	}
}

//==================== 衝突の例外をDBに保存 ====================//
function insertConflictExceptionToDB($conflict_exception_list){
	global $mysqli;
	global $mysqli_datetime;
	
	$mysqli->begin_transaction();
	foreach($conflict_exception_list as $conflict_exception){
		// (exception_id, prefix, date)
		$mysqli->query("insert into ConflictExceptionRoute values(null, '{$conflict_exception[0]}', '$mysqli_datetime')");
		$exception_id = $mysqli->insert_id;
		foreach($conflict_exception[1] as $asn){
			// (exception_id, asn)
			$mysqli->query("insert into ConflictExceptionAsn values($insert_id, $asn)");
		}
	}
	$mysqli->commit();
}
?>
