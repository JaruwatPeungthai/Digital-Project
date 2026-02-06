-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 06, 2026 at 10:05 AM
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

-- --------------------------------------------------------

--
-- Table structure for table `advisor_students`
--

CREATE TABLE `advisor_students` (
  `advisor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advisor_students`
--

INSERT INTO `advisor_students` (`advisor_id`, `student_id`) VALUES
(1, 7);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `checkin_time` datetime DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `status` enum('present','denied') DEFAULT 'present',
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `session_id`, `student_id`, `checkin_time`, `latitude`, `longitude`, `status`, `reason`) VALUES
(10, 32, 5, NULL, NULL, NULL, 'denied', ''),
(11, 34, 6, '2026-01-13 11:53:44', 13.9173745, 99.8425957, 'present', NULL),
(12, 32, 6, NULL, NULL, NULL, 'denied', ''),
(13, 35, 5, '2026-01-15 11:13:42', 13.9056523, 100.5294726, 'present', NULL),
(14, 38, 5, '2026-01-21 12:15:03', 13.9048029, 100.5293283, 'present', NULL),
(15, 39, 7, '2026-02-04 19:42:31', 13.904296744787, 100.52786544794, 'present', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_name` varchar(100) DEFAULT NULL,
  `room_name` varchar(50) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `radius_meter` int(11) DEFAULT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_sessions`
--

INSERT INTO `attendance_sessions` (`id`, `teacher_id`, `subject_name`, `room_name`, `start_time`, `end_time`, `latitude`, `longitude`, `radius_meter`, `qr_token`, `is_active`, `created_at`, `deleted_at`) VALUES
(32, 1, 'การตลาด', 'week1', '2026-01-06 17:36:00', '2026-01-07 17:36:00', 13.90450436961379, 100.52861046600702, 50, '7f14671d8693b67be2dfe9c0a1a24da5', 1, '2026-01-13 10:36:18', NULL),
(33, 1, 'ห้ะ', 'week1', '2026-01-05 17:51:00', '2026-01-21 17:51:00', 13.75152361242129, 100.52850723266603, 50, 'd40153967ff35f08302743cfb15f1349', 1, '2026-01-13 10:51:46', '2026-01-17 14:08:12'),
(34, 1, '5000', '5000', '2026-01-05 17:53:00', '2026-01-21 17:53:00', 13.915851921492687, 99.8436639273445, 5000, '0d888c4cc0cf3163cb287e0735b4f5d5', 1, '2026-01-13 10:53:24', '2026-01-19 12:55:07'),
(35, 1, 'การตลาด', 'การตลาด1', '2026-01-14 17:12:00', '2026-01-16 17:13:00', 13.905679651969145, 100.52903294627727, 300, '4b4cd55cd15faf20c3b8a6eef30c30be', 1, '2026-01-15 10:13:08', NULL),
(37, 1, '400', '400', '2026-01-19 02:55:00', '2026-01-21 02:55:00', 13.66520322103484, 100.7561874586203, 4000, 'f4df58f8f96d8f9c05ac338651b92e07', 1, '2026-01-19 19:55:18', NULL),
(38, 1, 'Digital projrect', 'Update 1/21/2026', '2026-01-20 18:06:00', '2026-01-27 18:06:00', 13.905698010040323, 100.52864513034814, 650, 'f9d2c0c0883bcd9514ddb3225f9ee63a', 1, '2026-01-21 11:13:49', NULL),
(39, 1, 'การตลาด', 'การตลาด1', '2026-02-01 17:14:00', '2026-02-04 17:14:00', 13.905708163865397, 100.52903386289357, 300, '06f3aa0bca3bf22f0a0a21ee6fc169ef', 1, '2026-02-03 10:14:52', NULL);

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
(7, '651310007', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', 1),
(8, '651310001', 'นักศึกษา ธุรกิจ 01', 'ธุรกิจ', NULL),
(9, '651310002', 'นักศึกษา ธุรกิจ 02', 'ธุรกิจ', NULL),
(10, '651310003', 'นักศึกษา ธุรกิจ 03', 'ธุรกิจ', NULL),
(11, '651310004', 'นักศึกษา ธุรกิจ 04', 'ธุรกิจ', NULL),
(12, '651310005', 'นักศึกษา ธุรกิจ 05', 'ธุรกิจ', NULL),
(13, '651310006', 'นักศึกษา ธุรกิจ 06', 'ธุรกิจ', NULL),
(14, '651310008', 'นักศึกษา ธุรกิจ 08', 'ธุรกิจ', NULL),
(15, '651310009', 'นักศึกษา ธุรกิจ 09', 'ธุรกิจ', NULL),
(16, '651310010', 'นักศึกษา ธุรกิจ 10', 'ธุรกิจ', NULL),
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
(106, '651310100', 'นักศึกษา นิเทศ 20', 'นิเทศ', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `teacher_id`, `subject_name`) VALUES
(4, 1, 'การตลาด'),
(5, 1, 'Cyber'),
(6, 1, 'Digital projects');

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
(0, 5),
(4, 6),
(5, 5),
(5, 6);

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
(1, 'อาจารย์', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', 'armrockdc@gmail.com', '$2y$10$FqSBWdAgba31SQ1HCLZEce4MaiLnEQdzIkOVy.eC/9KRG.iK4yDbq', 'approved', '2026-01-07 12:52:22'),
(2, 'อาจารย์', 'JARUWAT PEUNGTHAI', 'ออกแบบอนิเมชั่น', 'armeye76@gmail.com', '$2y$10$libc97osYsdIvwz.L3ZTVug3iJefP67UH7xUVOGLUPtjVLI8qm61C', 'approved', '2026-01-25 09:50:26'),
(3, 'ผศ.ดร.', 'อาจารย์ ทดสอบ', 'ธุรกิจ', 'testtest@gmail.com', '$2y$10$MGNBCgFUnPPWprLzN.gVm.vdCPl2vgX6owS4PoqGD1pw1vp8O5Ude', 'approved', '2026-02-03 10:18:14');

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
(7, 'Ubf3a11746398dd799f120b2fea473f72', 'student', '2026-02-03 10:15:38'),
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
(106, 'dummy_651310100', 'student', '2026-02-06 10:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `student_edit_requests`
--
ALTER TABLE `student_edit_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`,`student_id`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_deleted` (`deleted_at`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

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
