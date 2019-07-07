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
// ランダムに選択した国のホップ数が2,3,4の組み合わせ
$distance2 = array(54102,4428,13507,15619,29848,3657,35277,6823,26670,8725,57966,58013,57666,57391,21398,61226,57688,57482,2757,43311,44970,18860,280,29047,13819,23425,18137,58834,37479,56805,20589,38980,1609,14474,14533,44687,20925,25639,39601,26078,38262,40598,44480,14296,4017,57486,57551,56073,63397,4200,34129,36372,33538,16460,33932,46223,4664,32967,57663,12449,38615,60021,37081,63789,36040,29820,20612,45045,23233,15640,57936,41466,47476,3323,42754,4659,24076,61211,10249,5674,57657,12297,47925,57631,29890,57659,33321,2845,57396,1350,38384,57654,18166,15575,9744,25923,31353,59483,29746,33356);
$distance3 = array(59978,12553,34368,11081,41875,8471,44958,49668,16181,40753,15256,14349,9949,7976,42242,55662,16096,16635,13639,57082,15644,17747,52813,21662,32822,53810,26944,24358,5095,15596,35156,43234,24701,57097,15582,6570,18853,18768,2118,11655,64408,2150,11674,22551,1153,24137,45298,8466,55087,47956,15606,22291,54820,31406,17923,14231,41083,45332,51263,49470,18722,4019,63776,58025,54072,14730,14982,1100,5540,4958,47931,61203,28562,57419,11066,51794,8929,21352,39040,55032,25656,56308,8945,56773,33910,6400,24138,54154,65882,13435,63428,29801,40601,45833,4344,16989,57609,59553,34991,13079);
$distance4 = array(28010,9932,37757,15633,5607,25097,3920,21560,5678,18395,37753,29536,2158,17688,31886,22961,21443,37762,24522,10966,20985,39567,38667,6622,63781,63050,35637,489,55454,20306,33834,41729,57735,40301,15578,57926,106,54375,2159,25274,29021,34940,37754,29862,4339,39504,6146,15622,2078,15643,63051,34314,15625,27298,6792,55107,39051,2157,40660,31621,7702,18182,58028,61879,6387,38214,26282,4343,37403,52225,39128,15636,21587,37980,9516,39909,28009,40201,40756,31090,30052,8016,48337,55092,15620,22334,57969,29469,24733,25051,39033,55091,29056,55896,61219,27297,5259,28109,18685,17046);
// すべてをまとめてソート
$suspicious_ids = array_merge($distance2, $distance3, $distance4);
sort($suspicious_ids);
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
<table id="hijack_list">
	<?php
	//------------ th行作成 ------------//
	$th = "<tr class='whitelist'>".
		"<th style='width:40px;'>#".
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
		"<th style='width:195px;'>whitelist";

	//------------ データの取得 ------------//
	$query = "select * from SuspiciousAsnSet as t1 left join ".
			"(select suspicious_id, count(suspicious_id) as prefix_set_count, sum(count) as update_count, ip_prefix, conflict_ip_prefix ".
			"from PrefixConflictedUpdate group by suspicious_id) as t2 ".
			"on t1.suspicious_id=t2.suspicious_id where t1.suspicious_id in (" . implode(",", $suspicious_ids) . ") order by prefix_set_count desc";
	$result = $mysqli->query($query);
	echo "total count: ", $result->num_rows;
	//------------ 1行ずつ処理 ------------//
	for($i=0;$row = $result->fetch_assoc();$i++){
		if($i%50===0) echo $th;
		// 行を分割
		if($row["conflict_type"]>=50) echo "<tr class='blacklist'>";
		elseif($row["conflict_type"]>=10) echo "<tr class='whitelist'>";
		elseif($row["conflict_type"]==2) echo "<tr class='suspicious_checked'>";
		else echo "<tr class='suspicious'>";
		echo "<td>", $row["suspicious_id"],
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
					"<option value='13'>13:US DoD<option value='14'>14:近隣AS<option value='15'>15:DDoS軽減提供AS".
					"<option value='16'>16:DDoS軽減利用AS<option value='17'>17:友好AS<option value='18'>18:(Empty)".
					"<option value='19'>19:(Empty)<option value='20'>20:大学Project<option value='0'>----------------".
					"<option value='50'>50:malicious<option value='51'>51:IANA予約<option value='52'>52:4bitAS番号".
					"<option value='53'>53:(Empty)<option value='54'>54:(Empty)<option value='55'>55:(Empty)".
					"<option value='0'>----------------<option value='1'>1:suspicious<option value='2'>2:suspicious(checked)".
				"</select><input class='add_whitelist' type='button' value='追加'>";
	}

	?>
</table>
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
