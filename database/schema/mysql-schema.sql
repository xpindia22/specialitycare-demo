/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `openemr_log_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_oemr_log_id` (`openemr_log_id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admission_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admission_packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `package_id` bigint(20) unsigned NOT NULL,
  `quoted_price` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` enum('active','settled','cancelled') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admission_packages_admission_id_foreign` (`admission_id`),
  KEY `admission_packages_package_id_foreign` (`package_id`),
  KEY `admission_packages_created_by_foreign` (`created_by`),
  CONSTRAINT `admission_packages_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admission_packages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admission_packages_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `billing_packages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ipd_no` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `bed_id` bigint(20) unsigned NOT NULL,
  `ward_id` bigint(20) unsigned NOT NULL,
  `admitting_doctor_id` bigint(20) unsigned NOT NULL,
  `admission_type` enum('emergency','elective','referral') NOT NULL DEFAULT 'elective',
  `admission_date` datetime NOT NULL,
  `provisional_diagnosis` varchar(255) DEFAULT NULL,
  `referred_by` varchar(255) DEFAULT NULL,
  `referral_source_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('admitted','discharged','transferred','absconded') NOT NULL DEFAULT 'admitted',
  `is_mlc` tinyint(1) NOT NULL DEFAULT 0,
  `mlc_no` varchar(255) DEFAULT NULL,
  `mlc_police_station` varchar(255) DEFAULT NULL,
  `mlc_notes` text DEFAULT NULL,
  `discharge_date` datetime DEFAULT NULL,
  `discharge_condition` enum('improved','recovered','lama','expired','transferred') DEFAULT NULL,
  `discharge_notes` text DEFAULT NULL,
  `discharge_summary` text DEFAULT NULL,
  `discharge_instructions` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admissions_ipd_no_unique` (`ipd_no`),
  KEY `admissions_patient_id_foreign` (`patient_id`),
  KEY `admissions_bed_id_foreign` (`bed_id`),
  KEY `admissions_ward_id_foreign` (`ward_id`),
  KEY `admissions_admitting_doctor_id_foreign` (`admitting_doctor_id`),
  KEY `admissions_created_by_foreign` (`created_by`),
  KEY `admissions_referral_source_id_foreign` (`referral_source_id`),
  CONSTRAINT `admissions_admitting_doctor_id_foreign` FOREIGN KEY (`admitting_doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `admissions_bed_id_foreign` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`id`),
  CONSTRAINT `admissions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admissions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admissions_referral_source_id_foreign` FOREIGN KEY (`referral_source_id`) REFERENCES `referral_sources` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admissions_ward_id_foreign` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_no` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `duration` int(11) NOT NULL DEFAULT 15,
  `type` varchar(50) DEFAULT NULL,
  `status` enum('Scheduled','Confirmed','Checked-In','In-Progress','Completed','Cancelled','No-Show','Rescheduled') NOT NULL DEFAULT 'Scheduled',
  `chief_complaint` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rescheduled_from` bigint(20) unsigned DEFAULT NULL,
  `reschedule_reason` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointments_appointment_no_unique` (`appointment_no`),
  KEY `appointments_patient_id_foreign` (`patient_id`),
  KEY `appointments_doctor_id_foreign` (`doctor_id`),
  KEY `appointments_rescheduled_from_foreign` (`rescheduled_from`),
  KEY `appointments_created_by_foreign` (`created_by`),
  KEY `appointments_appointment_date_doctor_id_index` (`appointment_date`,`doctor_id`),
  CONSTRAINT `appointments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `appointments_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `appointments_rescheduled_from_foreign` FOREIGN KEY (`rescheduled_from`) REFERENCES `appointments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `beds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `beds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ward_id` bigint(20) unsigned NOT NULL,
  `bed_number` varchar(255) NOT NULL,
  `type` enum('general','semi_private','private','icu','hdu') NOT NULL DEFAULT 'general',
  `status` enum('available','occupied','reserved','maintenance') NOT NULL DEFAULT 'available',
  `notes` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `beds_ward_id_bed_number_unique` (`ward_id`,`bed_number`),
  CONSTRAINT `beds_ward_id_foreign` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bill_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` bigint(20) unsigned NOT NULL,
  `item_type` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bill_items_bill_id_foreign` (`bill_id`),
  CONSTRAINT `bill_items_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_package_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_package_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `package_id` bigint(20) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_type` varchar(40) NOT NULL DEFAULT 'service',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` decimal(8,2) NOT NULL DEFAULT 1.00,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_included` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `billing_package_items_package_id_foreign` (`package_id`),
  CONSTRAINT `billing_package_items_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `billing_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `package_price` decimal(12,2) NOT NULL,
  `includes_room` tinyint(1) NOT NULL DEFAULT 0,
  `includes_medicines` tinyint(1) NOT NULL DEFAULT 0,
  `includes_investigations` tinyint(1) NOT NULL DEFAULT 0,
  `includes_ot` tinyint(1) NOT NULL DEFAULT 0,
  `max_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_packages_created_by_foreign` (`created_by`),
  CONSTRAINT `billing_packages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bill_no` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `bill_date` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `insurance_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `insurance_company` varchar(255) DEFAULT NULL,
  `tpa_ref` varchar(255) DEFAULT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Unpaid','Partial','Paid','Refunded') NOT NULL DEFAULT 'Unpaid',
  `payment_method` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bills_bill_no_unique` (`bill_no`),
  KEY `bills_patient_id_foreign` (`patient_id`),
  KEY `bills_encounter_id_foreign` (`encounter_id`),
  KEY `bills_created_by_foreign` (`created_by`),
  KEY `bills_bill_date_index` (`bill_date`),
  CONSTRAINT `bills_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `bills_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`),
  CONSTRAINT `bills_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `certificate_no` varchar(20) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned DEFAULT NULL,
  `certificate_type` enum('medical_leave','fitness','accident','medical_examination','age_nativity','attendant','domiciliary') NOT NULL,
  `issued_date` date NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `diagnosis` varchar(255) DEFAULT NULL,
  `rest_from` date DEFAULT NULL,
  `rest_to` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificates_certificate_no_unique` (`certificate_no`),
  KEY `certificates_patient_id_foreign` (`patient_id`),
  KEY `certificates_doctor_id_foreign` (`doctor_id`),
  KEY `certificates_created_by_foreign` (`created_by`),
  CONSTRAINT `certificates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificates_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificates_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `charge_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `charge_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `charge_templates_created_by_foreign` (`created_by`),
  CONSTRAINT `charge_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clinical_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `note_type` varchar(255) NOT NULL DEFAULT 'Progress Note',
  `subjective` text DEFAULT NULL,
  `objective` text DEFAULT NULL,
  `assessment` text DEFAULT NULL,
  `plan` text DEFAULT NULL,
  `note_content` longtext DEFAULT NULL,
  `is_signed` tinyint(1) NOT NULL DEFAULT 0,
  `signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinical_notes_patient_id_foreign` (`patient_id`),
  KEY `clinical_notes_encounter_id_foreign` (`encounter_id`),
  KEY `clinical_notes_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `clinical_notes_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `clinical_notes_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`),
  CONSTRAINT `clinical_notes_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consent_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consent_forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `consent_type` enum('admission','surgical','anaesthesia','procedure','blood_transfusion') NOT NULL,
  `procedure_name` varchar(200) DEFAULT NULL,
  `is_obtained` tinyint(1) NOT NULL DEFAULT 0,
  `patient_or_guardian` varchar(150) DEFAULT NULL,
  `relationship` varchar(80) DEFAULT NULL,
  `witness_name` varchar(150) DEFAULT NULL,
  `obtained_at` timestamp NULL DEFAULT NULL,
  `obtained_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `consent_unique` (`admission_id`,`consent_type`,`procedure_name`),
  KEY `consent_forms_obtained_by_foreign` (`obtained_by`),
  CONSTRAINT `consent_forms_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consent_forms_obtained_by_foreign` FOREIGN KEY (`obtained_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `diagnoses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `diagnoses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `icd_code` varchar(255) DEFAULT NULL,
  `diagnosis_name` varchar(255) NOT NULL,
  `type` enum('Primary','Secondary','Differential') NOT NULL DEFAULT 'Primary',
  `status` enum('Active','Resolved','Chronic') NOT NULL DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diagnoses_encounter_id_foreign` (`encounter_id`),
  CONSTRAINT `diagnoses_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `doctor_id` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `alt_phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `pincode` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `qualification` varchar(255) NOT NULL,
  `registration_no` varchar(255) NOT NULL,
  `experience_years` int(11) NOT NULL DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `consultation_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bio` text DEFAULT NULL,
  `available_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_days`)),
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `slot_duration` int(11) NOT NULL DEFAULT 15,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctors_doctor_id_unique` (`doctor_id`),
  KEY `doctors_user_id_foreign` (`user_id`),
  KEY `doctors_department_id_foreign` (`department_id`),
  CONSTRAINT `doctors_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `doctors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drug_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `drug_interactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `drug1_name` varchar(255) NOT NULL,
  `drug2_name` varchar(255) NOT NULL,
  `severity` enum('minor','moderate','major','contraindicated') NOT NULL DEFAULT 'moderate',
  `description` text NOT NULL,
  `management` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drug_interactions_drug1_name_drug2_name_index` (`drug1_name`,`drug2_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecg_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecg_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `record_no` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `record_date` date NOT NULL,
  `heart_rate` varchar(255) DEFAULT NULL,
  `rhythm` varchar(255) DEFAULT NULL,
  `pr_interval` varchar(255) DEFAULT NULL,
  `qrs_duration` varchar(255) DEFAULT NULL,
  `qt_qtc` varchar(255) DEFAULT NULL,
  `axis` varchar(255) DEFAULT NULL,
  `interpretation` text DEFAULT NULL,
  `ecg_file` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `performed_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecg_records_record_no_unique` (`record_no`),
  KEY `ecg_records_patient_id_foreign` (`patient_id`),
  KEY `ecg_records_encounter_id_foreign` (`encounter_id`),
  KEY `ecg_records_doctor_id_foreign` (`doctor_id`),
  KEY `ecg_records_performed_by_foreign` (`performed_by`),
  CONSTRAINT `ecg_records_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `ecg_records_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`),
  CONSTRAINT `ecg_records_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `ecg_records_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `echo_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `echo_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `record_no` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `record_date` date NOT NULL,
  `diagnosis` varchar(255) DEFAULT NULL,
  `ref_by` varchar(255) DEFAULT NULL,
  `height_cm` varchar(255) DEFAULT NULL,
  `weight_kg` varchar(255) DEFAULT NULL,
  `bsa` varchar(255) DEFAULT NULL,
  `lv_edd` varchar(255) DEFAULT NULL,
  `lv_esd` varchar(255) DEFAULT NULL,
  `epss` varchar(255) DEFAULT NULL,
  `mapse` varchar(255) DEFAULT NULL,
  `ef_percent` varchar(255) DEFAULT NULL,
  `fs_percent` varchar(255) DEFAULT NULL,
  `ea_ratio` varchar(255) DEFAULT NULL,
  `lap_ee` varchar(255) DEFAULT NULL,
  `pap` varchar(255) DEFAULT NULL,
  `lvpw` varchar(255) DEFAULT NULL,
  `ivs` varchar(255) DEFAULT NULL,
  `la_size` varchar(255) DEFAULT NULL,
  `ao_root` varchar(255) DEFAULT NULL,
  `rv_dimension` varchar(255) DEFAULT NULL,
  `rv_wall_thickness` varchar(255) DEFAULT NULL,
  `tapse` varchar(255) DEFAULT NULL,
  `ivc_size` varchar(255) DEFAULT NULL,
  `mitral_valve` text DEFAULT NULL,
  `aortic_valve` text DEFAULT NULL,
  `tricuspid_valve` text DEFAULT NULL,
  `pulmonary_valve` text DEFAULT NULL,
  `impression` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `echo_file` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `performed_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lv_diastolic_function` varchar(255) DEFAULT NULL,
  `lv_systolic_function` varchar(255) DEFAULT NULL,
  `ivs_motion` varchar(255) DEFAULT NULL,
  `pericardium_status` varchar(255) DEFAULT NULL,
  `rv_longitudinal_function` varchar(255) DEFAULT NULL,
  `rv_transverse_function` varchar(255) DEFAULT NULL,
  `rv_paradoxical_movement` varchar(255) DEFAULT NULL,
  `ias_status` varchar(255) DEFAULT NULL,
  `pericardial_effusion` varchar(255) DEFAULT NULL,
  `mv_velocity` varchar(255) DEFAULT NULL,
  `mv_gradient` varchar(255) DEFAULT NULL,
  `av_velocity` varchar(255) DEFAULT NULL,
  `av_gradient` varchar(255) DEFAULT NULL,
  `tv_velocity` varchar(255) DEFAULT NULL,
  `tv_gradient` varchar(255) DEFAULT NULL,
  `pv_velocity` varchar(255) DEFAULT NULL,
  `pv_gradient` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `echo_records_record_no_unique` (`record_no`),
  KEY `echo_records_patient_id_foreign` (`patient_id`),
  KEY `echo_records_encounter_id_foreign` (`encounter_id`),
  KEY `echo_records_doctor_id_foreign` (`doctor_id`),
  KEY `echo_records_performed_by_foreign` (`performed_by`),
  CONSTRAINT `echo_records_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `echo_records_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`),
  CONSTRAINT `echo_records_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `echo_records_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `encounter_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `report_type` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `source_system` varchar(255) DEFAULT NULL,
  `source_id` varchar(255) DEFAULT NULL,
  `source_url` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `encounter_reports_source_system_source_id_unique` (`source_system`,`source_id`),
  KEY `encounter_reports_uploaded_by_foreign` (`uploaded_by`),
  KEY `encounter_reports_encounter_id_created_at_index` (`encounter_id`,`created_at`),
  KEY `encounter_reports_patient_id_created_at_index` (`patient_id`,`created_at`),
  CONSTRAINT `encounter_reports_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_reports_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `encounter_reports_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `encounter_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounter_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `chief_complaint` text DEFAULT NULL,
  `clinical_notes` text DEFAULT NULL,
  `diagnoses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`diagnoses`)),
  `prescription_template` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prescription_template`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_templates_created_by_foreign` (`created_by`),
  CONSTRAINT `encounter_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `encounters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `encounters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_no` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `opd_token_id` bigint(20) unsigned DEFAULT NULL,
  `encounter_date` date NOT NULL,
  `encounter_time` time NOT NULL,
  `visit_type` enum('New Visit','Revisit','Emergency','Follow-up','Review') NOT NULL DEFAULT 'New Visit',
  `status` enum('Open','Completed','Cancelled') NOT NULL DEFAULT 'Open',
  `is_mlc` tinyint(1) NOT NULL DEFAULT 0,
  `mlc_no` varchar(255) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,2) DEFAULT NULL,
  `bp_systolic` varchar(255) DEFAULT NULL,
  `bp_diastolic` varchar(255) DEFAULT NULL,
  `pulse` decimal(5,1) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `spo2` decimal(4,1) DEFAULT NULL,
  `respiratory_rate` decimal(4,1) DEFAULT NULL,
  `blood_glucose` varchar(255) DEFAULT NULL,
  `chief_complaint` text DEFAULT NULL,
  `history_of_present_illness` text DEFAULT NULL,
  `past_medical_history` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `social_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `review_of_systems` text DEFAULT NULL,
  `examination_findings` text DEFAULT NULL,
  `impression` text DEFAULT NULL,
  `plan` text DEFAULT NULL,
  `follow_up_instructions` text DEFAULT NULL,
  `follow_up_days` int(11) DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `encounters_encounter_no_unique` (`encounter_no`),
  KEY `encounters_patient_id_foreign` (`patient_id`),
  KEY `encounters_doctor_id_foreign` (`doctor_id`),
  KEY `encounters_appointment_id_foreign` (`appointment_id`),
  KEY `encounters_created_by_foreign` (`created_by`),
  KEY `encounters_opd_token_id_foreign` (`opd_token_id`),
  CONSTRAINT `encounters_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  CONSTRAINT `encounters_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `encounters_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `encounters_opd_token_id_foreign` FOREIGN KEY (`opd_token_id`) REFERENCES `opd_tokens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `encounters_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `icd_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `icd_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `icd_codes_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `insurance_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurance_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `insurer_id` bigint(20) unsigned NOT NULL,
  `claim_no` varchar(80) DEFAULT NULL,
  `policy_no` varchar(80) DEFAULT NULL,
  `member_id` varchar(80) DEFAULT NULL,
  `policy_holder_name` varchar(255) DEFAULT NULL,
  `policy_expiry` date DEFAULT NULL,
  `sum_insured` decimal(12,2) DEFAULT NULL,
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `settled_amount` decimal(12,2) DEFAULT NULL,
  `co_pay_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','submitted','query_raised','approved','partially_approved','settled','rejected','cancelled') NOT NULL DEFAULT 'draft',
  `submitted_date` date DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `settled_date` date DEFAULT NULL,
  `diagnosis_icd` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `insurance_claims_patient_id_foreign` (`patient_id`),
  KEY `insurance_claims_created_by_foreign` (`created_by`),
  KEY `insurance_claims_admission_id_index` (`admission_id`),
  KEY `insurance_claims_insurer_id_status_index` (`insurer_id`,`status`),
  CONSTRAINT `insurance_claims_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `insurance_claims_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `insurance_claims_insurer_id_foreign` FOREIGN KEY (`insurer_id`) REFERENCES `insurers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `insurance_claims_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `insurance_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurance_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `claim_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(80) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `insurance_documents_claim_id_foreign` (`claim_id`),
  KEY `insurance_documents_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `insurance_documents_claim_id_foreign` FOREIGN KEY (`claim_id`) REFERENCES `insurance_claims` (`id`) ON DELETE CASCADE,
  CONSTRAINT `insurance_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `insurers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'insurer',
  `code` varchar(50) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gst_no` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `insurers_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `io_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `io_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `io_type` enum('input','output') NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount_ml` smallint(5) unsigned NOT NULL,
  `remarks` varchar(300) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `io_entries_recorded_by_foreign` (`recorded_by`),
  KEY `io_entries_admission_id_recorded_at_index` (`admission_id`,`recorded_at`),
  CONSTRAINT `io_entries_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `io_entries_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipd_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_charges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `charge_date` date NOT NULL,
  `category` enum('room_rent','nursing_charge','doctor_visit','procedure','investigation','pharmacy','oxygen','diet','other') NOT NULL,
  `description` varchar(255) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` decimal(8,2) NOT NULL DEFAULT 1.00,
  `amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_charges_admission_id_foreign` (`admission_id`),
  KEY `ipd_charges_created_by_foreign` (`created_by`),
  CONSTRAINT `ipd_charges_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_charges_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipd_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_deposits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','upi','cheque','other') NOT NULL DEFAULT 'cash',
  `transaction_ref` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_deposits_admission_id_foreign` (`admission_id`),
  KEY `ipd_deposits_received_by_foreign` (`received_by`),
  CONSTRAINT `ipd_deposits_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_deposits_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipd_med_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_med_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned NOT NULL,
  `log_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `status` enum('given','missed','held','refused') NOT NULL DEFAULT 'given',
  `actual_time` time DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_med_logs_prescription_id_foreign` (`prescription_id`),
  KEY `ipd_med_logs_admission_id_foreign` (`admission_id`),
  KEY `ipd_med_logs_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `ipd_med_logs_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_med_logs_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `ipd_prescriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_med_logs_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipd_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `note_type` enum('doctor_round','nursing','observation','procedure','dietary','physiotherapy') NOT NULL,
  `noted_at` datetime NOT NULL,
  `note` text NOT NULL,
  `written_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_notes_admission_id_foreign` (`admission_id`),
  KEY `ipd_notes_written_by_foreign` (`written_by`),
  CONSTRAINT `ipd_notes_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_notes_written_by_foreign` FOREIGN KEY (`written_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipd_prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_prescriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `drug_name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `medicine_id` bigint(20) unsigned DEFAULT NULL,
  `dose` varchar(255) NOT NULL,
  `route` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `schedule_times` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schedule_times`)),
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `instructions` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `prescribed_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_prescriptions_admission_id_foreign` (`admission_id`),
  KEY `ipd_prescriptions_prescribed_by_foreign` (`prescribed_by`),
  KEY `ipd_prescriptions_medicine_id_foreign` (`medicine_id`),
  CONSTRAINT `ipd_prescriptions_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_prescriptions_medicine_id_foreign` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ipd_prescriptions_prescribed_by_foreign` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipd_vitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipd_vitals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `recorded_at` datetime NOT NULL,
  `temperature` decimal(5,1) DEFAULT NULL,
  `pulse` tinyint(3) unsigned DEFAULT NULL,
  `respiration_rate` tinyint(3) unsigned DEFAULT NULL,
  `bp_systolic` smallint(5) unsigned DEFAULT NULL,
  `bp_diastolic` smallint(5) unsigned DEFAULT NULL,
  `spo2` tinyint(3) unsigned DEFAULT NULL,
  `weight` decimal(5,1) DEFAULT NULL,
  `blood_glucose` decimal(6,1) DEFAULT NULL,
  `urine_output` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ipd_vitals_admission_id_foreign` (`admission_id`),
  KEY `ipd_vitals_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `ipd_vitals_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ipd_vitals_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_categories_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lab_order_id` bigint(20) unsigned NOT NULL,
  `lab_test_id` bigint(20) unsigned DEFAULT NULL,
  `lab_panel_id` bigint(20) unsigned DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('Ordered','Collected','Processing','Completed') NOT NULL DEFAULT 'Ordered',
  `result_value` varchar(255) DEFAULT NULL,
  `result_status` varchar(255) NOT NULL DEFAULT 'Pending',
  `abnormal_flag` varchar(20) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `result_entered_at` timestamp NULL DEFAULT NULL,
  `result_entered_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_order_items_lab_order_id_foreign` (`lab_order_id`),
  KEY `lab_order_items_lab_test_id_foreign` (`lab_test_id`),
  KEY `lab_order_items_lab_panel_id_foreign` (`lab_panel_id`),
  KEY `lab_order_items_result_entered_by_foreign` (`result_entered_by`),
  CONSTRAINT `lab_order_items_lab_order_id_foreign` FOREIGN KEY (`lab_order_id`) REFERENCES `lab_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_order_items_lab_panel_id_foreign` FOREIGN KEY (`lab_panel_id`) REFERENCES `lab_panels` (`id`),
  CONSTRAINT `lab_order_items_lab_test_id_foreign` FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests` (`id`),
  CONSTRAINT `lab_order_items_result_entered_by_foreign` FOREIGN KEY (`result_entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `admission_id` bigint(20) unsigned DEFAULT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `ordered_date` date NOT NULL,
  `status` enum('Ordered','Collected','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Ordered',
  `clinical_info` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_orders_order_no_unique` (`order_no`),
  KEY `lab_orders_patient_id_foreign` (`patient_id`),
  KEY `lab_orders_encounter_id_foreign` (`encounter_id`),
  KEY `lab_orders_doctor_id_foreign` (`doctor_id`),
  KEY `lab_orders_created_by_foreign` (`created_by`),
  KEY `lab_orders_admission_id_foreign` (`admission_id`),
  CONSTRAINT `lab_orders_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lab_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `lab_orders_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `lab_orders_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`),
  CONSTRAINT `lab_orders_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_panel_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_panel_tests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lab_panel_id` bigint(20) unsigned NOT NULL,
  `lab_test_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_panel_tests_lab_panel_id_foreign` (`lab_panel_id`),
  KEY `lab_panel_tests_lab_test_id_foreign` (`lab_test_id`),
  CONSTRAINT `lab_panel_tests_lab_panel_id_foreign` FOREIGN KEY (`lab_panel_id`) REFERENCES `lab_panels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_panel_tests_lab_test_id_foreign` FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_panels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_panels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `turnaround_hours` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lab_order_item_id` bigint(20) unsigned NOT NULL,
  `lab_test_id` bigint(20) unsigned NOT NULL,
  `parameter_name` varchar(255) NOT NULL,
  `result_value` varchar(255) NOT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `normal_range` varchar(255) DEFAULT NULL,
  `flag` varchar(30) NOT NULL DEFAULT 'Normal',
  `comments` text DEFAULT NULL,
  `reported_by` bigint(20) unsigned DEFAULT NULL,
  `reported_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_results_lab_order_item_id_foreign` (`lab_order_item_id`),
  KEY `lab_results_lab_test_id_foreign` (`lab_test_id`),
  KEY `lab_results_reported_by_foreign` (`reported_by`),
  CONSTRAINT `lab_results_lab_order_item_id_foreign` FOREIGN KEY (`lab_order_item_id`) REFERENCES `lab_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lab_results_lab_test_id_foreign` FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests` (`id`),
  CONSTRAINT `lab_results_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lab_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_tests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lab_category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sample_type` varchar(255) DEFAULT NULL,
  `normal_range` varchar(255) DEFAULT NULL,
  `normal_value` varchar(100) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `turnaround_hours` int(11) DEFAULT NULL,
  `instructions` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lab_tests_lab_category_id_foreign` (`lab_category_id`),
  CONSTRAINT `lab_tests_lab_category_id_foreign` FOREIGN KEY (`lab_category_id`) REFERENCES `lab_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medicine_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `medicine_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medicines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `medicines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `medicine_category_id` bigint(20) unsigned DEFAULT NULL,
  `dosage_form` varchar(255) NOT NULL,
  `strength` varchar(255) DEFAULT NULL,
  `unit` varchar(255) NOT NULL DEFAULT 'Tab',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `indications` text DEFAULT NULL,
  `side_effects` text DEFAULT NULL,
  `contraindications` text DEFAULT NULL,
  `drug_interactions` text DEFAULT NULL,
  `dosage_instructions` text DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medicines_medicine_category_id_foreign` (`medicine_category_id`),
  CONSTRAINT `medicines_medicine_category_id_foreign` FOREIGN KEY (`medicine_category_id`) REFERENCES `medicine_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notes_created_by_foreign` (`created_by`),
  CONSTRAINT `notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nursing_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admission_id` bigint(20) unsigned NOT NULL,
  `shift_date` date NOT NULL,
  `shift` enum('morning','evening','night') NOT NULL,
  `patient_condition` varchar(100) DEFAULT NULL,
  `assessment` text DEFAULT NULL,
  `care_given` text DEFAULT NULL,
  `medications_given` text DEFAULT NULL,
  `iv_lines` text DEFAULT NULL,
  `pending_tasks` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `written_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nursing_shifts_admission_id_shift_date_shift_unique` (`admission_id`,`shift_date`,`shift`),
  KEY `nursing_shifts_written_by_foreign` (`written_by`),
  CONSTRAINT `nursing_shifts_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nursing_shifts_written_by_foreign` FOREIGN KEY (`written_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opd_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `opd_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token_no` smallint(5) unsigned NOT NULL,
  `token_date` date NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('waiting','called','in_consultation','done','skipped') NOT NULL DEFAULT 'waiting',
  `priority` enum('normal','urgent') NOT NULL DEFAULT 'normal',
  `purpose` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `is_triaged` tinyint(1) NOT NULL DEFAULT 0,
  `called_at` timestamp NULL DEFAULT NULL,
  `consultation_started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `opd_tokens_token_no_token_date_unique` (`token_no`,`token_date`),
  KEY `opd_tokens_patient_id_foreign` (`patient_id`),
  KEY `opd_tokens_doctor_id_foreign` (`doctor_id`),
  KEY `opd_tokens_created_by_foreign` (`created_by`),
  CONSTRAINT `opd_tokens_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opd_tokens_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opd_tokens_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `openemr_import_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `openemr_import_map` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(60) NOT NULL COMMENT 'e.g. patient, encounter, lab_order, bill',
  `openemr_id` varchar(80) NOT NULL COMMENT 'Primary key in the OpenEMR database',
  `sc_id` bigint(20) unsigned NOT NULL COMMENT 'Primary key in SpecialityCare',
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_entity_openemr` (`entity`,`openemr_id`),
  KEY `openemr_import_map_sc_id_index` (`sc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `operations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `operation_no` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned DEFAULT NULL,
  `surgeon_id` bigint(20) unsigned NOT NULL,
  `anesthetist_name` varchar(255) DEFAULT NULL,
  `operation_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `procedure_name` varchar(255) NOT NULL,
  `icd_code` varchar(255) DEFAULT NULL,
  `diagnosis` varchar(255) NOT NULL,
  `anesthesia_type` enum('general','spinal','epidural','local','regional','none') NOT NULL DEFAULT 'general',
  `pre_op_diagnosis` text DEFAULT NULL,
  `post_op_diagnosis` text DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `complications` text DEFAULT NULL,
  `outcome` enum('successful','complicated','abandoned','patient_expired') NOT NULL DEFAULT 'successful',
  `scrub_nurse` varchar(255) DEFAULT NULL,
  `circulating_nurse` varchar(255) DEFAULT NULL,
  `blood_transfused` tinyint(1) NOT NULL DEFAULT 0,
  `blood_units` tinyint(4) NOT NULL DEFAULT 0,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `operations_operation_no_unique` (`operation_no`),
  KEY `operations_patient_id_foreign` (`patient_id`),
  KEY `operations_admission_id_foreign` (`admission_id`),
  KEY `operations_surgeon_id_foreign` (`surgeon_id`),
  KEY `operations_created_by_foreign` (`created_by`),
  CONSTRAINT `operations_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `operations_surgeon_id_foreign` FOREIGN KEY (`surgeon_id`) REFERENCES `doctors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_allergies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_allergies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `allergen` varchar(255) NOT NULL,
  `reaction` varchar(255) DEFAULT NULL,
  `severity` enum('mild','moderate','severe','fatal','unknown') DEFAULT 'unknown',
  `status` enum('active','inactive','confirmed','suspected') DEFAULT 'confirmed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `openemr_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_openemr_id` (`openemr_id`),
  KEY `idx_patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_insurance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_insurance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `type` enum('primary','secondary','tertiary') DEFAULT 'primary',
  `provider` varchar(255) DEFAULT NULL,
  `plan_name` varchar(255) DEFAULT NULL,
  `policy_number` varchar(255) DEFAULT NULL,
  `group_number` varchar(255) DEFAULT NULL,
  `subscriber_name` varchar(255) DEFAULT NULL,
  `subscriber_dob` date DEFAULT NULL,
  `subscriber_relationship` varchar(100) DEFAULT NULL,
  `copay` varchar(50) DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `openemr_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_openemr_id` (`openemr_id`),
  KEY `idx_patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `noted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `openemr_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_openemr_id` (`openemr_id`),
  KEY `idx_patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patient_vitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_vitals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `recorded_at` datetime NOT NULL,
  `bp_systolic` smallint(5) unsigned DEFAULT NULL,
  `bp_diastolic` smallint(5) unsigned DEFAULT NULL,
  `pulse` smallint(5) unsigned DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `spo2` decimal(5,2) DEFAULT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `height` decimal(6,2) DEFAULT NULL,
  `respiration` smallint(5) unsigned DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `openemr_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_openemr_id` (`openemr_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_encounter_id` (`encounter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `jhc_id` varchar(255) NOT NULL,
  `openemr_pid` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `gender` enum('Male','Female','Other','Unknown') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `blood_group` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `alt_phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `pincode` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'India',
  `aadhar_no` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(255) DEFAULT NULL,
  `emergency_contact_relation` varchar(255) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `marital_status` varchar(255) DEFAULT NULL,
  `religion` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) NOT NULL DEFAULT 'Indian',
  `referred_by` varchar(255) DEFAULT NULL,
  `insurance_company` varchar(255) DEFAULT NULL,
  `policy_no` varchar(255) DEFAULT NULL,
  `tpa_name` varchar(255) DEFAULT NULL,
  `insurance_valid_until` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `referral_source_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patients_jhc_id_unique` (`jhc_id`),
  UNIQUE KEY `patients_openemr_pid_unique` (`openemr_pid`),
  KEY `patients_jhc_id_index` (`jhc_id`),
  KEY `patients_phone_index` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `transaction_ref` varchar(255) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `received_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_bill_id_foreign` (`bill_id`),
  KEY `payments_received_by_foreign` (`received_by`),
  CONSTRAINT `payments_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`),
  CONSTRAINT `payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pharmacy_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pharmacy_stock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medicine_id` bigint(20) unsigned NOT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) NOT NULL DEFAULT 10,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `added_by` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pharmacy_stock_medicine_id_foreign` (`medicine_id`),
  KEY `pharmacy_stock_added_by_foreign` (`added_by`),
  CONSTRAINT `pharmacy_stock_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`),
  CONSTRAINT `pharmacy_stock_medicine_id_foreign` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prescription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescription_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prescription_id` bigint(20) unsigned NOT NULL,
  `medicine_id` bigint(20) unsigned NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `dose` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `route` varchar(255) NOT NULL DEFAULT 'Oral',
  `duration_days` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `instructions` text DEFAULT NULL,
  `is_dispensed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prescription_items_prescription_id_foreign` (`prescription_id`),
  KEY `prescription_items_medicine_id_foreign` (`medicine_id`),
  CONSTRAINT `prescription_items_medicine_id_foreign` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  CONSTRAINT `prescription_items_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prescriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prescription_no` varchar(255) NOT NULL,
  `encounter_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `prescribed_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `advice` text DEFAULT NULL,
  `status` enum('Pending','Dispensed','Partially Dispensed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prescriptions_prescription_no_unique` (`prescription_no`),
  KEY `prescriptions_encounter_id_foreign` (`encounter_id`),
  KEY `prescriptions_patient_id_foreign` (`patient_id`),
  KEY `prescriptions_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `prescriptions_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `prescriptions_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `radiology_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `radiology_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(255) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned DEFAULT NULL,
  `encounter_id` bigint(20) unsigned DEFAULT NULL,
  `ordered_by` bigint(20) unsigned NOT NULL,
  `modality` enum('xray','ct','mri','usg','mammography','dexa','other') NOT NULL DEFAULT 'xray',
  `body_part` varchar(255) NOT NULL,
  `clinical_indication` text DEFAULT NULL,
  `urgency` enum('routine','urgent','emergency') NOT NULL DEFAULT 'routine',
  `status` enum('ordered','scheduled','completed','reported') NOT NULL DEFAULT 'ordered',
  `scheduled_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `impression` text DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `reported_by` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `radiology_orders_order_no_unique` (`order_no`),
  KEY `radiology_orders_patient_id_foreign` (`patient_id`),
  KEY `radiology_orders_admission_id_foreign` (`admission_id`),
  KEY `radiology_orders_encounter_id_foreign` (`encounter_id`),
  KEY `radiology_orders_ordered_by_foreign` (`ordered_by`),
  KEY `radiology_orders_created_by_foreign` (`created_by`),
  CONSTRAINT `radiology_orders_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_orders_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `radiology_orders_ordered_by_foreign` FOREIGN KEY (`ordered_by`) REFERENCES `doctors` (`id`),
  CONSTRAINT `radiology_orders_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referral_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `referral_source_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `admission_id` bigint(20) unsigned DEFAULT NULL,
  `bill_id` bigint(20) unsigned DEFAULT NULL,
  `calculated_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','partial','paid') NOT NULL DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer','cheque','upi') DEFAULT NULL,
  `transaction_ref` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `referral_payments_referral_source_id_foreign` (`referral_source_id`),
  KEY `referral_payments_patient_id_foreign` (`patient_id`),
  KEY `referral_payments_admission_id_foreign` (`admission_id`),
  KEY `referral_payments_bill_id_foreign` (`bill_id`),
  KEY `referral_payments_created_by_foreign` (`created_by`),
  CONSTRAINT `referral_payments_admission_id_foreign` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `referral_payments_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `referral_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `referral_payments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referral_payments_referral_source_id_foreign` FOREIGN KEY (`referral_source_id`) REFERENCES `referral_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referral_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('doctor','hospital','clinic','nurse','other') NOT NULL DEFAULT 'doctor',
  `type_other` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `rate_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `rate_value` decimal(8,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_charges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 0,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `staff_id` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `alt_phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `pincode` varchar(255) DEFAULT NULL,
  `designation` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `joining_date` date NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `aadhar_no` varchar(255) DEFAULT NULL,
  `pan_no` varchar(255) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_staff_id_unique` (`staff_id`),
  KEY `staff_user_id_foreign` (`user_id`),
  CONSTRAINT `staff_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medicine_id` bigint(20) unsigned NOT NULL,
  `pharmacy_stock_id` bigint(20) unsigned DEFAULT NULL,
  `prescription_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL DEFAULT 0,
  `batch_number` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transactions_medicine_id_foreign` (`medicine_id`),
  KEY `stock_transactions_pharmacy_stock_id_foreign` (`pharmacy_stock_id`),
  KEY `stock_transactions_user_id_foreign` (`user_id`),
  CONSTRAINT `stock_transactions_medicine_id_foreign` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  CONSTRAINT `stock_transactions_pharmacy_stock_id_foreign` FOREIGN KEY (`pharmacy_stock_id`) REFERENCES `pharmacy_stock` (`id`),
  CONSTRAINT `stock_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `triage_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `triage_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `opd_token_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `triage_level` enum('green','yellow','red') NOT NULL DEFAULT 'green',
  `chief_complaint` varchar(255) DEFAULT NULL,
  `bp_systolic` smallint(5) unsigned DEFAULT NULL,
  `bp_diastolic` smallint(5) unsigned DEFAULT NULL,
  `pulse` smallint(5) unsigned DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `spo2` decimal(4,1) DEFAULT NULL,
  `weight` decimal(5,1) DEFAULT NULL,
  `height` decimal(5,1) DEFAULT NULL,
  `bmi` decimal(4,1) DEFAULT NULL,
  `blood_glucose` varchar(255) DEFAULT NULL,
  `nurse_notes` text DEFAULT NULL,
  `assessed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `triage_records_opd_token_id_foreign` (`opd_token_id`),
  KEY `triage_records_patient_id_foreign` (`patient_id`),
  KEY `triage_records_assessed_by_foreign` (`assessed_by`),
  CONSTRAINT `triage_records_assessed_by_foreign` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `triage_records_opd_token_id_foreign` FOREIGN KEY (`opd_token_id`) REFERENCES `opd_tokens` (`id`) ON DELETE CASCADE,
  CONSTRAINT `triage_records_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('general','semi_private','private','icu','hdu','emergency') NOT NULL DEFAULT 'general',
  `floor` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

/*M!999999\- enable the sandbox mode */ 
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_01_01_000001_create_specialitycare_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_04_29_000001_create_encounter_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_04_29_000002_add_source_fields_to_encounter_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2024_01_02_000001_create_openemr_import_map_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_04_30_000003_add_result_fields_to_lab_order_items_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_04_30_000004_expand_lab_result_flags',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_05_02_042912_add_extended_fields_to_echo_records_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_05_02_100001_create_notes_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_05_02_100002_create_activity_logs_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_05_02_200001_expand_user_roles',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_05_02_300001_create_opd_tokens_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_05_02_300002_create_triage_records_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_05_02_300099_add_opd_token_id_to_encounters',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_05_02_400001_create_wards_and_beds_tables',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_05_02_400002_create_admissions_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_05_02_500001_create_ipd_notes_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_05_02_500002_create_ipd_charges_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_05_02_500003_create_ipd_medications_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_05_02_500004_seed_room_rent_charges',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_05_03_100001_add_admission_id_to_lab_orders',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_05_02_235100_create_referral_sources_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_05_02_235110_create_ipd_deposits_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_05_02_235120_create_insurance_fields',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_05_02_235157_add_mlc_fields',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_05_02_235157_create_radiology_orders_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_05_02_235158_create_operations_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_05_02_235200_add_referral_source_id_to_patients',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_05_02_235300_create_referral_payments_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_05_03_200001_add_referral_source_id_to_admissions',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_05_03_210001_update_referral_source_type_enum',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_05_03_000001_add_medicine_id_to_ipd_prescriptions',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_05_03_300001_add_normal_value_to_lab_tests',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_05_03_400001_create_certificates_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_05_03_500001_add_allergy_photo_to_patients',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_05_03_500002_add_discharge_instructions_to_admissions',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_05_03_500003_create_charge_templates_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_05_03_500004_create_encounter_templates_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_05_03_500005_create_icd_codes_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_05_03_600001_create_io_entries_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_05_03_600002_create_nursing_shifts_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_05_03_600003_create_consent_forms_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_05_03_700001_create_insurance_tpa_tables',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_05_03_800001_create_billing_packages_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_05_04_100001_create_drug_interactions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_05_04_200001_add_generic_name_to_prescriptions',25);
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
