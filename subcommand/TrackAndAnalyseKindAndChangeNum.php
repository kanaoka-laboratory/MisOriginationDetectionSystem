<?php
require_once("subcommand/TrackOriginExactChangedPrefix.php");
require_once("subcommand/TrackOriginIncludeChangedPrefix.php");
require_once("subcommand/AnalyseKindAndChangeNum.php");

function TrackAndAnalyseKindAndChangeNum($start, $end){
	// 引数チェック
	$start_ts = strtotime($start);
	$end_ts = strtotime($end);
	if($start_ts>$end_ts) showLog('終了日時が開始日時より前です', true);
	
	showLog('TrackOriginExactChangedPrefixの実行');
	$filename = TrackOriginExactChangedPrefix($start, $end);
	
	showLog('結果の統計');
	AnalyseKindAndChangeNum($filename);
	
	showLog('TrackOriginIncludeChangedPrefix');
	$filename = TrackOriginIncludeChangedPrefix($start, $end);
	
	showLog('結果の統計');
	AnalyseKindAndChangeNum($filename);
}
?>