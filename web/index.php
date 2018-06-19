<?php
// DBの利用準備
require_once('mysqli.php');
$mysqli = new mymysqli();
?>

<html lang=“ja”>
<head>
	<meta charset=“UTF-8”>
	<title></title>
</head>
<body>
<div id=“container”>
	<h2>v4</h2>
	<?php
	// 日付ごとの衝突組数
	echo '<table><tr><th>日時<th>衝突組数';
	$result = $mysqli->query('select count(*),date_conflict from ConflictHistoryv4 group by date_conflict');
	while($row = $result->fetch_assoc()){
		echo "<tr><td>{$row['date_conflict']}<td>{$row['count(*)']}";
	}
	echo '</table>';
	// 最新状態での，ASごとの衝突しているAS数
	$conflict_as_list=array();
	$result = $mysqli->query('select asn1,asn2 from ConflictHistoryv4 where date_conflict=(select value from MetaInfo where name="latest_conflict_detection")');
	while($row = $result->fetch_assoc()){
		if(isset($conflict_as_list[$row['asn1']])) $conflict_as_list[$row['asn1']]++;
		else $conflict_as_list[$row['asn1']]=1;
		if(isset($conflict_as_list[$row['asn2']])) $conflict_as_list[$row['asn2']]++;
		else $conflict_as_list[$row['asn2']]=1;
	}
	ksort($conflict_as_list);
	echo '<table><tr><th>AS番号<th>衝突AS数';
	foreach($conflict_as_list as $asn=>$count){
		echo "<tr><td>$asn<td>$count";
	}
	echo '</table>';
	?>

	<h2>v6</h2>
	<?php
	// 日付ごとの衝突組数
	echo '<table><tr><th>日時<th>衝突組数';
	$result = $mysqli->query('select count(*),date_conflict from ConflictHistoryv6 group by date_conflict');
	while($row = $result->fetch_assoc()){
		echo "<tr><td>{$row['date_conflict']}<td>{$row['count(*)']}";
	}
	echo '</table>';
	// 最新状態での，ASごとの衝突しているAS数
	$conflict_as_list=array();
	$result = $mysqli->query('select asn1,asn2 from ConflictHistoryv6 where date_conflict=(select value from MetaInfo where name="latest_conflict_detection")');
	while($row = $result->fetch_assoc()){
		if(isset($conflict_as_list[$row['asn1']])) $conflict_as_list[$row['asn1']]++;
		else $conflict_as_list[$row['asn1']]=1;
		if(isset($conflict_as_list[$row['asn2']])) $conflict_as_list[$row['asn2']]++;
		else $conflict_as_list[$row['asn2']]=1;
	}
	ksort($conflict_as_list);
	echo '<table><tr><th>AS番号<th>衝突AS数';
	foreach($conflict_as_list as $asn=>$count){
		echo "<tr><td>$asn<td>$count";
	}
	echo '</table>';
	?>
</div>
</body>
</html>
