<?php
function GetWhoisInfoFromAsn($asn){
	if(ctype_digit($asn)) $asn = (int)$asn;
	else showLog("AS番号が不正です: $asn", true);
	$whois = GetWhois($asn);
	showLog("asn:  ".$whois["asn"]);
	showLog("host: ".$whois["host"]);
	showLog("name: ".$whois["name"]);
}
?>
