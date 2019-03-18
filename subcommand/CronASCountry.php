<?php
function CronASCountry(){
	global $mysqli;

	// 全てのrirに対して処理
	foreach(array("apnic","arin","ripencc","lacnic","afrinic") as $rir){
		// 現状を取得
		$cron = $mysqli->query("select id,value,failed_count>=max_failed_count as last_exec,processing ".
														"from CronProgress where cron='ASCountry' and name='$rir'")->fetch_assoc();
		$ts = strtotime($cron["value"]." +1 day");
		$date = date("Y-m-d",$ts);
		if(time() < strtotime("$date UTC")){
			showLog("$rir: まだ次の情報がアップされる時間ではありません");
			continue;
		}

		// 実行中フラグを立てる
		showLog("$rir: 実行開始");
		$mysqli->query("update CronProgress set processing=true where id={$cron["id"]}");

		// ASと国の紐付け情報をDL
		$filename = DownloadASCountry($rir, $ts);
		
		// 成功
		if($filename!==false){
			// ログ
			showLog("$rir: $date の情報登録");
			// ファイルからDBに登録
			GetASCountry($rir, $ts);
			// 終了処理
			$mysqli->query("update CronProgress set value='$date', value2='$date', failed_count=0, processing=false where id={$cron["id"]}");
		}// 失敗
		elseif($cron["last_exec"]==false){
			$mysqli->query("update CronProgress set failed_count=failed_count+1, processing=false where id={$cron["id"]}");
			showLog("$rir: 取得失敗：$date");
		}// 失敗（失敗が続いたためスキップする）
		else{
			$mysqli->query("update CronProgress set value='$date', failed_count=0, processing=false where id={$cron["id"]}");
			showLog("$rir: 失敗：$date");
			showLog("$rir: 複数回失敗したため $date をスキップします");
		}

	}
}

function GetASCountry($rir, $ts){
	global $mysqli;
	$filename = AS_COUNTRY.$rir."/".date("Y",$ts)."/".date("Ymd", $ts).".txt";
	$date = date('Y-m-d',$ts);

	// すでに登録されている情報をDBから取得
	$date_done = ($row = $mysqli->query("select max(date_until) as max from ASCountry")->fetch_assoc())? $row["max"]: "";
	$prev_data = array();
	$result = $mysqli->query("select id,asn,country from ASCountry where rir='$rir' and date_until='$date_done'");
	while($row = $result->fetch_assoc()){
		$prev_data[$row['asn']] = array('country'=>$row['country'], 'id'=>$row['id']);
	}
	$result->close();

	// トランザクション開始
	$mysqli->begin_transaction();
	// ファイルを読み込んでいく
	$fp = fopen($filename, 'r');
	while(($row=fgets($fp)) !== false){
		// 正規表現でASの国情報の行以外をスキップ
		if(!preg_match("/^$rir\|([A-Z]{2})\|asn\|([0-9]+)\|([0-9]+)\|([0-9]{8})\|/", $row, $m)) continue;
		// 国情報を抜き出し
		$country = $m[1];
		$asn = $m[2];
		$asn_limit = $asn+$m[3];
		$date_since = $m[4];
		// DBに追加
		for(; $asn<$asn_limit; $asn++){
			// 変更のない組み合わせ，date_untilの更新
			if(isset($prev_data[$asn]) && $prev_data[$asn]['country']===$country)
				$mysqli->query("update ASCountry set date_until='$date' where id=".$prev_data[$asn]['id']);
			// 新しい組み合わせ，insertを実行
			else 
				$mysqli->query("insert into ASCountry (asn,country,rir,date_since,date_until) values ($asn,'$country','$rir','$date_since','$date')");
		}
	
	}
	$mysqli->query("update ASCountryProgress set date='$date' where rir='$rir'");
	// トランザクション完了
	$mysqli->commit();
}

function DownloadASCountry($rir, $ts){
	$success = false;
	// 出力ファイル準備
	$Ymd = date("Ymd", $ts);
	$dir = AS_COUNTRY.$rir."/".date("Y",$ts);
	$filename = "$dir/$Ymd.txt";
	if(!is_dir($dir)) mkdir($dir);

	// 各rirごとに処理
	switch($rir){
	//==================== 1. APNIC ====================//
	// 最近の2日分だけ http://ftp.apnic.net/stats/apnic/delegated-apnic-Ymd
	// それより前は http://ftp.apnic.net/stats/apnic/2018/delegated-apnic-Ymd.gz
	// Ymdの情報はYmd 0:00時点の情報と思われる（ファイルのタイムスタンプは01:15）
	case "arin":
		// 最近の2日分の方をDL
		$url = "http://ftp.apnic.net/stats/apnic/delegated-apnic-$Ymd";
		$success = downloadFile($url, $filename);
		// アーカイブの方をDL
		if(!$success){
			// 12/31分は次の年のディレクトリの中にある
			$url = 'http://ftp.apnic.net/stats/apnic/'.date('Y',$ts['apnic']+60*60*24)."/delegated-apnic-$Ymd.gz";
			// gzをDLして展開
			if(downloadFile($url, "/tmp/$Ymd.gz")){
				system("gunzip -c /tmp/$Ymd.gz > $filename", $retval);
				if($retval===0) $success=true;
			}
		}
		break;
	
	//==================== 2. ARIN ====================//
	// 全て http://ftp.apnic.net/stats/arin/delegated-arin-extended-Ymd
	// Ymdの情報はYmd 0:00時点？（ファイルのタイムスタンプは16:45）
	case "arin":
		$url = "http://ftp.apnic.net/stats/arin/delegated-arin-extended-$Ymd";
		$success = downloadFile($url, $filename);
		break;

	//==================== 3. RIPE NCC ====================//
	// 全て http://ftp.apnic.net/stats/ripe-ncc/2018/delegated-ripencc-Ymd.bz2
	// 一応 http://ftp.apnic.net/stats/ripe-ncc/delegated-ripencc-Ymd もあるが，bz2のほうが更新が早い
	// Ymdの情報はYmd 23:59時点と思われる（ファイルのタイムスタンプは次の日の09:07）
	case "ripencc":
		$url = 'http://ftp.apnic.net/stats/ripe-ncc/'.date('Y',$ts['ripencc'])."/delegated-ripencc-$Ymd.bz2";
		// bz2をDLして展開
		$success = downloadFile($url, "/tmp/$Ymd.bz");
		if($success){
			system("bunzip2 -c /tmp/$Ymd.bz > '$filename'", $retval);
			// 展開に成功したらデータを取得
			if($retval!==0) $success = false; 
		}
		break;
	
	//==================== 4. LACNIC ====================//
	// 全て http://ftp.apnic.net/stats/lacnic/delegated-lacnic-Ymd
	// Ymdの情報はYmd 23:59時点と思われる（ファイルのタイムスタンプは次の日の11:51）
	case "lacnic":
		$url = "http://ftp.apnic.net/stats/lacnic/delegated-lacnic-$Ymd";
		$success = downloadFile($url, $filename);
		break;
	
	//==================== 5. AFRINIC ====================//
	// 最新だけ http://ftp.apnic.net/stats/afrinic/delegated-afrinic-Ymd
	// それより前は http://ftp.apnic.net/stats/afrinic/delegated-afrinic-Ymd
	// Ymdの情報はYmd 0:00時点の情報と思われる（ファイルのタイムスタンプは10:05）
	case "afrinic":
		// 最新の方をDL
		$url = "http://ftp.apnic.net/stats/afrinic/delegated-afrinic-$Ymd";
		$success = downloadFile($url, $filename);
		// アーカイブの方をDL
		if(!$success){
			$url = 'http://ftp.apnic.net/stats/afrinic/'.date('Y',$ts['afrinic'])."/delegated-afrinic-$Ymd";
			$success = downloadFile($url, $filename);
		}
		break;
	}

	// 成功した場合はファイル名，失敗した場合はfalseを返す
	if($success) return $filename;
	else return false;
}
?>