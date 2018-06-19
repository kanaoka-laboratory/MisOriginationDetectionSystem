-- phpMyAdmin SQL Dump
-- version 4.7.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 18, 2018 at 02:29 AM
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
-- Table structure for table `ConflictExceptionAsn`
--

CREATE TABLE `ConflictExceptionAsn` (
  `exception_id` int(10) UNSIGNED NOT NULL,
  `asn` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='衝突の例外（意図的な衝突）のAS番号情報' ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `ConflictExceptionRoute`
--

CREATE TABLE `ConflictExceptionRoute` (
  `exception_id` int(10) UNSIGNED NOT NULL,
  `prefix` varchar(18) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='衝突の例外（意図的な衝突）の経路情報' ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `ConflictHistoryv4`
--

CREATE TABLE `ConflictHistoryv4` (
  `conflict_id` int(10) UNSIGNED NOT NULL COMMENT '衝突ID',
  `asn1` int(10) UNSIGNED NOT NULL COMMENT 'AS番号1',
  `asn2` int(10) UNSIGNED NOT NULL COMMENT 'AS番号2',
  `date_conflict` datetime NOT NULL COMMENT '衝突検出日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='フルルート取得時の衝突検出履歴';

-- --------------------------------------------------------

--
-- Table structure for table `ConflictHistoryv6`
--

CREATE TABLE `ConflictHistoryv6` (
  `conflict_id` int(10) UNSIGNED NOT NULL COMMENT '衝突ID',
  `asn1` int(10) UNSIGNED NOT NULL COMMENT 'AS番号1',
  `asn2` int(10) UNSIGNED NOT NULL COMMENT 'AS番号2',
  `date_conflict` datetime NOT NULL COMMENT '衝突検出日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='フルルート取得時の衝突検出履歴';

-- --------------------------------------------------------

--
-- Table structure for table `DetectedUpdateHistoryv4`
--

CREATE TABLE `DetectedUpdateHistoryv4` (
  `asn` int(10) UNSIGNED NOT NULL COMMENT 'AS番号',
  `date_update` datetime NOT NULL COMMENT '変更日',
  `route_updated` mediumtext DEFAULT NULL COMMENT '変更後経路'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='前回と今回のフルルートの更新履歴';

-- --------------------------------------------------------

--
-- Table structure for table `DetectedUpdateHistoryv6`
--

CREATE TABLE `DetectedUpdateHistoryv6` (
  `asn` int(10) UNSIGNED NOT NULL COMMENT 'AS番号',
  `date_update` datetime NOT NULL COMMENT '変更日',
  `route_updated` mediumtext DEFAULT NULL COMMENT '変更後経路'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='前回と今回のフルルートの更新履歴';

-- --------------------------------------------------------

--
-- Table structure for table `FiveMinuteUpdateLogv4`
--

CREATE TABLE `FiveMinuteUpdateLogv4` (
  `asn` int(10) UNSIGNED NOT NULL COMMENT 'AS番号',
  `date_update` datetime NOT NULL COMMENT '更新日時',
  `type_update` bit(1) NOT NULL COMMENT '0:削除，1:追加',
  `route_update` char(255) NOT NULL COMMENT '削除/追加する経路'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='5分おきのアップデートを保存したログ';

-- --------------------------------------------------------

--
-- Table structure for table `FiveMinuteUpdateLogv6`
--

CREATE TABLE `FiveMinuteUpdateLogv6` (
  `asn` int(10) UNSIGNED NOT NULL COMMENT 'AS番号',
  `date_update` datetime NOT NULL COMMENT '更新日時',
  `type_update` bit(1) NOT NULL COMMENT '0:削除，1:追加',
  `route_update` char(255) NOT NULL COMMENT '削除/追加する経路'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='5分おきのアップデートを保存したログ';

-- --------------------------------------------------------

--
-- Table structure for table `MetaInfo`
--

CREATE TABLE `MetaInfo` (
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `RouteInfov4`
--

CREATE TABLE `RouteInfov4` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'ID',
  `asn` int(10) UNSIGNED NOT NULL COMMENT 'AS番号',
  `route` varchar(18) NOT NULL COMMENT '経路プレフィックス',
  `ip_min` int(10) UNSIGNED NOT NULL COMMENT '最小IP',
  `ip_max` int(10) UNSIGNED NOT NULL COMMENT '最大IP'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='最新のフルルートから取得したIPv4経路' ROW_FORMAT=COMPACT;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='最新のフルルートから取得したIPv6経路' ROW_FORMAT=COMPACT;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ConflictExceptionAsn`
--
ALTER TABLE `ConflictExceptionAsn`
  ADD PRIMARY KEY (`exception_id`,`asn`),
  ADD KEY `asn` (`asn`);

--
-- Indexes for table `ConflictExceptionRoute`
--
ALTER TABLE `ConflictExceptionRoute`
  ADD PRIMARY KEY (`exception_id`);

--
-- Indexes for table `ConflictHistoryv4`
--
ALTER TABLE `ConflictHistoryv4`
  ADD PRIMARY KEY (`conflict_id`),
  ADD KEY `asn1` (`asn1`),
  ADD KEY `asn2` (`asn2`),
  ADD KEY `date_conflict` (`date_conflict`);

--
-- Indexes for table `ConflictHistoryv6`
--
ALTER TABLE `ConflictHistoryv6`
  ADD PRIMARY KEY (`conflict_id`),
  ADD KEY `asn1` (`asn1`),
  ADD KEY `asn2` (`asn2`),
  ADD KEY `date_conflict` (`date_conflict`);

--
-- Indexes for table `DetectedUpdateHistoryv4`
--
ALTER TABLE `DetectedUpdateHistoryv4`
  ADD UNIQUE KEY `asn` (`asn`,`date_update`);

--
-- Indexes for table `DetectedUpdateHistoryv6`
--
ALTER TABLE `DetectedUpdateHistoryv6`
  ADD UNIQUE KEY `asn` (`asn`,`date_update`);

--
-- Indexes for table `FiveMinuteUpdateLogv4`
--
ALTER TABLE `FiveMinuteUpdateLogv4`
  ADD KEY `asn` (`asn`);

--
-- Indexes for table `FiveMinuteUpdateLogv6`
--
ALTER TABLE `FiveMinuteUpdateLogv6`
  ADD KEY `asn` (`asn`);

--
-- Indexes for table `MetaInfo`
--
ALTER TABLE `MetaInfo`
  ADD PRIMARY KEY (`name`);

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
-- AUTO_INCREMENT for table `ConflictExceptionRoute`
--
ALTER TABLE `ConflictExceptionRoute`
  MODIFY `exception_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `ConflictHistoryv4`
--
ALTER TABLE `ConflictHistoryv4`
  MODIFY `conflict_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '衝突ID';

--
-- AUTO_INCREMENT for table `ConflictHistoryv6`
--
ALTER TABLE `ConflictHistoryv6`
  MODIFY `conflict_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '衝突ID';

--
-- AUTO_INCREMENT for table `RouteInfov4`
--
ALTER TABLE `RouteInfov4`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=744445;

--
-- AUTO_INCREMENT for table `RouteInfov6`
--
ALTER TABLE `RouteInfov6`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=56072;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
