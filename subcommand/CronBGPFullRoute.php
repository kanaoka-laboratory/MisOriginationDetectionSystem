<?php
require_once('subcommand/GetBGPFullRoute.php');

function CronBGPFullRoute($rc){
	if(!isset(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	global $mysqli;

	// 現状を取得
	$cron = $mysqli->query("select id,value,failed_count>=max_failed_count as last_exec,processing from CronProgress ".
																			"where cron='BGPFullRoute' and name='$rc'")->fetch_assoc();
	if($cron===null) showLog("指定されたルートコレクタは実行できません", true);
	if($cron["processing"]==true) showLog("他プロセスで実行中", true);

	// DLするファイルのタイムスタンプ作成，$dateを更新
	$date = date("Y-m-d H:i", strtotime($cron["value"]." +8 hours"));
	// $dateが今より未来なら終了
	if(time() < strtotime("$date UTC")) showLog("$rc: まだ次のフルルートがダンプされる時間ではありません", true);

	// 実行中フラグを立てる
	showLog("$rc: 実行開始");
	$mysqli->query("update CronProgress set processing=true where id={$cron["id"]}");

	// DL，bgpdump・PHPDataの抽出
	$error = GetBGPFullRoute($rc, $date);
	
	//成功
	if(empty($error)){
		$mysqli->query("update CronProgress set value='$date', date_success=current_timestamp(), failed_count=0, processing=false where id={$cron["id"]}");
		showLog("$rc: DL，bgpdump・PHPData抽出完了");
	}// 失敗
	elseif($cron["last_exec"]==false){
		$mysqli->query("update CronProgress set failed_count=failed_count+1, processing=false where id={$cron["id"]}");
		showLog("$rc: 失敗：{$error[0]}");
	}// 失敗（失敗が続いたためスキップする）
	else{
		$mysqli->query("update CronProgress set value='$date', failed_count=0, processing=false where id={$cron["id"]}");
		showLog("$rc: 失敗：{$error[0]}");
		showLog("$rc: 複数回失敗したため $date をスキップします");
	}
}
?>
