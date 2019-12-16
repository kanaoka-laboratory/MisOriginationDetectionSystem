-- MySQL dump 10.16  Distrib 10.1.41-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: MisOriginationDetectionSystem
-- ------------------------------------------------------
-- Server version	10.1.41-MariaDB-0ubuntu0.18.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `MisOriginationDetectionSystem`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `MisOriginationDetectionSystem` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

USE `MisOriginationDetectionSystem`;

--
-- Table structure for table `ASCountry`
--

DROP TABLE IF EXISTS `ASCountry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ASCountry` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `asn` int(11) NOT NULL COMMENT 'AS番号',
  `country` char(2) NOT NULL COMMENT '国コード',
  `rir` enum('apnic','arin','ripencc','lacnic','afrinic') NOT NULL COMMENT '地域レジストリ',
  `date_since` date NOT NULL COMMENT '日時（開始日）',
  `date_until` date NOT NULL COMMENT '日時（終了日）',
  PRIMARY KEY (`id`),
  KEY `date_until` (`date_until`,`date_since`)
) ENGINE=InnoDB AUTO_INCREMENT=96931 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='ASと国の紐付け情報';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ConflictAsnWhiteList`
--

DROP TABLE IF EXISTS `ConflictAsnWhiteList`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ConflictAsnWhiteList` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `conflict_type` tinyint(4) NOT NULL COMMENT '例外の種類',
  `asn` int(10) unsigned NOT NULL COMMENT 'ハイジャックAS番号',
  `conflict_asn` int(10) unsigned NOT NULL COMMENT '被ハイジャックAS番号',
  `date_register` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '登録日',
  `disabled` datetime DEFAULT NULL COMMENT '無効化フラグ',
  PRIMARY KEY (`id`),
  UNIQUE KEY `asn` (`asn`,`conflict_asn`,`disabled`)
) ENGINE=InnoDB AUTO_INCREMENT=1035 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='ASレベルのホワイトリスト';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ConflictCountryWhiteList`
--

DROP TABLE IF EXISTS `ConflictCountryWhiteList`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ConflictCountryWhiteList` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `conflict_type` tinyint(4) NOT NULL COMMENT '例外の種類',
  `cc` char(2) NOT NULL COMMENT '国コード（ハイジャック側）',
  `conflict_cc` char(2) NOT NULL COMMENT '国コード（被ハイジャック側）',
  PRIMARY KEY (`id`),
  KEY `country` (`cc`,`conflict_cc`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3767 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='国レベルのホワイトリスト';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CountryDistance`
--

DROP TABLE IF EXISTS `CountryDistance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CountryDistance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cc1` char(2) NOT NULL,
  `cc2` char(2) NOT NULL,
  `distance` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53131 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CountryInfo`
--

DROP TABLE IF EXISTS `CountryInfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CountryInfo` (
  `cc` char(2) NOT NULL COMMENT '国コード',
  `country_name` varchar(255) NOT NULL COMMENT '国名',
  `rir` enum('apnic','arin','ripencc','lacnic','afrinic','other') NOT NULL COMMENT '所属RIR',
  PRIMARY KEY (`cc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='国情報';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CronProgress`
--

DROP TABLE IF EXISTS `CronProgress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CronProgress` (
  `id` int(11) NOT NULL COMMENT 'id',
  `cron` varchar(191) NOT NULL COMMENT 'Cron名（固定）',
  `name` varchar(191) NOT NULL COMMENT '属性名（固定）',
  `value` varchar(191) NOT NULL COMMENT '値1（可変）',
  `value2` varchar(191) DEFAULT NULL COMMENT '値2（可変）',
  `failed_count` int(11) NOT NULL COMMENT '連続失敗数',
  `max_failed_count` int(11) NOT NULL COMMENT '連続失敗数の閾値',
  `processing` bit(1) NOT NULL COMMENT '実行中フラグ',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cron` (`cron`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='cronの進捗管理';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PrefixConflictedUpdate`
--

DROP TABLE IF EXISTS `PrefixConflictedUpdate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PrefixConflictedUpdate` (
  `update_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `ip_protocol` enum('v4','v6') NOT NULL COMMENT 'v4 or v6',
  `adv_type` tinyint(4) NOT NULL COMMENT 'advertisement_type',
  `asn` int(10) unsigned NOT NULL COMMENT '広告したAS',
  `conflict_asn` varchar(191) NOT NULL COMMENT '衝突先AS',
  `ip_prefix` varchar(24) NOT NULL COMMENT '広告されたIP prefix',
  `conflict_ip_prefix` varchar(24) NOT NULL COMMENT '衝突先IP prefix',
  `date_update` datetime NOT NULL COMMENT '広告された時間',
  `rc` varchar(191) NOT NULL COMMENT 'ルートコレクタ',
  `count` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '重複数',
  `suspicious_id` int(10) unsigned DEFAULT NULL COMMENT 'SuspiciousUpdateのsuspicious_id',
  PRIMARY KEY (`update_id`),
  UNIQUE KEY `confliction` (`asn`,`conflict_asn`,`ip_prefix`,`conflict_ip_prefix`,`rc`) USING BTREE,
  KEY `adv_type` (`adv_type`),
  KEY `suspicious_id` (`suspicious_id`),
  CONSTRAINT `prefixconflictedupdate_ibfk_1` FOREIGN KEY (`suspicious_id`) REFERENCES `SuspiciousAsnSet` (`suspicious_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10535046 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='IPプレフィックスが衝突しているBGP広告';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SuspiciousAsnSet`
--

DROP TABLE IF EXISTS `SuspiciousAsnSet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SuspiciousAsnSet` (
  `suspicious_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'suspicious_id',
  `conflict_type` tinyint(4) NOT NULL COMMENT 'conflict_type（1, 10〜）',
  `asn` int(10) unsigned NOT NULL COMMENT '広告したAS',
  `conflict_asn` varchar(191) NOT NULL COMMENT '衝突先AS',
  `asn_cc` char(2) NOT NULL COMMENT '国コード',
  `conflict_asn_cc` varchar(191) NOT NULL COMMENT '衝突先国コード',
  `asn_whois` varchar(191) NOT NULL COMMENT 'whois情報',
  `conflict_asn_whois` varchar(191) NOT NULL COMMENT '衝突先whois情報',
  `date_detection` datetime NOT NULL COMMENT '検知日時',
  PRIMARY KEY (`suspicious_id`),
  UNIQUE KEY `conflict_info` (`asn`,`conflict_asn`,`asn_cc`,`conflict_asn_cc`) USING BTREE,
  KEY `date_detection` (`date_detection`),
  KEY `conflict_type` (`conflict_type`)
) ENGINE=InnoDB AUTO_INCREMENT=124241 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='怪しいASの組み合わせ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Whois`
--

DROP TABLE IF EXISTS `Whois`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Whois` (
  `whois_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(191) NOT NULL,
  `query` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `body` mediumtext NOT NULL,
  `date_query` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`whois_id`),
  UNIQUE KEY `query` (`query`,`date_query`)
) ENGINE=InnoDB AUTO_INCREMENT=9026 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='whois情報';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

--
-- Table structure for table `MOASCleaningIgnore`
--

DROP TABLE IF EXISTS `MOASCleaningIgnore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MOASCleaningIgnore` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `date_register` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC ;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-12-16 23:11:31
