<?php
//==================== URLからファイルをダウンロードする ====================//
// url: ダウンロードするファイルのURL
// filename: 保存先ファイル名
function downloadFile($url, $filename){
	if(is_file($filename))
		showLog("既にファイルが存在しているため上書きします: $filename");
	
	// DL用ファイルポインタの取得
	if(($fp_read = fopen($url, 'r', false, stream_context_create(array('http'=>array('ignore_errors'=>true))))) === false) return false;
	// 200以外はエラー
	preg_match('/HTTP\/1\.[0|1|x] ([0-9]{3})/', $http_response_header[0], $matches);
	if($matches[1] !== '200'){
		fclose($fp_read);
		return false;
	}
	// 書き込み用ファイルポインタの取得
	if(($fp_write = fopen($filename, 'w')) === false) return false;
	
	// 8KBずつ読み込んでファイルに保存していく
	while(!feof($fp_read)){
		// 8KB読み込んでバッファに保存
		if(($buf = fread($fp_read, 8192)) === false){
			$error = true;
			break;
		}
		// バッファを書き出し
		if(fwrite($fp_write, $buf) === false){
			$error = true;
			break;
		}
	}
	fclose($fp_read);
	fclose($fp_write);
	// 成功ならtrue，失敗ならfalseを返す
	return empty($error);
}

//==================== bgpdump形式のテキストファイルの情報を配列に格納する（フルルート用） ====================//
// filename: 読み込むbgpdump形式のテキストファイル
// 戻り値: network_list: 格納先の配列（参照渡し）
function getFullRouteFromBgpdump($filename){
	//------------ ファイルの存在確認 ------------//
	// if(!is_file($filename)) showLog('getFullRouteFromBgpdump: ファイルが存在しません: '.$filename);

	//------------ 代入先を初期化 ------------//
	$network_list = array('v4'=>array(), 'v6'=>array());

	//------------ ファイルの読み込み ------------//
	$fp = fopen($filename, 'r');
	while(($row = fgets($fp)) !== false){
		//------------ 行の読み込み ------------//
		// 1行にまとまったデータを分割
		list($protocol, $datetime, $type, $null1, $null2, $ip_prefix, $as_path, $origin_attr) = explode('|', rtrim($row));
		// 日付を再フォーマット		
		$datetime = date('Y-m-d H:i:s', strtotime($datetime));
		// $ip_protoの検出
		$ip_proto = strpos($ip_prefix, ':')===false? 'v4': 'v6';
		// $prev_network_listに[$ip_proto][$ip_prefix]を作成してネットワークアドレス・ブロードキャストアドレスを登録
		if(!isset($network_list[$ip_proto][$ip_prefix])){
			list($network, $broadcast) = getNetworkBroadcast($ip_prefix, $ip_proto);
			if($broadcast===null) continue;
			$network_list[$ip_proto][$ip_prefix] = array('network'=>$network, 'broadcast'=>$broadcast);
		}
		// すべてのOriginASを$prev_network_listに追加
		$as_path_list = explode(' ', $as_path);
		foreach(explode(',', str_replace(array('{','}'), '', end($as_path_list))) as $origin_as){
			$network_list[$ip_proto][$ip_prefix][$origin_as] = true;
		}
	}
	fclose($fp);
	
	//------------ 取得した$network_listを返す ------------//
	return $network_list;
}
?>
