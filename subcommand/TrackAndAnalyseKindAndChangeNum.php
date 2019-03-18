<?php
require_once("subcommand/TrackOriginExactChangedPrefix.php");
require_once("subcommand/TrackOriginExactChangedPrefix2.php");
require_once("subcommand/TrackOriginIncludeChangedPrefix.php");
require_once("subcommand/TrackOriginIncludeChangedPrefix2.php");
require_once("subcommand/AnalyseKindAndChangeNum.php");
function TrackAndAnalyseKindAndChangeNum($rc, $start, $end){
	// ver2（過去方向に1週間）
	if($end===null){
		showLog('TrackOriginExactChangedPrefix2の実行');
		$filename_exact = TrackOriginExactChangedPrefix2($rc, $start);
		showLog('TrackOriginIncludeChangedPrefix2の実行');
		$filename_include = TrackOriginIncludeChangedPrefix2($rc, $start);
	}else{
		showLog('TrackOriginExactChangedPrefixの実行');
		$filename_exact = TrackOriginExactChangedPrefix($rc, $start, $end);
		showLog('TrackOriginIncludeChangedPrefixの実行');
		$filename_include = TrackOriginIncludeChangedPrefix($rc, $start, $end);
	}
	
	showLog('ExactMatchの結果の統計');
	AnalyseKindAndChangeNum($filename_exact);
	
	showLog('IncludeMatchの結果の統計');
	AnalyseKindAndChangeNum($filename_include);
}
?>