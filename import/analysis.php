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
		//------------ 行の読み込み ------------//
		// 1行にまとまったデータを分割
		list($protocol, $datetime, $type, $null1, $null2, $ip_prefix, $as_path, $origin_attr) = explode('|', rtrim($row));
		// 日付を再フォーマット		
		$datetime = date('Y-m-d H:i:s', strtotime($datetime));
		// origin ASを取得
		$as_path_list = explode(' ', $as_path);
		// $origin_as = $as_path_list[count($as_path_list)-1];
		$origin_as = str_replace(array('{','}'), '', $as_path_list[count($as_path_list)-1]);
		
		// origin ASのフォーマット確認・int変換
		if(!ctype_digit($origin_as)){
			// echo $origin_as.PHP_EOL;
			continue;
		}
		$origin_as = (int)$origin_as;
		
		// $ip_protoの検出
		$ip_proto = strpos($ip_prefix, ':')===false? 'v4': 'v6';

		//------------ 重複する経路は追加しない ------------//
		if(isset($network_list[$ip_proto][$origin_as][NETWORK_LIST_IP_PREFIX][$ip_prefix]))
			continue;

		//------------ IPプレフィックスのネットワーク・ブロードキャストアドレス（両端）の計算 ------------//
		// IPv4アドレスをint値で保存（0x0〜0xFFFFFFFF）
		if($ip_proto==='v4'){
			list($ip,$mask) = explode('/', $ip_prefix);
			if($mask<8) continue;
			list($a,$b,$c,$d) = explode('.', $ip);
			$network = $a<<24 | $b<<16 | $c<<8 | $d;
			$broadcast = $network | 0xFFFFFFFF>>$mask;
		}// IPv6アドレスを文字列で保存（'00000000000000000000000000000000'〜'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'）
		else{
			list($ip,$mask) = explode('/', $ip_prefix);
			$mask = (int)$mask;
			if($mask<16) continue;
			$ip = inet_pton($ip);
			$network = '';
			$i=0;
			foreach(str_split($ip) as $char){
				$i+=8;
				if($i===$mask){
					$network .= str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
					$broadcast = $network;
					break;
				}elseif($i<$mask){
					$network .= str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
				}else{
					$dec = ord($char);
					$broadcast = $network.str_pad(dechex($dec|0xff>>$mask%8), 2, '0', STR_PAD_LEFT);
					$network .= str_pad(dechex($dec), 2, '0', STR_PAD_LEFT);
					break;
				}
			}
			$broadcast = str_pad($broadcast, 32, 'f', STR_PAD_RIGHT);
			$network = str_pad($network, 32, '0', STR_PAD_RIGHT);
		}

		//------------ IPv4とIPv6を区別して$network_listに経路情報を追加 ------------//
		// 初めてのOrigin-ASの場合は経路配列を初期化
		if(!isset($network_list[$ip_proto][$origin_as])){
			$network_list[$ip_proto][$origin_as] = array(
				array(),	// IPプレフィクス
				// null,		// 親AS（まだ実装してない）
				// $datetime,	// 日時（この配列には必要ない？）
				// $origin_attr,// Origin属性（まだ実装してない）
				// $protocol,	// プロトコル（必要？）
			);
		}

		// 経路を追加する
		$network_list[$ip_proto][$origin_as][NETWORK_LIST_IP_PREFIX][$ip_prefix] = array($network, $broadcast);
	}
	fclose($fp);

	return true;
}

//==================== その日のnetwork_listを分析してプレフィックスが衝突するASを検索 ====================//
// network_list: ネットワーク情報が入った配列
// 個人メモ：PHPはCopy on Writeがあるから，Read-Onlyの場合は巨大な配列でも参照渡しの必要がない
function detectConflict($network_list){
	//------------ v4 ------------//
	network_listのASを総当りで比較（同じAS同士はスキップ）
	$length = count($network_list['v4']);
	for($i=0; $i<$length-1; $i++){
		showLog('衝突検知中：'.$i.'/'.$length);
		$as1 = key(array_slice($network_list['v4'], $i, 1, true));
		$as_info1 = $network_list['v4'][$as1];
		$network_list2 = array_slice($network_list['v4'], $i+1, null, true);
		foreach($network_list2 as $as2 => $as_info2){
			// 2つのASがそれぞれ広告しているIPプレフィックス同士を比較
			foreach($as_info1[NETWORK_LIST_IP_PREFIX] as $prefix1 => $prefix_info1){
				foreach($as_info2[NETWORK_LIST_IP_PREFIX] as $prefix2 => $prefix_info2){
					// 衝突するプレフィックスがあれば出力
					if($prefix_info2[NETWORK_LIST_IP_PREFIX_NETWORK] <= $prefix_info1[NETWORK_LIST_IP_PREFIX_BROADCAST]
							&& $prefix_info1[NETWORK_LIST_IP_PREFIX_NETWORK] <= $prefix_info2[NETWORK_LIST_IP_PREFIX_BROADCAST]){
						// echo "AS$as1:$prefix1<=>AS$as2:$prefix2\n";
						echo "AS$as1\tAS$as2\n";
						continue 3;
					}
				}
			}
		}
	}

	//------------ v6 ------------//
	$length = count($network_list['v6']);
	for($i=0; $i<$length-1; $i++){
		showLog('衝突検知中：'.$i.'/'.$length);
		$as1 = key(array_slice($network_list['v6'], $i, 1, true));
		$as_info1 = $network_list['v6'][$as1];
		$network_list2 = array_slice($network_list['v6'], $i+1, null, true);
		foreach($network_list2 as $as2 => $as_info2){
			// 2つのASがそれぞれ広告しているIPプレフィックス同士を比較
			foreach($as_info1[NETWORK_LIST_IP_PREFIX] as $prefix1 => $prefix_info1){
				foreach($as_info2[NETWORK_LIST_IP_PREFIX] as $prefix2 => $prefix_info2){
					// 衝突するプレフィックスがあれば出力
					// $prefix_info2[NETWORK_LIST_IP_PREFIX_NETWORK] <= $prefix_info1[NETWORK_LIST_IP_PREFIX_BROADCAST]
					// 		&& $prefix_info1[NETWORK_LIST_IP_PREFIX_NETWORK] <= $prefix_info2[NETWORK_LIST_IP_PREFIX_BROADCAST]
					if(strcmp($prefix_info2[NETWORK_LIST_IP_PREFIX_NETWORK], $prefix_info1[NETWORK_LIST_IP_PREFIX_BROADCAST]) <= 0 
							&& strcmp($prefix_info1[NETWORK_LIST_IP_PREFIX_NETWORK], $prefix_info2[NETWORK_LIST_IP_PREFIX_BROADCAST]) <= 0){
						// echo "AS$as1:$prefix1<=>AS$as2:$prefix2\n";
						echo "AS$as1\tAS$as2\n";
						continue 3;
					}
				}
			}
		}
	}
}

?>
