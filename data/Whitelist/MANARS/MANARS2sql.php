<?php
// conflict_typeを設定
$conflict_type_manrs = 102;

if(!isset($argv[1]) || !is_file($argv[1])) exit("input file not found".PHP_EOL);

$asn_list = array();

$fp = fopen($argv[1], "r");
fgets($fp);
while(($row=fgetcsv($fp)) !== false){
	foreach(explode(";", $row[2]) as $asn){
		$asn_list[(int)trim($asn)] = true;
	}
}
fclose($fp);

ksort($asn_list);
unset($asn_list[0]);

$today = (new DateTime("now", new DateTimeZone("UTC")))->format("Y-m-d H:i:s");
echo "start transaction;".PHP_EOL;
foreach($asn_list as $asn => $null){
	echo "insert into ConflictAsnWhiteList (conflict_type,asn,conflict_asn,date_register) values($conflict_type_manrs,$asn,0,'$today');".PHP_EOL;
}
echo "commit;".PHP_EOL;
?>
