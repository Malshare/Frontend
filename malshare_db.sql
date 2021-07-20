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


INSERT INTO `tbl_users`(`name`, `email`, `api_key`, `approved`, `active`, `r_ip_address`) VALUES ('testuser', 'testuser@localhost.local', 'f2ca1bb6c7e907d06dafe4687e579fce76b37e4e93b7605022da52e6ccc26fd2', 1, 1, '127.0.0.1');
INSERT INTO `tbl_samples` VALUES (1913082,'0003c73c6646edac8dfe60393133311e','a6edf552b013a16bb69ef72619388af0ac216946','528bcddbb1631636f990078d4c467aa46f392628e68b639f6402e34307d3f827',NULL,1387302741,'',118,NULL,1,NULL,0,NULL,'',NULL),(1913083,'0000cc0b702231ba0001be3d9914b774','bc65df54ea68bed04e0df19ac50a9e219c93d95c','4627d43d1a2a1b4b22b690ec87b45114cd98c33eb8d1480bc7fddba212c08ba2',NULL,1387302741,'',2,NULL,1,NULL,0,NULL,'',NULL),(1913084,'0009294f47b918f3d3850d1df8f34f53','68a73b0216262294e6ea8ee9682001f0f08dbcfc','ccbfbd53caa5b60cc662ab0cddf0dae2dde8e44c1d4c6456e1c6c7f2f92a64f1',NULL,1387302741,'',98,NULL,1,NULL,0,NULL,'',NULL),(1913085,'000c9f3571d91bc890cb7408de55e1d6','ec71e303bf9d0d80cc454519ff6952a0bd2eefe1','3c75ab139cf246d5df93557c1cf19363c4645939e3d55ee6b6f649f08c32d58c',NULL,1387302741,'',2,NULL,1,NULL,0,NULL,'',NULL),(1913086,'0004ae61cd6b2cceb1b937ff9ca9db59','32aff82ea1e9f201ba717ff1a90ea00a1483a54c','b4c02ad72c5bd5bd04d5ab75dfb459d732b708b3387a712bfe0aa9bd842b6f3a',NULL,1387302742,'',105,NULL,1,NULL,0,NULL,'',NULL);
INSERT INTO `tbl_samples` VALUES (3775773,'86927f4d92665747679ab72a9be87b05','35549e85c4cb875e1710afaf274aeead50e06752','33b62b95281bb0ecbad2523bb99e4853fd516044b8f2b42ef4a1e29903e7bd0f','12288:5ytq8213MaAOYetrMrM0uPDzcjmA13QwncT0GT7t5uHcSpF:5yE8MMatYerMNu78h13QIcTJvt2V',1507255280,'PE32',90,NULL,0,'{\"yara\": []}',0,NULL,'',NULL),(3775775,'72161caa22e3392b904f5c5725ad10e3','abb5bfbd8a6b5c02f0292d7cb3f1d02ecf3a7b46','735e3ea1763fb6120294febdc514944d1468d548f68c6a838302c60fdf3e4b65','384:RBnaVbb2LF9tn3sQvBZ1rFKG/hK7fUr81NI8gtHwDb:RBaVn2p33s4Z1rNhZrp8+wDb',1507255333,'gzip',34,NULL,0,'{\"yara\": []}',0,NULL,'',NULL),(3775776,'af0040ce66d335579fc7ecf988819e89','b8782370026298de4b366e3d03c71aae15be0170','4a34ee2eb3d7eaa2c104a552784d585bac92ce9102a58def3f8fc040fc081a34','384:Gr4fIT2j8LALMGr+6kbAVl2auX7MmpRfnIz5bRRX/VK:I4fISj8LAAG66AKljY71LnWbX/k',1507255364,'gzip',32,NULL,0,'{\"yara\": []}',0,NULL,'',NULL),(3775777,'03c9afc3d0d83eb2872fe650a50755fa','4cc9e93e5de37fdf73a886f4965ac8f2bd8c6407','1ea974702d0f06d9742645a6d98b2f4a687af7f22cccb26c2797294c5e15803a','384:FSJZvFvp9h2SGrYOKqsHxuyaLSoDOZ7YDTU6HkH+vgUhq5y/A8ktrSo+:FGl5hFGrOxIGoHTxHkogUhIy/AjGP',1507255395,'gzip',32,NULL,0,'{\"yara\": []}',0,NULL,'',NULL),(3775778,'0cb1329d0486c77099e1c9e8d3fc735a','cccf3f6630b408e7ef4aa21e8c0628a0923177fe','21bf7d454c1f220609af381f5499288cf0608ffa9cd4334e3abab14ccf4d98fa','3072:ItdEFpebV1kQU7YLw6POwlwibcvUn44ViUr3:aLPdT44Vi63',1507255440,'HTML',32,NULL,0,'{\"yara\": []}',0,NULL,'',NULL);

INSERT INTO `tbl_samples` VALUES (3776160,'2fe60ffe6d85565003a3e2186b1cda34','f18ae70f16889d0c016fdd8c6f767241e2ee2232','47865e5992d488a6a7d14f5fb2b57cc14a2fd5f06f8c9bb5adeeb3e458c37d34','98304:Bj+Jh3429G+3rJW6rGlVwUQul36d28TaVc6Y4zhBzdVCpT9:0JhN9d86rGQUQ+s28Kq',1507689997,'PE32',49,NULL,0,'{\"yara\": [\"CuckooSandbox/embedded_macho\", \"YRP/Str_Win32_Winsock2_Library\", \"YRP/Str_Win32_Wininet_Library\", \"YRP/suspicious_packer_section\", \"YRP/UPX\", \"YRP/WMI_strings\", \"YRP/contentis_base64\", \"YRP/url\", \"YRP/domain\", \"YRP/IP\", \"YRP/UPXv20MarkusLaszloReiser\", \"YRP/UPXV200V290MarkusOberhumerLaszloMolnarJohnReiser\", \"YRP/UPX_wwwupxsourceforgenet_additional\", \"YRP/yodas_Protector_v1033_dllocx_Ashkbiz_Danehkar_h\", \"YRP/UPX_v0896_v102_v105_v124_Markus_Laszlo_overlay\", \"YRP/UPX_v0896_v102_v105_v124_Markus_Laszlo_overlay_additional\", \"YRP/UPX_wwwupxsourceforgenet\", \"YRP/IsPE32\", \"YRP/IsWindowsGUI\", \"YRP/IsPacked\", \"YRP/HasOverlay\", \"YRP/HasDigitalSignature\", \"YRP/HasRichSignature\", \"YRP/antisb_threatExpert\", \"YRP/screenshot\", \"YRP/CRC32_poly_Constant\", \"YRP/CRC32b_poly_Constant\", \"YRP/SHA1_Constants\", \"YRP/SHA512_Constants\"]}',0,NULL,'',NULL),(3779369,'b0397e1d413cdd0eafd0310d53655e72','3619412b2bc85b837417b4b0c4b35be6a7d6f647','a6fff3725874c935f9abb5fc7e494ed86e4022564ac67650d8b1f1b464e59297','3072:1nZ+ItuIMaxTGdTCnbkaI1wVa/vjZKe/gjTVo3rRLGdyZVwFa5YrV6OEgYEZ42:1x8roKduWCqZR/2S3rRSwVycyZZL',1509194741,'PE32',99,NULL,0,'{\"yara\": [\"YRP/maldoc_find_kernel32_base_method_1\", \"YRP/contentis_base64\", \"YRP/url\", \"YRP/domain\", \"YRP/VC8_Microsoft_Corporation\", \"YRP/Microsoft_Visual_Cpp_8\", \"YRP/IsPE32\", \"YRP/IsConsole\", \"YRP/IsPacked\", \"YRP/HasOverlay\", \"YRP/HasDigitalSignature\", \"YRP/HasDebugData\", \"YRP/HasRichSignature\", \"YRP/anti_dbg\", \"YRP/win_mutex\", \"YRP/win_files_operation\"]}',0,NULL,'',NULL),(3779437,'a8d493819d1298b641ccba52047b32cb','8ae8bbf7cd04f2989f79f398701b099a2cddd609','c6d2964ce303f791a56452b518a9cccc12e00ba0d2f816729a9e93d6bd363cae','6144:J0Tw82u8stbEpyP6bx2pOlz8Z4rGw+sugn5GXkyGBQyLsY7C5MS1DFfG:Sk8v8s95Lp28Z4rGWjQywKm+',1509410717,'PE32',113,NULL,0,'{\"yara\": [\"YRP/Microsoft_Visual_Basic_v50v60\", \"YRP/Microsoft_Visual_Basic_v50\", \"YRP/Microsoft_Visual_Basic_v50_v60\", \"YRP/Microsoft_Visual_Basic_v50_additional\", \"YRP/Microsoft_Visual_Basic_v50v60_additional\", \"YRP/IsPE32\", \"YRP/IsWindowsGUI\", \"YRP/HasOverlay\", \"YRP/HasDigitalSignature\", \"YRP/HasRichSignature\", \"YRP/domain\", \"YRP/url\", \"YRP/contentis_base64\", \"YRP/SEH__vba\"]}',0,NULL,'',NULL),(3779707,'3950bc8a4b5ae8a0cdf6258884c5a535','b94ac9ab1aab595badf083d36a0d0b4d520be601','42202cd03a683b14280b1153c4fa4e70488f1a1112865e777504f466719e582c','12288:HN7YC9UcSK1QlsmnakSGewyEDJZGjTaVInycgMxQFWvfK87ZgUvBOuGsRLuH:HNR9GRET5wFMn3BxQFef59gUJOZ4u',1510056249,'PE32',90,NULL,0,'{\"yara\": [\"YRP/Armadillo_v171\", \"YRP/Microsoft_Visual_Cpp_v60\", \"YRP/Microsoft_Visual_Cpp_v50v60_MFC_additional\", \"YRP/Microsoft_Visual_Cpp_50\", \"YRP/Microsoft_Visual_Cpp_v50v60_MFC\", \"YRP/Armadillo_v171_additional\", \"YRP/Armadillo_v4x\", \"YRP/Microsoft_Visual_Cpp\", \"YRP/IsPE32\", \"YRP/IsWindowsGUI\", \"YRP/IsPacked\", \"YRP/HasOverlay\", \"YRP/HasDigitalSignature\", \"YRP/HasDebugData\", \"YRP/domain\", \"YRP/url\", \"YRP/contentis_base64\", \"YRP/VMWare_Detection\", \"YRP/Dropper_Strings\", \"YRP/win_files_operation\", \"YRP/VC8_Random\", \"YRP/Str_Win32_Wininet_Library\", \"YRP/Str_Win32_Internet_API\", \"YRP/Str_Win32_Http_API\"]}',0,NULL,'',NULL),(3779724,'bfd09bafe0a174c379519da857e7a213','6363d020ef112dea8e9c00d739faff917e459ba0','b7dee12df4e82bbf11c73443f4371977caa38ada969256ac0f7973f2bf9e51ee','3072:w8FUjjgB0PvbPMM1lQTsvHdGk7uOwdkGf75Aoo/Ba51hAtB5wMH3pZB:w8F29P7F1lJv9NKO/P/8Qqs',1510062587,'PE32',111,NULL,0,'{\"yara\": [\"YRP/Microsoft_Visual_Basic_v50v60\", \"YRP/Microsoft_Visual_Basic_v50\", \"YRP/Microsoft_Visual_Basic_v50_v60\", \"YRP/Microsoft_Visual_Basic_v50_additional\", \"YRP/Microsoft_Visual_Basic_v50v60_additional\", \"YRP/IsPE32\", \"YRP/IsWindowsGUI\", \"YRP/HasOverlay\", \"YRP/HasDigitalSignature\", \"YRP/HasRichSignature\", \"YRP/domain\", \"YRP/url\", \"YRP/contentis_base64\", \"YRP/SEH__vba\", \"YRP/SharedStrings\", \"YRP/suspicious_packer_section\"]}',0,NULL,'',NULL),(3779839,'6ed74c713f74e2c5216e23a0c08b8c40','640b3904045d979aeeb95bf10cd7ab93af992ed3','d15b7999824461a1cdfec8d2e469225d8dfdf58c1f347934d6bf27213b6abb24','6144:IK2npD/+5gEk/46ooLnj8t3LAQOJYgJ9teLW08:IlpH7oujGrOV/t1',1510274734,'PE32',90,NULL,0,'{\"yara\": [\"YRP/IsPE32\", \"YRP/IsNET_EXE\", \"YRP/IsWindowsGUI\", \"YRP/IsPacked\", \"YRP/HasOverlay\", \"YRP/HasDigitalSignature\", \"YRP/domain\", \"YRP/IP\", \"YRP/url\", \"YRP/contentis_base64\"]}',0,NULL,'',NULL),(3779848,'3dfdb168828b49f667abc4a22bde55db','cdb70af7425c1ba99173be748c25d17af84287d4','db8aecd0e1653c6c295e0a340774d170697fdce5b47325dea53572895b99c4ec','3072:okNpsEaIBusAR1hiowau74iILmkvAEPcPPbRvtJdkczwh12zMyAiVYa2UnNHw6wa:TNmsA/h5aILmo+PbRlJpzx5VYPC/',1510275426,'PE32',92,NULL,0,'{\"yara\": [\"YRP/Microsoft_Visual_Studio_NET\", \"YRP/Microsoft_Visual_C_v70_Basic_NET_additional\", \"YRP/Microsoft_Visual_C_Basic_NET\", \"YRP/Microsoft_Visual_Studio_NET_additional\", \"YRP/Microsoft_Visual_C_v70_Basic_NET\", \"YRP/NET_executable_\", \"YRP/NET_executable\", \"YRP/NETexecutableMicrosoft\", \"YRP/IsPE32\", \"YRP/IsNET_EXE\", \"YRP/IsWindowsGUI\", \"YRP/HasOverlay\", \"YRP/HasDigitalSignature\", \"YRP/domain\", \"YRP/IP\", \"YRP/url\", \"YRP/contentis_base64\"]}',0,NULL,'',NULL),(3779914,'7da78cb88534cadbc804c2fc80521937','f578b251d5d122e0eba4e67fc78f68a6edca7372','172da2f7b0664a96842c9c9b546c3a84f577895fd8315d59194e67a10837b08c','6144:dRipAMqRuS6ifIKz4XI1KfmMFmfOQNrWld3htjZ:dQpmkzCIKz4q8pm2jZ',1510371820,'PE32',99,NULL,0,'{\"yara\": [\"YRP/Microsoft_Visual_Studio_NET\", \"YRP/Microsoft_Visual_C_v70_Basic_NET_additional\", \"YRP/Microsoft_Visual_C_Basic_NET\", \"YRP/Microsoft_Visual_Studio_NET_additional\", \"YRP/Microsoft_Visual_C_v70_Basic_NET\", \"YRP/NET_executable_\", \"YRP/NET_executable\", \"YRP/NETexecutableMicrosoft\", \"YRP/IsPE32\", \"YRP/IsNET_EXE\", \"YRP/IsWindowsGUI\", \"YRP/HasOverlay\", \"YRP/HasDigitalSignature\", \"YRP/domain\", \"YRP/IP\", \"YRP/url\", \"YRP/contentis_base64\"]}',0,NULL,'',NULL),(3780007,'8b5d9e70a3002241257dd32b8dddb185','a4197f88f82236ab885108ff81be79e80f6c790f','76a556858762323948f2f327262cad46d28cc587339757ba20215013c28c2377','6144:9ZMG/TBzRLU/OLN//zjTjQax6BpmyRkGmDd28gFQ3z41Z3xEgUqxbDmQnbuG9pVt:9ZMG78IMb7J3/mrzyqW',1510621060,'PE32',98,NULL,0,'{\"yara\": [\"YRP/Microsoft_Visual_Studio_NET\", \"YRP/Microsoft_Visual_C_v70_Basic_NET_additional\", \"YRP/Microsoft_Visual_C_Basic_NET\", \"YRP/Microsoft_Visual_Studio_NET_additional\", \"YRP/Microsoft_Visual_C_v70_Basic_NET\", \"YRP/NET_executable_\", \"YRP/NET_executable\", \"YRP/NETexecutableMicrosoft\", \"YRP/IsPE32\", \"YRP/IsNET_EXE\", \"YRP/IsWindowsGUI\", \"YRP/HasOverlay\", \"YRP/HasDigitalSignature\", \"YRP/domain\", \"YRP/IP\", \"YRP/url\", \"YRP/contentis_base64\"]}',0,NULL,'',NULL),(3780051,'b6d0ad1ce9e95db1a2d6f7ff65854808','dd5f8edda03992a40e3c1ed849cee9e979c40ac9','1b6cc688334377c3e35dcda79f530f693a823a7c6fcf10a70eb44fa6e79f11d3','3072:YEuwq1nBnW7hLAKZ7kUIor9zbsZ5GTNrYgJrccCq767nleNsaqe+gbQ7h:NAnBnW7Oc7V9z4vGTNkSrTCFnv',1510708052,'PE32',110,NULL,0,'{\"yara\": [\"YRP/Microsoft_Visual_Studio_NET\", \"YRP/Microsoft_Visual_C_v70_Basic_NET_additional\", \"YRP/Microsoft_Visual_C_Basic_NET\", \"YRP/Microsoft_Visual_Studio_NET_additional\", \"YRP/Microsoft_Visual_C_v70_Basic_NET\", \"YRP/NET_executable_\", \"YRP/NET_executable\", \"YRP/IsPE32\", \"YRP/IsNET_EXE\", \"YRP/IsWindowsGUI\", \"YRP/HasOverlay\", \"YRP/HasDigitalSignature\", \"YRP/domain\", \"YRP/IP\", \"YRP/url\", \"YRP/contentis_base64\"]}',0,NULL,'',NULL);
