<?php

$whitelist = array();
$fp = fopen('script/NeighborCountryList.csv', 'r');
// タイトルスキップ
fgets($fp);
while(($row = fgetcsv($fp,100))){
	// 行情報の読み込み
	$rir = str_replace(' ', '', $row[0]);
	$country_name = str_replace("'", "\\'",$row[1]);
	$country = $row[2];
	$neighbor_country = array_slice($row, 3);
	
	// 国情報
	echo "insert into CountryInfo (cc,country_name,rir) values ('$country','$country_name','$rir');".PHP_EOL;

	// ホワイトリスト
	foreach ($neighbor_country as $country2) {
		if($country2==='') break;
		$whitelist[$country][] = $country2;
		$whitelist[$country2][] = $country;
	}
}
fclose($fp);

// 組み合わせの出力
foreach($whitelist as $cc => $conflict_ccs) {
	foreach ($conflict_ccs as $conflict_cc) {
		echo "insert into ConflictCountryWhitelist (conflict_type,cc,conflict_cc) values (2,'$cc','$conflict_cc');".PHP_EOL;
	}
}
?>
