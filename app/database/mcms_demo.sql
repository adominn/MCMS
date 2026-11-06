-- MySQL dump 10.13  Distrib 8.4.13, for Linux (x86_64)
--
-- Host: localhost    Database: mcms_demo
-- ------------------------------------------------------
-- Server version	8.4.13

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

--
-- Table structure for table `abc_batches`
--

DROP TABLE IF EXISTS `abc_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abc_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptj_id` bigint unsigned NOT NULL,
  `format_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `row_count` smallint unsigned NOT NULL,
  `prepared_by` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL,
  `acr_ids` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abc_batches_reference_unique` (`reference`),
  KEY `abc_batches_ptj_id_foreign` (`ptj_id`),
  KEY `abc_batches_prepared_by_foreign` (`prepared_by`),
  CONSTRAINT `abc_batches_prepared_by_foreign` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abc_batches_ptj_id_foreign` FOREIGN KEY (`ptj_id`) REFERENCES `ptjs` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abc_batches`
--

LOCK TABLES `abc_batches` WRITE;
/*!40000 ALTER TABLE `abc_batches` DISABLE KEYS */;
INSERT INTO `abc_batches` VALUES (1,'ABC-BATCH-2026-001',1,'ABC-JSON-v1.4',2,12,'2026-09-15 01:42:39','[1, 2]'),(2,'ABC-BATCH-2026-002',1,'ABC-JSON-v1.4',2,12,'2026-08-30 10:00:00','[3, 4]'),(3,'ABC-BATCH-2026-003',1,'ABC-JSON-v1.4',2,12,'2026-10-28 10:00:00','[6, 7]'),(4,'ABC-BATCH-2026-004',4,'ABC-JSON-v1.4',1,12,'2026-10-12 10:00:00','[8]');
/*!40000 ALTER TABLE `abc_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `abc_deposits`
--

DROP TABLE IF EXISTS `abc_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abc_deposits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acr_id` bigint unsigned NOT NULL,
  `abc_batch_id` bigint unsigned DEFAULT NULL,
  `abc_reference` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit_date` date NOT NULL,
  `credits` decimal(5,1) NOT NULL,
  `credit_expiry` date DEFAULT NULL,
  `setting_version_id` bigint unsigned DEFAULT NULL,
  `deposit_status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credit_status` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abc_deposits_acr_id_unique` (`acr_id`),
  KEY `abc_deposits_abc_batch_id_foreign` (`abc_batch_id`),
  KEY `abc_deposits_setting_version_id_foreign` (`setting_version_id`),
  KEY `abc_deposits_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `abc_deposits_abc_batch_id_foreign` FOREIGN KEY (`abc_batch_id`) REFERENCES `abc_batches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abc_deposits_acr_id_foreign` FOREIGN KEY (`acr_id`) REFERENCES `acrs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abc_deposits_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `abc_deposits_setting_version_id_foreign` FOREIGN KEY (`setting_version_id`) REFERENCES `setting_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abc_deposits`
--

LOCK TABLES `abc_deposits` WRITE;
/*!40000 ALTER TABLE `abc_deposits` DISABLE KEYS */;
INSERT INTO `abc_deposits` VALUES (1,1,1,'ABC-MYS-2026-098471','2026-09-15',2.0,'2036-11-04',8,'confirmed','redeemed',12,'2026-09-15 01:42:39','2026-11-04 01:42:39'),(2,2,1,'ABC-MYS-2026-098472','2026-09-15',2.0,'2036-11-04',8,'confirmed','provisional',12,'2026-09-15 01:42:39','2026-11-04 01:42:39'),(3,3,2,'ABC-MYS-2026-098473','2026-08-30',2.0,'2031-08-30',8,'confirmed','redeemed',12,'2026-08-30 10:00:00','2026-11-05 09:00:00'),(4,4,2,'ABC-MYS-2026-098474','2026-08-30',2.0,'2031-08-30',8,'confirmed','available',12,'2026-08-30 10:00:00','2026-11-05 09:00:00'),(5,6,3,'ABC-MYS-2026-098475','2026-10-28',2.0,'2031-10-28',8,'confirmed','available',12,'2026-10-28 10:00:00','2026-11-05 09:00:00'),(6,7,3,'ABC-MYS-2026-098476','2026-10-28',3.0,'2031-10-28',8,'confirmed','available',12,'2026-10-28 10:00:00','2026-11-05 09:00:00'),(7,8,4,'ABC-MYS-2026-098477','2026-10-12',3.0,'2031-10-12',8,'confirmed','redeemed',12,'2026-10-12 10:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `abc_deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `achievement_records`
--

DROP TABLE IF EXISTS `achievement_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enrolment_id` bigint unsigned NOT NULL,
  `mc_version_id` bigint unsigned NOT NULL,
  `completion_status` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed_on` date DEFAULT NULL,
  `final_result` json DEFAULT NULL,
  `confirmed_by` bigint unsigned DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `achievement_records_enrolment_id_unique` (`enrolment_id`),
  KEY `achievement_records_mc_version_id_foreign` (`mc_version_id`),
  KEY `achievement_records_confirmed_by_foreign` (`confirmed_by`),
  CONSTRAINT `achievement_records_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `achievement_records_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `achievement_records_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `achievement_records`
--

LOCK TABLES `achievement_records` WRITE;
/*!40000 ALTER TABLE `achievement_records` DISABLE KEYS */;
INSERT INTO `achievement_records` VALUES (1,1,2,'complete','2026-09-05','{\"components\": [{\"max\": 100.0, \"name\": \"Docker Containerization Practical Lab\", \"score\": 88.0, \"weight\": 50.0}, {\"max\": 100.0, \"name\": \"Kubernetes Production Cluster Project\", \"score\": 92.0, \"weight\": 50.0}], \"weighted_percent\": 90.0}',10,'2026-09-10 01:42:39','2026-09-10 01:42:39'),(2,2,2,'complete','2026-09-05','{\"components\": [{\"max\": 100.0, \"name\": \"Docker Containerization Practical Lab\", \"score\": 76.0, \"weight\": 50.0}, {\"max\": 100.0, \"name\": \"Kubernetes Production Cluster Project\", \"score\": 82.0, \"weight\": 50.0}], \"weighted_percent\": 79.0}',10,'2026-09-10 01:42:39','2026-09-10 01:42:39'),(3,9,1,'complete','2026-08-20','{\"components\": [{\"max\": 100, \"name\": \"Module 1 Test: Linear Data Structures\", \"score\": 86, \"weight\": 30}, {\"max\": 100, \"name\": \"Module 2 Test: Non-Linear Structures\", \"score\": 89, \"weight\": 30}, {\"max\": 100, \"name\": \"Comprehensive Capstone Coding Project\", \"score\": 91, \"weight\": 40}], \"weighted_percent\": 88.9}',10,'2026-08-25 10:00:00','2026-08-25 10:00:00'),(4,10,1,'complete','2026-08-20','{\"components\": [{\"max\": 100, \"name\": \"Module 1 Test: Linear Data Structures\", \"score\": 80, \"weight\": 30}, {\"max\": 100, \"name\": \"Module 2 Test: Non-Linear Structures\", \"score\": 84, \"weight\": 30}, {\"max\": 100, \"name\": \"Comprehensive Capstone Coding Project\", \"score\": 85, \"weight\": 40}], \"weighted_percent\": 83.2}',10,'2026-08-25 10:00:00','2026-08-25 10:00:00'),(5,11,1,'complete','2026-08-20','{\"components\": [{\"max\": 100, \"name\": \"Module 1 Test: Linear Data Structures\", \"score\": 78, \"weight\": 30}, {\"max\": 100, \"name\": \"Module 2 Test: Non-Linear Structures\", \"score\": 75, \"weight\": 30}, {\"max\": 100, \"name\": \"Comprehensive Capstone Coding Project\", \"score\": 82, \"weight\": 40}], \"weighted_percent\": 78.7}',10,'2026-08-25 10:00:00','2026-08-25 10:00:00'),(6,12,1,'complete','2026-08-20','{\"components\": [{\"max\": 100, \"name\": \"Module 1 Test: Linear Data Structures\", \"score\": 92, \"weight\": 30}, {\"max\": 100, \"name\": \"Module 2 Test: Non-Linear Structures\", \"score\": 88, \"weight\": 30}, {\"max\": 100, \"name\": \"Comprehensive Capstone Coding Project\", \"score\": 94, \"weight\": 40}], \"weighted_percent\": 91.6}',10,'2026-08-25 10:00:00','2026-08-25 10:00:00'),(7,13,9,'complete','2026-10-20','{\"components\": [{\"max\": 100, \"name\": \"STRIDE Threat Model & OWASP Remediation Lab\", \"score\": 91, \"weight\": 50}, {\"max\": 100, \"name\": \"Automated DevSecOps Pipeline Capstone\", \"score\": 89, \"weight\": 50}], \"weighted_percent\": 90}',10,'2026-10-25 10:00:00','2026-10-25 10:00:00'),(8,14,9,'complete','2026-10-20','{\"components\": [{\"max\": 100, \"name\": \"STRIDE Threat Model & OWASP Remediation Lab\", \"score\": 85, \"weight\": 50}, {\"max\": 100, \"name\": \"Automated DevSecOps Pipeline Capstone\", \"score\": 87, \"weight\": 50}], \"weighted_percent\": 86}',10,'2026-10-25 10:00:00','2026-10-25 10:00:00'),(9,15,9,'complete','2026-10-20','{\"components\": [{\"max\": 100, \"name\": \"STRIDE Threat Model & OWASP Remediation Lab\", \"score\": 79, \"weight\": 50}, {\"max\": 100, \"name\": \"Automated DevSecOps Pipeline Capstone\", \"score\": 81, \"weight\": 50}], \"weighted_percent\": 80}',10,'2026-10-25 10:00:00','2026-10-25 10:00:00'),(10,16,9,'incomplete',NULL,'{\"components\": [{\"max\": 100, \"name\": \"STRIDE Threat Model & OWASP Remediation Lab\", \"score\": 58, \"weight\": 50}, {\"max\": 100, \"name\": \"Automated DevSecOps Pipeline Capstone\", \"score\": 34, \"weight\": 50}], \"weighted_percent\": 46}',10,'2026-10-25 10:00:00','2026-10-25 10:00:00'),(11,17,18,'complete','2026-09-30','{\"components\": [{\"max\": 100, \"name\": \"OOP Class Hierarchy & Polymorphism Practical\", \"score\": 88, \"weight\": 50}, {\"max\": 100, \"name\": \"SOLID Refactoring & JUnit Test Suite Project\", \"score\": 90, \"weight\": 50}], \"weighted_percent\": 89}',10,'2026-10-05 10:00:00','2026-10-05 10:00:00'),(12,18,18,'complete','2026-09-30','{\"components\": [{\"max\": 100, \"name\": \"OOP Class Hierarchy & Polymorphism Practical\", \"score\": 81, \"weight\": 50}, {\"max\": 100, \"name\": \"SOLID Refactoring & JUnit Test Suite Project\", \"score\": 83, \"weight\": 50}], \"weighted_percent\": 82}',10,'2026-10-05 10:00:00','2026-10-05 10:00:00'),(13,19,21,'complete','2026-10-01','{\"components\": [{\"max\": 100, \"name\": \"Lean Canvas Dossier\", \"score\": 86, \"weight\": 50}, {\"max\": 100, \"name\": \"Venture Pitch Defence\", \"score\": 88, \"weight\": 50}], \"weighted_percent\": 87}',25,'2026-10-08 10:00:00','2026-10-08 10:00:00'),(14,20,24,'complete','2026-10-25','{\"components\": [{\"max\": 100, \"name\": \"Enterprise RAG & Transformer Fine-Tuning Project\", \"score\": 93, \"weight\": 100}], \"weighted_percent\": 93}',25,'2026-10-30 10:00:00','2026-10-30 10:00:00'),(69,3,1,NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:56:14');
/*!40000 ALTER TABLE `achievement_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acr_checks`
--

DROP TABLE IF EXISTS `acr_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acr_checks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acr_id` bigint unsigned DEFAULT NULL,
  `enrolment_id` bigint unsigned NOT NULL,
  `check_key` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `passed` tinyint(1) NOT NULL,
  `detail` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `checked_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `acr_checks_acr_id_foreign` (`acr_id`),
  KEY `acr_checks_enrolment_id_checked_at_index` (`enrolment_id`,`checked_at`),
  CONSTRAINT `acr_checks_acr_id_foreign` FOREIGN KEY (`acr_id`) REFERENCES `acrs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acr_checks_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acr_checks`
--

LOCK TABLES `acr_checks` WRITE;
/*!40000 ALTER TABLE `acr_checks` DISABLE KEYS */;
INSERT INTO `acr_checks` VALUES (1,1,1,'category',1,'Category confirmed as credit-bearing (UMC/SAMC).','2026-10-26 10:00:00'),(2,1,1,'completed',1,'Learner completed the micro-credential with confirmed achievement record.','2026-10-26 10:00:00'),(3,1,1,'identity_results',1,'Identity and summative assessment results verified without open integrity holds.','2026-10-26 10:00:00'),(4,1,1,'accredited_parent',1,'Parent programme/course accreditation requirement satisfied.','2026-10-26 10:00:00'),(5,1,1,'qv_valid',1,'Active MQA Quality Verification (QV) cycle verified on completion date.','2026-10-26 10:00:00'),(6,1,1,'credit_basis',1,'Planned SLT and MQF level match approved credit calculation.','2026-10-26 10:00:00'),(7,1,1,'traceable',1,'Linked to valid verifiable credential and faculty approval decision.','2026-10-26 10:00:00'),(8,2,2,'category',1,'Category confirmed as credit-bearing (UMC/SAMC).','2026-10-26 10:00:00'),(9,2,2,'completed',1,'Learner completed the micro-credential with confirmed achievement record.','2026-10-26 10:00:00'),(10,2,2,'identity_results',1,'Identity and summative assessment results verified without open integrity holds.','2026-10-26 10:00:00'),(11,2,2,'accredited_parent',1,'Parent programme/course accreditation requirement satisfied.','2026-10-26 10:00:00'),(12,2,2,'qv_valid',1,'Active MQA Quality Verification (QV) cycle verified on completion date.','2026-10-26 10:00:00'),(13,2,2,'credit_basis',1,'Planned SLT and MQF level match approved credit calculation.','2026-10-26 10:00:00'),(14,2,2,'traceable',1,'Linked to valid verifiable credential and faculty approval decision.','2026-10-26 10:00:00'),(15,3,9,'category',1,'Category confirmed as credit-bearing (UMC/SAMC).','2026-10-26 10:00:00'),(16,3,9,'completed',1,'Learner completed the micro-credential with confirmed achievement record.','2026-10-26 10:00:00'),(17,3,9,'identity_results',1,'Identity and summative assessment results verified without open integrity holds.','2026-10-26 10:00:00'),(18,3,9,'accredited_parent',1,'Parent programme/course accreditation requirement satisfied.','2026-10-26 10:00:00'),(19,3,9,'qv_valid',1,'Active MQA Quality Verification (QV) cycle verified on completion date.','2026-10-26 10:00:00'),(20,3,9,'credit_basis',1,'Planned SLT and MQF level match approved credit calculation.','2026-10-26 10:00:00'),(21,3,9,'traceable',1,'Linked to valid verifiable credential and faculty approval decision.','2026-10-26 10:00:00'),(22,4,10,'category',1,'Category confirmed as credit-bearing (UMC/SAMC).','2026-10-26 10:00:00'),(23,4,10,'completed',1,'Learner completed the micro-credential with confirmed achievement record.','2026-10-26 10:00:00'),(24,4,10,'identity_results',1,'Identity and summative assessment results verified without open integrity holds.','2026-10-26 10:00:00'),(25,4,10,'accredited_parent',1,'Parent programme/course accreditation requirement satisfied.','2026-10-26 10:00:00'),(26,4,10,'qv_valid',1,'Active MQA Quality Verification (QV) cycle verified on completion date.','2026-10-26 10:00:00'),(27,4,10,'credit_basis',1,'Planned SLT and MQF level match approved credit calculation.','2026-10-26 10:00:00'),(28,4,10,'traceable',1,'Linked to valid verifiable credential and faculty approval decision.','2026-10-26 10:00:00'),(29,5,11,'category',1,'Category confirmed as credit-bearing (UMC/SAMC).','2026-10-26 10:00:00'),(30,5,11,'completed',1,'Learner completed the micro-credential with confirmed achievement record.','2026-10-26 10:00:00'),(31,5,11,'identity_results',1,'Identity and summative assessment results verified without open integrity holds.','2026-10-26 10:00:00'),(32,5,11,'accredited_parent',1,'Parent programme/course accreditation requirement satisfied.','2026-10-26 10:00:00'),(33,5,11,'qv_valid',1,'Active MQA Quality Verification (QV) cycle verified on completion date.','2026-10-26 10:00:00'),(34,5,11,'credit_basis',1,'Planned SLT and MQF level match approved credit calculation.','2026-10-26 10:00:00'),(35,5,11,'traceable',1,'Linked to valid verifiable credential and faculty approval decision.','2026-10-26 10:00:00'),(36,6,13,'category',1,'Category confirmed as credit-bearing (UMC/SAMC).','2026-10-26 10:00:00'),(37,6,13,'completed',1,'Learner completed the micro-credential with confirmed achievement record.','2026-10-26 10:00:00'),(38,6,13,'identity_results',1,'Identity and summative assessment results verified without open integrity holds.','2026-10-26 10:00:00'),(39,6,13,'accredited_parent',1,'Parent programme/course accreditation requirement satisfied.','2026-10-26 10:00:00'),(40,6,13,'qv_valid',1,'Active MQA Quality Verification (QV) cycle verified on completion date.','2026-10-26 10:00:00'),(41,6,13,'credit_basis',1,'Planned SLT and MQF level match approved credit calculation.','2026-10-26 10:00:00'),(42,6,13,'traceable',1,'Linked to valid verifiable credential and faculty approval decision.','2026-10-26 10:00:00'),(43,7,17,'category',1,'Category confirmed as credit-bearing (UMC/SAMC).','2026-10-26 10:00:00'),(44,7,17,'completed',1,'Learner completed the micro-credential with confirmed achievement record.','2026-10-26 10:00:00'),(45,7,17,'identity_results',1,'Identity and summative assessment results verified without open integrity holds.','2026-10-26 10:00:00'),(46,7,17,'accredited_parent',1,'Parent programme/course accreditation requirement satisfied.','2026-10-26 10:00:00'),(47,7,17,'qv_valid',1,'Active MQA Quality Verification (QV) cycle verified on completion date.','2026-10-26 10:00:00'),(48,7,17,'credit_basis',1,'Planned SLT and MQF level match approved credit calculation.','2026-10-26 10:00:00'),(49,7,17,'traceable',1,'Linked to valid verifiable credential and faculty approval decision.','2026-10-26 10:00:00'),(50,8,19,'category',1,'Category confirmed as credit-bearing (UMC/SAMC).','2026-10-26 10:00:00'),(51,8,19,'completed',1,'Learner completed the micro-credential with confirmed achievement record.','2026-10-26 10:00:00'),(52,8,19,'identity_results',1,'Identity and summative assessment results verified without open integrity holds.','2026-10-26 10:00:00'),(53,8,19,'accredited_parent',1,'Parent programme/course accreditation requirement satisfied.','2026-10-26 10:00:00'),(54,8,19,'qv_valid',1,'Active MQA Quality Verification (QV) cycle verified on completion date.','2026-10-26 10:00:00'),(55,8,19,'credit_basis',1,'Planned SLT and MQF level match approved credit calculation.','2026-10-26 10:00:00'),(56,8,19,'traceable',1,'Linked to valid verifiable credential and faculty approval decision.','2026-10-26 10:00:00'),(57,9,14,'category',1,'Category confirmed as credit-bearing (UMC/SAMC).','2026-10-26 10:00:00'),(58,9,14,'completed',1,'Learner completed the micro-credential with confirmed achievement record.','2026-10-26 10:00:00'),(59,9,14,'identity_results',1,'Identity and summative assessment results verified without open integrity holds.','2026-10-26 10:00:00'),(60,9,14,'accredited_parent',1,'Parent programme/course accreditation requirement satisfied.','2026-10-26 10:00:00'),(61,9,14,'qv_valid',1,'Active MQA Quality Verification (QV) cycle verified on completion date.','2026-10-26 10:00:00'),(62,9,14,'credit_basis',1,'Planned SLT and MQF level match approved credit calculation.','2026-10-26 10:00:00'),(63,9,14,'traceable',1,'Linked to valid verifiable credential and faculty approval decision.','2026-10-26 10:00:00'),(64,10,12,'category',1,'Category confirmed as credit-bearing (UMC/SAMC).','2026-10-26 10:00:00'),(65,10,12,'completed',1,'Learner completed the micro-credential with confirmed achievement record.','2026-10-26 10:00:00'),(66,10,12,'identity_results',1,'Identity and summative assessment results verified without open integrity holds.','2026-10-26 10:00:00'),(67,10,12,'accredited_parent',1,'Parent programme/course accreditation requirement satisfied.','2026-10-26 10:00:00'),(68,10,12,'qv_valid',1,'Active MQA Quality Verification (QV) cycle verified on completion date.','2026-10-26 10:00:00'),(69,10,12,'credit_basis',1,'Planned SLT and MQF level match approved credit calculation.','2026-10-26 10:00:00'),(70,10,12,'traceable',1,'Linked to valid verifiable credential and faculty approval decision.','2026-10-26 10:00:00');
/*!40000 ALTER TABLE `acr_checks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acrs`
--

DROP TABLE IF EXISTS `acrs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acrs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `achievement_record_id` bigint unsigned NOT NULL,
  `mc_version_id` bigint unsigned NOT NULL,
  `qv_record_id` bigint unsigned DEFAULT NULL,
  `credits` decimal(5,1) NOT NULL,
  `mqf_level` tinyint unsigned NOT NULL,
  `completion_date` date NOT NULL,
  `assessment_basis` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'prepared',
  `confirmed_by` bigint unsigned DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acrs_reference_unique` (`reference`),
  UNIQUE KEY `acrs_achievement_record_id_unique` (`achievement_record_id`),
  KEY `acrs_user_id_foreign` (`user_id`),
  KEY `acrs_mc_version_id_foreign` (`mc_version_id`),
  KEY `acrs_qv_record_id_foreign` (`qv_record_id`),
  KEY `acrs_confirmed_by_foreign` (`confirmed_by`),
  CONSTRAINT `acrs_achievement_record_id_foreign` FOREIGN KEY (`achievement_record_id`) REFERENCES `achievement_records` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acrs_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acrs_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acrs_qv_record_id_foreign` FOREIGN KEY (`qv_record_id`) REFERENCES `qv_records` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `acrs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acrs`
--

LOCK TABLES `acrs` WRITE;
/*!40000 ALTER TABLE `acrs` DISABLE KEYS */;
INSERT INTO `acrs` VALUES (1,'ACR-2026-0001',1,1,2,1,2.0,4,'2026-09-05','Continuous assessment with authentic capstone project (100% coursework).','{\"name\": {\"en\": \"Cloud Native Development\", \"ms\": \"Pembangunan Asal Awan\"}, \"created\": \"2026-09-05\", \"credits\": \"2.0\", \"category\": \"SAMC\", \"outcomes\": [\"Demonstrate verified micro-credential learning outcomes.\", \"Apply domain competencies in practical assessments.\"], \"provider\": \"Universiti Teknikal Malaysia Melaka\", \"mqf_level\": 4, \"reference\": \"ACR-2026-0001\", \"participant\": {\"name\": \"Amir Hakimi\", \"matric\": \"B032410001\"}, \"verification\": {\"credential\": \"UTEM-MC-2026-0001\", \"qv_reference\": \"QV/2023/051\", \"parent_course\": null, \"completion_confirmed_by\": \"Hakim Rahman\"}, \"completion_date\": \"2026-09-05\"}','deposited',10,'2026-09-13 01:42:39','2026-09-11 01:42:39','2026-11-04 01:42:39'),(2,'ACR-2026-0002',2,2,2,1,2.0,4,'2026-09-05','Continuous assessment with authentic capstone project.','{\"name\": {\"en\": \"Cloud Native Development\", \"ms\": \"Pembangunan Asal Awan\"}, \"created\": \"2026-09-05\", \"credits\": \"2.0\", \"category\": \"SAMC\", \"outcomes\": [\"Demonstrate verified micro-credential learning outcomes.\", \"Apply domain competencies in practical assessments.\"], \"provider\": \"Universiti Teknikal Malaysia Melaka\", \"mqf_level\": 4, \"reference\": \"ACR-2026-0002\", \"participant\": {\"name\": \"Bee Tan\", \"matric\": \"B032410002\"}, \"verification\": {\"credential\": \"UTEM-MC-2026-0002-R1\", \"qv_reference\": \"QV/2023/051\", \"parent_course\": null, \"completion_confirmed_by\": \"Hakim Rahman\"}, \"completion_date\": \"2026-09-05\"}','deposited',10,'2026-09-13 01:42:39','2026-09-11 01:42:39','2026-11-04 01:42:39'),(3,'ACR-2026-0003',1,3,1,2,2.0,6,'2026-08-20','Verified marks across 2 module tests and capstone project.','{\"name\": {\"en\": \"Data Structures\", \"ms\": \"Struktur Data\"}, \"created\": \"2026-08-20\", \"credits\": \"2.0\", \"category\": \"UMC\", \"outcomes\": [\"Demonstrate verified micro-credential learning outcomes.\", \"Apply domain competencies in practical assessments.\"], \"provider\": \"Universiti Teknikal Malaysia Melaka\", \"mqf_level\": 6, \"reference\": \"ACR-2026-0003\", \"participant\": {\"name\": \"Amir Hakimi\", \"matric\": \"B032410001\"}, \"verification\": {\"credential\": \"UTEM-MC-2026-0003\", \"qv_reference\": \"QV/2025/112\", \"parent_course\": \"BITS 1123\", \"completion_confirmed_by\": \"Hakim Rahman\"}, \"completion_date\": \"2026-08-20\"}','deposited',10,'2026-08-27 10:00:00','2026-08-26 10:00:00','2026-11-05 09:00:00'),(4,'ACR-2026-0004',2,4,1,2,2.0,6,'2026-08-20','Verified marks across 2 module tests and capstone project.','{\"name\": {\"en\": \"Data Structures\", \"ms\": \"Struktur Data\"}, \"created\": \"2026-08-20\", \"credits\": \"2.0\", \"category\": \"UMC\", \"outcomes\": [\"Demonstrate verified micro-credential learning outcomes.\", \"Apply domain competencies in practical assessments.\"], \"provider\": \"Universiti Teknikal Malaysia Melaka\", \"mqf_level\": 6, \"reference\": \"ACR-2026-0004\", \"participant\": {\"name\": \"Bee Tan\", \"matric\": \"B032410002\"}, \"verification\": {\"credential\": \"UTEM-MC-2026-0004\", \"qv_reference\": \"QV/2025/112\", \"parent_course\": \"BITS 1123\", \"completion_confirmed_by\": \"Hakim Rahman\"}, \"completion_date\": \"2026-08-20\"}','deposited',10,'2026-08-27 10:00:00','2026-08-26 10:00:00','2026-11-05 09:00:00'),(5,'ACR-2026-0005',3,5,1,2,2.0,6,'2026-08-20','Verified marks across 2 module tests and capstone project.','{\"name\": {\"en\": \"Data Structures\", \"ms\": \"Struktur Data\"}, \"created\": \"2026-08-20\", \"credits\": \"2.0\", \"category\": \"UMC\", \"outcomes\": [\"Demonstrate verified micro-credential learning outcomes.\", \"Apply domain competencies in practical assessments.\"], \"provider\": \"Universiti Teknikal Malaysia Melaka\", \"mqf_level\": 6, \"reference\": \"ACR-2026-0005\", \"participant\": {\"name\": \"Citra Dewi\", \"matric\": \"B032410003\"}, \"verification\": {\"credential\": \"UTEM-MC-2026-0005\", \"qv_reference\": \"QV/2025/112\", \"parent_course\": \"BITS 1123\", \"completion_confirmed_by\": \"Hakim Rahman\"}, \"completion_date\": \"2026-08-20\"}','verified',10,'2026-08-28 10:00:00','2026-08-26 10:00:00','2026-11-05 09:00:00'),(6,'ACR-2026-0006',1,7,9,5,2.0,6,'2026-10-20','Verified OWASP lab and DevSecOps pipeline capstone.','{\"name\": {\"en\": \"Secure Software Development Lifecycle (SSDLC)\", \"ms\": \"Kitar Hayat Pembangunan Perisian Selamat (SSDLC)\"}, \"created\": \"2026-10-20\", \"credits\": \"2.0\", \"category\": \"SAMC\", \"outcomes\": [\"Demonstrate verified micro-credential learning outcomes.\", \"Apply domain competencies in practical assessments.\"], \"provider\": \"Universiti Teknikal Malaysia Melaka\", \"mqf_level\": 6, \"reference\": \"ACR-2026-0006\", \"participant\": {\"name\": \"Amir Hakimi\", \"matric\": \"B032410001\"}, \"verification\": {\"credential\": \"UTEM-MC-2026-0007\", \"qv_reference\": \"QV/2026/088\", \"parent_course\": null, \"completion_confirmed_by\": \"Hakim Rahman\"}, \"completion_date\": \"2026-10-20\"}','deposited',10,'2026-10-27 10:00:00','2026-10-26 10:00:00','2026-11-05 09:00:00'),(7,'ACR-2026-0007',1,11,18,6,3.0,6,'2026-09-30','Verified OOP practical and SOLID JUnit project.','{\"name\": {\"en\": \"Object-Oriented Software Construction\", \"ms\": \"Pembinaan Perisian Berorientasikan Objek\"}, \"created\": \"2026-09-30\", \"credits\": \"3.0\", \"category\": \"UMC\", \"outcomes\": [\"Demonstrate verified micro-credential learning outcomes.\", \"Apply domain competencies in practical assessments.\"], \"provider\": \"Universiti Teknikal Malaysia Melaka\", \"mqf_level\": 6, \"reference\": \"ACR-2026-0007\", \"participant\": {\"name\": \"Amir Hakimi\", \"matric\": \"B032410001\"}, \"verification\": {\"credential\": \"UTEM-MC-2026-0010\", \"qv_reference\": \"QV/2026/042\", \"parent_course\": \"BITD 1123\", \"completion_confirmed_by\": \"Hakim Rahman\"}, \"completion_date\": \"2026-09-30\"}','deposited',10,'2026-10-07 10:00:00','2026-10-06 10:00:00','2026-11-05 09:00:00'),(8,'ACR-2026-0008',19,13,21,7,3.0,7,'2026-10-01','Verified Lean Canvas dossier and Venture Pitch defence.','{\"name\": {\"en\": \"Strategic Technopreneurship & Venture Creation\", \"ms\": \"Teknousahawan Strategik & Penciptaan Usaha Niaga\"}, \"created\": \"2026-10-01\", \"credits\": \"3.0\", \"category\": \"UMC\", \"outcomes\": [\"Demonstrate verified micro-credential learning outcomes.\", \"Apply domain competencies in practical assessments.\"], \"provider\": \"Universiti Teknikal Malaysia Melaka\", \"mqf_level\": 7, \"reference\": \"ACR-2026-0008\", \"participant\": {\"name\": \"Siti Sarah\", \"matric\": \"M042510019\"}, \"verification\": {\"credential\": \"UTEM-MC-2026-0011\", \"qv_reference\": \"QV/2026/065\", \"parent_course\": \"MBA 5113\", \"completion_confirmed_by\": \"Hakim Rahman\"}, \"completion_date\": \"2026-10-01\"}','deposited',25,'2026-10-10 10:00:00','2026-10-09 10:00:00','2026-11-05 09:00:00'),(9,'ACR-2026-0009',18,8,9,5,2.0,6,'2026-10-20','Verified OWASP lab and DevSecOps pipeline capstone.','{\"name\": {\"en\": \"Secure Software Development Lifecycle (SSDLC)\", \"ms\": \"Kitar Hayat Pembangunan Perisian Selamat (SSDLC)\"}, \"created\": \"2026-10-20\", \"credits\": \"2.0\", \"category\": \"SAMC\", \"outcomes\": [\"Demonstrate verified micro-credential learning outcomes.\", \"Apply domain competencies in practical assessments.\"], \"provider\": \"Universiti Teknikal Malaysia Melaka\", \"mqf_level\": 6, \"reference\": \"ACR-2026-0009\", \"participant\": {\"name\": \"Sharifah Puteri Nurul Ain Syuhada\", \"matric\": \"B032410088\"}, \"verification\": {\"credential\": \"UTEM-MC-2026-0008\", \"qv_reference\": \"QV/2026/088\", \"parent_course\": null, \"completion_confirmed_by\": \"Hakim Rahman\"}, \"completion_date\": \"2026-10-20\"}','prepared',NULL,NULL,'2026-10-26 10:00:00','2026-11-05 09:00:00'),(10,'ACR-2026-0010',19,6,1,2,2.0,6,'2026-08-20','Verified marks across 2 module tests and capstone project.','{\"name\": {\"en\": \"Data Structures\", \"ms\": \"Struktur Data\"}, \"created\": \"2026-08-20\", \"credits\": \"2.0\", \"category\": \"UMC\", \"outcomes\": [\"Demonstrate verified micro-credential learning outcomes.\", \"Apply domain competencies in practical assessments.\"], \"provider\": \"Universiti Teknikal Malaysia Melaka\", \"mqf_level\": 6, \"reference\": \"ACR-2026-0010\", \"participant\": {\"name\": \"Siti Sarah\", \"matric\": \"M042510019\"}, \"verification\": {\"credential\": \"UTEM-MC-2026-0006\", \"qv_reference\": \"QV/2025/112\", \"parent_course\": \"BITS 1123\", \"completion_confirmed_by\": \"Hakim Rahman\"}, \"completion_date\": \"2026-08-20\"}','consent_given',10,'2026-08-28 10:00:00','2026-08-26 10:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `acrs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admissions`
--

DROP TABLE IF EXISTS `admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `programme_id` bigint unsigned NOT NULL,
  `intake` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `evidence_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` bigint unsigned NOT NULL,
  `recorded_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `admissions_user_id_foreign` (`user_id`),
  KEY `admissions_programme_id_foreign` (`programme_id`),
  KEY `admissions_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `admissions_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `admissions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `admissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admissions`
--

LOCK TABLES `admissions` WRITE;
/*!40000 ALTER TABLE `admissions` DISABLE KEYS */;
INSERT INTO `admissions` VALUES (1,1,1,'2026/1','ADM-SURAT-TAWARAN-2026-01',10,'2026-10-04 01:42:39'),(2,2,1,'2026/1','ADM-SURAT-TAWARAN-2026-02',10,'2026-10-04 01:42:39'),(3,3,2,'2026/1','ADM-SURAT-TAWARAN-2026-03',10,'2026-09-15 10:00:00'),(4,18,8,'2026/1','ADM-SURAT-TAWARAN-2026-04',25,'2026-09-20 10:00:00'),(5,19,5,'2026/1','ADM-SURAT-TAWARAN-2026-05',25,'2026-09-22 10:00:00'),(6,4,1,'2026/1','ADM-SURAT-TAWARAN-2026-06',10,'2026-09-25 10:00:00');
/*!40000 ALTER TABLE `admissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_decisions`
--

DROP TABLE IF EXISTS `approval_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_decisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_version_id` bigint unsigned NOT NULL,
  `stage` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `decision` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `decided_by` bigint unsigned NOT NULL,
  `decided_role` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified_category` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_ref` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_date` date DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `conditions` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `approval_decisions_mc_version_id_foreign` (`mc_version_id`),
  KEY `approval_decisions_decided_by_foreign` (`decided_by`),
  CONSTRAINT `approval_decisions_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `approval_decisions_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_decisions`
--

LOCK TABLES `approval_decisions` WRITE;
/*!40000 ALTER TABLE `approval_decisions` DISABLE KEYS */;
INSERT INTO `approval_decisions` VALUES (1,1,'endorsement','endorsed',10,'deputy_dean','umc','JKKP-FTMK-2025/08','2026-03-04','Curriculum validated against BITS 1123.',NULL,'2026-03-04 01:42:39'),(2,1,'senate','approved',10,'deputy_dean',NULL,'Senate 3/2026','2026-05-04','Formally accredited by Senate for 2 academic credits.',NULL,'2026-05-04 01:42:39'),(3,2,'endorsement','endorsed',10,'deputy_dean','samc','JKKP-FTMK-2025/10','2026-04-04','Strong industry need confirmed.',NULL,'2026-04-04 01:42:39'),(4,2,'senate','approved',10,'deputy_dean',NULL,'Senate 2/2026','2026-06-04','Approved under MQA Standalone MC framework.',NULL,'2026-06-04 01:42:39'),(5,6,'endorsement','endorsed',10,'deputy_dean','samc','JKKP-FTMK-2026/02','2026-10-27','Endorsed based on favourable external expert review.',NULL,'2026-10-27 01:42:39'),(6,3,'senate','approved',10,'deputy_dean','mce','JKPA-FTMK-2026/03','2026-07-15','Approved as non-credit executive micro-credential.',NULL,'2026-07-15 15:00:00'),(7,5,'senate','approved',10,'deputy_dean','umc','Senate 8/2026','2026-10-28','Approved for 2.0 credits unbundled from BITS 1125.','Ensure SQL lab hours are logged in CQI.','2026-10-28 16:00:00'),(8,6,'senate','approved',10,'deputy_dean','samc','Senate 8/2026','2026-10-28','Approved for 2.0 credits under standalone framework.',NULL,'2026-10-28 16:30:00'),(9,7,'senate','approved',25,'deputy_dean','mce','JKPA-FPTT-2026/04','2026-10-10','Approved for FPTT professional offering.',NULL,'2026-10-10 11:00:00'),(10,8,'senate','approved',10,'deputy_dean','umc','JKKP-FTMK-2026/09','2026-10-04','Minor revision v2 approved with full credit transition.',NULL,'2026-10-04 14:00:00'),(11,9,'senate','approved',10,'deputy_dean','samc','Senate 6/2026','2026-08-15','Approved for 2.0 credits in Cybersecurity.',NULL,'2026-08-15 15:00:00'),(12,10,'endorsement','returned',10,'deputy_dean','umc','JKKP-FTMK-2026/11','2026-11-02','Returned to proposer for minor revision.','Complete assessment rubric readiness item and resolve reviewer SLT comment.','2026-11-02 17:00:00'),(13,12,'endorsement','endorsed',10,'deputy_dean','samc','JKKP-FTMK-2026/11','2026-10-29','Endorsed by faculty committee; forwarded for final approval.',NULL,'2026-10-29 15:00:00'),(14,18,'senate','approved',10,'deputy_dean','umc','Senate 4/2026','2026-06-15','Approved unbundled course from BITD 1123.',NULL,'2026-06-15 15:00:00'),(15,21,'senate','approved',25,'deputy_dean','umc','Senate 5/2026','2026-07-20','Approved postgraduate UMC for ODL MBA.',NULL,'2026-07-20 15:00:00'),(16,24,'senate','approved',25,'deputy_dean','umc','Senate 6/2026','2026-08-10','Approved postgraduate UMC for FAIX MTDL.',NULL,'2026-08-10 15:00:00'),(17,25,'endorsement','not_approved',10,'deputy_dean','samc','JKKP-FTMK-2026/11','2026-11-01','Rejected due to outdated technology scope and low industry demand.',NULL,'2026-11-01 16:00:00'),(18,27,'senate','approved',11,'deputy_dean','umc','Senate 8/2026','2026-10-12','Material revision v2 approved by Senate.','Learners on v1 may complete within 6 months or transition to v2.','2026-10-12 15:00:00');
/*!40000 ALTER TABLE `approval_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_component_outcomes`
--

DROP TABLE IF EXISTS `assessment_component_outcomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_component_outcomes` (
  `assessment_component_id` bigint unsigned NOT NULL,
  `learning_outcome_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`assessment_component_id`,`learning_outcome_id`),
  KEY `assessment_component_outcomes_learning_outcome_id_foreign` (`learning_outcome_id`),
  CONSTRAINT `assessment_component_outcomes_assessment_component_id_foreign` FOREIGN KEY (`assessment_component_id`) REFERENCES `assessment_components` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assessment_component_outcomes_learning_outcome_id_foreign` FOREIGN KEY (`learning_outcome_id`) REFERENCES `learning_outcomes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_component_outcomes`
--

LOCK TABLES `assessment_component_outcomes` WRITE;
/*!40000 ALTER TABLE `assessment_component_outcomes` DISABLE KEYS */;
INSERT INTO `assessment_component_outcomes` VALUES (1,1),(2,2),(3,3),(4,4),(5,5),(6,6),(7,8),(9,9),(10,10),(13,11),(14,13),(15,15),(8,16),(8,17),(11,20),(12,21),(16,25),(17,26),(18,29),(19,30),(20,33),(20,34);
/*!40000 ALTER TABLE `assessment_component_outcomes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_components`
--

DROP TABLE IF EXISTS `assessment_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_version_id` bigint unsigned NOT NULL,
  `mc_module_id` bigint unsigned DEFAULT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight_percent` decimal(5,1) NOT NULL DEFAULT '0.0',
  `csv_key` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_mark` decimal(6,2) NOT NULL DEFAULT '100.00',
  `pass_mark` decimal(6,2) NOT NULL DEFAULT '50.00',
  PRIMARY KEY (`id`),
  KEY `assessment_components_mc_version_id_foreign` (`mc_version_id`),
  KEY `assessment_components_mc_module_id_foreign` (`mc_module_id`),
  CONSTRAINT `assessment_components_mc_module_id_foreign` FOREIGN KEY (`mc_module_id`) REFERENCES `mc_modules` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assessment_components_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_components`
--

LOCK TABLES `assessment_components` WRITE;
/*!40000 ALTER TABLE `assessment_components` DISABLE KEYS */;
INSERT INTO `assessment_components` VALUES (1,1,1,'Module 1 Test: Linear Data Structures',30.0,'module1_mark',100.00,50.00),(2,1,2,'Module 2 Test: Non-Linear Structures',30.0,'module2_mark',100.00,50.00),(3,1,NULL,'Comprehensive Capstone Coding Project',40.0,'project_mark',100.00,50.00),(4,2,3,'Docker Containerization Practical Lab',50.0,'docker_lab',100.00,50.00),(5,2,4,'Kubernetes Production Cluster Project',50.0,'k8s_project',100.00,50.00),(6,3,5,'Leadership Case Simulation & Reflection Portfolio',100.0,'leadership_portfolio',100.00,50.00),(7,5,7,'Relational Schema & BCNF Normalization Assignment',50.0,'schema_norm',100.00,50.00),(8,5,8,'SQL Query Tuning & Transaction Isolation Lab Test',50.0,'sql_tuning',100.00,50.00),(9,6,9,'Predictive Analytics Python Notebook Project',100.0,'analytics_proj',100.00,50.00),(10,7,10,'DMAIC Process Capability Case Study',100.0,'dmaic_case',100.00,50.00),(11,9,19,'STRIDE Threat Model & OWASP Remediation Lab',50.0,'owasp_lab',100.00,50.00),(12,9,20,'Automated DevSecOps Pipeline Capstone',50.0,'devsecops_cap',100.00,50.00),(13,13,12,'AC/DC Network Theorem Analysis Assessment',100.0,'circuit_test',100.00,50.00),(14,15,14,'ESP32 Sensor Telemetry & MQTT Firmware Project',100.0,'iot_firmware',100.00,50.00),(15,17,16,'High-Dimensional Statistical Modelling Exam',100.0,'fds_exam',100.00,60.00),(16,18,25,'OOP Class Hierarchy & Polymorphism Practical',50.0,'oop_prac',100.00,50.00),(17,18,26,'SOLID Refactoring & JUnit Test Suite Project',50.0,'solid_junit',100.00,50.00),(18,21,30,'Lean Canvas & Market Validation Dossier',50.0,'lean_dossier',100.00,60.00),(19,21,31,'Venture Valuation & Executive Pitch Defence',50.0,'venture_pitch',100.00,60.00),(20,24,35,'Enterprise RAG & Transformer Fine-Tuning Project',100.0,'rag_project',100.00,60.00);
/*!40000 ALTER TABLE `assessment_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_result_revisions`
--

DROP TABLE IF EXISTS `assessment_result_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_result_revisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_result_id` bigint unsigned NOT NULL,
  `old_score` decimal(6,2) DEFAULT NULL,
  `new_score` decimal(6,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `assessment_result_revisions_assessment_result_id_foreign` (`assessment_result_id`),
  KEY `assessment_result_revisions_requested_by_foreign` (`requested_by`),
  KEY `assessment_result_revisions_approved_by_foreign` (`approved_by`),
  CONSTRAINT `assessment_result_revisions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assessment_result_revisions_assessment_result_id_foreign` FOREIGN KEY (`assessment_result_id`) REFERENCES `assessment_results` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assessment_result_revisions_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_result_revisions`
--

LOCK TABLES `assessment_result_revisions` WRITE;
/*!40000 ALTER TABLE `assessment_result_revisions` DISABLE KEYS */;
INSERT INTO `assessment_result_revisions` VALUES (1,1,84.00,88.00,'Rubric re-moderation for edge case handling in lab 3.',6,10,'2026-08-30 01:42:39'),(2,8,62.00,68.00,'Late penalty waived following approved medical certificate.',6,10,'2026-11-02 02:00:00'),(3,12,88.00,91.00,'Bonus marks awarded for O(V log V) priority queue optimization.',6,10,'2026-08-24 09:30:00'),(4,23,85.00,89.00,'Re-evaluation of GitLab DAST container scan rule.',7,10,'2026-10-24 09:00:00');
/*!40000 ALTER TABLE `assessment_result_revisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_results`
--

DROP TABLE IF EXISTS `assessment_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enrolment_id` bigint unsigned NOT NULL,
  `assessment_component_id` bigint unsigned NOT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'provisional',
  `source` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `import_file_id` bigint unsigned DEFAULT NULL,
  `entered_by` bigint unsigned DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_evidence` text COLLATE utf8mb4_unicode_ci,
  `hold_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_results_enrolment_id_assessment_component_id_unique` (`enrolment_id`,`assessment_component_id`),
  KEY `assessment_results_assessment_component_id_foreign` (`assessment_component_id`),
  KEY `assessment_results_import_file_id_foreign` (`import_file_id`),
  KEY `assessment_results_entered_by_foreign` (`entered_by`),
  KEY `assessment_results_verified_by_foreign` (`verified_by`),
  CONSTRAINT `assessment_results_assessment_component_id_foreign` FOREIGN KEY (`assessment_component_id`) REFERENCES `assessment_components` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assessment_results_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assessment_results_entered_by_foreign` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assessment_results_import_file_id_foreign` FOREIGN KEY (`import_file_id`) REFERENCES `import_files` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `assessment_results_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_results`
--

LOCK TABLES `assessment_results` WRITE;
/*!40000 ALTER TABLE `assessment_results` DISABLE KEYS */;
INSERT INTO `assessment_results` VALUES (1,1,4,88.00,'verified','manual',NULL,6,10,'2026-08-31 01:42:39','Practical lab evaluation submitted and graded.',NULL,NULL,NULL),(2,1,5,92.00,'verified','manual',NULL,6,10,'2026-08-31 01:42:39','Git repository and Kubernetes deployment validated.',NULL,NULL,NULL),(3,2,4,76.00,'verified','manual',NULL,6,10,'2026-08-31 01:42:39',NULL,NULL,NULL,NULL),(4,2,5,82.00,'verified','manual',NULL,6,10,'2026-08-31 01:42:39',NULL,NULL,NULL,NULL),(5,3,1,90.00,'verified','manual',NULL,6,10,'2026-10-30 01:42:39',NULL,NULL,NULL,NULL),(6,3,2,85.00,'provisional','manual',NULL,6,NULL,NULL,NULL,NULL,NULL,NULL),(7,4,1,74.00,'verified','manual',NULL,6,10,'2026-10-30 01:42:39',NULL,NULL,NULL,NULL),(8,5,1,62.00,'provisional','manual',NULL,6,NULL,NULL,NULL,NULL,NULL,NULL),(9,6,1,45.00,'held','manual',NULL,6,NULL,NULL,NULL,'Suspected identical code submission; integrity inquiry opened.',NULL,NULL),(10,9,1,86.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Moderated exam script & code repo.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(11,9,2,89.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Moderated exam script & code repo.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(12,9,3,91.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Capstone viva verified.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(13,10,1,80.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Verified.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(14,10,2,84.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Verified.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(15,10,3,85.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Verified.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(16,11,1,78.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Verified.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(17,11,2,75.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Verified.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(18,11,3,82.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Verified.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(19,12,1,92.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Verified.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(20,12,2,88.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Verified.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(21,12,3,94.00,'verified','csv',3,6,10,'2026-08-24 10:00:00','Verified.',NULL,'2026-08-22 10:00:00','2026-11-05 09:00:00'),(22,13,11,91.00,'verified','csv',4,7,10,'2026-10-24 10:00:00','OWASP lab verified.',NULL,'2026-10-22 10:00:00','2026-11-05 09:00:00'),(23,13,12,89.00,'verified','csv',4,7,10,'2026-10-24 10:00:00','DevSecOps pipeline verified.',NULL,'2026-10-22 10:00:00','2026-11-05 09:00:00'),(24,14,11,85.00,'verified','csv',4,7,10,'2026-10-24 10:00:00','Verified.',NULL,'2026-10-22 10:00:00','2026-11-05 09:00:00'),(25,14,12,87.00,'verified','csv',4,7,10,'2026-10-24 10:00:00','Verified.',NULL,'2026-10-22 10:00:00','2026-11-05 09:00:00'),(26,15,11,79.00,'verified','csv',4,7,10,'2026-10-24 10:00:00','Verified.',NULL,'2026-10-22 10:00:00','2026-11-05 09:00:00'),(27,15,12,81.00,'verified','csv',4,7,10,'2026-10-24 10:00:00','Verified.',NULL,'2026-10-22 10:00:00','2026-11-05 09:00:00'),(28,16,11,58.00,'verified','csv',4,7,10,'2026-10-24 10:00:00','Passed Module 1.',NULL,'2026-10-22 10:00:00','2026-11-05 09:00:00'),(29,16,12,34.00,'verified','csv',4,7,10,'2026-10-24 10:00:00','Below 50% pass threshold.',NULL,'2026-10-22 10:00:00','2026-11-05 09:00:00'),(30,17,16,88.00,'verified','manual',NULL,6,10,'2026-10-04 10:00:00','JUnit & OOP code review.',NULL,'2026-10-02 10:00:00','2026-11-05 09:00:00'),(31,17,17,90.00,'verified','manual',NULL,6,10,'2026-10-04 10:00:00','SOLID refactoring verified.',NULL,'2026-10-02 10:00:00','2026-11-05 09:00:00'),(32,18,16,81.00,'verified','manual',NULL,6,10,'2026-10-04 10:00:00','Verified.',NULL,'2026-10-02 10:00:00','2026-11-05 09:00:00'),(33,18,17,83.00,'verified','manual',NULL,6,10,'2026-10-04 10:00:00','Verified.',NULL,'2026-10-02 10:00:00','2026-11-05 09:00:00'),(34,19,18,86.00,'verified','manual',NULL,24,25,'2026-10-07 10:00:00','Lean canvas verified.',NULL,'2026-10-05 10:00:00','2026-11-05 09:00:00'),(35,19,19,88.00,'verified','manual',NULL,24,25,'2026-10-07 10:00:00','Panel pitch defence verified.',NULL,'2026-10-05 10:00:00','2026-11-05 09:00:00'),(36,20,20,93.00,'verified','manual',NULL,7,25,'2026-10-29 10:00:00','Transformer fine-tuning verified.',NULL,'2026-10-28 10:00:00','2026-11-05 09:00:00'),(37,21,1,88.00,'provisional','csv',6,6,NULL,NULL,NULL,NULL,'2026-11-03 14:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `assessment_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_chain_heads`
--

DROP TABLE IF EXISTS `audit_chain_heads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_chain_heads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chain` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_seq` bigint unsigned NOT NULL DEFAULT '0',
  `last_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified_seq` bigint unsigned NOT NULL DEFAULT '0',
  `verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_chain_heads_chain_unique` (`chain`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_chain_heads`
--

LOCK TABLES `audit_chain_heads` WRITE;
/*!40000 ALTER TABLE `audit_chain_heads` DISABLE KEYS */;
INSERT INTO `audit_chain_heads` VALUES (1,'main',72,'5c62b9d32bc1aab6873acaf60267f812b95d03ff5b9d68f49bd1bc77fc3b7f99',0,NULL);
/*!40000 ALTER TABLE `audit_chain_heads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_events`
--

DROP TABLE IF EXISTS `audit_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seq` bigint unsigned NOT NULL,
  `occurred_at` timestamp(6) NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_role` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `before` json NOT NULL,
  `after` json NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `context` json NOT NULL,
  `prev_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_events_seq_unique` (`seq`),
  KEY `audit_events_subject_type_subject_id_seq_index` (`subject_type`,`subject_id`,`seq`),
  KEY `audit_events_actor_id_occurred_at_index` (`actor_id`,`occurred_at`),
  CONSTRAINT `audit_events_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_events`
--

LOCK TABLES `audit_events` WRITE;
/*!40000 ALTER TABLE `audit_events` DISABLE KEYS */;
INSERT INTO `audit_events` VALUES (1,1,'2026-11-06 00:52:11.843861',6,NULL,'mc_version.submitted','mc_version','1','{\"status\": \"draft\"}','{\"status\": \"submitted\"}','Submitted Data Structures v1 proposal','{\"ip\": \"10.41.164.20\"}','0000000000000000000000000000000000000000000000000000000000000000','77a3b5a0a8a84a062263dfd1300adddd5feb81dfa699d865a57f24be4fd0c942'),(2,2,'2026-11-06 00:52:11.852213',10,NULL,'mc_version.endorsed','mc_version','1','{\"status\": \"in_review\"}','{\"status\": \"endorsed\"}','Endorsed by FTMK Academic Committee (JKKP-FTMK-2025/08)','{\"ip\": \"10.41.164.20\"}','77a3b5a0a8a84a062263dfd1300adddd5feb81dfa699d865a57f24be4fd0c942','22e37e594fc415d8e7a9d8c6213e13a5ac671c08553e86d64bef8cf7947be053'),(3,3,'2026-11-06 00:52:11.855082',10,NULL,'mc_version.approved','mc_version','1','{\"status\": \"endorsed\"}','{\"status\": \"approved\"}','Approved by Senate (Senate 3/2026)','{\"ip\": \"10.41.164.20\"}','22e37e594fc415d8e7a9d8c6213e13a5ac671c08553e86d64bef8cf7947be053','c9c3508aeea0610eacb8d19cccd06f91ed0055a549d65c838836db7b02a72727'),(4,4,'2026-11-06 00:52:11.857726',10,NULL,'mc_version.approved','mc_version','2','{\"status\": \"endorsed\"}','{\"status\": \"approved\"}','Approved Cloud Native Development v1 (Senate 2/2026)','{\"ip\": \"10.41.164.20\"}','c9c3508aeea0610eacb8d19cccd06f91ed0055a549d65c838836db7b02a72727','d7353bf4c43827b5c794ba79d247ea2453bd83741d191665549532ae8a15cc85'),(5,5,'2026-11-06 00:52:11.859991',10,NULL,'mc_version.approved','mc_version','9','{\"status\": \"endorsed\"}','{\"status\": \"approved\"}','Approved Secure SDLC v1 (Senate 6/2026)','{\"ip\": \"10.41.164.20\"}','d7353bf4c43827b5c794ba79d247ea2453bd83741d191665549532ae8a15cc85','be4942acd10d42afbf0dad439683d0a316958abe2fc791b4550bcaee2d8ece01'),(6,6,'2026-11-06 00:52:11.862236',10,NULL,'mc_version.returned','mc_version','10','{\"status\": \"in_review\"}','{\"status\": \"returned\"}','Returned Algorithmic Modelling for assessment rubric completion','{\"ip\": \"10.41.164.20\"}','be4942acd10d42afbf0dad439683d0a316958abe2fc791b4550bcaee2d8ece01','bc255218341d99f7306f75f96a4880dfaee7cafa5cec75b7c27d06bd49a4b007'),(7,7,'2026-11-06 00:52:11.865013',10,NULL,'mc_version.not_approved','mc_version','25','{\"status\": \"in_review\"}','{\"status\": \"not_approved\"}','Rejected Legacy Mainframe COBOL proposal','{\"ip\": \"10.41.164.20\"}','bc255218341d99f7306f75f96a4880dfaee7cafa5cec75b7c27d06bd49a4b007','75f5aee368557358af9016f2c726b512d8aa9481e25e6fc320a8e699c50b76bc'),(8,8,'2026-11-06 00:52:11.867366',11,NULL,'mc_version.superseded','mc_version','26','{\"status\": \"approved\"}','{\"status\": \"superseded\"}','Superseded PLC v1 by material revision v2 (#27)','{\"ip\": \"10.41.164.20\"}','75f5aee368557358af9016f2c726b512d8aa9481e25e6fc320a8e699c50b76bc','3fd504b63eede8982bcd286111cef7fa599781b6247a305319fcf0cd25f85232'),(9,9,'2026-11-06 00:52:11.869597',6,NULL,'offering.opened','offering','1','{\"status\": \"ready\"}','{\"status\": \"open\"}','Opened registration for Cloud Native Development 2026/1','{\"ip\": \"10.41.164.20\"}','3fd504b63eede8982bcd286111cef7fa599781b6247a305319fcf0cd25f85232','218dc9e2d1049a16ba59bee33d30276e7273d4a8536df5fe011b9104b0298894'),(10,10,'2026-11-06 00:52:11.871988',12,NULL,'offering.suspended','offering','6','{\"status\": \"running\"}','{\"status\": \"suspended\"}','Placed cohort 2026/S1 on hold for sandbox security patch','{\"ip\": \"10.41.164.20\"}','218dc9e2d1049a16ba59bee33d30276e7273d4a8536df5fe011b9104b0298894','e60d7b3fa63aebcfbb4e48f73716ce7e4a1dfdd9df2e65008c57ac2582060158'),(11,11,'2026-11-06 00:52:11.874183',10,NULL,'enrolment.completed','enrolment','1','{\"status\": \"enrolled\"}','{\"status\": \"completed\"}','Confirmed completion for Amir Hakimi (ENR-2026-0001)','{\"ip\": \"10.41.164.20\"}','e60d7b3fa63aebcfbb4e48f73716ce7e4a1dfdd9df2e65008c57ac2582060158','28f0778617f43482efb5e2e2a943bd2ec0d7ede4d26186acbff76b5bbf03cd2d'),(12,12,'2026-11-06 00:52:11.876293',10,NULL,'enrolment.completed','enrolment','9','{\"status\": \"enrolled\"}','{\"status\": \"completed\"}','Confirmed completion for Amir Hakimi (ENR-2026-0009)','{\"ip\": \"10.41.164.20\"}','28f0778617f43482efb5e2e2a943bd2ec0d7ede4d26186acbff76b5bbf03cd2d','621be56fea8ad953546c6774be9af5ec18f02b4cb916ce12e0672b76e49d8eb1'),(13,13,'2026-11-06 00:52:11.878748',10,NULL,'enrolment.completed','enrolment','13','{\"status\": \"enrolled\"}','{\"status\": \"completed\"}','Confirmed completion for Amir Hakimi (ENR-2026-0013)','{\"ip\": \"10.41.164.20\"}','621be56fea8ad953546c6774be9af5ec18f02b4cb916ce12e0672b76e49d8eb1','6e85a281128df1539cd8b0951fc06b5d9b8bdb3b600db17b1788f8c764b1b5e0'),(14,14,'2026-11-06 00:52:11.881091',25,NULL,'enrolment.completed','enrolment','19','{\"status\": \"enrolled\"}','{\"status\": \"completed\"}','Confirmed completion for Siti Sarah (ENR-2026-0019)','{\"ip\": \"10.41.164.20\"}','6e85a281128df1539cd8b0951fc06b5d9b8bdb3b600db17b1788f8c764b1b5e0','187362a99c30b511a63e7d1f405055fa4232d3f6f4cc4808350182e830f8f5b3'),(15,15,'2026-11-06 00:52:11.883076',10,NULL,'result.revised','assessment_result','1','{\"score\": \"84.00\"}','{\"score\": \"88.00\"}','Approved rubric re-moderation for Docker Lab','{\"ip\": \"10.41.164.20\"}','187362a99c30b511a63e7d1f405055fa4232d3f6f4cc4808350182e830f8f5b3','7365b4a10e30538d11b4a7eeb3fabfc5ed92329c3b6c037fa19cf867997a86b5'),(16,16,'2026-11-06 00:52:11.885441',10,NULL,'credential.issued','credential','1','[]','{\"number\": \"UTEM-MC-2026-0001\", \"status\": \"valid\"}','Issued Ed25519 VC-JWT credential to Amir Hakimi','{\"ip\": \"10.41.164.20\"}','7365b4a10e30538d11b4a7eeb3fabfc5ed92329c3b6c037fa19cf867997a86b5','38bccba53af9fd4e589918bcb4c33f7d55ccd35f2fe62f74b9ee97171bbcfb6d'),(17,17,'2026-11-06 00:52:11.887567',12,NULL,'credential.corrected','credential','2','{\"status\": \"valid\"}','{\"status\": \"corrected\", \"replaced_by_id\": 3}','Re-issued revision 2 credential UTEM-MC-2026-0002-R1 for Bee Tan','{\"ip\": \"10.41.164.20\"}','38bccba53af9fd4e589918bcb4c33f7d55ccd35f2fe62f74b9ee97171bbcfb6d','72d022779111c66934fc13546bf64b4c9d1cb91bba77dd2408d0fe0ebef74efd'),(18,18,'2026-11-06 00:52:11.889529',12,NULL,'credential.revoked','credential','10','{\"status\": \"valid\"}','{\"status\": \"revoked\"}','Revoked UTEM-MC-2026-0009 following employer sponsorship audit','{\"ip\": \"10.41.164.20\"}','72d022779111c66934fc13546bf64b4c9d1cb91bba77dd2408d0fe0ebef74efd','fdae1d472bd9b2122d813d3846c7f00e068794aeda9851804b8437865750a371'),(19,19,'2026-11-06 00:52:11.892021',12,NULL,'qv.verified','qv_record','1','{\"status\": \"submitted\"}','{\"ref\": \"QV/2023/051\", \"status\": \"verified\"}','Recorded MQA Quality Verification for MC-FTMK-002','{\"ip\": \"10.41.164.20\"}','fdae1d472bd9b2122d813d3846c7f00e068794aeda9851804b8437865750a371','95668f9113279d0ee9f27dd3fc4644f1eab7487554d9833f287a70c416390631'),(20,20,'2026-11-06 00:52:11.894302',12,NULL,'qv.verified','qv_record','2','{\"status\": \"submitted\"}','{\"ref\": \"QV/2025/112\", \"status\": \"verified\"}','Recorded MQA Quality Verification for MC-FTMK-001','{\"ip\": \"10.41.164.20\"}','95668f9113279d0ee9f27dd3fc4644f1eab7487554d9833f287a70c416390631','f042a9a6040a881edc39af1e7a553b80280558b57d1d2ebf9d0912e6d9df114f'),(21,21,'2026-11-06 00:52:11.896417',10,NULL,'cqi.confirmed','cqi_record','1','{\"status\": \"submitted\"}','{\"status\": \"confirmed\"}','Confirmed Cohort 2026/1 CQI Report (CQI-2026-001)','{\"ip\": \"10.41.164.20\"}','f042a9a6040a881edc39af1e7a553b80280558b57d1d2ebf9d0912e6d9df114f','2ac7fc1fa0b8592f9bfa515d3cc20c4bbd2d2768e8a1a677ca943cfd1020aac1'),(22,22,'2026-11-06 00:52:11.898994',10,NULL,'cqi.confirmed','cqi_record','3','{\"status\": \"submitted\"}','{\"status\": \"confirmed\"}','Confirmed Cohort 2026/1-DS CQI Report (CQI-2026-0003)','{\"ip\": \"10.41.164.20\"}','2ac7fc1fa0b8592f9bfa515d3cc20c4bbd2d2768e8a1a677ca943cfd1020aac1','70e6b0733ed22d30fe3eee27fe5efb6ae52d9e16f4ac3e0ae15f177cc32af09a'),(23,23,'2026-11-06 00:52:11.901283',10,NULL,'acr.confirmed','acr','1','{\"status\": \"pending\"}','{\"status\": \"confirmed\"}','Confirmed Academic Credit Record ACR-2026-0001','{\"ip\": \"10.41.164.20\"}','70e6b0733ed22d30fe3eee27fe5efb6ae52d9e16f4ac3e0ae15f177cc32af09a','7c0c61174166b4143d5962193a0623c07c136930bac1dbf004ff060efa3bc487'),(24,24,'2026-11-06 00:52:11.903569',12,NULL,'acr.deposited','abc_deposit','1','{\"status\": \"confirmed\"}','{\"abc_reference\": \"ABC-MYS-2026-098471\"}','Deposited ACR-2026-0001 in ABC Batch #1','{\"ip\": \"10.41.164.20\"}','7c0c61174166b4143d5962193a0623c07c136930bac1dbf004ff060efa3bc487','f6bd8a8ca264fab8e401af5e8af08a442d30d5484344d25c4adfe7f747ef9a20'),(25,25,'2026-11-06 00:52:11.905919',10,NULL,'pathway.published','pathway','1','{\"status\": \"approved\"}','{\"status\": \"published\"}','Published Stackable Pathway PW-BITS-2026-01','{\"ip\": \"10.41.164.20\"}','f6bd8a8ca264fab8e401af5e8af08a442d30d5484344d25c4adfe7f747ef9a20','53f001a4213b91f964fb40b0e72f4b4724b4ff2ffa87d6c89cf5647ebbfb255a'),(26,26,'2026-11-06 00:52:11.908146',10,NULL,'plan.decided','stackable_plan','1','{\"status\": \"submitted\"}','{\"status\": \"suitable\"}','Confirmed stackable study plan PLAN-2026-0001','{\"ip\": \"10.41.164.20\"}','53f001a4213b91f964fb40b0e72f4b4724b4ff2ffa87d6c89cf5647ebbfb255a','4ddfe5f35881ef4ef882234cce2334030a1c70316bd84240ad3d9812e13a37e9'),(27,27,'2026-11-06 00:52:11.910302',10,NULL,'transfer.approved','transfer_application','1','{\"status\": \"in_review\"}','{\"status\": \"approved\"}','Approved Credit Transfer Application TR-2026-0001','{\"ip\": \"10.41.164.20\"}','4ddfe5f35881ef4ef882234cce2334030a1c70316bd84240ad3d9812e13a37e9','49c6cef9efa58090a3b83ebc75794abdbd7ffd197da6e43c169b2c6cf4ef0c60'),(28,28,'2026-11-06 00:52:11.912854',10,NULL,'transfer.approved','transfer_application','3','{\"status\": \"in_review\"}','{\"status\": \"approved\"}','Approved Credit Transfer Application TR-2026-0003','{\"ip\": \"10.41.164.20\"}','49c6cef9efa58090a3b83ebc75794abdbd7ffd197da6e43c169b2c6cf4ef0c60','218e6f89a4507ce7d236b401d9a9f4d4348f6d15c80180fa740f939fa2644d02'),(29,29,'2026-11-06 00:52:11.915144',10,NULL,'credit.redeemed','redemption_event','1','{\"credit_status\": \"provisional\"}','{\"credit_status\": \"redeemed\"}','Redeemed ABC-MYS-2026-098471 against TR-2026-0001','{\"ip\": \"10.41.164.20\"}','218e6f89a4507ce7d236b401d9a9f4d4348f6d15c80180fa740f939fa2644d02','c93e3413172a6aa965d3a383e13f794ede618e1b6f6f61206941ecbc8ccd0277'),(30,30,'2026-11-06 00:52:11.917392',12,NULL,'discrepancy.resolved','discrepancy','1','{\"status\": \"open\"}','{\"status\": \"resolved\"}','Resolved identity discrepancy DISC-0001 via Correction #1','{\"ip\": \"10.41.164.20\"}','c93e3413172a6aa965d3a383e13f794ede618e1b6f6f61206941ecbc8ccd0277','0f5c0624d78e51c4c6bc5dd5ca4614052b8e249d1cce7ae057840ecb6debcfed'),(31,31,'2026-11-06 00:55:14.142768',10,'deputy_dean','auth.signed_in','User','10','[]','[]',NULL,'{\"method\": \"stub+mfa\"}','0f5c0624d78e51c4c6bc5dd5ca4614052b8e249d1cce7ae057840ecb6debcfed','c840ffccb03d49bea735bd62d7196b923cf8776a4474f110cd07f5825b2ad9f5'),(32,32,'2026-11-06 00:55:34.033912',10,'deputy_dean','auth.signed_in','User','10','[]','[]',NULL,'{\"method\": \"stub+mfa\"}','c840ffccb03d49bea735bd62d7196b923cf8776a4474f110cd07f5825b2ad9f5','0299d69510578ffd6e13ada39b830e13c94fc0391126ebbe83a1f3742d658c34'),(33,33,'2026-11-06 00:55:54.204224',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','0299d69510578ffd6e13ada39b830e13c94fc0391126ebbe83a1f3742d658c34','2861e9c2014ae668466f9c1e9244415284babc442afbdfac0074f550b8fdb70a'),(34,34,'2026-11-06 00:57:09.022262',10,'deputy_dean','auth.signed_in','User','10','[]','[]',NULL,'{\"method\": \"stub+mfa\"}','2861e9c2014ae668466f9c1e9244415284babc442afbdfac0074f550b8fdb70a','712cc1cf8b457d54b2705b11450e54c056b88e279cce45ebe67dd73f6b99c8eb'),(35,35,'2026-11-06 00:57:11.013799',1,'student','auth.signed_out','User','1','[]','[]',NULL,'[]','712cc1cf8b457d54b2705b11450e54c056b88e279cce45ebe67dd73f6b99c8eb','46d55b96ce3d9eda89a77ae9456f20bb7254e9d74aa2a5ea653493b740114089'),(36,36,'2026-11-06 00:57:12.906858',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','46d55b96ce3d9eda89a77ae9456f20bb7254e9d74aa2a5ea653493b740114089','e5c72a9400e1ce2a92711caada5e184592ac295b0c0061f5994769443991d010'),(37,37,'2026-11-06 00:57:15.994179',1,'student','auth.signed_out','User','1','[]','[]',NULL,'[]','e5c72a9400e1ce2a92711caada5e184592ac295b0c0061f5994769443991d010','0d5b5a7f38008335626188c092b236d25b07b8fc61924924452c354a85d5a15c'),(38,38,'2026-11-06 00:57:17.588783',8,'lecturer','auth.signed_in','User','8','[]','[]',NULL,'{\"method\": \"stub\"}','0d5b5a7f38008335626188c092b236d25b07b8fc61924924452c354a85d5a15c','e6a4d3f09b233fcf4eb2770165163f58dc47ee545997394c06c69c718582f8bf'),(39,39,'2026-11-06 00:57:47.757944',10,'deputy_dean','auth.signed_in','User','10','[]','[]',NULL,'{\"method\": \"stub+mfa\"}','e6a4d3f09b233fcf4eb2770165163f58dc47ee545997394c06c69c718582f8bf','73039063e8878eb7a0da503074a94159f0910b50e5ba604665a8f3898d6c4ebe'),(40,40,'2026-11-06 01:04:45.775623',25,'deputy_dean','auth.signed_in','User','25','[]','[]',NULL,'{\"method\": \"stub+mfa\"}','73039063e8878eb7a0da503074a94159f0910b50e5ba604665a8f3898d6c4ebe','d80e3b21546059a254ff95c6a153d9452a4122ed20e8650b6f952f58decc3b70'),(41,41,'2026-11-06 01:16:51.845644',10,'deputy_dean','auth.signed_in','User','10','[]','[]',NULL,'{\"method\": \"stub+mfa\"}','d80e3b21546059a254ff95c6a153d9452a4122ed20e8650b6f952f58decc3b70','306aa5c789f5285b11db087c8f6e381e1e044b3962d2d75e7edbfe0d4e265265'),(42,42,'2026-11-06 01:23:00.403284',8,'lecturer','auth.signed_out','User','8','[]','[]',NULL,'[]','306aa5c789f5285b11db087c8f6e381e1e044b3962d2d75e7edbfe0d4e265265','85a91774c88db99006aa711ae7eae1e3ae1da3121b5f0e4c614f8efc7c67129e'),(43,43,'2026-11-06 01:28:08.979812',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','85a91774c88db99006aa711ae7eae1e3ae1da3121b5f0e4c614f8efc7c67129e','70ce410ac26e545dd2f36a866163dbdaf92850f1854df1e982870e47f9ee1ebd'),(44,44,'2026-11-06 01:36:19.059669',25,'deputy_dean','auth.signed_in','User','25','[]','[]',NULL,'{\"method\": \"stub+mfa\"}','70ce410ac26e545dd2f36a866163dbdaf92850f1854df1e982870e47f9ee1ebd','1bb634d78ea39d269d21ba3ba29e99d1bd0528f8f80c2897ed619a12b76181c3'),(45,45,'2026-11-06 01:37:46.276648',25,'deputy_dean','auth.signed_in','User','25','[]','[]',NULL,'{\"method\": \"stub+mfa\"}','1bb634d78ea39d269d21ba3ba29e99d1bd0528f8f80c2897ed619a12b76181c3','2cbb92923046227655b12170d44adc2c25af27a49d8942878b30ed32b4ed0816'),(46,46,'2026-11-06 01:50:09.609248',6,'lecturer','auth.signed_in','User','6','[]','[]',NULL,'{\"method\": \"stub\"}','2cbb92923046227655b12170d44adc2c25af27a49d8942878b30ed32b4ed0816','ca9cb26c39d8cfdefafba3da61f7cd07342de8822e90c90bdc8ae479903751b0'),(47,47,'2026-11-06 01:50:10.884372',6,'lecturer','auth.signed_out','User','6','[]','[]',NULL,'[]','ca9cb26c39d8cfdefafba3da61f7cd07342de8822e90c90bdc8ae479903751b0','5d1980c4601a255ba7b4e9a147108574c0be3a1fc17fc2d6f4afe6b0ea44d043'),(48,48,'2026-11-06 01:50:12.066491',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','5d1980c4601a255ba7b4e9a147108574c0be3a1fc17fc2d6f4afe6b0ea44d043','b881968790efe46c83759f2ef5ca97d9a7058aed0ec56f8ea07419590a354d56'),(49,49,'2026-11-06 01:52:59.807573',1,'student','auth.signed_out','User','1','[]','[]',NULL,'[]','b881968790efe46c83759f2ef5ca97d9a7058aed0ec56f8ea07419590a354d56','fc9f6b116e36daa895e132a01be807f683462ba69961cdc006292fd11b014b4c'),(50,50,'2026-11-06 01:53:08.040250',6,'lecturer','auth.signed_in','User','6','[]','[]',NULL,'{\"method\": \"stub\"}','fc9f6b116e36daa895e132a01be807f683462ba69961cdc006292fd11b014b4c','87e19c14bbace3ca91f48b29fe5bc0617a9dfab701535f56a9c0a979281f0ba5'),(51,51,'2026-11-06 01:56:31.510624',6,'lecturer','auth.signed_out','User','6','[]','[]',NULL,'[]','87e19c14bbace3ca91f48b29fe5bc0617a9dfab701535f56a9c0a979281f0ba5','0e1f2295d1f73843f261fe4700935c2e96b9831939893c2fa8f21279e2f6c83c'),(52,52,'2026-11-06 01:56:38.160299',5,NULL,'auth.refused_inactive','User','5','[]','[]',NULL,'[]','0e1f2295d1f73843f261fe4700935c2e96b9831939893c2fa8f21279e2f6c83c','48f55c42fd9eae9535abc33ead5789f5a0185d7cf1dd0e9346c18db2703cb701'),(53,53,'2026-11-06 01:56:49.463907',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','48f55c42fd9eae9535abc33ead5789f5a0185d7cf1dd0e9346c18db2703cb701','11200168ce8f95f7f55a3f04ff2bd2e886e0ccc26fb7bf7ba986b7dd45944082'),(54,54,'2026-11-06 02:14:44.938814',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','11200168ce8f95f7f55a3f04ff2bd2e886e0ccc26fb7bf7ba986b7dd45944082','991bcc7d25bd642ab7b128a61baa9414ddafb6181be262590db5f99cb6ea0abc'),(55,55,'2026-11-06 02:21:50.431727',6,'lecturer','auth.signed_in','User','6','[]','[]',NULL,'{\"method\": \"stub\"}','991bcc7d25bd642ab7b128a61baa9414ddafb6181be262590db5f99cb6ea0abc','60bb023a91103580215d1c436dc93fba88eb3ebb781af8c46d1af74e77eb36f1'),(56,56,'2026-11-06 02:44:49.316522',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','60bb023a91103580215d1c436dc93fba88eb3ebb781af8c46d1af74e77eb36f1','9fd92ebc52c5e5ef5b88d57f8ff77cab0399a6e95f8a9209f0ed233cbe00e70d'),(57,57,'2026-11-06 02:44:54.319056',6,'lecturer','auth.signed_in','User','6','[]','[]',NULL,'{\"method\": \"stub\"}','9fd92ebc52c5e5ef5b88d57f8ff77cab0399a6e95f8a9209f0ed233cbe00e70d','d99f8451a36f394ae6147700ebb27c87ca76db6115f3eba31a42125c78cb86f4'),(58,58,'2026-11-06 02:58:58.348060',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','d99f8451a36f394ae6147700ebb27c87ca76db6115f3eba31a42125c78cb86f4','aa186f55c7c3cb5e1050edbe1e7102fca090db73b2b1d489340341cb7fb383df'),(59,59,'2026-11-06 03:06:32.495799',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','aa186f55c7c3cb5e1050edbe1e7102fca090db73b2b1d489340341cb7fb383df','d8b229d7653aa5814cef80ea9d80fcd6ffff498e18eb60d21fc7784b1d24ca01'),(60,60,'2026-11-06 03:06:58.756713',1,'student','auth.signed_out','User','1','[]','[]',NULL,'[]','d8b229d7653aa5814cef80ea9d80fcd6ffff498e18eb60d21fc7784b1d24ca01','e7bbcf6dc6f7225b6f8149077449b6d419daa5cd596b88c79c3e332f3bb845f3'),(61,61,'2026-11-06 03:08:53.082036',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','e7bbcf6dc6f7225b6f8149077449b6d419daa5cd596b88c79c3e332f3bb845f3','2574b642ff7ced83de4803de40aef132bd3a87298d0bed65b4b297739bc9a0e1'),(62,62,'2026-11-06 03:09:43.359825',1,'student','auth.signed_out','User','1','[]','[]',NULL,'[]','2574b642ff7ced83de4803de40aef132bd3a87298d0bed65b4b297739bc9a0e1','aacbc78c67ec4d4faab7f7a7d184cac27c369ff4bac19b0bd281d5d28389e501'),(63,63,'2026-11-06 03:22:33.106090',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','aacbc78c67ec4d4faab7f7a7d184cac27c369ff4bac19b0bd281d5d28389e501','7e426cf5cf00a589f8b5c70dce33a04108f2ab2bb7f56240208ee86d91677c96'),(64,64,'2026-11-06 03:40:33.548719',1,'student','auth.signed_out','User','1','[]','[]',NULL,'[]','7e426cf5cf00a589f8b5c70dce33a04108f2ab2bb7f56240208ee86d91677c96','81441def5ecc1a307a2a3a49f11ddefb4c917d8e6ebf2c380fc9bf83aea69a1a'),(65,65,'2026-11-06 03:41:58.289407',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','81441def5ecc1a307a2a3a49f11ddefb4c917d8e6ebf2c380fc9bf83aea69a1a','1934ea4f44ca4c25b7d2f0d7b5ebe2c0907dd089a76b56afdbfe935c6beb3845'),(66,66,'2026-11-06 07:02:41.291756',1,'student','auth.signed_in','User','1','[]','[]',NULL,'{\"method\": \"stub\"}','1934ea4f44ca4c25b7d2f0d7b5ebe2c0907dd089a76b56afdbfe935c6beb3845','412c12f9bc6d6d62594dc442f7da549085b74bc863128aafaea117d337bcb523'),(67,67,'2026-11-06 07:09:38.972760',8,'lecturer','auth.signed_in','User','8','[]','[]',NULL,'{\"method\": \"stub\"}','412c12f9bc6d6d62594dc442f7da549085b74bc863128aafaea117d337bcb523','9c9534e887b500c2ede53907fc69623f58f9b08010cb77b19dc6ef6266a65fe7'),(68,68,'2026-11-06 07:12:16.848796',6,'lecturer','auth.signed_in','User','6','[]','[]',NULL,'{\"method\": \"stub\"}','9c9534e887b500c2ede53907fc69623f58f9b08010cb77b19dc6ef6266a65fe7','7d7ea43eaaa5e15b6c5a0dc65467c5d4fb581a0e35843b0105592391439f7c22'),(69,69,'2026-11-06 07:12:17.288564',6,'lecturer','auth.signed_in','User','6','[]','[]',NULL,'{\"method\": \"stub\"}','7d7ea43eaaa5e15b6c5a0dc65467c5d4fb581a0e35843b0105592391439f7c22','4512da13887ff11adf9b688c9158516bf030ca8dae28cfd6731171eeb8fb929a'),(70,70,'2026-11-06 07:12:41.034660',6,'lecturer','auth.signed_in','User','6','[]','[]',NULL,'{\"method\": \"stub\"}','4512da13887ff11adf9b688c9158516bf030ca8dae28cfd6731171eeb8fb929a','8649faed162a7c334697b54799c9c336626cfe45f36371959a78bacfb5f2deaf'),(71,71,'2026-11-06 07:29:35.472301',1,'student','auth.signed_out','User','1','[]','[]',NULL,'[]','8649faed162a7c334697b54799c9c336626cfe45f36371959a78bacfb5f2deaf','460718fb5349a756618b71d432f56ad0b4ce01d6fb140bd22d0aaaa0dad28bbb'),(72,72,'2026-11-06 07:29:45.363368',6,'lecturer','auth.signed_in','User','6','[]','[]',NULL,'{\"method\": \"stub\"}','460718fb5349a756618b71d432f56ad0b4ce01d6fb140bd22d0aaaa0dad28bbb','5c62b9d32bc1aab6873acaf60267f812b95d03ff5b9d68f49bd1bc77fc3b7f99');
/*!40000 ALTER TABLE `audit_events` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`mcms`@`%`*/ /*!50003 TRIGGER `audit_events_no_update` BEFORE UPDATE ON `audit_events` FOR EACH ROW BEGIN
    IF @mcms_disable_triggers IS NULL OR @mcms_disable_triggers <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_events is append-only';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`mcms`@`%`*/ /*!50003 TRIGGER `audit_events_no_delete` BEFORE DELETE ON `audit_events` FOR EACH ROW BEGIN
    IF @mcms_disable_triggers IS NULL OR @mcms_disable_triggers <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_events is append-only';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('mcms-cache-2132c496e5ac87b4ebf341ecbb9497cbe5e98a65','i:2;',1793939359),('mcms-cache-2132c496e5ac87b4ebf341ecbb9497cbe5e98a65:timer','i:1793939359;',1793939359),('mcms-cache-356a192b7913b04c54574d18c28d46e6395428ab','i:3;',1793935480),('mcms-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1793935480;',1793935480),('mcms-cache-5c785c036466adea360111aa28563bfd556b5fba','i:2;',1793939135),('mcms-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1793939135;',1793939135),('mcms-cache-c1dfd96eea8cc2b62785275bca38ac261256e278','i:1;',1793930244),('mcms-cache-c1dfd96eea8cc2b62785275bca38ac261256e278:timer','i:1793930244;',1793930244);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `change_requests`
--

DROP TABLE IF EXISTS `change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `change_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `micro_credential_id` bigint unsigned NOT NULL,
  `from_version_id` bigint unsigned NOT NULL,
  `to_version_id` bigint unsigned DEFAULT NULL,
  `class` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `triggers` json NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `impact_note` text COLLATE utf8mb4_unicode_ci,
  `requires_qv` tinyint(1) NOT NULL DEFAULT '0',
  `requested_by` bigint unsigned NOT NULL,
  `status` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recorded',
  `acknowledged_by` bigint unsigned DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `cqi_logged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `change_requests_micro_credential_id_foreign` (`micro_credential_id`),
  KEY `change_requests_from_version_id_foreign` (`from_version_id`),
  KEY `change_requests_to_version_id_foreign` (`to_version_id`),
  KEY `change_requests_requested_by_foreign` (`requested_by`),
  KEY `change_requests_acknowledged_by_foreign` (`acknowledged_by`),
  CONSTRAINT `change_requests_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `change_requests_from_version_id_foreign` FOREIGN KEY (`from_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `change_requests_micro_credential_id_foreign` FOREIGN KEY (`micro_credential_id`) REFERENCES `micro_credentials` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `change_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `change_requests_to_version_id_foreign` FOREIGN KEY (`to_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `change_requests`
--

LOCK TABLES `change_requests` WRITE;
/*!40000 ALTER TABLE `change_requests` DISABLE KEYS */;
INSERT INTO `change_requests` VALUES (1,1,1,8,'minor','[\"technology_update\", \"student_feedback\"]','Upgrade programming examples from Python 3.8 to 3.12 and update benchmark test suites.','No change to credits or MQF level.',0,6,'recorded',10,'2026-10-04 01:42:39',NULL,'2026-10-04 01:42:39'),(2,2,2,NULL,'material','[\"syllabus_expansion\"]','Plan to introduce service mesh (Istio) and observability tools.','May increase SLT hours from 80 to 120.',1,6,'recorded',NULL,NULL,NULL,'2026-10-30 01:42:39'),(3,25,26,27,'material','[\"outcomes\", \"assessment\", \"slt\"]','Upgrade PLC syllabus to IEC 61131-3 Structured Text and add SCADA OPC-UA security assessment.','Replaces Module 2 lab assessment; requires re-approval.',1,8,'approved',11,'2026-10-12 15:00:00','2026-09-14 10:00:00','2026-09-14 09:30:00'),(4,5,5,NULL,'minor','[\"materials\"]','Update PostgreSQL lab image from 15 to 16 and refresh query planner screenshots.','No change to LOs, SLT, or credit value.',0,6,'acknowledged',10,'2026-11-01 11:00:00','2026-11-01 11:05:00','2026-10-30 14:00:00');
/*!40000 ALTER TABLE `change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consents`
--

DROP TABLE IF EXISTS `consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acr_id` bigint unsigned NOT NULL,
  `decision` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
  `wording` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_versions` json NOT NULL,
  `decided_by` bigint unsigned NOT NULL,
  `decided_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `consents_acr_id_foreign` (`acr_id`),
  KEY `consents_decided_by_foreign` (`decided_by`),
  CONSTRAINT `consents_acr_id_foreign` FOREIGN KEY (`acr_id`) REFERENCES `acrs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `consents_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consents`
--

LOCK TABLES `consents` WRITE;
/*!40000 ALTER TABLE `consents` DISABLE KEYS */;
INSERT INTO `consents` VALUES (1,1,'given','web_portal','I consent to the deposit of my Academic Credit Record into the Academic Bank of Credit (ABC) for storage.','{\"en\": \"v1.0\", \"ms\": \"v1.0\"}',1,'2026-09-14 01:42:39'),(2,2,'given','web_portal','I consent to the deposit of my Academic Credit Record into ABC.','{\"en\": \"v1.0\"}',2,'2026-09-14 01:42:39'),(3,3,'given','web_portal','I consent to the deposit of my Academic Credit Record into the Academic Bank of Credit (ABC).','{\"en\": \"v1.0\", \"ms\": \"v1.0\"}',1,'2026-08-28 10:00:00'),(4,4,'given','web_portal','I consent to the deposit of my Academic Credit Record into the Academic Bank of Credit (ABC).','{\"en\": \"v1.0\", \"ms\": \"v1.0\"}',2,'2026-08-28 11:00:00'),(5,6,'given','web_portal','I consent to the deposit of my Academic Credit Record into the Academic Bank of Credit (ABC).','{\"en\": \"v1.0\", \"ms\": \"v1.0\"}',1,'2026-10-27 14:00:00'),(6,7,'given','web_portal','I consent to the deposit of my Academic Credit Record into the Academic Bank of Credit (ABC).','{\"en\": \"v1.0\", \"ms\": \"v1.0\"}',1,'2026-10-08 10:00:00'),(7,8,'given','web_portal','I consent to the deposit of my Academic Credit Record into the Academic Bank of Credit (ABC).','{\"en\": \"v1.0\", \"ms\": \"v1.0\"}',19,'2026-10-11 10:00:00'),(8,10,'given','web_portal','I consent to the deposit of my Academic Credit Record into the Academic Bank of Credit (ABC).','{\"en\": \"v1.0\", \"ms\": \"v1.0\"}',19,'2026-08-29 10:00:00');
/*!40000 ALTER TABLE `consents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `corrections`
--

DROP TABLE IF EXISTS `corrections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `corrections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `discrepancy_id` bigint unsigned DEFAULT NULL,
  `record_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_ref` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original` json NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_checked` tinyint(1) NOT NULL,
  `approver_id` bigint unsigned NOT NULL,
  `corrected_on` date NOT NULL,
  `participant_notified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `corrections_discrepancy_id_foreign` (`discrepancy_id`),
  KEY `corrections_approver_id_foreign` (`approver_id`),
  CONSTRAINT `corrections_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `corrections_discrepancy_id_foreign` FOREIGN KEY (`discrepancy_id`) REFERENCES `discrepancies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `corrections`
--

LOCK TABLES `corrections` WRITE;
/*!40000 ALTER TABLE `corrections` DISABLE KEYS */;
INSERT INTO `corrections` VALUES (1,1,'user_identity','USER-0002','{\"name\": \"Bee Tan\", \"passport\": \"A5948123\"}','Verified against physical passport photocopy presented at CODL counter.','Updated user profile and re-issued valid verifiable credential revision 2.',1,12,'2026-10-15',1,'2026-10-15 01:42:39'),(2,3,'credential','UTEM-MC-2026-0009','{\"status\": \"valid\"}','Employer sponsorship audit finding.','Confirmed credential revocation and blocked ACR generation for enrolment #15.',1,12,'2026-11-01',1,'2026-11-01 14:00:00');
/*!40000 ALTER TABLE `corrections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cqi_actions`
--

DROP TABLE IF EXISTS `cqi_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cqi_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cqi_record_id` bigint unsigned NOT NULL,
  `cqi_issue_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsible_id` bigint unsigned NOT NULL,
  `due_on` date NOT NULL,
  `success_indicator` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `affects` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_started',
  `effectiveness` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `before_evidence` text COLLATE utf8mb4_unicode_ci,
  `after_evidence` text COLLATE utf8mb4_unicode_ci,
  `evaluated_by` bigint unsigned DEFAULT NULL,
  `evaluated_at` timestamp NULL DEFAULT NULL,
  `closed_by` bigint unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `carried_from_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cqi_actions_cqi_record_id_foreign` (`cqi_record_id`),
  KEY `cqi_actions_cqi_issue_id_foreign` (`cqi_issue_id`),
  KEY `cqi_actions_responsible_id_foreign` (`responsible_id`),
  KEY `cqi_actions_evaluated_by_foreign` (`evaluated_by`),
  KEY `cqi_actions_closed_by_foreign` (`closed_by`),
  KEY `cqi_actions_carried_from_id_foreign` (`carried_from_id`),
  CONSTRAINT `cqi_actions_carried_from_id_foreign` FOREIGN KEY (`carried_from_id`) REFERENCES `cqi_actions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cqi_actions_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cqi_actions_cqi_issue_id_foreign` FOREIGN KEY (`cqi_issue_id`) REFERENCES `cqi_issues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cqi_actions_cqi_record_id_foreign` FOREIGN KEY (`cqi_record_id`) REFERENCES `cqi_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cqi_actions_evaluated_by_foreign` FOREIGN KEY (`evaluated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cqi_actions_responsible_id_foreign` FOREIGN KEY (`responsible_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cqi_actions`
--

LOCK TABLES `cqi_actions` WRITE;
/*!40000 ALTER TABLE `cqi_actions` DISABLE KEYS */;
INSERT INTO `cqi_actions` VALUES (1,1,1,'Migrate local lab exercises to cloud-hosted GitHub Codespaces / browser IDE.',6,'2027-01-03','Zero setup configuration errors reported in week 1 of 2027/1 cohort.',NULL,'in_progress',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-20 01:42:39',NULL),(2,3,2,'Added interactive Python visualizer notebook and 15-minute animated video for tree rotations.',6,'2026-10-01','90%+ pass rate on tree traversal questions in Cohort 2026/2.','materials','closed','effective','Cohort 2026/1 Q2 average 76%.','Interactive notebook deployed in Version 2 (MC-FTMK-001 v2).',10,'2026-10-04 10:00:00',10,'2026-10-04 10:00:00',NULL,'2026-08-28 10:00:00','2026-11-05 09:00:00'),(3,4,3,'Provision shared cloud GitLab runner for OWASP ZAP scans so learners do not run heavy containers locally.',7,'2026-12-15','Zero RAM-related lab failures in next SSDLC cohort.','platform','open',NULL,'1 learner failed due to local container crash.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-28 10:00:00','2026-11-05 09:00:00'),(4,5,4,'Added Gradle build.gradle.kts alongside Maven pom.xml in starter repository.',6,'2026-10-10','Both build tools pass CI checks.','materials','closed','effective','Maven-only starter template.','Dual build templates committed.',10,'2026-10-12 10:00:00',10,'2026-10-12 10:00:00',NULL,'2026-10-08 10:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `cqi_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cqi_issues`
--

DROP TABLE IF EXISTS `cqi_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cqi_issues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cqi_record_id` bigint unsigned NOT NULL,
  `evidence` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `root_cause` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `impact` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cqi_issues_cqi_record_id_foreign` (`cqi_record_id`),
  CONSTRAINT `cqi_issues_cqi_record_id_foreign` FOREIGN KEY (`cqi_record_id`) REFERENCES `cqi_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cqi_issues`
--

LOCK TABLES `cqi_issues` WRITE;
/*!40000 ALTER TABLE `cqi_issues` DISABLE KEYS */;
INSERT INTO `cqi_issues` VALUES (1,1,'Student feedback survey indicated Docker CLI setup on Windows Home caused configuration delays in week 2.','Inconsistent local virtualization environments among learners.','3 students delayed initial lab submission by 2 days.','medium','2026-09-17 01:42:39'),(2,3,'Two learners requested additional visual walkthroughs for Red-Black tree rotations.','Static slide diagrams lacked step-by-step pointer animation.','Lower average score on Q2 of Module 2 test.','low','2026-08-27 10:00:00'),(3,4,'One learner failed Module 2 DAST capstone (34%) due to local Docker RAM exhaustion.','OWASP ZAP headless scan container requires >4GB dedicated RAM.','Cohort completion rate dropped to 75%.','high','2026-10-27 10:00:00'),(4,5,'Learners asked for Maven & Gradle dual build templates in Module 2.','Starter repo only included Maven pom.xml.','Minor setup questions in Week 5 forum.','low','2026-10-07 10:00:00');
/*!40000 ALTER TABLE `cqi_issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cqi_records`
--

DROP TABLE IF EXISTS `cqi_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cqi_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `offering_id` bigint unsigned NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `indicators` json NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` bigint unsigned DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmations` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cqi_records_reference_unique` (`reference`),
  KEY `cqi_records_offering_id_foreign` (`offering_id`),
  KEY `cqi_records_owner_id_foreign` (`owner_id`),
  KEY `cqi_records_confirmed_by_foreign` (`confirmed_by`),
  CONSTRAINT `cqi_records_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cqi_records_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cqi_records_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cqi_records`
--

LOCK TABLES `cqi_records` WRITE;
/*!40000 ALTER TABLE `cqi_records` DISABLE KEYS */;
INSERT INTO `cqi_records` VALUES (1,'CQI-2026-001',1,6,'{\"rows\": [{\"key\": \"participation\", \"below\": false, \"label\": \"Active participation rate\", \"value\": 100, \"target\": 80}, {\"key\": \"completion\", \"below\": false, \"label\": \"Completion rate\", \"value\": 100, \"target\": 70}, {\"key\": \"pass\", \"below\": false, \"label\": \"Pass rate\", \"value\": 100, \"target\": 70}, {\"key\": \"issuance\", \"below\": false, \"label\": \"Credential issuance rate\", \"value\": 100, \"target\": 95}], \"median\": 84.5, \"average\": 84.5, \"modules\": [{\"name\": \"Containerization with Docker\", \"share\": 100}, {\"name\": \"Kubernetes Orchestration & CI/CD\", \"share\": 100}], \"feedback\": {\"areas\": {\"content\": 4.5, \"support\": 4.5, \"platform\": 4.5, \"relevance\": 4.5, \"activities\": 4.5, \"assessment\": 4.5}, \"count\": 2, \"detail\": {\"ease\": 4.5, \"admin\": 3.5, \"depth\": 3.5, \"clarity\": 4.5, \"content\": 4.5, \"support\": 4.5, \"fairness\": 4.5, \"lecturer\": 4.5, \"platform\": 4.5, \"practice\": 4.5, \"workload\": 3.5, \"recommend\": 4.5, \"relevance\": 4.5, \"activities\": 4.5, \"assessment\": 4.5, \"reliability\": 4.5, \"feedback_quality\": 3.5}, \"comments\": [\"Exceptional course. The Kubernetes deployment project directly improved my daily work at my company.\", \"Very comprehensive curriculum, appreciated the weekly live consultation clinics.\"]}, \"outcomes\": [{\"code\": \"LO1\", \"text\": \"Package cloud-ready container images following OCI specifications.\", \"share\": 100}, {\"code\": \"LO2\", \"text\": \"Deploy resilient microservices workloads onto Kubernetes clusters.\", \"share\": 100}], \"participants\": 2}','confirmed','2026-09-20 01:42:39',10,'2026-09-25 01:42:39','{\"board_notified\": true, \"domains_checked\": true, \"actions_assigned\": true}','2026-09-15 01:42:39','2026-11-04 01:42:39'),(2,'CQI-2026-0002',2,6,'{\"rows\": [{\"key\": \"participation\", \"below\": false, \"label\": \"Active participation rate\", \"value\": 100, \"target\": 80}, {\"key\": \"completion\", \"below\": true, \"label\": \"Completion rate\", \"value\": 0, \"target\": 70}, {\"key\": \"pass\", \"below\": true, \"label\": \"Pass rate\", \"value\": 0, \"target\": 70}, {\"key\": \"issuance\", \"below\": false, \"label\": \"Credential issuance rate\", \"value\": null, \"target\": 95}], \"median\": 22.2, \"average\": 26.6, \"modules\": [{\"name\": \"Arrays and Linked Lists\", \"share\": 20}, {\"name\": \"Trees and Graphs\", \"share\": 0}], \"feedback\": {\"areas\": {\"content\": null, \"support\": null, \"platform\": null, \"relevance\": null, \"activities\": null, \"assessment\": null}, \"count\": 0, \"detail\": {\"ease\": null, \"admin\": null, \"depth\": null, \"clarity\": null, \"content\": null, \"support\": null, \"fairness\": null, \"lecturer\": null, \"platform\": null, \"practice\": null, \"workload\": null, \"recommend\": null, \"relevance\": null, \"activities\": null, \"assessment\": null, \"reliability\": null, \"feedback_quality\": null}, \"comments\": []}, \"outcomes\": [{\"code\": \"LO1\", \"text\": \"Implement contiguous and linked data structures in software.\", \"share\": 40}, {\"code\": \"LO2\", \"text\": \"Apply tree traversal and graph traversal algorithms to solve complex searching problems.\", \"share\": 0}, {\"code\": \"LO3\", \"text\": \"Evaluate asymptotic time and memory complexity of computer algorithms.\", \"share\": 0}], \"participants\": 5}','draft',NULL,NULL,NULL,NULL,'2026-11-04 02:43:03','2026-11-04 02:43:03'),(3,'CQI-2026-0003',12,6,'{\"rows\": [{\"key\": \"participation\", \"below\": false, \"label\": \"Active participation rate\", \"value\": 100, \"target\": 80}, {\"key\": \"completion\", \"below\": false, \"label\": \"Completion rate\", \"value\": 100, \"target\": 70}, {\"key\": \"pass\", \"below\": false, \"label\": \"Pass rate\", \"value\": 100, \"target\": 70}, {\"key\": \"issuance\", \"below\": false, \"label\": \"Credential issuance rate\", \"value\": 100, \"target\": 95}], \"median\": 86.1, \"average\": 85.6, \"modules\": [{\"name\": \"Arrays and Linked Lists\", \"share\": 100}, {\"name\": \"Trees and Graphs\", \"share\": 100}], \"feedback\": {\"areas\": {\"content\": 4.5, \"support\": 4.5, \"platform\": 4.5, \"relevance\": 4.5, \"activities\": 4.5, \"assessment\": 4.5}, \"count\": 4, \"detail\": {\"ease\": 4.5, \"admin\": 3.5, \"depth\": 3.5, \"clarity\": 4.5, \"content\": 4.5, \"support\": 4.5, \"fairness\": 4.5, \"lecturer\": 4.5, \"platform\": 4.5, \"practice\": 4.5, \"workload\": 3.5, \"recommend\": 4.5, \"relevance\": 4.5, \"activities\": 4.5, \"assessment\": 4.5, \"reliability\": 4.5, \"feedback_quality\": 3.5}, \"comments\": [\"Clear explanation of tree balancing and graph heuristics; lab test bench was very helpful.\", \"Great course structure, though week 6 graph assignment was challenging.\", \"Well-paced video lectures and responsive Teams clinic.\", \"Directly prepared me for technical coding interviews.\"]}, \"outcomes\": [{\"code\": \"LO1\", \"text\": \"Implement contiguous and linked data structures in software.\", \"share\": 100}, {\"code\": \"LO2\", \"text\": \"Apply tree traversal and graph traversal algorithms to solve complex searching problems.\", \"share\": 100}, {\"code\": \"LO3\", \"text\": \"Evaluate asymptotic time and memory complexity of computer algorithms.\", \"share\": 100}], \"participants\": 4}','confirmed','2026-08-28 10:00:00',10,'2026-09-02 10:00:00','{\"board_notified\": true, \"domains_checked\": true, \"actions_assigned\": true}','2026-08-26 10:00:00','2026-11-05 09:00:00'),(4,'CQI-2026-0004',13,7,'{\"rows\": [{\"key\": \"participation\", \"below\": false, \"label\": \"Active participation rate\", \"value\": 100, \"target\": 80}, {\"key\": \"completion\", \"below\": false, \"label\": \"Completion rate\", \"value\": 75, \"target\": 70}, {\"key\": \"pass\", \"below\": false, \"label\": \"Pass rate\", \"value\": 75, \"target\": 70}, {\"key\": \"issuance\", \"below\": false, \"label\": \"Credential issuance rate\", \"value\": 100, \"target\": 95}], \"median\": 83, \"average\": 75.5, \"modules\": [{\"name\": \"Threat Modelling & OWASP Top 10 Mitigation\", \"share\": 75}, {\"name\": \"Automated SAST/DAST Security Pipelines\", \"share\": 50}], \"feedback\": {\"areas\": {\"content\": 4.3, \"support\": 4.3, \"platform\": 4.3, \"relevance\": 4.3, \"activities\": 4.3, \"assessment\": 4.3}, \"count\": 3, \"detail\": {\"ease\": 4.3, \"admin\": 3.7, \"depth\": 3.7, \"clarity\": 4.3, \"content\": 4.3, \"support\": 4.3, \"fairness\": 4.3, \"lecturer\": 4.3, \"platform\": 4.3, \"practice\": 4.3, \"workload\": 3.7, \"recommend\": 4.3, \"relevance\": 4.3, \"activities\": 4.3, \"assessment\": 4.3, \"reliability\": 4.3, \"feedback_quality\": 3.7}, \"comments\": [\"Hands-on GitLab SAST/DAST pipeline labs are industry grade.\", \"STRIDE threat modelling templates were immediately useful at work.\", \"Module 2 DAST container setup required more memory than my laptop had.\"]}, \"outcomes\": [{\"code\": \"LO1\", \"text\": \"Perform STRIDE threat modelling and remediate OWASP Top 10 vulnerabilities.\", \"share\": 100}, {\"code\": \"LO2\", \"text\": \"Integrate automated SAST and DAST security gates into CI/CD pipelines.\", \"share\": 75}], \"participants\": 4}','submitted','2026-10-28 10:00:00',NULL,NULL,'{\"board_notified\": true, \"domains_checked\": true, \"actions_assigned\": true}','2026-10-26 10:00:00','2026-11-05 09:00:00'),(5,'CQI-2026-0005',14,6,'{\"rows\": [{\"key\": \"participation\", \"below\": false, \"label\": \"Active participation rate\", \"value\": 100, \"target\": 80}, {\"key\": \"completion\", \"below\": false, \"label\": \"Completion rate\", \"value\": 100, \"target\": 70}, {\"key\": \"pass\", \"below\": false, \"label\": \"Pass rate\", \"value\": 100, \"target\": 70}, {\"key\": \"issuance\", \"below\": true, \"label\": \"Credential issuance rate\", \"value\": 50, \"target\": 95}], \"median\": 85.5, \"average\": 85.5, \"modules\": [{\"name\": \"Encapsulation, Inheritance & Polymorphism in Java\", \"share\": 0}, {\"name\": \"SOLID Principles, Design Patterns & Unit Testing\", \"share\": 0}], \"feedback\": {\"areas\": {\"content\": 5, \"support\": 5, \"platform\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5}, \"count\": 1, \"detail\": {\"ease\": 5, \"admin\": 4, \"depth\": 4, \"clarity\": 5, \"content\": 5, \"support\": 5, \"fairness\": 5, \"lecturer\": 5, \"platform\": 5, \"practice\": 5, \"workload\": 4, \"recommend\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5, \"reliability\": 5, \"feedback_quality\": 4}, \"comments\": [\"SOLID refactoring exercises really sharpened my Java architecture skills.\"]}, \"outcomes\": [{\"code\": \"LO1\", \"text\": \"Design modular object-oriented software adhering to SOLID principles.\", \"share\": 100}, {\"code\": \"LO2\", \"text\": \"Verify software reliability using automated JUnit test suites.\", \"share\": 100}], \"participants\": 2}','confirmed','2026-10-08 10:00:00',10,'2026-10-12 10:00:00','{\"board_notified\": true, \"domains_checked\": true, \"actions_assigned\": true}','2026-10-06 10:00:00','2026-11-05 09:00:00'),(6,'CQI-2026-0006',15,24,'{\"rows\": [{\"key\": \"participation\", \"below\": false, \"label\": \"Active participation rate\", \"value\": 100, \"target\": 80}, {\"key\": \"completion\", \"below\": false, \"label\": \"Completion rate\", \"value\": 100, \"target\": 70}, {\"key\": \"pass\", \"below\": false, \"label\": \"Pass rate\", \"value\": 100, \"target\": 70}, {\"key\": \"issuance\", \"below\": false, \"label\": \"Credential issuance rate\", \"value\": 100, \"target\": 95}], \"median\": 87, \"average\": 87, \"modules\": [{\"name\": \"Technology Opportunity Recognition & Lean Canvas\", \"share\": 0}, {\"name\": \"Venture Valuation, IP Strategy & Investor Pitch\", \"share\": 0}], \"feedback\": {\"areas\": {\"content\": 5, \"support\": 5, \"platform\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5}, \"count\": 1, \"detail\": {\"ease\": 5, \"admin\": 4, \"depth\": 4, \"clarity\": 5, \"content\": 5, \"support\": 5, \"fairness\": 5, \"lecturer\": 5, \"platform\": 5, \"practice\": 5, \"workload\": 4, \"recommend\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5, \"reliability\": 5, \"feedback_quality\": 4}, \"comments\": [\"Executive mentoring and venture valuation rubric were top notch.\"]}, \"outcomes\": [{\"code\": \"LO1\", \"text\": \"Validate technology business models and formulate go-to-market strategies.\", \"share\": 100}, {\"code\": \"LO2\", \"text\": \"Construct financial projections and defend an investment-ready venture pitch.\", \"share\": 100}], \"participants\": 1}','confirmed','2026-10-10 10:00:00',25,'2026-10-14 10:00:00','{\"board_notified\": true, \"domains_checked\": true, \"actions_assigned\": true}','2026-10-09 10:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `cqi_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credential_issue_failures`
--

DROP TABLE IF EXISTS `credential_issue_failures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credential_issue_failures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enrolment_id` bigint unsigned NOT NULL,
  `reasons` json NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credential_issue_failures_enrolment_id_unique` (`enrolment_id`),
  CONSTRAINT `credential_issue_failures_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credential_issue_failures`
--

LOCK TABLES `credential_issue_failures` WRITE;
/*!40000 ALTER TABLE `credential_issue_failures` DISABLE KEYS */;
INSERT INTO `credential_issue_failures` VALUES (1,6,'[\"Completion is not confirmed.\", \"Some results are not verified.\"]','2026-11-05 08:32:12'),(2,16,'[\"Final completion status is not_completed (score 46.0% < 50.0%)\"]','2026-10-26 10:00:00'),(3,23,'[\"Enrolment is currently on hold due to cohort suspension #1\"]','2026-10-21 10:00:00');
/*!40000 ALTER TABLE `credential_issue_failures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credential_revisions`
--

DROP TABLE IF EXISTS `credential_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credential_revisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `credential_id` bigint unsigned NOT NULL,
  `revision` smallint unsigned NOT NULL,
  `event` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` json NOT NULL,
  `jwt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `credential_revisions_credential_id_foreign` (`credential_id`),
  KEY `credential_revisions_by_user_id_foreign` (`by_user_id`),
  CONSTRAINT `credential_revisions_by_user_id_foreign` FOREIGN KEY (`by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `credential_revisions_credential_id_foreign` FOREIGN KEY (`credential_id`) REFERENCES `credentials` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credential_revisions`
--

LOCK TABLES `credential_revisions` WRITE;
/*!40000 ALTER TABLE `credential_revisions` DISABLE KEYS */;
INSERT INTO `credential_revisions` VALUES (1,2,2,'corrected','{\"code\": \"CND03-9P4RQ\", \"name\": {\"en\": \"Cloud Native Development\", \"ms\": \"Pembangunan Asal Awan\"}, \"claims\": {\"qv\": true, \"mqf\": 4, \"credit\": \"2.0\", \"qv_until\": \"2026-12-15\"}, \"holder\": \"Bee Tan\", \"issued\": \"2026-10-15T00:00:00Z\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0002-R1\", \"stages\": null, \"category\": \"SAMC\", \"outcomes\": {\"en\": [\"Package cloud-ready container images.\", \"Deploy microservices to Kubernetes.\"], \"ms\": [\"Membungkus imej kontena.\", \"Menyebarkan mikroperkhidmatan.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This credential represents standalone micro-credential completion.\", \"ms\": \"Kredensial ini mewakili penamatan mikro-kredensial.\"}, \"assessment_basis\": \"Verified marks: Docker Lab (50%), Kubernetes Project (50%). Completion confirmed by the deputy dean.\"}','eyJhbGci...corrected_token','Legal name on national identity card verified by CODL registry.',12,'2026-10-15 01:42:39'),(2,10,2,'revoked','{\"code\": \"SEC03-2P6RW\", \"name\": {\"en\": \"Secure Software Development Lifecycle (SSDLC)\", \"ms\": \"Kitar Hayat Pembangunan Perisian Selamat (SSDLC)\"}, \"claims\": {\"qv\": true, \"mqf\": 6, \"credit\": \"2\", \"qv_until\": \"2029-08-15\"}, \"holder\": \"TAN LI\", \"issued\": \"2026-10-26T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0009\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"SAMC\", \"outcomes\": {\"en\": [\"Perform STRIDE threat modelling and remediate OWASP Top 10 vulnerabilities.\", \"Integrate automated SAST and DAST security gates into CI/CD pipelines.\"], \"ms\": [\"Melaksanakan pemodelan ancaman STRIDE dan memulihkan kelemahan OWASP Top 10.\", \"Mengintegrasikan gerbang keselamatan SAST dan DAST automatik ke dalam CI/CD.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This SAMC is Quality-Verified. Credit 2, MQF level 6, valid until 2029-08-15.\", \"ms\": \"SAMC ini telah disahkan melalui Quality Verification. Kredit 2, tahap MQF 6, sah hingga 2029-08-15.\"}, \"assessment_basis\": \"Verified marks: STRIDE Threat Model & OWASP Remediation Lab (50%), Automated DevSecOps Pipeline Capstone (50%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L1NFQzAzLTJQNlJXIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjY1MDU0OTk2OWMzYzA1NmMiLCJuYmYiOjE3OTMwMDg4MDAsImlhdCI6MTc5MzkyNDk2OCwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9TRUMwMy0yUDZSVyIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJTZWN1cmUgU29mdHdhcmUgRGV2ZWxvcG1lbnQgTGlmZWN5Y2xlIChTU0RMQykiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTEwLTI2VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IlRBTiBMSSJ9XSwiYWNoaWV2ZW1lbnQiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0L3B1YmxpYy92ZXJpZnkvU0VDMDMtMlA2UlcjYWNoaWV2ZW1lbnQiLCJ0eXBlIjpbIkFjaGlldmVtZW50Il0sImFjaGlldmVtZW50VHlwZSI6IkNlcnRpZmljYXRlIiwibmFtZSI6IlNlY3VyZSBTb2Z0d2FyZSBEZXZlbG9wbWVudCBMaWZlY3ljbGUgKFNTRExDKSIsImRlc2NyaXB0aW9uIjoiVGhpcyBTQU1DIGlzIFF1YWxpdHktVmVyaWZpZWQuIENyZWRpdCAyLCBNUUYgbGV2ZWwgNiwgdmFsaWQgdW50aWwgMjAyOS0wOC0xNS4iLCJjcml0ZXJpYSI6eyJuYXJyYXRpdmUiOiJWZXJpZmllZCBtYXJrczogU1RSSURFIFRocmVhdCBNb2RlbCAmIE9XQVNQIFJlbWVkaWF0aW9uIExhYiAoNTAlKSwgQXV0b21hdGVkIERldlNlY09wcyBQaXBlbGluZSBDYXBzdG9uZSAoNTAlKS4gQ29tcGxldGlvbiBjb25maXJtZWQgYnkgdGhlIGRlcHV0eSBkZWFuLiJ9LCJodW1hbkNvZGUiOiJVVEVNLU1DLTIwMjYtMDAwOSIsInRhZyI6WyJQZXJmb3JtIFNUUklERSB0aHJlYXQgbW9kZWxsaW5nIGFuZCByZW1lZGlhdGUgT1dBU1AgVG9wIDEwIHZ1bG5lcmFiaWxpdGllcy4iLCJJbnRlZ3JhdGUgYXV0b21hdGVkIFNBU1QgYW5kIERBU1Qgc2VjdXJpdHkgZ2F0ZXMgaW50byBDSS9DRCBwaXBlbGluZXMuIl0sImNyZWRpdHNBdmFpbGFibGUiOjJ9fSwibWNtcyI6eyJjcmVkZW50aWFsX251bWJlciI6IlVURU0tTUMtMjAyNi0wMDA5IiwiY2F0ZWdvcnkiOiJTQU1DIiwic2x0X2hvdXJzIjo4MCwiY2xhaW1zIjp7ImNyZWRpdCI6IjIiLCJtcWYiOjYsInF2Ijp0cnVlLCJxdl91bnRpbCI6IjIwMjktMDgtMTUifSwibXMiOnsibmFtZSI6IktpdGFyIEhheWF0IFBlbWJhbmd1bmFuIFBlcmlzaWFuIFNlbGFtYXQgKFNTRExDKSIsInN0YXRlbWVudCI6IlNBTUMgaW5pIHRlbGFoIGRpc2Foa2FuIG1lbGFsdWkgUXVhbGl0eSBWZXJpZmljYXRpb24uIEtyZWRpdCAyLCB0YWhhcCBNUUYgNiwgc2FoIGhpbmdnYSAyMDI5LTA4LTE1LiJ9fX19.WE59TAqupLYIhVcS7W5KgoezyKd3-0VvbJtejqFOuwcHeQwTczL9Yqd37qWwynfuFKhuqqCDv3TcDKtsVtY7CA','Revoked following post-issuance employer audit of external sponsor declaration.',12,'2026-11-01 10:00:00');
/*!40000 ALTER TABLE `credential_revisions` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`mcms`@`%`*/ /*!50003 TRIGGER `credential_revisions_keep_upd` BEFORE UPDATE ON `credential_revisions` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'credentials and their revisions are never deleted';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`mcms`@`%`*/ /*!50003 TRIGGER `credential_revisions_keep_del` BEFORE DELETE ON `credential_revisions` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'credentials and their revisions are never deleted';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `credentials`
--

DROP TABLE IF EXISTS `credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credentials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_code` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enrolment_id` bigint unsigned NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'valid',
  `revision` smallint unsigned NOT NULL DEFAULT '1',
  `content` json NOT NULL,
  `jwt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `kid` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `replaces_id` bigint unsigned DEFAULT NULL,
  `replaced_by_id` bigint unsigned DEFAULT NULL,
  `issued_at` timestamp NOT NULL,
  `status_changed_at` timestamp NULL DEFAULT NULL,
  `status_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credentials_number_unique` (`number`),
  UNIQUE KEY `credentials_public_code_unique` (`public_code`),
  KEY `credentials_enrolment_id_foreign` (`enrolment_id`),
  KEY `credentials_replaces_id_foreign` (`replaces_id`),
  KEY `credentials_replaced_by_id_foreign` (`replaced_by_id`),
  CONSTRAINT `credentials_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `credentials_replaced_by_id_foreign` FOREIGN KEY (`replaced_by_id`) REFERENCES `credentials` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `credentials_replaces_id_foreign` FOREIGN KEY (`replaces_id`) REFERENCES `credentials` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credentials`
--

LOCK TABLES `credentials` WRITE;
/*!40000 ALTER TABLE `credentials` DISABLE KEYS */;
INSERT INTO `credentials` VALUES (1,'UTEM-MC-2026-0001','CND01-7K9MJ',1,'valid',1,'{\"code\": \"CND01-7K9MJ\", \"name\": {\"en\": \"Cloud Native Development\", \"ms\": \"Pembangunan Asal Awan\"}, \"claims\": {\"qv\": true, \"mqf\": 4, \"credit\": \"2\", \"qv_until\": \"2026-12-15\"}, \"holder\": \"AMIR HAKIMI BIN ZULKIFLI\", \"issued\": \"2026-09-15T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0001\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"SAMC\", \"outcomes\": {\"en\": [\"Package cloud-ready container images following OCI specifications.\", \"Deploy resilient microservices workloads onto Kubernetes clusters.\"], \"ms\": [\"Membungkus imej kontena sedia awan mengikut spesifikasi OCI.\", \"Menyebarkan beban kerja mikroperkhidmatan ke kluster Kubernetes.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This SAMC is Quality-Verified. Credit 2, MQF level 4, valid until 2026-12-15.\", \"ms\": \"SAMC ini telah disahkan melalui Quality Verification. Kredit 2, tahap MQF 4, sah hingga 2026-12-15.\"}, \"assessment_basis\": \"Verified marks: Docker Containerization Practical Lab (50%), Kubernetes Production Cluster Project (50%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L0NORDAxLTdLOU1KIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjg3NDdiMDU2MzUzMGI3YTciLCJuYmYiOjE3ODk0NjY0MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9DTkQwMS03SzlNSiIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJDbG91ZCBOYXRpdmUgRGV2ZWxvcG1lbnQiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTA5LTE1VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IkFNSVIgSEFLSU1JIEJJTiBaVUxLSUZMSSJ9XSwiYWNoaWV2ZW1lbnQiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0L3B1YmxpYy92ZXJpZnkvQ05EMDEtN0s5TUojYWNoaWV2ZW1lbnQiLCJ0eXBlIjpbIkFjaGlldmVtZW50Il0sImFjaGlldmVtZW50VHlwZSI6IkNlcnRpZmljYXRlIiwibmFtZSI6IkNsb3VkIE5hdGl2ZSBEZXZlbG9wbWVudCIsImRlc2NyaXB0aW9uIjoiVGhpcyBTQU1DIGlzIFF1YWxpdHktVmVyaWZpZWQuIENyZWRpdCAyLCBNUUYgbGV2ZWwgNCwgdmFsaWQgdW50aWwgMjAyNi0xMi0xNS4iLCJjcml0ZXJpYSI6eyJuYXJyYXRpdmUiOiJWZXJpZmllZCBtYXJrczogRG9ja2VyIENvbnRhaW5lcml6YXRpb24gUHJhY3RpY2FsIExhYiAoNTAlKSwgS3ViZXJuZXRlcyBQcm9kdWN0aW9uIENsdXN0ZXIgUHJvamVjdCAoNTAlKS4gQ29tcGxldGlvbiBjb25maXJtZWQgYnkgdGhlIGRlcHV0eSBkZWFuLiJ9LCJodW1hbkNvZGUiOiJVVEVNLU1DLTIwMjYtMDAwMSIsInRhZyI6WyJQYWNrYWdlIGNsb3VkLXJlYWR5IGNvbnRhaW5lciBpbWFnZXMgZm9sbG93aW5nIE9DSSBzcGVjaWZpY2F0aW9ucy4iLCJEZXBsb3kgcmVzaWxpZW50IG1pY3Jvc2VydmljZXMgd29ya2xvYWRzIG9udG8gS3ViZXJuZXRlcyBjbHVzdGVycy4iXSwiY3JlZGl0c0F2YWlsYWJsZSI6Mn19LCJtY21zIjp7ImNyZWRlbnRpYWxfbnVtYmVyIjoiVVRFTS1NQy0yMDI2LTAwMDEiLCJjYXRlZ29yeSI6IlNBTUMiLCJzbHRfaG91cnMiOjgwLCJjbGFpbXMiOnsiY3JlZGl0IjoiMiIsIm1xZiI6NCwicXYiOnRydWUsInF2X3VudGlsIjoiMjAyNi0xMi0xNSJ9LCJtcyI6eyJuYW1lIjoiUGVtYmFuZ3VuYW4gQXNhbCBBd2FuIiwic3RhdGVtZW50IjoiU0FNQyBpbmkgdGVsYWggZGlzYWhrYW4gbWVsYWx1aSBRdWFsaXR5IFZlcmlmaWNhdGlvbi4gS3JlZGl0IDIsIHRhaGFwIE1RRiA0LCBzYWggaGluZ2dhIDIwMjYtMTItMTUuIn19fX0.kgoy--orgyJ47J8TmAvC7Ia2SVKZEiN43DWG-s6zG97WWgbGdntOr3H4ZtTbuEC7oqTMg4V_sF3sKEct4lx1AA','did:utem:codl:vc-2026-01',NULL,NULL,'2026-09-15 10:00:00',NULL,NULL,'2026-09-15 10:00:00','2026-11-05 09:00:00'),(2,'UTEM-MC-2026-0002','CND02-8M3NP',2,'corrected',1,'{\"code\": \"CND02-8M3NP\", \"name\": {\"en\": \"Cloud Native Development\", \"ms\": \"Pembangunan Asal Awan\"}, \"claims\": {\"qv\": true, \"mqf\": 4, \"credit\": \"2\", \"qv_until\": \"2026-12-15\"}, \"holder\": \"Bee Tan\", \"issued\": \"2026-09-15T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0002\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"SAMC\", \"outcomes\": {\"en\": [\"Package cloud-ready container images following OCI specifications.\", \"Deploy resilient microservices workloads onto Kubernetes clusters.\"], \"ms\": [\"Membungkus imej kontena sedia awan mengikut spesifikasi OCI.\", \"Menyebarkan beban kerja mikroperkhidmatan ke kluster Kubernetes.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This SAMC is Quality-Verified. Credit 2, MQF level 4, valid until 2026-12-15.\", \"ms\": \"SAMC ini telah disahkan melalui Quality Verification. Kredit 2, tahap MQF 4, sah hingga 2026-12-15.\"}, \"assessment_basis\": \"Verified marks: Docker Containerization Practical Lab (50%), Kubernetes Production Cluster Project (50%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L0NORDAyLThNM05QIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjdjODNiMDJjNDE3MWMzNzYiLCJuYmYiOjE3ODk0NjY0MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9DTkQwMi04TTNOUCIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJDbG91ZCBOYXRpdmUgRGV2ZWxvcG1lbnQiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTA5LTE1VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IkJlZSBUYW4ifV0sImFjaGlldmVtZW50Ijp7ImlkIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L0NORDAyLThNM05QI2FjaGlldmVtZW50IiwidHlwZSI6WyJBY2hpZXZlbWVudCJdLCJhY2hpZXZlbWVudFR5cGUiOiJDZXJ0aWZpY2F0ZSIsIm5hbWUiOiJDbG91ZCBOYXRpdmUgRGV2ZWxvcG1lbnQiLCJkZXNjcmlwdGlvbiI6IlRoaXMgU0FNQyBpcyBRdWFsaXR5LVZlcmlmaWVkLiBDcmVkaXQgMiwgTVFGIGxldmVsIDQsIHZhbGlkIHVudGlsIDIwMjYtMTItMTUuIiwiY3JpdGVyaWEiOnsibmFycmF0aXZlIjoiVmVyaWZpZWQgbWFya3M6IERvY2tlciBDb250YWluZXJpemF0aW9uIFByYWN0aWNhbCBMYWIgKDUwJSksIEt1YmVybmV0ZXMgUHJvZHVjdGlvbiBDbHVzdGVyIFByb2plY3QgKDUwJSkuIENvbXBsZXRpb24gY29uZmlybWVkIGJ5IHRoZSBkZXB1dHkgZGVhbi4ifSwiaHVtYW5Db2RlIjoiVVRFTS1NQy0yMDI2LTAwMDIiLCJ0YWciOlsiUGFja2FnZSBjbG91ZC1yZWFkeSBjb250YWluZXIgaW1hZ2VzIGZvbGxvd2luZyBPQ0kgc3BlY2lmaWNhdGlvbnMuIiwiRGVwbG95IHJlc2lsaWVudCBtaWNyb3NlcnZpY2VzIHdvcmtsb2FkcyBvbnRvIEt1YmVybmV0ZXMgY2x1c3RlcnMuIl0sImNyZWRpdHNBdmFpbGFibGUiOjJ9fSwibWNtcyI6eyJjcmVkZW50aWFsX251bWJlciI6IlVURU0tTUMtMjAyNi0wMDAyIiwiY2F0ZWdvcnkiOiJTQU1DIiwic2x0X2hvdXJzIjo4MCwiY2xhaW1zIjp7ImNyZWRpdCI6IjIiLCJtcWYiOjQsInF2Ijp0cnVlLCJxdl91bnRpbCI6IjIwMjYtMTItMTUifSwibXMiOnsibmFtZSI6IlBlbWJhbmd1bmFuIEFzYWwgQXdhbiIsInN0YXRlbWVudCI6IlNBTUMgaW5pIHRlbGFoIGRpc2Foa2FuIG1lbGFsdWkgUXVhbGl0eSBWZXJpZmljYXRpb24uIEtyZWRpdCAyLCB0YWhhcCBNUUYgNCwgc2FoIGhpbmdnYSAyMDI2LTEyLTE1LiJ9fX19.LgNG8MKqjBRVzsOcIRL7asQrEsP_mxtEHmVjvnlXceqsi8PIwsOxMKRIok7-ijYxcBHzIaZ55xBYDdTulswLAQ','did:utem:codl:vc-2026-01',NULL,3,'2026-09-15 10:00:00','2026-10-15 10:00:00','Corrected typographical error in national identification number.','2026-09-15 10:00:00','2026-11-05 09:00:00'),(3,'UTEM-MC-2026-0002-R1','CND03-9P4RQ',2,'valid',2,'{\"code\": \"CND03-9P4RQ\", \"name\": {\"en\": \"Cloud Native Development\", \"ms\": \"Pembangunan Asal Awan\"}, \"claims\": {\"qv\": true, \"mqf\": 4, \"credit\": \"2\", \"qv_until\": \"2026-12-15\"}, \"holder\": \"TAN BEE LING\", \"issued\": \"2026-10-15T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0002-R1\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"SAMC\", \"outcomes\": {\"en\": [\"Package cloud-ready container images following OCI specifications.\", \"Deploy resilient microservices workloads onto Kubernetes clusters.\"], \"ms\": [\"Membungkus imej kontena sedia awan mengikut spesifikasi OCI.\", \"Menyebarkan beban kerja mikroperkhidmatan ke kluster Kubernetes.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This SAMC is Quality-Verified. Credit 2, MQF level 4, valid until 2026-12-15.\", \"ms\": \"SAMC ini telah disahkan melalui Quality Verification. Kredit 2, tahap MQF 4, sah hingga 2026-12-15.\"}, \"assessment_basis\": \"Verified marks: Docker Containerization Practical Lab (50%), Kubernetes Production Cluster Project (50%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L0NORDAzLTlQNFJRIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjgxMjRiYjA2OWE4OGM2YzkiLCJuYmYiOjE3OTIwNTg0MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9DTkQwMy05UDRSUSIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJDbG91ZCBOYXRpdmUgRGV2ZWxvcG1lbnQiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTEwLTE1VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IlRBTiBCRUUgTElORyJ9XSwiYWNoaWV2ZW1lbnQiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0L3B1YmxpYy92ZXJpZnkvQ05EMDMtOVA0UlEjYWNoaWV2ZW1lbnQiLCJ0eXBlIjpbIkFjaGlldmVtZW50Il0sImFjaGlldmVtZW50VHlwZSI6IkNlcnRpZmljYXRlIiwibmFtZSI6IkNsb3VkIE5hdGl2ZSBEZXZlbG9wbWVudCIsImRlc2NyaXB0aW9uIjoiVGhpcyBTQU1DIGlzIFF1YWxpdHktVmVyaWZpZWQuIENyZWRpdCAyLCBNUUYgbGV2ZWwgNCwgdmFsaWQgdW50aWwgMjAyNi0xMi0xNS4iLCJjcml0ZXJpYSI6eyJuYXJyYXRpdmUiOiJWZXJpZmllZCBtYXJrczogRG9ja2VyIENvbnRhaW5lcml6YXRpb24gUHJhY3RpY2FsIExhYiAoNTAlKSwgS3ViZXJuZXRlcyBQcm9kdWN0aW9uIENsdXN0ZXIgUHJvamVjdCAoNTAlKS4gQ29tcGxldGlvbiBjb25maXJtZWQgYnkgdGhlIGRlcHV0eSBkZWFuLiJ9LCJodW1hbkNvZGUiOiJVVEVNLU1DLTIwMjYtMDAwMi1SMSIsInRhZyI6WyJQYWNrYWdlIGNsb3VkLXJlYWR5IGNvbnRhaW5lciBpbWFnZXMgZm9sbG93aW5nIE9DSSBzcGVjaWZpY2F0aW9ucy4iLCJEZXBsb3kgcmVzaWxpZW50IG1pY3Jvc2VydmljZXMgd29ya2xvYWRzIG9udG8gS3ViZXJuZXRlcyBjbHVzdGVycy4iXSwiY3JlZGl0c0F2YWlsYWJsZSI6Mn19LCJtY21zIjp7ImNyZWRlbnRpYWxfbnVtYmVyIjoiVVRFTS1NQy0yMDI2LTAwMDItUjEiLCJjYXRlZ29yeSI6IlNBTUMiLCJzbHRfaG91cnMiOjgwLCJjbGFpbXMiOnsiY3JlZGl0IjoiMiIsIm1xZiI6NCwicXYiOnRydWUsInF2X3VudGlsIjoiMjAyNi0xMi0xNSJ9LCJtcyI6eyJuYW1lIjoiUGVtYmFuZ3VuYW4gQXNhbCBBd2FuIiwic3RhdGVtZW50IjoiU0FNQyBpbmkgdGVsYWggZGlzYWhrYW4gbWVsYWx1aSBRdWFsaXR5IFZlcmlmaWNhdGlvbi4gS3JlZGl0IDIsIHRhaGFwIE1RRiA0LCBzYWggaGluZ2dhIDIwMjYtMTItMTUuIn19fX0.Mhu7tD8DB3_-c1BAI-tREzInPIblKai9azJ1QJYGm05usKpgkKQwZMKUUzzmxvNIjnutP1EEcpnGHdGQ5y32CA','did:utem:codl:vc-2026-01',2,NULL,'2026-10-15 10:00:00',NULL,NULL,'2026-10-15 10:00:00','2026-11-05 09:00:00'),(4,'UTEM-MC-2026-0003','DST01-4H8KP',9,'valid',1,'{\"code\": \"DST01-4H8KP\", \"name\": {\"en\": \"Data Structures\", \"ms\": \"Struktur Data\"}, \"claims\": {\"qv\": false, \"mqf\": null, \"credit\": 3, \"qv_until\": null}, \"holder\": \"AMIR HAKIMI BIN ZULKIFLI\", \"issued\": \"2026-08-26T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0003\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"UMC\", \"outcomes\": {\"en\": [\"Implement contiguous and linked data structures in software.\", \"Apply tree traversal and graph traversal algorithms to solve complex searching problems.\", \"Evaluate asymptotic time and memory complexity of computer algorithms.\"], \"ms\": [\"Melaksanakan struktur data bersebelahan dan berpaut dalam perisian.\", \"Mengaplikasikan algoritma lintasan pokok dan graf untuk menyelesaikan masalah carian.\", \"Menilai kerumitan masa dan ingatan asimtotik algoritma komputer.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This is an Unbundled Micro-Credential (UMC) from BITS 1123 Data Structures and Algorithms. Completing the whole UMC carries the credit of the parent course. A module or a badge on its own is not completion and not credit.\", \"ms\": \"Ini ialah Unbundled Micro-Credential (UMC) daripada BITS 1123 Data Structures and Algorithms. Menamatkan keseluruhan UMC membawa kredit kursus induk. Satu modul atau satu lencana sahaja bukan penamatan dan bukan kredit.\"}, \"assessment_basis\": \"Verified marks: Module 1 Test: Linear Data Structures (30%), Module 2 Test: Non-Linear Structures (30%), Comprehensive Capstone Coding Project (40%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L0RTVDAxLTRIOEtQIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjA5NzU3NGYwNDhmZGU1MDMiLCJuYmYiOjE3ODc3Mzg0MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9EU1QwMS00SDhLUCIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJEYXRhIFN0cnVjdHVyZXMiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTA4LTI2VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IkFNSVIgSEFLSU1JIEJJTiBaVUxLSUZMSSJ9XSwiYWNoaWV2ZW1lbnQiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0L3B1YmxpYy92ZXJpZnkvRFNUMDEtNEg4S1AjYWNoaWV2ZW1lbnQiLCJ0eXBlIjpbIkFjaGlldmVtZW50Il0sImFjaGlldmVtZW50VHlwZSI6IkNlcnRpZmljYXRlIiwibmFtZSI6IkRhdGEgU3RydWN0dXJlcyIsImRlc2NyaXB0aW9uIjoiVGhpcyBpcyBhbiBVbmJ1bmRsZWQgTWljcm8tQ3JlZGVudGlhbCAoVU1DKSBmcm9tIEJJVFMgMTEyMyBEYXRhIFN0cnVjdHVyZXMgYW5kIEFsZ29yaXRobXMuIENvbXBsZXRpbmcgdGhlIHdob2xlIFVNQyBjYXJyaWVzIHRoZSBjcmVkaXQgb2YgdGhlIHBhcmVudCBjb3Vyc2UuIEEgbW9kdWxlIG9yIGEgYmFkZ2Ugb24gaXRzIG93biBpcyBub3QgY29tcGxldGlvbiBhbmQgbm90IGNyZWRpdC4iLCJjcml0ZXJpYSI6eyJuYXJyYXRpdmUiOiJWZXJpZmllZCBtYXJrczogTW9kdWxlIDEgVGVzdDogTGluZWFyIERhdGEgU3RydWN0dXJlcyAoMzAlKSwgTW9kdWxlIDIgVGVzdDogTm9uLUxpbmVhciBTdHJ1Y3R1cmVzICgzMCUpLCBDb21wcmVoZW5zaXZlIENhcHN0b25lIENvZGluZyBQcm9qZWN0ICg0MCUpLiBDb21wbGV0aW9uIGNvbmZpcm1lZCBieSB0aGUgZGVwdXR5IGRlYW4uIn0sImh1bWFuQ29kZSI6IlVURU0tTUMtMjAyNi0wMDAzIiwidGFnIjpbIkltcGxlbWVudCBjb250aWd1b3VzIGFuZCBsaW5rZWQgZGF0YSBzdHJ1Y3R1cmVzIGluIHNvZnR3YXJlLiIsIkFwcGx5IHRyZWUgdHJhdmVyc2FsIGFuZCBncmFwaCB0cmF2ZXJzYWwgYWxnb3JpdGhtcyB0byBzb2x2ZSBjb21wbGV4IHNlYXJjaGluZyBwcm9ibGVtcy4iLCJFdmFsdWF0ZSBhc3ltcHRvdGljIHRpbWUgYW5kIG1lbW9yeSBjb21wbGV4aXR5IG9mIGNvbXB1dGVyIGFsZ29yaXRobXMuIl0sImNyZWRpdHNBdmFpbGFibGUiOjN9fSwibWNtcyI6eyJjcmVkZW50aWFsX251bWJlciI6IlVURU0tTUMtMjAyNi0wMDAzIiwiY2F0ZWdvcnkiOiJVTUMiLCJzbHRfaG91cnMiOjgwLCJjbGFpbXMiOnsiY3JlZGl0IjozLCJtcWYiOm51bGwsInF2IjpmYWxzZSwicXZfdW50aWwiOm51bGx9LCJtcyI6eyJuYW1lIjoiU3RydWt0dXIgRGF0YSIsInN0YXRlbWVudCI6IkluaSBpYWxhaCBVbmJ1bmRsZWQgTWljcm8tQ3JlZGVudGlhbCAoVU1DKSBkYXJpcGFkYSBCSVRTIDExMjMgRGF0YSBTdHJ1Y3R1cmVzIGFuZCBBbGdvcml0aG1zLiBNZW5hbWF0a2FuIGtlc2VsdXJ1aGFuIFVNQyBtZW1iYXdhIGtyZWRpdCBrdXJzdXMgaW5kdWsuIFNhdHUgbW9kdWwgYXRhdSBzYXR1IGxlbmNhbmEgc2FoYWphIGJ1a2FuIHBlbmFtYXRhbiBkYW4gYnVrYW4ga3JlZGl0LiJ9fX19.kPwTBmiVyL_opfZSDYJiyrWH1fjV4VUpgw85bC478YVQW3wy_x8NYfQUW5whEgfBYfLs32VoXRVZprdaAF4iCA','did:utem:codl:vc-2026-01',NULL,NULL,'2026-08-26 10:00:00',NULL,NULL,'2026-08-26 10:00:00','2026-11-05 09:00:00'),(5,'UTEM-MC-2026-0004','DST02-5J9LQ',10,'valid',1,'{\"code\": \"DST02-5J9LQ\", \"name\": {\"en\": \"Data Structures\", \"ms\": \"Struktur Data\"}, \"claims\": {\"qv\": false, \"mqf\": null, \"credit\": 3, \"qv_until\": null}, \"holder\": \"TAN BEE LING\", \"issued\": \"2026-08-26T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0004\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"UMC\", \"outcomes\": {\"en\": [\"Implement contiguous and linked data structures in software.\", \"Apply tree traversal and graph traversal algorithms to solve complex searching problems.\", \"Evaluate asymptotic time and memory complexity of computer algorithms.\"], \"ms\": [\"Melaksanakan struktur data bersebelahan dan berpaut dalam perisian.\", \"Mengaplikasikan algoritma lintasan pokok dan graf untuk menyelesaikan masalah carian.\", \"Menilai kerumitan masa dan ingatan asimtotik algoritma komputer.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This is an Unbundled Micro-Credential (UMC) from BITS 1123 Data Structures and Algorithms. Completing the whole UMC carries the credit of the parent course. A module or a badge on its own is not completion and not credit.\", \"ms\": \"Ini ialah Unbundled Micro-Credential (UMC) daripada BITS 1123 Data Structures and Algorithms. Menamatkan keseluruhan UMC membawa kredit kursus induk. Satu modul atau satu lencana sahaja bukan penamatan dan bukan kredit.\"}, \"assessment_basis\": \"Verified marks: Module 1 Test: Linear Data Structures (30%), Module 2 Test: Non-Linear Structures (30%), Comprehensive Capstone Coding Project (40%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L0RTVDAyLTVKOUxRIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjQ3NDc2MjVjMTRiY2RhYWQiLCJuYmYiOjE3ODc3Mzg0MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9EU1QwMi01SjlMUSIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJEYXRhIFN0cnVjdHVyZXMiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTA4LTI2VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IlRBTiBCRUUgTElORyJ9XSwiYWNoaWV2ZW1lbnQiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0L3B1YmxpYy92ZXJpZnkvRFNUMDItNUo5TFEjYWNoaWV2ZW1lbnQiLCJ0eXBlIjpbIkFjaGlldmVtZW50Il0sImFjaGlldmVtZW50VHlwZSI6IkNlcnRpZmljYXRlIiwibmFtZSI6IkRhdGEgU3RydWN0dXJlcyIsImRlc2NyaXB0aW9uIjoiVGhpcyBpcyBhbiBVbmJ1bmRsZWQgTWljcm8tQ3JlZGVudGlhbCAoVU1DKSBmcm9tIEJJVFMgMTEyMyBEYXRhIFN0cnVjdHVyZXMgYW5kIEFsZ29yaXRobXMuIENvbXBsZXRpbmcgdGhlIHdob2xlIFVNQyBjYXJyaWVzIHRoZSBjcmVkaXQgb2YgdGhlIHBhcmVudCBjb3Vyc2UuIEEgbW9kdWxlIG9yIGEgYmFkZ2Ugb24gaXRzIG93biBpcyBub3QgY29tcGxldGlvbiBhbmQgbm90IGNyZWRpdC4iLCJjcml0ZXJpYSI6eyJuYXJyYXRpdmUiOiJWZXJpZmllZCBtYXJrczogTW9kdWxlIDEgVGVzdDogTGluZWFyIERhdGEgU3RydWN0dXJlcyAoMzAlKSwgTW9kdWxlIDIgVGVzdDogTm9uLUxpbmVhciBTdHJ1Y3R1cmVzICgzMCUpLCBDb21wcmVoZW5zaXZlIENhcHN0b25lIENvZGluZyBQcm9qZWN0ICg0MCUpLiBDb21wbGV0aW9uIGNvbmZpcm1lZCBieSB0aGUgZGVwdXR5IGRlYW4uIn0sImh1bWFuQ29kZSI6IlVURU0tTUMtMjAyNi0wMDA0IiwidGFnIjpbIkltcGxlbWVudCBjb250aWd1b3VzIGFuZCBsaW5rZWQgZGF0YSBzdHJ1Y3R1cmVzIGluIHNvZnR3YXJlLiIsIkFwcGx5IHRyZWUgdHJhdmVyc2FsIGFuZCBncmFwaCB0cmF2ZXJzYWwgYWxnb3JpdGhtcyB0byBzb2x2ZSBjb21wbGV4IHNlYXJjaGluZyBwcm9ibGVtcy4iLCJFdmFsdWF0ZSBhc3ltcHRvdGljIHRpbWUgYW5kIG1lbW9yeSBjb21wbGV4aXR5IG9mIGNvbXB1dGVyIGFsZ29yaXRobXMuIl0sImNyZWRpdHNBdmFpbGFibGUiOjN9fSwibWNtcyI6eyJjcmVkZW50aWFsX251bWJlciI6IlVURU0tTUMtMjAyNi0wMDA0IiwiY2F0ZWdvcnkiOiJVTUMiLCJzbHRfaG91cnMiOjgwLCJjbGFpbXMiOnsiY3JlZGl0IjozLCJtcWYiOm51bGwsInF2IjpmYWxzZSwicXZfdW50aWwiOm51bGx9LCJtcyI6eyJuYW1lIjoiU3RydWt0dXIgRGF0YSIsInN0YXRlbWVudCI6IkluaSBpYWxhaCBVbmJ1bmRsZWQgTWljcm8tQ3JlZGVudGlhbCAoVU1DKSBkYXJpcGFkYSBCSVRTIDExMjMgRGF0YSBTdHJ1Y3R1cmVzIGFuZCBBbGdvcml0aG1zLiBNZW5hbWF0a2FuIGtlc2VsdXJ1aGFuIFVNQyBtZW1iYXdhIGtyZWRpdCBrdXJzdXMgaW5kdWsuIFNhdHUgbW9kdWwgYXRhdSBzYXR1IGxlbmNhbmEgc2FoYWphIGJ1a2FuIHBlbmFtYXRhbiBkYW4gYnVrYW4ga3JlZGl0LiJ9fX19.zDJ2ztSdMuKmd9kYeSNzdwUM23R5zE7Xgj8KeloZOcZDkBJHjDSYGfxuEGBPWX1Fps2I7h3b4V0MwUB5ykqnCA','did:utem:codl:vc-2026-01',NULL,NULL,'2026-08-26 10:00:00',NULL,NULL,'2026-08-26 10:00:00','2026-11-05 09:00:00'),(6,'UTEM-MC-2026-0005','DST03-6K2MR',11,'valid',1,'{\"code\": \"DST03-6K2MR\", \"name\": {\"en\": \"Data Structures\", \"ms\": \"Struktur Data\"}, \"claims\": {\"qv\": false, \"mqf\": null, \"credit\": 3, \"qv_until\": null}, \"holder\": \"CITRA DEWI BINTI ISMAIL\", \"issued\": \"2026-08-26T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0005\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"UMC\", \"outcomes\": {\"en\": [\"Implement contiguous and linked data structures in software.\", \"Apply tree traversal and graph traversal algorithms to solve complex searching problems.\", \"Evaluate asymptotic time and memory complexity of computer algorithms.\"], \"ms\": [\"Melaksanakan struktur data bersebelahan dan berpaut dalam perisian.\", \"Mengaplikasikan algoritma lintasan pokok dan graf untuk menyelesaikan masalah carian.\", \"Menilai kerumitan masa dan ingatan asimtotik algoritma komputer.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This is an Unbundled Micro-Credential (UMC) from BITS 1123 Data Structures and Algorithms. Completing the whole UMC carries the credit of the parent course. A module or a badge on its own is not completion and not credit.\", \"ms\": \"Ini ialah Unbundled Micro-Credential (UMC) daripada BITS 1123 Data Structures and Algorithms. Menamatkan keseluruhan UMC membawa kredit kursus induk. Satu modul atau satu lencana sahaja bukan penamatan dan bukan kredit.\"}, \"assessment_basis\": \"Verified marks: Module 1 Test: Linear Data Structures (30%), Module 2 Test: Non-Linear Structures (30%), Comprehensive Capstone Coding Project (40%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L0RTVDAzLTZLMk1SIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjEyODkyNjdjYmUyNzg1ZTAiLCJuYmYiOjE3ODc3Mzg0MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9EU1QwMy02SzJNUiIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJEYXRhIFN0cnVjdHVyZXMiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTA4LTI2VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IkNJVFJBIERFV0kgQklOVEkgSVNNQUlMIn1dLCJhY2hpZXZlbWVudCI6eyJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9EU1QwMy02SzJNUiNhY2hpZXZlbWVudCIsInR5cGUiOlsiQWNoaWV2ZW1lbnQiXSwiYWNoaWV2ZW1lbnRUeXBlIjoiQ2VydGlmaWNhdGUiLCJuYW1lIjoiRGF0YSBTdHJ1Y3R1cmVzIiwiZGVzY3JpcHRpb24iOiJUaGlzIGlzIGFuIFVuYnVuZGxlZCBNaWNyby1DcmVkZW50aWFsIChVTUMpIGZyb20gQklUUyAxMTIzIERhdGEgU3RydWN0dXJlcyBhbmQgQWxnb3JpdGhtcy4gQ29tcGxldGluZyB0aGUgd2hvbGUgVU1DIGNhcnJpZXMgdGhlIGNyZWRpdCBvZiB0aGUgcGFyZW50IGNvdXJzZS4gQSBtb2R1bGUgb3IgYSBiYWRnZSBvbiBpdHMgb3duIGlzIG5vdCBjb21wbGV0aW9uIGFuZCBub3QgY3JlZGl0LiIsImNyaXRlcmlhIjp7Im5hcnJhdGl2ZSI6IlZlcmlmaWVkIG1hcmtzOiBNb2R1bGUgMSBUZXN0OiBMaW5lYXIgRGF0YSBTdHJ1Y3R1cmVzICgzMCUpLCBNb2R1bGUgMiBUZXN0OiBOb24tTGluZWFyIFN0cnVjdHVyZXMgKDMwJSksIENvbXByZWhlbnNpdmUgQ2Fwc3RvbmUgQ29kaW5nIFByb2plY3QgKDQwJSkuIENvbXBsZXRpb24gY29uZmlybWVkIGJ5IHRoZSBkZXB1dHkgZGVhbi4ifSwiaHVtYW5Db2RlIjoiVVRFTS1NQy0yMDI2LTAwMDUiLCJ0YWciOlsiSW1wbGVtZW50IGNvbnRpZ3VvdXMgYW5kIGxpbmtlZCBkYXRhIHN0cnVjdHVyZXMgaW4gc29mdHdhcmUuIiwiQXBwbHkgdHJlZSB0cmF2ZXJzYWwgYW5kIGdyYXBoIHRyYXZlcnNhbCBhbGdvcml0aG1zIHRvIHNvbHZlIGNvbXBsZXggc2VhcmNoaW5nIHByb2JsZW1zLiIsIkV2YWx1YXRlIGFzeW1wdG90aWMgdGltZSBhbmQgbWVtb3J5IGNvbXBsZXhpdHkgb2YgY29tcHV0ZXIgYWxnb3JpdGhtcy4iXSwiY3JlZGl0c0F2YWlsYWJsZSI6M319LCJtY21zIjp7ImNyZWRlbnRpYWxfbnVtYmVyIjoiVVRFTS1NQy0yMDI2LTAwMDUiLCJjYXRlZ29yeSI6IlVNQyIsInNsdF9ob3VycyI6ODAsImNsYWltcyI6eyJjcmVkaXQiOjMsIm1xZiI6bnVsbCwicXYiOmZhbHNlLCJxdl91bnRpbCI6bnVsbH0sIm1zIjp7Im5hbWUiOiJTdHJ1a3R1ciBEYXRhIiwic3RhdGVtZW50IjoiSW5pIGlhbGFoIFVuYnVuZGxlZCBNaWNyby1DcmVkZW50aWFsIChVTUMpIGRhcmlwYWRhIEJJVFMgMTEyMyBEYXRhIFN0cnVjdHVyZXMgYW5kIEFsZ29yaXRobXMuIE1lbmFtYXRrYW4ga2VzZWx1cnVoYW4gVU1DIG1lbWJhd2Ega3JlZGl0IGt1cnN1cyBpbmR1ay4gU2F0dSBtb2R1bCBhdGF1IHNhdHUgbGVuY2FuYSBzYWhhamEgYnVrYW4gcGVuYW1hdGFuIGRhbiBidWthbiBrcmVkaXQuIn19fX0.rZKIDNQymK2qD7ywsrXphltQ5T7joBakaOAfVvr_7Keo0V6gWAqCrYfXmq4T2oq9GNSZNkQ97aWPPLKiAxbpCA','did:utem:codl:vc-2026-01',NULL,NULL,'2026-08-26 10:00:00',NULL,NULL,'2026-08-26 10:00:00','2026-11-05 09:00:00'),(7,'UTEM-MC-2026-0006','DST04-7L3NS',12,'valid',1,'{\"code\": \"DST04-7L3NS\", \"name\": {\"en\": \"Data Structures\", \"ms\": \"Struktur Data\"}, \"claims\": {\"qv\": false, \"mqf\": null, \"credit\": 3, \"qv_until\": null}, \"holder\": \"SITI SARAH BINTI ABDUL RAHMAN\", \"issued\": \"2026-08-26T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0006\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"UMC\", \"outcomes\": {\"en\": [\"Implement contiguous and linked data structures in software.\", \"Apply tree traversal and graph traversal algorithms to solve complex searching problems.\", \"Evaluate asymptotic time and memory complexity of computer algorithms.\"], \"ms\": [\"Melaksanakan struktur data bersebelahan dan berpaut dalam perisian.\", \"Mengaplikasikan algoritma lintasan pokok dan graf untuk menyelesaikan masalah carian.\", \"Menilai kerumitan masa dan ingatan asimtotik algoritma komputer.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This is an Unbundled Micro-Credential (UMC) from BITS 1123 Data Structures and Algorithms. Completing the whole UMC carries the credit of the parent course. A module or a badge on its own is not completion and not credit.\", \"ms\": \"Ini ialah Unbundled Micro-Credential (UMC) daripada BITS 1123 Data Structures and Algorithms. Menamatkan keseluruhan UMC membawa kredit kursus induk. Satu modul atau satu lencana sahaja bukan penamatan dan bukan kredit.\"}, \"assessment_basis\": \"Verified marks: Module 1 Test: Linear Data Structures (30%), Module 2 Test: Non-Linear Structures (30%), Comprehensive Capstone Coding Project (40%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L0RTVDA0LTdMM05TIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjcyN2JiNzFiYTMyMjljN2YiLCJuYmYiOjE3ODc3Mzg0MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9EU1QwNC03TDNOUyIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJEYXRhIFN0cnVjdHVyZXMiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTA4LTI2VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IlNJVEkgU0FSQUggQklOVEkgQUJEVUwgUkFITUFOIn1dLCJhY2hpZXZlbWVudCI6eyJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9EU1QwNC03TDNOUyNhY2hpZXZlbWVudCIsInR5cGUiOlsiQWNoaWV2ZW1lbnQiXSwiYWNoaWV2ZW1lbnRUeXBlIjoiQ2VydGlmaWNhdGUiLCJuYW1lIjoiRGF0YSBTdHJ1Y3R1cmVzIiwiZGVzY3JpcHRpb24iOiJUaGlzIGlzIGFuIFVuYnVuZGxlZCBNaWNyby1DcmVkZW50aWFsIChVTUMpIGZyb20gQklUUyAxMTIzIERhdGEgU3RydWN0dXJlcyBhbmQgQWxnb3JpdGhtcy4gQ29tcGxldGluZyB0aGUgd2hvbGUgVU1DIGNhcnJpZXMgdGhlIGNyZWRpdCBvZiB0aGUgcGFyZW50IGNvdXJzZS4gQSBtb2R1bGUgb3IgYSBiYWRnZSBvbiBpdHMgb3duIGlzIG5vdCBjb21wbGV0aW9uIGFuZCBub3QgY3JlZGl0LiIsImNyaXRlcmlhIjp7Im5hcnJhdGl2ZSI6IlZlcmlmaWVkIG1hcmtzOiBNb2R1bGUgMSBUZXN0OiBMaW5lYXIgRGF0YSBTdHJ1Y3R1cmVzICgzMCUpLCBNb2R1bGUgMiBUZXN0OiBOb24tTGluZWFyIFN0cnVjdHVyZXMgKDMwJSksIENvbXByZWhlbnNpdmUgQ2Fwc3RvbmUgQ29kaW5nIFByb2plY3QgKDQwJSkuIENvbXBsZXRpb24gY29uZmlybWVkIGJ5IHRoZSBkZXB1dHkgZGVhbi4ifSwiaHVtYW5Db2RlIjoiVVRFTS1NQy0yMDI2LTAwMDYiLCJ0YWciOlsiSW1wbGVtZW50IGNvbnRpZ3VvdXMgYW5kIGxpbmtlZCBkYXRhIHN0cnVjdHVyZXMgaW4gc29mdHdhcmUuIiwiQXBwbHkgdHJlZSB0cmF2ZXJzYWwgYW5kIGdyYXBoIHRyYXZlcnNhbCBhbGdvcml0aG1zIHRvIHNvbHZlIGNvbXBsZXggc2VhcmNoaW5nIHByb2JsZW1zLiIsIkV2YWx1YXRlIGFzeW1wdG90aWMgdGltZSBhbmQgbWVtb3J5IGNvbXBsZXhpdHkgb2YgY29tcHV0ZXIgYWxnb3JpdGhtcy4iXSwiY3JlZGl0c0F2YWlsYWJsZSI6M319LCJtY21zIjp7ImNyZWRlbnRpYWxfbnVtYmVyIjoiVVRFTS1NQy0yMDI2LTAwMDYiLCJjYXRlZ29yeSI6IlVNQyIsInNsdF9ob3VycyI6ODAsImNsYWltcyI6eyJjcmVkaXQiOjMsIm1xZiI6bnVsbCwicXYiOmZhbHNlLCJxdl91bnRpbCI6bnVsbH0sIm1zIjp7Im5hbWUiOiJTdHJ1a3R1ciBEYXRhIiwic3RhdGVtZW50IjoiSW5pIGlhbGFoIFVuYnVuZGxlZCBNaWNyby1DcmVkZW50aWFsIChVTUMpIGRhcmlwYWRhIEJJVFMgMTEyMyBEYXRhIFN0cnVjdHVyZXMgYW5kIEFsZ29yaXRobXMuIE1lbmFtYXRrYW4ga2VzZWx1cnVoYW4gVU1DIG1lbWJhd2Ega3JlZGl0IGt1cnN1cyBpbmR1ay4gU2F0dSBtb2R1bCBhdGF1IHNhdHUgbGVuY2FuYSBzYWhhamEgYnVrYW4gcGVuYW1hdGFuIGRhbiBidWthbiBrcmVkaXQuIn19fX0.2hl_BaMRq-HdKxmp_iwdkQaimIuLyRgzKEdbq3MeVMQgvEmZa3IplCG5CLw5uSYGLgmGdaFEwCxN2tTxSxqWDg','did:utem:codl:vc-2026-01',NULL,NULL,'2026-08-26 10:00:00',NULL,NULL,'2026-08-26 10:00:00','2026-11-05 09:00:00'),(8,'UTEM-MC-2026-0007','SEC01-8M4PT',13,'valid',1,'{\"code\": \"SEC01-8M4PT\", \"name\": {\"en\": \"Secure Software Development Lifecycle (SSDLC)\", \"ms\": \"Kitar Hayat Pembangunan Perisian Selamat (SSDLC)\"}, \"claims\": {\"qv\": true, \"mqf\": 6, \"credit\": \"2\", \"qv_until\": \"2029-08-15\"}, \"holder\": \"AMIR HAKIMI BIN ZULKIFLI\", \"issued\": \"2026-10-26T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0007\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"SAMC\", \"outcomes\": {\"en\": [\"Perform STRIDE threat modelling and remediate OWASP Top 10 vulnerabilities.\", \"Integrate automated SAST and DAST security gates into CI/CD pipelines.\"], \"ms\": [\"Melaksanakan pemodelan ancaman STRIDE dan memulihkan kelemahan OWASP Top 10.\", \"Mengintegrasikan gerbang keselamatan SAST dan DAST automatik ke dalam CI/CD.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This SAMC is Quality-Verified. Credit 2, MQF level 6, valid until 2029-08-15.\", \"ms\": \"SAMC ini telah disahkan melalui Quality Verification. Kredit 2, tahap MQF 6, sah hingga 2029-08-15.\"}, \"assessment_basis\": \"Verified marks: STRIDE Threat Model & OWASP Remediation Lab (50%), Automated DevSecOps Pipeline Capstone (50%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L1NFQzAxLThNNFBUIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjFhOWMxOGExYjY2NGM5MzYiLCJuYmYiOjE3OTMwMDg4MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9TRUMwMS04TTRQVCIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJTZWN1cmUgU29mdHdhcmUgRGV2ZWxvcG1lbnQgTGlmZWN5Y2xlIChTU0RMQykiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTEwLTI2VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IkFNSVIgSEFLSU1JIEJJTiBaVUxLSUZMSSJ9XSwiYWNoaWV2ZW1lbnQiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0L3B1YmxpYy92ZXJpZnkvU0VDMDEtOE00UFQjYWNoaWV2ZW1lbnQiLCJ0eXBlIjpbIkFjaGlldmVtZW50Il0sImFjaGlldmVtZW50VHlwZSI6IkNlcnRpZmljYXRlIiwibmFtZSI6IlNlY3VyZSBTb2Z0d2FyZSBEZXZlbG9wbWVudCBMaWZlY3ljbGUgKFNTRExDKSIsImRlc2NyaXB0aW9uIjoiVGhpcyBTQU1DIGlzIFF1YWxpdHktVmVyaWZpZWQuIENyZWRpdCAyLCBNUUYgbGV2ZWwgNiwgdmFsaWQgdW50aWwgMjAyOS0wOC0xNS4iLCJjcml0ZXJpYSI6eyJuYXJyYXRpdmUiOiJWZXJpZmllZCBtYXJrczogU1RSSURFIFRocmVhdCBNb2RlbCAmIE9XQVNQIFJlbWVkaWF0aW9uIExhYiAoNTAlKSwgQXV0b21hdGVkIERldlNlY09wcyBQaXBlbGluZSBDYXBzdG9uZSAoNTAlKS4gQ29tcGxldGlvbiBjb25maXJtZWQgYnkgdGhlIGRlcHV0eSBkZWFuLiJ9LCJodW1hbkNvZGUiOiJVVEVNLU1DLTIwMjYtMDAwNyIsInRhZyI6WyJQZXJmb3JtIFNUUklERSB0aHJlYXQgbW9kZWxsaW5nIGFuZCByZW1lZGlhdGUgT1dBU1AgVG9wIDEwIHZ1bG5lcmFiaWxpdGllcy4iLCJJbnRlZ3JhdGUgYXV0b21hdGVkIFNBU1QgYW5kIERBU1Qgc2VjdXJpdHkgZ2F0ZXMgaW50byBDSS9DRCBwaXBlbGluZXMuIl0sImNyZWRpdHNBdmFpbGFibGUiOjJ9fSwibWNtcyI6eyJjcmVkZW50aWFsX251bWJlciI6IlVURU0tTUMtMjAyNi0wMDA3IiwiY2F0ZWdvcnkiOiJTQU1DIiwic2x0X2hvdXJzIjo4MCwiY2xhaW1zIjp7ImNyZWRpdCI6IjIiLCJtcWYiOjYsInF2Ijp0cnVlLCJxdl91bnRpbCI6IjIwMjktMDgtMTUifSwibXMiOnsibmFtZSI6IktpdGFyIEhheWF0IFBlbWJhbmd1bmFuIFBlcmlzaWFuIFNlbGFtYXQgKFNTRExDKSIsInN0YXRlbWVudCI6IlNBTUMgaW5pIHRlbGFoIGRpc2Foa2FuIG1lbGFsdWkgUXVhbGl0eSBWZXJpZmljYXRpb24uIEtyZWRpdCAyLCB0YWhhcCBNUUYgNiwgc2FoIGhpbmdnYSAyMDI5LTA4LTE1LiJ9fX19.Iy9J_WbWDpR9PRzCbDCY-FElbdzJUS8HMRml8U23wBA_i3BmHnbJePOh7gx9Rw5t35qcW6Hig6rP4Kqp5Q-KCA','did:utem:codl:vc-2026-01',NULL,NULL,'2026-10-26 10:00:00',NULL,NULL,'2026-10-26 10:00:00','2026-11-05 09:00:00'),(9,'UTEM-MC-2026-0008','SEC02-9N5QV',14,'valid',1,'{\"code\": \"SEC02-9N5QV\", \"name\": {\"en\": \"Secure Software Development Lifecycle (SSDLC)\", \"ms\": \"Kitar Hayat Pembangunan Perisian Selamat (SSDLC)\"}, \"claims\": {\"qv\": true, \"mqf\": 6, \"credit\": \"2\", \"qv_until\": \"2029-08-15\"}, \"holder\": \"SHARIFAH PUTERI NURUL AIN SYUHADA BINTI MEGAT ISKANDAR ZULKARNAIN\", \"issued\": \"2026-10-26T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0008\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"SAMC\", \"outcomes\": {\"en\": [\"Perform STRIDE threat modelling and remediate OWASP Top 10 vulnerabilities.\", \"Integrate automated SAST and DAST security gates into CI/CD pipelines.\"], \"ms\": [\"Melaksanakan pemodelan ancaman STRIDE dan memulihkan kelemahan OWASP Top 10.\", \"Mengintegrasikan gerbang keselamatan SAST dan DAST automatik ke dalam CI/CD.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This SAMC is Quality-Verified. Credit 2, MQF level 6, valid until 2029-08-15.\", \"ms\": \"SAMC ini telah disahkan melalui Quality Verification. Kredit 2, tahap MQF 6, sah hingga 2029-08-15.\"}, \"assessment_basis\": \"Verified marks: STRIDE Threat Model & OWASP Remediation Lab (50%), Automated DevSecOps Pipeline Capstone (50%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L1NFQzAyLTlONVFWIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjMxNTZiOGQ4NmNjMjViMWIiLCJuYmYiOjE3OTMwMDg4MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9TRUMwMi05TjVRViIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJTZWN1cmUgU29mdHdhcmUgRGV2ZWxvcG1lbnQgTGlmZWN5Y2xlIChTU0RMQykiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTEwLTI2VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IlNIQVJJRkFIIFBVVEVSSSBOVVJVTCBBSU4gU1lVSEFEQSBCSU5USSBNRUdBVCBJU0tBTkRBUiBaVUxLQVJOQUlOIn1dLCJhY2hpZXZlbWVudCI6eyJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9TRUMwMi05TjVRViNhY2hpZXZlbWVudCIsInR5cGUiOlsiQWNoaWV2ZW1lbnQiXSwiYWNoaWV2ZW1lbnRUeXBlIjoiQ2VydGlmaWNhdGUiLCJuYW1lIjoiU2VjdXJlIFNvZnR3YXJlIERldmVsb3BtZW50IExpZmVjeWNsZSAoU1NETEMpIiwiZGVzY3JpcHRpb24iOiJUaGlzIFNBTUMgaXMgUXVhbGl0eS1WZXJpZmllZC4gQ3JlZGl0IDIsIE1RRiBsZXZlbCA2LCB2YWxpZCB1bnRpbCAyMDI5LTA4LTE1LiIsImNyaXRlcmlhIjp7Im5hcnJhdGl2ZSI6IlZlcmlmaWVkIG1hcmtzOiBTVFJJREUgVGhyZWF0IE1vZGVsICYgT1dBU1AgUmVtZWRpYXRpb24gTGFiICg1MCUpLCBBdXRvbWF0ZWQgRGV2U2VjT3BzIFBpcGVsaW5lIENhcHN0b25lICg1MCUpLiBDb21wbGV0aW9uIGNvbmZpcm1lZCBieSB0aGUgZGVwdXR5IGRlYW4uIn0sImh1bWFuQ29kZSI6IlVURU0tTUMtMjAyNi0wMDA4IiwidGFnIjpbIlBlcmZvcm0gU1RSSURFIHRocmVhdCBtb2RlbGxpbmcgYW5kIHJlbWVkaWF0ZSBPV0FTUCBUb3AgMTAgdnVsbmVyYWJpbGl0aWVzLiIsIkludGVncmF0ZSBhdXRvbWF0ZWQgU0FTVCBhbmQgREFTVCBzZWN1cml0eSBnYXRlcyBpbnRvIENJL0NEIHBpcGVsaW5lcy4iXSwiY3JlZGl0c0F2YWlsYWJsZSI6Mn19LCJtY21zIjp7ImNyZWRlbnRpYWxfbnVtYmVyIjoiVVRFTS1NQy0yMDI2LTAwMDgiLCJjYXRlZ29yeSI6IlNBTUMiLCJzbHRfaG91cnMiOjgwLCJjbGFpbXMiOnsiY3JlZGl0IjoiMiIsIm1xZiI6NiwicXYiOnRydWUsInF2X3VudGlsIjoiMjAyOS0wOC0xNSJ9LCJtcyI6eyJuYW1lIjoiS2l0YXIgSGF5YXQgUGVtYmFuZ3VuYW4gUGVyaXNpYW4gU2VsYW1hdCAoU1NETEMpIiwic3RhdGVtZW50IjoiU0FNQyBpbmkgdGVsYWggZGlzYWhrYW4gbWVsYWx1aSBRdWFsaXR5IFZlcmlmaWNhdGlvbi4gS3JlZGl0IDIsIHRhaGFwIE1RRiA2LCBzYWggaGluZ2dhIDIwMjktMDgtMTUuIn19fX0.hPAr9fFiz2qcwJxltL_PEGwB_FzPnCj2qhNm_xVEVBFqpNSvx7qGoP2LlwRWMz4ctopxUiapjMppIHls0_lgCg','did:utem:codl:vc-2026-01',NULL,NULL,'2026-10-26 10:00:00',NULL,NULL,'2026-10-26 10:00:00','2026-11-05 09:00:00'),(10,'UTEM-MC-2026-0009','SEC03-2P6RW',15,'revoked',1,'{\"code\": \"SEC03-2P6RW\", \"name\": {\"en\": \"Secure Software Development Lifecycle (SSDLC)\", \"ms\": \"Kitar Hayat Pembangunan Perisian Selamat (SSDLC)\"}, \"claims\": {\"qv\": true, \"mqf\": 6, \"credit\": \"2\", \"qv_until\": \"2029-08-15\"}, \"holder\": \"TAN LI\", \"issued\": \"2026-10-26T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0009\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"SAMC\", \"outcomes\": {\"en\": [\"Perform STRIDE threat modelling and remediate OWASP Top 10 vulnerabilities.\", \"Integrate automated SAST and DAST security gates into CI/CD pipelines.\"], \"ms\": [\"Melaksanakan pemodelan ancaman STRIDE dan memulihkan kelemahan OWASP Top 10.\", \"Mengintegrasikan gerbang keselamatan SAST dan DAST automatik ke dalam CI/CD.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 80, \"statement\": {\"en\": \"This SAMC is Quality-Verified. Credit 2, MQF level 6, valid until 2029-08-15.\", \"ms\": \"SAMC ini telah disahkan melalui Quality Verification. Kredit 2, tahap MQF 6, sah hingga 2029-08-15.\"}, \"assessment_basis\": \"Verified marks: STRIDE Threat Model & OWASP Remediation Lab (50%), Automated DevSecOps Pipeline Capstone (50%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L1NFQzAzLTJQNlJXIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjY1MDU0OTk2OWMzYzA1NmMiLCJuYmYiOjE3OTMwMDg4MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9TRUMwMy0yUDZSVyIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJTZWN1cmUgU29mdHdhcmUgRGV2ZWxvcG1lbnQgTGlmZWN5Y2xlIChTU0RMQykiLCJpc3N1ZXIiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0IiwidHlwZSI6WyJQcm9maWxlIl0sIm5hbWUiOiJVbml2ZXJzaXRpIFRla25pa2FsIE1hbGF5c2lhIE1lbGFrYSJ9LCJ2YWxpZEZyb20iOiIyMDI2LTEwLTI2VDEwOjAwOjAwKzAwOjAwIiwiY3JlZGVudGlhbFN1YmplY3QiOnsidHlwZSI6WyJBY2hpZXZlbWVudFN1YmplY3QiXSwiaWRlbnRpZmllciI6W3sidHlwZSI6IklkZW50aXR5T2JqZWN0IiwiaWRlbnRpdHlUeXBlIjoibmFtZSIsImhhc2hlZCI6ZmFsc2UsImlkZW50aXR5SGFzaCI6IlRBTiBMSSJ9XSwiYWNoaWV2ZW1lbnQiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0L3B1YmxpYy92ZXJpZnkvU0VDMDMtMlA2UlcjYWNoaWV2ZW1lbnQiLCJ0eXBlIjpbIkFjaGlldmVtZW50Il0sImFjaGlldmVtZW50VHlwZSI6IkNlcnRpZmljYXRlIiwibmFtZSI6IlNlY3VyZSBTb2Z0d2FyZSBEZXZlbG9wbWVudCBMaWZlY3ljbGUgKFNTRExDKSIsImRlc2NyaXB0aW9uIjoiVGhpcyBTQU1DIGlzIFF1YWxpdHktVmVyaWZpZWQuIENyZWRpdCAyLCBNUUYgbGV2ZWwgNiwgdmFsaWQgdW50aWwgMjAyOS0wOC0xNS4iLCJjcml0ZXJpYSI6eyJuYXJyYXRpdmUiOiJWZXJpZmllZCBtYXJrczogU1RSSURFIFRocmVhdCBNb2RlbCAmIE9XQVNQIFJlbWVkaWF0aW9uIExhYiAoNTAlKSwgQXV0b21hdGVkIERldlNlY09wcyBQaXBlbGluZSBDYXBzdG9uZSAoNTAlKS4gQ29tcGxldGlvbiBjb25maXJtZWQgYnkgdGhlIGRlcHV0eSBkZWFuLiJ9LCJodW1hbkNvZGUiOiJVVEVNLU1DLTIwMjYtMDAwOSIsInRhZyI6WyJQZXJmb3JtIFNUUklERSB0aHJlYXQgbW9kZWxsaW5nIGFuZCByZW1lZGlhdGUgT1dBU1AgVG9wIDEwIHZ1bG5lcmFiaWxpdGllcy4iLCJJbnRlZ3JhdGUgYXV0b21hdGVkIFNBU1QgYW5kIERBU1Qgc2VjdXJpdHkgZ2F0ZXMgaW50byBDSS9DRCBwaXBlbGluZXMuIl0sImNyZWRpdHNBdmFpbGFibGUiOjJ9fSwibWNtcyI6eyJjcmVkZW50aWFsX251bWJlciI6IlVURU0tTUMtMjAyNi0wMDA5IiwiY2F0ZWdvcnkiOiJTQU1DIiwic2x0X2hvdXJzIjo4MCwiY2xhaW1zIjp7ImNyZWRpdCI6IjIiLCJtcWYiOjYsInF2Ijp0cnVlLCJxdl91bnRpbCI6IjIwMjktMDgtMTUifSwibXMiOnsibmFtZSI6IktpdGFyIEhheWF0IFBlbWJhbmd1bmFuIFBlcmlzaWFuIFNlbGFtYXQgKFNTRExDKSIsInN0YXRlbWVudCI6IlNBTUMgaW5pIHRlbGFoIGRpc2Foa2FuIG1lbGFsdWkgUXVhbGl0eSBWZXJpZmljYXRpb24uIEtyZWRpdCAyLCB0YWhhcCBNUUYgNiwgc2FoIGhpbmdnYSAyMDI5LTA4LTE1LiJ9fX19.71sge5GoH1K2PwnLjA8wsXO_Q9HzUrCde6eK1NnOxmMjXOAed_ox6Hux22y7hcKmK-YisNWQygu0F4tVLtpcCA','did:utem:codl:vc-2026-01',NULL,NULL,'2026-10-26 10:00:00','2026-11-01 10:00:00','Revoked following post-issuance employer audit of external sponsor declaration.','2026-10-26 10:00:00','2026-11-05 09:00:00'),(11,'UTEM-MC-2026-0010','OOP01-3Q7SX',17,'valid',1,'{\"code\": \"OOP01-3Q7SX\", \"name\": {\"en\": \"Object-Oriented Software Construction\", \"ms\": \"Pembinaan Perisian Berorientasikan Objek\"}, \"claims\": {\"qv\": false, \"mqf\": null, \"credit\": 3, \"qv_until\": null}, \"holder\": \"AMIR HAKIMI BIN ZULKIFLI\", \"issued\": \"2026-10-06T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0010\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"UMC\", \"outcomes\": {\"en\": [\"Design modular object-oriented software adhering to SOLID principles.\", \"Verify software reliability using automated JUnit test suites.\"], \"ms\": [\"Mereka bentuk perisian berorientasikan objek modular mematuhi prinsip SOLID.\", \"Mengesahkan kebolehpercayaan perisian menggunakan suit ujian JUnit automatik.\"]}, \"signatory\": \"Hakim Rahman\", \"slt_hours\": 120, \"statement\": {\"en\": \"This is an Unbundled Micro-Credential (UMC) from BITD 1123 Object-Oriented Programming. Completing the whole UMC carries the credit of the parent course. A module or a badge on its own is not completion and not credit.\", \"ms\": \"Ini ialah Unbundled Micro-Credential (UMC) daripada BITD 1123 Object-Oriented Programming. Menamatkan keseluruhan UMC membawa kredit kursus induk. Satu modul atau satu lencana sahaja bukan penamatan dan bukan kredit.\"}, \"assessment_basis\": \"Verified marks: OOP Class Hierarchy & Polymorphism Practical (50%), SOLID Refactoring & JUnit Test Suite Project (50%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L09PUDAxLTNRN1NYIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjY2ODhkZTU0Njc5MGNmYTQiLCJuYmYiOjE3OTEyODA4MDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9PT1AwMS0zUTdTWCIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJPYmplY3QtT3JpZW50ZWQgU29mdHdhcmUgQ29uc3RydWN0aW9uIiwiaXNzdWVyIjp7ImlkIjoiaHR0cDovL2xvY2FsaG9zdCIsInR5cGUiOlsiUHJvZmlsZSJdLCJuYW1lIjoiVW5pdmVyc2l0aSBUZWtuaWthbCBNYWxheXNpYSBNZWxha2EifSwidmFsaWRGcm9tIjoiMjAyNi0xMC0wNlQxMDowMDowMCswMDowMCIsImNyZWRlbnRpYWxTdWJqZWN0Ijp7InR5cGUiOlsiQWNoaWV2ZW1lbnRTdWJqZWN0Il0sImlkZW50aWZpZXIiOlt7InR5cGUiOiJJZGVudGl0eU9iamVjdCIsImlkZW50aXR5VHlwZSI6Im5hbWUiLCJoYXNoZWQiOmZhbHNlLCJpZGVudGl0eUhhc2giOiJBTUlSIEhBS0lNSSBCSU4gWlVMS0lGTEkifV0sImFjaGlldmVtZW50Ijp7ImlkIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L09PUDAxLTNRN1NYI2FjaGlldmVtZW50IiwidHlwZSI6WyJBY2hpZXZlbWVudCJdLCJhY2hpZXZlbWVudFR5cGUiOiJDZXJ0aWZpY2F0ZSIsIm5hbWUiOiJPYmplY3QtT3JpZW50ZWQgU29mdHdhcmUgQ29uc3RydWN0aW9uIiwiZGVzY3JpcHRpb24iOiJUaGlzIGlzIGFuIFVuYnVuZGxlZCBNaWNyby1DcmVkZW50aWFsIChVTUMpIGZyb20gQklURCAxMTIzIE9iamVjdC1PcmllbnRlZCBQcm9ncmFtbWluZy4gQ29tcGxldGluZyB0aGUgd2hvbGUgVU1DIGNhcnJpZXMgdGhlIGNyZWRpdCBvZiB0aGUgcGFyZW50IGNvdXJzZS4gQSBtb2R1bGUgb3IgYSBiYWRnZSBvbiBpdHMgb3duIGlzIG5vdCBjb21wbGV0aW9uIGFuZCBub3QgY3JlZGl0LiIsImNyaXRlcmlhIjp7Im5hcnJhdGl2ZSI6IlZlcmlmaWVkIG1hcmtzOiBPT1AgQ2xhc3MgSGllcmFyY2h5ICYgUG9seW1vcnBoaXNtIFByYWN0aWNhbCAoNTAlKSwgU09MSUQgUmVmYWN0b3JpbmcgJiBKVW5pdCBUZXN0IFN1aXRlIFByb2plY3QgKDUwJSkuIENvbXBsZXRpb24gY29uZmlybWVkIGJ5IHRoZSBkZXB1dHkgZGVhbi4ifSwiaHVtYW5Db2RlIjoiVVRFTS1NQy0yMDI2LTAwMTAiLCJ0YWciOlsiRGVzaWduIG1vZHVsYXIgb2JqZWN0LW9yaWVudGVkIHNvZnR3YXJlIGFkaGVyaW5nIHRvIFNPTElEIHByaW5jaXBsZXMuIiwiVmVyaWZ5IHNvZnR3YXJlIHJlbGlhYmlsaXR5IHVzaW5nIGF1dG9tYXRlZCBKVW5pdCB0ZXN0IHN1aXRlcy4iXSwiY3JlZGl0c0F2YWlsYWJsZSI6M319LCJtY21zIjp7ImNyZWRlbnRpYWxfbnVtYmVyIjoiVVRFTS1NQy0yMDI2LTAwMTAiLCJjYXRlZ29yeSI6IlVNQyIsInNsdF9ob3VycyI6MTIwLCJjbGFpbXMiOnsiY3JlZGl0IjozLCJtcWYiOm51bGwsInF2IjpmYWxzZSwicXZfdW50aWwiOm51bGx9LCJtcyI6eyJuYW1lIjoiUGVtYmluYWFuIFBlcmlzaWFuIEJlcm9yaWVudGFzaWthbiBPYmplayIsInN0YXRlbWVudCI6IkluaSBpYWxhaCBVbmJ1bmRsZWQgTWljcm8tQ3JlZGVudGlhbCAoVU1DKSBkYXJpcGFkYSBCSVREIDExMjMgT2JqZWN0LU9yaWVudGVkIFByb2dyYW1taW5nLiBNZW5hbWF0a2FuIGtlc2VsdXJ1aGFuIFVNQyBtZW1iYXdhIGtyZWRpdCBrdXJzdXMgaW5kdWsuIFNhdHUgbW9kdWwgYXRhdSBzYXR1IGxlbmNhbmEgc2FoYWphIGJ1a2FuIHBlbmFtYXRhbiBkYW4gYnVrYW4ga3JlZGl0LiJ9fX19.-hweGbBUuGcAkwkI4S3Pnwvc1tnAFBXZPqLNCvDjbaNIPh7WLocfgKlFnHKXU8Vt_GULONU-W04SlJTPuy-cBg','did:utem:codl:vc-2026-01',NULL,NULL,'2026-10-06 10:00:00',NULL,NULL,'2026-10-06 10:00:00','2026-11-05 09:00:00'),(12,'UTEM-MC-2026-0011','MBA01-4R8TY',19,'valid',1,'{\"code\": \"MBA01-4R8TY\", \"name\": {\"en\": \"Strategic Technopreneurship & Venture Creation\", \"ms\": \"Teknousahawan Strategik & Penciptaan Usaha Niaga\"}, \"claims\": {\"qv\": false, \"mqf\": null, \"credit\": 3, \"qv_until\": null}, \"holder\": \"SITI SARAH BINTI ABDUL RAHMAN\", \"issued\": \"2026-10-09T10:00:00+00:00\", \"issuer\": \"Universiti Teknikal Malaysia Melaka\", \"number\": \"UTEM-MC-2026-0011\", \"stages\": {\"en\": \"Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.\", \"ms\": \"Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.\"}, \"category\": \"UMC\", \"outcomes\": {\"en\": [\"Validate technology business models and formulate go-to-market strategies.\", \"Construct financial projections and defend an investment-ready venture pitch.\"], \"ms\": [\"Mengesahkan model perniagaan teknologi dan merumuskan strategi pasaran.\", \"Membina unjuran kewangan dan mempertahankan pembentangan usaha niaga sedia pelaburan.\"]}, \"signatory\": \"Prof. Ir. Dr. Roslan bin Abd Rahim\", \"slt_hours\": 120, \"statement\": {\"en\": \"This is an Unbundled Micro-Credential (UMC) from MBA 5113 Strategic Management in Technopreneurship. Completing the whole UMC carries the credit of the parent course. A module or a badge on its own is not completion and not credit.\", \"ms\": \"Ini ialah Unbundled Micro-Credential (UMC) daripada MBA 5113 Strategic Management in Technopreneurship. Menamatkan keseluruhan UMC membawa kredit kursus induk. Satu modul atau satu lencana sahaja bukan penamatan dan bukan kredit.\"}, \"assessment_basis\": \"Verified marks: Lean Canvas & Market Validation Dossier (50%), Venture Valuation & Executive Pitch Defence (50%). Completion confirmed by the deputy dean.\"}','eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCIsImtpZCI6ImRpZDp1dGVtOmNvZGw6dmMtMjAyNi0wMSJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwianRpIjoiaHR0cDovL2xvY2FsaG9zdC9wdWJsaWMvdmVyaWZ5L01CQTAxLTRSOFRZIiwic3ViIjoidXJuOm1jbXM6aG9sZGVyOjkxZWNhYzYyYmU4NzMxZTgiLCJuYmYiOjE3OTE1NDAwMDAsImlhdCI6MTc5MzkyNjMzMSwidmMiOnsiQGNvbnRleHQiOlsiaHR0cHM6Ly93d3cudzMub3JnL25zL2NyZWRlbnRpYWxzL3YyIiwiaHR0cHM6Ly9wdXJsLmltc2dsb2JhbC5vcmcvc3BlYy9vYi92M3AwL2NvbnRleHQtMy4wLjMuanNvbiJdLCJpZCI6Imh0dHA6Ly9sb2NhbGhvc3QvcHVibGljL3ZlcmlmeS9NQkEwMS00UjhUWSIsInR5cGUiOlsiVmVyaWZpYWJsZUNyZWRlbnRpYWwiLCJPcGVuQmFkZ2VDcmVkZW50aWFsIl0sIm5hbWUiOiJTdHJhdGVnaWMgVGVjaG5vcHJlbmV1cnNoaXAgJiBWZW50dXJlIENyZWF0aW9uIiwiaXNzdWVyIjp7ImlkIjoiaHR0cDovL2xvY2FsaG9zdCIsInR5cGUiOlsiUHJvZmlsZSJdLCJuYW1lIjoiVW5pdmVyc2l0aSBUZWtuaWthbCBNYWxheXNpYSBNZWxha2EifSwidmFsaWRGcm9tIjoiMjAyNi0xMC0wOVQxMDowMDowMCswMDowMCIsImNyZWRlbnRpYWxTdWJqZWN0Ijp7InR5cGUiOlsiQWNoaWV2ZW1lbnRTdWJqZWN0Il0sImlkZW50aWZpZXIiOlt7InR5cGUiOiJJZGVudGl0eU9iamVjdCIsImlkZW50aXR5VHlwZSI6Im5hbWUiLCJoYXNoZWQiOmZhbHNlLCJpZGVudGl0eUhhc2giOiJTSVRJIFNBUkFIIEJJTlRJIEFCRFVMIFJBSE1BTiJ9XSwiYWNoaWV2ZW1lbnQiOnsiaWQiOiJodHRwOi8vbG9jYWxob3N0L3B1YmxpYy92ZXJpZnkvTUJBMDEtNFI4VFkjYWNoaWV2ZW1lbnQiLCJ0eXBlIjpbIkFjaGlldmVtZW50Il0sImFjaGlldmVtZW50VHlwZSI6IkNlcnRpZmljYXRlIiwibmFtZSI6IlN0cmF0ZWdpYyBUZWNobm9wcmVuZXVyc2hpcCAmIFZlbnR1cmUgQ3JlYXRpb24iLCJkZXNjcmlwdGlvbiI6IlRoaXMgaXMgYW4gVW5idW5kbGVkIE1pY3JvLUNyZWRlbnRpYWwgKFVNQykgZnJvbSBNQkEgNTExMyBTdHJhdGVnaWMgTWFuYWdlbWVudCBpbiBUZWNobm9wcmVuZXVyc2hpcC4gQ29tcGxldGluZyB0aGUgd2hvbGUgVU1DIGNhcnJpZXMgdGhlIGNyZWRpdCBvZiB0aGUgcGFyZW50IGNvdXJzZS4gQSBtb2R1bGUgb3IgYSBiYWRnZSBvbiBpdHMgb3duIGlzIG5vdCBjb21wbGV0aW9uIGFuZCBub3QgY3JlZGl0LiIsImNyaXRlcmlhIjp7Im5hcnJhdGl2ZSI6IlZlcmlmaWVkIG1hcmtzOiBMZWFuIENhbnZhcyAmIE1hcmtldCBWYWxpZGF0aW9uIERvc3NpZXIgKDUwJSksIFZlbnR1cmUgVmFsdWF0aW9uICYgRXhlY3V0aXZlIFBpdGNoIERlZmVuY2UgKDUwJSkuIENvbXBsZXRpb24gY29uZmlybWVkIGJ5IHRoZSBkZXB1dHkgZGVhbi4ifSwiaHVtYW5Db2RlIjoiVVRFTS1NQy0yMDI2LTAwMTEiLCJ0YWciOlsiVmFsaWRhdGUgdGVjaG5vbG9neSBidXNpbmVzcyBtb2RlbHMgYW5kIGZvcm11bGF0ZSBnby10by1tYXJrZXQgc3RyYXRlZ2llcy4iLCJDb25zdHJ1Y3QgZmluYW5jaWFsIHByb2plY3Rpb25zIGFuZCBkZWZlbmQgYW4gaW52ZXN0bWVudC1yZWFkeSB2ZW50dXJlIHBpdGNoLiJdLCJjcmVkaXRzQXZhaWxhYmxlIjozfX0sIm1jbXMiOnsiY3JlZGVudGlhbF9udW1iZXIiOiJVVEVNLU1DLTIwMjYtMDAxMSIsImNhdGVnb3J5IjoiVU1DIiwic2x0X2hvdXJzIjoxMjAsImNsYWltcyI6eyJjcmVkaXQiOjMsIm1xZiI6bnVsbCwicXYiOmZhbHNlLCJxdl91bnRpbCI6bnVsbH0sIm1zIjp7Im5hbWUiOiJUZWtub3VzYWhhd2FuIFN0cmF0ZWdpayAmIFBlbmNpcHRhYW4gVXNhaGEgTmlhZ2EiLCJzdGF0ZW1lbnQiOiJJbmkgaWFsYWggVW5idW5kbGVkIE1pY3JvLUNyZWRlbnRpYWwgKFVNQykgZGFyaXBhZGEgTUJBIDUxMTMgU3RyYXRlZ2ljIE1hbmFnZW1lbnQgaW4gVGVjaG5vcHJlbmV1cnNoaXAuIE1lbmFtYXRrYW4ga2VzZWx1cnVoYW4gVU1DIG1lbWJhd2Ega3JlZGl0IGt1cnN1cyBpbmR1ay4gU2F0dSBtb2R1bCBhdGF1IHNhdHUgbGVuY2FuYSBzYWhhamEgYnVrYW4gcGVuYW1hdGFuIGRhbiBidWthbiBrcmVkaXQuIn19fX0.KuXenmrdinbFYHR0DLHlrkfGSiVAk4iGWz3Ynp31uzFOPJrhzBV2GSOb40fa7P6OUlfCaMpyIUV0ekOs-IFKCg','did:utem:codl:vc-2026-01',NULL,NULL,'2026-10-09 10:00:00',NULL,NULL,'2026-10-09 10:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `credentials` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`mcms`@`%`*/ /*!50003 TRIGGER `credentials_keep` BEFORE DELETE ON `credentials` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'credentials and their revisions are never deleted';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `credit_ledger_entries`
--

DROP TABLE IF EXISTS `credit_ledger_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_ledger_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `abc_deposit_id` bigint unsigned NOT NULL,
  `event` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_status` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credits` decimal(5,1) NOT NULL,
  `redemption_event_id` bigint unsigned DEFAULT NULL,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_ledger_entries_abc_deposit_id_foreign` (`abc_deposit_id`),
  KEY `credit_ledger_entries_redemption_event_id_foreign` (`redemption_event_id`),
  KEY `credit_ledger_entries_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `credit_ledger_entries_abc_deposit_id_foreign` FOREIGN KEY (`abc_deposit_id`) REFERENCES `abc_deposits` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `credit_ledger_entries_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `credit_ledger_entries_redemption_event_id_foreign` FOREIGN KEY (`redemption_event_id`) REFERENCES `redemption_events` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_ledger_entries`
--

LOCK TABLES `credit_ledger_entries` WRITE;
/*!40000 ALTER TABLE `credit_ledger_entries` DISABLE KEYS */;
INSERT INTO `credit_ledger_entries` VALUES (1,1,'deposit',NULL,'available',2.0,NULL,12,'2026-09-15 01:42:39'),(2,2,'deposit',NULL,'available',2.0,NULL,12,'2026-09-15 01:42:39'),(3,1,'provisional','available','provisional',2.0,NULL,10,'2026-10-20 01:42:39'),(4,1,'redeemed','provisional','redeemed',2.0,1,10,'2026-10-25 10:00:00'),(5,2,'provisional','available','provisional',2.0,NULL,2,'2026-10-30 10:00:00'),(6,3,'deposit',NULL,'available',2.0,NULL,12,'2026-08-30 10:00:00'),(7,4,'deposit',NULL,'available',2.0,NULL,12,'2026-08-30 10:00:00'),(8,5,'deposit',NULL,'available',2.0,NULL,12,'2026-10-28 10:00:00'),(9,6,'deposit',NULL,'available',3.0,NULL,12,'2026-10-28 10:00:00'),(10,7,'deposit',NULL,'available',3.0,NULL,12,'2026-10-12 10:00:00'),(11,3,'redeemed','available','redeemed',2.0,2,10,'2026-09-12 10:30:00'),(12,7,'redeemed','available','redeemed',3.0,3,25,'2026-10-20 10:30:00');
/*!40000 ALTER TABLE `credit_ledger_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `digital_badges`
--

DROP TABLE IF EXISTS `digital_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_badges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_achievement_id` bigint unsigned NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_id` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_date` date DEFAULT NULL,
  `issued_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `digital_badges_module_achievement_id_unique` (`module_achievement_id`),
  UNIQUE KEY `digital_badges_code_unique` (`code`),
  CONSTRAINT `digital_badges_module_achievement_id_foreign` FOREIGN KEY (`module_achievement_id`) REFERENCES `module_achievements` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `digital_badges`
--

LOCK TABLES `digital_badges` WRITE;
/*!40000 ALTER TABLE `digital_badges` DISABLE KEYS */;
INSERT INTO `digital_badges` VALUES (1,1,'BADGE-CND-M1-01','OB-2026-9812','https://badges.utem.edu.my/ob3/9812','2026-07-17','2026-07-17 01:42:39'),(2,2,'BADGE-CND-M2-01','OB-2026-9813','https://badges.utem.edu.my/ob3/9813','2026-09-05','2026-09-05 01:42:39'),(3,3,'BADGE-CND-M1-02','OB-2026-9814','https://badges.utem.edu.my/ob3/9814','2026-07-17','2026-07-17 01:42:39'),(4,5,'BADGE-DS-M1-01','OB-2026-9901','https://badges.utem.edu.my/ob3/9901','2026-10-30','2026-10-30 01:42:39'),(5,4,'BADGE-CND-M2-02','OB-2026-9815','https://badges.utem.edu.my/ob3/9815','2026-09-05','2026-09-05 10:00:00'),(6,6,'BADGE-DS-M1-02','OB-2026-9820','https://badges.utem.edu.my/ob3/9820','2026-06-25','2026-06-25 10:00:00'),(7,7,'BADGE-DS-M2-02','OB-2026-9821','https://badges.utem.edu.my/ob3/9821','2026-08-20','2026-08-20 10:00:00'),(8,8,'BADGE-DS-M1-03','OB-2026-9822','https://badges.utem.edu.my/ob3/9822','2026-06-26','2026-06-26 10:00:00'),(9,9,'BADGE-DS-M2-03','OB-2026-9823','https://badges.utem.edu.my/ob3/9823','2026-08-20','2026-08-20 10:00:00'),(10,14,'BADGE-SSDLC-M1-01','OB-2026-9830','https://badges.utem.edu.my/ob3/9830','2026-09-20','2026-09-20 10:00:00'),(11,15,'BADGE-SSDLC-M2-01','OB-2026-9831','https://badges.utem.edu.my/ob3/9831','2026-10-20','2026-10-20 10:00:00'),(12,16,'BADGE-SSDLC-M1-02','OB-2026-9832','https://badges.utem.edu.my/ob3/9832','2026-09-21','2026-09-21 10:00:00'),(13,17,'BADGE-SSDLC-M2-02','OB-2026-9833','https://badges.utem.edu.my/ob3/9833','2026-10-20','2026-10-20 10:00:00'),(14,18,'BADGE-SSDLC-M1-03','OB-2026-9834','https://badges.utem.edu.my/ob3/9834','2026-09-25','2026-09-25 10:00:00');
/*!40000 ALTER TABLE `digital_badges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discrepancies`
--

DROP TABLE IF EXISTS `discrepancies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discrepancies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ptj_id` bigint unsigned DEFAULT NULL,
  `subject_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `found_at` timestamp NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discrepancies_key_unique` (`key`),
  KEY `discrepancies_user_id_foreign` (`user_id`),
  KEY `discrepancies_ptj_id_foreign` (`ptj_id`),
  KEY `discrepancies_resolved_by_foreign` (`resolved_by`),
  CONSTRAINT `discrepancies_ptj_id_foreign` FOREIGN KEY (`ptj_id`) REFERENCES `ptjs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `discrepancies_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `discrepancies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discrepancies`
--

LOCK TABLES `discrepancies` WRITE;
/*!40000 ALTER TABLE `discrepancies` DISABLE KEYS */;
INSERT INTO `discrepancies` VALUES (1,'no_credential','passport_ic_mismatch',2,1,'user','2','Student Bee Tan registered passport number in LMS differed by one digit from national IC record.','resolved','2026-10-13 01:42:39','2026-10-15 01:42:39',12),(2,'credit_mismatch','slt_discrepancy',NULL,1,'micro_credential','5','Database Systems module SLT sum (74 hours) does not equal total planned SLT (80 hours).','open','2026-11-02 01:42:39',NULL,NULL),(3,'acr_revoked','cred_revoked_acr_15',20,1,'enrolment','15','Credential UTEM-MC-2026-0009 was revoked on 2026-11-01; verify no active ACR deposit remains.','resolved','2026-11-01 10:15:00','2026-11-01 14:00:00',12),(4,'deposit_mismatch','dep_mismatch_acr_3',1,1,'abc_deposit','3','ACR-2026-0003 carries 2.0 MC credits mapped to 3.0 parent course credits in TR-2026-0003.','open','2026-11-03 09:00:00',NULL,NULL);
/*!40000 ALTER TABLE `discrepancies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disposal_records`
--

DROP TABLE IF EXISTS `disposal_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `disposal_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cutoff` date NOT NULL,
  `row_count` int unsigned NOT NULL,
  `retention_years` smallint unsigned NOT NULL,
  `approved_by` bigint unsigned NOT NULL,
  `approved_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `disposal_records_approved_by_foreign` (`approved_by`),
  CONSTRAINT `disposal_records_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disposal_records`
--

LOCK TABLES `disposal_records` WRITE;
/*!40000 ALTER TABLE `disposal_records` DISABLE KEYS */;
INSERT INTO `disposal_records` VALUES (1,'report_runs','2021-11-04',14820,5,12,'2026-09-04 01:42:39'),(2,'notifications','2024-11-01',4120,2,12,'2026-10-05 10:00:00');
/*!40000 ALTER TABLE `disposal_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrolment_status_log`
--

DROP TABLE IF EXISTS `enrolment_status_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enrolment_status_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enrolment_id` bigint unsigned NOT NULL,
  `from_status` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `changed_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `enrolment_status_log_enrolment_id_foreign` (`enrolment_id`),
  KEY `enrolment_status_log_changed_by_foreign` (`changed_by`),
  CONSTRAINT `enrolment_status_log_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `enrolment_status_log_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrolment_status_log`
--

LOCK TABLES `enrolment_status_log` WRITE;
/*!40000 ALTER TABLE `enrolment_status_log` DISABLE KEYS */;
INSERT INTO `enrolment_status_log` VALUES (1,1,'registered','enrolled',12,'Online payment confirmed.','2026-05-13 01:42:39'),(2,1,'enrolled','completed',10,'All assessment results verified and completion confirmed.','2026-09-10 01:42:39'),(3,2,'registered','enrolled',12,'Online payment confirmed.','2026-05-14 01:42:39'),(4,2,'enrolled','completed',10,'Completion confirmed.','2026-09-10 01:42:39'),(5,7,'enrolled','withdrawn',5,'Learner requested withdrawal due to job relocation.','2026-10-25 01:42:39'),(6,9,'registered','enrolled',1,'Payment succeeded via FPX.','2026-04-20 10:05:00'),(7,9,'enrolled','completed',10,'All Data Structures modules and project passed.','2026-08-25 10:00:00'),(8,10,'registered','enrolled',2,'Payment succeeded via card.','2026-04-21 10:05:00'),(9,10,'enrolled','completed',10,'Completion confirmed by Deputy Dean.','2026-08-25 10:00:00'),(10,11,'enrolled','completed',10,'Completion confirmed by Deputy Dean.','2026-08-25 10:00:00'),(11,12,'enrolled','completed',10,'Completion confirmed by Deputy Dean.','2026-08-25 10:00:00'),(12,13,'enrolled','completed',10,'Completion confirmed for SSDLC.','2026-10-25 10:00:00'),(13,14,'enrolled','completed',10,'Completion confirmed for SSDLC.','2026-10-25 10:00:00'),(14,15,'enrolled','completed',10,'Completion confirmed for SSDLC.','2026-10-25 10:00:00'),(15,16,'enrolled','not_completed',10,'Did not pass Module 2 DevSecOps capstone.','2026-10-25 10:00:00'),(16,17,'enrolled','completed',10,'Completion confirmed for OOP.','2026-10-05 10:00:00'),(17,18,'enrolled','completed',10,'Completion confirmed for OOP.','2026-10-05 10:00:00'),(18,19,'enrolled','completed',25,'Completion confirmed for MBA Technopreneurship.','2026-10-08 10:00:00'),(19,20,'enrolled','completed',25,'Completion confirmed for Deep Learning.','2026-10-30 10:00:00'),(20,23,'enrolled','held',12,'Cohort 2026/S1 placed on infrastructure hold.','2026-10-20 10:00:00'),(21,28,'registered','cancelled',12,'Cohort cancelled before start date.','2026-08-26 10:00:00');
/*!40000 ALTER TABLE `enrolment_status_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrolments`
--

DROP TABLE IF EXISTS `enrolments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enrolments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `offering_id` bigint unsigned NOT NULL,
  `mc_version_id` bigint unsigned NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee_amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `ack_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ack_versions` json NOT NULL,
  `withdraw_reason` text COLLATE utf8mb4_unicode_ci,
  `status_changed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `progress_percent` tinyint unsigned DEFAULT NULL,
  `modules_completed` smallint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrolments_user_id_offering_id_unique` (`user_id`,`offering_id`),
  UNIQUE KEY `enrolments_reference_unique` (`reference`),
  KEY `enrolments_mc_version_id_foreign` (`mc_version_id`),
  KEY `enrolments_offering_id_status_index` (`offering_id`,`status`),
  CONSTRAINT `enrolments_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `enrolments_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `enrolments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrolments`
--

LOCK TABLES `enrolments` WRITE;
/*!40000 ALTER TABLE `enrolments` DISABLE KEYS */;
INSERT INTO `enrolments` VALUES (1,'ENR-2026-0001',1,1,2,'completed',150.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-09-10 01:42:39','2026-05-13 01:42:39','2026-11-04 01:42:39',100,2),(2,'ENR-2026-0002',2,1,2,'completed',150.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\"}',NULL,'2026-09-10 01:42:39','2026-05-14 01:42:39','2026-11-04 01:42:39',100,2),(3,'ENR-2026-0003',1,2,1,'enrolled',0.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\"}',NULL,'2026-10-05 01:42:39','2026-09-30 01:42:39','2026-11-04 01:42:39',50,1),(4,'ENR-2026-0004',2,2,1,'enrolled',0.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\"}',NULL,'2026-10-05 01:42:39','2026-10-01 01:42:39','2026-11-04 01:42:39',50,1),(5,'ENR-2026-0005',3,2,1,'enrolled',0.00,'I accept all guidelines.','{\"pdpa\": \"v2.1\"}',NULL,'2026-10-05 01:42:39','2026-10-02 01:42:39','2026-11-04 01:42:39',20,0),(6,'ENR-2026-0006',4,2,1,'enrolled',0.00,'I accept all guidelines.','{\"pdpa\": \"v2.1\"}',NULL,'2026-10-05 01:42:39','2026-10-03 01:42:39','2026-11-04 01:42:39',10,0),(7,'ENR-2026-0007',5,2,1,'withdrawn',0.00,'I accept guidelines.','{\"pdpa\": \"v2.1\"}','Career relocation to Singapore.','2026-10-25 01:42:39','2026-09-30 01:42:39','2026-11-04 01:42:39',NULL,NULL),(8,'ENR-2026-0008',1,3,2,'registered',150.00,'I agree.','{\"pdpa\": \"v2.1\"}',NULL,'2026-11-01 01:42:39','2026-11-01 01:42:39','2026-11-04 01:42:39',NULL,NULL),(9,'ENR-2026-0009',1,12,1,'completed',150.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-08-25 10:00:00','2026-04-20 10:00:00','2026-11-05 09:00:00',100,2),(10,'ENR-2026-0010',2,12,1,'completed',150.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-08-25 10:00:00','2026-04-21 10:00:00','2026-11-05 09:00:00',100,2),(11,'ENR-2026-0011',3,12,1,'completed',150.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-08-25 10:00:00','2026-04-22 10:00:00','2026-11-05 09:00:00',100,2),(12,'ENR-2026-0012',19,12,1,'completed',150.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-08-25 10:00:00','2026-04-23 10:00:00','2026-11-05 09:00:00',100,2),(13,'ENR-2026-0013',1,13,9,'completed',200.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-25 10:00:00','2026-08-10 10:00:00','2026-11-05 09:00:00',100,2),(14,'ENR-2026-0014',18,13,9,'completed',200.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-25 10:00:00','2026-08-11 10:00:00','2026-11-05 09:00:00',100,2),(15,'ENR-2026-0015',20,13,9,'completed',200.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-25 10:00:00','2026-08-12 10:00:00','2026-11-05 09:00:00',100,2),(16,'ENR-2026-0016',4,13,9,'not_completed',200.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-25 10:00:00','2026-08-13 10:00:00','2026-11-05 09:00:00',50,1),(17,'ENR-2026-0017',1,14,18,'completed',180.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-05 10:00:00','2026-06-15 10:00:00','2026-11-05 09:00:00',100,2),(18,'ENR-2026-0018',3,14,18,'completed',180.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-05 10:00:00','2026-06-16 10:00:00','2026-11-05 09:00:00',100,2),(19,'ENR-2026-0019',19,15,21,'completed',300.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-08 10:00:00','2026-07-18 10:00:00','2026-11-05 09:00:00',100,2),(20,'ENR-2026-0020',18,16,24,'completed',250.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-30 10:00:00','2026-08-05 10:00:00','2026-11-05 09:00:00',100,2),(21,'ENR-2026-0021',18,2,1,'enrolled',0.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-05 10:00:00','2026-10-04 10:00:00','2026-11-05 09:00:00',75,1),(22,'ENR-2026-0022',20,5,3,'enrolled',0.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-20 10:00:00','2026-10-18 10:00:00','2026-11-05 09:00:00',60,0),(23,'ENR-2026-0023',3,6,2,'held',150.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-10-20 10:00:00','2026-09-12 10:00:00','2026-11-05 09:00:00',40,0),(24,'ENR-2026-0024',19,4,7,'enrolled',200.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-11-01 10:00:00','2026-11-01 09:30:00','2026-11-05 09:00:00',0,0),(25,'ENR-2026-0025',18,7,13,'registered',180.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-11-02 10:00:00','2026-11-02 10:00:00','2026-11-05 09:00:00',0,0),(26,'ENR-2026-0026',20,9,15,'enrolled',220.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-11-03 10:00:00','2026-11-03 09:30:00','2026-11-05 09:00:00',15,0),(27,'ENR-2026-0027',2,11,17,'registered',250.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}',NULL,'2026-11-04 10:00:00','2026-11-04 10:00:00','2026-11-05 09:00:00',0,0),(28,'ENR-2026-0028',4,19,3,'cancelled',0.00,'I accept all university course guidelines.','{\"pdpa\": \"v2.1\", \"academic_integrity\": \"v1.0\"}','Cohort 2026/LE-CANC cancelled due to low enrolment.','2026-08-26 10:00:00','2026-08-15 10:00:00','2026-11-05 09:00:00',0,0);
/*!40000 ALTER TABLE `enrolments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equivalence_assessments`
--

DROP TABLE IF EXISTS `equivalence_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equivalence_assessments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_line_id` bigint unsigned NOT NULL,
  `target_outcome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_outcome_id` bigint unsigned DEFAULT NULL,
  `match` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `evaluator_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equivalence_assessments_transfer_line_id_target_outcome_unique` (`transfer_line_id`,`target_outcome`),
  KEY `equivalence_assessments_source_outcome_id_foreign` (`source_outcome_id`),
  KEY `equivalence_assessments_evaluator_id_foreign` (`evaluator_id`),
  CONSTRAINT `equivalence_assessments_evaluator_id_foreign` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `equivalence_assessments_source_outcome_id_foreign` FOREIGN KEY (`source_outcome_id`) REFERENCES `learning_outcomes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `equivalence_assessments_transfer_line_id_foreign` FOREIGN KEY (`transfer_line_id`) REFERENCES `transfer_lines` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equivalence_assessments`
--

LOCK TABLES `equivalence_assessments` WRITE;
/*!40000 ALTER TABLE `equivalence_assessments` DISABLE KEYS */;
INSERT INTO `equivalence_assessments` VALUES (1,1,'Evaluate cloud infrastructure and orchestration algorithms.',5,'full','Direct alignment with course syllabus.',7),(2,1,'Design divide-and-conquer algorithms',4,'partial','Covered via distributed container scheduling labs.',7),(3,3,'Explain core data structures',1,'full','100% syllabus match with BITS 1123 CLO1.',6),(4,3,'Implement algorithms in code',2,'full','100% syllabus match with BITS 1123 CLO2.',6),(5,3,'Analyse complexity',3,'full','100% syllabus match with BITS 1123 CLO3.',6),(6,4,'Formulate strategic plans',29,'full','Unbundled directly from MBA 5113.',24);
/*!40000 ALTER TABLE `equivalence_assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equivalence_criteria`
--

DROP TABLE IF EXISTS `equivalence_criteria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equivalence_criteria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_line_id` bigint unsigned NOT NULL,
  `criterion` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `finding` text COLLATE utf8mb4_unicode_ci,
  `result` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `evaluator_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equivalence_criteria_transfer_line_id_criterion_unique` (`transfer_line_id`,`criterion`),
  KEY `equivalence_criteria_evaluator_id_foreign` (`evaluator_id`),
  CONSTRAINT `equivalence_criteria_evaluator_id_foreign` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `equivalence_criteria_transfer_line_id_foreign` FOREIGN KEY (`transfer_line_id`) REFERENCES `transfer_lines` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equivalence_criteria`
--

LOCK TABLES `equivalence_criteria` WRITE;
/*!40000 ALTER TABLE `equivalence_criteria` DISABLE KEYS */;
INSERT INTO `equivalence_criteria` VALUES (1,1,'outcomes','Criterion \'outcomes\' verified >= 80% equivalence.','equivalent',7),(2,1,'credit_slt','Criterion \'credit_slt\' verified >= 80% equivalence.','equivalent',7),(3,1,'mqf','Criterion \'mqf\' verified >= 80% equivalence.','equivalent',7),(4,1,'content','Criterion \'content\' verified >= 80% equivalence.','equivalent',7),(5,1,'activities','Criterion \'activities\' verified >= 80% equivalence.','equivalent',7),(6,1,'assessment','Criterion \'assessment\' verified >= 80% equivalence.','equivalent',7),(7,1,'accreditation','Criterion \'accreditation\' verified >= 80% equivalence.','equivalent',7),(8,1,'currency','Criterion \'currency\' verified >= 80% equivalence.','equivalent',7),(9,1,'professional','Not regulated by external professional body.','na',7),(10,3,'outcomes','Criterion \'outcomes\' verified >= 80% equivalence.','equivalent',6),(11,3,'credit_slt','Criterion \'credit_slt\' verified >= 80% equivalence.','equivalent',6),(12,3,'mqf','Criterion \'mqf\' verified >= 80% equivalence.','equivalent',6),(13,3,'content','Criterion \'content\' verified >= 80% equivalence.','equivalent',6),(14,3,'activities','Criterion \'activities\' verified >= 80% equivalence.','equivalent',6),(15,3,'assessment','Criterion \'assessment\' verified >= 80% equivalence.','equivalent',6),(16,3,'accreditation','Criterion \'accreditation\' verified >= 80% equivalence.','equivalent',6),(17,3,'currency','Criterion \'currency\' verified >= 80% equivalence.','equivalent',6),(18,3,'professional','Not regulated by external professional body.','na',6),(19,4,'outcomes','Criterion \'outcomes\' verified >= 80% equivalence.','equivalent',24),(20,4,'credit_slt','Criterion \'credit_slt\' verified >= 80% equivalence.','equivalent',24),(21,4,'mqf','Criterion \'mqf\' verified >= 80% equivalence.','equivalent',24),(22,4,'content','Criterion \'content\' verified >= 80% equivalence.','equivalent',24),(23,4,'activities','Criterion \'activities\' verified >= 80% equivalence.','equivalent',24),(24,4,'assessment','Criterion \'assessment\' verified >= 80% equivalence.','equivalent',24),(25,4,'accreditation','Criterion \'accreditation\' verified >= 80% equivalence.','equivalent',24),(26,4,'currency','Criterion \'currency\' verified >= 80% equivalence.','equivalent',24),(27,4,'professional','Not regulated by external professional body.','na',24);
/*!40000 ALTER TABLE `equivalence_criteria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `export_files`
--

DROP TABLE IF EXISTS `export_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `export_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offering_id` bigint unsigned NOT NULL,
  `template_version` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `row_count` int unsigned NOT NULL,
  `run_by` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `export_files_offering_id_foreign` (`offering_id`),
  KEY `export_files_run_by_foreign` (`run_by`),
  CONSTRAINT `export_files_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `export_files_run_by_foreign` FOREIGN KEY (`run_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `export_files`
--

LOCK TABLES `export_files` WRITE;
/*!40000 ALTER TABLE `export_files` DISABLE KEYS */;
INSERT INTO `export_files` VALUES (1,1,'v1.2','exports/2026/cnd_cohort1_mqa_summary.xlsx','c3d4e5f6789012345678901234567890123456789012345678901234567890ab',14,12,'2026-09-25 01:42:39'),(2,2,'v1.2','exports/2026/ds_cohort2_mark_template.csv','fb4a445769f1c4eee0c82c15d4a535e808566a4804ab6b53bd7cda5b9fb4dda6',5,6,'2026-10-28 10:00:00'),(3,12,'v1.2','exports/2026/ds_cohort1_final_marks.csv','3115e829cb3e6778ce0d3c2da7e4f0a827a2716475a67899029f67e7770ffb00',4,6,'2026-08-21 10:00:00'),(4,13,'v1.2','exports/2026/ssdlc_cohort1_final_marks.csv','368e828f1998628138e18c348515ebe3cf03186af4f6c409d2f82a9fe1f1e47d',4,7,'2026-10-21 10:00:00'),(5,14,'v1.2','exports/2026/oop_cohort1_final_marks.csv','9f1a72d040d91a370783c064474391728f9579025bf84eab63be34ed12e4f52d',2,6,'2026-10-01 10:00:00');
/*!40000 ALTER TABLE `export_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
INSERT INTO `failed_jobs` VALUES (1,'failed-uuid-101','database','credentials','{\"displayName\":\"App\\\\Jobs\\\\IssueDigitalBadgeJob\",\"data\":{\"module_id\":1,\"user_id\":5}}','RuntimeException: Remote badge server timeout (HTTP 504 Gateway Timeout) at /var/www/html/app/Modules/Credentials/BadgeIssuer.php:84','2026-11-03 23:42:39'),(2,'failed-uuid-102','database','notifications','{\"displayName\":\"App\\\\Jobs\\\\SendEmailNotificationJob\",\"data\":{\"notification_id\":15}}','RuntimeException: SMTP 421 4.7.0 Remote mail server deferred connection at /var/www/html/app/Modules/Shared/MailAdapter.php:52','2026-11-04 07:30:00');
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_responses`
--

DROP TABLE IF EXISTS `feedback_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_responses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offering_id` bigint unsigned NOT NULL,
  `enrolment_id` bigint unsigned NOT NULL,
  `answers` json NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feedback_responses_enrolment_id_unique` (`enrolment_id`),
  KEY `feedback_responses_offering_id_foreign` (`offering_id`),
  CONSTRAINT `feedback_responses_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `feedback_responses_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_responses`
--

LOCK TABLES `feedback_responses` WRITE;
/*!40000 ALTER TABLE `feedback_responses` DISABLE KEYS */;
INSERT INTO `feedback_responses` VALUES (1,1,1,'{\"ease\": 5, \"admin\": 4, \"depth\": 4, \"clarity\": 5, \"content\": 5, \"support\": 5, \"fairness\": 5, \"lecturer\": 5, \"platform\": 5, \"practice\": 5, \"workload\": 4, \"recommend\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5, \"reliability\": 5, \"feedback_quality\": 4}','Exceptional course. The Kubernetes deployment project directly improved my daily work at my company.','2026-09-07 01:42:39'),(2,1,2,'{\"ease\": 4, \"admin\": 3, \"depth\": 3, \"clarity\": 4, \"content\": 4, \"support\": 4, \"fairness\": 4, \"lecturer\": 4, \"platform\": 4, \"practice\": 4, \"workload\": 3, \"recommend\": 4, \"relevance\": 4, \"activities\": 4, \"assessment\": 4, \"reliability\": 4, \"feedback_quality\": 3}','Very comprehensive curriculum, appreciated the weekly live consultation clinics.','2026-09-08 01:42:39'),(3,12,9,'{\"ease\": 5, \"admin\": 4, \"depth\": 4, \"clarity\": 5, \"content\": 5, \"support\": 5, \"fairness\": 5, \"lecturer\": 5, \"platform\": 5, \"practice\": 5, \"workload\": 4, \"recommend\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5, \"reliability\": 5, \"feedback_quality\": 4}','Clear explanation of tree balancing and graph heuristics; lab test bench was very helpful.','2026-08-21 10:00:00'),(4,12,10,'{\"ease\": 4, \"admin\": 3, \"depth\": 3, \"clarity\": 4, \"content\": 4, \"support\": 4, \"fairness\": 4, \"lecturer\": 4, \"platform\": 4, \"practice\": 4, \"workload\": 3, \"recommend\": 4, \"relevance\": 4, \"activities\": 4, \"assessment\": 4, \"reliability\": 4, \"feedback_quality\": 3}','Great course structure, though week 6 graph assignment was challenging.','2026-08-21 14:00:00'),(5,12,11,'{\"ease\": 4, \"admin\": 3, \"depth\": 3, \"clarity\": 4, \"content\": 4, \"support\": 4, \"fairness\": 4, \"lecturer\": 4, \"platform\": 4, \"practice\": 4, \"workload\": 3, \"recommend\": 4, \"relevance\": 4, \"activities\": 4, \"assessment\": 4, \"reliability\": 4, \"feedback_quality\": 3}','Well-paced video lectures and responsive Teams clinic.','2026-08-22 09:00:00'),(6,12,12,'{\"ease\": 5, \"admin\": 4, \"depth\": 4, \"clarity\": 5, \"content\": 5, \"support\": 5, \"fairness\": 5, \"lecturer\": 5, \"platform\": 5, \"practice\": 5, \"workload\": 4, \"recommend\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5, \"reliability\": 5, \"feedback_quality\": 4}','Directly prepared me for technical coding interviews.','2026-08-22 11:00:00'),(7,13,13,'{\"ease\": 5, \"admin\": 4, \"depth\": 4, \"clarity\": 5, \"content\": 5, \"support\": 5, \"fairness\": 5, \"lecturer\": 5, \"platform\": 5, \"practice\": 5, \"workload\": 4, \"recommend\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5, \"reliability\": 5, \"feedback_quality\": 4}','Hands-on GitLab SAST/DAST pipeline labs are industry grade.','2026-10-21 10:00:00'),(8,13,14,'{\"ease\": 5, \"admin\": 4, \"depth\": 4, \"clarity\": 5, \"content\": 5, \"support\": 5, \"fairness\": 5, \"lecturer\": 5, \"platform\": 5, \"practice\": 5, \"workload\": 4, \"recommend\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5, \"reliability\": 5, \"feedback_quality\": 4}','STRIDE threat modelling templates were immediately useful at work.','2026-10-21 15:00:00'),(9,13,16,'{\"ease\": 3, \"admin\": 3, \"depth\": 3, \"clarity\": 3, \"content\": 3, \"support\": 3, \"fairness\": 3, \"lecturer\": 3, \"platform\": 3, \"practice\": 3, \"workload\": 3, \"recommend\": 3, \"relevance\": 3, \"activities\": 3, \"assessment\": 3, \"reliability\": 3, \"feedback_quality\": 3}','Module 2 DAST container setup required more memory than my laptop had.','2026-10-22 09:00:00'),(10,14,17,'{\"ease\": 5, \"admin\": 4, \"depth\": 4, \"clarity\": 5, \"content\": 5, \"support\": 5, \"fairness\": 5, \"lecturer\": 5, \"platform\": 5, \"practice\": 5, \"workload\": 4, \"recommend\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5, \"reliability\": 5, \"feedback_quality\": 4}','SOLID refactoring exercises really sharpened my Java architecture skills.','2026-10-01 10:00:00'),(11,15,19,'{\"ease\": 5, \"admin\": 4, \"depth\": 4, \"clarity\": 5, \"content\": 5, \"support\": 5, \"fairness\": 5, \"lecturer\": 5, \"platform\": 5, \"practice\": 5, \"workload\": 4, \"recommend\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5, \"reliability\": 5, \"feedback_quality\": 4}','Executive mentoring and venture valuation rubric were top notch.','2026-10-03 10:00:00'),(12,16,20,'{\"ease\": 5, \"admin\": 4, \"depth\": 4, \"clarity\": 5, \"content\": 5, \"support\": 5, \"fairness\": 5, \"lecturer\": 5, \"platform\": 5, \"practice\": 5, \"workload\": 4, \"recommend\": 5, \"relevance\": 5, \"activities\": 5, \"assessment\": 5, \"reliability\": 5, \"feedback_quality\": 4}','Loved the RAG and transformer fine-tuning notebooks.','2026-10-27 10:00:00');
/*!40000 ALTER TABLE `feedback_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_files`
--

DROP TABLE IF EXISTS `import_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `offering_id` bigint unsigned NOT NULL,
  `template_version` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `row_count` int unsigned NOT NULL DEFAULT '0',
  `staged_count` int unsigned NOT NULL DEFAULT '0',
  `rejected_count` int unsigned NOT NULL DEFAULT '0',
  `duplicate_of_id` bigint unsigned DEFAULT NULL,
  `run_by` bigint unsigned NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL,
  `staged_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_files_offering_id_foreign` (`offering_id`),
  KEY `import_files_duplicate_of_id_foreign` (`duplicate_of_id`),
  KEY `import_files_run_by_foreign` (`run_by`),
  KEY `import_files_sha256_index` (`sha256`),
  CONSTRAINT `import_files_duplicate_of_id_foreign` FOREIGN KEY (`duplicate_of_id`) REFERENCES `import_files` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `import_files_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `import_files_run_by_foreign` FOREIGN KEY (`run_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_files`
--

LOCK TABLES `import_files` WRITE;
/*!40000 ALTER TABLE `import_files` DISABLE KEYS */;
INSERT INTO `import_files` VALUES (1,'results',1,'v1.2','cnd_cohort1_final_results.csv','imports/2026/cnd_cohort1_final_results.csv','a1b2c3d4e5f67890123456789012345678901234567890123456789012345678',15,14,1,NULL,6,'partly_accepted',NULL,'2026-08-30 01:42:39','2026-08-30 01:42:39'),(2,'badges',1,'v1.2','cnd_badges_export.csv','imports/2026/cnd_badges_export.csv','b2c3d4e5f678901234567890123456789012345678901234567890123456789a',14,14,0,NULL,6,'accepted',NULL,'2026-09-05 01:42:39','2026-09-05 01:42:39'),(3,'results',12,'v1.2','ds_2026_1_marks.csv','imports/2026/ds_2026_1_marks.csv','ada3b1f2e1943d15554e095178c522c0192bbbfc6d875d94ec26f73a41c362f4',4,4,0,NULL,6,'accepted',NULL,'2026-08-22 09:30:00','2026-08-22 09:30:00'),(4,'results',13,'v1.2','ssdlc_2026_1_marks.csv','imports/2026/ssdlc_2026_1_marks.csv','5fc0d97963ef3ef322e999677f855eb5781688e647e45d3aa596bc5f54add4d7',5,4,1,NULL,7,'partly_accepted',NULL,'2026-10-22 10:00:00','2026-10-22 10:00:00'),(5,'results',2,'v1.1','ds_2026_2_corrupted.csv','imports/2026/ds_2026_2_corrupted.csv','526568ea1633b53e301976c4047d2669534451a7cdacafafed3b6c7c47be4556',5,0,5,NULL,6,'rejected','Template version mismatch: expected v1.2 header columns.','2026-10-29 11:00:00',NULL),(6,'results',2,'v1.2','ds_2026_2_module1.csv','imports/2026/ds_2026_2_module1.csv','f6b1f28d835c3cb80f47f08f214517806cdb7588dfec0651b4920143667082d5',5,4,1,NULL,6,'checked',NULL,'2026-11-03 13:50:00','2026-11-03 13:50:00');
/*!40000 ALTER TABLE `import_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_row_errors`
--

DROP TABLE IF EXISTS `import_row_errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_row_errors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `import_file_id` bigint unsigned NOT NULL,
  `line` int unsigned NOT NULL,
  `learner_id` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `import_row_errors_import_file_id_foreign` (`import_file_id`),
  CONSTRAINT `import_row_errors_import_file_id_foreign` FOREIGN KEY (`import_file_id`) REFERENCES `import_files` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_row_errors`
--

LOCK TABLES `import_row_errors` WRITE;
/*!40000 ALTER TABLE `import_row_errors` DISABLE KEYS */;
INSERT INTO `import_row_errors` VALUES (1,1,15,'B032410999','Learner ID not enrolled in offering cohort 2026/1.'),(2,4,5,'950815-10-9999','Score 105.00 exceeds maximum mark 100.00 for component owasp_lab.'),(3,5,1,'HEADER','Missing required column csv_key: project_mark.'),(4,6,4,'B032410005','Learner status is withdrawn; marks cannot be staged.');
/*!40000 ALTER TABLE `import_row_errors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `integration_calls`
--

DROP TABLE IF EXISTS `integration_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `integration_calls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `adapter` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `operation` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_ref` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL DEFAULT '0',
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `next_attempt_at` timestamp NULL DEFAULT NULL,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `integration_calls_adapter_external_ref_unique` (`adapter`,`external_ref`),
  KEY `integration_calls_status_next_attempt_at_index` (`status`,`next_attempt_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `integration_calls`
--

LOCK TABLES `integration_calls` WRITE;
/*!40000 ALTER TABLE `integration_calls` DISABLE KEYS */;
INSERT INTO `integration_calls` VALUES (1,'learning_platform','sync_catalogue','UCREDS-REQ-89471','{\"method\": \"GET\", \"endpoint\": \"/v2/courses\"}','ok',1,NULL,NULL,'2026-11-03 01:42:39','2026-11-03 01:42:39'),(2,'abc','batch_deposit','ABC-REQ-10293','{\"count\": 2, \"batch_id\": \"ABC-BATCH-2026-001\"}','ok',1,NULL,NULL,'2026-09-15 01:42:39','2026-09-15 01:42:39'),(3,'learning_platform','issue_assertion','BADGE-REQ-5512','{\"recipient\": \"amir.hakimi@learner.example.test\"}','failed',3,'HTTP 504: Remote OpenBadge issuer timeout.',NULL,'2026-11-03 23:42:39','2026-11-03 23:42:39'),(4,'sso','verify_staff_token','SSO-2026-8812','{\"uid\": \"aina@utem.test\"}','ok',1,NULL,NULL,'2026-11-04 08:00:00','2026-11-04 08:00:00'),(5,'payment','fpx_callback','FPX-20261101-013','{\"ref\": \"PAY-2026-017\", \"amount\": \"200.00\"}','ok',1,NULL,NULL,'2026-11-01 09:35:00','2026-11-01 09:35:00'),(6,'abc','batch_deposit','ABC-REQ-10410','{\"batch\": \"ABC-BATCH-2026-003\", \"count\": 2}','ok',1,NULL,NULL,'2026-10-28 10:05:00','2026-10-28 10:05:00'),(7,'qvc_mqa','submit_qv_dossier','MQA-SUB-2026-140','{\"ref\": \"MQA/QV/2026/140\", \"qv_record_id\": 9}','ok',1,NULL,NULL,'2026-11-01 11:00:00','2026-11-01 11:00:00'),(8,'sis','post_credit_exemption','SMP-EX-2026-091','{\"course\": \"BITS 1123\", \"matric\": \"B032410001\"}','ok',1,NULL,NULL,'2026-09-12 11:00:00','2026-09-12 11:00:00'),(9,'email','send_revocation_notice','SMTP-ERR-421','{\"to\": \"tan.li@learner.example.test\"}','retrying',2,'421 4.7.0 Remote mail server temporarily deferred connection.','2026-11-05 12:00:00','2026-11-04 07:30:00','2026-11-01 10:05:00'),(10,'abc','sync_expiry_status','ABC-SYNC-2026-1104','{\"ptj_id\": 1}','pending',0,NULL,'2026-11-06 00:00:00',NULL,'2026-11-04 18:00:00');
/*!40000 ALTER TABLE `integration_calls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `integrity_cases`
--

DROP TABLE IF EXISTS `integrity_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `integrity_cases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offering_id` bigint unsigned NOT NULL,
  `enrolment_id` bigint unsigned NOT NULL,
  `opened_by` bigint unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `outcome` text COLLATE utf8mb4_unicode_ci,
  `closed_by` bigint unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `integrity_cases_offering_id_foreign` (`offering_id`),
  KEY `integrity_cases_enrolment_id_foreign` (`enrolment_id`),
  KEY `integrity_cases_opened_by_foreign` (`opened_by`),
  KEY `integrity_cases_closed_by_foreign` (`closed_by`),
  CONSTRAINT `integrity_cases_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `integrity_cases_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `integrity_cases_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `integrity_cases_opened_by_foreign` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `integrity_cases`
--

LOCK TABLES `integrity_cases` WRITE;
/*!40000 ALTER TABLE `integrity_cases` DISABLE KEYS */;
INSERT INTO `integrity_cases` VALUES (1,2,6,6,'Moss similarity index 89% with public GitHub repository.','open',NULL,NULL,NULL,'2026-10-31 01:42:39'),(2,13,16,7,'Suspected shared API token in Module 2 DAST pipeline run.','closed','Cleared of plagiarism; learner penalized 10% for improper environment variable hygiene.',10,'2026-10-23 16:00:00','2026-10-18 10:00:00'),(3,12,11,6,'Routine proctoring webcam disconnect during Module 1 test.','closed','Verified via secondary screen recording and brief oral viva; no misconduct.',10,'2026-06-24 14:00:00','2026-06-20 10:00:00');
/*!40000 ALTER TABLE `integrity_cases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
INSERT INTO `job_batches` VALUES ('batch-2026-11-001','Issue Batch Credentials',4,0,1,'[\"failed-uuid-101\"]','{\"cohort_id\":1}',NULL,1793752959,1793753559),('batch-2026-11-002','Generate Cohort 2026/1-DS Verifiable Credentials',4,0,0,'[]','{\"offering_id\":12}',NULL,1787738400,1787738460);
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (1,'default','{\"displayName\":\"App\\\\Jobs\\\\SyncUcredsCatalogue\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"data\":{\"commandName\":\"App\\\\Jobs\\\\SyncUcredsCatalogue\"}}',0,NULL,1793757159,1793756559),(2,'credentials','{\"displayName\":\"App\\\\Jobs\\\\RenderW3cJwtCredential\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"data\":{\"enrolment_id\":1}}',0,NULL,1793757759,1793756559),(3,'default','{\"displayName\":\"App\\\\Jobs\\\\DispatchAbcBatchDeposit\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"data\":{\"batch_id\":3}}',0,NULL,1793800000,1793799000);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `learning_outcomes`
--

DROP TABLE IF EXISTS `learning_outcomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_outcomes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_version_id` bigint unsigned NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_en` text COLLATE utf8mb4_unicode_ci,
  `text_ms` text COLLATE utf8mb4_unicode_ci,
  `sort_order` smallint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_outcomes_mc_version_id_foreign` (`mc_version_id`),
  CONSTRAINT `learning_outcomes_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_outcomes`
--

LOCK TABLES `learning_outcomes` WRITE;
/*!40000 ALTER TABLE `learning_outcomes` DISABLE KEYS */;
INSERT INTO `learning_outcomes` VALUES (1,1,'LO1','Implement contiguous and linked data structures in software.','Melaksanakan struktur data bersebelahan dan berpaut dalam perisian.',1),(2,1,'LO2','Apply tree traversal and graph traversal algorithms to solve complex searching problems.','Mengaplikasikan algoritma lintasan pokok dan graf untuk menyelesaikan masalah carian.',2),(3,1,'LO3','Evaluate asymptotic time and memory complexity of computer algorithms.','Menilai kerumitan masa dan ingatan asimtotik algoritma komputer.',3),(4,2,'LO1','Package cloud-ready container images following OCI specifications.','Membungkus imej kontena sedia awan mengikut spesifikasi OCI.',1),(5,2,'LO2','Deploy resilient microservices workloads onto Kubernetes clusters.','Menyebarkan beban kerja mikroperkhidmatan ke kluster Kubernetes.',2),(6,3,'LO1','Demonstrate situational leadership and effective team negotiation.','Menunjukkan kepimpinan situasi dan rundingan pasukan yang berkesan.',1),(7,4,'LO1','Detect security anomalies and execute initial response playbook.','Mengesan anomali keselamatan dan melaksanakan buku panduan tindak balas awal.',1),(8,5,'LO1','Normalize database relations up to Boyce-Codd Normal Form.','Menormalkan hubungan pangkalan data sehingga Bentuk Normal Boyce-Codd.',1),(9,6,'LO1','Build predictive linear models using Python and statistical toolkits.','Membina model linear ramalan menggunakan kit alat statistik Python.',1),(10,7,'LO1','Apply DMAIC metrics to identify and eliminate manufacturing bottlenecks.','Mengaplikasikan metrik DMAIC untuk mengenal pasti dan menghapuskan kekangan.',1),(11,13,'LO1','Analyze complex multi-loop circuits using network theorems.','Menganalisis litar kompleks menggunakan teorem rangkaian.',1),(12,14,'LO1','Calculate internal reactions and stresses in mechanical structures.','Mengira tindak balas dalaman dan tegasan dalam struktur mekanikal.',1),(13,15,'LO1','Design low-power wireless sensor communication protocols.','Mereka bentuk protokol komunikasi sensor wayarles kuasa rendah.',1),(14,16,'LO1','Evaluate machining tolerances and surface roughness against standards.','Menilai toleransi pemesinan dan kekasaran permukaan mengikut piawaian.',1),(15,17,'LO1','Execute statistical exploratory analyses on high-dimensional datasets.','Melaksanakan analisis penerokaan statistik ke atas set data.',1),(16,5,'LO2','Construct execution-plan optimized SQL queries with composite indexes.','Membina pertanyaan SQL dioptimumkan pelan pelaksanaan dengan indeks komposit.',2),(17,5,'LO3','Implement ACID transaction isolation levels and deadlock recovery.','Melaksanakan tahap pengasingan transaksi ACID dan pemulihan kebuntuan.',3),(18,8,'LO1','Implement memory-efficient linear and hash-based data structures.','Melaksanakan struktur data linear dan berasaskan cincangan yang cekap memori.',1),(19,8,'LO2','Solve shortest-path and spanning-tree network problems.','Menyelesaikan masalah rangkaian laluan terpendek dan pepohon merentang.',2),(20,9,'LO1','Perform STRIDE threat modelling and remediate OWASP Top 10 vulnerabilities.','Melaksanakan pemodelan ancaman STRIDE dan memulihkan kelemahan OWASP Top 10.',1),(21,9,'LO2','Integrate automated SAST and DAST security gates into CI/CD pipelines.','Mengintegrasikan gerbang keselamatan SAST dan DAST automatik ke dalam CI/CD.',2),(22,10,'LO1','Formulate linear and integer optimization models for resource allocation.','Merumuskan model pengoptimuman linear dan integer untuk peruntukan sumber.',1),(23,11,'LO1','Enforce Kubernetes admission controller and runtime security policies.','Menguatkuasakan pengawal kemasukan Kubernetes dan polisi keselamatan masa jalan.',1),(24,12,'LO1','Design versioned OpenAPI contracts and OAuth2/OIDC gateway policies.','Mereka bentuk kontrak OpenAPI berversi dan polisi gerbang OAuth2/OIDC.',1),(25,18,'LO1','Design modular object-oriented software adhering to SOLID principles.','Mereka bentuk perisian berorientasikan objek modular mematuhi prinsip SOLID.',1),(26,18,'LO2','Verify software reliability using automated JUnit test suites.','Mengesahkan kebolehpercayaan perisian menggunakan suit ujian JUnit automatik.',2),(27,19,'LO1','Simulate grid-synchronized PWM inverter controllers under fault conditions.','Mensimulasikan pengawal penyongsang PWM disegerakkan grid di bawah keadaan kerosakan.',1),(28,20,'LO1','Conduct industrial thermal energy audits and quantify waste-heat recovery.','Menjalankan audit tenaga terma industri dan mengukur pemulihan haba sisa.',1),(29,21,'LO1','Validate technology business models and formulate go-to-market strategies.','Mengesahkan model perniagaan teknologi dan merumuskan strategi pasaran.',1),(30,21,'LO2','Construct financial projections and defend an investment-ready venture pitch.','Membina unjuran kewangan dan mempertahankan pembentangan usaha niaga sedia pelaburan.',2),(31,22,'LO1','Synthesize and verify synchronous digital IPs on FPGA hardware.','Mensintesis dan mengesahkan IP digital segerak pada perkakasan FPGA.',1),(32,23,'LO1','Synchronize PLC ladder logic with a 3D digital twin workcell.','Menyegerakkan logik tangga PLC dengan sel kerja kembar digital 3D.',1),(33,24,'LO1','Train and evaluate deep neural network architectures on domain datasets.','Melatih dan menilai seni bina rangkaian neural mendalam pada set data domain.',1),(34,24,'LO2','Fine-tune transformer models and deploy retrieval-augmented generation (RAG).','Menala halus model transformer dan menyebarkan penjanaan dipertingkat carian (RAG).',2);
/*!40000 ALTER TABLE `learning_outcomes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mc_modules`
--

DROP TABLE IF EXISTS `mc_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mc_modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_version_id` bigint unsigned NOT NULL,
  `seq` smallint unsigned NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_ms` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slt_hours` smallint unsigned NOT NULL DEFAULT '0',
  `is_assessment` tinyint(1) NOT NULL DEFAULT '0',
  `separately_recognisable` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `mc_modules_mc_version_id_seq_index` (`mc_version_id`,`seq`),
  CONSTRAINT `mc_modules_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mc_modules`
--

LOCK TABLES `mc_modules` WRITE;
/*!40000 ALTER TABLE `mc_modules` DISABLE KEYS */;
INSERT INTO `mc_modules` VALUES (1,1,1,'Arrays and Linked Lists','Tatasusunan dan Senarai Berpaut',40,1,1),(2,1,2,'Trees and Graphs','Pepohon dan Graf',40,1,1),(3,2,1,'Containerization with Docker','Pengkontenaan dengan Docker',40,1,1),(4,2,2,'Kubernetes Orchestration & CI/CD','Orkestrasi Kubernetes & CI/CD',40,1,1),(5,3,1,'Team Collaboration and Conflict Management','Kerjasama Pasukan dan Pengurusan Konflik',16,1,0),(6,4,1,'Network Defence & Incident Triage','Pertahanan Rangkaian & Triaj Insiden',80,1,1),(7,5,1,'Relational Schema Design & Normalization','Reka Bentuk Skema Hubungan & Penormalan',40,1,1),(8,5,2,'Advanced Query Optimization & Indexing','Pengoptimuman Pertanyaan Lanjutan & Pengindeksan',40,1,1),(9,6,1,'Exploratory Data Analysis and Visualization','Analisis Data Penerokaan dan Visualisasi',80,1,1),(10,7,1,'DMAIC Principles and Process Capability','Prinsip DMAIC dan Keupayaan Proses',40,1,1),(11,12,1,'OpenAPI Specification & Gateway Policies','Spesifikasi OpenAPI & Polisi Gerbang',40,1,1),(12,13,1,'AC and DC Network Analysis','Analisis Rangkaian AU dan AT',40,1,1),(13,14,1,'Static Equilibrium and Kinematics','Keseimbangan Statik dan Kinematik',40,1,1),(14,15,1,'Embedded Firmware and Sensors','Perisian Tegar Terbenam dan Sensor',40,1,1),(15,16,1,'Advanced Machining & Metrology','Pemesinan Lanjutan & Metrologi',40,1,1),(16,17,1,'Exploratory Data Analysis and Statistics','Analisis Data Penerokaan dan Statistik',40,1,1),(17,8,1,'Linear Structures & Memory Profiling','Struktur Linear & Pemprofilan Memori',40,1,1),(18,8,2,'Graph Algorithms & Shortest Path Heuristics','Algoritma Graf & Heuristik Laluan Terpendek',40,1,1),(19,9,1,'Threat Modelling & OWASP Top 10 Mitigation','Pemodelan Ancaman & Mitigasi OWASP Top 10',40,1,1),(20,9,2,'Automated SAST/DAST Security Pipelines','Saluran Paip Keselamatan SAST/DAST Automatik',40,1,1),(21,10,1,'Linear Programming & Network Flow Models','Pengaturcaraan Linear & Model Aliran Rangkaian',60,1,0),(22,10,2,'Multi-Criteria Decision Analysis in Python','Analisis Keputusan Pelbagai Kriteria dalam Python',60,1,0),(23,11,1,'Container Hardening & Runtime Policy Enforcement',' pengerasan Kontena & Penguatkuasaan Polisi Masa Jalan',80,1,1),(24,12,2,'OAuth2, OIDC & Zero-Trust Microservice Mesh','OAuth2, OIDC & Jaringan Mikroperkhidmatan Sifar-Amanah',40,1,1),(25,18,1,'Encapsulation, Inheritance & Polymorphism in Java','Enkapsulasi, Pewarisan & Polimorfisme dalam Java',60,1,1),(26,18,2,'SOLID Principles, Design Patterns & Unit Testing','Prinsip SOLID, Corak Reka Bentuk & Ujian Unit',60,1,1),(27,19,1,'Grid-Tied Inverter Topologies & PWM Control','Topologi Penyongsang Terikat Grid & Kawalan PWM',40,1,1),(28,19,2,'Battery Energy Storage & Microgrid Stability','Penyimpanan Tenaga Bateri & Kestabilan Mikrogrid',40,1,1),(29,20,1,'Industrial Heat Exchanger & Steam Cycle Analysis','Penukar Haba Industri & Analisis Kitaran Stim',80,1,1),(30,21,1,'Technology Opportunity Recognition & Lean Canvas','Pengecaman Peluang Teknologi & Kanvas Lean',60,1,1),(31,21,2,'Venture Valuation, IP Strategy & Investor Pitch','Penilaian Usaha Niaga, Strategi IP & Pembentangan Pelabur',60,1,1),(32,22,1,'RTL Synthesis & FSM Design in Verilog','Sintesis RTL & Reka Bentuk FSM dalam Verilog',80,1,1),(33,23,1,'Virtual Commissioning with Siemens NX & TIA Portal','Pentauliahan Maya dengan Siemens NX & TIA Portal',24,1,0),(34,24,1,'Convolutional & Recurrent Neural Architectures','Seni Bina Neural Konvolusi & Berulang',60,1,1),(35,24,2,'Attention Mechanisms, Transformers & RAG Deployment','Mekanisme Perhatian, Transformer & Penggunaan RAG',60,1,1),(36,27,1,'IEC 61131-3 Structured Text & OPC-UA Telemetry','Teks Berstruktur IEC 61131-3 & Telemetri OPC-UA',80,1,1);
/*!40000 ALTER TABLE `mc_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mc_versions`
--

DROP TABLE IF EXISTS `mc_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mc_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `micro_credential_id` bigint unsigned NOT NULL,
  `version_no` smallint unsigned NOT NULL DEFAULT '1',
  `proposal_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_ms` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discipline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_language` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offering_period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `need` text COLLATE utf8mb4_unicode_ci,
  `evidence_types` json DEFAULT NULL,
  `added_value` text COLLATE utf8mb4_unicode_ci,
  `planned_slt` smallint unsigned DEFAULT NULL,
  `credit_sought` tinyint(1) NOT NULL DEFAULT '0',
  `parent_course_id` bigint unsigned DEFAULT NULL,
  `credit_model` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assessment_standard` text COLLATE utf8mb4_unicode_ci,
  `integrity_mechanism` text COLLATE utf8mb4_unicode_ci,
  `feedback_method` text COLLATE utf8mb4_unicode_ci,
  `credential_types` json DEFAULT NULL,
  `achievement_level` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qv_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proposed_credit` decimal(5,1) DEFAULT NULL,
  `proposed_mqf_level` tinyint unsigned DEFAULT NULL,
  `ip_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `ip_note` text COLLATE utf8mb4_unicode_ci,
  `submit_response` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `supersedes_id` bigint unsigned DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `change_class` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'initial',
  `change_summary` text COLLATE utf8mb4_unicode_ci,
  `qv_valid_until` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mc_versions_micro_credential_id_version_no_unique` (`micro_credential_id`,`version_no`),
  KEY `mc_versions_parent_course_id_foreign` (`parent_course_id`),
  KEY `mc_versions_updated_by_foreign` (`updated_by`),
  KEY `mc_versions_supersedes_id_foreign` (`supersedes_id`),
  CONSTRAINT `mc_versions_micro_credential_id_foreign` FOREIGN KEY (`micro_credential_id`) REFERENCES `micro_credentials` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `mc_versions_parent_course_id_foreign` FOREIGN KEY (`parent_course_id`) REFERENCES `parent_courses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `mc_versions_supersedes_id_foreign` FOREIGN KEY (`supersedes_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `mc_versions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mc_versions`
--

LOCK TABLES `mc_versions` WRITE;
/*!40000 ALTER TABLE `mc_versions` DISABLE KEYS */;
INSERT INTO `mc_versions` VALUES (1,1,1,'new','approved','Data Structures','Struktur Data','Computing','Software developers & CS undergraduates','online','English','14 weeks','Critical core computer science competence requested by industry.','[\"industry\", \"feedback\", \"programme\"]','Provides stackable academic credit towards accredited UTeM degree programmes.',80,1,1,'credit_based','Minimum 50% pass mark across all summative components with verified identity.','Proctored online assessment and viva verification.','Individual rubric breakdown and automated quiz feedback within 7 days.','[\"certificate\", \"badge\"]','whole','verified','National ID / Passport check and LMS proctoring log.',2.0,6,1,NULL,NULL,'2026-02-04 01:42:39',6,'2026-02-04 01:42:39','2026-11-04 01:42:39',NULL,'2026-05-04','initial',NULL,'2029-11-04'),(2,2,1,'new','approved','Cloud Native Development','Pembangunan Asal Awan','Computing','Cloud practitioners and engineers','online','English','10 weeks','High demand for Kubernetes, microservices and DevOps skills.','[\"industry\", \"standard\", \"technology\"]','Aligned with enterprise cloud and cybersecurity competency frameworks.',80,1,NULL,'topic_based','Competency-based practical lab and capstone project (>= 50%).','Git commit history audit and authenticated sandbox environment.','Line-by-line code review and rubric commentary.','[\"certificate\", \"badge\"]','module','verified','Authenticated learner session and code repository signature.',2.0,4,1,NULL,NULL,'2026-04-04 01:42:39',6,'2026-04-04 01:42:39','2026-11-04 01:42:39',NULL,'2026-06-04','initial',NULL,'2026-12-15'),(3,3,1,'new','approved','Leadership Essentials','Asas Kepimpinan','Management','Supervisors and team leads','blended','English','2 weeks','Executive leadership training for tech professionals.','[\"industry\", \"feedback\"]','Practical leadership toolkit for technical leads and engineering supervisors.',16,0,NULL,NULL,'Reflective leadership portfolio and peer case simulation.','Facilitator-observed synchronous workshop participation.','Facilitator coaching notes.','[\"certificate\"]','whole','na','Attendance verification and portfolio authorship declaration.',0.0,3,1,NULL,NULL,'2026-06-04 01:42:39',6,'2026-06-04 01:42:39','2026-11-04 01:42:39',NULL,'2026-08-04','initial',NULL,NULL),(4,4,1,'new','in_review','Cyber Security Operations Fundamentals','Asas Operasi Keselamatan Siber','Computing','SOC analysts and security trainees','online','English','8 weeks','Demand for junior security analysts in financial sector.','[\"industry\", \"standard\", \"technology\"]','Aligned with enterprise cloud and cybersecurity competency frameworks.',80,1,NULL,'topic_based','Competency-based practical lab and capstone project (>= 50%).','Git commit history audit and authenticated sandbox environment.','Line-by-line code review and rubric commentary.','[\"certificate\", \"badge\"]','module',NULL,'Authenticated learner session and code repository signature.',2.0,4,1,NULL,NULL,'2026-10-25 01:42:39',7,'2026-10-25 01:42:39','2026-11-04 01:42:39',NULL,NULL,'initial',NULL,NULL),(5,5,1,'new','approved','Database Systems','Sistem Pangkalan Data','Computing','Data engineers','online','English','12 weeks','Modern SQL schema design and optimization.','[\"industry\", \"feedback\", \"programme\"]','Provides stackable academic credit towards accredited UTeM degree programmes.',80,1,3,'credit_based','Minimum 50% pass mark across all summative components with verified identity.','Proctored online assessment and viva verification.','Individual rubric breakdown and automated quiz feedback within 7 days.','[\"certificate\", \"badge\"]','whole',NULL,'National ID / Passport check and LMS proctoring log.',2.0,6,1,NULL,NULL,'2026-10-20 01:42:39',6,'2026-10-20 01:42:39','2026-11-04 01:42:39',NULL,NULL,'initial',NULL,NULL),(6,6,1,'new','approved','Applied Data Analytics','Analisis Data Gunaan','Computing','Business analysts','online','English','10 weeks','Data storytelling and predictive analytics.','[\"industry\", \"standard\", \"technology\"]','Aligned with enterprise cloud and cybersecurity competency frameworks.',80,1,NULL,'topic_based','Competency-based practical lab and capstone project (>= 50%).','Git commit history audit and authenticated sandbox environment.','Line-by-line code review and rubric commentary.','[\"certificate\", \"badge\"]','module',NULL,'Authenticated learner session and code repository signature.',2.0,5,1,NULL,NULL,'2026-10-10 01:42:39',6,'2026-10-10 01:42:39','2026-11-04 01:42:39',NULL,NULL,'initial',NULL,NULL),(7,7,1,'new','approved','Lean Six Sigma Yellow Belt','Lean Six Sigma Tali Pinggang Kuning','Engineering Management','Process improvement engineers','online','English','4 weeks','Operational excellence and DMAIC methodologies.','[\"industry\", \"standard\", \"technology\"]','Aligned with enterprise cloud and cybersecurity competency frameworks.',40,1,NULL,'topic_based','Competency-based practical lab and capstone project (>= 50%).','Git commit history audit and authenticated sandbox environment.','Line-by-line code review and rubric commentary.','[\"certificate\", \"badge\"]','module',NULL,'Authenticated learner session and code repository signature.',1.5,3,1,NULL,NULL,'2026-09-04 01:42:39',6,'2026-09-04 01:42:39','2026-11-04 01:42:39',NULL,'2026-10-15','initial',NULL,NULL),(8,1,2,'revision','approved','Data Structures (2026 Enhanced)','Struktur Data (Dipertingkat 2026)','Computing','Software developers','online','English','14 weeks','Updated syllabus with Python 3.12 syntax and lab modules.','[\"industry\", \"feedback\", \"programme\"]','Provides stackable academic credit towards accredited UTeM degree programmes.',80,1,1,'credit_based','Minimum 50% pass mark across all summative components with verified identity.','Proctored online assessment and viva verification.','Individual rubric breakdown and automated quiz feedback within 7 days.','[\"certificate\", \"badge\"]','whole',NULL,'National ID / Passport check and LMS proctoring log.',2.0,6,1,NULL,NULL,NULL,NULL,'2026-10-04 01:42:39','2026-11-04 01:42:39',1,'2026-12-04','minor','Updated lab guidelines and added memory management topic.',NULL),(9,8,1,'new','approved','Secure Software Development Lifecycle (SSDLC)','Kitar Hayat Pembangunan Perisian Selamat (SSDLC)','Cybersecurity','Application developers and QA engineers','online','English','8 weeks','Mandatory OWASP Top 10 compliance in enterprise software teams.','[\"industry\", \"feedback\", \"programme\"]','Includes automated SAST/DAST pipeline templates.',80,1,NULL,'topic_based','50% pass mark.','Authenticated lab repository.','Automated scanner + lecturer review.','[\"certificate\", \"badge\"]','module','verified','IC verification & code walkthrough.',2.0,6,1,'Original FTMK courseware.',NULL,'2026-07-25 10:00:00',7,'2026-07-20 09:00:00','2026-11-05 09:00:00',NULL,'2026-08-20','initial',NULL,'2029-08-15'),(10,9,1,'new','returned','Algorithmic Modelling and Decision Making','Pemodelan Algoritma dan Pembuatan Keputusan','Computing','Undergraduate CS students and junior software engineers','online','English','10 weeks','Growing demand for optimization and decision-support modelling in logistics.','[\"industry\", \"feedback\", \"programme\"]','Bridges discrete mathematics with practical Python solver libraries.',120,1,2,'topic_based','50% pass threshold.','Authenticated LMS quiz and viva.','Rubric feedback in LMS.','[\"certificate\", \"badge\"]','whole','will_apply','Student ID check.',2.0,6,1,NULL,'Updated practical SLT hours and clarified LO2 assessment rubric.','2026-10-28 09:00:00',6,'2026-11-04 02:20:19','2026-11-04 02:20:55',NULL,NULL,'initial',NULL,NULL),(11,10,1,'new','submitted','Cloud Security Posture & DevSecOps Automation','Postur Keselamatan Awan & Automasi DevSecOps','Computing','Cloud developers','online','English','8 weeks','Critical shortage of DevSecOps engineers capable of automating container compliance.','[\"industry\", \"feedback\", \"programme\"]','Hands-on CI/CD security gate implementation.',80,1,1,'credit_based',NULL,NULL,NULL,'[\"certificate\", \"badge\"]','whole','will_apply',NULL,2.0,6,1,NULL,NULL,'2026-11-02 10:30:00',6,'2026-11-04 07:54:05','2026-11-04 07:54:05',NULL,NULL,'initial',NULL,NULL),(12,11,1,'new','pending_approval','Enterprise API Architecture & Microservices Governance','Seni Bina API Perusahaan & Tadbir Urus Mikroperkhidmatan','Computing','Cloud practitioners','online','English','8 weeks','Financial and telecom sectors require standardized OpenAPI and gateway governance skills.','[\"industry\", \"feedback\", \"programme\"]','Endorsed by industry partners for API security and throttling design.',80,1,1,'topic_based',NULL,NULL,NULL,'[\"certificate\", \"badge\"]','module','will_apply',NULL,2.0,6,1,NULL,NULL,'2026-10-18 14:00:00',6,'2026-11-04 08:13:28','2026-11-04 08:13:28',NULL,NULL,'initial',NULL,NULL),(13,12,1,'new','approved','Electric Circuit Theory & Power Systems','Teori Litar Elektrik & Sistem Kuasa','Electrical Engineering','Electrical technicians and engineers','online','English','12 weeks','Foundational circuit analysis for engineering technologists.','[\"industry\", \"feedback\", \"programme\"]','Provides stackable academic credit towards accredited UTeM degree programmes.',80,1,11,'credit_based','Minimum 50% pass mark across all summative components with verified identity.','Proctored online assessment and viva verification.','Individual rubric breakdown and automated quiz feedback within 7 days.','[\"certificate\", \"badge\"]','whole',NULL,'National ID / Passport check and LMS proctoring log.',2.0,6,1,NULL,NULL,'2026-08-01 01:42:39',8,'2026-08-01 01:42:39','2026-11-04 01:42:39',NULL,'2026-09-01','initial',NULL,NULL),(14,13,1,'new','approved','Engineering Mechanics & Dynamics','Mekanik Kejuruteraan & Dinamik','Mechanical Engineering','Mechanical technologists','online','English','12 weeks','Statics and dynamics fundamentals for mechanical engineering.','[\"industry\", \"feedback\", \"programme\"]','Provides stackable academic credit towards accredited UTeM degree programmes.',80,1,12,'credit_based','Minimum 50% pass mark across all summative components with verified identity.','Proctored online assessment and viva verification.','Individual rubric breakdown and automated quiz feedback within 7 days.','[\"certificate\", \"badge\"]','whole',NULL,'National ID / Passport check and LMS proctoring log.',2.0,6,1,NULL,NULL,'2026-08-01 01:42:39',6,'2026-08-01 01:42:39','2026-11-04 01:42:39',NULL,'2026-09-01','initial',NULL,NULL),(15,14,1,'new','approved','Embedded Internet of Things (IoT) Systems','Sistem Terbenam Internet Kebendaan (IoT)','Computer Engineering','IoT and embedded firmware developers','online','English','12 weeks','IoT edge system design and low-power microcontrollers.','[\"industry\", \"feedback\", \"programme\"]','Provides stackable academic credit towards accredited UTeM degree programmes.',80,1,14,'credit_based','Minimum 50% pass mark across all summative components with verified identity.','Proctored online assessment and viva verification.','Individual rubric breakdown and automated quiz feedback within 7 days.','[\"certificate\", \"badge\"]','whole',NULL,'National ID / Passport check and LMS proctoring log.',2.0,7,1,NULL,NULL,'2026-08-01 01:42:39',6,'2026-08-01 01:42:39','2026-11-04 01:42:39',NULL,'2026-09-01','initial',NULL,NULL),(16,15,1,'new','approved','Manufacturing Processes & Automation','Proses Pembuatan & Automasi','Manufacturing Engineering','Manufacturing and industrial engineers','online','English','12 weeks','Advanced machining, automation and manufacturing processes.','[\"industry\", \"feedback\", \"programme\"]','Provides stackable academic credit towards accredited UTeM degree programmes.',80,1,15,'credit_based','Minimum 50% pass mark across all summative components with verified identity.','Proctored online assessment and viva verification.','Individual rubric breakdown and automated quiz feedback within 7 days.','[\"certificate\", \"badge\"]','whole',NULL,'National ID / Passport check and LMS proctoring log.',2.0,6,1,NULL,NULL,'2026-08-01 01:42:39',6,'2026-08-01 01:42:39','2026-11-04 01:42:39',NULL,'2026-09-01','initial',NULL,NULL),(17,16,1,'new','approved','Fundamental of Data Science','Asas Sains Data','Artificial Intelligence & Data Science','Data practitioners and analysts','online','English','12 weeks','Data analysis, statistics and predictive modelling.','[\"industry\", \"feedback\", \"programme\"]','Provides stackable academic credit towards accredited UTeM degree programmes.',80,1,16,'credit_based','Minimum 50% pass mark across all summative components with verified identity.','Proctored online assessment and viva verification.','Individual rubric breakdown and automated quiz feedback within 7 days.','[\"certificate\", \"badge\"]','whole',NULL,'National ID / Passport check and LMS proctoring log.',2.0,7,1,NULL,NULL,'2026-08-01 01:42:39',7,'2026-08-01 01:42:39','2026-11-04 01:42:39',NULL,'2026-09-01','initial',NULL,NULL),(18,17,1,'new','approved','Object-Oriented Software Construction','Pembinaan Perisian Berorientasikan Objek','Computing','CS students and junior developers','online','English','12 weeks','Core OOP design patterns and SOLID principles.','[\"industry\", \"feedback\", \"programme\"]','Directly maps to BITD 1123.',120,1,10,'credit_based','50% pass.','Proctored exam and project viva.','Rubric.','[\"certificate\", \"badge\"]','whole','verified','Identity check.',3.0,6,1,NULL,NULL,'2026-05-01 10:00:00',6,'2026-04-10 09:00:00','2026-11-05 09:00:00',NULL,'2026-06-20','initial',NULL,'2029-06-30'),(19,18,1,'new','approved','Smart Grid Power Electronics & Renewable Integration','Elektronik Kuasa Grid Pintar & Integrasi Tenaga Boleh Baharu','Electrical Engineering','Power systems engineers','blended','English','10 weeks','National energy transition requires grid-tied inverter competencies.','[\"industry\", \"feedback\", \"programme\"]','Includes MATLAB/Simulink hardware-in-the-loop labs.',80,1,4,'topic_based','50% pass.','Lab verification.','Lab report grading.','[\"certificate\", \"badge\"]','module','will_apply','In-person lab check.',2.0,6,1,NULL,NULL,'2026-08-15 10:00:00',8,'2026-08-05 09:00:00','2026-11-05 09:00:00',NULL,'2026-09-20','initial',NULL,NULL),(20,19,1,'new','approved','Applied Thermo-Fluids & Energy Efficiency Audit','Termo-Bendalir Gunaan & Audit Kecekapan Tenaga','Mechanical Engineering','Plant and HVAC engineers','blended','English','10 weeks','Industrial energy efficiency compliance under EECA.','[\"industry\", \"feedback\", \"programme\"]','Maps to BMKG 2123.',80,1,19,'credit_based','50% pass.','Lab viva.','Rubric.','[\"certificate\", \"badge\"]','whole','will_apply','ID check.',2.0,6,1,NULL,NULL,'2026-08-25 10:00:00',22,'2026-08-12 09:00:00','2026-11-05 09:00:00',NULL,'2026-09-25','initial',NULL,NULL),(21,20,1,'new','approved','Strategic Technopreneurship & Venture Creation','Teknousahawan Strategik & Penciptaan Usaha Niaga','Business & Technopreneurship','MBA candidates and startup founders','online','Bilingual','8 weeks','Executive pathway into UTeM ODL MBA.','[\"industry\", \"feedback\", \"programme\"]','Unbundled from MBA 5113 with full credit equivalence.',120,1,13,'credit_based','60% postgraduate pass.','Pitch defence & similarity check.','Panel rubric.','[\"certificate\", \"badge\"]','whole','verified','Live video defence.',3.0,7,1,NULL,NULL,'2026-06-01 10:00:00',24,'2026-05-15 09:00:00','2026-11-05 09:00:00',NULL,'2026-07-25','initial',NULL,'2029-07-31'),(22,21,1,'new','approved','FPGA Digital IC Design & Verilog Verification','Reka Bentuk IC Digital FPGA & Pengesahan Verilog','Electronic Engineering','Semiconductor and IC design engineers','online','English','10 weeks','Supports Malaysia National Semiconductor Strategy (NSS).','[\"industry\", \"feedback\", \"programme\"]','Hands-on Xilinx Vivado synthesis and timing closure.',80,1,22,'topic_based','50% pass.','Testbench waveform audit.','Timing report feedback.','[\"certificate\", \"badge\"]','module','will_apply','Live code walkthrough.',2.0,7,1,NULL,NULL,'2026-09-01 10:00:00',23,'2026-08-20 09:00:00','2026-11-05 09:00:00',NULL,'2026-09-28','initial',NULL,NULL),(23,22,1,'new','approved','Smart Factory Digital Twin & PLC Commissioning','Kembar Digital Kilang Pintar & Pentauliahan PLC','Manufacturing Engineering','Automation technicians and factory supervisors','blended','Bilingual','3 weeks','Industry4WRD digital twin adoption for SMEs.','[\"industry\", \"technology\"]','Short executive workshop with certificate of completion.',24,0,NULL,NULL,'Pass/Fail practical.','Workshop practical demonstration.','Practical debrief.','[\"certificate\"]','whole','na','Facilitator sign-off.',0.0,4,1,NULL,NULL,'2026-09-15 10:00:00',22,'2026-09-01 09:00:00','2026-11-05 09:00:00',NULL,'2026-10-08','initial',NULL,NULL),(24,23,1,'new','approved','Deep Learning & Transformer Architectures for Enterprise AI','Pembelajaran Mendalam & Seni Bina Transformer untuk AI Perusahaan','Artificial Intelligence','AI engineers and data scientists','online','English','12 weeks','High demand for LLM fine-tuning and RAG pipelines.','[\"industry\", \"feedback\", \"programme\"]','Unbundled from MAXL 5143 with GPU lab access.',120,1,25,'credit_based','60% pass.','Model training log + viva.','Rubric.','[\"certificate\", \"badge\"]','module','verified','Identity & notebook audit.',3.0,7,1,NULL,NULL,'2026-07-01 10:00:00',7,'2026-06-01 09:00:00','2026-11-05 09:00:00',NULL,'2026-08-12','initial',NULL,'2029-08-30'),(25,24,1,'new','not_approved','Legacy Mainframe COBOL Maintenance Basics','Asas Penyelenggaraan COBOL Kerangka Utama Warisan','Computing','Junior programmers','online','English','6 weeks','Proposed for legacy banking maintenance.','[\"other\"]','Basic syntax overview.',40,1,NULL,'topic_based','50%.','Quiz.','Score.','[\"certificate\"]','whole','not_applied','Login.',1.0,4,1,NULL,NULL,'2026-10-29 10:00:00',7,'2026-10-28 09:00:00','2026-11-05 09:00:00',NULL,NULL,'initial',NULL,NULL),(26,25,1,'new','superseded','Industrial PLC Programming (v1)','Pengaturcaraan PLC Industri (v1)','Electrical Engineering','Electrical technologists','online','English','8 weeks','Core ladder logic programming.','[\"industry\", \"feedback\", \"programme\"]','Maps to BENE 2133.',80,1,4,'topic_based','50%.','Lab test.','Rubric.','[\"certificate\", \"badge\"]','whole','not_applied','ID check.',2.0,6,1,NULL,NULL,'2026-01-15 10:00:00',8,'2026-01-10 09:00:00','2026-11-05 09:00:00',NULL,'2026-03-01','initial',NULL,NULL),(27,25,2,'revision','approved','Industrial PLC & SCADA OPC-UA Programming (v2)','Pengaturcaraan PLC Industri & SCADA OPC-UA (v2)','Electrical Engineering','Electrical and automation engineers','online','English','10 weeks','Modern factories require OPC-UA connectivity alongside IEC 61131-3.','[\"industry\", \"feedback\", \"programme\"]','Updated for Industry 4.0 cyber-physical integration.',80,1,4,'topic_based','50%.','Simulation & viva.','Rubric.','[\"certificate\", \"badge\"]','module','will_apply','ID check.',2.0,6,1,NULL,NULL,'2026-09-20 10:00:00',8,'2026-09-15 09:00:00','2026-11-05 09:00:00',26,'2026-10-15','material','Upgraded syllabus to IEC 61131-3 Structured Text and added SCADA OPC-UA security module.',NULL);
/*!40000 ALTER TABLE `mc_versions` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`mcms`@`%`*/ /*!50003 TRIGGER `mc_versions_keep` BEFORE DELETE ON `mc_versions` FOR EACH ROW BEGIN
    IF OLD.status <> 'draft' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'a submitted version is never deleted';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `micro_credentials`
--

DROP TABLE IF EXISTS `micro_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `micro_credentials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proposed',
  `origin` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qv_planned` tinyint(1) NOT NULL DEFAULT '0',
  `ptj_id` bigint unsigned NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `category_confirmed_by` bigint unsigned DEFAULT NULL,
  `category_confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `micro_credentials_code_unique` (`code`),
  KEY `micro_credentials_ptj_id_foreign` (`ptj_id`),
  KEY `micro_credentials_owner_id_foreign` (`owner_id`),
  KEY `micro_credentials_category_confirmed_by_foreign` (`category_confirmed_by`),
  CONSTRAINT `micro_credentials_category_confirmed_by_foreign` FOREIGN KEY (`category_confirmed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `micro_credentials_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `micro_credentials_ptj_id_foreign` FOREIGN KEY (`ptj_id`) REFERENCES `ptjs` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `micro_credentials`
--

LOCK TABLES `micro_credentials` WRITE;
/*!40000 ALTER TABLE `micro_credentials` DISABLE KEYS */;
INSERT INTO `micro_credentials` VALUES (1,'MC-FTMK-001','umc','confirmed','unbundled',0,1,6,10,'2026-05-04 01:42:39','2026-01-04 01:42:39','2026-11-04 01:42:39'),(2,'MC-FTMK-002','samc','confirmed','own',1,1,6,10,'2026-05-04 01:42:39','2026-03-04 01:42:39','2026-11-04 01:42:39'),(3,'MC-FTMK-003','mce','confirmed','own',0,1,6,10,'2026-06-04 01:42:39','2026-05-04 01:42:39','2026-11-04 01:42:39'),(4,'MC-FTMK-004','samc','proposed','own',1,1,7,NULL,NULL,'2026-10-25 01:42:39','2026-11-04 01:42:39'),(5,'MC-FTMK-005','umc','confirmed','unbundled',0,1,6,10,'2026-10-28 01:42:39','2026-10-15 01:42:39','2026-11-04 01:42:39'),(6,'MC-FTMK-006','samc','confirmed','own',1,1,6,10,'2026-09-04 01:42:39','2026-07-04 01:42:39','2026-11-04 01:42:39'),(7,'MC-FPTT-007','mce','confirmed','own',0,4,6,10,'2026-09-04 01:42:39','2026-08-04 01:42:39','2026-11-04 01:42:39'),(8,'MC-FTMK-008','samc','confirmed','own',1,1,7,10,'2026-08-15 10:00:00','2026-07-20 09:00:00','2026-11-05 09:00:00'),(9,'MC-FTMK-009','umc','proposed','unbundled',1,1,6,NULL,NULL,'2026-11-01 09:00:00','2026-11-05 09:00:00'),(10,'MC-FTMK-010','umc','proposed','unbundled',0,1,6,NULL,NULL,'2026-11-02 09:00:00','2026-11-05 09:00:00'),(11,'MC-FTMK-011','samc','verified','partner',1,1,7,10,'2026-10-28 11:00:00','2026-10-10 09:00:00','2026-11-05 09:00:00'),(12,'MC-FTKE-008','umc','confirmed','unbundled',0,2,8,11,'2026-09-04 01:42:39','2026-08-01 01:42:39','2026-11-04 01:42:39'),(13,'MC-FTKM-009','umc','confirmed','unbundled',0,3,23,25,'2026-09-04 01:42:39','2026-08-01 01:42:39','2026-11-04 01:42:39'),(14,'MC-FTKEK-010','umc','confirmed','unbundled',0,5,24,25,'2026-09-04 01:42:39','2026-08-01 01:42:39','2026-11-04 01:42:39'),(15,'MC-FTKIP-011','umc','confirmed','unbundled',0,6,23,25,'2026-09-04 01:42:39','2026-08-01 01:42:39','2026-11-04 01:42:39'),(16,'MC-FAIX-012','umc','confirmed','unbundled',0,7,24,25,'2026-09-04 01:42:39','2026-08-01 01:42:39','2026-11-04 01:42:39'),(17,'MC-FTMK-013','umc','confirmed','unbundled',1,1,6,10,'2026-06-15 10:00:00','2026-04-10 09:00:00','2026-11-05 09:00:00'),(18,'MC-FTKE-014','samc','confirmed','industry',1,2,8,11,'2026-09-12 10:00:00','2026-08-05 09:00:00','2026-11-05 09:00:00'),(19,'MC-FTKM-015','umc','confirmed','unbundled',1,3,22,25,'2026-09-18 10:00:00','2026-08-12 09:00:00','2026-11-05 09:00:00'),(20,'MC-FPTT-016','umc','confirmed','unbundled',1,4,24,25,'2026-07-20 10:00:00','2026-05-15 09:00:00','2026-11-05 09:00:00'),(21,'MC-FTKEK-017','samc','confirmed','partner',1,5,23,25,'2026-09-25 10:00:00','2026-08-20 09:00:00','2026-11-05 09:00:00'),(22,'MC-FTKIP-018','mce','confirmed','industry',0,6,22,25,'2026-10-05 10:00:00','2026-09-01 09:00:00','2026-11-05 09:00:00'),(23,'MC-FAIX-019','umc','confirmed','unbundled',1,7,7,25,'2026-08-10 10:00:00','2026-06-01 09:00:00','2026-11-05 09:00:00'),(24,'MC-FTMK-020','samc','proposed','own',1,1,7,NULL,NULL,'2026-10-28 09:00:00','2026-11-05 09:00:00'),(25,'MC-FTKE-021','umc','confirmed','unbundled',1,2,8,11,'2026-10-12 10:00:00','2026-09-15 09:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `micro_credentials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_28_000001_foundation_tables',1),(5,'2026_10_28_000002_foundation_more',1),(6,'2026_10_29_000001_curriculum',1),(7,'2026_10_30_000001_approval',1),(8,'2026_11_02_000001_versions',1),(9,'2026_11_04_000001_offerings',1),(10,'2026_11_09_000001_enrolment',1),(11,'2026_11_16_000001_results',1),(12,'2026_11_23_000001_achievement',1),(13,'2026_11_30_000001_credentials',1),(14,'2026_12_07_000001_quality_verification',1),(15,'2026_12_14_000001_credit_bank',1),(16,'2026_12_21_000001_transfer',1),(17,'2026_12_28_000001_pathways',1),(18,'2027_01_04_000001_quality_improvement',1),(19,'2027_01_11_000001_records_reports',1),(20,'2027_01_18_000001_ucreds_courses',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module_achievements`
--

DROP TABLE IF EXISTS `module_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `module_achievements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enrolment_id` bigint unsigned NOT NULL,
  `mc_module_id` bigint unsigned NOT NULL,
  `verified_by` bigint unsigned NOT NULL,
  `achieved_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_achievements_enrolment_id_mc_module_id_unique` (`enrolment_id`,`mc_module_id`),
  KEY `module_achievements_mc_module_id_foreign` (`mc_module_id`),
  KEY `module_achievements_verified_by_foreign` (`verified_by`),
  CONSTRAINT `module_achievements_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `module_achievements_mc_module_id_foreign` FOREIGN KEY (`mc_module_id`) REFERENCES `mc_modules` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `module_achievements_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `module_achievements`
--

LOCK TABLES `module_achievements` WRITE;
/*!40000 ALTER TABLE `module_achievements` DISABLE KEYS */;
INSERT INTO `module_achievements` VALUES (1,1,3,10,'2026-07-17 01:42:39'),(2,1,4,10,'2026-09-05 01:42:39'),(3,2,3,10,'2026-07-17 01:42:39'),(4,2,4,10,'2026-09-05 01:42:39'),(5,3,1,10,'2026-10-30 01:42:39'),(6,9,1,10,'2026-06-25 10:00:00'),(7,9,2,10,'2026-08-20 10:00:00'),(8,10,1,10,'2026-06-26 10:00:00'),(9,10,2,10,'2026-08-20 10:00:00'),(10,11,1,10,'2026-06-27 10:00:00'),(11,11,2,10,'2026-08-20 10:00:00'),(12,12,1,10,'2026-06-28 10:00:00'),(13,12,2,10,'2026-08-20 10:00:00'),(14,13,19,10,'2026-09-20 10:00:00'),(15,13,20,10,'2026-10-20 10:00:00'),(16,14,19,10,'2026-09-21 10:00:00'),(17,14,20,10,'2026-10-20 10:00:00'),(18,16,19,10,'2026-09-25 10:00:00');
/*!40000 ALTER TABLE `module_achievements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module_enrolments`
--

DROP TABLE IF EXISTS `module_enrolments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `module_enrolments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enrolment_id` bigint unsigned NOT NULL,
  `mc_module_id` bigint unsigned NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `enrolled_at` timestamp NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_enrolments_enrolment_id_mc_module_id_unique` (`enrolment_id`,`mc_module_id`),
  KEY `module_enrolments_mc_module_id_foreign` (`mc_module_id`),
  CONSTRAINT `module_enrolments_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `module_enrolments_mc_module_id_foreign` FOREIGN KEY (`mc_module_id`) REFERENCES `mc_modules` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `module_enrolments`
--

LOCK TABLES `module_enrolments` WRITE;
/*!40000 ALTER TABLE `module_enrolments` DISABLE KEYS */;
INSERT INTO `module_enrolments` VALUES (1,1,3,'completed','2026-05-18 01:42:39','2026-07-17 01:42:39'),(2,1,4,'completed','2026-07-17 01:42:39','2026-09-05 01:42:39'),(3,2,3,'completed','2026-05-18 01:42:39','2026-07-17 01:42:39'),(4,2,4,'completed','2026-07-17 01:42:39','2026-09-05 01:42:39'),(5,3,1,'completed','2026-10-05 01:42:39','2026-10-30 01:42:39'),(6,3,2,'enrolled','2026-10-30 01:42:39',NULL),(7,4,1,'enrolled','2026-10-05 01:42:39',NULL),(8,5,1,'enrolled','2026-10-05 01:42:39',NULL),(9,9,1,'completed','2026-05-10 09:00:00','2026-06-25 10:00:00'),(10,9,2,'completed','2026-06-25 10:00:00','2026-08-20 10:00:00'),(11,10,1,'completed','2026-05-10 09:00:00','2026-06-26 10:00:00'),(12,10,2,'completed','2026-06-26 10:00:00','2026-08-20 10:00:00'),(13,11,1,'completed','2026-05-10 09:00:00','2026-06-27 10:00:00'),(14,11,2,'completed','2026-06-27 10:00:00','2026-08-20 10:00:00'),(15,12,1,'completed','2026-05-10 09:00:00','2026-06-28 10:00:00'),(16,12,2,'completed','2026-06-28 10:00:00','2026-08-20 10:00:00'),(17,13,19,'completed','2026-08-25 09:00:00','2026-09-20 10:00:00'),(18,13,20,'completed','2026-09-20 10:00:00','2026-10-20 10:00:00'),(19,14,19,'completed','2026-08-25 09:00:00','2026-09-21 10:00:00'),(20,14,20,'completed','2026-09-21 10:00:00','2026-10-20 10:00:00'),(21,15,19,'completed','2026-08-25 09:00:00','2026-09-22 10:00:00'),(22,15,20,'completed','2026-09-22 10:00:00','2026-10-20 10:00:00'),(23,16,19,'completed','2026-08-25 09:00:00','2026-09-25 10:00:00'),(24,16,20,'enrolled','2026-09-25 10:00:00',NULL),(25,17,25,'completed','2026-07-01 09:00:00','2026-08-15 10:00:00'),(26,17,26,'completed','2026-08-15 10:00:00','2026-09-30 10:00:00'),(27,18,25,'completed','2026-07-01 09:00:00','2026-08-16 10:00:00'),(28,18,26,'completed','2026-08-16 10:00:00','2026-09-30 10:00:00'),(29,19,30,'completed','2026-08-01 09:00:00','2026-09-01 10:00:00'),(30,19,31,'completed','2026-09-01 10:00:00','2026-10-01 10:00:00'),(31,20,34,'completed','2026-08-15 09:00:00','2026-09-20 10:00:00'),(32,20,35,'completed','2026-09-20 10:00:00','2026-10-25 10:00:00'),(33,21,1,'completed','2026-10-05 09:00:00','2026-10-30 10:00:00'),(34,21,2,'enrolled','2026-10-30 10:00:00',NULL),(35,22,5,'enrolled','2026-10-20 09:00:00',NULL),(36,26,14,'enrolled','2026-11-03 10:00:00',NULL);
/*!40000 ALTER TABLE `module_enrolments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `template_key` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_preferences_user_id_template_key_unique` (`user_id`,`template_key`),
  CONSTRAINT `notification_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
INSERT INTO `notification_preferences` VALUES (1,1,'enrolment.confirmed',1),(2,1,'credential.issued',1),(3,6,'proposal.submitted',1),(4,7,'reviewer.assigned',1),(5,10,'decision.made',1),(6,1,'results.verified',1),(7,1,'completion.window',0),(8,2,'enrolment.registered',1),(9,2,'results.verified',1),(10,3,'results.verified',0),(11,6,'review.submitted',1),(12,6,'results.staged',1),(13,6,'qv.expiring',1),(14,7,'proposal.submitted',0),(15,8,'review.due',1),(16,10,'proposal.submitted',1),(17,10,'change.minor',1),(18,18,'results.verified',1),(19,20,'enrolment.registered',1),(20,23,'results.staged',1),(21,24,'reviewer.assigned',1),(22,25,'review.due',1);
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `template_key` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dedupe_key` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notifications_user_id_template_key_channel_dedupe_key_unique` (`user_id`,`template_key`,`channel`,`dedupe_key`),
  KEY `notifications_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'credential.issued','in_system','en','Your Digital Credential Has Been Issued: Cloud Native Development','Hello Amir Hakimi, your official W3C Verifiable Credential for Cloud Native Development is issued. Credential Number: UTEM-MC-2026-0001.','sent',NULL,'2026-09-17 01:42:39','2026-09-15 01:42:39'),(2,1,'acr.confirmed','in_system','en','Your Academic Credit Record is ready: Cloud Native Development','Hello Amir Hakimi, your Academic Credit Record for Cloud Native Development is confirmed. Open Credit Ledger and ABC to give consent to deposit it.','sent',NULL,'2026-09-14 01:42:39','2026-09-13 01:42:39'),(3,6,'proposal.submitted','in_system','en','Review requested: Cyber Security Operations Fundamentals','Hello Dr. Aina Zulkifli, you have been invited to review Cyber Security Operations Fundamentals by next week.','sent',NULL,NULL,'2026-10-31 01:42:39'),(4,10,'decision.made','in_system','ms','Cadangan Mikro-Kredensial Dihantar: Cyber Security Operations Fundamentals','Salam Hakim Rahman, Dr. Lim Wei Jie telah menghantar cadangan untuk penilaian.','sent',NULL,NULL,'2026-10-31 01:42:39'),(5,1,'enrolment.confirmed','email','en','Enrolment Confirmed: Data Structures (2026/1-DS)','Hello Amir Hakimi, your enrolment ENR-2026-0009 in Data Structures is confirmed.','sent',NULL,'2026-04-20 12:00:00','2026-04-20 10:05:00'),(6,1,'credential.issued','in_system','en','Your Digital Credential Has Been Issued: Data Structures','Credential UTEM-MC-2026-0003 (Public Code: DST01-4H8KP) is ready for download.','sent',NULL,'2026-08-27 09:00:00','2026-08-26 10:05:00'),(7,1,'credential.issued','in_system','en','Your Digital Credential Has Been Issued: Secure SDLC','Credential UTEM-MC-2026-0007 (Public Code: SEC01-8M4PT) is ready for download.','sent',NULL,'2026-11-06 02:53:53','2026-10-26 10:05:00'),(8,2,'credential.issued','in_system','en','Re-issued Credential Available: Cloud Native Development (Rev 2)','Your corrected credential UTEM-MC-2026-0002-R1 has been issued.','sent',NULL,'2026-10-16 09:00:00','2026-10-15 10:05:00'),(9,3,'acr.confirmed','in_system','en','Action Required: Consent for ABC Credit Deposit (ACR-2026-0005)','Please review and grant consent to deposit your 2.0 credits into the ABC ledger.','sent',NULL,NULL,'2026-08-28 10:05:00'),(10,18,'credential.issued','in_system','ms','Kredensial Digital Anda Telah Dikeluarkan: Secure SDLC','Tahniah Sharifah Puteri, kredensial UTEM-MC-2026-0008 telah sedia untuk dimuat turun.','sent',NULL,'2026-10-27 10:00:00','2026-10-26 10:05:00'),(11,19,'decision.made','in_system','en','Credit Transfer Approved: TR-2026-0004 (MBA 5113)','Your transfer application TR-2026-0004 for 3.0 credits has been approved.','sent',NULL,'2026-10-21 10:00:00','2026-10-20 10:15:00'),(12,6,'decision.made','in_system','en','Proposal Returned for Revision: Algorithmic Modelling (MC-FTMK-009)','Please address the assessment rubric readiness item and resubmit.','sent',NULL,NULL,'2026-11-02 17:05:00'),(13,15,'reviewer.assigned','in_system','en','Expert Review Invitation: Cloud Security Posture (MC-FTMK-010)','You have been invited as an Industry Expert reviewer. Due date: 2026-11-15.','sent',NULL,NULL,'2026-11-03 09:05:00'),(14,12,'qv.expiring','email','en','QV Renewal Reminder: Cloud Native Development (QV/2023/051)','Quality Verification expires within 45 days. Cycle 2 dossier is in preparation.','queued',NULL,NULL,'2026-11-04 08:00:00'),(15,20,'credential.revoked','email','en','Notice of Credential Revocation: UTEM-MC-2026-0009','Credential UTEM-MC-2026-0009 has been revoked by CODL Registry.','failed',NULL,NULL,'2026-11-01 10:05:00');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offerings`
--

DROP TABLE IF EXISTS `offerings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `offerings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_version_id` bigint unsigned NOT NULL,
  `cohort_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `delivery_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` smallint unsigned NOT NULL,
  `reg_opens_on` date NOT NULL,
  `reg_closes_on` date NOT NULL,
  `fee` decimal(8,2) NOT NULL DEFAULT '0.00',
  `facilitators` text COLLATE utf8mb4_unicode_ci,
  `eligible_groups` text COLLATE utf8mb4_unicode_ci,
  `platform_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `prerequisites` text COLLATE utf8mb4_unicode_ci,
  `access_period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `learning_support` text COLLATE utf8mb4_unicode_ci,
  `completion_conditions` text COLLATE utf8mb4_unicode_ci,
  `staged_completion` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_ready',
  `readiness_state` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `held_reason` text COLLATE utf8mb4_unicode_ci,
  `cancel_reason` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `withdrawal_deadline` date DEFAULT NULL,
  `results_submitted_at` timestamp NULL DEFAULT NULL,
  `completion_window_months` smallint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offerings_mc_version_id_foreign` (`mc_version_id`),
  KEY `offerings_created_by_foreign` (`created_by`),
  KEY `offerings_status_start_date_index` (`status`,`start_date`),
  CONSTRAINT `offerings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `offerings_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offerings`
--

LOCK TABLES `offerings` WRITE;
/*!40000 ALTER TABLE `offerings` DISABLE KEYS */;
INSERT INTO `offerings` VALUES (1,2,'2026/1','2026-06-07','2026-09-05','online',30,'2026-05-08','2026-06-02',150.00,'Dr. Aina Zulkifli','UTeM undergraduates, alumni, and working professionals.','uLearn-CND-2026-1','Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'ended','ready',NULL,NULL,6,'2026-04-28 01:42:39','2026-11-04 01:42:39','2026-11-30','2026-08-30 10:00:00',6),(2,1,'2026/2','2026-10-15','2027-01-03','online',30,'2026-09-15','2026-10-10',0.00,'Dr. Aina Zulkifli, Dr. Lim Wei Jie','UTeM undergraduates, alumni, and working professionals.','uLearn-DS-2026-2','Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'running','ready',NULL,NULL,6,'2026-09-10 01:42:39','2026-11-04 01:42:39','2026-11-30','2026-11-03 14:00:00',6),(3,2,'2027/1','2026-12-04','2027-03-04','online',40,'2026-10-25','2026-11-24',150.00,'Dr. Aina Zulkifli','UTeM undergraduates, alumni, and working professionals.','uLearn-CND-2027-1','Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'open','ready',NULL,NULL,6,'2026-10-20 01:42:39','2026-11-04 01:42:39','2026-11-30',NULL,6),(4,7,'2026/12','2026-12-14','2027-02-02','online',25,'2026-10-30','2026-12-04',200.00,'Assoc. Prof. Dr. Ahmad Razali','UTeM undergraduates, alumni, and working professionals.','uLearn-LSS-2026-12','Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'open','ready',NULL,NULL,6,'2026-10-25 01:42:39','2026-11-04 01:42:39','2026-11-30',NULL,6),(5,3,'2026/LE1','2026-10-25','2026-11-24','blended',20,'2026-10-05','2026-10-20',0.00,'Dr. Aina Zulkifli','UTeM undergraduates, alumni, and working professionals.',NULL,'Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'running','ready',NULL,NULL,6,'2026-09-30 01:42:39','2026-11-04 01:42:39','2026-11-30',NULL,6),(6,2,'2026/S1','2026-09-25','2026-12-04','online',15,'2026-09-05','2026-09-20',150.00,NULL,'UTeM undergraduates, alumni, and working professionals.',NULL,'Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'running','ready','Infrastructure platform migration maintenance',NULL,6,'2026-08-26 01:42:39','2026-11-06 00:56:08','2026-11-30',NULL,6),(7,13,'2026/FTKE1','2026-11-25','2027-02-15','online',35,'2026-10-20','2026-11-20',180.00,'Assoc. Prof. Dr. Ahmad Razali','UTeM undergraduates, alumni, and working professionals.','uLearn-ECT-2026-1','Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'open','ready',NULL,NULL,8,'2026-10-15 01:42:39','2026-11-04 01:42:39','2026-11-30',NULL,6),(8,14,'2026/FTKM1','2026-11-25','2027-02-15','online',30,'2026-10-20','2026-11-20',175.00,'Dr. Aina Zulkifli','UTeM undergraduates, alumni, and working professionals.','uLearn-EMD-2026-1','Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'open','ready',NULL,NULL,6,'2026-10-15 01:42:39','2026-11-04 01:42:39','2026-11-30',NULL,6),(9,15,'2026/FTKEK1','2026-11-25','2027-02-15','online',30,'2026-10-20','2026-11-20',220.00,'Dr. Lim Wei Jie','UTeM undergraduates, alumni, and working professionals.','uLearn-IOT-2026-1','Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'open','ready',NULL,NULL,6,'2026-10-15 01:42:39','2026-11-04 01:42:39','2026-11-30',NULL,6),(10,16,'2026/FTKIP1','2026-11-25','2027-02-15','online',30,'2026-10-20','2026-11-20',190.00,'Dr. Aina Zulkifli','UTeM undergraduates, alumni, and working professionals.','uLearn-MPA-2026-1','Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'open','ready',NULL,NULL,6,'2026-10-15 01:42:39','2026-11-04 01:42:39','2026-11-30',NULL,6),(11,17,'2026/FAIX1','2026-11-25','2027-02-15','online',30,'2026-10-20','2026-11-20',250.00,'Dr. Lim Wei Jie','UTeM undergraduates, alumni, and working professionals.','uLearn-FDS-2026-1','Comprehensive modular micro-credential delivered via UTeM uLearn with guided lab clinics and authentic competency assessment.','Basic programming or engineering literacy; laptop with 8GB RAM.','16 weeks from cohort start date.','Weekly 1-hour synchronous consultation via Microsoft Teams and 24/7 uLearn discussion forum.','Complete all learning modules and achieve at least 50% in every summative assessment component.',1,'open','ready',NULL,NULL,7,'2026-10-15 01:42:39','2026-11-04 01:42:39','2026-11-30',NULL,6),(12,1,'2026/1-DS','2026-05-10','2026-08-20','online',35,'2026-04-10','2026-05-05',150.00,'Dr. Aina Zulkifli','All learners','uLearn-DS-2026-1','Inaugural Data Structures cohort (completed).','Introductory programming.','16 weeks','Weekly Teams clinic','Pass all 3 assessments >= 50%',1,'ended','ready',NULL,NULL,6,'2026-04-01 09:00:00','2026-11-06 00:56:08','2026-06-01','2026-08-22 10:00:00',6),(13,9,'2026/1-SSDLC','2026-08-25','2026-10-20','online',30,'2026-08-01','2026-08-22',200.00,'Dr. Lim Wei Jie','Developers','uLearn-SSDLC-2026-1','Secure SDLC & DevSecOps cohort.','Git and web basics.','12 weeks','Forum & lab clinic','Pass both modules >= 50%',1,'ended','ready',NULL,NULL,7,'2026-07-28 09:00:00','2026-11-05 09:00:00','2026-09-10','2026-10-22 14:00:00',6),(14,18,'2026/1-OOP','2026-07-01','2026-09-30','online',40,'2026-06-01','2026-06-28',180.00,'Dr. Aina Zulkifli','CS learners','uLearn-OOP-2026-1','Object-Oriented Software Construction cohort.','BITD 1113 or equivalent.','16 weeks','Weekly code review','Pass both modules >= 50%',1,'ended','ready',NULL,NULL,6,'2026-05-25 09:00:00','2026-11-06 00:56:08','2026-07-20','2026-10-02 11:00:00',6),(15,21,'2026/1-MBA','2026-08-01','2026-10-01','online',25,'2026-07-10','2026-07-29',300.00,'Dr. Farah Nadia binti Kassim','Executives','uLearn-MBA-2026-1','Executive Technopreneurship cohort.','Bachelor degree or 3 yrs.','12 weeks','Executive mentoring','Pass >= 60%',1,'ended','ready',NULL,NULL,24,'2026-07-05 09:00:00','2026-11-05 09:00:00','2026-08-15','2026-10-05 16:00:00',6),(16,24,'2026/1-DL','2026-08-15','2026-10-25','online',30,'2026-07-25','2026-08-12',250.00,'Dr. Lim Wei Jie','AI engineers','uLearn-DL-2026-1','Deep Learning & Transformers cohort.','Python & linear algebra.','14 weeks','GPU lab clinic','Pass >= 60%',1,'ended','ready',NULL,NULL,7,'2026-07-20 09:00:00','2026-11-05 09:00:00','2026-09-01','2026-10-28 15:00:00',6),(17,5,'2027/1-DBS','2027-01-15','2027-04-10','online',35,'2026-11-15','2027-01-10',160.00,'Dr. Aina Zulkifli','All learners','uLearn-DBS-2027-1','Upcoming Database Systems cohort.','Basic SQL.','16 weeks','Weekly clinic','Pass >= 50%',1,'ready','ready',NULL,NULL,6,'2026-10-30 09:00:00','2026-11-05 09:00:00','2027-02-01',NULL,6),(18,6,'2027/1-ADA','2027-02-01','2027-04-20','online',30,'2026-12-01','2027-01-25',180.00,'Dr. Aina Zulkifli','Analysts','uLearn-ADA-2027-1','Draft cohort in preparation.','Python basics.','12 weeks','Forum','Pass >= 50%',0,'not_ready','preparing',NULL,NULL,6,'2026-11-01 09:00:00','2026-11-06 00:56:08','2027-02-15',NULL,6),(19,3,'2026/LE-CANC','2026-09-01','2026-09-15','blended',20,'2026-08-01','2026-08-25',0.00,'Dr. Aina Zulkifli','Team leads','uLearn-LE-CANC','Cancelled executive cohort.','None','4 weeks','Workshop','Portfolio',0,'cancelled','ready',NULL,'Minimum cohort size (10 participants) not met at registration close; enrolled learners transferred to 2026/LE1.',6,'2026-07-25 09:00:00','2026-11-05 09:00:00','2026-09-03',NULL,3),(20,26,'2026/PLC-V1','2026-04-01','2026-06-15','online',25,'2026-03-05','2026-03-28',180.00,'Assoc. Prof. Dr. Ahmad Razali','Technicians','uLearn-PLC-V1','Superseded v1 cohort.','Basic circuits.','12 weeks','Forum','Pass >= 50%',0,'ended','ready',NULL,'Superseded by v2 IEC 61131-3 & OPC-UA curriculum.',8,'2026-03-01 09:00:00','2026-11-06 00:56:08','2026-04-15',NULL,6);
/*!40000 ALTER TABLE `offerings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_courses`
--

DROP TABLE IF EXISTS `parent_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parent_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credit` smallint unsigned NOT NULL,
  `slt_hours` smallint unsigned NOT NULL,
  `clos` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `programme_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_courses_code_unique` (`code`),
  KEY `parent_courses_programme_id_foreign` (`programme_id`),
  CONSTRAINT `parent_courses_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_courses`
--

LOCK TABLES `parent_courses` WRITE;
/*!40000 ALTER TABLE `parent_courses` DISABLE KEYS */;
INSERT INTO `parent_courses` VALUES (1,'BITS 1123','Data Structures and Algorithms',3,120,'Explain core data structures;Implement algorithms in code;Analyse complexity','2024-11-04 01:42:39','2026-11-04 01:42:39',1),(2,'BITS 1124','Advanced Algorithms',3,120,'Design divide-and-conquer algorithms;Solve dynamic programming models','2024-11-04 01:42:39','2026-11-04 01:42:39',1),(3,'BITS 1125','Database Systems',3,120,'Design relational schemas;Execute complex SQL;Ensure transaction ACID properties','2024-11-04 01:42:39','2026-11-04 01:42:39',1),(4,'BENE 2133','Microprocessor Systems',3,120,'Program embedded microcontrollers;Interface peripheral hardware devices','2024-11-04 01:42:39','2026-11-04 01:42:39',3),(5,'BITD 1233','Database Systems',3,120,'Design relational databases;Write SQL queries;Apply normalization','2026-11-04 02:50:09','2026-11-04 02:50:09',2),(6,'BITD 2213','Database Administration',3,120,'Configure database instances;Manage user permissions;Perform backup and recovery','2026-11-04 02:50:09','2026-11-04 02:50:09',2),(7,'BITD 2223','Data Warehousing and Mining',3,120,'Construct data marts;Extract insights;Perform ETL operations','2026-11-04 02:50:09','2026-11-04 02:50:09',2),(8,'BITD 3213','Advanced Database Management',3,120,'Implement NoSQL databases;Optimize query performance;Ensure high availability','2026-11-04 02:50:09','2026-11-04 02:50:09',2),(9,'BITD 1113','Programming Fundamentals',3,120,'Write structured algorithms;Implement variables and loops;Debug source code','2026-11-04 02:50:09','2026-11-04 02:50:09',2),(10,'BITD 1123','Object-Oriented Programming',3,120,'Apply OOP concepts;Implement class hierarchies;Handle exceptions','2026-11-04 02:50:09','2026-11-04 02:50:09',2),(11,'BENE 1123','Electric Circuit Theory',3,120,'Analyze AC/DC circuits;Apply Kirchhoff laws','2026-11-04 02:50:09','2026-11-04 02:50:09',3),(12,'BMKG 1113','Engineering Mechanics',3,120,'Apply statics and dynamics;Solve equilibrium equations','2026-11-04 02:50:09','2026-11-04 02:50:09',4),(13,'MBA 5113','Strategic Management in Technopreneurship',3,120,'Formulate strategic plans;Evaluate competitive environments;Align execution','2026-11-04 02:50:09','2026-11-04 02:50:09',5),(14,'MECE 5113','Embedded Internet of Things (IoT) Systems',3,120,'Design embedded microcontroller architectures;Implement sensor protocols','2026-11-04 02:50:09','2026-11-04 02:50:09',6),(15,'BMFG 1113','Manufacturing Processes & Systems',3,120,'Explain modern machining and forming processes;Optimize manufacturing parameters','2026-11-04 02:50:09','2026-11-04 02:50:09',7),(16,'MAXL 5113','Fundamental of Data Science',3,120,'Explain foundational data science concepts;Analyze raw data structures','2026-11-04 02:50:09','2026-11-04 02:50:09',8),(17,'BITS 2313','Computer Networks and Distributed Systems',3,120,'Analyze OSI and TCP/IP network protocols;Configure enterprise routing and switching;Design resilient distributed systems','2024-11-04 01:42:39','2026-11-04 01:42:39',1),(18,'BITS 3423','Cloud and Microservices Architecture',3,120,'Package cloud-ready container images;Deploy resilient microservices workloads;Implement CI/CD and cloud observability','2024-11-04 01:42:39','2026-11-04 01:42:39',1),(19,'BITD 3313','Applied Predictive Analytics',3,120,'Build predictive models using statistical toolkits;Design interactive data visualizations;Evaluate model accuracy and bias','2024-11-04 01:42:39','2026-11-04 01:42:39',2),(20,'BENE 3123','Power Electronics and Renewable Grid Integration',3,120,'Analyze power converter topologies;Design PWM inverter controllers;Assess grid harmonic distortion','2024-11-04 01:42:39','2026-11-04 01:42:39',3),(21,'BMKG 2213','Applied Thermodynamics and Heat Transfer',3,120,'Apply first and second laws of thermodynamics;Analyze power and refrigeration cycles;Calculate conduction and convection heat transfer rates','2024-11-04 01:42:39','2026-11-04 01:42:39',4),(22,'MBA 5223','Financial Analytics and Venture Valuation',3,120,'Evaluate capital budgeting and DCF models;Analyze startup financial statements;Structure venture capital term sheets','2024-11-04 01:42:39','2026-11-04 01:42:39',5),(23,'MECE 5213','VLSI and System-on-Chip Hardware Acceleration',3,120,'Synthesize RTL digital logic in Verilog/SystemVerilog;Optimize FPGA timing and resource utilization;Interface hardware accelerators over AXI bus','2024-11-04 01:42:39','2026-11-04 01:42:39',6),(24,'BMFG 2123','Industrial Robotics and PLC Automation',3,120,'Program industrial PLC ladder logic;Model robotic manipulator kinematics;Integrate Industry 4.0 SCADA safety interlocks','2024-11-04 01:42:39','2026-11-04 01:42:39',7),(25,'BAIX 3113','Deep Learning and Generative AI Systems',3,120,'Design transformer and attention architectures;Fine-tune large language models with retrieval augmentation;Evaluate AI safety and hallucination guardrails','2024-11-04 01:42:39','2026-11-04 01:42:39',9),(26,'BCSX 2213','Security Operations and Incident Response',3,120,'Detect network security anomalies in SIEM;Execute incident containment playbooks;Perform digital forensics triage','2024-11-04 01:42:39','2026-11-04 01:42:39',10);
/*!40000 ALTER TABLE `parent_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES ('dan.ong@learner.example.test','$2y$10$wK1k9q8mQ3LqV6eU9Q.ZuebZ2mH8wK7zY.3bQ1L8y1k8mQ3LqV6eU','2026-11-04 00:42:39'),('faiz.kamarudin@learner.example.test','$2y$10$wK1k9q8mQ3LqV6eU9Q.ZuebZ2mH8wK7zY.3bQ1L8y1k8mQ3LqV6eU','2026-11-04 06:00:00'),('hafizuddin.othman@utem.example.test','$2y$10$zY.3bQ1L8y1k8mQ3LqV6eU9Q.ZuebZ2mH8wK7wK1k9q8mQ3LqV6eU','2026-11-04 07:30:00'),('jason.lee@industry.example.test','$2y$10$zY.3bQ1L8y1k8mQ3LqV6eU9Q.ZuebZ2mH8wK7wK1k9q8mQ3LqV6eU','2026-11-03 23:42:39');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pathway_items`
--

DROP TABLE IF EXISTS `pathway_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pathway_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pathway_id` bigint unsigned NOT NULL,
  `micro_credential_id` bigint unsigned NOT NULL,
  `parent_course_id` bigint unsigned NOT NULL,
  `credits` decimal(5,1) NOT NULL,
  `lo_equivalence` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_equivalence` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preliminary` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plo_map` json DEFAULT NULL,
  `bok_map` json DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pathway_items_mc_pc_unique` (`pathway_id`,`micro_credential_id`,`parent_course_id`),
  KEY `pathway_items_micro_credential_id_foreign` (`micro_credential_id`),
  KEY `pathway_items_parent_course_id_foreign` (`parent_course_id`),
  CONSTRAINT `pathway_items_micro_credential_id_foreign` FOREIGN KEY (`micro_credential_id`) REFERENCES `micro_credentials` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pathway_items_parent_course_id_foreign` FOREIGN KEY (`parent_course_id`) REFERENCES `parent_courses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pathway_items_pathway_id_foreign` FOREIGN KEY (`pathway_id`) REFERENCES `pathways` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pathway_items`
--

LOCK TABLES `pathway_items` WRITE;
/*!40000 ALTER TABLE `pathway_items` DISABLE KEYS */;
INSERT INTO `pathway_items` VALUES (1,1,1,1,3.0,'high','high','suitable','[{\"plo\": \"PLO1\", \"contribution\": \"main\"}, {\"plo\": \"PLO2\", \"contribution\": \"supporting\"}]','[\"Algorithms\"]','Maps Data Structures MC directly to core course BITS 1123.'),(2,1,2,2,2.0,'high','partial','suitable','[{\"plo\": \"PLO4\", \"contribution\": \"main\"}]','[\"Cloud Computing\"]','Elective specialization credit.'),(3,2,5,3,3.0,'high','high','suitable','[{\"plo\": \"PLO1\", \"contribution\": \"main\"}]','[\"Database Administration\"]','Draft mapping for Database Systems.'),(4,1,17,10,3.0,'high','high','suitable','[{\"plo\": \"PLO1\", \"contribution\": \"main\"}, {\"plo\": \"PLO2\", \"contribution\": \"main\"}]','[\"Software Engineering\"]','Unbundled OOP course equivalence.'),(5,1,8,17,2.0,'high','partial','suitable','[{\"plo\": \"PLO4\", \"contribution\": \"main\"}]','[\"Software Engineering\"]','Maps SSDLC to Secure Software Engineering.'),(6,2,17,10,3.0,'high','high','suitable','[{\"plo\": \"PLO1\", \"contribution\": \"main\"}]','[\"Database Administration\"]','Core programming foundation for BITD.'),(7,3,20,13,3.0,'high','high','suitable','[{\"plo\": \"PLO1\", \"contribution\": \"main\"}, {\"plo\": \"PLO5\", \"contribution\": \"main\"}]','[\"Strategic Management\", \"Technopreneurship\"]','Direct MBA 5113 credit exemption.'),(8,4,16,16,2.0,'high','high','suitable','[{\"plo\": \"PLO1\", \"contribution\": \"main\"}]','[\"Data Science\"]','Maps to MAXL 5113.'),(9,4,23,25,3.0,'high','high','suitable','[{\"plo\": \"PLO3\", \"contribution\": \"main\"}, {\"plo\": \"PLO4\", \"contribution\": \"main\"}]','[\"Deep Learning\"]','Maps to MAXL 5143.');
/*!40000 ALTER TABLE `pathway_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pathways`
--

DROP TABLE IF EXISTS `pathways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pathways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `programme_id` bigint unsigned NOT NULL,
  `version_no` smallint unsigned NOT NULL DEFAULT '1',
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `plos` json DEFAULT NULL,
  `bok` json DEFAULT NULL,
  `approval_ref` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_on` date DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `review_reason` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pathways_programme_id_version_no_unique` (`programme_id`,`version_no`),
  UNIQUE KEY `pathways_reference_unique` (`reference`),
  KEY `pathways_approved_by_foreign` (`approved_by`),
  KEY `pathways_created_by_foreign` (`created_by`),
  CONSTRAINT `pathways_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pathways_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pathways_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pathways`
--

LOCK TABLES `pathways` WRITE;
/*!40000 ALTER TABLE `pathways` DISABLE KEYS */;
INSERT INTO `pathways` VALUES (1,'PW-BITS-2026-01',1,1,'published','[\"PLO1\", \"PLO2\", \"PLO4\"]','[\"Algorithms\", \"Software Engineering\", \"Cloud Computing\"]','Senate 7/2026','2026-08-04',10,'2026-08-04 01:42:39',NULL,10,'2026-07-04 01:42:39','2026-11-04 01:42:39'),(2,'PW-BITD-2026-01',2,1,'draft','[\"PLO1\", \"PLO3\"]','[\"Database Administration\", \"Distributed Systems\"]',NULL,NULL,NULL,NULL,NULL,10,'2026-10-04 01:42:39','2026-11-04 01:42:39'),(3,'PW-MBA-2026-01',5,1,'published','[\"PLO1\", \"PLO2\", \"PLO5\"]','[\"Strategic Management\", \"Technopreneurship\", \"Venture Finance\"]','Senate 6/2026','2026-07-25',25,'2026-07-26 10:00:00',NULL,25,'2026-07-01 10:00:00','2026-11-05 09:00:00'),(4,'PW-MTDL-2026-01',8,1,'published','[\"PLO1\", \"PLO3\", \"PLO4\"]','[\"Data Science\", \"Deep Learning\", \"Statistical Modelling\"]','Senate 6/2026','2026-08-12',25,'2026-08-15 10:00:00',NULL,25,'2026-07-15 10:00:00','2026-11-05 09:00:00'),(5,'PW-BENE-2026-01',3,1,'in_review','[\"PLO1\", \"PLO2\"]','[\"Circuit Analysis\", \"Power Electronics\", \"Microprocessors\"]',NULL,NULL,NULL,NULL,'Triggered for review following v2 PLC material revision.',11,'2026-09-01 10:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `pathways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enrolment_id` bigint unsigned NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dummy_card',
  `card_last4` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_ref` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_reference_unique` (`reference`),
  KEY `payments_enrolment_id_foreign` (`enrolment_id`),
  CONSTRAINT `payments_enrolment_id_foreign` FOREIGN KEY (`enrolment_id`) REFERENCES `enrolments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,150.00,'fpx',NULL,'PAY-2026-001','FPX-TXN-984712','succeeded',NULL,'2026-05-13 01:42:39'),(2,2,150.00,'dummy_card','1111','PAY-2026-002','STRIPE-CHG-584719','succeeded',NULL,'2026-05-14 01:42:39'),(3,8,150.00,'fpx',NULL,'PAY-2026-003','FPX-TXN-102938','failed','FPX bank authorization timed out; awaiting learner retry.','2026-11-03 01:42:39'),(4,8,150.00,'dummy_card','0002','PAY-2026-004',NULL,'failed','Card declined by issuer: insufficient funds.','2026-11-02 01:42:39'),(5,9,150.00,'fpx',NULL,'PAY-2026-005','FPX-20260420-001','succeeded',NULL,'2026-04-20 10:05:00'),(6,10,150.00,'dummy_card','4242','PAY-2026-006','CARD-20260421-002','succeeded',NULL,'2026-04-21 10:05:00'),(7,11,150.00,'fpx',NULL,'PAY-2026-007','FPX-20260422-003','succeeded',NULL,'2026-04-22 10:05:00'),(8,12,150.00,'fpx',NULL,'PAY-2026-008','FPX-20260423-004','succeeded',NULL,'2026-04-23 10:05:00'),(9,13,200.00,'fpx',NULL,'PAY-2026-009','FPX-20260810-005','succeeded',NULL,'2026-08-10 10:05:00'),(10,14,200.00,'dummy_card','8812','PAY-2026-010','CARD-20260811-006','succeeded',NULL,'2026-08-11 10:05:00'),(11,15,200.00,'dummy_card','5519','PAY-2026-011','CARD-20260812-007','succeeded',NULL,'2026-08-12 10:05:00'),(12,16,200.00,'fpx',NULL,'PAY-2026-012','FPX-20260813-008','succeeded',NULL,'2026-08-13 10:05:00'),(13,17,180.00,'fpx',NULL,'PAY-2026-013','FPX-20260615-009','succeeded',NULL,'2026-06-15 10:05:00'),(14,18,180.00,'dummy_card','3190','PAY-2026-014','CARD-20260616-010','succeeded',NULL,'2026-06-16 10:05:00'),(15,19,300.00,'fpx',NULL,'PAY-2026-015','FPX-20260718-011','succeeded',NULL,'2026-07-18 10:05:00'),(16,20,250.00,'dummy_card','7741','PAY-2026-016','CARD-20260805-012','succeeded',NULL,'2026-08-05 10:05:00'),(17,24,200.00,'fpx',NULL,'PAY-2026-017','FPX-20261101-013','succeeded',NULL,'2026-11-01 09:35:00'),(18,26,220.00,'dummy_card','9012','PAY-2026-018','CARD-20261103-014','succeeded',NULL,'2026-11-03 09:35:00');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pdpa_acknowledgements`
--

DROP TABLE IF EXISTS `pdpa_acknowledgements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pdpa_acknowledgements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `notice_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acknowledged_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdpa_acknowledgements_user_id_notice_version_unique` (`user_id`,`notice_version`),
  CONSTRAINT `pdpa_acknowledgements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pdpa_acknowledgements`
--

LOCK TABLES `pdpa_acknowledgements` WRITE;
/*!40000 ALTER TABLE `pdpa_acknowledgements` DISABLE KEYS */;
INSERT INTO `pdpa_acknowledgements` VALUES (1,1,'v2.1','2026-05-04 01:42:39'),(2,2,'v2.1','2026-06-04 01:42:39'),(3,3,'v2.1','2026-07-04 01:42:39'),(4,4,'v2.1','2026-08-04 01:42:39'),(5,5,'v2.0','2026-01-10 10:00:00'),(6,1,'v2.0','2025-11-15 09:00:00'),(7,18,'v2.1','2026-04-12 11:20:00'),(8,19,'v2.1','2026-05-15 14:30:00'),(9,20,'v2.1','2026-05-22 16:10:00'),(10,21,'v2.1','2026-06-18 10:05:00');
/*!40000 ALTER TABLE `pdpa_acknowledgements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periodic_reviews`
--

DROP TABLE IF EXISTS `periodic_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periodic_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `micro_credential_id` bigint unsigned NOT NULL,
  `due_on` date NOT NULL,
  `completed_on` date DEFAULT NULL,
  `outcome` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `decided_by` bigint unsigned DEFAULT NULL,
  `reminded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `periodic_reviews_micro_credential_id_foreign` (`micro_credential_id`),
  KEY `periodic_reviews_decided_by_foreign` (`decided_by`),
  CONSTRAINT `periodic_reviews_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `periodic_reviews_micro_credential_id_foreign` FOREIGN KEY (`micro_credential_id`) REFERENCES `micro_credentials` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periodic_reviews`
--

LOCK TABLES `periodic_reviews` WRITE;
/*!40000 ALTER TABLE `periodic_reviews` DISABLE KEYS */;
INSERT INTO `periodic_reviews` VALUES (1,1,'2028-11-04',NULL,NULL,'3-year periodic academic review scheduled with FTMK Board.',10,NULL,'2026-05-04 01:42:39','2026-11-04 01:42:39'),(2,2,'2026-12-15','2026-10-30','continue','Full periodic review cycle completed; renewed for next cycle.',10,NULL,'2025-11-04 01:42:39','2026-11-04 01:42:39'),(3,2,'2028-06-04','2026-11-04','continue',NULL,6,NULL,'2026-11-04 02:21:25','2026-11-04 02:40:56'),(4,3,'2028-08-04','2026-11-04','continue','test',6,NULL,'2026-11-04 02:21:25','2026-11-04 02:41:03'),(5,7,'2028-10-15',NULL,NULL,NULL,NULL,NULL,'2026-11-04 02:21:25','2026-11-04 02:21:25'),(6,2,'2028-11-04',NULL,NULL,NULL,NULL,NULL,'2026-11-04 02:40:56','2026-11-04 02:40:56'),(7,3,'2028-11-04',NULL,NULL,NULL,NULL,NULL,'2026-11-04 02:41:03','2026-11-04 02:41:03'),(8,5,'2028-11-04',NULL,NULL,NULL,NULL,NULL,'2026-11-04 03:10:49','2026-11-04 03:10:49'),(9,6,'2028-11-04',NULL,NULL,NULL,NULL,NULL,'2026-11-04 03:10:49','2026-11-04 03:10:49'),(10,1,'2026-10-01','2026-10-04','minor_cqi','Completed annual review; minor update v2 issued for Python 3.12.',10,'2026-09-15 09:00:00','2026-05-01 09:00:00','2026-11-05 09:00:00'),(11,25,'2026-10-10','2026-10-12','reapproval','Material revision v2 (IEC 61131-3 & OPC-UA) reapproved by Senate.',11,'2026-09-20 09:00:00','2026-06-01 09:00:00','2026-11-05 09:00:00'),(12,8,'2026-11-20',NULL,NULL,'Upcoming review for Secure SDLC.',NULL,'2026-11-01 09:00:00','2026-08-20 09:00:00','2026-11-05 09:00:00'),(13,15,'2028-09-01',NULL,NULL,NULL,NULL,NULL,'2026-11-05 07:50:00','2026-11-05 07:50:00'),(14,16,'2028-09-01',NULL,NULL,NULL,NULL,NULL,'2026-11-05 07:50:00','2026-11-05 07:50:00'),(15,12,'2028-09-01',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34'),(16,13,'2028-09-01',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34'),(17,14,'2028-09-01',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34'),(18,17,'2028-06-20',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34'),(19,18,'2028-09-20',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34'),(20,19,'2028-09-25',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34'),(21,20,'2028-07-25',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34'),(22,21,'2028-09-28',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34'),(23,22,'2028-10-08',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34'),(24,23,'2028-08-12',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34'),(25,25,'2028-10-15',NULL,NULL,NULL,NULL,NULL,'2026-11-06 00:29:34','2026-11-06 00:29:34');
/*!40000 ALTER TABLE `periodic_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plan_requirements`
--

DROP TABLE IF EXISTS `plan_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stackable_plan_id` bigint unsigned NOT NULL,
  `requirement_key` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `met` tinyint(1) NOT NULL,
  `detail` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `checked_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `plan_requirements_stackable_plan_id_foreign` (`stackable_plan_id`),
  CONSTRAINT `plan_requirements_stackable_plan_id_foreign` FOREIGN KEY (`stackable_plan_id`) REFERENCES `stackable_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plan_requirements`
--

LOCK TABLES `plan_requirements` WRITE;
/*!40000 ALTER TABLE `plan_requirements` DISABLE KEYS */;
INSERT INTO `plan_requirements` VALUES (123,1,'home_credit',1,'100% of the 120 graduating credits would be earned directly at UTeM; at least 30% is required. Meeting 30% does not mean the rest can be transferred automatically.','2026-11-06 00:52:11'),(124,1,'residency',1,'Residency planned: 6 semester(s); at least 1 required for a conventional programme.','2026-11-06 00:52:11'),(125,1,'credit_bearing',1,'Every credential counted toward the degree is credit-bearing (UMC or Quality-Verified SAMC). An MCE never counts.','2026-11-06 00:52:11'),(126,1,'plo_mapping',1,'Every credential counted is mapped to programme learning outcomes (BR-36).','2026-11-06 00:52:11'),(127,1,'remaining',1,'4 remaining requirement(s) listed.','2026-11-06 00:52:11'),(128,2,'home_credit',1,'100% of the 120 graduating credits would be earned directly at UTeM; at least 30% is required. Meeting 30% does not mean the rest can be transferred automatically.','2026-11-06 00:52:11'),(129,2,'residency',1,'Residency planned: 6 semester(s); at least 1 required for a conventional programme.','2026-11-06 00:52:11'),(130,2,'credit_bearing',1,'Every credential counted toward the degree is credit-bearing (UMC or Quality-Verified SAMC). An MCE never counts.','2026-11-06 00:52:11'),(131,2,'plo_mapping',1,'Every credential counted is mapped to programme learning outcomes (BR-36).','2026-11-06 00:52:11'),(132,2,'remaining',1,'4 remaining requirement(s) listed.','2026-11-06 00:52:11'),(133,3,'home_credit',1,'100% of the 40 graduating credits would be earned directly at UTeM; at least 30% is required. Meeting 30% does not mean the rest can be transferred automatically.','2026-11-06 00:52:11'),(134,3,'residency',1,'Not required for this programme mode.','2026-11-06 00:52:11'),(135,3,'credit_bearing',1,'Every credential counted toward the degree is credit-bearing (UMC or Quality-Verified SAMC). An MCE never counts.','2026-11-06 00:52:11'),(136,3,'plo_mapping',1,'Every credential counted is mapped to programme learning outcomes (BR-36).','2026-11-06 00:52:11'),(137,3,'remaining',1,'2 remaining requirement(s) listed.','2026-11-06 00:52:11'),(138,4,'home_credit',1,'100% of the 40 graduating credits would be earned directly at UTeM; at least 30% is required. Meeting 30% does not mean the rest can be transferred automatically.','2026-11-06 00:52:11'),(139,4,'residency',1,'Not required for this programme mode.','2026-11-06 00:52:11'),(140,4,'credit_bearing',1,'Every credential counted toward the degree is credit-bearing (UMC or Quality-Verified SAMC). An MCE never counts.','2026-11-06 00:52:11'),(141,4,'plo_mapping',1,'Every credential counted is mapped to programme learning outcomes (BR-36).','2026-11-06 00:52:11'),(142,4,'remaining',1,'2 remaining requirement(s) listed.','2026-11-06 00:52:11');
/*!40000 ALTER TABLE `plan_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programmes`
--

DROP TABLE IF EXISTS `programmes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `programmes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mqf_level` tinyint unsigned NOT NULL,
  `mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accreditation_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_credits` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ptj_id` bigint unsigned DEFAULT NULL,
  `transfer_limit_percent` tinyint unsigned NOT NULL DEFAULT '50',
  `non_transferable` text COLLATE utf8mb4_unicode_ci,
  `curriculum_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2024',
  `intake` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `programmes_code_unique` (`code`),
  KEY `programmes_ptj_id_foreign` (`ptj_id`),
  CONSTRAINT `programmes_ptj_id_foreign` FOREIGN KEY (`ptj_id`) REFERENCES `ptjs` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programmes`
--

LOCK TABLES `programmes` WRITE;
/*!40000 ALTER TABLE `programmes` DISABLE KEYS */;
INSERT INTO `programmes` VALUES (1,'BITS','Bachelor of Computer Science (Software Development) with Honours',6,'conventional','accredited',120,'2024-11-04 01:42:39','2026-11-04 01:42:39',1,50,'[\"BITU 3983\",\"BITU 3926\"]','2024','2026/1'),(2,'BITD','Bachelor of Computer Science (Database Management) with Honours',6,'conventional','accredited',122,'2024-11-04 01:42:39','2026-11-04 01:42:39',1,50,'[\"BITU 3973\",\"BITU 3926\"]','2024','2026/1'),(3,'BENE','Bachelor of Electrical Engineering Technology with Honours',6,'conventional','accredited',130,'2024-11-04 01:42:39','2026-11-04 01:42:39',2,30,'[\"BENE 4984\",\"BENE 4926\"]','2023','2026/1'),(4,'BMKG','Bachelor of Mechanical Engineering with Honours',6,'conventional','accredited',135,'2024-11-04 01:42:39','2026-11-04 01:42:39',3,30,'[\"BMKG 4984\"]','2023','2026/1'),(5,'MBA','Master of Business Administration',7,'odl','accredited',40,'2024-11-04 01:42:39','2026-11-04 01:42:39',4,50,NULL,'2025','2026/1'),(6,'MECE','Master of Electronic Engineering (Computer Engineering)',7,'odl','accredited',40,'2024-11-04 01:42:39','2026-11-04 01:42:39',5,50,NULL,'2025','2026/1'),(7,'BMFG','Bachelor of Manufacturing Engineering Technology with Honours',6,'conventional','accredited',130,'2024-11-04 01:42:39','2026-11-04 01:42:39',6,30,NULL,'2024','2026/1'),(8,'MTDL','Master of Technology in Data Science and Analytics',7,'odl','accredited',40,'2024-11-04 01:42:39','2026-11-04 01:42:39',7,50,NULL,'2025','2026/1'),(9,'BAIX','Bachelor of Computer Science (Artificial Intelligence) with Honours',6,'conventional','accredited',120,'2024-11-04 01:42:39','2026-11-04 01:42:39',7,30,'[\"BAIX 3983\"]','2024','2026/1'),(10,'BCSX','Bachelor of Computer Science (Cybersecurity) with Honours',6,'conventional','provisional',120,'2025-02-01 01:42:39','2026-11-04 01:42:39',7,30,'[\"BCSX 3983\"]','2025','2026/1'),(11,'BTEC','Bachelor of Technopreneurship with Honours',6,'conventional','accredited',120,'2024-11-04 01:42:39','2026-11-04 01:42:39',4,30,NULL,'2024','2026/1'),(12,'BERE','Bachelor of Electronic Engineering with Honours',6,'conventional','accredited',135,'2024-11-04 01:42:39','2026-11-04 01:42:39',5,30,NULL,'2024','2026/1'),(13,'MITS','Master of Computer Science (Software Engineering)',7,'odl','accredited',40,'2025-01-10 01:42:39','2026-11-04 01:42:39',1,50,'[\"MITS 5998\"]','2025','2026/2'),(14,'BMAN','Bachelor of Industrial Engineering Technology with Honours',6,'conventional','provisional',126,'2025-03-01 01:42:39','2026-11-04 01:42:39',6,30,NULL,'2025','2026/1');
/*!40000 ALTER TABLE `programmes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proposal_readiness`
--

DROP TABLE IF EXISTS `proposal_readiness`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proposal_readiness` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_version_id` bigint unsigned NOT NULL,
  `item` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_yet',
  `note` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposal_readiness_mc_version_id_item_unique` (`mc_version_id`,`item`),
  CONSTRAINT `proposal_readiness_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proposal_readiness`
--

LOCK TABLES `proposal_readiness` WRITE;
/*!40000 ALTER TABLE `proposal_readiness` DISABLE KEYS */;
INSERT INTO `proposal_readiness` VALUES (1,1,'materials','yes','Verified against UTeM CODL readiness checklist.'),(2,1,'platform','yes','Verified against UTeM CODL readiness checklist.'),(3,1,'assessment','yes','Verified against UTeM CODL readiness checklist.'),(4,1,'facilitators','yes','Verified against UTeM CODL readiness checklist.'),(5,1,'participant_info','yes','Verified against UTeM CODL readiness checklist.'),(6,1,'records','yes','Verified against UTeM CODL readiness checklist.'),(7,1,'accessibility','yes','Verified against UTeM CODL readiness checklist.'),(8,2,'materials','yes','Verified against UTeM CODL readiness checklist.'),(9,2,'platform','yes','Verified against UTeM CODL readiness checklist.'),(10,2,'assessment','yes','Verified against UTeM CODL readiness checklist.'),(11,2,'facilitators','yes','Verified against UTeM CODL readiness checklist.'),(12,2,'participant_info','yes','Verified against UTeM CODL readiness checklist.'),(13,2,'records','yes','Verified against UTeM CODL readiness checklist.'),(14,2,'accessibility','yes','Verified against UTeM CODL readiness checklist.'),(15,4,'materials','yes','Verified against UTeM CODL readiness checklist.'),(16,4,'platform','yes','Verified against UTeM CODL readiness checklist.'),(17,4,'assessment','yes','Verified against UTeM CODL readiness checklist.'),(18,4,'facilitators','yes','Verified against UTeM CODL readiness checklist.'),(19,4,'participant_info','yes','Verified against UTeM CODL readiness checklist.'),(20,4,'records','yes','Verified against UTeM CODL readiness checklist.'),(21,4,'accessibility','not_yet','Video captions being generated for Module 1.'),(22,5,'materials','yes','Verified against UTeM CODL readiness checklist.'),(23,5,'platform','yes','Verified against UTeM CODL readiness checklist.'),(24,5,'assessment','yes','Verified against UTeM CODL readiness checklist.'),(25,5,'facilitators','yes','Verified against UTeM CODL readiness checklist.'),(26,5,'participant_info','yes','Verified against UTeM CODL readiness checklist.'),(27,5,'records','yes','Verified against UTeM CODL readiness checklist.'),(28,5,'accessibility','yes','Verified against UTeM CODL readiness checklist.'),(29,9,'materials','yes','Verified against UTeM CODL readiness checklist.'),(30,9,'platform','yes','Verified against UTeM CODL readiness checklist.'),(31,9,'assessment','yes','Verified against UTeM CODL readiness checklist.'),(32,9,'facilitators','yes','Verified against UTeM CODL readiness checklist.'),(33,9,'participant_info','yes','Verified against UTeM CODL readiness checklist.'),(34,9,'records','yes','Verified against UTeM CODL readiness checklist.'),(35,9,'accessibility','yes','Verified against UTeM CODL readiness checklist.'),(36,10,'materials','yes','Verified against UTeM CODL readiness checklist.'),(37,10,'platform','yes','Verified against UTeM CODL readiness checklist.'),(38,10,'assessment','not_yet','Pending rubric moderation for LO2.'),(39,10,'facilitators','yes','Verified against UTeM CODL readiness checklist.'),(40,10,'participant_info','yes','Verified against UTeM CODL readiness checklist.'),(41,10,'records','yes','Verified against UTeM CODL readiness checklist.'),(42,10,'accessibility','yes','Verified against UTeM CODL readiness checklist.'),(43,12,'materials','yes','Verified against UTeM CODL readiness checklist.'),(44,12,'platform','yes','Verified against UTeM CODL readiness checklist.'),(45,12,'assessment','yes','Verified against UTeM CODL readiness checklist.'),(46,12,'facilitators','yes','Verified against UTeM CODL readiness checklist.'),(47,12,'participant_info','yes','Verified against UTeM CODL readiness checklist.'),(48,12,'records','yes','Verified against UTeM CODL readiness checklist.'),(49,12,'accessibility','yes','Verified against UTeM CODL readiness checklist.'),(50,18,'materials','yes','Verified against UTeM CODL readiness checklist.'),(51,18,'platform','yes','Verified against UTeM CODL readiness checklist.'),(52,18,'assessment','yes','Verified against UTeM CODL readiness checklist.'),(53,18,'facilitators','yes','Verified against UTeM CODL readiness checklist.'),(54,18,'participant_info','yes','Verified against UTeM CODL readiness checklist.'),(55,18,'records','yes','Verified against UTeM CODL readiness checklist.'),(56,18,'accessibility','yes','Verified against UTeM CODL readiness checklist.'),(57,21,'materials','yes','Verified against UTeM CODL readiness checklist.'),(58,21,'platform','yes','Verified against UTeM CODL readiness checklist.'),(59,21,'assessment','yes','Verified against UTeM CODL readiness checklist.'),(60,21,'facilitators','yes','Verified against UTeM CODL readiness checklist.'),(61,21,'participant_info','yes','Verified against UTeM CODL readiness checklist.'),(62,21,'records','yes','Verified against UTeM CODL readiness checklist.'),(63,21,'accessibility','yes','Verified against UTeM CODL readiness checklist.'),(64,24,'materials','yes','Verified against UTeM CODL readiness checklist.'),(65,24,'platform','yes','Verified against UTeM CODL readiness checklist.'),(66,24,'assessment','yes','Verified against UTeM CODL readiness checklist.'),(67,24,'facilitators','yes','Verified against UTeM CODL readiness checklist.'),(68,24,'participant_info','yes','Verified against UTeM CODL readiness checklist.'),(69,24,'records','yes','Verified against UTeM CODL readiness checklist.'),(70,24,'accessibility','yes','Verified against UTeM CODL readiness checklist.');
/*!40000 ALTER TABLE `proposal_readiness` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ptjs`
--

DROP TABLE IF EXISTS `ptjs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ptjs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ptjs_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ptjs`
--

LOCK TABLES `ptjs` WRITE;
/*!40000 ALTER TABLE `ptjs` DISABLE KEYS */;
INSERT INTO `ptjs` VALUES (1,'FTMK','Fakulti Teknologi Maklumat Dan Komunikasi','2024-11-04 01:42:39','2026-11-04 01:42:39'),(2,'FTKE','Fakulti Teknologi Dan Kejuruteraan Elektrik','2024-11-04 01:42:39','2026-11-04 01:42:39'),(3,'FTKM','Fakulti Teknologi Dan Kejuruteraan Mekanikal','2024-11-04 01:42:39','2026-11-04 01:42:39'),(4,'FPTT','Fakulti Pengurusan Teknologi Dan Teknousahawanan','2024-11-04 01:42:39','2026-11-04 01:42:39'),(5,'FTKEK','Fakulti Teknologi Dan Kejuruteraan Elektronik Dan Komputer','2024-11-04 01:42:39','2026-11-04 01:42:39'),(6,'FTKIP','Fakulti Teknologi Dan Kejuruteraan Industri Dan Pembuatan','2024-11-04 01:42:39','2026-11-04 01:42:39'),(7,'FAIX','Fakulti Kecerdasan Buatan Dan Keselamatan Siber','2024-11-04 01:42:39','2026-11-04 01:42:39');
/*!40000 ALTER TABLE `ptjs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qualification_awards`
--

DROP TABLE IF EXISTS `qualification_awards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qualification_awards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stackable_plan_id` bigint unsigned NOT NULL,
  `evidence_ref` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `awarded_on` date NOT NULL,
  `recorded_by` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `qualification_awards_stackable_plan_id_foreign` (`stackable_plan_id`),
  KEY `qualification_awards_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `qualification_awards_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `qualification_awards_stackable_plan_id_foreign` FOREIGN KEY (`stackable_plan_id`) REFERENCES `stackable_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qualification_awards`
--

LOCK TABLES `qualification_awards` WRITE;
/*!40000 ALTER TABLE `qualification_awards` DISABLE KEYS */;
INSERT INTO `qualification_awards` VALUES (1,1,'SENATE-AWARD-BITS-2026/04','2029-11-04',10,'2026-10-25 01:42:39'),(2,3,'SENATE-AWARD-MBA-2026/09','2026-10-25',25,'2026-10-25 10:00:00');
/*!40000 ALTER TABLE `qualification_awards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quality_domain_checks`
--

DROP TABLE IF EXISTS `quality_domain_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quality_domain_checks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cqi_record_id` bigint unsigned NOT NULL,
  `domain` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `met` tinyint(1) NOT NULL,
  `finding` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `checked_by` bigint unsigned NOT NULL,
  `checked_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quality_domain_checks_cqi_record_id_domain_unique` (`cqi_record_id`,`domain`),
  KEY `quality_domain_checks_checked_by_foreign` (`checked_by`),
  CONSTRAINT `quality_domain_checks_checked_by_foreign` FOREIGN KEY (`checked_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `quality_domain_checks_cqi_record_id_foreign` FOREIGN KEY (`cqi_record_id`) REFERENCES `cqi_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quality_domain_checks`
--

LOCK TABLES `quality_domain_checks` WRITE;
/*!40000 ALTER TABLE `quality_domain_checks` DISABLE KEYS */;
INSERT INTO `quality_domain_checks` VALUES (1,1,'content',1,'Domain \'content\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(2,1,'delivery',1,'Domain \'delivery\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(3,1,'assessment',1,'Domain \'assessment\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(4,1,'platform',1,'Domain \'platform\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(5,1,'support',1,'Domain \'support\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(6,1,'outcomes',1,'Domain \'outcomes\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(7,3,'content',1,'Domain \'content\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(8,3,'delivery',1,'Domain \'delivery\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(9,3,'assessment',1,'Domain \'assessment\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(10,3,'platform',1,'Domain \'platform\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(11,3,'support',1,'Domain \'support\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(12,3,'outcomes',1,'Domain \'outcomes\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(13,4,'content',1,'Domain \'content\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(14,4,'delivery',1,'Domain \'delivery\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(15,4,'assessment',1,'Domain \'assessment\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(16,4,'platform',0,'Local DAST container memory spike noted on learner laptops; cloud runner planned.',10,'2026-10-28 10:00:00'),(17,4,'support',1,'Domain \'support\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(18,4,'outcomes',1,'Domain \'outcomes\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(19,5,'content',1,'Domain \'content\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(20,5,'delivery',1,'Domain \'delivery\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(21,5,'assessment',1,'Domain \'assessment\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(22,5,'platform',1,'Domain \'platform\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(23,5,'support',1,'Domain \'support\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(24,5,'outcomes',1,'Domain \'outcomes\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(25,6,'content',1,'Domain \'content\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(26,6,'delivery',1,'Domain \'delivery\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(27,6,'assessment',1,'Domain \'assessment\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(28,6,'platform',1,'Domain \'platform\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(29,6,'support',1,'Domain \'support\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00'),(30,6,'outcomes',1,'Domain \'outcomes\' met cohort quality threshold (>= 80% satisfaction).',10,'2026-10-28 10:00:00');
/*!40000 ALTER TABLE `quality_domain_checks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qv_evidence`
--

DROP TABLE IF EXISTS `qv_evidence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qv_evidence` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `qv_record_id` bigint unsigned NOT NULL,
  `item` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_yet',
  `file_ref` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `linked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `qv_evidence_qv_record_id_item_unique` (`qv_record_id`,`item`),
  CONSTRAINT `qv_evidence_qv_record_id_foreign` FOREIGN KEY (`qv_record_id`) REFERENCES `qv_records` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qv_evidence`
--

LOCK TABLES `qv_evidence` WRITE;
/*!40000 ALTER TABLE `qv_evidence` DISABLE KEYS */;
INSERT INTO `qv_evidence` VALUES (1,1,'academic_design','available','docs/qv/record_1_academic_design.pdf','Verified and linked to system dossier.',1),(2,1,'assessment_achievement','available','docs/qv/record_1_assessment_achievement.pdf','Verified and linked to system dossier.',1),(3,1,'delivery_support','available','docs/qv/record_1_delivery_support.pdf','Verified and linked to system dossier.',1),(4,1,'quality_cqi','available','docs/qv/record_1_quality_cqi.pdf','Verified and linked to system dossier.',1),(5,1,'credential','available','docs/qv/record_1_credential.pdf','Verified and linked to system dossier.',1),(6,2,'academic_design','available','docs/qv/record_2_academic_design.pdf','Verified and linked to system dossier.',1),(7,2,'assessment_achievement','available','docs/qv/record_2_assessment_achievement.pdf','Verified and linked to system dossier.',1),(8,2,'delivery_support','available','docs/qv/record_2_delivery_support.pdf','Verified and linked to system dossier.',1),(9,2,'quality_cqi','available','docs/qv/record_2_quality_cqi.pdf','Verified and linked to system dossier.',1),(10,2,'credential','available','docs/qv/record_2_credential.pdf','Verified and linked to system dossier.',1),(11,3,'academic_design','available','docs/qv/record_3_academic_design.pdf','Verified and linked to system dossier.',1),(12,3,'assessment_achievement','available','docs/qv/record_3_assessment_achievement.pdf','Verified and linked to system dossier.',1),(13,3,'delivery_support','available','docs/qv/record_3_delivery_support.pdf','Verified and linked to system dossier.',1),(14,3,'quality_cqi','not_yet',NULL,'Awaiting cohort CQI closure.',0),(15,3,'credential','not_yet',NULL,'Awaiting cohort CQI closure.',0),(16,4,'academic_design','available','docs/qv/record_4_academic_design.pdf','Verified and linked to system dossier.',1),(17,4,'assessment_achievement','available','docs/qv/record_4_assessment_achievement.pdf','Verified and linked to system dossier.',1),(18,4,'delivery_support','available','docs/qv/record_4_delivery_support.pdf','Verified and linked to system dossier.',1),(19,4,'quality_cqi','not_yet',NULL,'Awaiting cohort CQI closure.',0),(20,4,'credential','not_yet',NULL,'Awaiting cohort CQI closure.',0),(21,5,'academic_design','available','docs/qv/record_5_academic_design.pdf','Verified and linked to system dossier.',1),(22,5,'assessment_achievement','available','docs/qv/record_5_assessment_achievement.pdf','Verified and linked to system dossier.',1),(23,5,'delivery_support','available','docs/qv/record_5_delivery_support.pdf','Verified and linked to system dossier.',1),(24,5,'quality_cqi','available','docs/qv/record_5_quality_cqi.pdf','Verified and linked to system dossier.',1),(25,5,'credential','available','docs/qv/record_5_credential.pdf','Verified and linked to system dossier.',1),(26,6,'academic_design','available','docs/qv/record_6_academic_design.pdf','Verified and linked to system dossier.',1),(27,6,'assessment_achievement','available','docs/qv/record_6_assessment_achievement.pdf','Verified and linked to system dossier.',1),(28,6,'delivery_support','available','docs/qv/record_6_delivery_support.pdf','Verified and linked to system dossier.',1),(29,6,'quality_cqi','available','docs/qv/record_6_quality_cqi.pdf','Verified and linked to system dossier.',1),(30,6,'credential','available','docs/qv/record_6_credential.pdf','Verified and linked to system dossier.',1),(31,7,'academic_design','available','docs/qv/record_7_academic_design.pdf','Verified and linked to system dossier.',1),(32,7,'assessment_achievement','available','docs/qv/record_7_assessment_achievement.pdf','Verified and linked to system dossier.',1),(33,7,'delivery_support','available','docs/qv/record_7_delivery_support.pdf','Verified and linked to system dossier.',1),(34,7,'quality_cqi','available','docs/qv/record_7_quality_cqi.pdf','Verified and linked to system dossier.',1),(35,7,'credential','available','docs/qv/record_7_credential.pdf','Verified and linked to system dossier.',1),(36,8,'academic_design','available','docs/qv/record_8_academic_design.pdf','Verified and linked to system dossier.',1),(37,8,'assessment_achievement','available','docs/qv/record_8_assessment_achievement.pdf','Verified and linked to system dossier.',1),(38,8,'delivery_support','available','docs/qv/record_8_delivery_support.pdf','Verified and linked to system dossier.',1),(39,8,'quality_cqi','available','docs/qv/record_8_quality_cqi.pdf','Verified and linked to system dossier.',1),(40,8,'credential','available','docs/qv/record_8_credential.pdf','Verified and linked to system dossier.',1),(41,9,'academic_design','available','docs/qv/record_9_academic_design.pdf','Verified and linked to system dossier.',1),(42,9,'assessment_achievement','available','docs/qv/record_9_assessment_achievement.pdf','Verified and linked to system dossier.',1),(43,9,'delivery_support','available','docs/qv/record_9_delivery_support.pdf','Verified and linked to system dossier.',1),(44,9,'quality_cqi','available','docs/qv/record_9_quality_cqi.pdf','Verified and linked to system dossier.',1),(45,9,'credential','available','docs/qv/record_9_credential.pdf','Verified and linked to system dossier.',1),(46,10,'academic_design','available','docs/qv/record_10_academic_design.pdf','Verified and linked to system dossier.',1),(47,10,'assessment_achievement','available','docs/qv/record_10_assessment_achievement.pdf','Verified and linked to system dossier.',1),(48,10,'delivery_support','available','docs/qv/record_10_delivery_support.pdf','Verified and linked to system dossier.',1),(49,10,'quality_cqi','available','docs/qv/record_10_quality_cqi.pdf','Verified and linked to system dossier.',1),(50,10,'credential','available','docs/qv/record_10_credential.pdf','Verified and linked to system dossier.',1);
/*!40000 ALTER TABLE `qv_evidence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qv_records`
--

DROP TABLE IF EXISTS `qv_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qv_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `micro_credential_id` bigint unsigned NOT NULL,
  `cycle_no` smallint unsigned NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'preparing',
  `change_review` json DEFAULT NULL,
  `readiness_decision` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `readiness_missing` text COLLATE utf8mb4_unicode_ci,
  `target_date` date DEFAULT NULL,
  `readiness_by` bigint unsigned DEFAULT NULL,
  `readiness_confirmed_by` bigint unsigned DEFAULT NULL,
  `readiness_confirmed_at` timestamp NULL DEFAULT NULL,
  `submitted_on` date DEFAULT NULL,
  `submitted_ref` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_by` bigint unsigned DEFAULT NULL,
  `qv_reference` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_credit` decimal(5,1) DEFAULT NULL,
  `verified_mqf_level` tinyint unsigned DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `conditions` text COLLATE utf8mb4_unicode_ci,
  `decision_document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `outcome_note` text COLLATE utf8mb4_unicode_ci,
  `reverification` tinyint(1) NOT NULL DEFAULT '0',
  `decided_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qv_records_micro_credential_id_cycle_no_unique` (`micro_credential_id`,`cycle_no`),
  KEY `qv_records_readiness_by_foreign` (`readiness_by`),
  KEY `qv_records_readiness_confirmed_by_foreign` (`readiness_confirmed_by`),
  KEY `qv_records_submitted_by_foreign` (`submitted_by`),
  KEY `qv_records_decided_by_foreign` (`decided_by`),
  CONSTRAINT `qv_records_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `qv_records_micro_credential_id_foreign` FOREIGN KEY (`micro_credential_id`) REFERENCES `micro_credentials` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `qv_records_readiness_by_foreign` FOREIGN KEY (`readiness_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `qv_records_readiness_confirmed_by_foreign` FOREIGN KEY (`readiness_confirmed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `qv_records_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qv_records`
--

LOCK TABLES `qv_records` WRITE;
/*!40000 ALTER TABLE `qv_records` DISABLE KEYS */;
INSERT INTO `qv_records` VALUES (1,2,1,'verified',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2023-11-24','MQA/QV/2023/051',12,'QV/2023/051',2.0,4,'2023-12-15','2026-12-15',NULL,'qv-2023-051.pdf',NULL,0,10,'2023-11-04 01:42:39','2026-11-04 01:42:39'),(2,1,1,'verified',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-04','MQA/QV/2025/112',12,'QV/2025/112',2.0,6,'2026-03-04','2029-11-04',NULL,'qv-2025-112.pdf',NULL,0,10,'2026-01-04 01:42:39','2026-11-04 01:42:39'),(3,6,1,'ready',NULL,'ready',NULL,'2027-01-04',6,10,'2026-10-28 10:00:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-10-04 01:42:39','2026-11-04 01:42:39'),(4,2,2,'preparing',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-11-04 03:02:33','2026-11-04 03:02:33'),(5,8,1,'verified',NULL,'ready',NULL,'2026-08-10',7,10,'2026-08-05 10:00:00','2026-08-08','MQA/QV/2026/088',12,'QV/2026/088',2.0,6,'2026-08-15','2029-08-15',NULL,'qv-2026-088.pdf','Verified by MQA panel.',0,12,'2026-07-25 10:00:00','2026-11-05 09:00:00'),(6,17,1,'verified',NULL,'ready',NULL,'2026-06-15',6,10,'2026-06-10 10:00:00','2026-06-12','MQA/QV/2026/042',12,'QV/2026/042',3.0,6,'2026-06-30','2029-06-30',NULL,'qv-2026-042.pdf','Unbundled from accredited BITD 1123.',0,12,'2026-05-20 10:00:00','2026-11-05 09:00:00'),(7,20,1,'verified',NULL,'ready',NULL,'2026-07-20',24,25,'2026-07-15 10:00:00','2026-07-18','MQA/QV/2026/065',12,'QV/2026/065',3.0,7,'2026-07-31','2029-07-31',NULL,'qv-2026-065.pdf','Postgraduate MBA UMC verified.',0,12,'2026-06-25 10:00:00','2026-11-05 09:00:00'),(8,23,1,'verified',NULL,'ready',NULL,'2026-08-20',7,25,'2026-08-12 10:00:00','2026-08-15','MQA/QV/2026/094',12,'QV/2026/094',3.0,7,'2026-08-30','2029-08-30',NULL,'qv-2026-094.pdf','Verified for FAIX MTDL.',0,12,'2026-07-15 10:00:00','2026-11-05 09:00:00'),(9,5,1,'submitted',NULL,'ready',NULL,'2026-12-01',6,10,'2026-10-30 10:00:00','2026-11-01','MQA/QV/2026/140',12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-10-28 10:00:00','2026-11-05 09:00:00'),(10,18,1,'ready',NULL,'ready',NULL,'2026-12-15',8,11,'2026-11-02 10:00:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-10-20 10:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `qv_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `readiness_confirmations`
--

DROP TABLE IF EXISTS `readiness_confirmations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `readiness_confirmations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offering_id` bigint unsigned NOT NULL,
  `role` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `outcome` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conditions` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `readiness_confirmations_offering_id_foreign` (`offering_id`),
  KEY `readiness_confirmations_user_id_foreign` (`user_id`),
  CONSTRAINT `readiness_confirmations_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `readiness_confirmations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `readiness_confirmations`
--

LOCK TABLES `readiness_confirmations` WRITE;
/*!40000 ALTER TABLE `readiness_confirmations` DISABLE KEYS */;
INSERT INTO `readiness_confirmations` VALUES (1,1,'lecturer',6,'ready',NULL,'2026-05-03 01:42:39'),(2,1,'ptj',10,'ready',NULL,'2026-05-04 01:42:39'),(3,2,'lecturer',6,'ready',NULL,'2026-09-13 01:42:39'),(4,2,'ptj',10,'ready',NULL,'2026-09-14 01:42:39'),(5,3,'lecturer',6,'ready',NULL,'2026-10-23 01:42:39'),(6,3,'ptj',10,'ready',NULL,'2026-10-24 01:42:39'),(7,7,'lecturer',8,'ready',NULL,'2026-10-18 01:42:39'),(8,8,'lecturer',6,'ready',NULL,'2026-10-18 01:42:39'),(9,9,'lecturer',6,'ready',NULL,'2026-10-18 01:42:39'),(10,10,'lecturer',6,'ready',NULL,'2026-10-18 01:42:39'),(11,11,'lecturer',7,'ready',NULL,'2026-10-18 01:42:39'),(12,4,'lecturer',6,'ready',NULL,'2026-10-26 10:00:00'),(13,4,'ptj',25,'ready',NULL,'2026-10-27 10:00:00'),(14,5,'lecturer',6,'ready',NULL,'2026-10-01 10:00:00'),(15,5,'ptj',10,'ready',NULL,'2026-10-02 10:00:00'),(16,7,'ptj',11,'ready',NULL,'2026-10-19 10:00:00'),(17,12,'lecturer',6,'ready',NULL,'2026-04-05 10:00:00'),(18,12,'ptj',10,'ready',NULL,'2026-04-06 10:00:00'),(19,13,'lecturer',7,'ready',NULL,'2026-07-29 10:00:00'),(20,13,'ptj',10,'ready_conditions','Ensure OWASP Zap container image is pre-warmed before Week 3.','2026-07-30 10:00:00'),(21,14,'lecturer',6,'ready',NULL,'2026-05-28 10:00:00'),(22,14,'ptj',10,'ready',NULL,'2026-05-29 10:00:00'),(23,15,'lecturer',24,'ready',NULL,'2026-07-08 10:00:00'),(24,15,'ptj',25,'ready',NULL,'2026-07-09 10:00:00'),(25,18,'ptj',10,'not_ready','Complete assessment integrity and credential readiness items first.','2026-11-02 10:00:00');
/*!40000 ALTER TABLE `readiness_confirmations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `readiness_items`
--

DROP TABLE IF EXISTS `readiness_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `readiness_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offering_id` bigint unsigned NOT NULL,
  `item` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_yet',
  `note` text COLLATE utf8mb4_unicode_ci,
  `accepted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `readiness_items_offering_id_item_unique` (`offering_id`,`item`),
  CONSTRAINT `readiness_items_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `readiness_items`
--

LOCK TABLES `readiness_items` WRITE;
/*!40000 ALTER TABLE `readiness_items` DISABLE KEYS */;
INSERT INTO `readiness_items` VALUES (1,1,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(2,1,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(3,1,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(4,1,'records','yes','Verified and configured in uLearn & MCMS.',1),(5,1,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(6,1,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(7,2,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(8,2,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(9,2,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(10,2,'records','yes','Verified and configured in uLearn & MCMS.',1),(11,2,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(12,2,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(13,3,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(14,3,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(15,3,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(16,3,'records','yes','Verified and configured in uLearn & MCMS.',1),(17,3,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(18,3,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(19,4,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(20,4,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(21,4,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(22,4,'records','yes','Verified and configured in uLearn & MCMS.',1),(23,4,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(24,4,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(25,5,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(26,5,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(27,5,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(28,5,'records','yes','Verified and configured in uLearn & MCMS.',1),(29,5,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(30,5,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(31,6,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(32,6,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(33,6,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(34,6,'records','yes','Verified and configured in uLearn & MCMS.',1),(35,6,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(36,6,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(37,7,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(38,7,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(39,7,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(40,7,'records','yes','Verified and configured in uLearn & MCMS.',1),(41,7,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(42,7,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(43,8,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(44,8,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(45,8,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(46,8,'records','yes','Verified and configured in uLearn & MCMS.',1),(47,8,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(48,8,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(49,9,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(50,9,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(51,9,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(52,9,'records','yes','Verified and configured in uLearn & MCMS.',1),(53,9,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(54,9,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(55,10,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(56,10,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(57,10,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(58,10,'records','yes','Verified and configured in uLearn & MCMS.',1),(59,10,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(60,10,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(61,11,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(62,11,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(63,11,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(64,11,'records','yes','Verified and configured in uLearn & MCMS.',1),(65,11,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(66,11,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(67,12,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(68,12,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(69,12,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(70,12,'records','yes','Verified and configured in uLearn & MCMS.',1),(71,12,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(72,12,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(73,13,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(74,13,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(75,13,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(76,13,'records','yes','Verified and configured in uLearn & MCMS.',1),(77,13,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(78,13,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(79,14,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(80,14,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(81,14,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(82,14,'records','yes','Verified and configured in uLearn & MCMS.',1),(83,14,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(84,14,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(85,15,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(86,15,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(87,15,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(88,15,'records','yes','Verified and configured in uLearn & MCMS.',1),(89,15,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(90,15,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(91,16,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(92,16,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(93,16,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(94,16,'records','yes','Verified and configured in uLearn & MCMS.',1),(95,16,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(96,16,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(97,17,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(98,17,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(99,17,'assessment_integrity','yes','Verified and configured in uLearn & MCMS.',1),(100,17,'records','yes','Verified and configured in uLearn & MCMS.',1),(101,17,'completion_credential','yes','Verified and configured in uLearn & MCMS.',1),(102,17,'change_notification','yes','Verified and configured in uLearn & MCMS.',1),(103,18,'transparency','yes','Verified and configured in uLearn & MCMS.',1),(104,18,'materials_platform','yes','Verified and configured in uLearn & MCMS.',1),(105,18,'assessment_integrity','not_yet','Pending final moderation.',0),(106,18,'records','yes','Verified and configured in uLearn & MCMS.',1),(107,18,'completion_credential','not_yet','Pending final moderation.',0),(108,18,'change_notification','yes','Verified and configured in uLearn & MCMS.',1);
/*!40000 ALTER TABLE `readiness_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `redemption_events`
--

DROP TABLE IF EXISTS `redemption_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `redemption_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `abc_deposit_id` bigint unsigned NOT NULL,
  `transfer_line_id` bigint unsigned DEFAULT NULL,
  `event` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `evidence_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `abc_updated_on` date DEFAULT NULL,
  `abc_update_ref` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `redemption_events_abc_deposit_id_foreign` (`abc_deposit_id`),
  KEY `redemption_events_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `redemption_events_abc_deposit_id_foreign` FOREIGN KEY (`abc_deposit_id`) REFERENCES `abc_deposits` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `redemption_events_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redemption_events`
--

LOCK TABLES `redemption_events` WRITE;
/*!40000 ALTER TABLE `redemption_events` DISABLE KEYS */;
INSERT INTO `redemption_events` VALUES (1,1,1,'redemption','TR-2026-0001','2026-10-25','ABC-UPDATE-78192',10,'2026-10-25 01:42:39'),(2,3,3,'redemption','TR-2026-0003','2026-09-12','ABC-UPDATE-78210',10,'2026-09-12 10:30:00'),(3,7,4,'redemption','TR-2026-0004','2026-10-20','ABC-UPDATE-78305',25,'2026-10-20 10:30:00');
/*!40000 ALTER TABLE `redemption_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_runs`
--

DROP TABLE IF EXISTS `report_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `format` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `row_count` int unsigned NOT NULL,
  `scope` json DEFAULT NULL,
  `ran_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `report_runs_user_id_foreign` (`user_id`),
  CONSTRAINT `report_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_runs`
--

LOCK TABLES `report_runs` WRITE;
/*!40000 ALTER TABLE `report_runs` DISABLE KEYS */;
INSERT INTO `report_runs` VALUES (1,'qv',12,'print',7,'{\"ptj\": \"ALL\", \"year\": 2025}','2026-10-04 01:42:39'),(2,'enrolments',10,'csv',45,'{\"faculty\": \"FTMK\"}','2026-10-28 01:42:39'),(3,'offerings',6,'screen',5,'[1]','2026-11-04 02:22:54'),(4,'offerings',6,'screen',5,'[1]','2026-11-04 04:11:45'),(5,'offerings',6,'csv',5,'[1]','2026-11-04 04:11:52'),(6,'offerings',6,'print',5,'[1]','2026-11-04 04:11:56'),(7,'enrolments',12,'screen',6,'null','2026-11-04 07:57:14'),(8,'enrolments',12,'screen',6,'null','2026-11-04 08:10:26'),(9,'enrolments',12,'screen',6,'null','2026-11-04 08:12:17'),(10,'enrolments',12,'screen',6,'null','2026-11-04 08:13:30'),(11,'credentials',12,'screen',12,'[1]','2026-11-04 09:00:00'),(12,'acr',12,'csv',9,'null','2026-11-04 09:10:00'),(13,'transfers',10,'screen',5,'[1]','2026-11-04 09:20:00'),(14,'redemption',12,'print',3,'null','2026-11-04 09:30:00'),(15,'cqi',10,'screen',6,'[1]','2026-11-04 09:40:00'),(16,'plans',10,'screen',4,'[1]','2026-11-04 09:50:00'),(17,'suspensions',12,'print',3,'null','2026-11-04 10:00:00');
/*!40000 ALTER TABLE `report_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `result_change_requests`
--

DROP TABLE IF EXISTS `result_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `result_change_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_result_id` bigint unsigned NOT NULL,
  `new_score` decimal(6,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `decided_by` bigint unsigned DEFAULT NULL,
  `decision_note` text COLLATE utf8mb4_unicode_ci,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `result_change_requests_assessment_result_id_foreign` (`assessment_result_id`),
  KEY `result_change_requests_requested_by_foreign` (`requested_by`),
  KEY `result_change_requests_decided_by_foreign` (`decided_by`),
  CONSTRAINT `result_change_requests_assessment_result_id_foreign` FOREIGN KEY (`assessment_result_id`) REFERENCES `assessment_results` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `result_change_requests_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `result_change_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `result_change_requests`
--

LOCK TABLES `result_change_requests` WRITE;
/*!40000 ALTER TABLE `result_change_requests` DISABLE KEYS */;
INSERT INTO `result_change_requests` VALUES (1,8,68.00,'Late submission penalty waived upon medical certificate verification.',6,'approved',10,'Medical leave accepted as valid extenuating circumstance.','2026-11-02 01:42:39','2026-11-01 01:42:39'),(2,7,79.00,'Arithmetic totaling correction on Q3 linked list diagram.',6,'pending',NULL,NULL,NULL,'2026-11-04 11:00:00'),(3,29,50.00,'Learner requested condonation pass for DevSecOps capstone.',7,'rejected',10,'Summative pass threshold of 50% is mandatory; learner may re-sit next cohort.','2026-10-25 09:00:00','2026-10-24 15:00:00');
/*!40000 ALTER TABLE `result_change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `retention_holds`
--

DROP TABLE IF EXISTS `retention_holds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `retention_holds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `placed_by` bigint unsigned NOT NULL,
  `placed_at` timestamp NOT NULL,
  `lifted_by` bigint unsigned DEFAULT NULL,
  `lifted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retention_holds_placed_by_foreign` (`placed_by`),
  KEY `retention_holds_lifted_by_foreign` (`lifted_by`),
  CONSTRAINT `retention_holds_lifted_by_foreign` FOREIGN KEY (`lifted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `retention_holds_placed_by_foreign` FOREIGN KEY (`placed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `retention_holds`
--

LOCK TABLES `retention_holds` WRITE;
/*!40000 ALTER TABLE `retention_holds` DISABLE KEYS */;
INSERT INTO `retention_holds` VALUES (1,'integration_calls','Hold placed on Cohort 2026/2 records pending outcome of integrity hearing case #1.',10,'2026-10-31 01:42:39',NULL,NULL),(2,'notifications','Legal hold on notification delivery logs during annual PDPA compliance audit.',12,'2026-09-01 09:00:00',12,'2026-10-01 17:00:00');
/*!40000 ALTER TABLE `retention_holds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_assignments`
--

DROP TABLE IF EXISTS `review_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_version_id` bigint unsigned NOT NULL,
  `reviewer_id` bigint unsigned NOT NULL,
  `kind` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_by` bigint unsigned NOT NULL,
  `due_on` date NOT NULL,
  `scope` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'invited',
  `coi_declared_at` timestamp NULL DEFAULT NULL,
  `has_conflict` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_assignments_mc_version_id_reviewer_id_unique` (`mc_version_id`,`reviewer_id`),
  KEY `review_assignments_reviewer_id_foreign` (`reviewer_id`),
  KEY `review_assignments_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `review_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `review_assignments_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `review_assignments_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_assignments`
--

LOCK TABLES `review_assignments` WRITE;
/*!40000 ALTER TABLE `review_assignments` DISABLE KEYS */;
INSERT INTO `review_assignments` VALUES (1,4,6,'reviewer',10,'2026-11-11','Academic rigor and SLT adequacy','in_progress','2026-11-04 08:13:31',0,'2026-10-31 01:42:39','2026-11-04 08:13:31'),(2,5,7,'reviewer',10,'2026-11-02','Curriculum alignment and assessment weights','submitted','2026-10-27 01:42:39',0,'2026-10-26 01:42:39',NULL),(3,6,15,'expert',10,'2026-10-25','Industry relevance and competency mapping','submitted','2026-10-15 01:42:39',0,'2026-10-13 01:42:39',NULL),(4,4,16,'expert',10,'2026-11-12','SOC industry toolset and incident triage realism','submitted','2026-11-01 10:00:00',0,'2026-10-31 09:00:00','2026-11-05 09:00:00'),(5,9,16,'expert',10,'2026-08-10','OWASP Top 10 and enterprise DevSecOps alignment','submitted','2026-08-01 10:00:00',0,'2026-07-28 09:00:00','2026-11-05 09:00:00'),(6,10,7,'reviewer',10,'2026-11-03','SLT allocation and optimization rubric review','submitted','2026-10-29 10:00:00',0,'2026-10-28 11:00:00','2026-11-05 09:00:00'),(7,12,6,'reviewer',10,'2026-10-28','Academic design and MQF level 6 mapping','submitted','2026-10-20 10:00:00',0,'2026-10-19 09:00:00','2026-11-05 09:00:00'),(8,12,26,'expert',10,'2026-10-28','Enterprise API gateway and zero-trust relevance','submitted','2026-10-20 14:00:00',0,'2026-10-19 09:30:00','2026-11-05 09:00:00'),(9,11,15,'expert',10,'2026-11-15','Cloud compliance and container security review','invited',NULL,0,'2026-11-03 09:00:00','2026-11-05 09:00:00'),(10,4,8,'reviewer',10,'2026-11-05','Cross-faculty technical review','declined','2026-11-01 08:30:00',1,'2026-10-31 09:00:00','2026-11-05 09:00:00'),(11,25,6,'reviewer',10,'2026-11-02','Currency of syllabus and market demand check','submitted','2026-10-30 09:00:00',0,'2026-10-29 14:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `review_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_comments`
--

DROP TABLE IF EXISTS `review_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_version_id` bigint unsigned NOT NULL,
  `review_assignment_id` bigint unsigned NOT NULL,
  `author_id` bigint unsigned NOT NULL,
  `item` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `blocking` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `response` text COLLATE utf8mb4_unicode_ci,
  `responded_by` bigint unsigned DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `resolve_reason` text COLLATE utf8mb4_unicode_ci,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `review_comments_mc_version_id_foreign` (`mc_version_id`),
  KEY `review_comments_review_assignment_id_foreign` (`review_assignment_id`),
  KEY `review_comments_author_id_foreign` (`author_id`),
  KEY `review_comments_responded_by_foreign` (`responded_by`),
  KEY `review_comments_resolved_by_foreign` (`resolved_by`),
  CONSTRAINT `review_comments_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `review_comments_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `review_comments_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `review_comments_responded_by_foreign` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `review_comments_review_assignment_id_foreign` FOREIGN KEY (`review_assignment_id`) REFERENCES `review_assignments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_comments`
--

LOCK TABLES `review_comments` WRITE;
/*!40000 ALTER TABLE `review_comments` DISABLE KEYS */;
INSERT INTO `review_comments` VALUES (1,5,2,7,'slt','Adequacy of SLT: lab exercises need an additional 6 hours to cover window functions properly.',1,'answered','Added 6 guided laboratory hours to Module 2.',6,'2026-11-03 01:42:39',NULL,NULL,NULL,'2026-11-02 01:42:39'),(2,5,2,7,'alignment','Outcome 3 (Transaction Management) has no corresponding assessment item.',1,'open',NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-02 01:42:39'),(3,5,2,7,'parent_mapping','Please cite the exact 2024 BITS 1125 syllabus version in the parent mapping note.',0,'resolved','Cited 2024 curriculum revision v2.1.',6,'2026-11-03 10:00:00','Verified against FTMK curriculum handbook.',10,'2026-11-03 16:00:00','2026-11-02 02:00:00'),(4,10,6,7,'assessment','Provide the detailed grading rubric for the Python PuLP optimization project before resubmission.',1,'answered','Uploaded 4-criteria rubric in section 4.',6,'2026-11-04 09:30:00',NULL,NULL,NULL,'2026-11-01 16:05:00'),(5,10,6,7,'slt','Clarify guided vs independent learning hours in Module 2.',1,'open',NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-01 16:10:00'),(6,12,8,26,'delivery','Recommend including Kong or Envoy gateway in the practical sandbox.',0,'resolved','Added Envoy proxy lab in Module 2.',6,'2026-10-27 11:00:00','Lab guide updated.',10,'2026-10-28 09:00:00','2026-10-26 14:15:00');
/*!40000 ALTER TABLE `review_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `review_assignment_id` bigint unsigned NOT NULL,
  `ratings` json NOT NULL,
  `recommendation` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `submitted_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_review_assignment_id_unique` (`review_assignment_id`),
  CONSTRAINT `reviews_review_assignment_id_foreign` FOREIGN KEY (`review_assignment_id`) REFERENCES `review_assignments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,2,'{\"slt\": {\"rating\": \"needs_amendment\", \"comment\": \"Practical SQL lab hours appear underestimated by 6 hours.\"}, \"delivery\": {\"rating\": \"satisfactory\", \"comment\": \"Online lab environment is suitable.\"}, \"outcomes\": {\"rating\": \"satisfactory\", \"comment\": \"Outcomes are clearly stated.\"}, \"alignment\": {\"rating\": \"needs_amendment\", \"comment\": \"LO3 (Transaction Management) requires an explicit practical assessment component.\"}, \"structure\": {\"rating\": \"satisfactory\", \"comment\": \"Two-module structure is logical.\"}, \"compliance\": {\"rating\": \"satisfactory\", \"comment\": \"Meets COPPA-MC rules.\"}, \"level_scope\": {\"rating\": \"satisfactory\", \"comment\": \"Appropriate for MQF Level 6.\"}, \"parent_mapping\": {\"rating\": \"satisfactory\", \"comment\": \"Aligned with BITS 1125.\"}, \"credential_info\": {\"rating\": \"satisfactory\", \"comment\": \"Credential claims are accurate.\"}}','amend','Sound overall technical content; practical lab hours need adjustment.','2026-11-02 01:42:39'),(2,3,'{\"slt\": {\"rating\": \"satisfactory\", \"comment\": \"80 hours SLT matches 2.0 credit calculation (1 credit = 40 SLT hours).\"}, \"delivery\": {\"rating\": \"satisfactory\", \"comment\": \"Online asynchronous videos paired with live lab clinics are appropriate.\"}, \"outcomes\": {\"rating\": \"satisfactory\", \"comment\": \"Clear, measurable action verbs aligned with Bloom taxonomy.\"}, \"alignment\": {\"rating\": \"satisfactory\", \"comment\": \"Constructive alignment between LOs, modules, and summative tasks is complete.\"}, \"structure\": {\"rating\": \"satisfactory\", \"comment\": \"Modular breakdown supports progressive skill acquisition.\"}, \"compliance\": {\"rating\": \"satisfactory\", \"comment\": \"Complies with MQA COPPA-MC and UTeM CODL governance guidelines.\"}, \"level_scope\": {\"rating\": \"satisfactory\", \"comment\": \"Depth of technical exercises matches the proposed MQF level.\"}, \"parent_mapping\": {\"rating\": \"satisfactory\", \"comment\": \"Unbundled mapping to parent course CLOs is well documented.\"}, \"credential_info\": {\"rating\": \"satisfactory\", \"comment\": \"Public credential metadata and badge criteria are transparent.\"}}','support','Fully endorsed by industry advisory panel for workforce upskilling.','2026-10-23 01:42:39'),(3,4,'{\"slt\": {\"rating\": \"satisfactory\", \"comment\": \"80 hours SLT matches 2.0 credit calculation (1 credit = 40 SLT hours).\"}, \"delivery\": {\"rating\": \"satisfactory\", \"comment\": \"Online asynchronous videos paired with live lab clinics are appropriate.\"}, \"outcomes\": {\"rating\": \"satisfactory\", \"comment\": \"Clear, measurable action verbs aligned with Bloom taxonomy.\"}, \"alignment\": {\"rating\": \"satisfactory\", \"comment\": \"Constructive alignment between LOs, modules, and summative tasks is complete.\"}, \"structure\": {\"rating\": \"satisfactory\", \"comment\": \"Modular breakdown supports progressive skill acquisition.\"}, \"compliance\": {\"rating\": \"satisfactory\", \"comment\": \"Complies with MQA COPPA-MC and UTeM CODL governance guidelines.\"}, \"level_scope\": {\"rating\": \"satisfactory\", \"comment\": \"Depth of technical exercises matches the proposed MQF level.\"}, \"parent_mapping\": {\"rating\": \"satisfactory\", \"comment\": \"Unbundled mapping to parent course CLOs is well documented.\"}, \"credential_info\": {\"rating\": \"satisfactory\", \"comment\": \"Public credential metadata and badge criteria are transparent.\"}}','support','SIEM playbook exercises accurately reflect Tier-1 SOC analyst workflows in Malaysian financial institutions.','2026-11-03 15:00:00'),(4,5,'{\"slt\": {\"rating\": \"satisfactory\", \"comment\": \"80 hours SLT matches 2.0 credit calculation (1 credit = 40 SLT hours).\"}, \"delivery\": {\"rating\": \"satisfactory\", \"comment\": \"Online asynchronous videos paired with live lab clinics are appropriate.\"}, \"outcomes\": {\"rating\": \"satisfactory\", \"comment\": \"Clear, measurable action verbs aligned with Bloom taxonomy.\"}, \"alignment\": {\"rating\": \"satisfactory\", \"comment\": \"Constructive alignment between LOs, modules, and summative tasks is complete.\"}, \"structure\": {\"rating\": \"satisfactory\", \"comment\": \"Modular breakdown supports progressive skill acquisition.\"}, \"compliance\": {\"rating\": \"satisfactory\", \"comment\": \"Complies with MQA COPPA-MC and UTeM CODL governance guidelines.\"}, \"level_scope\": {\"rating\": \"satisfactory\", \"comment\": \"Depth of technical exercises matches the proposed MQF level.\"}, \"parent_mapping\": {\"rating\": \"satisfactory\", \"comment\": \"Unbundled mapping to parent course CLOs is well documented.\"}, \"credential_info\": {\"rating\": \"satisfactory\", \"comment\": \"Public credential metadata and badge criteria are transparent.\"}}','support','Highly relevant OWASP and DevSecOps curriculum; strongly endorsed for industry upskilling.','2026-08-05 11:00:00'),(5,6,'{\"slt\": {\"rating\": \"needs_amendment\", \"comment\": \"Practical SQL lab hours appear underestimated by 6 hours.\"}, \"delivery\": {\"rating\": \"satisfactory\", \"comment\": \"Online lab environment is suitable.\"}, \"outcomes\": {\"rating\": \"satisfactory\", \"comment\": \"Outcomes are clearly stated.\"}, \"alignment\": {\"rating\": \"needs_amendment\", \"comment\": \"LO3 (Transaction Management) requires an explicit practical assessment component.\"}, \"structure\": {\"rating\": \"satisfactory\", \"comment\": \"Two-module structure is logical.\"}, \"compliance\": {\"rating\": \"satisfactory\", \"comment\": \"Meets COPPA-MC rules.\"}, \"level_scope\": {\"rating\": \"satisfactory\", \"comment\": \"Appropriate for MQF Level 6.\"}, \"parent_mapping\": {\"rating\": \"satisfactory\", \"comment\": \"Aligned with BITS 1125.\"}, \"credential_info\": {\"rating\": \"satisfactory\", \"comment\": \"Credential claims are accurate.\"}}','amend','Returned to proposer to complete readiness item on assessment rubric and refine SLT breakdown.','2026-11-01 16:00:00'),(6,7,'{\"slt\": {\"rating\": \"satisfactory\", \"comment\": \"80 hours SLT matches 2.0 credit calculation (1 credit = 40 SLT hours).\"}, \"delivery\": {\"rating\": \"satisfactory\", \"comment\": \"Online asynchronous videos paired with live lab clinics are appropriate.\"}, \"outcomes\": {\"rating\": \"satisfactory\", \"comment\": \"Clear, measurable action verbs aligned with Bloom taxonomy.\"}, \"alignment\": {\"rating\": \"satisfactory\", \"comment\": \"Constructive alignment between LOs, modules, and summative tasks is complete.\"}, \"structure\": {\"rating\": \"satisfactory\", \"comment\": \"Modular breakdown supports progressive skill acquisition.\"}, \"compliance\": {\"rating\": \"satisfactory\", \"comment\": \"Complies with MQA COPPA-MC and UTeM CODL governance guidelines.\"}, \"level_scope\": {\"rating\": \"satisfactory\", \"comment\": \"Depth of technical exercises matches the proposed MQF level.\"}, \"parent_mapping\": {\"rating\": \"satisfactory\", \"comment\": \"Unbundled mapping to parent course CLOs is well documented.\"}, \"credential_info\": {\"rating\": \"satisfactory\", \"comment\": \"Public credential metadata and badge criteria are transparent.\"}}','support','Academic structure and assessment rigour meet all FTMK standards.','2026-10-25 10:00:00'),(7,8,'{\"slt\": {\"rating\": \"satisfactory\", \"comment\": \"80 hours SLT matches 2.0 credit calculation (1 credit = 40 SLT hours).\"}, \"delivery\": {\"rating\": \"satisfactory\", \"comment\": \"Online asynchronous videos paired with live lab clinics are appropriate.\"}, \"outcomes\": {\"rating\": \"satisfactory\", \"comment\": \"Clear, measurable action verbs aligned with Bloom taxonomy.\"}, \"alignment\": {\"rating\": \"satisfactory\", \"comment\": \"Constructive alignment between LOs, modules, and summative tasks is complete.\"}, \"structure\": {\"rating\": \"satisfactory\", \"comment\": \"Modular breakdown supports progressive skill acquisition.\"}, \"compliance\": {\"rating\": \"satisfactory\", \"comment\": \"Complies with MQA COPPA-MC and UTeM CODL governance guidelines.\"}, \"level_scope\": {\"rating\": \"satisfactory\", \"comment\": \"Depth of technical exercises matches the proposed MQF level.\"}, \"parent_mapping\": {\"rating\": \"satisfactory\", \"comment\": \"Unbundled mapping to parent course CLOs is well documented.\"}, \"credential_info\": {\"rating\": \"satisfactory\", \"comment\": \"Public credential metadata and badge criteria are transparent.\"}}','support','Excellent coverage of OAuth2/OIDC and zero-trust microservice patterns.','2026-10-26 14:00:00'),(8,11,'{\"slt\": {\"rating\": \"needs_amendment\", \"comment\": \"Practical SQL lab hours appear underestimated by 6 hours.\"}, \"delivery\": {\"rating\": \"satisfactory\", \"comment\": \"Online lab environment is suitable.\"}, \"outcomes\": {\"rating\": \"satisfactory\", \"comment\": \"Outcomes are clearly stated.\"}, \"alignment\": {\"rating\": \"needs_amendment\", \"comment\": \"LO3 (Transaction Management) requires an explicit practical assessment component.\"}, \"structure\": {\"rating\": \"satisfactory\", \"comment\": \"Two-module structure is logical.\"}, \"compliance\": {\"rating\": \"satisfactory\", \"comment\": \"Meets COPPA-MC rules.\"}, \"level_scope\": {\"rating\": \"satisfactory\", \"comment\": \"Appropriate for MQF Level 6.\"}, \"parent_mapping\": {\"rating\": \"satisfactory\", \"comment\": \"Aligned with BITS 1125.\"}, \"credential_info\": {\"rating\": \"satisfactory\", \"comment\": \"Credential claims are accurate.\"}}','not_support','Syllabus lacks modern mainframe-to-cloud migration context and overlaps deprecated material.','2026-10-31 17:00:00');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_assignments`
--

DROP TABLE IF EXISTS `role_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptj_id` bigint unsigned DEFAULT NULL,
  `scope_ref` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `assigned_by` bigint unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `role_assignments_ptj_id_foreign` (`ptj_id`),
  KEY `role_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `role_assignments_user_id_role_valid_to_index` (`user_id`,`role`,`valid_to`),
  CONSTRAINT `role_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `role_assignments_ptj_id_foreign` FOREIGN KEY (`ptj_id`) REFERENCES `ptjs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `role_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_assignments`
--

LOCK TABLES `role_assignments` WRITE;
/*!40000 ALTER TABLE `role_assignments` DISABLE KEYS */;
INSERT INTO `role_assignments` VALUES (1,1,'student',NULL,NULL,NULL,NULL,NULL,NULL,'Enrolled learner','2026-11-04 01:42:39',NULL),(2,2,'student',NULL,NULL,NULL,NULL,NULL,NULL,'Enrolled learner','2026-11-04 01:42:39',NULL),(3,3,'student',NULL,NULL,NULL,NULL,NULL,NULL,'Enrolled learner','2026-11-04 01:42:39',NULL),(4,4,'student',NULL,NULL,NULL,NULL,NULL,NULL,'Enrolled learner','2026-11-04 01:42:39',NULL),(5,5,'student',NULL,NULL,NULL,NULL,NULL,NULL,'Alumni account','2026-11-04 01:42:39',NULL),(6,6,'lecturer','ptj',1,NULL,NULL,NULL,NULL,'FTMK Academic Staff','2026-11-04 01:42:39',NULL),(7,7,'lecturer','ptj',1,NULL,NULL,NULL,NULL,'FTMK Academic Staff & Reviewer','2026-11-04 01:42:39',NULL),(8,8,'lecturer','ptj',2,NULL,NULL,NULL,NULL,'FTKE Academic Staff','2026-11-04 01:42:39',NULL),(9,9,'lecturer','ptj',1,NULL,NULL,NULL,NULL,'Past Staff (Resigned)','2026-11-04 01:42:39',NULL),(10,10,'deputy_dean','ptj',1,NULL,NULL,NULL,NULL,'Deputy Dean (Academic) FTMK','2026-11-04 01:42:39',NULL),(11,11,'deputy_dean','ptj',2,NULL,NULL,NULL,NULL,'Deputy Dean (Academic) FTKE','2026-11-04 01:42:39',NULL),(12,12,'codl_admin','university',NULL,NULL,NULL,NULL,NULL,'Chief CODL Administrator','2026-11-04 01:42:39',NULL),(13,13,'codl_admin','university',NULL,NULL,NULL,NULL,NULL,'CODL Operations & Registry Officer','2026-11-04 01:42:39',NULL),(14,14,'codl_admin','university',NULL,NULL,NULL,NULL,NULL,'Deactivated Admin Officer','2026-11-04 01:42:39',NULL),(15,15,'industry_expert',NULL,NULL,NULL,NULL,NULL,NULL,'Industry Advisory Panel - Cloud Architecture','2026-11-04 01:42:39',NULL),(16,16,'industry_expert',NULL,NULL,NULL,NULL,NULL,NULL,'Industry Advisory Panel - Cybersecurity','2026-11-04 01:42:39',NULL),(17,17,'industry_expert',NULL,NULL,NULL,NULL,NULL,NULL,'Term Ended Advisor','2026-11-04 01:42:39',NULL),(18,18,'student',NULL,NULL,NULL,'2026-04-10',NULL,12,'Undergraduate student enrolment','2026-04-10 09:00:00',NULL),(19,19,'student',NULL,NULL,NULL,'2026-05-12',NULL,12,'Working professional lifelong learner','2026-05-12 10:30:00',NULL),(20,20,'student',NULL,NULL,NULL,'2026-05-20',NULL,12,'Public micro-credential participant','2026-05-20 11:15:00',NULL),(21,21,'student',NULL,NULL,NULL,'2026-06-15',NULL,12,'UTeM Alumni upskilling account','2026-06-15 08:45:00',NULL),(22,22,'student',NULL,NULL,NULL,'2026-11-04',NULL,12,'Newly registered student (empty state)','2026-11-04 09:00:00',NULL),(23,23,'lecturer','ptj',3,NULL,'2024-06-01',NULL,12,'FTKM Senior Lecturer & Coordinator','2024-06-01 08:00:00',NULL),(24,23,'lecturer','ptj',4,NULL,'2025-01-01',NULL,12,'Cross-faculty appointment for FPTT & FTKIP engineering management','2025-01-01 08:00:00',NULL),(25,23,'lecturer','ptj',6,NULL,'2025-01-01',NULL,12,'FTKIP Industrial Engineering Coordinator','2025-01-01 08:00:00',NULL),(26,24,'lecturer','ptj',7,NULL,'2024-06-01',NULL,12,'FAIX AI & Cybersecurity Academic Lead','2024-06-01 08:00:00',NULL),(27,24,'lecturer','ptj',5,NULL,'2024-06-01',NULL,12,'FTKEK Embedded Systems Joint Coordinator','2024-06-01 08:00:00',NULL),(28,25,'deputy_dean','university',NULL,NULL,'2024-01-01',NULL,12,'University-wide Senate Micro-Credential Governance Chair','2024-01-01 08:00:00',NULL),(29,26,'industry_expert',NULL,NULL,NULL,'2025-03-01','2028-02-28',12,'Industry Advisory Panel - Smart Manufacturing & IoT','2025-03-01 09:00:00',NULL),(30,6,'lecturer','ptj',4,NULL,'2025-01-01',NULL,12,'Cross-faculty lecturer for FPTT Executive MCs','2025-01-01 09:00:00',NULL),(31,7,'deputy_dean','ptj',1,NULL,'2024-01-01','2024-12-31',12,'Acting Deputy Dean (Academic) FTMK during sabbatical (expired)','2024-01-01 08:00:00','2025-01-01 08:00:00');
/*!40000 ALTER TABLE `role_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('2eA02OwhRajT6f3WkYxlaz8A46CQAyqDeCbm6fDL',8,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiJ6UlVFRFFtQ1UwQ1JLM0hIbm03NmJSN0kzV2hVdmpNM3dmNFRXbTdFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC90YXNrcyIsInJvdXRlIjoidGFza3MifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6OCwicGFzc3dvcmRfaGFzaF93ZWIiOiJkZDNhNmI0NWFmM2IxMTk5NWFjNGRjZjUwZDY1ZTVmMTVkMzUxMGJhMDZlYTY5OGRlYzk1ZWNlNzYyNDI2MDFlIiwiYWN0aXZlX3JvbGUiOiJsZWN0dXJlciIsImxhc3RfYWN0aXZpdHkiOjE3OTM5NDg5Nzl9',1793948979),('6owlDjYnz7y4Wp2A2vl2KuROsv61ZgFxteFG9gju',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJDYlUyTk9hREp5d3E2NlBGZHRaS2NvSUplWHVVNnFBRVRtaDBxVzlWIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgxMTBcL3Rhc2tzIiwicm91dGUiOiJ0YXNrcyJ9LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6NiwicGFzc3dvcmRfaGFzaF93ZWIiOiJkZDNhNmI0NWFmM2IxMTk5NWFjNGRjZjUwZDY1ZTVmMTVkMzUxMGJhMDZlYTY5OGRlYzk1ZWNlNzYyNDI2MDFlIiwiYWN0aXZlX3JvbGUiOiJsZWN0dXJlciIsImxhc3RfYWN0aXZpdHkiOjE3OTM5NTAxODV9',1793950185),('9fLuUJNkW7q8mXhCR1sP4bksJXmhvBEyCWcsyRAN',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJCamRqT00zSG95ZFI5WnZOMDA5VlRkZFBuY2ZXVXFhZmNnU2hrVGd1IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgxMTBcL215LWNyZWRlbnRpYWxzXC8xMSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODExMFwvc2lnbmluIiwicm91dGUiOiJzaWduaW4ifX0=',1793948102),('cCwCIHpIKK2ZGDGlkiaqF68qmVaDg6BCCzrZABem',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiI0ZFhYeThoZlRKUzJJODJING1IamNqUFhKcXViSElETW1saEZ2M1gwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC9zaWduaW4iLCJyb3V0ZSI6InNpZ25pbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1793949136),('cjNhed5T9b0UhpoBLDfVN6a9m9FrV5AIxNBCoiGL',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiJIdmN2N0pXS3lMRXlSeWxIeXBiSlhNejhpUFJuTDZNcU1UeGtoWEhHIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC9zaWduaW4iLCJyb3V0ZSI6InNpZ25pbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1793950051),('cQ2VyI3IomwcEI6pne1oST8MCeBZmZGkwQS322eG',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiJldzVmRHRObXBWQnJGN09xdVo5Sks2d1E3T2txcTE3bldVRll6d3pDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC9zaWduaW4iLCJyb3V0ZSI6InNpZ25pbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1793949160),('cvOu5szWeqVaLnGex4NMVn57JFyYuGudISKb2b7m',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiJyejBCb0NUeUN3RUdkRHpiR2tkaFhlN0VEWGIwa2dWTEZXT0dPcnJFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC9wcm9wb3NhbHNcLzFcL3ByaW50Iiwicm91dGUiOiJwcm9wb3NhbHMucHJpbnQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6NiwicGFzc3dvcmRfaGFzaF93ZWIiOiJkZDNhNmI0NWFmM2IxMTk5NWFjNGRjZjUwZDY1ZTVmMTVkMzUxMGJhMDZlYTY5OGRlYzk1ZWNlNzYyNDI2MDFlIiwiYWN0aXZlX3JvbGUiOiJsZWN0dXJlciIsImxhc3RfYWN0aXZpdHkiOjE3OTM5NDkxNjJ9',1793949162),('GBz05Ksgfm1nd6DkE7xdUWvnEksqMDB40GOVkboo',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI3TGp3NE5Rd2swSTFIQVRYZUNibzczTHozUnBnek1lWjRibUhMNHl2IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgxMTBcL3NpZ25pbiIsInJvdXRlIjoic2lnbmluIn19',1793950175),('IbCXk3xgbq9uavUYK7f1rhPNjImlLyh515sSQ6qj',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiI0VDVPdjF0S0RWM2tHVmNyRzlNS3VIT3BFU0kxQklvek9wYWxnZWN2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC9zaWduaW4iLCJyb3V0ZSI6InNpZ25pbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1793948978),('lLJPumIVKqD1AsnG8AC23eJMtEFt7mEiD6X1sN7E',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiJ2UUNQZUkzWkliTmRjSVFvZzJoQWZnQVFyYlZLZ0lqVmozZ1dydTFhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC9zaWduaW4iLCJyb3V0ZSI6InNpZ25pbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1793949051),('lVNNt9TlNaMqZxib8azhHQ9aVmhKN9HcRFgZjcUO',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiJIMWY3WGlJYmpxMWwxc3k0M21BZzhQVHNpSlVOeGlYQ0w1VzhWQ1lhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC9wdWJsaWMiLCJyb3V0ZSI6InB1YmxpYy5ob21lIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1793950052),('moNQTk7jFnZ4sBjRHGWmxtK5owiyD7dxV5aPfsuP',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiJQbG13TUs3dWhaVE5TNEFHTjNOTGJVVVowNnFLa3JEcGhZalVCcWdXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC9wdWJsaWMiLCJyb3V0ZSI6InB1YmxpYy5ob21lIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1793950129),('nIjAJceodLzyj9wtHSqJdzH7nZWQqC4Nc7Dm2OkW',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiJXODduN0RYQndCdFQ5MzdMeVZPd0ZDakNNd0tBVmh6R0VzYlBkdHFQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC9zaWduaW4iLCJyb3V0ZSI6InNpZ25pbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1793950128),('T8JJdoJ77MYCoJaBRJ0fhgT9hqNBLl7rEgMcHrCG',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiJOd2V1RENsM3VtbkVDcm1rUThBTFF0TVhGWTFYUXNwMGh6a2J3c2VQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC90YXNrcyIsInJvdXRlIjoidGFza3MifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6NiwicGFzc3dvcmRfaGFzaF93ZWIiOiJkZDNhNmI0NWFmM2IxMTk5NWFjNGRjZjUwZDY1ZTVmMTVkMzUxMGJhMDZlYTY5OGRlYzk1ZWNlNzYyNDI2MDFlIiwiYWN0aXZlX3JvbGUiOiJsZWN0dXJlciIsImxhc3RfYWN0aXZpdHkiOjE3OTM5NDkxMzd9',1793949137),('ttZ4Ug2Z19EGUz9CNqI8RvpvalEI3FUuK12zHYXB',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0','eyJfdG9rZW4iOiI3VDJFV1hnUkg1WEt6WUpOb2dSaXh1czJRc2pnTWVxQ0NYMG1xY0s1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MTEwXC90YXNrcyIsInJvdXRlIjoidGFza3MifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6NiwicGFzc3dvcmRfaGFzaF93ZWIiOiJkZDNhNmI0NWFmM2IxMTk5NWFjNGRjZjUwZDY1ZTVmMTVkMzUxMGJhMDZlYTY5OGRlYzk1ZWNlNzYyNDI2MDFlIiwiYWN0aXZlX3JvbGUiOiJsZWN0dXJlciIsImxhc3RfYWN0aXZpdHkiOjE3OTM5NDkxMzd9',1793949137);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setting_versions`
--

DROP TABLE IF EXISTS `setting_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `setting_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_id` bigint unsigned NOT NULL,
  `value` json DEFAULT NULL,
  `effective_from` timestamp NOT NULL,
  `set_by` bigint unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `supersedes_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `setting_versions_set_by_foreign` (`set_by`),
  KEY `setting_versions_supersedes_id_foreign` (`supersedes_id`),
  KEY `setting_versions_setting_id_effective_from_index` (`setting_id`,`effective_from`),
  CONSTRAINT `setting_versions_set_by_foreign` FOREIGN KEY (`set_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `setting_versions_setting_id_foreign` FOREIGN KEY (`setting_id`) REFERENCES `settings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `setting_versions_supersedes_id_foreign` FOREIGN KEY (`supersedes_id`) REFERENCES `setting_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setting_versions`
--

LOCK TABLES `setting_versions` WRITE;
/*!40000 ALTER TABLE `setting_versions` DISABLE KEYS */;
INSERT INTO `setting_versions` VALUES (1,1,'40','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(2,2,'40','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(3,3,'40','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(4,4,'40','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(5,5,'3','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(6,6,'1','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(7,7,'1','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(8,8,'10','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(9,9,'30','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(10,10,'1','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(11,11,'[180, 90, 30]','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(12,12,'[365, 90]','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(13,13,'14','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(14,14,'null','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(15,15,'\"deputy_dean\"','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(16,16,'\"deputy_dean\"','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(17,17,'\"deputy_dean\"','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(18,18,'\"deputy_dean\"','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(19,19,'80','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(20,20,'70','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(21,21,'70','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(22,22,'95','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(23,23,'24','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(24,24,'30','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(25,25,'\"deputy_dean\"','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(26,26,'2','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(27,27,'2','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(28,28,'2','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(29,29,'0','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(30,30,'\"deputy_dean\"','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(31,31,'20','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(32,32,'48','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(33,33,'30','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(34,34,'[\"Name\", \"Credential\", \"Issuer\", \"Date\", \"Status\"]','2024-11-05 04:50:46',NULL,'Initial value from the Guide and the Glossary',NULL),(35,11,'[180, 90, 30]','2025-06-01 00:00:00',12,'Added 180-day early warning threshold per CODL Quality Committee.',11),(36,11,'[180, 90, 60, 30]','2026-01-01 00:00:00',12,'Included 60-day intermediate reminder for SAMC QV renewal.',35);
/*!40000 ALTER TABLE `setting_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_ms` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'slt.min_hours_samc','int','hours','Minimum SLT for a SAMC',NULL,'G 6.3.2, BR-01'),(2,'slt.min_hours_umc_whole','int','hours','Minimum SLT for a whole UMC (or 1 credit)',NULL,'G 6.3.1, BR-01'),(3,'slt.min_hours_module_credit_model','int','hours','Minimum SLT per module under the credit-based unbundling model',NULL,'G 6.3.1.1, BR-10'),(4,'credit.slt_hours_per_credit','int','hours','Notional learning hours per credit',NULL,'D-27'),(5,'qv.max_validity_years','int','years','Maximum continuous validity of Quality Verification',NULL,'G 11.2.2, BR-02'),(6,'qv.min_completed_cohorts','int','cohorts','Completed cohorts needed before QV submission',NULL,'G 11.2.1, BR-03'),(7,'qv.provider_registered','int','1 = yes','UTeM is registered and authorised to offer the programme (1 = yes, 0 = no)',NULL,'FR-164'),(8,'credit.abc_validity_years','int','years','Validity of credit in ABC from deposit date',NULL,'G 12.8, BR-04'),(9,'stackable.min_home_credit_percent','int','percent','Minimum share of graduating credit earned at UTeM',NULL,'G 14.5.1, BR-05'),(10,'stackable.residency_semesters','int','semesters','Residency for conventional programmes',NULL,'G 14.5.3, BR-06'),(11,'alerts.qv_expiry_days','int_list','days','Days before QV expiry at which alerts are sent',NULL,'FR-173'),(12,'alerts.credit_expiry_days','int_list','days','Days before credit expiry at which the participant is alerted',NULL,'FR-194'),(13,'alerts.decision_overdue_days','int','days','Days after which a pending review or decision is reminded',NULL,'FR-71'),(14,'umc.completion_window_months','int_or_empty','months','Default period to complete all modules of a modular UMC; an offering may set its own period',NULL,'G 7.4, OI-13'),(15,'approval.route.endorsement','role',NULL,'Role that endorses a proposal and verifies its category',NULL,'FR-16, D-11'),(16,'approval.route.transfer','role',NULL,'Role that decides a credit transfer for the target programme',NULL,'FR-ctauthority, D-11'),(17,'approval.route.stackable','role',NULL,'Role that approves and publishes a stackable pathway mapping',NULL,'FR-spapprove, D-11'),(18,'approval.route.suspension','role',NULL,'Role that decides the suspension or termination of an offering',NULL,'FR-prsuspend, D-11'),(19,'quality.target_participation_percent','int','percent','Target active participation rate of a cohort',NULL,'App C2'),(20,'quality.target_completion_percent','int','percent','Target completion rate of a cohort',NULL,'App C2'),(21,'quality.target_pass_percent','int','percent','Target pass rate of a cohort',NULL,'App C2'),(22,'quality.target_issuance_percent','int','percent','Target credential issuance rate among those who completed',NULL,'App C2'),(23,'review.interval_months','int','months','Months between periodic reviews of a micro-credential',NULL,'FR-prschedule, G 17.9'),(24,'review.remind_days','int','days','Days before a periodic review at which the lecturer and PTj are reminded',NULL,'FR-prschedule'),(25,'approval.route.disposal','role',NULL,'Role that approves the disposal of records after their retention period',NULL,'FR-retention, D-11'),(26,'retention.notifications_years','int','years','Retention of the notification log',NULL,'FR-retention, OI-07'),(27,'retention.report_runs_years','int','years','Retention of the report run log',NULL,'FR-retention, OI-07'),(28,'retention.integration_years','int','years','Retention of the integration call log',NULL,'FR-retention, OI-07'),(29,'demo.clock_offset_days','int','days','Days the demo clock is ahead of the real date (test environments only)',NULL,'PRD 7.2'),(30,'approval.route.senate','role',NULL,'Role that records the Senate decision',NULL,'FR-16, D-11'),(31,'enrolment.withdrawal_days_before_end','int','days','A participant can withdraw until this many days before the cohort ends (assumption UA-05, OI-12)',NULL,'FR-105, OI-12'),(32,'enrolment.payment_window_hours','int','hours','A registration not paid within this time is cancelled and the place released',NULL,'FR-101, JB-20'),(33,'auth.idle_timeout_minutes','int','minutes','Idle session timeout',NULL,'FR-06'),(34,'verification.public_fields','text_list',NULL,'Fields shown on the public verification page',NULL,'FR-154');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signing_keys`
--

DROP TABLE IF EXISTS `signing_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `signing_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kid` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_key` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret_enc` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `signing_keys_kid_unique` (`kid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signing_keys`
--

LOCK TABLES `signing_keys` WRITE;
/*!40000 ALTER TABLE `signing_keys` DISABLE KEYS */;
INSERT INTO `signing_keys` VALUES (1,'did:utem:codl:vc-2026-01','0tcOFzNg9wh6OkvBraHzO2J+3ND5GuyJIFOuw6NBz34=','eyJpdiI6IkhhTjBMZ3lRREZZSUcwdVRPSnhKMFE9PSIsInZhbHVlIjoiNTl2clVmK0JURExHQXBBcTVtcEg1YUpNY2dBNWx4SmF1OWxZUGQvaU0zTlhFV09POS9nMThVaWdUMjhhdzhXTk5OUnpRTGFnTktrT2JkSWxkQjBheG9nWFduUWpreW9MMlNjRU9rNENKYWQvUEZCeTY5Q0RpSytKSjdDRkg0WEYiLCJtYWMiOiJjNjAzOGY3ZDRhMGRkNGViY2I1MzlmYzc4MjA2MmNiYTI2N2VjNzBhNmZlNjY4OGRkZWMxNzRjZDFmOTM0NjUzIiwidGFnIjoiIn0=','active','2025-11-04 01:42:39'),(2,'did:utem:codl:vc-2025-01','Kt27I6qPQnrN8wBeeIYDPfmjTjS1zTXDPIuH7ABFyNk=','eyJpdiI6ImNQaEl4ejNkaEhvOGxnakRtTFdQMFE9PSIsInZhbHVlIjoiVytmSGh6WnVmZ2cwZ2pNa29OODdLMU5oWjVnZWx0R3NJK21IYVBZMjNJVHVMSW1RRTJYaU01TjNhUlBOYWo0ZG9ydlJScjJjUUdXRzZkQ1Q4QkJ2dDJIeTI5VUpnUlBuSWJpeHlOb0Y3UmtONGQxRjIxbnFTZ21jQzAyYjlCQ2UiLCJtYWMiOiIxY2NlMTVmMzljZDZlN2RmZmJhMTNhOGJhMTFkMGIyZmVkMmFkNmI0ZmEwOWRhYzhjNWIwNzk3MTI5MjA1NzQ0IiwidGFnIjoiIn0=','retired','2024-11-04 01:42:39'),(3,'did:utem:codl:vc-2024-01','JmAUY8do1vTMGYF1I2xK3UuMetsXPu3M76FP64w6VLI=','eyJpdiI6IkEwcHBtTUhWZi9vQXNRVUxWT1JydUE9PSIsInZhbHVlIjoiVmZnZWZTQ3h5TjZ6TU0zTSt6bGQ5dmV1bERhd3l2OFNzbno5NkdHZUdPTEt5TE1DRy9KS25DWXNzSWpYMWw5V3NaWjYxRUZVSnRWK3c5bUIvL09PM3N3Ym5qTG9Vbk1Vb2xiRTlCcGhoT0JySnZZSlcrMDdndklaUlg4V2xBSHciLCJtYWMiOiJhYjg5OWM5Yjk2MWZlZDMxMWM1MTcyZTZmNDZiNzlmNmU3OThlOTI0YTQ0Mzc5ZGIxNzk5OWU3Zjg1ZWQyNWJiIiwidGFnIjoiIn0=','retired','2023-11-04 01:42:39');
/*!40000 ALTER TABLE `signing_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stackable_plans`
--

DROP TABLE IF EXISTS `stackable_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stackable_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `pathway_id` bigint unsigned NOT NULL,
  `programme_id` bigint unsigned NOT NULL,
  `item_ids` json NOT NULL,
  `other_credits` decimal(5,1) NOT NULL DEFAULT '0.0',
  `transfer_credits` decimal(5,1) NOT NULL,
  `utem_percent` decimal(5,1) NOT NULL,
  `residency_semesters` smallint unsigned NOT NULL DEFAULT '0',
  `delivery_method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'conventional',
  `remaining` json DEFAULT NULL,
  `setting_version_id` bigint unsigned DEFAULT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'needs_review',
  `decision` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conditions` text COLLATE utf8mb4_unicode_ci,
  `decided_by` bigint unsigned DEFAULT NULL,
  `decided_on` date DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stackable_plans_reference_unique` (`reference`),
  KEY `stackable_plans_user_id_foreign` (`user_id`),
  KEY `stackable_plans_pathway_id_foreign` (`pathway_id`),
  KEY `stackable_plans_programme_id_foreign` (`programme_id`),
  KEY `stackable_plans_setting_version_id_foreign` (`setting_version_id`),
  KEY `stackable_plans_decided_by_foreign` (`decided_by`),
  KEY `stackable_plans_created_by_foreign` (`created_by`),
  CONSTRAINT `stackable_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stackable_plans_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stackable_plans_pathway_id_foreign` FOREIGN KEY (`pathway_id`) REFERENCES `pathways` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stackable_plans_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stackable_plans_setting_version_id_foreign` FOREIGN KEY (`setting_version_id`) REFERENCES `setting_versions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stackable_plans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stackable_plans`
--

LOCK TABLES `stackable_plans` WRITE;
/*!40000 ALTER TABLE `stackable_plans` DISABLE KEYS */;
INSERT INTO `stackable_plans` VALUES (1,'PLAN-2026-0001',1,1,1,'[1, 2, 4, 5]',0.0,5.0,100.0,6,'conventional','[{\"kind\": \"core\", \"name\": \"BITI 2213 Software Engineering\", \"status\": \"planned\", \"planned\": \"Year 2 Sem 1\"}, {\"kind\": \"core\", \"name\": \"BITP 3113 Operating Systems\", \"status\": \"todo\", \"planned\": \"Year 2 Sem 2\"}, {\"kind\": \"project\", \"name\": \"BITU 3983 Final Year Project I\", \"status\": \"todo\", \"planned\": \"Year 3 Sem 1\"}, {\"kind\": \"training\", \"name\": \"BITU 3926 Industrial Training\", \"status\": \"todo\", \"planned\": \"Year 3 Sem 2\"}]',NULL,'suitable','suitable','Must maintain minimum CGPA of 2.0 in major subjects.',10,'2026-10-25',1,'2026-10-10 01:42:39','2026-11-04 01:42:39'),(2,'PLAN-2026-0002',2,1,1,'[1, 2]',0.0,4.0,100.0,6,'conventional','[{\"kind\": \"core\", \"name\": \"BITI 2213 Software Engineering\", \"status\": \"planned\", \"planned\": \"Year 2 Sem 1\"}, {\"kind\": \"core\", \"name\": \"BITP 3113 Operating Systems\", \"status\": \"todo\", \"planned\": \"Year 2 Sem 2\"}, {\"kind\": \"project\", \"name\": \"BITU 3983 Final Year Project I\", \"status\": \"todo\", \"planned\": \"Year 3 Sem 1\"}, {\"kind\": \"training\", \"name\": \"BITU 3926 Industrial Training\", \"status\": \"todo\", \"planned\": \"Year 3 Sem 2\"}]',NULL,'submitted',NULL,NULL,NULL,NULL,2,'2026-10-28 10:00:00','2026-11-05 09:00:00'),(3,'PLAN-2026-0003',19,3,5,'[7]',0.0,3.0,100.0,3,'odl','[{\"kind\": \"core\", \"name\": \"MBA 5123 Managerial Economics\", \"status\": \"done\", \"planned\": \"Sem 1\"}, {\"kind\": \"project\", \"name\": \"MBA 5996 Executive Action Research Dissertation\", \"status\": \"done\", \"planned\": \"Sem 3\"}]',NULL,'suitable','suitable','Approved for ODL MBA stackable credit exemption.',25,'2026-10-18',19,'2026-10-12 10:00:00','2026-11-05 09:00:00'),(4,'PLAN-2026-0004',18,4,8,'[8, 9]',0.0,5.0,100.0,3,'odl','[{\"kind\": \"core\", \"name\": \"MAXL 5133 Big Data Engineering\", \"status\": \"planned\", \"planned\": \"Sem 2\"}, {\"kind\": \"project\", \"name\": \"MAXL 5986 Data Science Capstone\", \"status\": \"todo\", \"planned\": \"Sem 3\"}]',NULL,'draft',NULL,NULL,NULL,NULL,18,'2026-11-01 10:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `stackable_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suspensions`
--

DROP TABLE IF EXISTS `suspensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suspensions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `micro_credential_id` bigint unsigned NOT NULL,
  `offering_id` bigint unsigned DEFAULT NULL,
  `kind` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_from` date NOT NULL,
  `transition_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transition_plan` text COLLATE utf8mb4_unicode_ci,
  `offering_ids` json NOT NULL,
  `participants_notified` smallint unsigned NOT NULL DEFAULT '0',
  `authority` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `decided_by` bigint unsigned NOT NULL,
  `lifted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suspensions_micro_credential_id_foreign` (`micro_credential_id`),
  KEY `suspensions_offering_id_foreign` (`offering_id`),
  KEY `suspensions_decided_by_foreign` (`decided_by`),
  CONSTRAINT `suspensions_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `suspensions_micro_credential_id_foreign` FOREIGN KEY (`micro_credential_id`) REFERENCES `micro_credentials` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `suspensions_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suspensions`
--

LOCK TABLES `suspensions` WRITE;
/*!40000 ALTER TABLE `suspensions` DISABLE KEYS */;
INSERT INTO `suspensions` VALUES (1,2,6,'suspension','Lab cloud sandbox security patch and tenant isolation update.','2026-10-20','completion_window','Enrolled students will have access extended by 21 days upon resumption.','[6]',4,'CODL Dean Directive',12,NULL,'2026-10-20 01:42:39',NULL),(2,25,20,'termination','Legacy PLC v1 curriculum retired and superseded by v2 IEC 61131-3 & OPC-UA.','2026-10-15','new_version','Enrolled learners transitioned to v2 cohort at no additional fee.','[20]',1,'Senate 8/2026',11,NULL,'2026-10-15 10:00:00','2026-11-05 09:00:00'),(3,3,5,'suspension','Temporary 48-hour hold to update blended workshop venue and safety compliance checklist.','2026-10-21','completion_window','Workshop rescheduled within same cohort window.','[5]',1,'FTMK Deputy Dean',10,'2026-10-23 09:00:00','2026-10-21 09:00:00','2026-10-23 09:00:00');
/*!40000 ALTER TABLE `suspensions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assignee_role` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_ref` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `ptj_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `kind` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_id` bigint unsigned DEFAULT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_assignee_role_area_created_at_index` (`assignee_role`,`area`,`created_at`),
  KEY `tasks_ptj_id_foreign` (`ptj_id`),
  KEY `tasks_user_id_foreign` (`user_id`),
  CONSTRAINT `tasks_ptj_id_foreign` FOREIGN KEY (`ptj_id`) REFERENCES `ptjs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tasks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'deputy_dean','approval','Decide on Micro-Credential Proposal: Cyber Security Operations Fundamentals','MC-FTMK-004','2026-10-31 01:42:39',NULL,1,10,'proposal_decide',4,'/approvals/4'),(2,'lecturer','review','Review: Cyber Security Operations Fundamentals','REV-001','2026-10-31 01:42:39',NULL,1,6,'review_submit',1,'/reviews/1'),(3,'lecturer','review_comments','Respond to Review Comments: Database Systems','MC-FTMK-005','2026-11-02 01:42:39',NULL,1,6,'comment_respond',5,'/proposals/5?tab=comments'),(4,'codl_admin','qv_monitoring','Prepare Quality Verification Renewal: Cloud Native Development (41 days remaining)','QV-2023-051','2026-11-03 01:42:39',NULL,NULL,12,'qv_renew',1,'/quality/2'),(5,'deputy_dean','results','Verify Results: Data Structures 2026/2','OFF-002','2026-11-03 14:05:00',NULL,1,10,'verify_results',2,'/results/2'),(6,'industry_expert','review','Submit Industry Expert Review: Cloud Security Posture & DevSecOps Automation','MC-FTMK-010','2026-11-03 09:00:00',NULL,1,15,'review_submit',9,'/reviews/9'),(7,'deputy_dean','approval','Record Approval Decision: Enterprise API Architecture & Microservices Governance','MC-FTMK-011','2026-10-29 15:00:00',NULL,1,10,'proposal_decide',12,'/approvals/12'),(8,'deputy_dean','cqi','Confirm Cohort CQI Report: Secure SDLC 2026/1-SSDLC','CQI-2026-0004','2026-10-28 10:00:00',NULL,1,10,'cqi_confirm',4,'/cqi/4'),(9,'deputy_dean','acr','Confirm Academic Credit Record: Sharifah Puteri (SSDLC)','ACR-2026-0009','2026-10-26 10:00:00',NULL,1,10,'acr_confirm',9,'/deposits/9'),(10,'deputy_dean','transfer','Evaluate Credit Transfer Application: Bee Tan (TR-2026-0002)','TR-2026-0002','2026-10-30 10:00:00',NULL,1,10,'transfer_decide',2,'/transfers/2'),(11,'deputy_dean','pathway','Assess Stackable Study Plan: Bee Tan (PLAN-2026-0002)','PLAN-2026-0002','2026-10-28 10:00:00',NULL,1,10,'plan_decide',2,'/plans/2'),(12,'student','acr','Give ABC Deposit Consent for Data Structures (ACR-2026-0005)','ACR-2026-0005','2026-08-28 10:00:00',NULL,1,3,'consent_give',5,'/my-credit/5'),(13,'deputy_dean','records','Resolve Credit Deposit Discrepancy on ACR-2026-0003','DISC-0004','2026-11-03 09:00:00',NULL,1,10,'discrepancy_fix',4,'/records/discrepancies'),(14,'industry_expert','review','Submit Industry Expert Review: Cyber Security Operations Fundamentals','MC-FTMK-004','2026-10-31 09:00:00','2026-11-03 15:00:00',1,16,'review_submit',4,'/reviews/4'),(15,'deputy_dean','results','Verify Results: Data Structures 2026/1-DS','OFF-012','2026-08-22 10:00:00','2026-08-25 10:00:00',1,10,'verify_results',12,'/results/12');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `template_versions`
--

DROP TABLE IF EXISTS `template_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `template_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint unsigned NOT NULL,
  `locale` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int unsigned NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `set_by` bigint unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_versions_template_id_locale_version_unique` (`template_id`,`locale`,`version`),
  KEY `template_versions_set_by_foreign` (`set_by`),
  CONSTRAINT `template_versions_set_by_foreign` FOREIGN KEY (`set_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `template_versions_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `template_versions`
--

LOCK TABLES `template_versions` WRITE;
/*!40000 ALTER TABLE `template_versions` DISABLE KEYS */;
INSERT INTO `template_versions` VALUES (1,1,'en',1,'Your MCMS role changed','Hello {name}, you now hold the role {role} ({scope}) from {from}. Reason: {reason}.',NULL,'First version','2025-11-05 04:50:46'),(2,1,'ms',1,'Peranan MCMS anda telah berubah','Salam {name}, anda kini memegang peranan {role} ({scope}) mulai {from}. Sebab: {reason}.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(3,2,'en',1,'Your MCMS role ended','Hello {name}, your role {role} has ended. Reason: {reason}.',NULL,'First version','2025-11-05 04:50:46'),(4,2,'ms',1,'Peranan MCMS anda telah tamat','Salam {name}, peranan {role} anda telah tamat. Sebab: {reason}.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(5,3,'en',1,'Your MCMS account is deactivated','Hello {name}, your account has been deactivated. Reason: {reason}. Your records are kept.',NULL,'First version','2025-11-05 04:50:46'),(6,3,'ms',1,'Akaun MCMS anda dinyahaktifkan','Salam {name}, akaun anda telah dinyahaktifkan. Sebab: {reason}. Rekod anda dikekalkan.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(7,4,'en',1,'Your MCMS account is active again','Hello {name}, your account is active again.',NULL,'First version','2025-11-05 04:50:46'),(8,4,'ms',1,'Akaun MCMS anda aktif semula','Salam {name}, akaun anda aktif semula.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(9,5,'en',1,'A decision was recorded on {item}','Hello {name}, the decision on {item} is: {decision}.',NULL,'First version','2025-11-05 04:50:46'),(10,5,'ms',1,'Keputusan telah direkodkan bagi {item}','Salam {name}, keputusan bagi {item} ialah: {decision}.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(11,6,'en',1,'Proposal submitted: {title}','Hello {name}, {owner} submitted the proposal \"{title}\". Please assign reviewers.',NULL,'First version','2025-11-05 04:50:46'),(12,6,'ms',1,'Cadangan dihantar: {title}','Salam {name}, {owner} telah menghantar cadangan \"{title}\". Sila tetapkan penilai.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(13,7,'en',1,'Review requested: {title}','Hello {name}, you are asked to review \"{title}\" by {due}. Declare any conflict of interest before you open it.',NULL,'First version','2025-11-05 04:50:46'),(14,7,'ms',1,'Permintaan penilaian: {title}','Salam {name}, anda diminta menilai \"{title}\" sebelum {due}. Isytiharkan sebarang konflik kepentingan sebelum membukanya.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(15,8,'en',1,'Reviewer declined: {title}','Hello {name}, {reviewer} declared a conflict of interest and left the review of \"{title}\". Please assign another reviewer.',NULL,'First version','2025-11-05 04:50:46'),(16,8,'ms',1,'Penilai menarik diri: {title}','Salam {name}, {reviewer} mengisytiharkan konflik kepentingan dan menarik diri daripada penilaian \"{title}\". Sila tetapkan penilai lain.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(17,9,'en',1,'Review submitted: {title}','Hello {name}, {reviewer} submitted a review of \"{title}\": {recommendation}.',NULL,'First version','2025-11-05 04:50:46'),(18,9,'ms',1,'Penilaian dihantar: {title}','Salam {name}, {reviewer} telah menghantar penilaian \"{title}\": {recommendation}.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(19,10,'en',1,'Returned for amendment: {title}','Hello {name}, \"{title}\" is returned for amendment. Reason: {reason}. It is unlocked.',NULL,'First version','2025-11-05 04:50:46'),(20,10,'ms',1,'Dikembalikan untuk pindaan: {title}','Salam {name}, \"{title}\" dikembalikan untuk pindaan. Sebab: {reason}. Ia dibuka semula.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(21,11,'en',1,'Endorsed: {title}','Hello {name}, \"{title}\" is endorsed and waits for the Senate decision.',NULL,'First version','2025-11-05 04:50:46'),(22,11,'ms',1,'Disokong: {title}','Salam {name}, \"{title}\" telah disokong dan menunggu keputusan Senat.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(23,12,'en',1,'Minor change recorded: {title}','Hello {name}, {owner} recorded a minor change to \"{title}\": {summary}. Please acknowledge it.',NULL,'First version','2025-11-05 04:50:46'),(24,12,'ms',1,'Perubahan kecil direkodkan: {title}','Salam {name}, {owner} merekodkan perubahan kecil bagi \"{title}\": {summary}. Sila akui.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(25,13,'en',1,'Please pay to complete your registration: {title}','Hello {name}, your place in \"{title}\" is held while you pay RM {fee}. Open \"Registrations and payments\" to pay.',NULL,'First version','2025-11-05 04:50:46'),(26,13,'ms',1,'Sila bayar untuk melengkapkan pendaftaran: {title}','Salam {name}, tempat anda dalam \"{title}\" dikekalkan sementara anda membayar RM {fee}. Buka \"Pendaftaran dan bayaran\" untuk membayar.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(27,14,'en',1,'Payment received: {reference}','Hello {name}, we received RM {amount} for registration {reference} (payment reference {payment}).',NULL,'First version','2025-11-05 04:50:46'),(28,14,'ms',1,'Bayaran diterima: {reference}','Salam {name}, kami menerima RM {amount} untuk pendaftaran {reference} (rujukan bayaran {payment}).',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(29,15,'en',1,'You are enrolled: {title}','Hello {name}, you are enrolled in \"{title}\" (ID {reference}). The cohort starts on {start}. Open the learning platform course: {platform}.',NULL,'First version','2025-11-05 04:50:46'),(30,15,'ms',1,'Anda telah didaftarkan: {title}','Salam {name}, anda telah didaftarkan dalam \"{title}\" (ID {reference}). Kohort bermula pada {start}. Buka kursus platform pembelajaran: {platform}.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(31,16,'en',1,'You have withdrawn: {title}','Hello {name}, you have withdrawn from \"{title}\". Refunds are handled outside MCMS: ask the CODL office.',NULL,'First version','2025-11-05 04:50:46'),(32,16,'ms',1,'Anda telah menarik diri: {title}','Salam {name}, anda telah menarik diri daripada \"{title}\". Bayaran balik diuruskan di luar MCMS: hubungi pejabat CODL.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(33,17,'en',1,'A change to {title}','Hello {name}, version {version} of \"{title}\" takes effect on {date}. What this means for you: {rule}.',NULL,'First version','2025-11-05 04:50:46'),(34,17,'ms',1,'Perubahan pada {title}','Salam {name}, versi {version} bagi \"{title}\" berkuat kuasa pada {date}. Maksudnya bagi anda: {rule}.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(35,18,'en',1,'UCreds {kind} staged: {title}','Hello {name}, {staged} rows of the UCreds {kind} file for \"{title}\" were staged. {detail} Complete any missing marks on the cohort results page.',NULL,'First version','2025-11-05 04:50:46'),(36,18,'ms',1,'Fail UCreds disediakan: {title}','Salam {name}, {staged} baris fail UCreds {kind} bagi \"{title}\" telah disediakan. {detail} Lengkapkan markah yang tiada pada halaman keputusan kohort.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(37,19,'en',1,'Your results are verified: {title}','Hello {name}, your results for \"{title}\" are verified. Open \"My results\" to see them.',NULL,'First version','2025-11-05 04:50:46'),(38,19,'ms',1,'Keputusan anda telah disahkan: {title}','Salam {name}, keputusan anda bagi \"{title}\" telah disahkan. Buka \"Keputusan saya\" untuk melihatnya.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(39,20,'en',1,'Completion confirmed: {title}','Hello {name}, the deputy dean confirmed that you completed \"{title}\". Your certificate or Digital Credential is issued after this confirmation.',NULL,'First version','2025-11-05 04:50:46'),(40,20,'ms',1,'Penamatan disahkan: {title}','Salam {name}, timbalan dekan telah mengesahkan bahawa anda menamatkan \"{title}\". Sijil atau Digital Credential anda dikeluarkan selepas pengesahan ini.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(41,21,'en',1,NULL,'I agree that UTeM may send my Academic Credit Record to the Academic Bank of Credit (ABC) for storage. A deposit is storage of a credit record only. It is not a credit transfer and does not mean any programme accepts the credit. I can decline or postpone and give consent later.',NULL,'First version','2025-11-05 04:50:46'),(42,21,'ms',1,NULL,'Saya bersetuju UTeM menghantar Rekod Kredit Akademik saya kepada Akaun Bank Kredit (ABC) untuk disimpan. Deposit ialah penyimpanan rekod kredit sahaja. Ia bukan pindahan kredit dan tidak bermakna mana-mana program menerima kredit tersebut. Saya boleh menolak atau menangguhkan dan memberi persetujuan kemudian.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(43,22,'en',1,'Your Academic Credit Record is ready: {title}','Hello {name}, your Academic Credit Record for \"{title}\" is confirmed. Open \"Credit ledger and ABC\" to give, decline or postpone consent to deposit it. Nothing is deposited without your consent.',NULL,'First version','2025-11-05 04:50:46'),(44,22,'ms',1,'Rekod Kredit Akademik anda sedia: {title}','Salam {name}, Rekod Kredit Akademik anda bagi \"{title}\" telah disahkan. Buka \"Lejar kredit dan ABC\" untuk memberi, menolak atau menangguhkan persetujuan deposit. Tiada apa didepositkan tanpa persetujuan anda.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(45,23,'en',1,'Your credit is deposited: {title}','Hello {name}, your Academic Credit Record for \"{title}\" is deposited in ABC and the credit is available until {date}. A deposit is storage only and is not a credit transfer.',NULL,'First version','2025-11-05 04:50:46'),(46,23,'ms',1,'Kredit anda didepositkan: {title}','Salam {name}, Rekod Kredit Akademik anda bagi \"{title}\" telah didepositkan di ABC dan kredit tersedia sehingga {date}. Deposit ialah penyimpanan sahaja dan bukan pindahan kredit.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(47,24,'en',1,'Your credit ends in {days} days: {title}','Hello {name}, the credit from \"{title}\" is valid until {date}, in {days} days. After that it shows as Expired for transfer purposes. Your record is kept.',NULL,'First version','2025-11-05 04:50:46'),(48,24,'ms',1,'Kredit anda tamat dalam {days} hari: {title}','Salam {name}, kredit daripada \"{title}\" sah sehingga {date}, dalam {days} hari. Selepas itu ia dipaparkan Tamat Tempoh untuk tujuan pindahan. Rekod anda kekal.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(49,25,'en',1,'Your credit has expired: {title}','Hello {name}, the credit from \"{title}\" expired on {date}. Your Academic Credit Record is kept. A transfer request that uses it is flagged for re-evaluation.',NULL,'First version','2025-11-05 04:50:46'),(50,25,'ms',1,'Kredit anda telah tamat tempoh: {title}','Salam {name}, kredit daripada \"{title}\" tamat tempoh pada {date}. Rekod Kredit Akademik anda kekal. Permohonan pindahan yang menggunakannya ditanda untuk penilaian semula.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(51,26,'en',1,'Credit transfer {ref}: {outcome}','Hello {name}, the decision on your credit transfer application {ref} is: {outcome}. Open \"Credit transfer\" for the details. A transfer decision is separate from admission to the programme.',NULL,'First version','2025-11-05 04:50:46'),(52,26,'ms',1,'Pindahan kredit {ref}: {outcome}','Salam {name}, keputusan permohonan pindahan kredit anda {ref} ialah: {outcome}. Buka \"Pindahan kredit\" untuk butiran. Keputusan pindahan adalah berasingan daripada kemasukan ke program.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(53,27,'en',1,'More evidence needed: {ref}','Hello {name}, the evaluator needs more evidence for your credit transfer application {ref}: {request}',NULL,'First version','2025-11-05 04:50:46'),(54,27,'ms',1,'Bukti tambahan diperlukan: {ref}','Salam {name}, penilai memerlukan bukti tambahan bagi permohonan pindahan kredit anda {ref}: {request}',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(55,28,'en',1,'Your degree plan {ref}: {decision}','Hello {name}, the decision on your stackable degree plan {ref} is: {decision}. A plan is advice. It is not admission, a credit transfer or an award.',NULL,'First version','2025-11-05 04:50:46'),(56,28,'ms',1,'Pelan ijazah anda {ref}: {decision}','Salam {name}, keputusan pelan ijazah berperingkat anda {ref} ialah: {decision}. Pelan ialah nasihat. Ia bukan kemasukan, pindahan kredit atau anugerah.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(57,29,'en',1,'Your cohort was {kind}: {title}','Hello {name}, the cohort \"{title}\" was {kind} from {date}. Reason: {reason}. What happens to you: {plan}. Achievements, credentials and your Academic Credit Record you already earned stay valid.',NULL,'First version','2025-11-05 04:50:46'),(58,29,'ms',1,'Kohort anda {kind}: {title}','Salam {name}, kohort \"{title}\" telah {kind} mulai {date}. Sebab: {reason}. Apa yang berlaku kepada anda: {plan}. Pencapaian, kredensial dan Rekod Kredit Akademik yang telah anda peroleh kekal sah.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(59,30,'en',1,'Periodic review due on {date}: {title}','Hello {name}, the periodic review of \"{title}\" is due on {date}. Record its outcome in Quality review.',NULL,'First version','2025-11-05 04:50:46'),(60,30,'ms',1,'Semakan berkala pada {date}: {title}','Salam {name}, semakan berkala bagi \"{title}\" perlu dibuat pada {date}. Rekodkan hasilnya dalam Semakan kualiti.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(61,31,'en',1,'A correction affects your record','Hello {name}, a mismatch in your records was corrected. What was found: {what} What was done: {action} The original is kept in the record and your earlier achievements stay valid.',NULL,'First version','2025-11-05 04:50:46'),(62,31,'ms',1,'Pembetulan melibatkan rekod anda','Salam {name}, ketidakpadanan dalam rekod anda telah dibetulkan. Apa yang ditemui: {what} Apa yang dilakukan: {action} Rekod asal disimpan dan pencapaian anda yang terdahulu kekal sah.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(63,32,'en',1,'Quality Verification ends in {days} days: {title}','Hello {name}, the Quality Verification of \"{title}\" ends on {date}, in {days} days. Prepare a new cycle so the credit and level stay in the catalogue.',NULL,'First version','2025-11-05 04:50:46'),(64,32,'ms',1,'Pengesahan Kualiti tamat dalam {days} hari: {title}','Salam {name}, Pengesahan Kualiti bagi \"{title}\" tamat pada {date}, dalam {days} hari. Sediakan kitaran baharu supaya kredit dan tahap kekal dalam katalog.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(65,33,'en',1,'Quality Verification ended: {title}','Hello {name}, the Quality Verification of \"{title}\" ended. Reason: {why}. The credit and level are no longer shown for new cohorts and new credentials. Credentials already earned stay.',NULL,'First version','2025-11-05 04:50:46'),(66,33,'ms',1,'Pengesahan Kualiti tamat: {title}','Salam {name}, Pengesahan Kualiti bagi \"{title}\" telah tamat. Sebab: {why}. Kredit dan tahap tidak lagi dipaparkan untuk kohort dan kredensial baharu. Kredensial yang telah diperoleh kekal.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(67,34,'en',1,'Time to finish: {title}','Hello {name}, you have until {date} to complete all modules of \"{title}\".',NULL,'First version','2025-11-05 04:50:46'),(68,34,'ms',1,'Masa untuk menamatkan: {title}','Salam {name}, anda mempunyai sehingga {date} untuk menamatkan semua modul \"{title}\".',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(69,35,'en',1,'Your credential is issued: {title}','Hello {name}, your certificate and Digital Credential for \"{title}\" are issued (number {number}). Open \"Credentials\" to see, share and download them.',NULL,'First version','2025-11-05 04:50:46'),(70,35,'ms',1,'Kredensial anda dikeluarkan: {title}','Salam {name}, sijil dan Digital Credential anda bagi \"{title}\" telah dikeluarkan (nombor {number}). Buka \"Kredensial\" untuk melihat, berkongsi dan memuat turunnya.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(71,36,'en',1,'Your credential was corrected: {number}','Hello {name}, credential {number} was corrected. Reason: {reason}. The original is kept and the public link shows Corrected.',NULL,'First version','2025-11-05 04:50:46'),(72,36,'ms',1,'Kredensial anda telah dibetulkan: {number}','Salam {name}, kredensial {number} telah dibetulkan. Sebab: {reason}. Yang asal disimpan dan pautan awam menunjukkan Dibetulkan.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(73,37,'en',1,'Your credential was replaced: {number}','Hello {name}, credential {number} was replaced by a new one. Reason: {reason}. Share the new link.',NULL,'First version','2025-11-05 04:50:46'),(74,37,'ms',1,'Kredensial anda telah diganti: {number}','Salam {name}, kredensial {number} telah diganti dengan yang baharu. Sebab: {reason}. Kongsikan pautan baharu.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(75,38,'en',1,'Your credential was revoked: {number}','Hello {name}, credential {number} was revoked. Reason: {reason}.',NULL,'First version','2025-11-05 04:50:46'),(76,38,'ms',1,'Kredensial anda telah dibatalkan: {number}','Salam {name}, kredensial {number} telah dibatalkan. Sebab: {reason}.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(77,39,'en',1,'Registration cancelled: {title}','Hello {name}, your registration for \"{title}\" is cancelled. {reason}',NULL,'First version','2025-11-05 04:50:46'),(78,39,'ms',1,'Pendaftaran dibatalkan: {title}','Salam {name}, pendaftaran anda untuk \"{title}\" dibatalkan. {reason}',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(79,40,'en',1,NULL,'This is an Unbundled Micro-Credential (UMC) from {parent}. Completing the whole UMC carries the credit of the parent course. A module or a badge on its own is not completion and not credit.',NULL,'First version','2025-11-05 04:50:46'),(80,40,'ms',1,NULL,'Ini ialah Unbundled Micro-Credential (UMC) daripada {parent}. Menamatkan keseluruhan UMC membawa kredit kursus induk. Satu modul atau satu lencana sahaja bukan penamatan dan bukan kredit.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(81,41,'en',1,NULL,'This is a Standalone Micro-Credential (SAMC). It carries no credit value and no MQF level unless it is Quality-Verified and the verification is valid.',NULL,'First version','2025-11-05 04:50:46'),(82,41,'ms',1,NULL,'Ini ialah Standalone Micro-Credential (SAMC). Ia tidak membawa nilai kredit atau tahap MQF melainkan telah disahkan melalui Quality Verification dan pengesahan itu masih sah.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(83,42,'en',1,NULL,'This SAMC is Quality-Verified. Credit {credit}, MQF level {mqf}, valid until {until}.',NULL,'First version','2025-11-05 04:50:46'),(84,42,'ms',1,NULL,'SAMC ini telah disahkan melalui Quality Verification. Kredit {credit}, tahap MQF {mqf}, sah hingga {until}.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(85,43,'en',1,NULL,'This is an Executive Micro-Credential (MCE). It earns no academic credit, no Academic Credit Record and no deposit to the Academic Bank of Credit.',NULL,'First version','2025-11-05 04:50:46'),(86,43,'ms',1,NULL,'Ini ialah Micro-Credential Eksekutif (MCE). Ia tidak memberi kredit akademik, Academic Credit Record atau deposit ke Academic Bank of Credit.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(87,44,'en',1,NULL,'Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.',NULL,'First version','2025-11-05 04:50:46'),(88,44,'ms',1,NULL,'Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.',NULL,'Keluaran awal templat','2025-11-05 04:50:46'),(89,1,'en',2,'Your MCMS role assignment has been updated','Hello {name}, you now hold the role {role} ({scope}) effective {from}. Governance reason: {reason}.',12,'Clarified governance reason label in role notification.','2026-06-01 10:00:00'),(90,1,'ms',2,'Penetapan peranan MCMS anda telah dikemas kini','Salam {name}, anda kini memegang peranan {role} ({scope}) berkuat kuasa {from}. Sebab tadbir urus: {reason}.',12,'Penambahbaikan istilah tadbir urus.','2026-06-01 10:00:00');
/*!40000 ALTER TABLE `template_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mandatory` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `templates_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `templates`
--

LOCK TABLES `templates` WRITE;
/*!40000 ALTER TABLE `templates` DISABLE KEYS */;
INSERT INTO `templates` VALUES (1,'role.assigned','notification',0,'A role was given to a user'),(2,'role.revoked','notification',0,'A role was taken away from a user'),(3,'account.deactivated','notification',1,'An account was deactivated'),(4,'account.reactivated','notification',1,'An account was reactivated'),(5,'decision.made','notification',1,'A decision was made on something the user submitted'),(6,'proposal.submitted','notification',0,'A proposal was submitted to the deputy dean'),(7,'reviewer.assigned','notification',0,'A reviewer was asked to review a proposal'),(8,'reviewer.declined','notification',0,'A reviewer declared a conflict of interest'),(9,'review.submitted','notification',0,'A review was submitted'),(10,'proposal.returned','notification',1,'A proposal was returned for amendment'),(11,'proposal.endorsed','notification',1,'A proposal was endorsed'),(12,'change.minor','notification',0,'A minor change was recorded'),(13,'enrolment.registered','notification',0,'A registration waits for payment'),(14,'payment.confirmed','notification',1,'A payment was received'),(15,'enrolment.confirmed','notification',1,'An enrolment is confirmed, with access instructions'),(16,'enrolment.withdrawn','notification',1,'A participant withdrew'),(17,'version.changed','notification',1,'An approved change affects current participants'),(18,'results.staged','notification',0,'Results or badges were staged from a UCreds file'),(19,'results.verified','notification',0,'Results were verified'),(20,'completion.confirmed','notification',1,'Completion of a micro-credential was confirmed'),(21,'consent.abc_deposit','statement',1,'Wording of the consent to deposit an Academic Credit Record'),(22,'acr.confirmed','notification',1,'The Academic Credit Record was confirmed and needs consent'),(23,'abc.deposited','notification',1,'The credit was deposited'),(24,'credit.expiring','notification',1,'Credit validity is ending'),(25,'credit.expired','notification',1,'Credit validity ended'),(26,'transfer.decided','notification',1,'A credit transfer decision was recorded'),(27,'transfer.evidence','notification',1,'More evidence is needed for a transfer'),(28,'plan.decided','notification',1,'A stackable plan decision was recorded'),(29,'suspension.notice','notification',1,'An offering was suspended or terminated'),(30,'review.due','notification',0,'A periodic review is due'),(31,'correction.recorded','notification',1,'A correction was recorded'),(32,'qv.expiring','notification',0,'Quality Verification is about to end'),(33,'qv.lapsed','notification',1,'Quality Verification ended'),(34,'completion.window','notification',0,'The time to finish all modules is running out'),(35,'credential.issued','notification',1,'A credential was issued'),(36,'credential.corrected','notification',1,'A credential was corrected'),(37,'credential.replaced','notification',1,'A credential was replaced'),(38,'credential.revoked','notification',1,'A credential was revoked'),(39,'enrolment.cancelled','notification',1,'A registration was cancelled'),(40,'statement.catalogue.umc','statement',0,'Catalogue statement for a UMC'),(41,'statement.catalogue.samc','statement',0,'Catalogue statement for a SAMC without valid Quality Verification'),(42,'statement.catalogue.samc_qv','statement',0,'Catalogue statement for a Quality-Verified SAMC'),(43,'statement.catalogue.mce','statement',0,'Catalogue statement for an MCE'),(44,'statement.separate_stages','statement',0,'Notice shown with every offering and credential (D-15)');
/*!40000 ALTER TABLE `templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_applications`
--

DROP TABLE IF EXISTS `transfer_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `programme_id` bigint unsigned NOT NULL,
  `institution` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UTeM',
  `documents` text COLLATE utf8mb4_unicode_ci,
  `evidence_request` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `validity` json DEFAULT NULL,
  `validity_by` bigint unsigned DEFAULT NULL,
  `applied_by` bigint unsigned NOT NULL,
  `applied_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_applications_reference_unique` (`reference`),
  KEY `transfer_applications_user_id_foreign` (`user_id`),
  KEY `transfer_applications_programme_id_foreign` (`programme_id`),
  KEY `transfer_applications_validity_by_foreign` (`validity_by`),
  KEY `transfer_applications_applied_by_foreign` (`applied_by`),
  CONSTRAINT `transfer_applications_applied_by_foreign` FOREIGN KEY (`applied_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `transfer_applications_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `transfer_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `transfer_applications_validity_by_foreign` FOREIGN KEY (`validity_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_applications`
--

LOCK TABLES `transfer_applications` WRITE;
/*!40000 ALTER TABLE `transfer_applications` DISABLE KEYS */;
INSERT INTO `transfer_applications` VALUES (1,'TR-2026-0001',1,1,'UTeM','acr-2026-0001.pdf;portfolio-amir.pdf',NULL,'approved','{\"abc_status\": {\"note\": \"ABC deposit status confirmed as available/provisional.\", \"result\": \"pass\"}, \"authenticity\": {\"note\": \"Verified against CODL credential and ACR registry.\", \"result\": \"pass\"}, \"qv_accreditation\": {\"note\": \"MQA Quality Verification cycle active on completion date.\", \"result\": \"pass\"}}',10,1,'2026-10-15 01:42:39','2026-10-15 01:42:39','2026-11-04 01:42:39'),(2,'TR-2026-0002',2,1,'UTeM','acr-2026-0002.pdf',NULL,'submitted',NULL,NULL,2,'2026-10-30 01:42:39','2026-10-30 01:42:39','2026-11-04 01:42:39'),(3,'TR-2026-0003',1,1,'UTeM','acr-2026-0003.pdf;syllabus-ds.pdf',NULL,'approved','{\"abc_status\": {\"note\": \"ABC deposit status confirmed as available/provisional.\", \"result\": \"pass\"}, \"authenticity\": {\"note\": \"Verified against CODL credential and ACR registry.\", \"result\": \"pass\"}, \"qv_accreditation\": {\"note\": \"MQA Quality Verification cycle active on completion date.\", \"result\": \"pass\"}}',10,1,'2026-09-02 10:00:00','2026-09-02 10:00:00','2026-11-05 09:00:00'),(4,'TR-2026-0004',19,5,'UTeM','acr-2026-0008.pdf;mba-pitch.pdf',NULL,'approved','{\"abc_status\": {\"note\": \"ABC deposit status confirmed as available/provisional.\", \"result\": \"pass\"}, \"authenticity\": {\"note\": \"Verified against CODL credential and ACR registry.\", \"result\": \"pass\"}, \"qv_accreditation\": {\"note\": \"MQA Quality Verification cycle active on completion date.\", \"result\": \"pass\"}}',25,19,'2026-10-14 10:00:00','2026-10-14 10:00:00','2026-11-05 09:00:00'),(5,'TR-2026-0005',1,1,'UTeM','acr-2026-0006.pdf;acr-2026-0007.pdf','Please attach JUnit test coverage report for BITD 1123 equivalence.','under_eval','{\"abc_status\": {\"note\": \"ABC deposit status confirmed as available/provisional.\", \"result\": \"pass\"}, \"authenticity\": {\"note\": \"Verified against CODL credential and ACR registry.\", \"result\": \"pass\"}, \"qv_accreditation\": {\"note\": \"MQA Quality Verification cycle active on completion date.\", \"result\": \"pass\"}}',10,1,'2026-10-30 10:00:00','2026-10-30 10:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `transfer_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_decisions`
--

DROP TABLE IF EXISTS `transfer_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_decisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_application_id` bigint unsigned NOT NULL,
  `outcome` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `decided_by` bigint unsigned NOT NULL,
  `decided_on` date NOT NULL,
  `authority` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `decision_ref` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conditions` text COLLATE utf8mb4_unicode_ci,
  `warnings` json DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transfer_decisions_transfer_application_id_foreign` (`transfer_application_id`),
  KEY `transfer_decisions_decided_by_foreign` (`decided_by`),
  CONSTRAINT `transfer_decisions_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `transfer_decisions_transfer_application_id_foreign` FOREIGN KEY (`transfer_application_id`) REFERENCES `transfer_applications` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_decisions`
--

LOCK TABLES `transfer_decisions` WRITE;
/*!40000 ALTER TABLE `transfer_decisions` DISABLE KEYS */;
INSERT INTO `transfer_decisions` VALUES (1,1,'approved',10,'2026-10-25','FTMK Academic Board','JKKP-FTMK-TR-2026/02','Exemption granted for 2 elective credits.',NULL,'2026-10-25 01:42:39'),(2,3,'approved',10,'2026-09-12','FTMK Academic Board','JKKP-FTMK-TR-2026/05','Full 3.0 credit exemption granted for BITS 1123 Data Structures and Algorithms.',NULL,'2026-09-12 10:00:00'),(3,4,'approved',25,'2026-10-20','FPTT Postgraduate Committee','JKPS-FPTT-TR-2026/03','Full 3.0 credit exemption granted for MBA 5113.',NULL,'2026-10-20 10:00:00');
/*!40000 ALTER TABLE `transfer_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_lines`
--

DROP TABLE IF EXISTS `transfer_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_application_id` bigint unsigned NOT NULL,
  `acr_id` bigint unsigned NOT NULL,
  `abc_deposit_id` bigint unsigned NOT NULL,
  `mc_version_id` bigint unsigned NOT NULL,
  `parent_course_id` bigint unsigned NOT NULL,
  `credits_requested` decimal(5,1) NOT NULL,
  `credits_approved` decimal(5,1) DEFAULT NULL,
  `decision` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reevaluate` tinyint(1) NOT NULL DEFAULT '0',
  `target_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_version_no` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transfer_lines_transfer_application_id_foreign` (`transfer_application_id`),
  KEY `transfer_lines_acr_id_foreign` (`acr_id`),
  KEY `transfer_lines_abc_deposit_id_foreign` (`abc_deposit_id`),
  KEY `transfer_lines_mc_version_id_foreign` (`mc_version_id`),
  KEY `transfer_lines_parent_course_id_foreign` (`parent_course_id`),
  CONSTRAINT `transfer_lines_abc_deposit_id_foreign` FOREIGN KEY (`abc_deposit_id`) REFERENCES `abc_deposits` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `transfer_lines_acr_id_foreign` FOREIGN KEY (`acr_id`) REFERENCES `acrs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `transfer_lines_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `transfer_lines_parent_course_id_foreign` FOREIGN KEY (`parent_course_id`) REFERENCES `parent_courses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `transfer_lines_transfer_application_id_foreign` FOREIGN KEY (`transfer_application_id`) REFERENCES `transfer_applications` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_lines`
--

LOCK TABLES `transfer_lines` WRITE;
/*!40000 ALTER TABLE `transfer_lines` DISABLE KEYS */;
INSERT INTO `transfer_lines` VALUES (1,1,1,1,2,2,2.0,2.0,'approved',0,'2024',1,'2026-10-15 01:42:39','2026-11-04 01:42:39'),(2,2,2,2,2,2,2.0,NULL,NULL,0,'2024',1,'2026-10-30 01:42:39','2026-11-04 01:42:39'),(3,3,3,3,1,1,3.0,3.0,'approved',0,'2024',1,'2026-09-02 10:00:00','2026-11-05 09:00:00'),(4,4,8,7,21,13,3.0,3.0,'approved',0,'2025',1,'2026-10-14 10:00:00','2026-11-05 09:00:00'),(5,5,6,5,9,17,2.0,NULL,NULL,0,'2024',1,'2026-10-30 10:00:00','2026-11-05 09:00:00'),(6,5,7,6,18,10,3.0,NULL,NULL,0,'2024',1,'2026-10-30 10:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `transfer_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ucreds_courses`
--

DROP TABLE IF EXISTS `ucreds_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ucreds_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_code` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hours` decimal(7,2) DEFAULT NULL,
  `fee` decimal(9,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ucreds_courses_course_id_unique` (`course_id`),
  KEY `ucreds_courses_subject_code_index` (`subject_code`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ucreds_courses`
--

LOCK TABLES `ucreds_courses` WRITE;
/*!40000 ALTER TABLE `ucreds_courses` DISABLE KEYS */;
INSERT INTO `ucreds_courses` VALUES (1,78,'Master of Business Administration','programme','ODL Master Degree Programmes','Master',NULL,'1600 Hours 00 Minutes',1600.00,22610.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(2,80,'Master of Technology in Data Science and Analytics','programme','ODL Master Degree Programmes','Master',NULL,'1600 Hours 00 Minutes',1600.00,25110.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(3,1560,'Master of Electronic Engineering (Computer Engineering)','programme','ODL Master Degree Programmes','Master',NULL,'1600 Hours 00 Minutes',1600.00,NULL,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(4,155,'First Order Differential Equations','micro_course','Mathematics, Calculus & Computational Methods','Intermediate',NULL,'18 Hours 00 Minutes',18.00,12.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(5,140,'Second Order Linear Differential Equations','micro_course','Mathematics, Calculus & Computational Methods','Intermediate',NULL,'18 Hours 00 Minutes',18.00,12.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(6,138,'Laplace Transform','micro_course','Mathematics, Calculus & Computational Methods','Intermediate',NULL,'33 Hours 00 Minutes',33.00,12.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(7,157,'Fourier Series','micro_course','Mathematics, Calculus & Computational Methods','Intermediate',NULL,'18 Hours 00 Minutes',18.00,10.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(8,154,'Partial Differential Equations','micro_course','Mathematics, Calculus & Computational Methods','Intermediate',NULL,'33 Hours 00 Minutes',33.00,12.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(9,1430,'Math is Fun Aras 1: Penambahan dan Penolakan (Lingkungan 0-100, Tahun 1)','micro_course','Mathematics, Calculus & Computational Methods','Beginner',NULL,NULL,NULL,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(10,1431,'Math is Fun Aras 2: Penambahan dan Penolakan (Lingkungan 1000, Tahun 2)','micro_course','Mathematics, Calculus & Computational Methods','Beginner',NULL,NULL,NULL,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(11,1432,'Math is Fun Aras 3: Penambahan, Penolakan, Darab, dan Bahagi (Lingkungan 10,000, Tahun 3)','micro_course','Mathematics, Calculus & Computational Methods','Beginner',NULL,NULL,NULL,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(12,1433,'Math is Fun Aras 4: Operasi Aritmetik Lanjutan (Lingkungan 100,000, Tahun 4)','micro_course','Mathematics, Calculus & Computational Methods','Beginner',NULL,NULL,NULL,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(13,1434,'Math is Fun Aras 5: Operasi Aritmetik Kompleks (Lingkungan 1,000,000, Tahun 5)','micro_course','Mathematics, Calculus & Computational Methods','Beginner',NULL,NULL,NULL,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(14,1435,'Math is Fun Aras 6: Penguatan Operasi Aritmetik (Lingkungan 10,000,000, Tahun 6)','micro_course','Mathematics, Calculus & Computational Methods','Beginner',NULL,NULL,NULL,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(15,661,'Application of Graph Theory: Network Modelling','micro_course','Mathematics, Calculus & Computational Methods','Intermediate',NULL,'11 Hours 00 Minutes',11.00,29.90,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(16,1425,'MATH is FUN Juara Asas Pengiraan','micro_course','Mathematics, Calculus & Computational Methods','Beginner',NULL,NULL,NULL,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(17,515,'Embracing Machine Learning for Business Decision [PM Choo]','micro_course','Artificial Intelligence, Data Science & Machine Learning','Beginner',NULL,'06 Hours 30 Minutes',6.50,50.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(18,517,'7. [MAXL5143] Introduction to Deep Learning','micro_course','Artificial Intelligence, Data Science & Machine Learning','Intermediate','MAXL5143','07 Hours 30 Minutes',7.50,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(19,530,'6.[MAXL5113] Overview of Analytics Models','micro_course','Artificial Intelligence, Data Science & Machine Learning','Beginner','MAXL5113','06 Hours 00 Minutes',6.00,50.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(20,435,'Data Modeling','micro_course','Artificial Intelligence, Data Science & Machine Learning','Beginner',NULL,'16 Hours 00 Minutes',16.00,84.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(21,262,'Artificial Intelligence for Everyone','micro_course','Artificial Intelligence, Data Science & Machine Learning','Beginner',NULL,'08 Hours 00 Minutes',8.00,10.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(22,519,'Time Series Forecasting','micro_course','Artificial Intelligence, Data Science & Machine Learning','Intermediate',NULL,'08 Hours 00 Minutes',8.00,150.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(23,665,'Kick-start Your Ambition as Machine Learning Engineer','micro_course','Artificial Intelligence, Data Science & Machine Learning','Beginner',NULL,'08 Hours 00 Minutes',8.00,9.99,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(24,817,'Working with Machine Learning by Examples','micro_course','Artificial Intelligence, Data Science & Machine Learning','Intermediate',NULL,'15 Hours 00 Minutes',15.00,19.99,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(25,1296,'MAXL5273 Customer and Financial Analytics [Elective]','micro_course','Artificial Intelligence, Data Science & Machine Learning','Intermediate','MAXL5273','120 Hours 00 Minutes',120.00,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(26,648,'Journey Towards Artificial Intelligence','micro_course','Artificial Intelligence, Data Science & Machine Learning','Beginner',NULL,'10 Hours 00 Minutes',10.00,10.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(27,486,'Phishing Techniques','micro_course','Cybersecurity, Information Security & Privacy','Beginner',NULL,'04 Hours 00 Minutes',4.00,9.99,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(28,436,'Social Engineering','micro_course','Cybersecurity, Information Security & Privacy','Beginner',NULL,'04 Hours 00 Minutes',4.00,9.99,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(29,487,'Security at home','micro_course','Cybersecurity, Information Security & Privacy','Beginner',NULL,'04 Hours 00 Minutes',4.00,9.99,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(30,489,'Password and Authentication 101','micro_course','Cybersecurity, Information Security & Privacy','Beginner',NULL,'04 Hours 00 Minutes',4.00,9.99,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(31,543,'Public Wi-Fi Security','micro_course','Cybersecurity, Information Security & Privacy','Beginner',NULL,'04 Hours 00 Minutes',4.00,9.99,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(32,520,'1. [MAXL5123] Big Data Management Demystified','micro_course','Database Engineering & Big Data Management','Intermediate','MAXL5123','07 Hours 00 Minutes',7.00,50.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(33,644,'Fundamentals of a Database','micro_course','Database Engineering & Big Data Management','Beginner',NULL,'16 Hours 00 Minutes',16.00,84.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(34,512,'Maximize Your Database Efficiency: A Normalization Practical Guide','micro_course','Database Engineering & Big Data Management','Beginner',NULL,'16 Hours 00 Minutes',16.00,80.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(35,1883,'Python Essentials 1: Module 3','micro_course','Software Engineering, Programming & Mobile Apps','Beginner',NULL,NULL,NULL,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(36,2009,'Python Essentials 1: Module 4','micro_course','Software Engineering, Programming & Mobile Apps','Beginner',NULL,NULL,NULL,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(37,290,'Java Programming for Beginners','micro_course','Software Engineering, Programming & Mobile Apps','Beginner',NULL,'04 Hours 00 Minutes',4.00,50.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(38,571,'Easy-Peasy Python Programming','micro_course','Software Engineering, Programming & Mobile Apps','Beginner',NULL,'08 Hours 00 Minutes',8.00,20.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(39,827,'Membina Aplikasi Flutter: Panduan Praktikal','micro_course','Software Engineering, Programming & Mobile Apps','Beginner',NULL,'25 Hours 00 Minutes',25.00,150.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(40,1541,'PlaCe-GMC: Player-Based JavaScript Journey','micro_course','Software Engineering, Programming & Mobile Apps','Beginner',NULL,'08 Hours 00 Minutes',8.00,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(41,1881,'Level 1: Hello, World! 🐍 — Basics of Python Programming','micro_course','Software Engineering, Programming & Mobile Apps','Beginner',NULL,'120 Hours 00 Minutes',120.00,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(42,132,'Industrial Robotics for Industry 4.0','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Beginner',NULL,'08 Hours 00 Minutes',8.00,5.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(43,309,'3D Design using AUTODESK INVENTOR as easy 1 2 3','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Beginner',NULL,'01 Hours 25 Minutes',1.42,10.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(44,498,'Getting Started with STM32 IoT','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Beginner',NULL,'06 Hours 00 Minutes',6.00,19.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(45,499,'Buoyancy: Float the body, or sink it?','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Beginner',NULL,'08 Hours 00 Minutes',8.00,2.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(46,568,'Control Chart for Beginners in TVET Education','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Beginner',NULL,'08 Hours 00 Minutes',8.00,39.90,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(47,573,'Mudahnya Robot Mudah Alih!','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Beginner',NULL,'08 Hours 00 Minutes',8.00,15.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(48,580,'CAD Modeling: Easy Modeling Vertebrae Bone using MRI Images','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Intermediate',NULL,'08 Hours 00 Minutes',8.00,10.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(49,659,'3D Scanning to 3D Printing','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Beginner',NULL,'08 Hours 00 Minutes',8.00,20.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(50,808,'Visualise Product Through Packaging','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Beginner',NULL,'08 Hours 00 Minutes',8.00,10.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(51,809,'Teknologi dan Kejuruteraan Sistem Kawalan dalam Kehidupan','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Beginner',NULL,'08 Hours 00 Minutes',8.00,20.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(52,412,'The Essentials of Critical and Creative Thinking for Innovation','micro_course','Languages, Communication & Soft Skills','Beginner',NULL,'04 Hours 00 Minutes',4.00,4.99,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(53,182,'Speak Mandarin Using Chinese Phonetics','micro_course','Languages, Communication & Soft Skills','Beginner',NULL,'12 Hours 00 Minutes',12.00,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(54,184,'The Wonders of Chinese Characters','micro_course','Languages, Communication & Soft Skills','Beginner',NULL,'12 Hours 00 Minutes',12.00,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(55,553,'Berani Berucap','micro_course','Languages, Communication & Soft Skills','Beginner',NULL,'42 Hours 00 Minutes',42.00,39.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(56,570,'Beautiful Photo with Smartphone Photography Techniques','micro_course','Languages, Communication & Soft Skills','Beginner',NULL,'04 Hours 00 Minutes',4.00,10.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(57,1776,'MPU21072 Pengenalan Penghayatan Etika dan Peradaban Dalam Acuan Malaysia','micro_course','Languages, Communication & Soft Skills','Beginner',NULL,'120 Hours 00 Minutes',120.00,0.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(58,1513,'Writing Literature Review','micro_course','Languages, Communication & Soft Skills','Beginner',NULL,'07 Hours 00 Minutes',7.00,100.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(59,1770,'Literature Search (FTMK)','micro_course','Languages, Communication & Soft Skills','Beginner',NULL,'06 Hours 00 Minutes',6.00,100.00,'2026-11-04 01:42:39','2026-11-04 01:42:39'),(60,2101,'Cloud Native Architecture & Kubernetes Security','micro_course','Software Engineering, Programming & Mobile Apps','Advanced','BITS3423','40 Hours 00 Minutes',40.00,150.00,'2026-11-05 09:00:00','2026-11-05 09:00:00'),(61,2102,'Industrial IoT Sensor Networks & Edge AI','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Intermediate','MECE5113','40 Hours 00 Minutes',40.00,220.00,'2026-11-05 09:00:00','2026-11-05 09:00:00'),(62,2103,'Strategic Technopreneurship & Venture Creation','micro_course','ODL Master Degree Programmes','Master','MBA5113','40 Hours 00 Minutes',40.00,300.00,'2026-11-05 09:00:00','2026-11-05 09:00:00'),(63,2104,'Natural Language Processing with Large Language Models','micro_course','Artificial Intelligence, Data Science & Machine Learning','Advanced','MAXL5143','40 Hours 00 Minutes',40.00,250.00,'2026-11-05 09:00:00','2026-11-05 09:00:00'),(64,2105,'Smart Grid Power Electronics & Renewable Integration','micro_course','Advanced TVET, Robotics & Mechanical Engineering','Intermediate','BENE2133','40 Hours 00 Minutes',40.00,180.00,'2026-11-05 09:00:00','2026-11-05 09:00:00');
/*!40000 ALTER TABLE `ucreds_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `entra_oid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `institution_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upn` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_on_certificate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locale` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `participant_group` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_entra_oid_unique` (`entra_oid`),
  UNIQUE KEY `users_institution_id_unique` (`institution_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Amir Hakimi','amir.hakimi@learner.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-03-04 01:42:39','2026-11-06 07:02:41',NULL,'B032410001','amir.hakimi@learner.example.test','Amir Hakimi bin Zulkarnain','+6012-3456789','en','active','2026-11-06 07:02:41','working professional'),(2,'Bee Tan','bee.tan@learner.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-05-04 01:42:39','2026-11-05 07:48:39',NULL,'B032410002','bee.tan@learner.example.test','Bee Tan','+6017-2938475','en','active','2026-11-05 07:48:39','working professional'),(3,'Cee Lim','cee.lim@learner.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-06-04 01:42:39','2026-11-05 06:46:21',NULL,'B032410003','cee.lim@learner.example.test','Lim Cee Wei','+6016-8392011','ms','active','2026-11-05 06:46:21','undergraduate'),(4,'Dan Ong','dan.ong@learner.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-06-04 01:42:39','2026-11-05 07:07:49',NULL,'B032410004','dan.ong@learner.example.test',NULL,NULL,'en','active','2026-11-05 07:07:49','undergraduate'),(5,'Elena Gomez','elena.gomez@learner.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-01-04 01:42:39','2026-11-04 01:42:39',NULL,'B032410005','elena.gomez@learner.example.test','Elena Gomez','+6019-4455667','en','deactivated',NULL,'alumni'),(6,'Dr. Aina Zulkifli','aina.zulkifli@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2024-11-04 01:42:39','2026-11-06 07:29:45',NULL,'STF-FTMK-01','aina.zulkifli@utem.example.test','Dr. Aina Zulkifli','+606-2701101','ms','active','2026-11-06 07:29:45',NULL),(7,'Dr. Lim Wei Jie','lim.weijie@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2024-11-04 01:42:39','2026-11-04 01:42:39',NULL,'STF-FTMK-02','lim.weijie@utem.example.test','Dr. Lim Wei Jie','+606-2701102','en','active',NULL,NULL),(8,'Assoc. Prof. Dr. Ahmad Razali','ahmad.razali@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2024-11-04 01:42:39','2026-11-06 07:09:38',NULL,'STF-FTKE-01','ahmad.razali@utem.example.test','Assoc. Prof. Dr. Ahmad Razali','+606-2702101','ms','active','2026-11-06 07:09:38',NULL),(9,'Dr. Norah Hashim','norah.hashim@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2023-11-04 01:42:39','2026-11-04 01:42:39',NULL,'STF-FTMK-03','norah.hashim@utem.example.test',NULL,NULL,'en','deactivated',NULL,NULL),(10,'Hakim Rahman','hakim.rahman@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2023-11-04 01:42:39','2026-11-06 01:16:51',NULL,'DD-FTMK-01','hakim.rahman@utem.example.test','Prof. Madya Dr. Hakim Rahman','+606-2701001','en','active','2026-11-06 01:16:51',NULL),(11,'Prof. Dr. Mastura Badron','mastura.badron@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2023-11-04 01:42:39','2026-11-06 00:38:15',NULL,'DD-FTKE-01','mastura.badron@utem.example.test','Prof. Dr. Mastura Badron','+606-2702001','en','active','2026-11-06 00:38:15',NULL),(12,'Farid Hashim','farid.hashim@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2023-11-04 01:42:39','2026-11-04 01:42:39',NULL,'ADM-CODL-01','farid.hashim@utem.example.test','Farid Hashim','+606-2708001','en','active',NULL,NULL),(13,'Nurul Izzah','nurul.izzah@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2024-11-04 01:42:39','2026-11-04 01:42:39',NULL,'ADM-CODL-02','nurul.izzah@utem.example.test','Nurul Izzah binti Mansor','+606-2708002','ms','active',NULL,NULL),(14,'Zulhilmi Daud','zulhilmi.daud@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2022-11-04 01:42:39','2026-11-04 01:42:39',NULL,'ADM-CODL-03','zulhilmi.daud@utem.example.test',NULL,NULL,'en','deactivated',NULL,NULL),(15,'Ir. Siti Aminah','siti.aminah@industry.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2025-11-04 01:42:39','2026-11-05 08:02:52',NULL,'IND-EXP-01','siti.aminah@industry.example.test','Ir. Siti Aminah binti Othman','+6012-9988776','ms','active','2026-11-05 08:02:52',NULL),(16,'Ts. Jason Lee','jason.lee@industry.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-01-04 01:42:39','2026-11-04 01:42:39',NULL,'IND-EXP-02','jason.lee@industry.example.test','Ts. Jason Lee Kah Wai','+6013-5566778','en','active',NULL,NULL),(17,'Wan Farhana','farhana.wan@industry.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2025-05-04 01:42:39','2026-11-04 01:42:39',NULL,'IND-EXP-03','farhana.wan@industry.example.test',NULL,NULL,'en','deactivated',NULL,NULL),(18,'Tengku Muhammad Iskandar Zulkarnain bin Tengku Abdul Rahman Al-Haj','iskandar.zulkarnain@learner.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-04-10 09:00:00','2026-11-04 08:15:00',NULL,'B032410006','iskandar.zulkarnain@learner.example.test','Tengku Muhammad Iskandar Zulkarnain bin Tengku Abdul Rahman Al-Haj','+6019-2837465','ms','active','2026-11-04 08:15:00','undergraduate'),(19,'Tan Yi','tan.yi@learner.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-05-12 10:30:00','2026-11-03 16:40:00',NULL,'B032410007','tan.yi@learner.example.test','Tan Yi','+6012-8811223','en','active','2026-11-03 16:40:00','working professional'),(20,'Kavitha a/p Subramaniam','kavitha.subra@learner.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-05-20 11:15:00','2026-11-04 06:20:00',NULL,'B032410008','kavitha.subra@learner.example.test','Kavitha a/p Subramaniam','+6016-5544332','en','active','2026-11-04 06:20:00','public'),(21,'Muhammad Faiz bin Kamarudin','faiz.kamarudin@learner.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-06-15 08:45:00','2026-11-02 14:10:00',NULL,'B032410009','faiz.kamarudin@learner.example.test','Muhammad Faiz bin Kamarudin','+6013-7788990','ms','active','2026-11-02 14:10:00','alumni'),(22,'Nur Aisyah Humaira binti Mohd Zaki','aisyah.humaira@learner.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2026-11-04 09:00:00','2026-11-04 09:00:00',NULL,'B032410010','aisyah.humaira@learner.example.test',NULL,NULL,'en','active',NULL,'undergraduate'),(23,'Ir. Ts. Dr. Mohd Hafizuddin bin Othman','hafizuddin.othman@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2024-06-01 08:00:00','2026-11-03 17:30:00',NULL,'STF-FTKM-01','hafizuddin.othman@utem.example.test','Ir. Ts. Dr. Mohd Hafizuddin bin Othman','+606-2703101','ms','active','2026-11-03 17:30:00',NULL),(24,'Assoc. Prof. Ts. Dr. Chong Mei Ling','chong.meiling@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2024-06-01 08:00:00','2026-11-06 00:11:05',NULL,'STF-FAIX-01','chong.meiling@utem.example.test','Assoc. Prof. Ts. Dr. Chong Mei Ling','+606-2707101','en','active','2026-11-06 00:11:05',NULL),(25,'Prof. Ir. Dr. Roslan bin Abd Rahim','roslan.rahim@utem.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2023-01-15 08:00:00','2026-11-06 01:37:46',NULL,'DD-UNIV-01','roslan.rahim@utem.example.test','Prof. Ir. Dr. Roslan bin Abd Rahim','+606-2709000','ms','active','2026-11-06 01:37:46',NULL),(26,'Datuk Ir. Kamarul Azman bin Dato\' Seri Ibrahim','kamarul.azman@industry.example.test',NULL,'$2y$10$t5zx3n38/bkDwKIVrkb.aeG33q3ad4PBthtE.exAGrXTw0f5OrYra',NULL,'2025-03-01 09:00:00','2026-11-01 11:20:00',NULL,'IND-EXP-04','kamarul.azman@industry.example.test',NULL,'+6012-3009988','en','active','2026-11-01 11:20:00',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_log`
--

DROP TABLE IF EXISTS `verification_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verification_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `credential_id` bigint unsigned DEFAULT NULL,
  `outcome` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `via` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `network_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `verification_log_credential_id_created_at_index` (`credential_id`,`created_at`),
  CONSTRAINT `verification_log_credential_id_foreign` FOREIGN KEY (`credential_id`) REFERENCES `credentials` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_log`
--

LOCK TABLES `verification_log` WRITE;
/*!40000 ALTER TABLE `verification_log` DISABLE KEYS */;
INSERT INTO `verification_log` VALUES (1,1,'valid','qr','10.41.164.20','2026-10-05 01:42:39'),(2,1,'valid','web','175.143.12.8','2026-10-25 01:42:39'),(3,2,'corrected','api','202.190.43.1','2026-10-30 01:42:39'),(4,NULL,'not_found','page','10.41.164.20','2026-11-04 04:23:35'),(5,NULL,'not_found','page','10.41.164.20','2026-11-04 04:23:39'),(6,NULL,'not_found','page','10.41.164.20','2026-11-04 04:23:46'),(7,NULL,'not_found','page','10.41.164.20','2026-11-04 04:23:49'),(8,NULL,'not_found','page','127.0.0.1','2026-11-04 07:54:03'),(9,NULL,'not_found','page','127.0.0.1','2026-11-04 07:57:12'),(10,NULL,'not_found','page','127.0.0.1','2026-11-04 07:57:12'),(11,NULL,'not_found','page','127.0.0.1','2026-11-04 07:57:12'),(12,NULL,'not_found','page','127.0.0.1','2026-11-04 08:10:24'),(13,NULL,'not_found','page','127.0.0.1','2026-11-04 08:10:24'),(14,NULL,'not_found','page','127.0.0.1','2026-11-04 08:10:24'),(15,NULL,'not_found','page','127.0.0.1','2026-11-04 08:12:16'),(16,NULL,'not_found','page','127.0.0.1','2026-11-04 08:12:16'),(17,NULL,'not_found','page','127.0.0.1','2026-11-04 08:12:16'),(18,3,'valid','page','202.190.43.12','2026-10-20 11:00:00'),(19,4,'valid','qr','175.143.18.44','2026-09-02 14:20:00'),(20,8,'valid','page','115.135.22.90','2026-10-28 09:15:00'),(21,9,'valid','qr','103.21.55.10','2026-10-29 16:40:00'),(22,10,'revoked','page','203.106.88.14','2026-11-02 10:30:00'),(51,NULL,'valid','page','127.0.0.1','2026-11-06 01:29:58'),(52,NULL,'not_found','page','127.0.0.1','2026-11-06 01:31:25'),(53,NULL,'not_found','page','127.0.0.1','2026-11-06 01:32:32'),(54,NULL,'not_found','page','127.0.0.1','2026-11-06 01:33:40'),(55,NULL,'not_found','page','127.0.0.1','2026-11-06 01:34:28'),(56,NULL,'not_found','page','127.0.0.1','2026-11-06 01:34:33'),(57,NULL,'not_found','page','127.0.0.1','2026-11-06 01:56:24'),(58,NULL,'valid','page','127.0.0.1','2026-11-06 02:14:33'),(59,11,'valid','page','127.0.0.1','2026-11-06 02:14:46'),(60,NULL,'not_found','page','127.0.0.1','2026-11-06 02:14:47'),(61,NULL,'valid','page','127.0.0.1','2026-11-06 02:14:56'),(62,11,'valid','page','127.0.0.1','2026-11-06 02:19:44'),(63,11,'valid','page','127.0.0.1','2026-11-06 02:20:02'),(64,11,'signature_ok','file','127.0.0.1','2026-11-06 02:21:11'),(65,11,'valid','page','127.0.0.1','2026-11-06 02:44:52'),(66,11,'valid','page','127.0.0.1','2026-11-06 02:59:00'),(67,11,'valid','page','127.0.0.1','2026-11-06 03:06:54'),(68,11,'valid','page','127.0.0.1','2026-11-06 03:09:06'),(69,11,'valid','page','127.0.0.1','2026-11-06 03:09:27'),(70,11,'valid','page','127.0.0.1','2026-11-06 03:09:59'),(71,11,'valid','page','127.0.0.1','2026-11-06 03:10:38'),(72,11,'valid','page','127.0.0.1','2026-11-06 03:15:34'),(73,NULL,'not_found','page','127.0.0.1','2026-11-06 03:15:56'),(74,11,'valid','page','127.0.0.1','2026-11-06 03:19:18'),(75,NULL,'not_found','page','127.0.0.1','2026-11-06 03:23:40'),(76,11,'valid','page','127.0.0.1','2026-11-06 03:23:45'),(77,11,'valid','page','127.0.0.1','2026-11-06 04:23:32'),(78,11,'valid','page','127.0.0.1','2026-11-06 04:24:35'),(79,11,'valid','page','127.0.0.1','2026-11-06 04:24:43'),(80,11,'valid','page','10.41.164.20','2026-11-06 04:28:19'),(81,11,'valid','page','10.41.164.20','2026-11-06 04:28:25');
/*!40000 ALTER TABLE `verification_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `version_transition_rules`
--

DROP TABLE IF EXISTS `version_transition_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `version_transition_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mc_version_id` bigint unsigned NOT NULL,
  `from_version_id` bigint unsigned NOT NULL,
  `rule` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `approval_decision_id` bigint unsigned NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `version_transition_rules_mc_version_id_foreign` (`mc_version_id`),
  KEY `version_transition_rules_from_version_id_foreign` (`from_version_id`),
  KEY `version_transition_rules_approval_decision_id_foreign` (`approval_decision_id`),
  CONSTRAINT `version_transition_rules_approval_decision_id_foreign` FOREIGN KEY (`approval_decision_id`) REFERENCES `approval_decisions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `version_transition_rules_from_version_id_foreign` FOREIGN KEY (`from_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `version_transition_rules_mc_version_id_foreign` FOREIGN KEY (`mc_version_id`) REFERENCES `mc_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `version_transition_rules`
--

LOCK TABLES `version_transition_rules` WRITE;
/*!40000 ALTER TABLE `version_transition_rules` DISABLE KEYS */;
INSERT INTO `version_transition_rules` VALUES (1,8,1,'equivalence',10,'Learners who completed Version 1 retain full credit equivalence for credit transfer.','2026-10-04 01:42:39'),(2,8,1,'finish_old',10,'Current enrolled participants in 2026/2 cohort complete under Version 1 rules.','2026-10-04 01:42:39'),(3,27,26,'move_new',18,'Active learners in FTKE PLC v1 cohort may opt into v2 OPC-UA lab module at no extra fee.','2026-10-12 15:05:00'),(4,27,26,'equivalence',18,'Completed v1 certificates remain valid for BENE 2133 credit transfer until 2028.','2026-10-12 15:06:00');
/*!40000 ALTER TABLE `version_transition_rules` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-06  7:47:55
