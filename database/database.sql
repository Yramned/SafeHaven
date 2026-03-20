-- SafeHaven Complete Database Schema
-- Compatible with both localhost (safehaven) and HelioHost (zellpetermiranda_safehaven)
-- Password for all seeded users: "password"

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('evacuee','admin') DEFAULT 'evacuee',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `users` (`id`, `full_name`, `email`, `phone_number`, `address`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@safehaven.com', '+63 912 345 6789', '123 Main Street, Bacolod City', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2024-01-15 10:30:00'),
(2, 'John Doe', 'user@example.com', '+63 923 456 7890', '456 Oak Avenue, Bacolod City', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'evacuee', '2024-01-16 14:20:00'),
(3, 'Maria Santos', 'maria@safehaven.com', '+63 934 567 8901', '789 Pine Street, Bacolod City', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'evacuee', '2024-01-17 09:15:00');

-- --------------------------------------------------------
-- Table: evacuation_centers
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `evacuation_centers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `current_occupancy` int(11) NOT NULL DEFAULT 0,
  `status` enum('accepting','limited','full') DEFAULT 'accepting',
  `contact_number` varchar(20) DEFAULT NULL,
  `facilities` text DEFAULT NULL,
  `warning_pct` decimal(5,2) DEFAULT 75.00,
  `critical_pct` decimal(5,2) DEFAULT 90.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_barangay` (`barangay`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `evacuation_centers` (`id`,`name`,`barangay`,`address`,`latitude`,`longitude`,`capacity`,`current_occupancy`,`status`,`contact_number`,`facilities`) VALUES
(1,'Barangay 18 High School Evacuation Center','Barangay 18','Barangay 18, Bacolod City',10.67640000,122.95360000,250,155,'accepting','+63 912 111 2222','Restrooms, Medical Aid, Kitchen'),
(2,'Barangay Central Gym','Zone 1','Zone 1, Bacolod City',10.68000000,122.95600000,150,120,'limited','+63 912 222 3333','Sports Facilities, Showers'),
(3,'Community Center North','Zone 2','Zone 2, Bacolod City',10.68500000,122.95800000,200,92,'accepting','+63 912 333 4444','Meeting Rooms, Kitchen'),
(4,'Sports Complex East','Zone 3','Zone 3, Bacolod City',10.67800000,122.96000000,300,300,'full','+63 912 444 5555','Basketball Court, Parking'),
(5,'Elementary School West','Zone 4','Zone 4, Bacolod City',10.67500000,122.95200000,180,95,'accepting','+63 912 555 6666','Classrooms, Playground'),
(6,'Barangay Hall South','Zone 5','Zone 5, Bacolod City',10.67200000,122.95400000,120,110,'limited','+63 912 666 7777','Office Space, Parking');

-- --------------------------------------------------------
-- Table: evacuation_requests
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `evacuation_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `center_id` int(11) DEFAULT NULL,
  `confirmation_code` varchar(50) NOT NULL,
  `location_street` varchar(255) DEFAULT NULL,
  `location_barangay` varchar(100) DEFAULT NULL,
  `location_city` varchar(100) DEFAULT NULL,
  `location_latitude` decimal(10,8) DEFAULT NULL,
  `location_longitude` decimal(11,8) DEFAULT NULL,
  `priority` varchar(50) DEFAULT NULL,
  `family_members` int(11) DEFAULT 1,
  `special_needs` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed','denied') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `confirmation_code` (`confirmation_code`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_center_id` (`center_id`),
  KEY `idx_status` (`status`),
  KEY `fk_resolved_by` (`resolved_by`),
  CONSTRAINT `er_fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `er_fk_center` FOREIGN KEY (`center_id`) REFERENCES `evacuation_centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `er_fk_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: capacity_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `capacity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `center_id` int(11) NOT NULL,
  `occupancy` int(11) NOT NULL,
  `capacity` int(11) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_type` enum('check-in','check-out','manual-update','evacuation-request') DEFAULT 'manual-update',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `changed_by` (`changed_by`),
  KEY `idx_center_id` (`center_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `cl_fk_center` FOREIGN KEY (`center_id`) REFERENCES `evacuation_centers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cl_fk_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: messages (contact form)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: alerts (situational alerts)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `severity` enum('critical','evacuation','warning','info') NOT NULL DEFAULT 'info',
  `location` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_severity` (`severity`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_alert_creator` (`created_by`),
  CONSTRAINT `al_fk_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `alerts` (`id`,`title`,`message`,`severity`,`location`,`created_by`,`is_read`) VALUES
(1,'Typhoon Warning Level 2','Typhoon Signal No. 2 is now raised over the eastern portions of Negros Occidental. Residents in low-lying areas should prepare for immediate evacuation.','warning','Eastern Negros Occidental',1,0),
(2,'Flash Flood Alert - Zone 3','Water levels at the river basin have reached critical stage. Residents within 500m of the riverbank must evacuate immediately.','critical','Zone 3, Bacolod City',1,0),
(3,'Evacuation Order - Barangay 18','Mandatory evacuation order issued for Barangay 18 due to rising flood waters. Proceed to nearest evacuation center.','evacuation','Barangay 18',1,0),
(4,'Shelter Advisory','All evacuation centers are now open and ready to receive evacuees. Bring essential documents and medications.','info','All Barangays',1,1);

-- --------------------------------------------------------
-- Views
-- --------------------------------------------------------
DROP VIEW IF EXISTS `v_center_status`;
CREATE VIEW `v_center_status` AS
SELECT ec.id AS center_id, ec.name AS center_name, ec.barangay, ec.capacity AS max_capacity,
  ec.current_occupancy AS inside_now, (ec.capacity - ec.current_occupancy) AS beds_free,
  ROUND(ec.current_occupancy / NULLIF(ec.capacity,0) * 100, 2) AS utilization_pct,
  ec.status, ec.contact_number, ec.facilities, ec.updated_at AS last_updated
FROM evacuation_centers ec ORDER BY utilization_pct DESC;

DROP VIEW IF EXISTS `v_system_summary`;
CREATE VIEW `v_system_summary` AS
SELECT SUM(ec.current_occupancy) AS total_inside, SUM(ec.capacity) AS total_capacity,
  SUM(ec.capacity - ec.current_occupancy) AS total_available,
  ROUND(SUM(ec.current_occupancy) / NULLIF(SUM(ec.capacity),0) * 100, 2) AS occupancy_pct,
  COUNT(0) AS total_centers,
  SUM(ec.status = 'accepting') AS accepting_count,
  SUM(ec.status = 'limited') AS limited_count,
  SUM(ec.status = 'full') AS full_count,
  (SELECT COUNT(0) FROM evacuation_requests WHERE status = 'pending') AS pending_requests
FROM evacuation_centers ec;

DROP VIEW IF EXISTS `v_pending_requests`;
CREATE VIEW `v_pending_requests` AS
SELECT er.id AS request_id, er.confirmation_code, u.full_name AS evacuee_name,
  ec.name AS center_name, er.family_members AS people_count, er.priority,
  er.status, er.created_at AS request_date, er.location_barangay, er.special_needs
FROM evacuation_requests er
JOIN users u ON er.user_id = u.id
JOIN evacuation_centers ec ON er.center_id = ec.id
WHERE er.status = 'pending'
ORDER BY er.created_at ASC;

DROP VIEW IF EXISTS `v_recent_requests`;
CREATE VIEW `v_recent_requests` AS
SELECT er.id AS request_id, er.confirmation_code, u.full_name AS evacuee_name,
  ec.name AS center_name, er.family_members AS people_count, er.priority,
  er.status, er.created_at AS request_date, er.resolved_at, ru.full_name AS resolved_by_name
FROM evacuation_requests er
JOIN users u ON er.user_id = u.id
JOIN evacuation_centers ec ON er.center_id = ec.id
LEFT JOIN users ru ON er.resolved_by = ru.id
WHERE er.status IN ('approved','rejected','denied','completed')
ORDER BY er.resolved_at DESC;

-- --------------------------------------------------------
-- Table: sensor_readings (admin-editable situational sensor data)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sensor_readings` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `sensor_key`  varchar(64)  NOT NULL,
  `label`       varchar(128) NOT NULL,
  `value`       varchar(64)  NOT NULL DEFAULT '0',
  `unit`        varchar(32)  NOT NULL DEFAULT '',
  `trend`       varchar(255) NOT NULL DEFAULT '',
  `status`      enum('ok','warn','critical') NOT NULL DEFAULT 'ok',
  `icon`        varchar(16)  NOT NULL DEFAULT '📡',
  `updated_by`  int(11)      DEFAULT NULL,
  `updated_at`  timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sensor_key` (`sensor_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `sensor_readings` (`sensor_key`,`label`,`value`,`unit`,`trend`,`status`,`icon`) VALUES
('temperature','Temperature','38.4','°C','↑ 2.1° from last hour','critical','🌡️'),
('humidity','Humidity','82','%','↑ 5% from last hour','warn','💧'),
('wind_speed','Wind Speed','14','km/h','↓ 3 km/h from last hour','ok','🌬️'),
('flood_level','Flood Level','2.8','m','↑ 0.4 m – rising','warn','🌊');

COMMIT;
