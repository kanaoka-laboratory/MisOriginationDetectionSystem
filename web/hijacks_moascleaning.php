<?php
ini_set("display_errors", "On");
//==================== 初期設定 ====================//
chdir("..");
// 設定ファイル読み込み
require_once("config.php");
// 関数などの読み込み
foreach(glob("import/*.php") as $filename) require_once($filename);
$mysqli = new mymysqli();
//================================================//

//------------ パラメタ処理 ------------//
// ページの処理
$item_per_page = 500;
$page = isset($_GET["page"])? (int)$_GET["page"]: 1;
$page_max = ceil(($mysqli->query("select count(*) from SuspiciousAsnSet where 0<conflict_type and conflict_type<100")->fetch_assoc()["count(*)"])/$item_per_page);
if($page<0) $page=1;
if($page>$page_max) $page = $page_max;
$page_html = "<div style='text-align:center;'>";
for($i=1;$i<=$page_max;$i++){
	if($i===$page) $page_html .= " $i ";
	else $page_html .= " <a href='?page=$i'>$i</a> ";
}
$page_html .= "</div>";
?>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<title>MOASCleaning</title>
	<link rel="stylesheet" href="_/mods.css">							<!-- CSS: MODS標準 -->
	<!-- <link rel="stylesheet" href="_/<?=basename(__FILE__,".php")?>.css">	<!-- CSS: 専用CSS -->
	<link rel="stylesheet" href="_/hijacks.css">	<!-- CSS: 専用CSS -->
</head>
<body>
<div id="container">
	<!-- header -->
	<?php require_once "web/_/header.php";?>
	
	<!-- main-contents -->
	<div id="main-contents">
		<!-- contents(-shadow) -->
		<div id="contents">
			<!--=*=*=*=*=*=*=*=*=*= main(rightside) =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=-->
			<div id="main">
<h1>Title</h1>
<!-- main-body -->
<div class="main-body">
<p>	whitelist：<br>
	10: その他<br>
	11: 同じ組織<br>
	12: Akamai<br>
	13: US_DoD</p>

<?=$page_html?>
<table id="hijack_list">
	<?php
	//------------ th行作成 ------------//
	$th = "<tr class='whitelist'>".
		"<th style='width:40px;'>conf<br>_type".
		"<th style='width:160px;'>ip_prefix".
		"<th style='width:160px;'>conf_ip_prefix".
		"<th style='width:80px;'>asn".
		"<th style='width:240px;'>conf_asn".
		"<th style='width:64px;'>asn_cc".
		"<th style='width:180px;'>conflict_asn_cc".
		"<th style='width:240px;'>asn_whois".
		"<th style='width:480px;'>conflict_asn_whois".
		"<th style='width:130px;'>date_detection".
		"<th style='width:65px;'>prefix_set<br>_count".
		"<th style='width:65px;'>update<br>_count".
		"<th style='width:150px;'>whitelist";

	//------------ MOASCleaningListを取得 ------------//
	$limit_from = ($page-1)*$item_per_page;
	$query = "select * from SuspiciousAsnSet as t1 left join ".
			"(select suspicious_id, count(suspicious_id) as prefix_set_count, sum(count) as update_count, ip_prefix, conflict_ip_prefix ".
			"from PrefixConflictedUpdate group by suspicious_id) as t2 ".
			"on t1.suspicious_id=t2.suspicious_id ".
			"where 0<conflict_type and conflict_type<100 order by t1.suspicious_id desc limit $limit_from, $item_per_page";
	$result = $mysqli->query($query);

	//------------ 1行ずつ処理 ------------//
	for($i=0;$row = $result->fetch_assoc();$i++){
		if($i%50===0) echo $th;
		// 行を分割
		echo ($row["conflict_type"]>=10? "<tr class='whitelist'>": "<tr>"),
			"<td name='conflict_type'>", $row["conflict_type"],
			"<td>", $row["ip_prefix"],
			"<td>", $row["conflict_ip_prefix"],
			"<td>", $row["asn"],
			"<td>", str_replace("/", ", ", $row["conflict_asn"]),
			"<td>", str_replace(array("-X","-P"), array("unknown","Private"), $row["asn_cc"]),
			"<td>", str_replace(array("-X","-P", "/"), array("unknown","Private",", "), $row["conflict_asn_cc"]),
			"<td>", $row["asn_whois"],
			"<td>", str_replace("/", ", ", $row["conflict_asn_whois"]),
			"<td>", $row["date_detection"],
			"<td>", $row["prefix_set_count"],
			"<td>", $row["update_count"],
			"<td data-id='{$row["suspicious_id"]}' data-asn='{$row["asn"]}' data-conf_asn='{$row["conflict_asn"]}'>".
				"<select>".
					"<option value='10'>10:その他<option value='11'>11:同一組織<option value='12'>12:Akamai".
					"<option value='13'>13:US DoD<option value='14'>14:(Empty)<option value='15'>15:(Empty)".
					"<option value='16'>16:(Empty)<option value='17'>17:(Empty)<option value='18'>18:(Empty)".
					"<option value='1'>1:suspicious".
				"</select><input class='add_whitelist' type='button' value='追加'>";
	}

	?>
</table>
<?=$page_html?>
</div>
			<!--=*=*=*=*=*=*=*=*=*= end of main(rightside) =*=*=*=*=*=*=*=*=*=*=*=*=*=*=-->
			</div>
			<!-- footer -->
			<?php require_once "web/_/footer.php";?>
		<!-- end of contents -->
		</div>
	<!-- end of main-contents -->
	</div>
</div>
<script src="_/jquery-3.3.1.min.js"></script>				<!-- JavaScript: jQuery -->
<script src="_/mods.js" charset="UTF-8"></script>							<!-- JavaScript: MODS標準 -->
<script src="_/hijacks.js" charset="UTF-8"></script>	<!-- JavaScript: 専用JS -->
</body>
</html>
