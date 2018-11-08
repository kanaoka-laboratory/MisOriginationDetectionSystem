<?php
function GetASCountry($date){
	if($date===null)$ts = strtotime('now') - 60*60*9;	// 指定がない場合は現在時刻
	else			$ts = strtotime($date);
	
	showLog(date('Y-m-d', $ts).'の情報取得');
	$date = date('Ymd', $ts);
	
	$context = stream_context_create(array('http' => array('ignore_errors' => true)));
	$ASCountry = array();

	//------------ afrinic ------------//
	showLog('AFRINIC');
	$data = file_get_contents('http://ftp.apnic.net/stats/afrinic/delegated-afrinic-'.$date, false, $context);
	if( strpos($http_response_header[0], '200') === false){
		$data = file_get_contents('http://ftp.apnic.net/stats/afrinic/'.date('Y',$ts).'/delegated-afrinic-'.$date, false, $context);
	}
	foreach (explode("\n",$data) as $row) {
		// 正規表現でASの国情報の行だけ取得
		if(preg_match('/^afrinic\|([A-Z]{2})\|asn\|([0-9]+)\|([0-9]+)\|[0-9]{8}\|allocated$/', trim($row), $m)){
			// それぞれの情報を抽出
			$country = $m[1];
			$asn = $m[2];
			$imax = $m[3];
			// 配列に追加
			for($i=0; $i<$imax; $i++){ $ASCountry[$asn+$i] = $country; }
		}
	}

	//------------ apnic ------------//
	showLog('APNIC');
	$data = file_get_contents('http://ftp.apnic.net/stats/apnic/delegated-apnic-'.$date, false, $context);
	if( strpos($http_response_header[0], '200') === false){
		$data = file_get_contents('http://ftp.apnic.net/stats/apnic/'.date('Y',$ts).'/delegated-apnic-'.$date, false, $context);
	}
	foreach (explode("\n",$data) as $row) {
		// 正規表現でASの国情報の行だけ取得
		if(preg_match('/^apnic\|([A-Z]{2})\|asn\|([0-9]+)\|([0-9]+)\|[0-9]{8}\|allocated$/', trim($row), $m)){
			// それぞれの情報を抽出
			$country = $m[1];
			$asn = $m[2];
			$imax = $m[3];
			// 配列に追加
			for($i=0; $i<$imax; $i++){ $ASCountry[$asn+$i] = $country; }
		}
	}

	//------------ arin ------------//
	showLog('ARIN');
	$data = file_get_contents('http://ftp.apnic.net/stats/arin/delegated-arin-'.$date, false, $context);
	if( strpos($http_response_header[0], '200') === false){
		$data = file_get_contents('http://ftp.apnic.net/stats/arin/'.date('Y',$ts).'/delegated-arin-'.$date, false, $context);
	}
	foreach (explode("\n",$data) as $row) {
		// 正規表現でASの国情報の行だけ取得
		if(preg_match('/^arin\|([A-Z]{2})\|asn\|([0-9]+)\|([0-9]+)\|[0-9]{8}\|allocated$/', trim($row), $m)){
			// それぞれの情報を抽出
			$country = $m[1];
			$asn = $m[2];
			$imax = $m[3];
			// 配列に追加
			for($i=0; $i<$imax; $i++){ $ASCountry[$asn+$i] = $country; }
		}
	}

	//------------ iana ------------//
	showLog('IANA');
	$data = file_get_contents('http://ftp.apnic.net/stats/iana/delegated-iana-'.$date, false, $context);
	if( strpos($http_response_header[0], '200') === false){
		$data = file_get_contents('http://ftp.apnic.net/stats/iana/'.date('Y',$ts).'/delegated-iana-'.$date, false, $context);
	}
	foreach (explode("\n",$data) as $row) {
		// 正規表現でASの国情報の行だけ取得
		if(preg_match('/^iana\|([A-Z]{2})\|asn\|([0-9]+)\|([0-9]+)\|[0-9]{8}\|allocated$/', trim($row), $m)){
			// それぞれの情報を抽出
			$country = $m[1];
			$asn = $m[2];
			$imax = $m[3];
			// 配列に追加
			for($i=0; $i<$imax; $i++){ $ASCountry[$asn+$i] = $country; }
		}
	}

	//------------ LACNIC ------------//
	showLog('LACNIC');
	$data = file_get_contents('http://ftp.apnic.net/stats/lacnic/delegated-lacnic-'.$date, false, $context);
	if( strpos($http_response_header[0], '200') === false){
		$data = file_get_contents('http://ftp.apnic.net/stats/lacnic/'.date('Y',$ts).'/delegated-lacnic-'.$date, false, $context);
	}
	foreach (explode("\n",$data) as $row) {
		// 正規表現でASの国情報の行だけ取得
		if(preg_match('/^lacnic\|([A-Z]{2})\|asn\|([0-9]+)\|([0-9]+)\|[0-9]{8}\|allocated$/', trim($row), $m)){
			// それぞれの情報を抽出
			$country = $m[1];
			$asn = $m[2];
			$imax = $m[3];
			// 配列に追加
			for($i=0; $i<$imax; $i++){ $ASCountry[$asn+$i] = $country; }
		}
	}

	//------------ ripencc ------------//
	showLog('RIPE NCC');
	$data = file_get_contents('http://ftp.apnic.net/stats/ripe-ncc/delegated-ripencc-'.$date, false, $context);
	if( strpos($http_response_header[0], '200') === false){
		$data = file_get_contents('http://ftp.apnic.net/stats/ripe-ncc/'.date('Y',$ts).'/delegated-ripencc-'.$date, false, $context);
	}
	foreach (explode("\n",$data) as $row) {
		// 正規表現でASの国情報の行だけ取得
		if(preg_match('/^ripencc\|([A-Z]{2})\|asn\|([0-9]+)\|([0-9]+)\|[0-9]{8}\|allocated$/', trim($row), $m)){
			// それぞれの情報を抽出
			$country = $m[1];
			$asn = $m[2];
			$imax = $m[3];
			// 配列に追加
			for($i=0; $i<$imax; $i++){ $ASCountry[$asn+$i] = $country; }
		}
	}
	
	$fp = fopen(GET_AS_COUNTRY_RESULT."$date.txt", 'w');
	foreach ($ASCountry as $asn => $country) {
		fwrite($fp, "$asn,$country".PHP_EOL);
	}
}
?>