-- MySQL dump 10.13  Distrib 5.7.27, for Linux (x86_64)
--
-- Host: localhost    Database: malshare_db
-- ------------------------------------------------------
-- Server version	5.7.27-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tbl_public_searches`
--

DROP TABLE IF EXISTS `tbl_public_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_public_searches` (
  `query` text,
  `ts` int(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_sample_partners`
--

DROP TABLE IF EXISTS `tbl_sample_partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_sample_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `display_name` varchar(50) NOT NULL DEFAULT 'Sample Feed Partner',
  `private_name` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_sample_sources`
--

DROP TABLE IF EXISTS `tbl_sample_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_sample_sources` (
  `id` int(10) unsigned NOT NULL,
  `source` text,
  `added` int(20) DEFAULT NULL,
  `sample_partner_submission` int(11) DEFAULT NULL,
  KEY `id` (`id`),
  KEY `sample_source` (`source`(128)),
  KEY `idx_tbl_sample_sourced_added` (`added`),
  KEY `sample_partner_submission` (`sample_partner_submission`),
  CONSTRAINT `tbl_sample_sources_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbl_samples` (`id`),
  CONSTRAINT `tbl_sample_sources_ibfk_2` FOREIGN KEY (`sample_partner_submission`) REFERENCES `tbl_sample_partners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_samples`
--

DROP TABLE IF EXISTS `tbl_samples`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_samples` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `md5` varchar(32) NOT NULL,
  `sha1` varchar(42) NOT NULL,
  `sha256` varchar(66) NOT NULL,
  `ssdeep` text,
  `added` int(15) NOT NULL,
  `ftype` varchar(32) NOT NULL,
  `counter` int(12) NOT NULL,
  `path` text,
  `processed` tinyint(20) NOT NULL DEFAULT '0',
  `yara` json DEFAULT NULL,
  `pending` tinyint(2) DEFAULT NULL,
  `subType` int(5) DEFAULT NULL,
  `filenames` text,
  `parent_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `md5` (`md5`),
  KEY `added` (`added`),
  KEY `sample_id` (`md5`,`sha1`,`sha256`),
  KEY `idx_tbl_samples_parent_id` (`pending`),
  KEY `idx_tbl_samples_pending` (`pending`)
) ENGINE=InnoDB AUTO_INCREMENT=5314031 DEFAULT CHARSET=ascii;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_searches`
--

DROP TABLE IF EXISTS `tbl_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_searches` (
  `query` text,
  `source` varchar(30) DEFAULT NULL,
  `ts` int(20) DEFAULT NULL,
  `private` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_upgrade_codes`
--

DROP TABLE IF EXISTS `tbl_upgrade_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_upgrade_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guid` char(36) NOT NULL,
  `action` varchar(30) NOT NULL,
  `rlimit` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_upgrade_codes_history`
--

DROP TABLE IF EXISTS `tbl_upgrade_codes_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_upgrade_codes_history` (
  `user_id` int(11) NOT NULL,
  `code_id` int(11) NOT NULL,
  KEY `tbl_upgrade_codes_history_ibfk_1` (`user_id`),
  KEY `tbl_upgrade_codes_history_ibfk_2` (`code_id`),
  CONSTRAINT `tbl_upgrade_codes_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`),
  CONSTRAINT `tbl_upgrade_codes_history_ibfk_2` FOREIGN KEY (`code_id`) REFERENCES `tbl_upgrade_codes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_uploads`
--

DROP TABLE IF EXISTS `tbl_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_uploads` (
  `name` text,
  `md5` varchar(32) DEFAULT NULL,
  `source` varchar(30) DEFAULT NULL,
  `ts` int(20) DEFAULT NULL,
  KEY `idx_tbl_uploads_ts` (`ts`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_url_download_tasks`
--

DROP TABLE IF EXISTS `tbl_url_download_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_url_download_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guid` char(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `url` varchar(610) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recursive` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `finished_at` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  PRIMARY KEY (`id`),
  KEY `tbl_url_download_task_ibfk_1` (`user_id`),
  CONSTRAINT `tbl_url_download_task_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5369 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_users`
--

DROP TABLE IF EXISTS `tbl_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(610) COLLATE ascii_bin NOT NULL,
  `email` varchar(255) COLLATE ascii_bin NOT NULL,
  `query_limit` int(5) DEFAULT '2000',
  `query_base` int(5) DEFAULT '2000',
  `last_query` int(15) DEFAULT NULL,
  `login_count` mediumint(19) DEFAULT '0',
  `api_key` varchar(255) COLLATE ascii_bin NOT NULL,
  `approved` tinyint(4) DEFAULT '0',
  `recursive_url_download_allowed` tinyint(1) DEFAULT NULL,
  `r_ip_address` varchar(15) COLLATE ascii_bin NOT NULL,
  `active` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`,`email`),
  UNIQUE KEY `api_key` (`api_key`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=20005 DEFAULT CHARSET=ascii COLLATE=ascii_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-11-01 13:29:24
