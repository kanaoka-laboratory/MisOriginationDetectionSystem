<?php
require_once('config.php');
require_once('import/mysqli.php');
$mysqli = new mymysqli();
$conflict_type_list = array(
    0=>"PrivateASN",
    1=>"Suspicious",
    2=>"Suspicious_checked",
    11=>"SameOrganization",
    12=>"Akamai",
    13=>"USDoD",
    14=>"NearbyAS",
    15=>"DDoSMitigation",
    16=>"DDoSMitigationClient",
    17=>"AffinityAS",
    18=>"DefensiveUpdate",
    19=>"GovernmentBlock",
    20=>"UniversityProject",
    21=>"AddressLease",
    51=>"IANA Reserved",
    52=>"ASTrans",
    53=>"UnassignedASN",
    90=>"Typo",
    100=>"ConnectedByLand",
    101=>"ConnectedBySubmarineCable",
    102=>"MANRS",
    110=>"SameCountry"
);

foreach($conflict_type_list as $conflict_type => $description){
    $fp = fopen("../raw/$conflict_type-$description.csv",'w');
    fwrite($fp, "update_id,suspicious_id,ip_protocol,conflict_type,adv_type,ip_prefix,conflict_ip_prefix,asn,conflict_asn,asn_cc,conflict_asn_cc,asn_whois,conflict_asn_whois,count,rc,date_update,date_detection".PHP_EOL);
    
    $query = "SELECT update_id,t1.suspicious_id,ip_protocol,conflict_type,adv_type,ip_prefix,conflict_ip_prefix,t1.asn,t1.conflict_asn,asn_cc,conflict_asn_cc,asn_whois,conflict_asn_whois,count,rc,date_update,date_detection FROM MODS2018.PrefixConflictedUpdate as t1 left join MisOriginationDetectionSystem.SuspiciousAsnSet as t2 on t1.suspicious_id=t2.suspicious_id WHERE conflict_type=$conflict_type";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        fputcsv($fp, $row);
    }
    fclose($fp);
}
?>