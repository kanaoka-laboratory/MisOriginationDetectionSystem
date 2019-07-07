<?php
require_once('config.php');
$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, 'MODS2018');

//==================== 国の距離一覧を取得 ====================//
$country_distance = array();
$result = $mysqli->query("select * from CountryDistance");
while($row = $result->fetch_assoc()){
	$country_distance[$row["cc1"]][$row["cc2"]] = (int)$row["distance"];
}
$result->close();

//==================== データを取得 ====================//
$suspicious_ids = array("2"=> array(), "3"=> array(), "4"=>array());
$query = "select * from SuspiciousAsnSet as t1 left join ".
			"(select suspicious_id, count(suspicious_id) as prefix_set_count, sum(count) as update_count, ip_prefix, conflict_ip_prefix ".
			"from PrefixConflictedUpdate group by suspicious_id) as t2 on t1.suspicious_id=t2.suspicious_id ".
			"where conflict_type=1";
$result = $mysqli->query($query);
//------------ 1行ずつ処理 ------------//
while($row = $result->fetch_assoc()){
	// 距離が2,3,4でないものはスキップ
	$distance = isset($country_distance[$row["asn_cc"]][$row["conflict_asn_cc"]])? $country_distance[$row["asn_cc"]][$row["conflict_asn_cc"]]: null;
	if($distance!==2 && $distance!==3 && $distance!==4) continue;
	// 配列にデータを入れる
	$suspicious_ids[$distance][] = $row["suspicious_id"];
}

// 配列をシャッフルして先頭（ランダム）100件を抽出
shuffle($suspicious_ids[2]);
shuffle($suspicious_ids[3]);
shuffle($suspicious_ids[4]);
echo  implode(",", array_slice($suspicious_ids[2], 0, 100)) . PHP_EOL;
echo  implode(",", array_slice($suspicious_ids[3], 0, 100)) . PHP_EOL;
echo  implode(",", array_slice($suspicious_ids[4], 0, 100)) . PHP_EOL;

// 配列をコピー
foreach(array(2,3,4) as $distance){
	for($i=0; $i<100; $i++){
		$mysqli->query("insert into Random100 select * from SuspiciousAsnSet where suspicious_id=".$suspicious_ids[$distance][$i]);
	}
}
?>
