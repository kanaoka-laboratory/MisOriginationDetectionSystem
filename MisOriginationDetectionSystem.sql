-- phpMyAdmin SQL Dump
-- version 4.7.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 11, 2018 at 09:26 AM
-- Server version: 10.3.7-MariaDB
-- PHP Version: 7.1.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `MisOriginationDetectionSystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `ConflictHistory`
--

CREATE TABLE `ConflictHistory` (
  `conflict_id` int(10) UNSIGNED NOT NULL COMMENT '衝突ID',
  `asn1` int(10) UNSIGNED NOT NULL COMMENT 'AS番号1',
  `asn2` int(10) UNSIGNED NOT NULL COMMENT 'AS番号2',
  `date_conflict` datetime NOT NULL COMMENT '衝突検出日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='フルルート取得時の衝突検出履歴';

-- --------------------------------------------------------

--
-- Table structure for table `DetectedUpdateHistory`
--

CREATE TABLE `DetectedUpdateHistory` (
  `id` int(10) UNSIGNED NOT NULL COMMENT '変更履歴ID',
  `asn` int(10) UNSIGNED NOT NULL COMMENT 'AS番号',
  `date_update` datetime NOT NULL COMMENT '変更日',
  `route_updated` varchar(15000) NOT NULL COMMENT '変更後経路'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='前回と今回のフルルートの更新履歴';

-- --------------------------------------------------------

--
-- Table structure for table `FiveMinuteUpdateLog`
--

CREATE TABLE `FiveMinuteUpdateLog` (
  `asn` int(10) UNSIGNED NOT NULL COMMENT 'AS番号',
  `date_update` datetime NOT NULL COMMENT '更新日時',
  `type_update` bit(1) NOT NULL COMMENT '0:削除，1:追加',
  `route_update` char(255) NOT NULL COMMENT '削除/追加する経路'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='5分おきのアップデートを保存したログ';

-- --------------------------------------------------------

--
-- Table structure for table `RouteInfov4`
--

CREATE TABLE `RouteInfov4` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'ID',
  `asn` int(10) UNSIGNED NOT NULL COMMENT 'AS番号',
  `route` varchar(16) NOT NULL COMMENT '経路プレフィックス',
  `ip_min` int(10) UNSIGNED NOT NULL COMMENT '最小IP',
  `ip_max` int(10) UNSIGNED NOT NULL COMMENT '最大IP'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='最新のフルルートから取得したIPv4経路';

-- --------------------------------------------------------

--
-- Table structure for table `RouteInfov6`
--

CREATE TABLE `RouteInfov6` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'ID',
  `asn` int(10) UNSIGNED NOT NULL COMMENT 'AS番号',
  `route` varchar(24) NOT NULL COMMENT '経路プレフィックス',
  `ip_min` char(32) NOT NULL COMMENT '最小IP',
  `ip_max` char(32) NOT NULL COMMENT '最大IP'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='最新のフルルートから取得したIPv6経路';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ConflictHistory`
--
ALTER TABLE `ConflictHistory`
  ADD PRIMARY KEY (`conflict_id`),
  ADD KEY `asn1` (`asn1`),
  ADD KEY `asn2` (`asn2`),
  ADD KEY `date_conflict` (`date_conflict`);

--
-- Indexes for table `DetectedUpdateHistory`
--
ALTER TABLE `DetectedUpdateHistory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asn` (`asn`,`date_update`);

--
-- Indexes for table `FiveMinuteUpdateLog`
--
ALTER TABLE `FiveMinuteUpdateLog`
  ADD KEY `asn` (`asn`);

--
-- Indexes for table `RouteInfov4`
--
ALTER TABLE `RouteInfov4`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asn` (`asn`,`route`),
  ADD KEY `ip_range` (`ip_min`,`ip_max`) USING BTREE,
  ADD KEY `route` (`route`);

--
-- Indexes for table `RouteInfov6`
--
ALTER TABLE `RouteInfov6`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asn` (`asn`,`route`),
  ADD KEY `ip_range` (`ip_min`,`ip_max`) USING BTREE,
  ADD KEY `route` (`route`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ConflictHistory`
--
ALTER TABLE `ConflictHistory`
  MODIFY `conflict_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '衝突ID';

--
-- AUTO_INCREMENT for table `DetectedUpdateHistory`
--
ALTER TABLE `DetectedUpdateHistory`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '変更履歴ID';

--
-- AUTO_INCREMENT for table `RouteInfov4`
--
ALTER TABLE `RouteInfov4`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- AUTO_INCREMENT for table `RouteInfov6`
--
ALTER TABLE `RouteInfov6`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
