<?php
//==================== bgpdump形式のテキストファイルの情報を配列に格納する（フルルート用） ====================//
// filename: 読み込むbgpdump形式のテキストファイル
// network_list: 格納先の配列（参照渡し）
function getFullRouteFromBgpdump($filename, &$network_list){
	// 代入先を初期化
	$network_list = array('v4'=>array(), 'v6'=>array());

	// ファイルポインタの取得
	if(($fp = fopen($filename, 'r')) === false)
		return false;
	
	// 1行ずつ読み込み
	while(($row = fgets($fp)) !== false){
		// 1行にまとまったデータを分割
		list($protocol, $datetime, $type, $ip, $origin_as, $ip_prefix, $as_path, $origin_attr) = explode('|', rtrim($row));
		// 日付を再フォーマット		
		$datetime = date('Y-m-d H:i:s', strtotime($datetime));

		
		// IPv4とIPv6を区別して配列に代入
		$ip_proto = strpos($ip, ':')===false? 'v4': 'v6';
		$network_list[$ip_proto][$origin_as] = array(
			$ip_prefix,		// IPプレフィックス
			// $datetime,		// 日付
			// $as_path,		// AS Path
			// $ip,			// IPアドレス
			// $origin_attr,	// Origin属性
			// $type,			// アップデートの種類
			// $protocol,	// プロトコル（不要？）
		);

	}
	fclose($fp);

	return true;
}

function detectConflict(&$network_list1, &$network_list2){
	// network_list1のASとnetwork_list2のASを総当りで比較（ただし同じAS同士はスキップ）
	foreach($network_list1['v4'] as $as1 => $prefix_list1){
		foreach($network_list2['v4'] as $as2 => $prefix_list2){
			// 2つのASがそれぞれ広告しているIPプレフィックス同士を比較
			foreach($prefix_list1 as $prefix1){
				foreach($prefix_list2 as $prefix2){
					// 衝突するプレフィックスがあれば出力
					if(detectIpv4PrefixOverlap($prefix1, $prefix2)){
						echo "AS$as1:$prefix1<=>AS$as2:$prefix2\n";
					}

				}
			}		

		}
	}
}

?>
