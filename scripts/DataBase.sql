-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.43 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.11.0.7065
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for rentfinder_sl
CREATE DATABASE IF NOT EXISTS `rentfinder_sl` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `rentfinder_sl`;

-- Dumping structure for table rentfinder_sl.otp_verification
CREATE TABLE IF NOT EXISTS `otp_verification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_code` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table rentfinder_sl.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rental_agreement_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('rent','deposit','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('card','bank_transfer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `rental_agreement_id` (`rental_agreement_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`rental_agreement_id`) REFERENCES `rental_agreements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table rentfinder_sl.payment_cards
CREATE TABLE IF NOT EXISTS `payment_cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `card_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_four_digits` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_month` int NOT NULL,
  `expiry_year` int NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `payment_cards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table rentfinder_sl.properties
CREATE TABLE IF NOT EXISTS `properties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `owner_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `property_type` enum('apartment','house','villa','room') COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `bedrooms` int DEFAULT '0',
  `bathrooms` int DEFAULT '0',
  `area_sqft` int DEFAULT '0',
  `amenities` text COLLATE utf8mb4_unicode_ci,
  `images` json DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table rentfinder_sl.property_visits
CREATE TABLE IF NOT EXISTS `property_visits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `property_id` int NOT NULL,
  `tenant_id` int NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `property_visits_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `property_visits_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table rentfinder_sl.rental_agreements
CREATE TABLE IF NOT EXISTS `rental_agreements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `property_id` int NOT NULL,
  `tenant_id` int NOT NULL,
  `owner_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) DEFAULT '0.00',
  `status` enum('active','expired','terminated') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `rental_agreements_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rental_agreements_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rental_agreements_ibfk_3` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table rentfinder_sl.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` enum('tenant','owner','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'tenant',
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
