-- MySQL dump 10.16  Distrib 10.1.41-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: MODS2018
-- ------------------------------------------------------
-- Server version	10.1.41-MariaDB-0ubuntu0.18.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `CountryInfo`
--

LOCK TABLES `CountryInfo` WRITE;
/*!40000 ALTER TABLE `CountryInfo` DISABLE KEYS */;
INSERT INTO `CountryInfo` VALUES ('AD','ANDORRA','ripencc'),('AE','UNITED ARAB EMIRATES','ripencc'),('AF','AFGHANISTAN','apnic'),('AG','ANTIGUA AND BARBUDA','arin'),('AI','ANGUILLA','arin'),('AL','ALBANIA','ripencc'),('AM','ARMENIA','ripencc'),('AO','ANGOLA','afrinic'),('AQ','ANTARCTICA','arin'),('AR','ARGENTINA','lacnic'),('AS','AMERICAN SAMOA','apnic'),('AT','AUSTRIA','ripencc'),('AU','AUSTRALIA','apnic'),('AW','ARUBA','lacnic'),('AX','ÅLAND ISLANDS','ripencc'),('AZ','AZERBAIJAN','ripencc'),('BA','BOSNIA AND HERZEGOWINA','ripencc'),('BB','BARBADOS','arin'),('BD','BANGLADESH','apnic'),('BE','BELGIUM','ripencc'),('BF','BURKINA FASO','afrinic'),('BG','BULGARIA','ripencc'),('BH','BAHRAIN','ripencc'),('BI','BURUNDI','afrinic'),('BJ','BENIN','afrinic'),('BL','SAINT BARTHÉLEMY','arin'),('BM','BERMUDA','arin'),('BN','BRUNEI DARUSSALAM','apnic'),('BO','BOLIVIA, PLURINATIONAL STATE OF','lacnic'),('BQ','BONAIRE, SINT EUSTATIUS AND SABA','lacnic'),('BR','BRAZIL','lacnic'),('BS','BAHAMAS','arin'),('BT','BHUTAN','apnic'),('BV','BOUVET ISLAND','arin'),('BW','BOTSWANA','afrinic'),('BY','BELARUS','ripencc'),('BZ','BELIZE','lacnic'),('CA','CANADA','arin'),('CC','COCOS (KEELING) ISLANDS','apnic'),('CD','CONGO, THE DEMOCRATIC REPUBLIC OF THE','afrinic'),('CF','CENTRAL AFRICAN REPUBLIC','afrinic'),('CG','CONGO','afrinic'),('CH','SWITZERLAND','ripencc'),('CI','COTE D\'IVOIRE','afrinic'),('CK','COOK ISLANDS','apnic'),('CL','CHILE','lacnic'),('CM','CAMEROON','afrinic'),('CN','CHINA','apnic'),('CO','COLOMBIA','lacnic'),('CR','COSTA RICA','lacnic'),('CU','CUBA','lacnic'),('CV','CAPE VERDE','afrinic'),('CW','CURAÇAO','lacnic'),('CX','CHRISTMAS ISLAND','apnic'),('CY','CYPRUS','ripencc'),('CZ','CZECHIA','ripencc'),('DE','GERMANY','ripencc'),('DJ','DJIBOUTI','afrinic'),('DK','DENMARK','ripencc'),('DM','DOMINICA','arin'),('DO','DOMINICAN REPUBLIC','lacnic'),('DZ','ALGERIA','afrinic'),('EC','ECUADOR','lacnic'),('EE','ESTONIA','ripencc'),('EG','EGYPT','afrinic'),('EH','WESTERN SAHARA','afrinic'),('ER','ERITREA','afrinic'),('ES','SPAIN','ripencc'),('ET','ETHIOPIA','afrinic'),('FI','FINLAND','ripencc'),('FJ','FIJI','apnic'),('FK','FALKLAND ISLANDS (MALVINAS)','lacnic'),('FM','MICRONESIA, FEDERATED STATES OF','apnic'),('FO','FAROE ISLANDS','ripencc'),('FR','FRANCE','ripencc'),('GA','GABON','afrinic'),('GB','UNITED KINGDOM OF GREAT BRITAIN AND NORTHERN IRELAND*','ripencc'),('GD','GRENADA','arin'),('GE','GEORGIA','ripencc'),('GF','FRENCH GUIANA','lacnic'),('GG','GUERNSEY','ripencc'),('GH','GHANA','afrinic'),('GI','GIBRALTAR','ripencc'),('GL','GREENLAND','ripencc'),('GM','GAMBIA','afrinic'),('GN','GUINEA','afrinic'),('GP','GUADELOUPE','arin'),('GQ','EQUATORIAL GUINEA','afrinic'),('GR','GREECE','ripencc'),('GS','SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS','lacnic'),('GT','GUATEMALA','lacnic'),('GU','GUAM','apnic'),('GW','GUINEA-BISSAU','afrinic'),('GY','GUYANA','lacnic'),('HK','HONG KONG','apnic'),('HM','HEARD AND MC DONALD ISLANDS','arin'),('HN','HONDURAS','lacnic'),('HR','CROATIA (local name: Hrvatska)','ripencc'),('HT','HAITI','lacnic'),('HU','HUNGARY','ripencc'),('ID','INDONESIA','apnic'),('IE','IRELAND','ripencc'),('IL','ISRAEL','ripencc'),('IM','ISLE OF MAN','ripencc'),('IN','INDIA','apnic'),('IO','BRITISH INDIAN OCEAN TERRITORY','apnic'),('IQ','IRAQ','ripencc'),('IR','IRAN (ISLAMIC REPUBLIC OF)','ripencc'),('IS','ICELAND','ripencc'),('IT','ITALY','ripencc'),('JE','JERSEY','ripencc'),('JM','JAMAICA','arin'),('JO','JORDAN','ripencc'),('JP','JAPAN','apnic'),('KE','KENYA','afrinic'),('KG','KYRGYZSTAN','ripencc'),('KH','CAMBODIA','apnic'),('KI','KIRIBATI','apnic'),('KM','COMOROS','afrinic'),('KN','SAINT KITTS AND NEVIS','arin'),('KP','KOREA, DEMOCRATIC PEOPLE\'S REPUBLIC OF','apnic'),('KR','KOREA, REPUBLIC OF','apnic'),('KW','KUWAIT','ripencc'),('KY','CAYMAN ISLANDS','arin'),('KZ','KAZAKHSTAN','ripencc'),('LA','LAO PEOPLE\'S DEMOCRATIC REPUBLIC','apnic'),('LB','LEBANON','ripencc'),('LC','SAINT LUCIA','arin'),('LI','LIECHTENSTEIN','ripencc'),('LK','SRI LANKA','apnic'),('LR','LIBERIA','afrinic'),('LS','LESOTHO','afrinic'),('LT','LITHUANIA','ripencc'),('LU','LUXEMBOURG','ripencc'),('LV','LATVIA','ripencc'),('LY','LIBYA','afrinic'),('MA','MOROCCO','afrinic'),('MC','MONACO','ripencc'),('MD','MOLDOVA, REPUBLIC OF','ripencc'),('ME','MONTENEGRO','ripencc'),('MF','SAINT MARTIN (FRENCH PART)','arin'),('MG','MADAGASCAR','afrinic'),('MH','MARSHALL ISLANDS','apnic'),('MK','MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF','ripencc'),('ML','MALI','afrinic'),('MM','MYANMAR','apnic'),('MN','MONGOLIA','apnic'),('MO','MACAO','apnic'),('MP','NORTHERN MARIANA ISLANDS','apnic'),('MQ','MARTINIQUE','arin'),('MR','MAURITANIA','afrinic'),('MS','MONTSERRAT','arin'),('MT','MALTA','ripencc'),('MU','MAURITIUS','afrinic'),('MV','MALDIVES','apnic'),('MW','MALAWI','arin'),('MX','MEXICO','lacnic'),('MY','MALAYSIA','apnic'),('MZ','MOZAMBIQUE','afrinic'),('NA','NAMIBIA','afrinic'),('NC','NEW CALEDONIA','apnic'),('NE','NIGER','afrinic'),('NF','NORFOLK ISLAND','apnic'),('NG','NIGERIA','afrinic'),('NI','NICARAGUA','lacnic'),('NL','NETHERLANDS','ripencc'),('NO','NORWAY','ripencc'),('NP','NEPAL','apnic'),('NR','NAURU','apnic'),('NU','NIUE','apnic'),('NZ','NEW ZEALAND','apnic'),('OM','OMAN','ripencc'),('PA','PANAMA','lacnic'),('PE','PERU','lacnic'),('PF','FRENCH POLYNESIA','apnic'),('PG','PAPUA NEW GUINEA','apnic'),('PH','PHILIPPINES','apnic'),('PK','PAKISTAN','apnic'),('PL','POLAND','ripencc'),('PM','SAINT PIERRE AND MIQUELON','arin'),('PN','PITCAIRN','apnic'),('PR','PUERTO RICO','arin'),('PS','PALESTINE, STATE OF','ripencc'),('PT','PORTUGAL','ripencc'),('PW','PALAU','apnic'),('PY','PARAGUAY','lacnic'),('QA','QATAR','ripencc'),('RE','REUNION','afrinic'),('RO','ROMANIA','ripencc'),('RS','SERBIA','ripencc'),('RU','RUSSIAN FEDERATION','ripencc'),('RW','RWANDA','afrinic'),('SA','SAUDI ARABIA','ripencc'),('SB','SOLOMON ISLANDS','apnic'),('SC','SEYCHELLES','afrinic'),('SD','SUDAN','afrinic'),('SE','SWEDEN','ripencc'),('SG','SINGAPORE','apnic'),('SH','SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA','arin'),('SI','SLOVENIA','ripencc'),('SJ','SVALBARD AND JAN MAYEN ISLANDS','ripencc'),('SK','SLOVAKIA','ripencc'),('SL','SIERRA LEONE','afrinic'),('SM','SAN MARINO','ripencc'),('SN','SENEGAL','afrinic'),('SO','SOMALIA','afrinic'),('SR','SURINAME','lacnic'),('SS','SOUTH SUDAN','afrinic'),('ST','SAO TOME AND PRINCIPE','afrinic'),('SV','EL SALVADOR','lacnic'),('SX','SINT MAARTEN (DUTCH PART)','lacnic'),('SY','SYRIAN ARAB REPUBLIC','ripencc'),('SZ','ESWATINI','afrinic'),('TC','TURKS AND CAICOS ISLANDS','arin'),('TD','CHAD','afrinic'),('TF','FRENCH SOUTHERN TERRITORIES','apnic'),('TG','TOGO','afrinic'),('TH','THAILAND','apnic'),('TJ','TAJIKISTAN','ripencc'),('TK','TOKELAU','apnic'),('TL','TIMOR-LESTE','apnic'),('TM','TURKMENISTAN','ripencc'),('TN','TUNISIA','afrinic'),('TO','TONGA','apnic'),('TR','TURKEY','ripencc'),('TT','TRINIDAD AND TOBAGO','lacnic'),('TV','TUVALU','apnic'),('TW','TAIWAN, PROVINCE OF CHINA','apnic'),('TZ','TANZANIA, UNITED REPUBLIC OF','afrinic'),('UA','UKRAINE','ripencc'),('UG','UGANDA','afrinic'),('UM','UNITED STATES MINOR OUTLYING ISLANDS','arin'),('US','UNITED STATES OF AMERICA','arin'),('UY','URUGUAY','lacnic'),('UZ','UZBEKISTAN','ripencc'),('VA','HOLY SEE','ripencc'),('VC','SAINT VINCENT AND THE GRENADINES','arin'),('VE','VENEZUELA, BOLIVARIAN REPUBLIC OF','lacnic'),('VG','VIRGIN ISLANDS (BRITISH)','arin'),('VI','VIRGIN ISLANDS (U.S.)','arin'),('VN','VIET NAM','apnic'),('VU','VANUATU','apnic'),('WF','WALLIS AND FUTUNA ISLANDS','apnic'),('WS','SAMOA','apnic'),('YE','YEMEN','ripencc'),('YT','MAYOTTE','afrinic'),('ZA','SOUTH AFRICA','afrinic'),('ZM','ZAMBIA','afrinic'),('ZW','ZIMBABWE','afrinic');
/*!40000 ALTER TABLE `CountryInfo` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-09-09 10:10:26