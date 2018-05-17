<?php
// IPの重複を検知する関数の読み込み
require_once('detectIpPrefixOverlap.php');

var_dump(detectIpPrefixOverlap("172.18.32.0/20", "172.18.11.0/24"));

?>
