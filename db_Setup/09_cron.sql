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
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `CronProgress`
--

LOCK TABLES `CronProgress` WRITE;
/*!40000 ALTER TABLE `CronProgress` DISABLE KEYS */;
INSERT INTO `CronProgress` VALUES (1,'BGPFullRoute','ripe_rc00','2019-09-09 00:00','2019-09-09 00:00',0,16,'\0'),(2,'BGPUpdate','ripe_rc00','2019-08-21 17:10','2019-08-21 07:55',0,16,''),(3,'BGPFullRoute','routeviews_oregon','2019-09-09 00:00','2019-09-09 00:00',0,16,'\0'),(4,'BGPUpdate','routeviews_oregon','2019-09-01 08:00','2019-08-31 23:45',0,32,''),(5,'BGPFullRoute','ripe_rc01','2019-09-09 00:00','2019-09-09 00:00',0,16,'\0'),(6,'BGPUpdate','ripe_rc01','2019-08-27 16:55','2019-08-27 07:55',0,16,''),(100,'FilterSuspiciousBGPUpdate','','',NULL,0,0,'\0'),(101,'ASCountry','apnic','2019-09-09','2019-09-09',0,24,'\0'),(102,'ASCountry','arin','2019-09-08','2019-09-08',0,24,'\0'),(103,'ASCountry','ripencc','2019-09-07','2019-09-07',22,24,'\0'),(104,'ASCountry','lacnic','2019-09-07','2019-09-07',19,24,'\0'),(105,'ASCountry','afrinic','2019-09-08','2019-09-08',2,24,'\0');
/*!40000 ALTER TABLE `CronProgress` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-09-09 10:29:12
