-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2026 at 09:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `checkin_time` datetime DEFAULT NULL,
  `checkout_time` datetime DEFAULT NULL,
  `checkin_status` enum('on-time','late') DEFAULT NULL,
  `checkout_status` enum('checked-out','not-checked-out') DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `status` enum('present','denied') DEFAULT 'present',
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `session_id`, `student_id`, `checkin_time`, `checkout_time`, `checkin_status`, `checkout_status`, `latitude`, `longitude`, `status`, `reason`) VALUES
(186, 69, 10, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(187, 69, 11, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(188, 69, 12, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(189, 69, 13, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(190, 69, 16, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(191, 69, 17, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(192, 69, 107, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(193, 69, 108, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(194, 69, 110, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(195, 69, 111, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(196, 69, 113, '2026-03-03 02:47:15', '2026-03-03 03:03:31', 'on-time', 'checked-out', 13.9057024, 100.529444, 'present', NULL),
(197, 70, 10, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(198, 70, 11, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(199, 70, 12, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(200, 70, 13, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(201, 70, 16, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(202, 70, 17, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(203, 70, 108, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(204, 70, 110, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(205, 70, 111, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(206, 70, 114, '2026-03-03 16:31:44', NULL, 'on-time', NULL, 13.904484313478, 100.52881908124, 'present', NULL),
(207, 71, 10, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(208, 71, 11, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(209, 71, 12, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(210, 71, 13, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(211, 71, 16, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(212, 71, 17, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(213, 71, 108, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(214, 71, 110, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(215, 71, 111, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(216, 71, 114, NULL, NULL, NULL, NULL, NULL, NULL, 'present', NULL),
(240, 80, 114, '2026-03-04 01:30:01', '2026-03-04 01:35:06', 'on-time', 'checked-out', 13.905734, 100.5294309, 'present', NULL),
(241, 81, 114, '2026-03-04 02:14:38', NULL, 'on-time', NULL, 13.9057597, 100.5293978, 'present', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `subject_name` varchar(100) DEFAULT NULL,
  `room_name` varchar(50) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `checkin_start` datetime DEFAULT NULL COMMENT 'When check-in window opens (defaults to 15 min before start_time)',
  `checkin_deadline` datetime DEFAULT NULL COMMENT 'After this time = late (defaults to start_time)',
  `checkout_start` datetime DEFAULT NULL COMMENT 'When check-out window opens (defaults to end_time)',
  `checkout_deadline` datetime DEFAULT NULL COMMENT 'After this time = not checked-out (defaults to end_time + 30 min)',
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `radius_meter` int(11) DEFAULT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty_admin`
--

CREATE TABLE `faculty_admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_admin`
--

INSERT INTO `faculty_admin` (`id`, `username`, `password_hash`) VALUES
(0, 'ICT', '$2y$10$jEovHw..WX1P3IrMA9WHtez9eaJXdT5aFWVqDMszpo/pgHSh1jxYa');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `user_id` int(11) NOT NULL,
  `student_code` varchar(20) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `class_group` varchar(50) DEFAULT NULL,
  `advisor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`user_id`, `student_code`, `full_name`, `class_group`, `advisor_id`) VALUES
(6, 'Dababy', 'Letgo', 'นิเทศ', 3),
(8, '651310001', 'นักศึกษา ธุรกิจ 01', 'ธุรกิจ', NULL),
(9, '651310002', 'นักศึกษา ธุรกิจ 02', 'ธุรกิจ', NULL),
(10, '651310003', 'นักศึกษา ธุรกิจ 03', 'ธุรกิจ', 1),
(11, '651310004', 'นักศึกษา ธุรกิจ 04', 'ธุรกิจ', 1),
(12, '651310005', 'นักศึกษา ธุรกิจ 05', 'ธุรกิจ', 1),
(13, '651310006', 'นักศึกษา ธุรกิจ 06', 'ธุรกิจ', 1),
(14, '651310008', 'นักศึกษา ธุรกิจ 08', 'ธุรกิจ', NULL),
(15, '651310009', 'นักศึกษา ธุรกิจ 09', 'ธุรกิจ', NULL),
(16, '651310010', 'นักศึกษา ธุรกิจ 10', 'ธุรกิจ', 1),
(17, '651310011', 'นักศึกษา ธุรกิจ 11', 'ธุรกิจ', NULL),
(18, '651310012', 'นักศึกษา ธุรกิจ 12', 'ธุรกิจ', NULL),
(19, '651310013', 'นักศึกษา ธุรกิจ 13', 'ธุรกิจ', NULL),
(20, '651310014', 'นักศึกษา ธุรกิจ 14', 'ธุรกิจ', NULL),
(21, '651310015', 'นักศึกษา ธุรกิจ 15', 'ธุรกิจ', NULL),
(22, '651310016', 'นักศึกษา ธุรกิจ 16', 'ธุรกิจ', NULL),
(23, '651310017', 'นักศึกษา ธุรกิจ 17', 'ธุรกิจ', NULL),
(24, '651310018', 'นักศึกษา ธุรกิจ 18', 'ธุรกิจ', NULL),
(25, '651310019', 'นักศึกษา ธุรกิจ 19', 'ธุรกิจ', NULL),
(26, '651310020', 'นักศึกษา ธุรกิจ 20', 'ธุรกิจ', NULL),
(27, '651310021', 'นักศึกษา อนิเมชั่น 01', 'ออกแบบอนิเมชั่น', NULL),
(28, '651310022', 'นักศึกษา อนิเมชั่น 02', 'ออกแบบอนิเมชั่น', NULL),
(29, '651310023', 'นักศึกษา อนิเมชั่น 03', 'ออกแบบอนิเมชั่น', NULL),
(30, '651310024', 'นักศึกษา อนิเมชั่น 04', 'ออกแบบอนิเมชั่น', NULL),
(31, '651310025', 'นักศึกษา อนิเมชั่น 05', 'ออกแบบอนิเมชั่น', NULL),
(32, '651310026', 'นักศึกษา อนิเมชั่น 06', 'ออกแบบอนิเมชั่น', NULL),
(33, '651310027', 'นักศึกษา อนิเมชั่น 07', 'ออกแบบอนิเมชั่น', NULL),
(34, '651310028', 'นักศึกษา อนิเมชั่น 08', 'ออกแบบอนิเมชั่น', NULL),
(35, '651310029', 'นักศึกษา อนิเมชั่น 09', 'ออกแบบอนิเมชั่น', NULL),
(36, '651310030', 'นักศึกษา อนิเมชั่น 10', 'ออกแบบอนิเมชั่น', NULL),
(37, '651310031', 'นักศึกษา อนิเมชั่น 11', 'ออกแบบอนิเมชั่น', NULL),
(38, '651310032', 'นักศึกษา อนิเมชั่น 12', 'ออกแบบอนิเมชั่น', NULL),
(39, '651310033', 'นักศึกษา อนิเมชั่น 13', 'ออกแบบอนิเมชั่น', NULL),
(40, '651310034', 'นักศึกษา อนิเมชั่น 14', 'ออกแบบอนิเมชั่น', NULL),
(41, '651310035', 'นักศึกษา อนิเมชั่น 15', 'ออกแบบอนิเมชั่น', NULL),
(42, '651310036', 'นักศึกษา อนิเมชั่น 16', 'ออกแบบอนิเมชั่น', NULL),
(43, '651310037', 'นักศึกษา อนิเมชั่น 17', 'ออกแบบอนิเมชั่น', NULL),
(44, '651310038', 'นักศึกษา อนิเมชั่น 18', 'ออกแบบอนิเมชั่น', NULL),
(45, '651310039', 'นักศึกษา อนิเมชั่น 19', 'ออกแบบอนิเมชั่น', NULL),
(46, '651310040', 'นักศึกษา อนิเมชั่น 20', 'ออกแบบอนิเมชั่น', NULL),
(47, '651310041', 'นักศึกษา แอพ 01', 'ออกแบบแอพ', NULL),
(48, '651310042', 'นักศึกษา แอพ 02', 'ออกแบบแอพ', NULL),
(49, '651310043', 'นักศึกษา แอพ 03', 'ออกแบบแอพ', NULL),
(50, '651310044', 'นักศึกษา แอพ 04', 'ออกแบบแอพ', NULL),
(51, '651310045', 'นักศึกษา แอพ 05', 'ออกแบบแอพ', NULL),
(52, '651310046', 'นักศึกษา แอพ 06', 'ออกแบบแอพ', NULL),
(53, '651310047', 'นักศึกษา แอพ 07', 'ออกแบบแอพ', NULL),
(54, '651310048', 'นักศึกษา แอพ 08', 'ออกแบบแอพ', NULL),
(55, '651310049', 'นักศึกษา แอพ 09', 'ออกแบบแอพ', NULL),
(56, '651310050', 'นักศึกษา แอพ 10', 'ออกแบบแอพ', NULL),
(57, '651310051', 'นักศึกษา แอพ 11', 'ออกแบบแอพ', NULL),
(58, '651310052', 'นักศึกษา แอพ 12', 'ออกแบบแอพ', NULL),
(59, '651310053', 'นักศึกษา แอพ 13', 'ออกแบบแอพ', NULL),
(60, '651310054', 'นักศึกษา แอพ 14', 'ออกแบบแอพ', NULL),
(61, '651310055', 'นักศึกษา แอพ 15', 'ออกแบบแอพ', NULL),
(62, '651310056', 'นักศึกษา แอพ 16', 'ออกแบบแอพ', NULL),
(63, '651310057', 'นักศึกษา แอพ 17', 'ออกแบบแอพ', NULL),
(64, '651310058', 'นักศึกษา แอพ 18', 'ออกแบบแอพ', NULL),
(65, '651310059', 'นักศึกษา แอพ 19', 'ออกแบบแอพ', NULL),
(66, '651310060', 'นักศึกษา แอพ 20', 'ออกแบบแอพ', NULL),
(67, '651310061', 'นักศึกษา เกม 01', 'ออกแบบเกม', NULL),
(68, '651310062', 'นักศึกษา เกม 02', 'ออกแบบเกม', NULL),
(69, '651310063', 'นักศึกษา เกม 03', 'ออกแบบเกม', NULL),
(70, '651310064', 'นักศึกษา เกม 04', 'ออกแบบเกม', NULL),
(71, '651310065', 'นักศึกษา เกม 05', 'ออกแบบเกม', NULL),
(72, '651310066', 'นักศึกษา เกม 06', 'ออกแบบเกม', NULL),
(73, '651310067', 'นักศึกษา เกม 07', 'ออกแบบเกม', NULL),
(74, '651310068', 'นักศึกษา เกม 08', 'ออกแบบเกม', NULL),
(75, '651310069', 'นักศึกษา เกม 09', 'ออกแบบเกม', NULL),
(76, '651310070', 'นักศึกษา เกม 10', 'ออกแบบเกม', NULL),
(77, '651310071', 'นักศึกษา เกม 11', 'ออกแบบเกม', NULL),
(78, '651310072', 'นักศึกษา เกม 12', 'ออกแบบเกม', NULL),
(79, '651310073', 'นักศึกษา เกม 13', 'ออกแบบเกม', NULL),
(80, '651310074', 'นักศึกษา เกม 14', 'ออกแบบเกม', NULL),
(81, '651310075', 'นักศึกษา เกม 15', 'ออกแบบเกม', NULL),
(82, '651310076', 'นักศึกษา เกม 16', 'ออกแบบเกม', NULL),
(83, '651310077', 'นักศึกษา เกม 17', 'ออกแบบเกม', NULL),
(84, '651310078', 'นักศึกษา เกม 18', 'ออกแบบเกม', NULL),
(85, '651310079', 'นักศึกษา เกม 19', 'ออกแบบเกม', NULL),
(86, '651310080', 'นักศึกษา เกม 20', 'ออกแบบเกม', NULL),
(87, '651310081', 'นักศึกษา นิเทศ 01', 'นิเทศ', NULL),
(88, '651310082', 'นักศึกษา นิเทศ 02', 'นิเทศ', NULL),
(89, '651310083', 'นักศึกษา นิเทศ 03', 'นิเทศ', NULL),
(90, '651310084', 'นักศึกษา นิเทศ 04', 'นิเทศ', NULL),
(91, '651310085', 'นักศึกษา นิเทศ 05', 'นิเทศ', NULL),
(92, '651310086', 'นักศึกษา นิเทศ 06', 'นิเทศ', NULL),
(93, '651310087', 'นักศึกษา นิเทศ 07', 'นิเทศ', NULL),
(94, '651310088', 'นักศึกษา นิเทศ 08', 'นิเทศ', NULL),
(95, '651310089', 'นักศึกษา นิเทศ 09', 'นิเทศ', NULL),
(96, '651310090', 'นักศึกษา นิเทศ 10', 'นิเทศ', NULL),
(97, '651310091', 'นักศึกษา นิเทศ 11', 'นิเทศ', NULL),
(98, '651310092', 'นักศึกษา นิเทศ 12', 'นิเทศ', NULL),
(99, '651310093', 'นักศึกษา นิเทศ 13', 'นิเทศ', NULL),
(100, '651310094', 'นักศึกษา นิเทศ 14', 'นิเทศ', NULL),
(101, '651310095', 'นักศึกษา นิเทศ 15', 'นิเทศ', NULL),
(102, '651310096', 'นักศึกษา นิเทศ 16', 'นิเทศ', NULL),
(103, '651310097', 'นักศึกษา นิเทศ 17', 'นิเทศ', NULL),
(104, '651310098', 'นักศึกษา นิเทศ 18', 'นิเทศ', NULL),
(105, '651310099', 'นักศึกษา นิเทศ 19', 'นิเทศ', NULL),
(106, '651310100', 'นักศึกษา นิเทศ 20', 'นิเทศ', NULL),
(108, '651310008', 'Jirapa', 'ธุรกิจ', 1),
(109, '651310027', 'วิไลวรรณ วงค์คำใหญ่', 'ธุรกิจ', NULL),
(110, '651310002', 'Kanyaphak ', 'ธุรกิจ', 1),
(111, '651310009', 'ธนวันต์', 'ธุรกิจ', 1),
(114, '651310007', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_edit_requests`
--

CREATE TABLE `student_edit_requests` (
  `request_id` varchar(20) NOT NULL,
  `student_id` int(11) NOT NULL,
  `requested_by` varchar(50) DEFAULT NULL COMMENT 'advisor_id or faculty',
  `old_student_code` varchar(20) DEFAULT NULL,
  `old_full_name` varchar(100) DEFAULT NULL,
  `old_class_group` varchar(50) DEFAULT NULL,
  `new_student_code` varchar(20) DEFAULT NULL,
  `new_full_name` varchar(100) DEFAULT NULL,
  `new_class_group` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_edit_requests`
--

INSERT INTO `student_edit_requests` (`request_id`, `student_id`, `requested_by`, `old_student_code`, `old_full_name`, `old_class_group`, `new_student_code`, `new_full_name`, `new_class_group`, `status`, `created_at`, `reviewed_at`, `reviewed_by`) VALUES
('REQ20260206183155856', 7, 'advisor_1', '651310007', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', '651310007dsa', 'จารุวัฒน์ พึ่งไทยdasdsad', 'ออกแบบแอพ', 'rejected', '2026-02-06 17:31:55', '2026-02-07 00:35:15', 1),
('REQ20260206183601463', 7, 'faculty', '651310007', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', '651310007dsa', 'จารุวัฒน์ พึ่งไทยdasdsad', 'ออกแบบเกม', 'rejected', '2026-02-06 17:36:01', '2026-02-07 00:48:13', 0),
('REQ20260206221809714', 7, 'advisor_1', '651310007', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', '651310007dsa', 'จารุวัฒน์ พึ่งไทยdasdsad', 'ออกแบบเกม', 'approved', '2026-02-06 21:18:09', '2026-02-07 04:18:18', 1),
('REQ20260206221859508', 7, 'advisor_1', '651310007dsa', 'จารุวัฒน์ พึ่งไทยdasdsad', 'ออกแบบเกม', '651310007', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', 'approved', '2026-02-06 21:18:59', '2026-02-07 04:19:28', 0),
('REQ20260206222310400', 7, 'advisor_1', '651310007', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', '6513100077', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', 'rejected', '2026-02-06 21:23:10', '2026-02-07 04:23:20', 1),
('REQ20260207114737552', 7, 'advisor_1', '651310007', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', '651310007.2', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', 'rejected', '2026-02-07 10:47:37', '2026-03-02 02:12:50', 0),
('REQ20260301193138439', 107, 'advisor_1', '651310007', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', '651310007..', 'จารุวัฒน์ พึ่งไทย..', 'ออกแบบแอพ', 'rejected', '2026-03-01 18:31:38', '2026-03-02 02:12:53', 0);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_name` varchar(100) DEFAULT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL,
  `years` varchar(4) NOT NULL,
  `semester` varchar(1) NOT NULL,
  `hash` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `teacher_id`, `subject_name`, `subject_code`, `section`, `years`, `semester`, `hash`) VALUES
(26, 1, 'Digital projects', '800-001', '1', '2569', '1', '360433a786c5671ade1ffec33c361661aecdc9aa3fc4023ddf68ee3e9e594329'),
(28, 1, 'Digital projects', '800-001', '1', '2569', '2', '79790560501eb52a1cb2f188b295865aca4defed5355d8f5640ad2daa694eef1'),
(29, 1, 'Digital projects', '800-001', '1', '2569', '3', '681776d4b6434995b1549b1a4d3a7184c34cae3eea6d58f439ca0f6d654f72d3');

-- --------------------------------------------------------

--
-- Table structure for table `subject_students`
--

CREATE TABLE `subject_students` (
  `subject_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_students`
--

INSERT INTO `subject_students` (`subject_id`, `student_id`) VALUES
(0, 5);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `title` enum('ผศ.ดร.','รศ.ดร.','ศ.ดร.','อาจารย์') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department` enum('ธุรกิจ','ออกแบบอนิเมชั่น','ออกแบบแอพ','ออกแบบเกม','นิเทศ') NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('pending','approved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `title`, `full_name`, `department`, `email`, `password_hash`, `status`, `created_at`) VALUES
(1, 'อาจารย์', 'iL Dottore', 'ธุรกิจ', 'armrockdc@gmail.com', '$2y$10$FqSBWdAgba31SQ1HCLZEce4MaiLnEQdzIkOVy.eC/9KRG.iK4yDbq', 'approved', '2026-01-07 12:52:22'),
(2, 'อาจารย์', 'JARUWAT PEUNGTHAI', 'ออกแบบอนิเมชั่น', 'armeye76@gmail.com', '$2y$10$libc97osYsdIvwz.L3ZTVug3iJefP67UH7xUVOGLUPtjVLI8qm61C', 'approved', '2026-01-25 09:50:26'),
(3, 'ผศ.ดร.', 'อาจารย์ ทดสอบ', 'ธุรกิจ', 'testtest@gmail.com', '$2y$10$MGNBCgFUnPPWprLzN.gVm.vdCPl2vgX6owS4PoqGD1pw1vp8O5Ude', 'approved', '2026-02-03 10:18:14'),
(5, 'ศ.ดร.', 'ยศชนัน วงศ์สวัสดิ์', 'ออกแบบอนิเมชั่น', 'CH@gmail.com', '$2y$10$bUTeEYZ0D/2JpiLjtYCI5.CCy8l2J3vryb/a6VZ3bVdg6XAnspkT6', 'pending', '2026-02-07 09:51:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `line_user_id` varchar(50) DEFAULT NULL,
  `role` enum('student','teacher') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `line_user_id`, `role`, `created_at`) VALUES
(6, 'U5bc3c214ca83302d9ac9535d128ca93c', 'student', '2026-01-13 10:50:55'),
(8, 'dummy_651310001', 'student', '2026-02-06 10:00:00'),
(9, 'dummy_651310002', 'student', '2026-02-06 10:00:00'),
(10, 'dummy_651310003', 'student', '2026-02-06 10:00:00'),
(11, 'dummy_651310004', 'student', '2026-02-06 10:00:00'),
(12, 'dummy_651310005', 'student', '2026-02-06 10:00:00'),
(13, 'dummy_651310006', 'student', '2026-02-06 10:00:00'),
(14, 'dummy_651310008', 'student', '2026-02-06 10:00:00'),
(15, 'dummy_651310009', 'student', '2026-02-06 10:00:00'),
(16, 'dummy_651310010', 'student', '2026-02-06 10:00:00'),
(17, 'dummy_651310011', 'student', '2026-02-06 10:00:00'),
(18, 'dummy_651310012', 'student', '2026-02-06 10:00:00'),
(19, 'dummy_651310013', 'student', '2026-02-06 10:00:00'),
(20, 'dummy_651310014', 'student', '2026-02-06 10:00:00'),
(21, 'dummy_651310015', 'student', '2026-02-06 10:00:00'),
(22, 'dummy_651310016', 'student', '2026-02-06 10:00:00'),
(23, 'dummy_651310017', 'student', '2026-02-06 10:00:00'),
(24, 'dummy_651310018', 'student', '2026-02-06 10:00:00'),
(25, 'dummy_651310019', 'student', '2026-02-06 10:00:00'),
(26, 'dummy_651310020', 'student', '2026-02-06 10:00:00'),
(27, 'dummy_651310021', 'student', '2026-02-06 10:00:00'),
(28, 'dummy_651310022', 'student', '2026-02-06 10:00:00'),
(29, 'dummy_651310023', 'student', '2026-02-06 10:00:00'),
(30, 'dummy_651310024', 'student', '2026-02-06 10:00:00'),
(31, 'dummy_651310025', 'student', '2026-02-06 10:00:00'),
(32, 'dummy_651310026', 'student', '2026-02-06 10:00:00'),
(33, 'dummy_651310027', 'student', '2026-02-06 10:00:00'),
(34, 'dummy_651310028', 'student', '2026-02-06 10:00:00'),
(35, 'dummy_651310029', 'student', '2026-02-06 10:00:00'),
(36, 'dummy_651310030', 'student', '2026-02-06 10:00:00'),
(37, 'dummy_651310031', 'student', '2026-02-06 10:00:00'),
(38, 'dummy_651310032', 'student', '2026-02-06 10:00:00'),
(39, 'dummy_651310033', 'student', '2026-02-06 10:00:00'),
(40, 'dummy_651310034', 'student', '2026-02-06 10:00:00'),
(41, 'dummy_651310035', 'student', '2026-02-06 10:00:00'),
(42, 'dummy_651310036', 'student', '2026-02-06 10:00:00'),
(43, 'dummy_651310037', 'student', '2026-02-06 10:00:00'),
(44, 'dummy_651310038', 'student', '2026-02-06 10:00:00'),
(45, 'dummy_651310039', 'student', '2026-02-06 10:00:00'),
(46, 'dummy_651310040', 'student', '2026-02-06 10:00:00'),
(47, 'dummy_651310041', 'student', '2026-02-06 10:00:00'),
(48, 'dummy_651310042', 'student', '2026-02-06 10:00:00'),
(49, 'dummy_651310043', 'student', '2026-02-06 10:00:00'),
(50, 'dummy_651310044', 'student', '2026-02-06 10:00:00'),
(51, 'dummy_651310045', 'student', '2026-02-06 10:00:00'),
(52, 'dummy_651310046', 'student', '2026-02-06 10:00:00'),
(53, 'dummy_651310047', 'student', '2026-02-06 10:00:00'),
(54, 'dummy_651310048', 'student', '2026-02-06 10:00:00'),
(55, 'dummy_651310049', 'student', '2026-02-06 10:00:00'),
(56, 'dummy_651310050', 'student', '2026-02-06 10:00:00'),
(57, 'dummy_651310051', 'student', '2026-02-06 10:00:00'),
(58, 'dummy_651310052', 'student', '2026-02-06 10:00:00'),
(59, 'dummy_651310053', 'student', '2026-02-06 10:00:00'),
(60, 'dummy_651310054', 'student', '2026-02-06 10:00:00'),
(61, 'dummy_651310055', 'student', '2026-02-06 10:00:00'),
(62, 'dummy_651310056', 'student', '2026-02-06 10:00:00'),
(63, 'dummy_651310057', 'student', '2026-02-06 10:00:00'),
(64, 'dummy_651310058', 'student', '2026-02-06 10:00:00'),
(65, 'dummy_651310059', 'student', '2026-02-06 10:00:00'),
(66, 'dummy_651310060', 'student', '2026-02-06 10:00:00'),
(67, 'dummy_651310061', 'student', '2026-02-06 10:00:00'),
(68, 'dummy_651310062', 'student', '2026-02-06 10:00:00'),
(69, 'dummy_651310063', 'student', '2026-02-06 10:00:00'),
(70, 'dummy_651310064', 'student', '2026-02-06 10:00:00'),
(71, 'dummy_651310065', 'student', '2026-02-06 10:00:00'),
(72, 'dummy_651310066', 'student', '2026-02-06 10:00:00'),
(73, 'dummy_651310067', 'student', '2026-02-06 10:00:00'),
(74, 'dummy_651310068', 'student', '2026-02-06 10:00:00'),
(75, 'dummy_651310069', 'student', '2026-02-06 10:00:00'),
(76, 'dummy_651310070', 'student', '2026-02-06 10:00:00'),
(77, 'dummy_651310071', 'student', '2026-02-06 10:00:00'),
(78, 'dummy_651310072', 'student', '2026-02-06 10:00:00'),
(79, 'dummy_651310073', 'student', '2026-02-06 10:00:00'),
(80, 'dummy_651310074', 'student', '2026-02-06 10:00:00'),
(81, 'dummy_651310075', 'student', '2026-02-06 10:00:00'),
(82, 'dummy_651310076', 'student', '2026-02-06 10:00:00'),
(83, 'dummy_651310077', 'student', '2026-02-06 10:00:00'),
(84, 'dummy_651310078', 'student', '2026-02-06 10:00:00'),
(85, 'dummy_651310079', 'student', '2026-02-06 10:00:00'),
(86, 'dummy_651310080', 'student', '2026-02-06 10:00:00'),
(87, 'dummy_651310081', 'student', '2026-02-06 10:00:00'),
(88, 'dummy_651310082', 'student', '2026-02-06 10:00:00'),
(89, 'dummy_651310083', 'student', '2026-02-06 10:00:00'),
(90, 'dummy_651310084', 'student', '2026-02-06 10:00:00'),
(91, 'dummy_651310085', 'student', '2026-02-06 10:00:00'),
(92, 'dummy_651310086', 'student', '2026-02-06 10:00:00'),
(93, 'dummy_651310087', 'student', '2026-02-06 10:00:00'),
(94, 'dummy_651310088', 'student', '2026-02-06 10:00:00'),
(95, 'dummy_651310089', 'student', '2026-02-06 10:00:00'),
(96, 'dummy_651310090', 'student', '2026-02-06 10:00:00'),
(97, 'dummy_651310091', 'student', '2026-02-06 10:00:00'),
(98, 'dummy_651310092', 'student', '2026-02-06 10:00:00'),
(99, 'dummy_651310093', 'student', '2026-02-06 10:00:00'),
(100, 'dummy_651310094', 'student', '2026-02-06 10:00:00'),
(101, 'dummy_651310095', 'student', '2026-02-06 10:00:00'),
(102, 'dummy_651310096', 'student', '2026-02-06 10:00:00'),
(103, 'dummy_651310097', 'student', '2026-02-06 10:00:00'),
(104, 'dummy_651310098', 'student', '2026-02-06 10:00:00'),
(105, 'dummy_651310099', 'student', '2026-02-06 10:00:00'),
(106, 'dummy_651310100', 'student', '2026-02-06 10:00:00'),
(108, 'Ubdeb30009f9b09fe08455919ed2efac0', 'student', '2026-02-07 11:16:16'),
(109, 'U0386b2c3430d763cb4933dceb9adbd62', 'student', '2026-02-07 11:17:18'),
(110, 'Ubd563de3684c424859da2c940eb29bd1', 'student', '2026-02-07 11:19:55'),
(111, 'U1d086b3f7f07da747fdf6ca06fc55d76', 'student', '2026-02-07 11:21:32'),
(114, 'Ubf3a11746398dd799f120b2fea473f72', 'student', '2026-03-02 20:36:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`,`student_id`),
  ADD KEY `idx_checkout_time` (`checkout_time`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_deleted` (`deleted_at`),
  ADD KEY `idx_checkin_times` (`checkin_start`,`checkin_deadline`),
  ADD KEY `idx_checkout_times` (`checkout_start`,`checkout_deadline`);

--
-- Indexes for table `faculty_admin`
--
ALTER TABLE `faculty_admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `student_edit_requests`
--
ALTER TABLE `student_edit_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `subject_students`
--
ALTER TABLE `subject_students`
  ADD PRIMARY KEY (`subject_id`,`student_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `line_user_id` (`line_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
