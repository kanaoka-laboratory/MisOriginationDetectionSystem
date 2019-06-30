<?php
require_once('subcommand/GetBGPUpdate.php');
require_once('subcommand/AnalyseBGPUpdate.php');
function CronBGPUpdate($rc){
	if(!isset(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	global $mysqli;


	//------------ 現状を取得 ------------//
	$cron = $mysqli->query("select id,value,value2,failed_count>=max_failed_count as last_exec,processing from CronProgress ".
																			"where cron='BGPUpdate' and name='$rc'")->fetch_assoc();
	if($cron===null) showLog("指定されたルートコレクタは実行できません", true);
	if($cron["processing"]==true) showLog("他プロセスで実行中", true);
	
	$date_extracted = $cron["value"];
	$date_completed = $cron["value2"];
	
	//------------ 実行準備 ------------//
	$ts_download = strtotime("$date_extracted +".UPDATE_INTERVAL[$rc]." minutes");
	$date = date("Y-m-d H:i", $ts_download);
	// $dateが今より未来なら終了
	if(time() < strtotime("$date UTC")) showLog("$rc: まだ次のフルルートがダンプされる時間ではありません", true);

	// 実行中フラグを立てる
	showLog("$rc: 実行開始");
	$mysqli->query("update CronProgress set processing=true where id={$cron["id"]}");

	//------------ DL，bgpdumpの抽出 ------------//
	$error = GetBGPUpdate($rc, $date);
	
	// 成功
	if(empty($error)){
		$mysqli->query("update CronProgress set value='$date',failed_count=0 where id={$cron["id"]}");
		showLog("$rc: DL，bgpdump抽出完了");
		//------------ 変更検出 ------------//
		// 変更検出を行うoldestなタイムスタンプを取得
		$ts = strtotime("$date_completed +".UPDATE_INTERVAL[$rc]." minutes");
		$ts_max = $ts_download;
		// 展開が終わってるlatestのフルルートの時間から$ts_maxを取得
		$row = $mysqli->query("select value2 from CronProgress where cron='BGPFullRoute' and name='$rc'")->fetch_assoc();
		$ts_fullroute_available = strtotime(($row? $row['value2']: "1970-01-01 UTC"). " +8 hours");
		
		// 変更検出するデータがない：$ts_fullroute_availableは$ts_maxより大きくなければ実行できない
		if($ts_fullroute_available <= $ts_max){
			showLog("変更検出の対象となるデータがありません");
			$mysqli->query("update CronProgress set processing=false where id={$cron["id"]}");
		}// 変更検出
		else{
			// $ts〜$ts_maxまでの変更検出，1ts毎にDBを更新
			for(; $ts<=$ts_max; $ts+=UPDATE_INTERVAL[$rc]*60){
				AnalyseBGPUpdate($rc, date('Y-m-d H:i', $ts));
				$mysqli->query("update CronProgress set value2='$date_max' where id={$cron["id"]}");
			}
			showLog('変更検出完了');
			$mysqli->query("update CronProgress set processing=false where id={$cron["id"]}");
		}
	}// 失敗
	elseif($cron["last_exec"]==false){
		$mysqli->query("update CronProgress set failed_count=failed_count+1, processing=false where id={$cron["id"]}");
		showLog("$rc: 失敗：$date");
	}// 失敗失敗（失敗が続いたためスキップする）
	else{
		$mysqli->query("update CronProgress set value='$date', failed_count=0, processing=false where id={$cron["id"]}");
		showLog("$rc: 失敗：$date");
		showLog("$rc: 複数回失敗したため $date をスキップします");
	}
}
?>
