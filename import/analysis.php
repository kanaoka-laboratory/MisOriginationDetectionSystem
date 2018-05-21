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

?>
