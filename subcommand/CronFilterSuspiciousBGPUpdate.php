<?php
require_once('subcommand/FilterSuspiciousBGPUpdate.php');
function CronFilterSuspiciousBGPUpdate($rc = null){
	if($rc!==null && !isset(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	global $mysqli;

	//------------ 現状を取得 ------------//
	$cron = $mysqli->query("select id,processing from CronProgress where cron='FilterSuspiciousBGPUpdate'")->fetch_assoc();
	if($cron["processing"]==true) showLog("他プロセスで実行中", true);
	
	//------------ 実行準備 ------------//
	$mysqli->query("update CronProgress set processing=true where id={$cron["id"]}");

	//------------ 実行 ------------//
	FilterSuspiciousBGPUpdate($rc);

	//------------ 終了処理 ------------//
	$mysqli->query("update CronProgress set processing=false where id={$cron["id"]}");
}
?>
