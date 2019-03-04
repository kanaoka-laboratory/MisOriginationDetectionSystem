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
<h2>SubTitle</h2>
<!-- main-body -->
<div class="main-body">
	<p>テキスト</p>
</div>
			<!--=*=*=*=*=*=*=*=*=*= end of main(rightside) =*=*=*=*=*=*=*=*=*=*=*=*=*=*=-->
			</div>
			<!-- footer -->
			<?php require_once 'web/_/footer.php';?>
		<!-- end of contents(-shadow) -->
		</div>
	<!-- end of main-contents -->
	</div>
</div>
<script src="web/_/jquery-3.3.1.min.js"></script>				<!-- JavaScript: jQuery -->
<script src="web/_/mods.js"></script>							<!-- JavaScript: MODS標準 -->
<script src="web/_/<?=basename(__FILE__,'.php')?>.js"></script>	<!-- JavaScript: 専用JS -->
</body>
</html>
