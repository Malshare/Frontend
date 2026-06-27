-- MySQL dump 10.13  Distrib 8.0.42, for Linux (x86_64)
--
-- Host: 34.44.192.195    Database: malshare_db
-- ------------------------------------------------------
-- Server version 8.0.31-google

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '25a82e9d-cadd-11ef-bd15-42010a400002:1-78251247';

--
-- Table structure for table `tbl_public_searches`
--

DROP TABLE IF EXISTS `tbl_public_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_public_searches` (
  `query` text,
  `ts` int DEFAULT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `tbl_public_searches_ts_idx` (`ts`)
) ENGINE=InnoDB AUTO_INCREMENT=8906798 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_sample_partners`
--

DROP TABLE IF EXISTS `tbl_sample_partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sample_partners` (
  `id` int NOT NULL AUTO_INCREMENT,
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sample_sources` (
  `id` int unsigned NOT NULL,
  `source` text,
  `added` int DEFAULT NULL,
  `sample_partner_submission` int DEFAULT NULL,
  `baseid` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`baseid`),
  KEY `id` (`id`),
  KEY `sample_source` (`source`(128)),
  KEY `idx_tbl_sample_sourced_added` (`added`),
  KEY `sample_partner_submission` (`sample_partner_submission`),
  FULLTEXT KEY `ft_source` (`source`),
  CONSTRAINT `tbl_sample_sources_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbl_samples` (`id`),
  CONSTRAINT `tbl_sample_sources_ibfk_2` FOREIGN KEY (`sample_partner_submission`) REFERENCES `tbl_sample_partners` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9364364 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_samples`
--

DROP TABLE IF EXISTS `tbl_samples`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_samples` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `md5` varchar(32) NOT NULL,
  `sha1` varchar(42) NOT NULL,
  `sha256` varchar(66) NOT NULL,
  `ssdeep` text,
  `added` int NOT NULL,
  `ftype` varchar(32) NOT NULL,
  `counter` int NOT NULL,
  `path` text,
  `processed` tinyint NOT NULL DEFAULT '0',
  `yara` json DEFAULT NULL,
  `pending` tinyint DEFAULT NULL,
  `subType` int DEFAULT NULL,
  `filenames` text,
  `parent_id` int DEFAULT NULL,
  `size` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `added` (`added`),
  KEY `sample_id` (`md5`,`sha1`,`sha256`),
  KEY `idx_tbl_samples_pending` (`pending`),
  KEY `idx_tbl_samples_parent_id` (`parent_id`) USING BTREE,
  KEY `idx_tbl_samples_ftype` (`ftype`),
  KEY `idx_tbl_samples_added` (`added`),
  KEY `tbl_samples_sha256_IDX` (`sha256`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11625888 DEFAULT CHARSET=ascii;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_stats_cache`
--

DROP TABLE IF EXISTS `tbl_stats_cache`;
CREATE TABLE `tbl_stats_cache` (
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `updated_at` int NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `tbl_searches`
--

DROP TABLE IF EXISTS `tbl_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_searches` (
  `query` text,
  `source` varchar(45) DEFAULT NULL,
  `ts` int DEFAULT NULL,
  `private` int DEFAULT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `searches_ts_idx` (`ts`)
) ENGINE=InnoDB AUTO_INCREMENT=28405344 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `tbl_users`
--

DROP TABLE IF EXISTS `tbl_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(610) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `email` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `query_limit` int DEFAULT '2000',
  `query_base` int DEFAULT '2000',
  `last_query` int DEFAULT NULL,
  `login_count` mediumint DEFAULT '0',
  `last_login` int DEFAULT NULL,
  `last_login_ip_address` varchar(45) DEFAULT NULL,
  `api_key` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `approved` tinyint DEFAULT '0',
  `recursive_url_download_allowed` tinyint(1) DEFAULT NULL,
  `r_ip_address` varchar(45) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `active` tinyint DEFAULT '0',
  `is_admin` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`email`),
  UNIQUE KEY `api_key` (`api_key`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=46920 DEFAULT CHARSET=ascii COLLATE=ascii_bin;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `tbl_upgrade_codes`
--

DROP TABLE IF EXISTS `tbl_upgrade_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_upgrade_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `guid` char(36) NOT NULL,
  `action` varchar(30) NOT NULL,
  `rlimit` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `tbl_uploads`
--

DROP TABLE IF EXISTS `tbl_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_uploads` (
  `name` text,
  `md5` varchar(32) DEFAULT NULL,
  `source` varchar(45) DEFAULT NULL,
  `ts` int DEFAULT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `idx_tbl_uploads_ts` (`ts`),
  KEY `idx_tbl_uploads_md5` (`md5`)
) ENGINE=InnoDB AUTO_INCREMENT=10717665 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `tbl_api_calls`
--

DROP TABLE IF EXISTS `tbl_api_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_api_calls` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `endpoint` varchar(30) NOT NULL,
  `ts` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_api_calls_ts` (`ts`),
  KEY `idx_api_calls_endpoint` (`endpoint`),
  KEY `idx_api_calls_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_api_calls_daily`
--

DROP TABLE IF EXISTS `tbl_api_calls_daily`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_api_calls_daily` (
  `id` int NOT NULL AUTO_INCREMENT,
  `day` date NOT NULL,
  `endpoint` varchar(30) NOT NULL,
  `user_id` int DEFAULT NULL,
  `call_count` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_day_endpoint_user` (`day`,`endpoint`,`user_id`),
  KEY `idx_api_calls_daily_day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;
/*!40101 SET character_set_client = @saved_cs_client */;

SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-15 21:18:40




INSERT INTO `tbl_users`(`name`, `email`, `api_key`, `approved`, `active`, `r_ip_address`) VALUES ('testuser', 'testuser@localhost.local', 'f2ca1bb6c7e907d06dafe4687e579fce76b37e4e93b7605022da52e6ccc26fd2', 1, 1, '127.0.0.1');

INSERT INTO `tbl_samples` VALUES (1913082,'0003c73c6646edac8dfe60393133311e','a6edf552b013a16bb69ef72619388af0ac216946','528bcddbb1631636f990078d4c467aa46f392628e68b639f6402e34307d3f827',NULL,1387302741,'',119,NULL,1,NULL,0,NULL,'',NULL);
INSERT INTO `tbl_samples` VALUES (3775773,'86927f4d92665747679ab72a9be87b05','35549e85c4cb875e1710afaf274aeead50e06752','33b62b95281bb0ecbad2523bb99e4853fd516044b8f2b42ef4a1e29903e7bd0f','12288:5ytq8213MaAOYetrMrM0uPDzcjmA13QwncT0GT7t5uHcSpF:5yE8MMatYerMNu78h13QIcTJvt2V',1507255280,'PE32',90,NULL,0,'{\"yara\": []}',0,NULL,'',NULL);
INSERT INTO `tbl_samples` VALUES (3776160,'2fe60ffe6d85565003a3e2186b1cda34','f18ae70f16889d0c016fdd8c6f767241e2ee2232','47865e5992d488a6a7d14f5fb2b57cc14a2fd5f06f8c9bb5adeeb3e458c37d34','98304:Bj+Jh3429G+3rJW6rGlVwUQul36d28TaVc6Y4zhBzdVCpT9:0JhN9d86rGQUQ+s28Kq',1507689997,'PE32',49,NULL,0,'{\"yara\": []}',0,NULL,'',NULL);







LOCK TABLES `tbl_sample_sources` WRITE;
/*!40000 ALTER TABLE `tbl_sample_sources` DISABLE KEYS */;
INSERT INTO `tbl_sample_sources` VALUES (3775773,'http://www.gtCartographic.co.uk/9hgfdfyr6',1507255280,NULL,1369555),(3775773,'http://ilibarcelos.pt/9hgfdfyr6',1507255302,NULL,1369557),(3775773,'http://www.100kisses.org/9hgfdfyr6',1507331085,NULL,1369699),(3775773,'http://pnkparamount.com/9hgfdfyr6',1507331106,NULL,1369700),(3775773,'http://highpressurewelding.co.uk/9hgfdfyr6',1507331108,NULL,1369701),(3775773,'http://georginabringas.com/9hgfdfyr6',1507331108,NULL,1369702),(3775773,'http://eurecas.org/9hgfdfyr6',1507331111,NULL,1369703),(3775773,'http://emeryconsult.com/9hgfdfyr6',1507331112,NULL,1369704),(3775773,'http://ecofloraholland.nl/9hgfdfyr6',1507331116,NULL,1369705),(3775773,'http://demopowerindo.com/9hgfdfyr6',1507331119,NULL,1369706),(3775773,'http://conxibit.com/9hgfdfyr6',1507331127,NULL,1369707),(3775773,'http://pnkparamount.com/9hgfdfyr6',1507380682,NULL,1369784),(3775773,'http://highpressurewelding.co.uk/9hgfdfyr6',1507380707,NULL,1369786),(3775773,'http://georginabringas.com/9hgfdfyr6',1507380713,NULL,1369788),(3775773,'http://emeryconsult.com/9hgfdfyr6',1507380728,NULL,1369790),(3775773,'http://ecofloraholland.nl/9hgfdfyr6',1507380731,NULL,1369791),(3775773,'http://conxibit.com/9hgfdfyr6',1507380735,NULL,1369793),(3775773,'http://www.100kisses.org/9hgfdfyr6',1507423742,NULL,1369807),(3775773,'http://eurecas.org/9hgfdfyr6',1507423773,NULL,1369810),(3775773,'http://demopowerindo.com/9hgfdfyr6',1507423790,NULL,1369811),(3775773,'http://unifiedfloor.com/9hgfdfyr6',1507596400,NULL,1369945),(3775773,'http://unifiedfloor.com/9hgfdfyr6',1507639573,NULL,1370009);
/*!40000 ALTER TABLE `tbl_sample_sources` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `tbl_searches` WRITE;
/*!40000 ALTER TABLE `tbl_searches` DISABLE KEYS */;
INSERT INTO `tbl_searches`(query,source,ts,private) VALUES ('uploader','8.8.8.8',1506803442,1);
INSERT INTO `tbl_searches`(query,source,ts,private) VALUES ('test2','8.8.8.8',1506803436,1);
/*!40000 ALTER TABLE `tbl_searches` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Table structure for table `tbl_url_download_tasks`
--

DROP TABLE IF EXISTS `tbl_url_download_tasks`;
CREATE TABLE `tbl_url_download_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `guid` char(36) NOT NULL,
  `user_id` int NOT NULL,
  `url` varchar(610) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fetchall` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `finished_at` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  PRIMARY KEY (`id`),
  KEY `tbl_url_download_task_ibfk_1` (`user_id`),
  KEY `idx_started_at` (`started_at`),
  CONSTRAINT `tbl_url_download_task_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-01-03 21:12:26

