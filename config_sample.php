<?php
//------------ グローバル設定 ------------//
// ログをファイルだけでなく標準出力にも表示させる
define('LOG_STDOUT', true);
// 不正なネットマスク長をもつIPプレフィックスを無視する
define('IGNORE_ILLEGAL_SUBNET', true);

//------------ DB設定 ------------//
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'username');
define('MYSQL_PASS', 'password');
define('MYSQL_DB', 'MisOriginationDetectionSystem');

//------------ Custom Search API ------------//
define('CSA_API_KEY', 'Google Custom Search API Key');
define('CSA_SEARCH_ENGINE_ID', 'Google Custom Search Search Engine ID');

//==================== RC以下に出力 ====================//
//------------ RC設定 ------------//
define('DIR_RC', array(
				'ripe_rc00'			=> 'data/ripe_rc00/',
				'ripe_rc01'			=> 'data/ripe_rc01/',
				'routeviews_oregon'	=> 'data/routeviews_oregon/'));

//------------ GetRIPE, GetRIPEUpdate ------------//
// データの場所
define('BGP_FULLROUTE_DL',			'BGPFullRouteDL/');
define('BGP_FULLROUTE_BGPSCANNER',	'BGPFullRouteBGPScanner/');
define('BGP_FULLROUTE_PHPDATA',		'BGPFullRoutePHPData/');
define('BGP_UPDATE_DL',				'BGPUpdateDL/');
define('BGP_UPDATE_BGPSCANNER',		'BGPUpdateBGPScanner/');

//------------ TrackOriginExactChangedPrefix(2), TrackOriginIncludeChangedPrefix(2) ------------//
// データの場所
define('TRACK_ORIGIN_CHANGED_PREFIX',  'TrackOriginChangedPrefix/');

//==================== data直下に出力 ====================//
//------------ AnalyseBGPUpdate(Summary) ------------//
define('ANALYSE_BGP_UPDATE', 'AnalyseBGPUpdate/');

//------------ FilterSuspiciousAdvertisement(Summary) ------------//
define('FILTER_SUSPICIOUS_ADVERTISEMENT', 'data/FilterSuspiciousAdvertisement/');

//------------ SummaryCountryDistance ------------//
define('SUMMARY_COUNTRY_DISTANCE', 'data/SummaryCountryDistance/');

//==================== アップデートの間隔(分) ====================//
define('UPDATE_INTERVAL', array(
				'ripe_rc00'			=> 5,
				'ripe_rc01'			=> 5,
				'routeviews_oregon'	=> 15));

//==================== 衝突のタイプ ====================//
// プライベートAS番号によるハイジャック
define('CONFLICT_TYPE_PRIVATE_ASN', 0);
// 怪しいハイジャックイベント
define('CONFLICT_TYPE_SUSPICIOUS', 1);
define('CONFLICT_TYPE_SUSPICIOUS_CHECKED', 2);
// 手動ホワイトリスト
define('CONFLICT_TYPE_WHITELIST_OTHER', 10);
define('CONFLICT_TYPE_WHITELIST_SAME_ORGANIZATION', 11);
define('CONFLICT_TYPE_WHITELIST_AKAMAI', 12);
define('CONFLICT_TYPE_WHITELIST_US_DOD', 13);
define('CONFLICT_TYPE_WHITELIST_NEARBY_AS', 14);
define('CONFLICT_TYPE_WHITELIST_DDOS_MITIGATION', 15);
define('CONFLICT_TYPE_WHITELIST_DDOS_MITIGATION_CLIENT', 16);
define('CONFLICT_TYPE_WHITELIST_AFFINITY_AS', 17);
define('CONFLICT_TYPE_WHITELIST_DEFENSIVE_UPDATE', 18);
define('CONFLICT_TYPE_WHITELIST_GOVERNMENT_BLOCKING', 19);
define('CONFLICT_TYPE_WHITELIST_UNIVERSITY_PROJECT', 20);
define('CONFLICT_TYPE_WHITELIST_ADDRESS_LEASE', 21);
// 手動ブラックリスト
define('CONFLICT_TYPE_BLACKLIST_OTHER', 50);
define('CONFLICT_TYPE_BLACKLIST_IANA_RESERVE', 51);
define('CONFLICT_TYPE_BLACKLIST_4BIT_ASN', 52);
define('CONFLICT_TYPE_BLACKLIST_UNASSIGNED_ASN', 53);
define('CONFLICT_TYPE_BLACKLIST_TYPO', 90);

// 自動ホワイトリスト
define('CONFLICT_TYPE_CONNECTED_BY_LAND', 100);
define('CONFLICT_TYPE_CONNECTED_BY_SUBMARINE_CABLE', 101);
define('CONFLICT_TYPE_MANRS', 102);
define('CONFLICT_TYPE_SAME_COUNTRY', 110);

//------------ CronASCountry ------------//
define('AS_COUNTRY', 'data/ASCountry/');

// UPDATE_ROUTEの内部配列
define('UPDATE_ROUTE_ASN', 0);
define('UPDATE_ROUTE_ROUTE', 1);
define('UPDATE_ROUTE_IP_MIN', 2);
define('UPDATE_ROUTE_IP_MAX', 3);

// ASCountryの内部配列
define('ASCOUNTRY_COUNTRY', 0);
define('ASCOUNTRY_CREDIBLE', 1);
define('ASCOUNTRY_DATE_SINCE', 2);
define('ASCOUNTRY_DATE_UNTIL', 3);

//------------ ImportSubmarineCableList ------------//
define('SUBMARINE_CABLE_LIST', 'data/Whitelist/SubmarineCableList/');
?>
