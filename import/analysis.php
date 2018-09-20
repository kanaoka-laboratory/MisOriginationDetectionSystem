<?php
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

?>
