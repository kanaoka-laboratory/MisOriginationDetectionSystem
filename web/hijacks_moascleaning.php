<?php
//==================== 初期設定 ====================//
chdir('..');
// 設定ファイル読み込み
require_once('config.php');
// 関数などの読み込み
foreach(glob('import/*.php') as $filename) require_once($filename);
$mysqli = new mymysqli();
//================================================//

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
<p>
<?php 
foreach(glob(FILTER_SUSPICIOUS_ADVERTISEMENT.'main/MOASCleaningList_*_whois.csv') as $filename){
	$filename = basename($filename, '.csv');
	echo "<a href='?file=$filename'>$filename</a><br>";
}
?>
</p>
<!-- main-body -->
<div class="main-body">
<p>	whitelist：<br>
	10: その他<br>
	11: US_DoD<br>
	12: Akamai</p>
<?php
if(isset($_GET['file'])){
	$filename = FILTER_SUSPICIOUS_ADVERTISEMENT."main/{$_GET['file']}.csv";
	if(is_file($filename)){
		$fp = fopen($filename, 'r');
		// タイトル行
		echo '<table><tr><th>whitelist<th>'.implode('<th>', fgetcsv($fp)).'<th>詳細';
		// 1行ずつ読み込み
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
<script src="_/hijacks.js"></script>	<!-- JavaScript: 専用JS -->
</body>
</html>
