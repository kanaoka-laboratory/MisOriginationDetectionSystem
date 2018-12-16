<?php
function CronASCountry(){
	global $mysqli;
	
	// 各RIRの取得状況を参照する（取得すべきdateを$tsに保存）
	$result = $mysqli->query('select * from ASCountryProgress');
	while($row = $result->fetch_assoc()) $ts[$row['rir']] = strtotime($row['date'].' +1 day');
	$result->close();

	//==================== 1. APNIC ====================//
	// 最近の2日分だけ http://ftp.apnic.net/stats/apnic/delegated-apnic-Ymd
	// それより前は http://ftp.apnic.net/stats/apnic/2018/delegated-apnic-Ymd.gz
	// Ymdの情報はYmd 0:00時点の情報と思われる（ファイルのタイムスタンプは01:15）
	showLog('APNIC');
	$Ymd = date('Ymd', $ts['apnic']);
	$filename = AS_COUNTRY."apnic/$Ymd.txt";
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
	// DLに成功したらデータを取得
	if($success) GetASCountry('apnic', $ts['apnic']);
	else showLog('データ更新なし');

	//==================== 2. ARIN ====================//
	// 全て http://ftp.apnic.net/stats/arin/delegated-arin-extended-Ymd
	// Ymdの情報はYmd 0:00時点？（ファイルのタイムスタンプは16:45）
	showLog('ARIN');
	$Ymd = date('Ymd', $ts['arin']);
	$filename = AS_COUNTRY."arin/$Ymd.txt";
	$url = "http://ftp.apnic.net/stats/arin/delegated-arin-extended-$Ymd";
	// DLに成功したらデータを取得
	if(downloadFile($url, $filename)) GetASCountry('arin', $ts['arin']);
	else showLog('データ更新なし');

	//==================== 3. RIPE NCC ====================//
	// 全て http://ftp.apnic.net/stats/ripe-ncc/2018/delegated-ripencc-Ymd.bz2
	// 一応 http://ftp.apnic.net/stats/ripe-ncc/delegated-ripencc-Ymd もあるが，bz2のほうが更新が早い
	// Ymdの情報はYmd 23:59時点と思われる（ファイルのタイムスタンプは次の日の09:07）
	showLog('RIPE NCC');
	$Ymd = date('Ymd', $ts['ripencc']);
	$filename = AS_COUNTRY."ripencc/$Ymd.txt";
	$url = 'http://ftp.apnic.net/stats/ripe-ncc/'.date('Y',$ts['ripencc'])."/delegated-ripencc-$Ymd.bz2";
	// bz2をDLして展開
	if(downloadFile($url, "/tmp/$Ymd.bz")){
		system("bunzip2 -c /tmp/$Ymd.bz > '$filename'", $retval);
		// 展開に成功したらデータを取得
		if($retval===0) GetASCountry('ripencc', $ts['ripencc']);
	}
	else showLog('データ更新なし');


	//==================== 4. LACNIC ====================//
	// 全て http://ftp.apnic.net/stats/lacnic/delegated-lacnic-Ymd
	// Ymdの情報はYmd 23:59時点と思われる（ファイルのタイムスタンプは次の日の11:51）
	showLog('LACNIC');
	$Ymd = date('Ymd', $ts['lacnic']);
	$filename = AS_COUNTRY."lacnic/$Ymd.txt";
	$url = "http://ftp.apnic.net/stats/lacnic/delegated-lacnic-$Ymd";
	// DLに成功したらデータを取得
	if(downloadFile($url, $filename)) GetASCountry('lacnic', $ts['lacnic']);
	else showLog('データ更新なし');

	//==================== 5. AFRINIC ====================//
	// 最新だけ http://ftp.apnic.net/stats/afrinic/delegated-afrinic-Ymd
	// それより前は http://ftp.apnic.net/stats/afrinic/delegated-afrinic-Ymd
	// Ymdの情報はYmd 0:00時点の情報と思われる（ファイルのタイムスタンプは10:05）
	showLog('AFRINIC');
	$Ymd = date('Ymd', $ts['afrinic']);
	$filename = AS_COUNTRY."afrinic/$Ymd.txt";
	// 最新の方をDL
	$url = "http://ftp.apnic.net/stats/afrinic/delegated-afrinic-$Ymd";
	$success = downloadFile($url, $filename);
	// アーカイブの方をDL
	if(!$success){
		$url = 'http://ftp.apnic.net/stats/afrinic/'.date('Y',$ts['afrinic'])."/delegated-afrinic-$Ymd";
		$success = downloadFile($url, $filename);
	}
	// DLに成功したらデータを取得
	if($success) GetASCountry('afrinic', $ts['afrinic']);
	else showLog('データ更新なし');
}

function GetASCountry($rir, $ts){
	global $mysqli;
	$filename = AS_COUNTRY.$rir.'/'.date('Ymd',$ts).'.txt';
	$date = date('Y-m-d',$ts);

	// ログ
	showLog(date('Y/m/d',$ts).'の情報登録');

	// すでに登録されている情報をDBから取得
	$date_done = date('Y-m-d', $ts-24*60*60);
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
?>