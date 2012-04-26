-- MySQL dump 10.13  Distrib 5.5.16, for osx10.6 (i386)
--
-- Host: localhost    Database: itc
-- ------------------------------------------------------
-- Server version	5.5.16

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
-- Current Database: `itc`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `itc` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `itc`;

--
-- Table structure for table `itc_apps`
--

DROP TABLE IF EXISTS `itc_apps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `itc_apps` (
  `Title` varchar(255) NOT NULL DEFAULT '',
  `SKU` varchar(255) NOT NULL DEFAULT '',
  `BundleId` varchar(255) NOT NULL DEFAULT '',
  `AppleIdentifier` varchar(255) NOT NULL,
  `AppType` varchar(64) NOT NULL DEFAULT '',
  `DefaultLanguage` varchar(64) NOT NULL DEFAULT '',
  `AppstoreLink` varchar(255) NOT NULL DEFAULT '',
  `cver_version` varchar(16) DEFAULT NULL,
  `cver_status` varchar(32) DEFAULT NULL,
  `cver_status_color` varchar(16) DEFAULT NULL,
  `cver_date_created` int(11) DEFAULT NULL,
  `cver_date_released` int(11) DEFAULT NULL,
  `nver_version` varchar(16) DEFAULT NULL,
  `nver_status` varchar(32) DEFAULT NULL,
  `nver_status_color` varchar(16) DEFAULT NULL,
  `nver_date_created` int(11) DEFAULT NULL,
  `nver_date_released` int(11) DEFAULT NULL,
  `creation_date` int(11) NOT NULL DEFAULT '0',
  `update_date` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`AppleIdentifier`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `itc_sales`
--

DROP TABLE IF EXISTS `itc_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `itc_sales` (
  `Provider` varchar(255) NOT NULL DEFAULT '',
  `ProviderCountry` varchar(255) NOT NULL DEFAULT '',
  `SKU` varchar(255) NOT NULL DEFAULT '',
  `Developer` varchar(255) NOT NULL DEFAULT '',
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Version` varchar(255) NOT NULL DEFAULT '',
  `ProductTypeIdentifier` varchar(255) NOT NULL DEFAULT '',
  `Units` int(11) NOT NULL DEFAULT '0',
  `DeveloperProceeds` float NOT NULL DEFAULT '0',
  `BeginDate` int(11) NOT NULL DEFAULT '0',
  `EndDate` int(11) NOT NULL DEFAULT '0',
  `CustomerCurrency` varchar(255) NOT NULL DEFAULT '',
  `CountryCode` varchar(255) NOT NULL DEFAULT '',
  `CurrencyOfProceeds` varchar(255) NOT NULL DEFAULT '',
  `AppleIdentifier` varchar(255) NOT NULL DEFAULT '',
  `CustomerPrice` float NOT NULL DEFAULT '0',
  `PromoCode` varchar(255) DEFAULT NULL,
  `ParentIdentifier` varchar(255) DEFAULT NULL,
  `Subscription` varchar(255) DEFAULT NULL,
  `Period` varchar(255) DEFAULT NULL,
  `creation_date` int(11) NOT NULL DEFAULT '0',
  `update_date` int(11) NOT NULL DEFAULT '0',
  KEY `fk_AppleId` (`AppleIdentifier`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'itc'
--
/*!50003 DROP PROCEDURE IF EXISTS `sp_itc_process_app` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50020 DEFINER=`root`@`localhost`*/ /*!50003 PROCEDURE `sp_itc_process_app`(
prm_Title varchar(255),
prm_SKU varchar(255),
prm_BundleId varchar(255),
prm_AppleIdentifier varchar(16), 
prm_AppType varchar(64),
prm_DefaultLanguage varchar(64),
prm_AppstoreLink varchar(255),
prm_cver_version varchar(16),
prm_cver_status varchar(32),
prm_cver_status_color varchar(16),
prm_cver_date_created integer,
prm_cver_date_released integer,
prm_nver_version varchar(16),
prm_nver_status varchar(32),
prm_nver_status_color varchar(16),
prm_nver_date_created integer,
prm_nver_date_released integer
)
BEGIN
if not exists (select null from itc_apps where Title=prm_Title and BundleId = prm_BundleId) then
insert into itc_apps (
Title,
SKU,
BundleId,
AppleIdentifier, 
AppType,
DefaultLanguage,
AppstoreLink,
cver_version,
cver_status,
cver_status_color,
cver_date_created,
cver_date_released,
nver_version,
nver_status,
nver_status_color,
nver_date_created,
nver_date_released,
creation_date,
update_date
) values (
prm_Title,
prm_SKU,
prm_BundleId,
prm_AppleIdentifier, 
prm_AppType,
prm_DefaultLanguage,
prm_AppstoreLink,
prm_cver_version,
prm_cver_status,
prm_cver_status_color,
prm_cver_date_created,
prm_cver_date_released,
prm_nver_version,
prm_nver_status,
prm_nver_status_color,
prm_nver_date_created,
prm_nver_date_released,
unix_timestamp(),
unix_timestamp()
);
else
if not exists (select null from itc_apps where
Title= prm_Title and
AppleIdentifier= prm_AppleIdentifier and
AppType= prm_AppType and
DefaultLanguage= prm_DefaultLanguage and
AppstoreLink= prm_AppstoreLink and
cver_version= prm_cver_version and
cver_status= prm_cver_status and
cver_status_color= prm_cver_status_color and
cver_date_created= prm_cver_date_created and
cver_date_released= prm_cver_date_released and
ifnull(nver_version,'n/a')= ifnull(prm_nver_version,'n/a') and
ifnull(nver_status,'n/a')= ifnull(prm_nver_status, 'n/a') and
ifnull(nver_status_color,'n/a')= ifnull(prm_nver_status_color, 'n/a') and
ifnull(nver_date_created,'n/a')= ifnull(prm_nver_date_created, 'n/a') and
ifnull(nver_date_released,'n/a')= ifnull(prm_nver_date_released, 'n/a') and
BundleId = prm_BundleId and 
Title=prm_Title
) then
update 
itc_apps
set
Title= prm_Title,
AppType= prm_AppType,
DefaultLanguage= prm_DefaultLanguage,
AppstoreLink= prm_AppstoreLink,
cver_version= prm_cver_version,
cver_status= prm_cver_status,
cver_status_color= prm_cver_status_color,
cver_date_created= prm_cver_date_created,
cver_date_released= prm_cver_date_released,
nver_version= prm_nver_version,
nver_status= prm_nver_status,
nver_status_color= prm_nver_status_color,
nver_date_created= prm_nver_date_created,
nver_date_released= prm_nver_date_released,
update_date = unix_timestamp()
where
AppleIdentifier= prm_AppleIdentifier;
end if;
end if;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_itc_process_report` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50020 DEFINER=`root`@`localhost`*/ /*!50003 PROCEDURE `sp_itc_process_report`(
  prm_Provider varchar(255),
  prm_ProviderCountry varchar(255),
  prm_SKU varchar(255),
  prm_Developer varchar(255),
  prm_Title varchar(255),
  prm_Version varchar(255),
  prm_ProductTypeIdentifier varchar(255),
  prm_Units int(11),
  prm_DeveloperProceeds float,
  prm_BeginDate integer,
  prm_EndDate integer,
  prm_CustomerCurrency varchar(255),
  prm_CountryCode varchar(255),
  prm_CurrencyOfProceeds varchar(255),
  prm_AppleIdentifier varchar(255),
  prm_CustomerPrice float,
  prm_PromoCode varchar(255),
  prm_ParentIdentifier varchar(255),
  prm_Subscription varchar(255),
  prm_Period varchar(255)
)
BEGIN
if not exists 
(select null from itc_sales 
where
  Provider = prm_Provider and
  ProviderCountry = prm_ProviderCountry and
  SKU = prm_SKU and
  Developer = prm_Developer and
  Title = prm_Title and
  Version = prm_Version and
  ProductTypeIdentifier = prm_ProductTypeIdentifier and
  Units = prm_Units and
  DeveloperProceeds = prm_DeveloperProceeds and
  BeginDate = prm_BeginDate and
  EndDate = prm_EndDate and
  CustomerCurrency = prm_CustomerCurrency and
  CountryCode = prm_CountryCode and
  CurrencyOfProceeds = prm_CurrencyOfProceeds and
  AppleIdentifier = prm_AppleIdentifier and
  CustomerPrice = prm_CustomerPrice
) then
insert into itc_sales (
  Provider,
  ProviderCountry,
  SKU,
  Developer,
  Title,
  Version,
  ProductTypeIdentifier,
  Units,
  DeveloperProceeds,
  BeginDate,
  EndDate,
  CustomerCurrency,
  CountryCode,
  CurrencyOfProceeds,
  AppleIdentifier,
  CustomerPrice,
  PromoCode,
  ParentIdentifier,
  Subscription,
  Period,
  creation_date,
  update_date
) values (
  prm_Provider,
  prm_ProviderCountry,
  prm_SKU,
  prm_Developer,
  prm_Title,
  prm_Version,
  prm_ProductTypeIdentifier,
  prm_Units,
  prm_DeveloperProceeds,
  prm_BeginDate,
  prm_EndDate,
  prm_CustomerCurrency,
  prm_CountryCode,
  prm_CurrencyOfProceeds,
  prm_AppleIdentifier,
  prm_CustomerPrice,
  prm_PromoCode,
  prm_ParentIdentifier,
  prm_Subscription,
  prm_Period,
  unix_timestamp(),
  unix_timestamp()
);
end if;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-04-26 12:41:10
