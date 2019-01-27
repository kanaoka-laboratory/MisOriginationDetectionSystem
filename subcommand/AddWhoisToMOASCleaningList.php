<?php
function AddWhoisToMOASCleaningList($filename){
	// 引数チェック
	if(!is_file($filename)) showLog("ファイルが存在しません：$filename", true);
	
	// ファイルポインタの取得
	$fp = fopen($filename, 'r');
	$fp_out = fopen(AppendStrToFilename($filename, '_whois'), 'w');
	
	// タイトル処理（MakeMOASCleaningListの結果かどうかの確認）
	$title = rtrim(fgets($fp));
	if(!preg_match('/^adv_type,conf_type,ip_prefix,conf_ip_prefix,asn,conf_asn,asn_cc,conf_asn_cc(,file(,count)?)$/', $title, $m))
		showLog("MOASCleaningListではありません：$filename", true);
	$title = 'adv_type,conf_type,ip_prefix,conf_ip_prefix,asn,conf_asn,asn_cc,conf_asn_cc,name,conf_name'.$m[1];

	// 1行ずつ処理
	while(($row = fgets($fp))!==false){
		// 読み込んで分割
		list($adv_type, $conf_type, $ip_prefix, $conf_ip_prefix, $asn, $conf_asn, $asn_cc, $conf_asn_cc, $other) = explode(',', rtrim($row), 9);
		// whois情報取得
		$name = GetWhoisAS($asn)['name'];
		$conf_name = GetWhoisAS($conf_asn)['name'];
		// 結合して出力
		fputcsv($fp_out, array_merge(array($adv_type, $conf_type, $ip_prefix, $conf_ip_prefix, $asn, $conf_asn, $asn_cc, $conf_asn_cc, $name, $conf_name), explode(',', $other)));
	}

	// ファイルクローズ
	fclose($fp);
	fclose($fp_out);
}

?>
