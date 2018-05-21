<?php
//==================== 2つのIPアドレスが重複しているかどうかを調べる ====================//
// prefix1, prefix2: 調べたいIPアドレス（ww.xxx.yy.z/dd）
function detectIpv4PrefixOverlap($prefix1, $prefix2){
	if(!preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/', $prefix1, $m1))	return "prefix1 is an illegal format.";
	if(!preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/', $prefix2, $m2))	return "prefix2 is an illegal format.";

	// prefix1のネットワークアドレス（ビット列）とブロードキャストアドレス（ビット列）を取得
	$prefix1network = sprintf("%08b%08b%08b%08b",$m1[1],$m1[2],$m1[3],$m1[4]);
	if((int)substr($prefix1network, $m1[5]) !== 0) return "prefix1 is not network address.";
	$prefix1broadcast = substr(substr($prefix1network, 0, $m1[5]).'11111111111111111111111111111111', 0, 32);
	// echo $prefix1network.PHP_EOL.$prefix1broadcast.PHP_EOL;

	// prefix2のネットワークアドレス（ビット列）とブロードキャストアドレス（ビット列）を取得
	$prefix2network = sprintf("%08b%08b%08b%08b",$m2[1],$m2[2],$m2[3],$m2[4]);
	if((int)substr($prefix2network, $m2[5]) !== 0) return "prefix2 is not network address.";
	$prefix2broadcast = substr(substr($prefix2network, 0, $m2[5]).'11111111111111111111111111111111', 0, 32);
	// echo $prefix2network.PHP_EOL.$prefix2broadcast.PHP_EOL;

	// 重複しているかどうかをbool値で返す
	return ($prefix2network <= $prefix1broadcast && $prefix1network <= $prefix2broadcast);
}

function detectIpv6PrefixOverlap($prefix1, $prefix2){
	// if(!preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/', $prefix1, $m1))	return "prefix1 is an illegal format.";
	// if(!preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/', $prefix2, $m2))	return "prefix2 is an illegal format.";

	// // prefix1のネットワークアドレス（ビット列）とブロードキャストアドレス（ビット列）を取得
	// $prefix1network = sprintf("%08b%08b%08b%08b",$m1[1],$m1[2],$m1[3],$m1[4]);
	// if((int)substr($prefix1network, $m1[5]) !== 0) return "prefix1 is not network address.";
	// $prefix1broadcast = substr(substr($prefix1network, 0, $m1[5]).'11111111111111111111111111111111', 0, 32);
	// // echo $prefix1network.PHP_EOL.$prefix1broadcast.PHP_EOL;

	// // prefix2のネットワークアドレス（ビット列）とブロードキャストアドレス（ビット列）を取得
	// $prefix2network = sprintf("%08b%08b%08b%08b",$m2[1],$m2[2],$m2[3],$m2[4]);
	// if((int)substr($prefix2network, $m2[5]) !== 0) return "prefix2 is not network address.";
	// $prefix2broadcast = substr(substr($prefix2network, 0, $m2[5]).'11111111111111111111111111111111', 0, 32);
	// // echo $prefix2network.PHP_EOL.$prefix2broadcast.PHP_EOL;

	// // 重複しているかどうかをbool値で返す
	// return ($prefix2network <= $prefix1broadcast && $prefix1network <= $prefix2broadcast);
}
?>
