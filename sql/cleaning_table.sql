-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 04, 2024 at 05:07 AM
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
-- Table structure for table `cleaning_table`
--

DROP TABLE IF EXISTS `cleaning_table`;
CREATE TABLE IF NOT EXISTS `cleaning_table` (
  `Room` varchar(50) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Status` varchar(50) NOT NULL,
  `Nursing_area` varchar(50) NOT NULL,
  `Modified_Date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `PC_Name` varchar(100) NOT NULL,
  PRIMARY KEY (`Room`,`Modified_Date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cleaning_table`
--

INSERT INTO `cleaning_table` (`Room`, `Name`, `Status`, `Nursing_area`, `Modified_Date`, `PC_Name`) VALUES
('203', 'Tom Hanks', 'Final Billing', '5A', '2023-09-07 18:30:00', 'PC3'),
('102', 'Jane Smith', 'Sent for Billing', '4A2', '2023-09-04 18:30:00', 'PC2'),
('101', 'John Doe', 'Discharge Recommended', '4A1', '2023-08-31 18:30:00', 'PC1'),
('204', 'Alice Johnson', 'Discharged', '6A', '2023-09-11 18:30:00', 'PC4'),
('305', 'Robert Brown', 'Patient Vacated', '7A', '2023-09-14 18:30:00', 'PC5'),
('306', 'Charlie Lee', 'Cleaning in Progress', '4B', '2023-09-17 18:30:00', 'PC6'),
('407', 'Emma Stone', 'Room Cleaned', '5B', '2023-09-21 18:30:00', 'PC7'),
('408', 'Sophia Wilson', 'Discharge Recommended', '6B', '2023-09-24 18:30:00', 'PC8'),
('509', 'Michael Clark', 'Sent for Billing', '7B', '2023-09-27 18:30:00', 'PC9'),
('110', 'Chris Evans', 'Final Billing', '8B', '2023-09-30 18:30:00', 'PC1'),
('111', 'Scarlett Johansson', 'Discharged', '4A1', '2023-10-02 18:30:00', 'PC2'),
('212', 'Mark Ruffalo', 'Patient Vacated', '4A2', '2023-10-03 18:30:00', 'PC3'),
('213', 'Jeremy Renner', 'Cleaning in Progress', '5A', '2023-10-04 18:30:00', 'PC4'),
('314', 'Natalie Portman', 'Room Cleaned', '6A', '2023-10-05 18:30:00', 'PC5'),
('415', 'Tom Holland', 'Discharge Recommended', '7A', '2023-10-06 18:30:00', 'PC6'),
('416', 'Brie Larson', 'Sent for Billing', '4B', '2023-10-07 18:30:00', 'PC7'),
('517', 'Paul Rudd', 'Final Billing', '5B', '2023-10-08 18:30:00', 'PC8'),
('621', 'Robert Downey Jr.', 'Cleaning in Progress', '7B', '2024-10-02 03:55:45', '10.4.2.51'),
('722', 'Chris Hemsworth', 'Cleaning in Progress', '8B', '2024-10-01 18:30:00', 'PC2'),
('823', 'Benedict Cumberbatch', 'Room Cleaned', '4A1', '2024-10-01 18:30:00', 'PC3'),
('824', 'Elizabeth Olsen', 'Discharge Recommended', '4A2', '2024-10-01 18:30:00', 'PC4'),
('725', 'Sebastian Stan', 'Sent for Billing', '5A', '2024-10-01 18:30:00', 'PC5'),
('626', 'Anthony Mackie', 'Final Billing', '6A', '2024-10-01 18:30:00', 'PC6'),
('527', 'Don Cheadle', 'Discharge Recommended', '7A', '2024-10-02 03:55:31', '10.4.2.51'),
('828', 'Tom Hardy', 'Patient Vacated', '4B', '2024-10-01 18:30:00', 'PC8'),
('456', 'VASCULAR SURGEON OP', 'Patient Vacated', '4A1', '2024-10-04 03:58:55', '10.4.2.51'),
('606', 'awdaw', 'Final Billing', '6A', '2024-10-03 07:34:49', '10.4.2.51'),
('123', 'aefswesef', 'Room Cleaned', '7A', '2024-10-04 04:57:15', '10.4.2.51'),
('468', 'awdaw', 'Discharge Recommended', '4A1', '2024-10-04 04:16:10', '10.4.2.51'),
('978', 'dvczdv', 'Room Cleaned', '4A1', '2024-10-04 04:45:39', '10.4.2.51'),
('132', 'esfefsef', 'Sent for Billing', '4A1', '2024-10-04 04:55:04', '10.4.2.51');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- to drop primary key in the table
ALTER TABLE `cleaning_table`
DROP PRIMARY KEY;

-- to add id key as primary key
ALTER TABLE `cleaning_table`
ADD COLUMN `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;

