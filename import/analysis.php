<?php
//==================== bgpscanner形式のテキストファイルの情報を配列に格納する（フルルート用） ====================//
// filename: 読み込むbgpscanner形式のテキストファイル
// 戻り値: network_list: 格納先の配列（参照渡し）
function getFullRouteFromBGPScanner($filename){
	//------------ ファイルの存在確認 ------------//
	if(!is_file($filename)) showLog('getFullRouteFromBgpscanner: ファイルが存在しません: '.$filename);

	//------------ 代入先を初期化 ------------//
	$network_list = array('v4'=>array(), 'v6'=>array());

	//------------ ファイルの読み込み ------------//
	$fp = fopen($filename, 'r');
	while(($row = fgets($fp)) !== false){
		//------------ 行の読み込み ------------//
		// 1行にまとまったデータを分割
		// list($null1, $ip_prefix, $as_path, $null2, $origin_attr, $null3, $null4, $null5, $null6, $ts, $null7)
		// // 日付を再フォーマット		
		// $datetime = date('Y-m-d H:i:s', $datetime);
		list($null1, $ip_prefix, $as_path, $null2) = explode('|', $row, 4);
		// $ip_protoの検出
		$ip_proto = strpos($ip_prefix, ':')===false? 'v4': 'v6';
		// $network_listに[$ip_proto][$ip_prefix]を作成してネットワークアドレス・ブロードキャストアドレスを登録
		if(!isset($network_list[$ip_proto][$ip_prefix])){
			list($network, $broadcast) = getNetworkBroadcast($ip_prefix, $ip_proto);
			if($broadcast===null) continue;
			$network_list[$ip_proto][$ip_prefix] = array('network'=>$network, 'broadcast'=>$broadcast);
		}
		// すべてのOriginASを$network_listに追加
		$as_path_list = explode(' ', $as_path);
		foreach(explode(',', str_replace(array('{','}'), '', end($as_path_list))) as $origin_as){
			$network_list[$ip_proto][$ip_prefix][$origin_as] = true;
		}
	}
	fclose($fp);
	
	//------------ 取得した$network_listを返す ------------//
	return $network_list;
}

//==================== IPプレフィックスのネットワーク・ブロードキャストアドレスを返す ====================//
// 返り値：array($network, $broadcast);
function getNetworkBroadcast($ip_prefix, $ip_proto = null){
	if($ip_proto!=='v4' && $ip_proto!=='v6')
		$ip_proto = (strpos($ip_prefix,':')===false)? 'v4': 'v6';
	//------------ IPv4 ------------//
	// アドレスをint値で保存（0x0〜0xFFFFFFFF）
	if($ip_proto==='v4'){
		// アドレスとネットマスクを分割
		list($ip,$mask) = explode('/', $ip_prefix);
		$mask = (int)$mask;
		// サブネットマスクが不正なときは処理を中断
		if($mask===0) return null;
		if(IGNORE_ILLEGAL_SUBNET && $mask<8) return null;
		// アドレス（string）をint値に変換
		list($octet1,$octet2,$octet3,$octet4) = explode('.', $ip);
		$network = $octet1<<24 | $octet2<<16 | $octet3<<8 | $octet4;
		$broadcast = $network | 0xFFFFFFFF>>$mask;
	}//------------ v6 ------------//
	// アドレス上位64bitをintで保存（'0x0'〜'0x7FFFFFFFFFFFFFFF'）
	// intの正の値は63bitまでしか表現できないが，v6で最上位のビットが1になるアドレスは存在しない
	else{
		// アドレスとネットマスクを分割
		list($ip,$mask) = explode('/', $ip_prefix);
		$mask = (int)$mask;
		// サブネットマスクが不正なときは処理を中断
		if($mask===0 ||  $mask>64) return null;
		if(IGNORE_ILLEGAL_SUBNET && $mask<19) return null;
		// アドレス（string）を128bitバイナリに変換
		if(($ip = inet_pton($ip)) === false){
			showLog('getNetworkBroadcast: 無効なIPアドレス: '.$ip_prefix);
			return null;
		}
		// 128bitバイナリから上位64bitをint値に変換
		list($octet1,$octet2,$octet3,$octet4,$octet5,$octet6,$octet7,$octet8) = str_split($ip);
		$network = ord($octet1)<<56 | ord($octet2)<<48 | ord($octet3)<<40 | ord($octet4)<<32 | ord($octet5)<<24 | ord($octet6)<<16 | ord($octet7)<<8 | ord($octet8);
		// 0xFFFFFFFFFFFFFFFFはオーバーフローして負の値（-1）になり，いくら右シフトしても値が変わらない
		$broadcast = $network | ~(-1<<(64-$mask));
	}
	// ネットワークアドレスとブロードキャストアドレスを返す	
	return array($network, $broadcast);
}

//==================== SuspiciousAsnSetにホワイトリストを再適用する ====================//
function ReApplyWhitelist($suspicious_id = null){
	global $mysqli;

	// ホワイトリストを再適用する行を取得
	$where_condition = $suspicious_id!==null? "suspicious_id=".(int)$suspicious_id: "0<conflict_type and conflict_type<100";
	$result = $mysqli->query("select suspicious_id,conflict_type,asn,conflict_asn from SuspiciousAsnSet where $where_condition");
	
	// 再適用していく
	while($row = $result->fetch_assoc()){
		// 更新がある場合はDBにupdateを投げる
		$conflict_type = $mysqli->VerifyConflictAsnWhiteList($row["asn"], $row["conflict_asn"]);
		if($conflict_type===null) $conflict_type = CONFLICT_TYPE_SUSPICIOUS;
		if($conflict_type!==$row["conflict_type"])
			$mysqli->query("update SuspiciousAsnSet set conflict_type=$conflict_type where suspicious_id={$row["suspicious_id"]}");
	}

	// 全体への再適用の場合は[ASX,0]を探して[ASX,ASY][ASX,ASZ]を削除する
	if($suspicious_id===null){
		$result = $mysqli->query("select conflict_type,asn from ConflictAsnWhiteList where conflict_asn=0");
		while($row = $result->fetch_assoc()){
			// 重複するデータを削除する
			$mysqli->query("delete from ConflictAsnWhiteList where asn={$row["asn"]} and conflict_asn!=0 and disabled is null and conflict_type={$row["conflict_type"]}");
		}
	}
}

?>
