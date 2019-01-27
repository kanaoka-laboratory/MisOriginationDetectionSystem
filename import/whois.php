<?php
function GetWhois($query){
	if(preg_match("/^(AS|as)?([0-9]+)$/", $query, $m))
		return GetWhoisAS($m[2]);
	else
		return null;
}

//==================== AS番号からwhois情報の取得 ====================//
// asn: 検索するAS番号
// 返り値: DBの構造をした配列（今のとこ whois_id, host, query, name, body, date）
function GetWhoisAS($asn){
	// 引数チェック
	if(preg_match('/^(AS|as)?([0-9]+)$/', $asn, $m)) $asn = (int)$m[2];
	else return null;
	// 不正なAS番号
	if($asn<=0 || (64512<=$asn && $asn<=65564)) return null;
	
	global $mysqli;
	// mysql問い合わせ
	$whois = $mysqli->query("select * from Whois where query='as$asn'")->fetch_assoc();
	
	// 結果が帰ってきた場合はそのまま返す
	if($whois!==null) return $whois;
	
	// DBになかった場合はwhois検索（・DB登録）を行い，結果を返す．	
	if(($whois_id = QueryWhoisAS($asn))!==null){
		$whois = $mysqli->query("select * from Whois where whois_id=$whois_id")->fetch_assoc();
		return $whois;
	}

	// whois検索しても見つからない場合はnullを返す
	return null;
}

function QueryWhoisAS($asn){
	// 引数チェック
	if(preg_match('/^(AS|as)?([0-9]+)$/', $asn, $m)) $asn = (int)$m[2];
	else return null;
	// 不正なAS番号
	if($asn<=0 || (64512<=$asn && $asn<=65534)) return null;

	// mysql利用準備
	global $mysqli;
	// そのAS番号を管理しているRIRをDBから検索
	$result = $mysqli->query("select rir from ASCountry where asn=$asn order by date_until limit 1")->fetch_assoc();
	// DBにマッチしない場合はarinのwhoisサーバに問い合わせる
	if($result===null) $rir = 'arin';
	else $rir = ($result['rir']==='ripencc')? 'ripe': $result['rir'];
	
	// 連続アクセスでBANされないように連続アクセスの場合は遅延処理（5秒以上間隔を開ける）
	static $last_request_time = 0;
	if(($delay = $last_request_time+5-time())>0) sleep($delay);
	$last_request_time = time();
	
	// 最大3回で結果が得られなければwhois情報が存在しない（誤rir->arin->正rir）
	$whois_not_exists = false;
	$name = 'NoName';	// 初期値
	for($i=0; $i<3; $i++){
		//------------ リクエストを送り，結果（fulltext）を取得 ------------//
		$fulltext = '';
		$fp = fsockopen("whois.$rir.net", 43);
		// arinとそれ以外ではクエリの方法が違う
		if($rir==='arin') fwrite($fp, "a $asn".PHP_EOL);	
		else fwrite($fp, "as$asn".PHP_EOL);
		while(!feof($fp)) $fulltext .= fgets($fp, 128);
		$fulltext = str_replace(array("\r\n","\r"),"\n", $fulltext);
		fclose($fp);
		//------------ fulltextからnameを取得 ------------//
		switch($rir) {
		// apnic
		case 'apnic':
			// apnicになければ "%ERROR:101: no entries found" が帰ってくる
			if(preg_match('/^%ERROR:101: no entries found$/m', $fulltext)){
				$rir = 'arin';
				break;
			}// 結果が帰ってきた
			else{
				if(preg_match('/^org-name:\s+?([^\s].*?)$/m', $fulltext, $m)) $name = $m[1];
				elseif(preg_match('/^as-name:\s+?([^\s].*?)$/m', $fulltext, $m)) $name = $m[1];
				break 2;
			}
		// arin
		case 'arin':
			// whoisが存在しないasなら "No match found for a $asn."，そうでないならOrgNameにRIR名が帰ってくる
			if(preg_match("/^No match found for( \+)? a $asn\.$/m", $fulltext, $m)){
				$whois_not_exists = true;
				break 2;
			}// 結果が帰ってきた
			else{
				if(preg_match('/^OrgName:\s+?([^\s].*?)$/m', $fulltext, $m)){
					if($m[1]==='African Network Information Center')						{ $rir = 'afrinic';	break; }
					if($m[1]==='Asia Pacific Network Information Centre')					{ $rir = 'apnic';	break; }
					if($m[1]==='RIPE Network Coordination Centre')							{ $rir = 'ripe';	break; }
					if($m[1]==='Latin American and Caribbean IP address Regional Registry')	{ $rir = 'lacnic';	break; }
					$name = $m[1];
				}
				break 2;
			}
		// ripe
		case 'ripe':
			// RIPEになければ "descr:   ASN block not managed by the RIPE NCC" が帰ってくる
			if(preg_match('/^descr:\s+?ASN block not managed by the RIPE NCC$/m', $fulltext)){
				$rir = 'arin';
				break;
			}// 結果が帰ってきた
			else{
				if(preg_match('/^org-name:\s+?([^\s].*?)$/m', $fulltext, $m)) $name = $m[1];
				elseif(preg_match('/^as-name:\s+?([^\s].*?)$/m', $fulltext, $m)) $name = $m[1];
				break 2;
			}
		// lacnic
		case 'lacnic':
			// "% (RIR) resource: whois.(rir).net" この記述でどこのrirかわかる
			// 上記の記述なし：どこのRIRかわからないのでarinに投げる
			if(!preg_match('/^% (APNIC|ARIN|RIPENCC|LACNIC|AFRINIC) resource: whois\.(apnic|arin|ripe|lacnic|afrinic)\.net$/m', $fulltext, $m)){
				$rir = 'arin';
				break;
			}
			// lacnic以外のRIR
			if($m[2]!=='lacnic'){
				$rir = $m[2];
				break;
			}// 結果が帰ってきた
			else{
				if(preg_match('/^owner:\s+?([^\s].*?)$/m', $fulltext, $m)) $name = $m[1];
				break 2;
			}
		// afrinic
		case 'afrinic':
			// どこのRIRかわからない
			if(preg_match('/^%ERROR:101: no entries found$/m', $fulltext)) { $rir = 'arin'; break; }
			// 他RIR管理の場合は宣言なく再帰問い合わせをするため，他のrirの自己紹介を目印にする
			if(preg_match('/^% \[whois.apnic.net\]$/m', $fulltext))														{ $rir = 'apnic'; break; }
			if(preg_match('/^# Copyright 1997-[0-9]{4}, American Registry for Internet Numbers, Ltd\.$/m', $fulltext))	{ $rir = 'arin'; break; }
			if(preg_match('/^% This is the RIPE Database query service\.$/m', $fulltext))								{ $rir = 'ripe'; break; }
			if(preg_match('/^% Joint Whois - whois.lacnic.net$/m', $fulltext))											{ $rir = 'lacnic'; break; }
			// 結果が帰ってきた
			if(preg_match('/^org-name:\s+?([^\s].*?)$/m', $fulltext, $m)) $name = $m[1];
			elseif(preg_match('/^as-name:\s+?([^\s].*?)$/m', $fulltext, $m)) $name = $m[1];
			break 2;
		}
		// ここが実行されるときはwhois情報が得られなかったとき
		$whois_not_exists = true;
	}
	
	// 該当するwhois情報が得られなかった
	if($whois_not_exists) return null;
	
	//------------ mysqlに登録 ------------//
	$name = $mysqli->real_escape_string($name);
	$fulltext = $mysqli->real_escape_string($fulltext);
	$mysqli->query("insert into Whois (host, query, name, body) values ('whois.$rir.net', 'as$asn', '$name', '$fulltext')");
	
	// 成功した場合はinsert_idを返す
	return $mysqli->insert_id;
}
?>
