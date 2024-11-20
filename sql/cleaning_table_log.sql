-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 04, 2024 at 05:08 AM
-- Server version: 8.3.0
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cleaning_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cleaning_table_log`
--

DROP TABLE IF EXISTS `cleaning_table_log`;
CREATE TABLE IF NOT EXISTS `cleaning_table_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action` varchar(10) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `nursing_area` varchar(10) DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL,
  `pc_name` varchar(100) DEFAULT NULL,
  `performed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `operation` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cleaning_table_log`
--

INSERT INTO `cleaning_table_log` (`id`, `action`, `room`, `name`, `status`, `nursing_area`, `modified_date`, `pc_name`, `performed_at`, `operation`) VALUES
(1, 'INSERT', '244', 'ecsed', 'Discharge Recommended', '4A1', '2024-10-04 09:48:50', '10.4.2.51', '2024-10-04 09:48:50', 'insert'),
(2, 'INSERT', '132', 'esfefsef', 'Discharge Recommended', '4A1', '2024-10-04 09:48:57', '10.4.2.51', '2024-10-04 09:48:57', 'insert'),
(3, 'DELETE', '244', NULL, NULL, NULL, NULL, NULL, '2024-10-04 09:50:19', 'delete'),
(4, 'INSERT', '978', 'dvczdv', 'Discharge Recommended', '4A1', '2024-10-04 10:08:41', '10.4.2.51', '2024-10-04 10:08:41', 'insert'),
(5, 'INSERT', '18968', 'vcgbcgb', 'Discharge Recommended', '4A1', '2024-10-04 10:10:51', '10.4.2.51', '2024-10-04 10:10:51', 'insert'),
(6, 'DELETE', '18968', NULL, NULL, NULL, NULL, NULL, '2024-10-04 10:10:56', 'delete'),
(7, 'UPDATE', '108', NULL, 'Discharged', '5A', '2024-10-04 10:14:44', '10.4.2.51', '2024-10-04 10:14:44', 'update'),
(8, 'UPDATE', '108', NULL, 'Room Cleaned', '5A', '2024-10-04 10:14:52', '10.4.2.51', '2024-10-04 10:14:52', 'update'),
(9, 'UPDATE', '978', NULL, 'Room Cleaned', '4A1', '2024-10-04 10:15:39', '10.4.2.51', '2024-10-04 10:15:39', 'update'),
(10, 'UPDATE', '108', NULL, 'Sent for Billing', '5A', '2024-10-04 10:25:22', '10.4.2.51', '2024-10-04 10:25:22', 'update'),
(11, 'UPDATE', '108', 'VASCULAR SURGEON OP', 'Discharged', '5A', '2024-10-04 10:26:36', '10.4.2.51', '2024-10-04 10:26:36', 'update'),
(12, 'INSERT', '123', 'aefswesef', 'Discharge Recommended', '7A', '2024-10-04 10:26:54', '10.4.2.51', '2024-10-04 10:26:54', 'insert'),
(13, 'UPDATE', '123', 'aefswesef', 'Room Cleaned', '7A', '2024-10-04 10:27:15', '10.4.2.51', '2024-10-04 10:27:15', 'update'),
(14, 'UPDATE', '108', 'VASCULAR SURGEON OP', 'Final Billing', '5A', '2024-10-04 10:31:38', '10.4.3.116', '2024-10-04 10:31:38', 'update'),
(15, 'DELETE', '108', NULL, NULL, NULL, NULL, NULL, '2024-10-04 10:31:52', 'delete');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
