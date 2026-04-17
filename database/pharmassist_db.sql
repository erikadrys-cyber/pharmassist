-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 15, 2026 at 03:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pharmassist_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_messages`
--

CREATE TABLE `admin_messages` (
  `message_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `subject` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `super_admin_reply` mediumtext DEFAULT NULL,
  `status` enum('new','replied','closed') DEFAULT 'new',
  `replied_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `replied_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_messages`
--

INSERT INTO `admin_messages` (`message_id`, `admin_id`, `name`, `email`, `subject`, `message`, `super_admin_reply`, `status`, `replied_by`, `created_at`, `replied_at`, `updated_at`, `branch_id`) VALUES
(1, 33, 'Admin', 'a.pharmasee@gmail.com', 'Low Stock Concern', 'Good day,\r\n\r\nI would like to report that some medicines in Branch 2 are already running low, which may affect our operations. I suggest improving stock monitoring and alerts to avoid shortages.\r\n\r\nThank you for your time.\r\n\r\nRespectfully,\r\nPatricia Santos\r\nPharmacist / Branch Manager', 'Good day,\r\n\r\nThank you for bringing this to my attention. I will review the current stock monitoring system and coordinate with the team to improve alerts and replenishment processes.\r\n\r\nBest regards,\r\nVivian Azcona', 'replied', 31, '2026-04-14 01:31:40', '2026-04-14 01:43:13', '2026-04-14 01:43:13', 2),
(8, 32, 'Romi Valdez', 'pawfectlyyourss@gmail.com', 'Request for Additional Medicine Stock', 'Good day! We are running low on Salbutamol Nebule and Procaterol Syrup at Ro-Eful Pharmacy. Requesting approval for restocking.', NULL, 'new', NULL, '2026-04-10 09:00:00', NULL, NULL, 1),
(9, 33, 'Sarah Smith', 'e.croochets@gmail.com', 'Staff Schedule Conflict - April', 'Hi, there seems to be a scheduling conflict for our pharmacy assistants during the Holy Week break. Please advise on how to proceed.', 'Thank you for flagging this. Please coordinate with HR and submit an adjusted schedule by April 15.', 'replied', 31, '2026-04-11 10:30:00', '2026-04-11 14:00:00', '2026-04-11 14:00:00', 2);

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `branch_address` varchar(255) NOT NULL,
  `access_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `branch_address`, `access_id`) VALUES
(1, 'Ro-Eful Pharmacy', '560 A. Tagaytay St. Kaloocan City', 32),
(2, 'The Generics Pharmacy', '2520 J. Posadas St., Brgy. 902 zone 100 district VI 1009 Manila City', 33);

-- --------------------------------------------------------

--
-- Table structure for table `branch_inventory`
--

CREATE TABLE `branch_inventory` (
  `inventory_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `stocks_status` enum('available','low stock','out of stock') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch_inventory`
--

INSERT INTO `branch_inventory` (`inventory_id`, `medicine_id`, `stock_quantity`, `stocks_status`) VALUES
(1, 1, 10, 'out of stock'),
(2, 2, 5, 'out of stock'),
(3, 3, 5, 'out of stock'),
(4, 4, 15, 'out of stock'),
(0, 43, 10, 'out of stock'),
(0, 44, 10, 'out of stock'),
(0, 45, 100, ''),
(0, 46, 150, ''),
(0, 47, 125, ''),
(0, 48, 155, ''),
(1, 1, 10, 'out of stock'),
(2, 2, 5, 'out of stock'),
(3, 3, 5, 'out of stock'),
(4, 4, 15, 'out of stock'),
(0, 43, 10, 'out of stock'),
(0, 44, 10, 'out of stock'),
(0, 45, 100, ''),
(0, 46, 150, ''),
(0, 47, 125, ''),
(0, 48, 155, '');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `user_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `subject` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','replied','closed') NOT NULL DEFAULT 'new',
  `admin_reply` mediumtext DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`user_id`, `name`, `email`, `subject`, `message`, `status`, `admin_reply`, `replied_by`, `replied_at`, `created_at`, `updated_at`, `branch_id`) VALUES
(1, 'shofia', 'mslmagpayo@tip.edu.ph', 'Medicine', 'Hi', 'new', NULL, NULL, NULL, '2025-10-24 11:13:16', '2025-10-24 11:13:16', NULL),
(12, 'hceae', 'erikadrys@gmail.com', 'QUESTION ABOUT MEDICINE', 'try', 'replied', 'Hello! Based on your question, .......', 11, '2025-10-27 16:15:07', '2025-10-27 16:06:27', '2025-10-27 16:15:07', NULL),
(13, 'hceae', 'erikadrys@gmail.com', 'TEST EMAIL', 'test', 'replied', 'test reply', 11, '2025-10-28 00:34:16', '2025-10-28 00:18:43', '2025-10-28 00:34:16', NULL),
(14, 'e.drys', 'erikadrys@gmail.com', 'TEST EMAIL', 'blablabla', 'new', NULL, NULL, NULL, '2026-04-13 19:57:24', '2026-04-13 19:57:24', NULL),
(15, 'e.drys', 'a.pharmasee@gmail.com', 'TEST EMAIL', 'sdada', 'new', NULL, NULL, NULL, '2026-04-13 19:59:05', '2026-04-13 19:59:05', NULL),
(16, 'Ruo Zeya', 'rosesarewhat13@gmail.com', 'Prescription Inquiry for Levocitirizine', 'Hi,\r\n\r\nI\'m writing because I haven\'t been able to find [Medication Name] at any of your locations. One of your colleagues mentioned it might be a wider supply issue.\r\n\r\nSince it seems to be unavailable everywhere, could you let me know if there is a similar version or a different dosage currently in stock that I could ask my doctor to prescribe instead? I want to make sure I have an option ready before I call my clinic.\r\n\r\nThank you for you help!', 'new', NULL, NULL, NULL, '2026-04-14 00:54:44', '2026-04-14 00:54:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_codes`
--

CREATE TABLE `email_verification_codes` (
  `code_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification_codes`
--

INSERT INTO `email_verification_codes` (`code_id`, `user_id`, `code`, `is_used`, `expires_at`, `created_at`) VALUES
(10, 30, '195873', 0, '2026-04-13 02:03:05', '2026-04-12 17:53:05'),
(11, 30, '270901', 0, '2026-04-13 02:10:24', '2026-04-12 18:00:24'),
(12, 30, '484929', 1, '2026-04-13 02:14:45', '2026-04-12 18:04:45'),
(13, 31, '436386', 1, '2026-04-13 02:34:20', '2026-04-12 18:24:20'),
(14, 32, '624018', 1, '2026-04-13 03:29:01', '2026-04-12 19:19:01'),
(15, 33, '022629', 1, '2026-04-13 03:56:29', '2026-04-12 19:46:29'),
(17, 39, '152496', 1, '2026-04-13 20:11:34', '2026-04-13 12:01:34'),
(18, 40, '723222', 1, '2026-04-13 21:42:38', '2026-04-13 13:32:38'),
(20, 42, '449304', 1, '2026-04-13 22:58:44', '2026-04-13 14:48:44'),
(21, 43, '842524', 1, '2026-04-14 00:36:03', '2026-04-13 16:26:03'),
(22, 44, '609503', 1, '2026-04-14 00:37:22', '2026-04-13 16:27:22'),
(23, 45, '066897', 0, '2026-04-14 00:38:56', '2026-04-13 16:28:56'),
(24, 46, '506378', 0, '2026-04-14 00:40:30', '2026-04-13 16:30:30'),
(25, 47, '591038', 1, '2026-04-14 00:48:59', '2026-04-13 16:38:59'),
(26, 48, '122524', 1, '2026-04-14 01:29:35', '2026-04-13 17:19:35'),
(27, 49, '539595', 1, '2026-04-14 01:31:20', '2026-04-13 17:21:20'),
(28, 50, '192217', 0, '2026-04-14 23:57:28', '2026-04-14 15:47:28');

-- --------------------------------------------------------

--
-- Table structure for table `id_verification_log`
--

CREATE TABLE `id_verification_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL,
  `rejection_reason` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `id_verification_log`
--

INSERT INTO `id_verification_log` (`log_id`, `user_id`, `admin_id`, `status`, `rejection_reason`, `reviewed_at`, `created_at`) VALUES
(1, 30, 31, 'approved', NULL, '2026-04-13 03:22:25', '2026-04-12 19:22:25'),
(2, 30, 31, 'approved', NULL, '2026-04-13 03:33:10', '2026-04-12 19:33:10'),
(3, 31, 31, 'approved', NULL, '2026-04-13 03:33:32', '2026-04-12 19:33:32'),
(4, 32, 31, 'approved', NULL, '2026-04-13 03:34:16', '2026-04-12 19:34:16'),
(5, 33, 31, 'approved', NULL, '2026-04-13 03:47:34', '2026-04-12 19:47:34'),
(6, 40, 31, 'approved', NULL, '2026-04-13 21:37:25', '2026-04-13 13:37:25'),
(8, 42, 31, 'approved', NULL, '2026-04-13 22:50:17', '2026-04-13 14:50:17'),
(10, 39, 31, 'approved', NULL, '2026-04-13 22:54:46', '2026-04-13 14:54:46'),
(11, 43, 31, 'approved', NULL, '2026-04-14 00:42:11', '2026-04-13 16:42:11'),
(12, 49, 31, 'approved', NULL, '2026-04-14 01:22:05', '2026-04-13 17:22:05'),
(13, 48, 31, 'approved', NULL, '2026-04-14 01:22:16', '2026-04-13 17:22:16'),
(14, 44, 31, 'approved', NULL, '2026-04-14 04:27:13', '2026-04-13 20:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `medicine`
--

CREATE TABLE `medicine` (
  `medicine_id` int(11) NOT NULL,
  `medicine_name` varchar(100) NOT NULL,
  `batch_no` varchar(50) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `branch` varchar(100) NOT NULL,
  `prescription_required` enum('yes','no') NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `expiration_date` date DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `branch_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine`
--

INSERT INTO `medicine` (`medicine_id`, `medicine_name`, `batch_no`, `category`, `price`, `branch`, `prescription_required`, `quantity`, `expiration_date`, `image_path`, `created_at`, `branch_id`) VALUES
(43, 'Amoxicillin', 'asdfhg', 'Antibiotics', 10.00, 'Ro-Eful Pharmacy', 'no', 10, '2026-06-06', 'uploads/1776187968_1759727010_amoxicillin.jpg', '2026-04-14 17:32:48', 1),
(44, 'Neozep', 'dasdfaf', 'Antibiotics', 7.00, 'Ro-Eful Pharmacy', 'yes', 10, '2026-05-09', 'uploads/1776188012_1759721715_neozep.png', '2026-04-14 17:33:32', 1),
(45, 'Mefenamic Acid  Capsule 500mg', '018BKN', 'Pain Relievers', 3.00, 'The Generics Pharmacy', 'yes', 100, '2028-10-31', 'uploads/1776190994_Mefenamic Acid.png', '2026-04-14 18:23:14', 2),
(46, 'Losartan Tablet 50mg', '25L32', 'Hypertension', 4.75, 'The Generics Pharmacy', 'yes', 150, '2028-12-31', 'uploads/1776191402_Losartan.png', '2026-04-14 18:30:02', 2),
(47, 'Ibuprofen Advil 200mg', '25ADV035', 'Pain Relievers', 9.00, 'The Generics Pharmacy', 'no', 125, '2028-03-31', 'uploads/1776191638_Advil.png', '2026-04-14 18:33:58', 2),
(48, 'Telmisartan Tab 80mg', 'M-187020', 'Hypertension', 15.00, 'The Generics Pharmacy', 'yes', 155, '2028-08-31', 'uploads/1776191965_Telmisartan.png', '2026-04-14 18:39:25', 2);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `type` enum('customer_action','admin_action') NOT NULL,
  `message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `reset_token_expiration` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `reset_token`, `reset_token_expiration`, `created_at`) VALUES
(26, 30, '0c59c53a45ab6bebbadfa3c5324b329912386c89e76a89884a0204d90d886a558c1013a016432ba9fb2923a7e90f609fa17a', '2026-04-13 13:40:12', '2026-04-13 19:35:12'),
(27, 30, '18b5929b8ca427ddf195ab8024047ca1f746f40e14372ac0bf3c364be14f24e78aabb2192324d5927ce74f7b1787082a345c', '2026-04-13 13:44:52', '2026-04-13 19:39:52');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `medicine` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `contact_no` varchar(20) NOT NULL,
  `prescription` varchar(255) DEFAULT NULL,
  `discount` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `date_reserved` datetime NOT NULL DEFAULT current_timestamp(),
  `time_slot` varchar(50) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `code` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `branch_id` int(11) DEFAULT NULL,
  `id_upload` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `first_name`, `last_name`, `medicine`, `price`, `quantity`, `notes`, `contact_no`, `prescription`, `discount`, `email`, `date_reserved`, `time_slot`, `status`, `code`, `remarks`, `is_deleted`, `created_at`, `branch_id`, `id_upload`) VALUES
(1, 40, 'Ruo', 'Zeya', 'AlumMagSimChewTab (KREMIL-S)', 9.75, 2, '', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 02:51:37', '0', 'Approved', 'RES-2026-001', NULL, 0, '2026-04-13 18:51:37', 1, NULL),
(2, 40, 'Ruo', 'Zeya', 'Acetylcystne Efferv Tab 600mg (FLUIMUCIL)', 38.75, 1, '', '09760687737', 'PRESC_1776106297_69dd3b39842d6.jpg', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 02:51:37', '0', 'Pending', 'RES-2026-945821D4', NULL, 0, '2026-04-13 18:51:37', 1, NULL),
(3, 40, 'Ruo', 'Zeya', 'Amlodipine Tab 5mg (CARDIOVASC)', 5.75, 1, '', '09760687737', 'PRESC_1776106297_69dd3b3985442.jpg', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 02:51:37', '0', 'Pending', 'RES-2026-945821D4', NULL, 0, '2026-04-13 18:51:37', 1, NULL),
(4, 40, 'Ruo', 'Zeya', 'BIOGESIC 250MG SYR 60ML ORANGE', 140.57, 20, '', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 02:53:47', '0', 'Approved', 'RES-2026-928C96BE', NULL, 0, '2026-04-13 18:53:47', 2, NULL),
(5, 40, 'Ruo', 'Zeya', 'CATAPRES TAB 75MCG', 23.15, 1, '', '09760687737', 'PRESC_1776106427_69dd3bbb500ac.jpg', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 02:53:47', '0', 'Approved', 'RES-2026-928C96BE', NULL, 0, '2026-04-13 18:53:47', 2, NULL),
(6, 40, 'Ruo', 'Zeya', 'DOLFENAL 500MG TAB', 33.96, 1, '', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 02:53:47', '0', 'Approved', 'RES-2026-928C96BE', NULL, 0, '2026-04-13 18:53:47', 2, NULL),
(7, 40, 'Ruo', 'Zeya', 'AlumMagSimChewTab (KREMIL-S)', 9.75, 10, '', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 02:54:49', '0', 'Pending', 'RES-2026-007', NULL, 0, '2026-04-13 18:54:49', 1, NULL),
(8, 43, 'Belle', 'Eya', 'NEOBLOC 100MG TAB', 6.92, 10, 'Will get at exactly 3pm. Thanks', '09760687737', 'PRESC_1776106828_69dd3d4c407b9.jpg', NULL, 'belle.02345@gmail.com', '2026-04-14 03:00:28', '0', 'Pending', 'RES-2026-AD509624', NULL, 0, '2026-04-13 19:00:28', 2, NULL),
(9, 43, 'Belle', 'Eya', 'NASATAPP SYR 60ML', 108.05, 1, 'Will get at exactly 3pm. Thanks', '09760687737', 'PRESC_1776106828_69dd3d4c44c41.jpg', NULL, 'belle.02345@gmail.com', '2026-04-14 03:00:28', '0', 'Pending', 'RES-2026-AD509624', NULL, 0, '2026-04-13 19:00:28', 2, NULL),
(10, 43, 'Belle', 'Eya', 'NASATAPP DROPS 15ML', 106.29, 10, 'Will get at exactly 3pm. Thanks', '09760687737', 'PRESC_1776106828_69dd3d4c472c4.jpg', NULL, 'belle.02345@gmail.com', '2026-04-14 03:00:28', '0', 'Pending', 'RES-2026-AD509624', NULL, 0, '2026-04-13 19:00:28', 2, NULL),
(11, 40, 'Ruo', 'Zeya', 'ADVIL LIQUIDGEL CAP', 7.89, 1, 'Will get at afternn', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 03:41:09', '0', 'Pending', 'RES-2026-B8E8C675', NULL, 0, '2026-04-13 19:41:09', 2, NULL),
(12, 40, 'Ruo', 'Zeya', 'BIOGESIC 250MG SYR 60ML ORANGE', 140.57, 10, 'Will get at afternn', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 03:41:09', '0', 'Pending', 'RES-2026-B8E8C675', NULL, 0, '2026-04-13 19:41:09', 2, NULL),
(13, 40, 'Ruo', 'Zeya', 'DOLFENAL 500MG TAB', 33.96, 1, 'Will get at afternn', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 03:41:09', '0', 'Pending', 'RES-2026-B8E8C675', NULL, 0, '2026-04-13 19:41:09', 2, NULL),
(14, 44, 'Erika', 'Xiiera', 'Salbutamol Tab 4mg (TGP)', 1.50, 100, 'Please put it in a plastic.', '09760687737', 'PRESC_1776112184_69dd52385904c.jpg', NULL, 'xiierika@gmail.com', '2026-04-14 04:29:44', '0', 'Pending', 'RES-2026-A8B09F3E', NULL, 0, '2026-04-13 20:29:44', 1, NULL),
(15, 44, 'Erika', 'Xiiera', 'Carbocias Syr 200mg/120ml (SOLMUX)', 192.00, 10, 'Please put it in a plastic.', '09760687737', '', NULL, 'xiierika@gmail.com', '2026-04-14 04:29:44', '0', 'Pending', 'RES-2026-A8B09F3E', NULL, 0, '2026-04-13 20:29:44', 1, NULL),
(16, 44, 'Erika', 'Xiiera', 'SKELAN 550MG TAB', 19.13, 100, 'Please put it in the proper box.', '09760687737', 'PRESC_1776112298_69dd52aa2090a.jpg', NULL, 'xiierika@gmail.com', '2026-04-14 04:31:38', '0', 'Approved', 'RES-2026-CC3323F6', NULL, 0, '2026-04-13 20:31:38', 2, NULL),
(17, 40, 'Ruo', 'Zeya', 'IBUPROFEN SOFTGEL (ADVIL)', 8.75, 1, '', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 12:23:14', '0', 'Pending', 'RES-2026-BFF4F73E', NULL, 0, '2026-04-14 04:23:14', 2, NULL),
(18, 40, 'Ruo', 'Zeya', 'AlumMagSimChewTab (KREMIL-S)', 9.75, 1, '', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 13:14:14', '0', 'Pending', 'RES-2026-FF7CB5BE', NULL, 0, '2026-04-14 05:14:14', 1, NULL),
(19, 40, 'Ruo', 'Zeya', 'BIOGESIC 250MG SYR 60ML ORANGE', 140.57, 1, '', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 13:14:14', '0', 'Pending', 'RES-2026-FF7CB5BE', NULL, 0, '2026-04-14 05:14:14', 2, NULL),
(20, 40, 'Ruo', 'Zeya', 'Salbutamol Tab 4mg (TGP)', 1.50, 10, '', '09760687737', 'PRESC_1776143654_69ddcd262f31d.jpg', NULL, 'rosesarewhat13@gmail.com', '2026-04-14 13:14:14', '0', 'Pending', 'RES-2026-FF7CB5BE', NULL, 0, '2026-04-14 05:14:14', 1, NULL),
(21, 40, 'Ruo', 'Zeya', 'AlumMagSimChewTab (KREMIL-S)', 9.75, 1, '', '09760687737', '', NULL, 'rosesarewhat13@gmail.com', '2026-04-15 01:31:21', '0', 'Pending', 'RES-2026-DC09FD80', NULL, 0, '2026-04-14 17:31:21', 1, NULL),
(22, 40, 'Ruo', 'Zeya', 'Neozep', 7.00, 1, '', '09760687737', 'PRESC_1776189453_69de800d8928b.pdf', 'none', 'rosesarewhat13@gmail.com', '2026-04-15 01:57:33', '0', 'Pending', 'RES-2026-4BDFAF3A', NULL, 0, '2026-04-14 17:57:33', 1, ''),
(23, 30, 'Erika', 'Ablola', 'Ibuprofen Advil 200mg', 9.00, 200, '', '09614929303', '', 'none', 'erikadrys@gmail.com', '2026-04-15 03:41:14', '0', 'Pending', 'RES-2026-3FE0C2C5', NULL, 0, '2026-04-14 19:41:14', 2, ''),
(24, 30, 'Erika', 'Ablola', 'Losartan Tablet 50mg', 4.75, 1, '', '09614929303', 'PRESC_1776195674_69de985ac3bdf.jpg', 'none', 'erikadrys@gmail.com', '2026-04-15 03:41:14', '0', 'Pending', 'RES-2026-3FE0C2C5', NULL, 0, '2026-04-14 19:41:14', 2, '');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `shift_id` int(11) NOT NULL,
  `shift_name` varchar(100) NOT NULL,
  `shift_label` varchar(100) DEFAULT NULL,
  `shift_start_display` varchar(20) DEFAULT NULL,
  `shift_time_start` time NOT NULL,
  `shift_time_end` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`shift_id`, `shift_name`, `shift_label`, `shift_start_display`, `shift_time_start`, `shift_time_end`, `is_active`, `created_at`) VALUES
(1, 'Morning', NULL, NULL, '06:00:00', '14:00:00', 1, '2026-04-11 09:23:06'),
(2, 'Afternoon', NULL, NULL, '14:00:00', '22:00:00', 1, '2026-04-11 09:23:06'),
(3, 'Night', NULL, NULL, '22:00:00', '06:00:00', 1, '2026-04-11 09:23:06');

-- --------------------------------------------------------

--
-- Table structure for table `staff_members`
--

CREATE TABLE `staff_members` (
  `staff_id` int(11) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_members`
--

INSERT INTO `staff_members` (`staff_id`, `position`, `email`, `phone`, `hire_date`, `is_active`, `created_at`, `updated_at`, `user_id`, `first_name`, `last_name`, `branch_id`) VALUES
(1, 'Pharmacist', 'maria.santos@gmail.com', '09171234567', '2024-01-15', 1, '2026-04-11 08:54:41', '2026-04-13 10:16:49', 32, 'Maria', 'Santos', 1),
(2, 'Pharmacy Assistant', 'juan.delacruz@gmail.com', '09171234568', '2024-02-20', 1, '2026-04-11 08:54:41', '2026-04-13 10:16:56', 36, 'Juan', 'Dela Cruz', 1),
(3, 'Pharmacy Technician', 'rosette.garcia@gmail.com', '09171234569', '2024-03-10', 1, '2026-04-11 08:54:41', '2026-04-13 10:17:06', 37, 'Rosette', 'Garcia', 1),
(4, 'Pharmacy Assistant', 'carlos.reyes@gmail.com', '09171234570', '2024-01-25', 1, '2026-04-11 08:54:41', '2026-04-13 10:17:23', NULL, 'Carlos', 'Reyes', 1),
(5, 'Pharmacist', 'angela.torres@gmail.com', '09171234571', '2024-02-15', 1, '2026-04-11 08:54:41', '2026-04-13 10:17:32', NULL, 'Angela', 'Torres', 1),
(6, 'Pharmacy Technician', 'michel.lim@gmail.com', '09171234572', '2024-03-05', 1, '2026-04-11 08:54:41', '2026-04-13 10:17:40', NULL, 'Michel', 'Lim', 1),
(7, 'Pharmacist', 'patricia.santos@tgp.com', '09171234573', '2024-02-01', 1, '2026-04-13 09:40:29', '2026-04-13 09:40:29', NULL, 'Patricia', 'Santos', 2),
(8, 'Pharmacy Assistant', 'miguel.cruz@tgp.com', '09171234574', '2024-02-15', 1, '2026-04-13 09:40:29', '2026-04-13 09:40:29', NULL, 'Miguel', 'Cruz', 2),
(9, 'Pharmacy Assistant', 'aurora.reyes@tgp.com', '09171234575', '2024-03-01', 1, '2026-04-13 09:40:29', '2026-04-13 09:40:29', NULL, 'Aurora', 'Reyes', 2),
(10, 'Pharmacy Technician', 'daniel.torres@tgp.com', '09171234576', '2024-02-20', 1, '2026-04-13 09:40:29', '2026-04-13 09:40:29', NULL, 'Daniel', 'Torres', 2),
(11, 'Pharmacy Technician', 'sofia.mendoza@tgp.com', '09171234577', '2024-03-10', 1, '2026-04-13 09:40:29', '2026-04-13 09:40:29', NULL, 'Sofia', 'Mendoza', 2);

-- --------------------------------------------------------

--
-- Table structure for table `staff_shifts`
--

CREATE TABLE `staff_shifts` (
  `shift_assignment_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `status` enum('pending','active','completed') DEFAULT 'pending',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_shifts`
--

INSERT INTO `staff_shifts` (`shift_assignment_id`, `staff_id`, `shift_id`, `assigned_date`, `assigned_by`, `check_in_time`, `check_out_time`, `status`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 5, 3, '0000-00-00', 11, NULL, NULL, 'pending', 0, '2026-04-11 14:02:24', '2026-04-11 14:05:25'),
(2, 4, 3, '0000-00-00', 11, NULL, NULL, 'pending', 0, '2026-04-11 14:02:30', '2026-04-11 14:05:23'),
(3, 6, 3, '0000-00-00', 11, NULL, NULL, 'pending', 0, '2026-04-11 14:02:36', '2026-04-11 14:05:19'),
(7, 5, 3, '2026-04-11', 11, '2026-04-12 00:27:24', '2026-04-12 00:27:35', 'completed', 1, '2026-04-11 14:16:49', '2026-04-11 16:27:35'),
(8, 4, 3, '2026-04-11', 11, '2026-04-12 00:26:46', NULL, 'active', 1, '2026-04-11 14:17:24', '2026-04-11 16:26:46'),
(10, 6, 3, '2026-04-11', 32, NULL, NULL, 'pending', 1, '2026-04-12 21:05:23', '2026-04-12 21:05:23'),
(11, 6, 1, '2026-04-11', 32, NULL, NULL, 'pending', 1, '2026-04-12 21:05:47', '2026-04-12 21:05:47'),
(12, 7, 1, '2026-04-15', 33, NULL, NULL, 'pending', 1, '2026-04-13 17:08:40', '2026-04-13 17:08:40'),
(13, 9, 1, '2026-04-15', 33, NULL, NULL, 'pending', 1, '2026-04-13 17:09:08', '2026-04-13 17:09:08'),
(14, 11, 1, '2026-04-15', 33, NULL, NULL, 'pending', 1, '2026-04-13 17:09:20', '2026-04-13 17:09:20');

-- --------------------------------------------------------

--
-- Table structure for table `stock_alerts`
--

CREATE TABLE `stock_alerts` (
  `alert_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `medicine_name` varchar(100) NOT NULL,
  `current_stock` int(11) NOT NULL,
  `min_threshold` int(11) DEFAULT 20,
  `alert_type` enum('low_stock','critical_stock','out_of_stock') DEFAULT 'low_stock',
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('manager1','manager2','p_assistant1','p_assistant2','p_technician1','p_technician2','ceo','customer') DEFAULT 'customer',
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `id_photo` varchar(255) DEFAULT NULL,
  `id_verified` enum('pending','approved','rejected') DEFAULT 'pending',
  `id_rejected_reason` text DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 0,
  `id_verification_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `role`, `first_name`, `last_name`, `username`, `id_photo`, `id_verified`, `id_rejected_reason`, `email_verified`, `is_active`, `id_verification_status`, `branch_id`) VALUES
(30, 'erikadrys@gmail.com', '$2y$10$uqNNjblJ21KWLevm.yMxee68N6Sg2OvcATMLmTv1mCbF5/qFrRT/y', 'customer', 'Erika', 'Ablola', 'e.drys', 'id_1776016385_69dbdc01adba6.jpg', 'pending', NULL, 1, 0, 'approved', NULL),
(31, 'a.pharmasee@gmail.com', '$2y$10$ei6/3rngNzyIMYkK94jXxOFA5Pz5RerYVL2mngCtOMeKrsYMzJlJG', 'ceo', 'Vivian', 'Azcona', 'v.azcona', 'id_1776018260_69dbe354c59d9.jpg', 'pending', NULL, 1, 0, 'approved', NULL),
(32, 'pawfectlyyourss@gmail.com', '$2y$10$VYCrAEda9GHHM0uPVQcQwOUGQIOxUKKwRJRQ0G1UKKAImeYll5sz6', 'manager1', 'Romi', 'Valdez', 'roeful', 'id_1776021541_69dbf02513190.jpg', 'pending', NULL, 1, 0, 'approved', 1),
(33, 'e.croochets@gmail.com', '$2y$10$YeReeyG/0L2wEAHoud3q3u344sQ4Wma79WJd8D2nd8KWUqOnhJp4a', 'manager2', 'Sarah', 'Smith', 'tgp.novaliches', 'id_1776023189_69dbf695b971d.jpg', 'pending', NULL, 1, 0, 'approved', 2),
(34, 'rph.assistant@pharmacy.local', '$2y$10$6wby.BRBt62KMdHO8/NfyeeqbXb9jtOXtTibMPmol/jjFX.hZz5UO', 'p_assistant1', 'RPH', 'Assistant', 'rph.assistant', NULL, 'pending', NULL, 1, 1, 'approved', 1),
(35, 'rph.technician@pharmacy.local', '$2y$10$hsOUiDpRYu5AJOvIsu9b8.6IJLFxVJnNZqYJ0ai.LNpQAqkyWkzSO', 'p_technician1', 'RPH', 'Technician', 'rph.technician', NULL, 'pending', NULL, 1, 1, 'approved', 1),
(36, 'tgp.assistant@pharmacy.local', '$2y$10$fWGDp0wuD9Ec4dIF0X31Lu2DvkxVphmkt/b/qEU4YOMLWmuYxjTna', 'p_assistant2', 'TGP', 'Assistant', 'tgp.assistant', NULL, 'pending', NULL, 1, 1, 'approved', 2),
(37, 'tgp.technician@pharmacy.local', '$2y$10$mtFMapwFDrEW4simwdexiuQnPGxg3dK748UuxuDvSl2.saFqCAIgC', 'p_technician2', 'TGP', 'Technician', 'tgp.technician', NULL, 'pending', NULL, 1, 1, 'approved', 2),
(39, 'cascayok@gmail.com', '$2y$10$2vkLgW1.6myvA.HLEieL0.X.meEVSxkViTmrB.nLcHv8.GbvTglta', 'customer', 'Karl', 'Cascayo', 'krl.ward', 'id_1776081694_69dcdb1e7f00e.jpg', 'pending', NULL, 1, 0, 'approved', NULL),
(40, 'rosesarewhat13@gmail.com', '$2y$10$kmn6LifzzMP/6D0o2.xbHOoVDPgB.EQwXsMSvlocyvM4127Gp5MRW', 'customer', 'Ruo', 'Zeya', 'rcoas', 'id_1776087158_69dcf0769d60f.jpg', 'pending', NULL, 1, 0, 'approved', NULL),
(42, 'reyesjanellerenee083@gmail.com', '$2y$10$BMIaKlLRJecdfS/3c84ABupqQDWLzYVeXXz.7.8O/tHTAhd9R8hze', 'customer', 'Jane', 'Doe', 'd.jane', 'id_1776091723_69dd024bf2a1a.jpg', 'pending', NULL, 1, 0, 'approved', NULL),
(43, 'belle.02345@gmail.com', '$2y$10$z4mqKlyKAszQjeYuoHK1zeLHGdWwkRvdwFv3U64o5PQzk2cVGO0/K', 'customer', 'Belle', 'Anne', 'belle02345', 'id_1776097563_69dd191bb6edf.jpg', 'pending', NULL, 1, 0, 'approved', NULL),
(44, 'xiierika@gmail.com', '$2y$10$Ya9NxE2eXiHyo6yf7B2vn.uWIjaSQE1co2sNYfSeXAG4vWt9EyCN.', 'customer', 'Erika', 'Xiomara', 'xiierika', 'id_1776097642_69dd196ab5fe6.jpg', 'pending', NULL, 1, 0, 'approved', NULL),
(45, 'whatroses7@gmail.com', '$2y$10$Cb3LKNfZmBxjYuioCplAKO71W0S75cG6kSLrsSr9HDYL1LbdLz/1q', 'customer', 'Rose', 'Whatley', 'whatroses7', 'id_1776097736_69dd19c87794a.jpg', 'pending', NULL, 0, 0, 'pending', NULL),
(46, 'rcespino2006@gmail.com', '$2y$10$Mh2QjZWW8VWEwFu6KLYQMu0Ypw/kxxwLDUXXj6CeYJY/G7gwP.ug6', 'customer', 'Rose', 'Espino', 'rcespino', 'id_1776097830_69dd1a26406b2.jpg', 'pending', NULL, 0, 0, 'pending', NULL),
(47, 'eng9ablola@gmail.com', '$2y$10$Ld5hebg5HBTfgwP.Pl5KUeem952Ud/ISQCaygd5ymRzLGRfXPBbMe', 'customer', 'Ablola', 'Eng', 'eng9ablola', 'id_1776098339_69dd1c234d892.jpg', 'pending', NULL, 1, 0, 'pending', NULL),
(48, 'isturis.reyesfam@gmail.com', '$2y$10$jB9Lc2L/feKNktALNNnJZOe2qA2vWsJQwnk50FQPVUsUtkF0/uLri', 'customer', 'Jijie', 'Isturis', 'Jijie', 'id_1776100775_69dd25a77abb2.jpg', 'pending', NULL, 1, 0, 'approved', NULL),
(49, 'reyesjanelle083@gmail.com', '$2y$10$TzYiZ9LWME2JgtGL9wm14OX3ySu9SWD3PVcxCpvwdTpv533nvR9ZW', 'customer', 'Janelle', 'Reyes', 'j.renee', 'id_1776100880_69dd2610d1ff0.jpg', 'pending', NULL, 1, 0, 'approved', NULL),
(50, 'meablola@tip.edu.ph', '$2y$10$j00NjnCSPUBauhwF/WLtWeeXk/OK9m.MKQttnWQNtST.0St4At2w6', 'customer', 'Erika', 'Ablola', 'meablola', 'id_1776181648_69de6190c66df.jpg', 'pending', NULL, 0, 0, 'pending', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_messages`
--
ALTER TABLE `admin_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `replied_by` (`replied_by`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`),
  ADD KEY `fk_branch_manager` (`access_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  ADD PRIMARY KEY (`code_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `code` (`code`);

--
-- Indexes for table `id_verification_log`
--
ALTER TABLE `id_verification_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_reviewed` (`reviewed_at`);

--
-- Indexes for table `medicine`
--
ALTER TABLE `medicine`
  ADD PRIMARY KEY (`medicine_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reset_token` (`reset_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`shift_id`),
  ADD UNIQUE KEY `unique_shift_name` (`shift_name`);

--
-- Indexes for table `staff_members`
--
ALTER TABLE `staff_members`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  ADD PRIMARY KEY (`shift_assignment_id`),
  ADD UNIQUE KEY `unique_staff_shift_date` (`staff_id`,`shift_id`,`assigned_date`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `idx_assigned_date` (`assigned_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_branch` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_messages`
--
ALTER TABLE `admin_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  MODIFY `code_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `id_verification_log`
--
ALTER TABLE `id_verification_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `medicine`
--
ALTER TABLE `medicine`
  MODIFY `medicine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `shift_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staff_members`
--
ALTER TABLE `staff_members`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  MODIFY `shift_assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_messages`
--
ALTER TABLE `admin_messages`
  ADD CONSTRAINT `admin_messages_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_messages_ibfk_2` FOREIGN KEY (`replied_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `fk_branch_manager` FOREIGN KEY (`access_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  ADD CONSTRAINT `email_verification_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `id_verification_log`
--
ALTER TABLE `id_verification_log`
  ADD CONSTRAINT `id_verification_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `id_verification_log_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  ADD CONSTRAINT `staff_shifts_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_members` (`staff_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_shifts_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`shift_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
