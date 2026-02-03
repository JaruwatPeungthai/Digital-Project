-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 19, 2026 at 10:15 AM
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
(13, 35, 5, '2026-01-15 11:13:42', 13.9056523, 100.5294726, 'present', NULL);

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
(35, 1, 'การตลาด', 'การตลาด1', '2026-01-14 17:12:00', '2026-01-16 17:13:00', 13.905679651969145, 100.52903294627727, 300, '4b4cd55cd15faf20c3b8a6eef30c30be', 1, '2026-01-15 10:13:08', NULL);

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
  `class_group` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`user_id`, `student_code`, `full_name`, `class_group`) VALUES
(5, '651310007', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ'),
(6, 'Dababy', 'Letgo', 'นิเทศ');

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
(5, 1, 'Cyber');

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
(1, 'อาจารย์', 'จารุวัฒน์ พึ่งไทย', 'ธุรกิจ', 'armrockdc@gmail.com', '$2y$10$FqSBWdAgba31SQ1HCLZEce4MaiLnEQdzIkOVy.eC/9KRG.iK4yDbq', 'approved', '2026-01-07 12:52:22');

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
(5, 'Ubf3a11746398dd799f120b2fea473f72', 'student', '2026-01-07 11:20:07'),
(6, 'U5bc3c214ca83302d9ac9535d128ca93c', 'student', '2026-01-13 10:50:55');

--
-- Indexes for dumped tables
--

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
