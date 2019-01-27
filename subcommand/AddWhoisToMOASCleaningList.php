<?php
function AddWhoisToMOASCleaningList($filename){
	// 引数チェック
	if(!is_file($filename)) showLog("ファイルが存在しません：$filename", true);
	
	// ファイルポインタの取得
	showLog("ファイル読み込み：$filename");
	$rows = file($filename, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	$fp = fopen(AppendStrToFilename($filename, '_whois'), 'w');
	
	// タイトル処理（MakeMOASCleaningListの結果かどうかの確認）
	$title = $rows[0];
	if(!preg_match('/^adv_type,conf_type,ip_prefix,conf_ip_prefix,asn,conf_asn,asn_cc,conf_asn_cc(,file(,count)?)$/', $title, $m))
		showLog("MOASCleaningListではありません：$filename", true);
	// タイトル出力
	fwrite($fp, 'adv_type,conf_type,ip_prefix,conf_ip_prefix,asn,conf_asn,asn_cc,conf_asn_cc,name,conf_name'.$m[1].PHP_EOL);

	// 1行ずつ処理
	showLog('0% 完了');
	$num_rows = count($rows);
	$whois_null = array();	// 実行中に限るwhois登録なしasnリスト
	for($i=1; $i<$num_rows; $i++){
		//------------ 読み込み ------------//
		$row = $rows[$i];
		list($adv_type, $conf_type, $ip_prefix, $conf_ip_prefix, $asn, $conf_asn, $asn_cc, $conf_asn_cc, $other) = explode(',', $row, 9);
		//------------ whois情報取得 ------------//
		// asn
		if($asn_cc==='Private'){
			$name = 'Private';
		}elseif(isset($whois_null[$asn])){
			$name = '-';
		}else{
			$name = GetWhoisAS($asn)['name'];
			if($name===null){
				$name = '-';
				$whois_null[$asn] = true;
			}
		}
		// conf_asn
		$conf_names = array();
		foreach(array_combine(explode('/',$conf_asn), explode('/',$conf_asn_cc)) as $conf_asn2=>$conf_asn_cc2){
			if($conf_asn_cc2==='Private'){
				$conf_names[] = 'Private';
			}elseif(isset($whois_null[$conf_asn2])){
				$conf_names[] = '-';
			}else{
				$conf_name = GetWhoisAS($conf_asn2);
				if($conf_name===null){
					$conf_names[] = '-';
					$whois_null[$conf_asn2] = true;
				}else{
					$conf_names[] = $conf_name['name'];
				}
			}
		}
		$conf_name = implode('/', $conf_names);
		//------------ 出力 ------------//
		fputcsv($fp, array_merge(array($adv_type, $conf_type, $ip_prefix, $conf_ip_prefix, $asn, $conf_asn, $asn_cc, $conf_asn_cc, $name, $conf_name), explode(',', $other)));
		//------------ 進捗表示 ------------//
		if($i%10===0) showLog(round($i*100/$num_rows,2)."% 完了");
	}

	// ファイルクローズ
	fclose($fp);
}

?>
