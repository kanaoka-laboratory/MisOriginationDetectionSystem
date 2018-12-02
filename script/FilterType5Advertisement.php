<?php
// 設定ファイル読み込み
require_once('config.php');
// 関数などの読み込み
foreach(glob('import/*.php') as $filename) require_once($filename);

// usage: php script/FilterType5Advertisement.php 
define('NEARBY_COUNTRY', 1);
define('PRIVATE_ASN', 2);
define('US_DOD', 3);
define('AKAMAI', 4);
define('OTHER', 4);

// ホワイトリストの作成
$current_exception=null;
$exception = array();
$fp = fopen('script/okada_whitelist.txt', 'r');
while(($row=fgets($fp)) !== false){
	if($row==="\n") continue;
	
	$asn = explode(',', rtrim($row));
	// AS番号じゃない場合はcurrent_exceptionを更新
	if(!isset($asn[1])){
		switch ($asn[0]) {
			case 'nearby_country':	$current_exception = NEARBY_COUNTRY; break;
			case 'US_DoD':			$current_exception = US_DOD; break;
			case 'akamai':			$current_exception = AKAMAI; break;
		}
	}// AS番号の場合は$exception[asn(小)][asn(大)]に$current_exceptionを入れる
	else{
		list($asn1,$asn2) = $asn[0]<$asn[1]? $asn: [$asn[1],$asn[0]];
		$exception[$asn1][$asn2] = $current_exception;
	}
}
fclose($fp);

// type5の読み込み・分類
$ts = strtotime('2018-11-02');
$ts_end = strtotime('2018-11-02 23:55');
$fp_summary = fopen(FILTER_TYPE5_ADVERTISEMENT_SUMMARY.date('Ymd.Hi',$ts).'_'.date('Ymd.Hi',$ts_end).'.csv', 'w');
// タイトル行の出力
fwrite($fp_summary, 'date,NEARBY_COUNTRY,PRIVATE_ASN,US_DOD,AKAMAI,OTHER'.PHP_EOL);
for(; $ts<=$ts_end; $ts+=60*5){
	// それぞれのtypeのカウント用配列
	$count = array(1=>0, 2=>0, 3=>0, 4=>0, 5=>0);
	// ファイルオープン
	$ripe = MakeRIPEUpdateParam($ts);
	$fp = fopen($ripe['analyse_advertisement'], 'r');
	$fp_out = fopen($ripe['filter_type5_advertisement'], 'w');
	fwrite($fp_out, 'ip_prefix,asn,type,conflict_ip_prefix,conflict_asn,conflict_type'.PHP_EOL);
	// タイトル行をスキップ
	fgets($fp);
	// 1行ずつ読み込んで$countの該当するtypeを+1
	while(($row=fgets($fp))!==false){
		// typeが5のときだけ該当する行を出力
		$rowinfo = explode(',', rtrim($row));
		if((int)$rowinfo[2]===5){
			// AS番号をソート
			list($asn1,$asn2) = $rowinfo[1]<$rowinfo[4]? [$rowinfo[1],$rowinfo[4]]: [$rowinfo[4],$rowinfo[1]];
			// ホワイトリストにある組み合わせ
			if(isset($exception[$asn1][$asn2])) $type = $exception[$asn1][$asn2];
			// 片方か両方がプライベートAS
			elseif((64512<=$asn1 && $asn1<65534) || (64512<=$asn2 && $asn2<65534)) $type = PRIVATE_ASN;
			// akamai
			elseif((int)$asn1===35994 || (int)$asn2===35994) $type = AKAMAI;
			// 危ないやつ見つけた
			else $type = 5;
			$count[$type]++;

			// ファイルに出力
			fwrite($fp_out, rtrim($row).",$type".PHP_EOL);
		}
	}
	// 結果の出力をしてファイルクローズ
	fwrite($fp_summary, date('Y/m/d H:i', $ts).",{$count[1]},{$count[2]},{$count[3]},{$count[4]},{$count[5]}".PHP_EOL);
	fclose($fp);
	fclose($fp_out);
}
fclose($fp_summary);

// 出力

?>