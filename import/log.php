<?php
//==================== 時間測定用 ====================//
$debug_program_start_time = 0;
function resetProgramTimer($message = null){
	global $debug_program_start_time;
	$debug_program_start_time = time();
	if($message !== null) showLog($message);
}
function showLog($message){
	global $debug_program_start_time;
	$spent_time = date('H:i:s', time()-$debug_program_start_time);
	echo str_replace(array('%%','=='), array($spent_time,$message),
			date(str_replace(array('spent_time','message'), array('%%','=='), LOG_FORMAT)));
	echo PHP_EOL;
}
?>