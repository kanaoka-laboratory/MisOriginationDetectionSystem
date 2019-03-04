<?php
//==================== 初期設定 ====================//
chdir('..');
// 設定ファイル読み込み
require_once('config.php');
// 関数などの読み込み
foreach(glob('import/*.php') as $filename) require_once($filename);
$mysqli = new mymysqli();
//================================================//

//==================== URLパラメタ処理 ====================//
// 日付
$ts = isset($_GET['date'])? strtotime($_GET['date']): time();

?>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<title>#####</title>
	<link rel="stylesheet" href="_/mods.css">							<!-- CSS: MODS標準 -->
	<link rel="stylesheet" href="_/<?=basename(__FILE__,'.php')?>.css">	<!-- CSS: 専用CSS -->
</head>
<body>
<div id="container">
	<!-- header -->
	<?php require_once 'web/_/header.php';?>
	
	<!-- main-contents -->
	<div id="main-contents">
		<!-- contents(-shadow) -->
		<div id="contents">
			<!--=*=*=*=*=*=*=*=*=*= main(rightside) =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=-->
			<div id="main">
<h1>Title</h1>
<h2><?=date('Y-m-d', $ts)?></h2>
<form method="get" action=""><input type="text" name="date" placeholder="2019-01-01"><input type="submit" value="　GO　"></form>
<!-- main-body -->
<div class="main-body">
<?php
// 1日（1440分）
$Y_m = date('Y.m',$ts);
$ts_max = $ts+60*60*24;
for (; $ts<$ts_max; $ts+=5*60){
	$Ymd_Hi = date('Ymd.Hi',$ts);
	$filename = FILTER_SUSPICIOUS_ADVERTISEMENT."okada1101/$Y_m/$Ymd_Hi.csv";
	// ファイルが存在しない場合スキップ
	if(!is_file($filename)) continue;

	// テーブルの表示
	echo'<table><caption>'.date('H:i', $ts).'</caption>';
	// 1行ずつ読み込み
	$fp = fopen($filename, 'r');
	echo '<tr><th>whitelist<th>'.str_replace(',', '<th>', fgets($fp));
	while (($row = fgetcsv($fp))!==false){
		// 行を分割
		list($adv_type, $conf_type, $ip_prefix, $conf_ip_prefix, $asn, $conf_asn, $asn_cc, $conf_asn_cc) = $row;
		if($row[1]==='1'){
			if($result = $mysqli->query("select conflict_type from ConflictAsnWhiteList where asn='$asn' and conflict_asn='$conf_asn'")->fetch_assoc())
				echo "<tr class='whitelist'><td><input type='number' min='10' max='100' value='{$result['conflict_type']}' disabled><input type='checkbox' data-asn='$asn' data-conf_asn='$conf_asn' checked>";
			else
				echo "<tr><td><input type='number' min=10 max=100 value=10><input type='checkbox' data-asn='$asn' data-conf_asn='$conf_asn'>";

		}elseif($row[1]>=10){
			echo "<tr class='whitelist'><td><input type='number' min='10' max='100' value='$conf_type' disabled><input type='checkbox' data-asn='$asn' data-conf_asn='$conf_asn' checked>";
		}else{
			continue;
		}

		// ハイジャック判定orホワイトリスティングされているイベントだけを表示
		echo '<td>'.implode('<td>', $row);
	}
	fclose($fp);

	echo'</table>';
}
?>
</div>
			<!--=*=*=*=*=*=*=*=*=*= end of main(rightside) =*=*=*=*=*=*=*=*=*=*=*=*=*=*=-->
			</div>
			<!-- footer -->
			<?php require_once 'web/_/footer.php';?>
		<!-- end of contents -->
		</div>
	<!-- end of main-contents -->
	</div>
</div>
<script src="_/jquery-3.3.1.min.js"></script>				<!-- JavaScript: jQuery -->
<script src="_/mods.js"></script>							<!-- JavaScript: MODS標準 -->
<script src="_/<?=basename(__FILE__,'.php')?>.js"></script>	<!-- JavaScript: 専用JS -->
</body>
</html>
