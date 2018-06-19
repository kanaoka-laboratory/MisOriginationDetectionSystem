<?php
// 例外処理
if(empty($_GET['asn'])){ header('Location: index.php'); exit; }

// DBの利用準備
require_once('mysqli.php');
$mysqli = new mymysqli();

// AS番号の取得
$asn = (int)$_GET['asn'];
?>

<html lang=“ja”>
<head>
	<meta charset=“UTF-8”>
	<title>AS<?=$asn?>の情報</title>
</head>
<body>
<div id=“container”>
	<h1>AS<?=$asn?></h1>
	<h2>v4</h2>
	<h3>経路広告履歴</h3>
	<?php
	echo '<table><tr><th>変更日時<th>経路情報';
	$result = $mysqli->query("select date_update,route_updated from DetectedUpdateHistoryv4 where asn=$asn order by date_update desc");
	while($row = $result->fetch_assoc()){
		echo "<tr><td>{$row['date_update']}<td>".str_replace(',', '<br>', $row['route_updated']);
	}
	echo '</table>';
	?>
	<h2>v6</h2>
	<h3>経路広告履歴</h3>
	<?php
	echo '<table><tr><th>変更日時<th>経路情報';
	$result = $mysqli->query("select date_update,route_updated from DetectedUpdateHistoryv6 where asn=$asn order by date_update desc");
	while($row = $result->fetch_assoc()){
		echo "<tr><td>{$row['date_update']}<td>".str_replace(',', '<br>', $row['route_updated']);
	}
	echo '</table>';
	?>
	
</div>
</body>
</html>
