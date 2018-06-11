<?php
//==================== URLからファイルをダウンロードする ====================//
// url: ダウンロードするファイルのURL
// filename: 保存先ファイル名
function downloadFile($url, $filename){
	// ファイルポインタの取得
	if(($fp_read = fopen($url, 'r')) === false) return false;
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
		if(isset($network_list[$ip_proto][$origin_as][$ip_prefix]))
			continue;

		//------------ IPプレフィックスのネットワーク・ブロードキャストアドレス（両端）の計算 ------------//
		// IPv4アドレスをint値で保存（0x0〜0xFFFFFFFF）
		if($ip_proto==='v4'){
			list($ip,$mask) = explode('/', $ip_prefix);
			if($mask<8) continue;
			list($a,$b,$c,$d) = explode('.', $ip);
			$ip_min = $a<<24 | $b<<16 | $c<<8 | $d;
			$ip_max = $ip_min | 0xFFFFFFFF>>$mask;
		}// IPv6アドレスを文字列で保存（'00000000000000000000000000000000'〜'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF'）
		else{
			list($ip,$mask) = explode('/', $ip_prefix);
			$mask = (int)$mask;
			if($mask<16) continue;
			$ip = inet_pton($ip);
			$ip_min = '';
			$i=0;
			foreach(str_split($ip) as $char){
				$i+=8;
				if($i===$mask){
					$ip_min .= str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
					$ip_max = $ip_min;
					break;
				}elseif($i<$mask){
					$ip_min .= str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
				}else{
					$dec = ord($char);
					$ip_max = $ip_min.str_pad(dechex($dec|0xff>>$mask%8), 2, '0', STR_PAD_LEFT);
					$ip_min .= str_pad(dechex($dec), 2, '0', STR_PAD_LEFT);
					break;
				}
			}
			$ip_max = str_pad($ip_max, 32, 'f', STR_PAD_RIGHT);
			$ip_min = str_pad($ip_min, 32, '0', STR_PAD_RIGHT);
		}

		//------------ IPv4とIPv6を区別して$network_listに経路情報を追加 ------------//
		// 初めてのOrigin-ASの場合は経路配列を初期化
		if(!isset($network_list[$ip_proto][$origin_as])){
			$network_list[$ip_proto][$origin_as] = array();
		}

		// 経路を追加する
		$network_list[$ip_proto][$origin_as][$ip_prefix] = array($ip_min, $ip_max);
	}
	fclose($fp);

	return true;
}
?>
