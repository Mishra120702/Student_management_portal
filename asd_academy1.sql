-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 22, 2025 at 07:25 AM
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
-- Database: `asd_academy1`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `batch_id` varchar(10) NOT NULL,
  `student_name` varchar(50) NOT NULL,
  `status` enum('Present','Absent','Late') DEFAULT 'Present',
  `camera_status` enum('On','Off') DEFAULT 'Off',
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `date`, `batch_id`, `student_name`, `status`, `camera_status`, `remarks`) VALUES
(1, '2025-01-10', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(2, '2025-01-12', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(3, '2025-01-15', 'B001', 'Alice Williams', 'Late', 'On', 'Traffic delay'),
(4, '2025-01-17', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(5, '2025-01-19', 'B001', 'Alice Williams', 'Absent', 'Off', 'Sick'),
(6, '2025-01-22', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(7, '2025-01-24', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(8, '2025-01-26', 'B001', 'Alice Williams', 'Late', 'On', 'Internet issues'),
(9, '2025-01-29', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(10, '2025-01-31', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(11, '2025-02-02', 'B001', 'Alice Williams', 'Absent', 'Off', 'Family event'),
(12, '2025-02-05', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(13, '2025-02-07', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(14, '2025-02-09', 'B001', 'Alice Williams', 'Late', 'On', 'Power outage'),
(15, '2025-02-12', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(16, '2025-02-14', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(17, '2025-02-16', 'B001', 'Alice Williams', 'Absent', 'Off', 'Personal reasons'),
(18, '2025-02-19', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(19, '2025-02-21', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(20, '2025-02-23', 'B001', 'Alice Williams', 'Late', 'On', 'Transportation delay'),
(21, '2025-02-26', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(22, '2025-02-28', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(23, '2025-03-03', 'B001', 'Alice Williams', 'Absent', 'Off', 'Sick'),
(24, '2025-03-05', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(25, '2025-03-07', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(26, '2025-03-09', 'B001', 'Alice Williams', 'Late', 'On', 'Technical issues'),
(27, '2025-03-12', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(28, '2025-03-14', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(29, '2025-03-16', 'B001', 'Alice Williams', 'Absent', 'Off', 'Personal reasons'),
(30, '2025-03-19', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(31, '2025-03-21', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(32, '2025-03-23', 'B001', 'Alice Williams', 'Late', 'On', 'Internet problems'),
(33, '2025-03-26', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(34, '2025-03-28', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(35, '2025-03-30', 'B001', 'Alice Williams', 'Absent', 'Off', 'Family event'),
(36, '2025-04-02', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(37, '2025-04-04', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(38, '2025-04-06', 'B001', 'Alice Williams', 'Late', 'On', 'Transportation delay'),
(39, '2025-04-09', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(40, '2025-01-10', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(41, '2025-01-12', 'B001', 'Bob Miller', 'Absent', 'Off', 'Sick'),
(42, '2025-01-15', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(43, '2025-01-17', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(44, '2025-01-19', 'B001', 'Bob Miller', 'Late', 'On', 'Technical issues'),
(45, '2025-01-22', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(46, '2025-01-24', 'B001', 'Bob Miller', 'Absent', 'Off', 'Personal reasons'),
(47, '2025-01-26', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(48, '2025-01-29', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(49, '2025-01-31', 'B001', 'Bob Miller', 'Late', 'On', 'Internet problems'),
(50, '2025-02-02', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(51, '2025-02-05', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(52, '2025-02-07', 'B001', 'Bob Miller', 'Absent', 'Off', 'Family event'),
(53, '2025-02-09', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(54, '2025-02-12', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(55, '2025-02-14', 'B001', 'Bob Miller', 'Late', 'On', 'Transportation delay'),
(56, '2025-02-16', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(57, '2025-02-19', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(58, '2025-02-21', 'B001', 'Bob Miller', 'Absent', 'Off', 'Sick'),
(59, '2025-02-23', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(60, '2025-02-26', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(61, '2025-02-28', 'B001', 'Bob Miller', 'Late', 'On', 'Power outage'),
(62, '2025-03-03', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(63, '2025-03-05', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(64, '2025-03-07', 'B001', 'Bob Miller', 'Absent', 'Off', 'Personal reasons'),
(65, '2025-03-09', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(66, '2025-03-12', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(67, '2025-03-14', 'B001', 'Bob Miller', 'Late', 'On', 'Internet issues'),
(68, '2025-03-16', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(69, '2025-03-19', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(70, '2025-03-21', 'B001', 'Bob Miller', 'Absent', 'Off', 'Family event'),
(71, '2025-03-23', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(72, '2025-03-26', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(73, '2025-03-28', 'B001', 'Bob Miller', 'Late', 'On', 'Traffic delay'),
(74, '2025-03-30', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(75, '2025-04-02', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(76, '2025-04-04', 'B001', 'Bob Miller', 'Absent', 'Off', 'Sick'),
(77, '2025-04-06', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(78, '2025-04-09', 'B001', 'Bob Miller', 'Present', 'On', NULL),
(79, '2025-02-15', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(80, '2025-02-17', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(81, '2025-02-19', 'B020', 'Frank Anderson', 'Late', 'On', 'Internet issues'),
(82, '2025-02-21', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(83, '2025-02-23', 'B020', 'Frank Anderson', 'Absent', 'Off', 'Sick'),
(84, '2025-02-25', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(85, '2025-02-27', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(86, '2025-03-01', 'B020', 'Frank Anderson', 'Late', 'On', 'Transportation delay'),
(87, '2025-03-03', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(88, '2025-03-05', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(89, '2025-03-07', 'B020', 'Frank Anderson', 'Absent', 'Off', 'Family event'),
(90, '2025-03-09', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(91, '2025-03-11', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(92, '2025-03-13', 'B020', 'Frank Anderson', 'Late', 'On', 'Technical problems'),
(93, '2025-03-15', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(94, '2025-03-17', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(95, '2025-03-19', 'B020', 'Frank Anderson', 'Absent', 'Off', 'Personal reasons'),
(96, '2025-03-21', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(97, '2025-03-23', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(98, '2025-03-25', 'B020', 'Frank Anderson', 'Late', 'On', 'Power outage'),
(99, '2025-03-27', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(100, '2025-03-29', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(101, '2025-03-31', 'B020', 'Frank Anderson', 'Absent', 'Off', 'Sick'),
(102, '2025-04-02', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(103, '2025-04-04', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(104, '2025-04-06', 'B020', 'Frank Anderson', 'Late', 'On', 'Traffic delay'),
(105, '2025-04-08', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(106, '2025-04-10', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(107, '2025-04-12', 'B020', 'Frank Anderson', 'Absent', 'Off', 'Family event'),
(108, '2025-04-14', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(109, '2025-04-16', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(110, '2025-04-18', 'B020', 'Frank Anderson', 'Late', 'On', 'Internet problems'),
(111, '2025-04-20', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(112, '2025-04-22', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(113, '2025-04-24', 'B020', 'Frank Anderson', 'Absent', 'Off', 'Personal reasons'),
(114, '2025-04-26', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(115, '2025-04-28', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(116, '2025-04-30', 'B020', 'Frank Anderson', 'Late', 'On', 'Technical issues'),
(117, '2025-05-02', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(118, '2025-05-04', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(119, '2025-05-06', 'B020', 'Frank Anderson', 'Absent', 'Off', 'Sick'),
(120, '2025-05-08', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(121, '2025-05-10', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(122, '2025-05-12', 'B020', 'Frank Anderson', 'Late', 'On', 'Transportation delay'),
(123, '2025-05-14', 'B020', 'Frank Anderson', 'Present', 'On', NULL),
(124, '2025-03-01', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(125, '2025-03-03', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(126, '2025-03-05', 'B003', 'Karen Martin', 'Late', 'On', 'Internet issues'),
(127, '2025-03-07', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(128, '2025-03-09', 'B003', 'Karen Martin', 'Absent', 'Off', 'Sick'),
(129, '2025-03-11', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(130, '2025-03-13', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(131, '2025-03-15', 'B003', 'Karen Martin', 'Late', 'On', 'Transportation delay'),
(132, '2025-03-17', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(133, '2025-03-19', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(134, '2025-03-21', 'B003', 'Karen Martin', 'Absent', 'Off', 'Family event'),
(135, '2025-03-23', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(136, '2025-03-25', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(137, '2025-03-27', 'B003', 'Karen Martin', 'Late', 'On', 'Technical problems'),
(138, '2025-03-29', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(139, '2025-03-31', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(140, '2025-04-02', 'B003', 'Karen Martin', 'Absent', 'Off', 'Personal reasons'),
(141, '2025-04-04', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(142, '2025-04-06', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(143, '2025-04-08', 'B003', 'Karen Martin', 'Late', 'On', 'Power outage'),
(144, '2025-04-10', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(145, '2025-04-12', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(146, '2025-04-14', 'B003', 'Karen Martin', 'Absent', 'Off', 'Sick'),
(147, '2025-04-16', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(148, '2025-04-18', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(149, '2025-04-20', 'B003', 'Karen Martin', 'Late', 'On', 'Traffic delay'),
(150, '2025-04-22', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(151, '2025-04-24', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(152, '2025-04-26', 'B003', 'Karen Martin', 'Absent', 'Off', 'Family event'),
(153, '2025-04-28', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(154, '2025-04-30', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(155, '2025-05-02', 'B003', 'Karen Martin', 'Late', 'On', 'Internet problems'),
(156, '2025-05-04', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(157, '2025-05-06', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(158, '2025-05-08', 'B003', 'Karen Martin', 'Absent', 'Off', 'Personal reasons'),
(159, '2025-05-10', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(160, '2025-05-12', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(161, '2025-05-14', 'B003', 'Karen Martin', 'Late', 'On', 'Technical issues'),
(162, '2025-05-16', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(163, '2025-05-18', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(164, '2025-05-20', 'B003', 'Karen Martin', 'Absent', 'Off', 'Sick'),
(165, '2025-05-22', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(166, '2025-05-24', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(167, '2025-05-26', 'B003', 'Karen Martin', 'Late', 'On', 'Transportation delay'),
(168, '2025-05-28', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(169, '2025-05-30', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(170, '2025-06-01', 'B003', 'Karen Martin', 'Absent', 'Off', 'Family event'),
(171, '2025-06-03', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(172, '2025-06-05', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(173, '2025-06-07', 'B003', 'Karen Martin', 'Late', 'On', 'Internet issues'),
(174, '2025-06-09', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(175, '2025-06-11', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(176, '2025-06-13', 'B003', 'Karen Martin', 'Absent', 'Off', 'Personal reasons'),
(177, '2025-06-15', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(178, '2025-06-17', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(179, '2025-06-19', 'B003', 'Karen Martin', 'Late', 'On', 'Power outage'),
(180, '2025-06-21', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(181, '2025-06-23', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(182, '2025-06-25', 'B003', 'Karen Martin', 'Absent', 'Off', 'Sick'),
(183, '2025-06-27', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(184, '2025-06-29', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(185, '2025-06-30', 'B003', 'Karen Martin', 'Late', 'On', 'Traffic delay'),
(186, '2025-07-01', 'B001', 'Alice Williams', 'Present', 'On', ''),
(187, '2025-07-01', 'B001', 'Bob Miller', 'Present', 'On', ''),
(188, '2025-07-01', 'B020', 'Frank Anderson', 'Late', 'Off', NULL),
(189, '2025-07-02', 'B001', 'Alice Williams', 'Present', 'On', NULL),
(190, '2025-07-02', 'B003', 'Karen Martin', 'Present', 'On', NULL),
(191, '2025-07-07', 'B001', 'Charlie Wilson', 'Present', 'Off', 'ok'),
(192, '2025-07-07', 'B001', 'David Moore', 'Absent', 'Off', ''),
(193, '2025-07-07', 'B001', 'Eva Taylor', 'Absent', 'Off', ''),
(194, '2025-07-07', 'B001', 'Raj Mishra', 'Absent', 'Off', ''),
(195, '2025-07-06', 'B020', 'Frank Anderson', 'Absent', 'Off', NULL),
(196, '2025-07-06', 'B020', 'Grace Thomas', 'Absent', 'Off', NULL),
(197, '2025-07-06', 'B020', 'Henry Jackson', 'Absent', 'Off', NULL),
(198, '2025-07-06', 'B002', 'Ivy White', 'Absent', 'Off', 'jijijiji'),
(199, '2025-07-06', 'B002', 'Jack Harris', 'Absent', 'Off', NULL),
(200, '2025-07-19', 'B028', 'Samuel Walker', 'Absent', 'Off', ''),
(201, '2025-07-19', 'B028', 'Rose Stewart', 'Absent', 'Off', 'due to health issue'),
(202, '2025-07-19', 'B028', 'Simon Sanchez', 'Present', 'Off', ''),
(203, '2025-07-19', 'B020', 'Raj Mishra', 'Present', 'On', ''),
(204, '2025-07-19', 'B003', 'Karen Martin', 'Present', 'On', ''),
(205, '2025-07-19', 'B003', 'Leo Garcia', 'Present', 'On', ''),
(206, '2025-07-19', 'B003', 'Mia Martinez', 'Present', 'On', ''),
(207, '2025-07-19', 'B003', 'Noah Robinson', 'Present', 'Off', ''),
(208, '2025-07-19', 'B003', 'Olivia Clark', 'Absent', 'Off', 'kikiiki'),
(209, '2025-07-18', 'B007', 'Ethan Gonzalez', 'Absent', 'Off', ''),
(210, '2025-07-18', 'B007', 'Fiona Nelson', 'Present', 'Off', ''),
(211, '2025-07-18', 'B007', 'George Carter', 'Present', 'Off', ''),
(212, '2025-07-18', 'B007', 'Hannah Mitchell', 'Absent', 'Off', ''),
(213, '2025-07-18', 'B007', 'Ian Perez', 'Absent', 'Off', '');

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `batch_id` varchar(10) NOT NULL,
  `course_name` varchar(50) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `time_slot` varchar(50) DEFAULT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `meeting_link` varchar(2083) DEFAULT NULL,
  `max_students` int(11) DEFAULT NULL,
  `current_enrollment` int(11) DEFAULT 0,
  `academic_year` varchar(20) DEFAULT NULL,
  `batch_mentor_id` int(11) DEFAULT NULL,
  `mode` enum('online','offline') DEFAULT 'online',
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`batch_id`, `course_name`, `start_date`, `end_date`, `time_slot`, `platform`, `meeting_link`, `max_students`, `current_enrollment`, `academic_year`, `batch_mentor_id`, `mode`, `status`, `created_at`, `created_by`) VALUES
('B001', 'Web Development Fundamentals', '2025-01-10', '2025-04-10', '09:00-11:00', 'Zoom', 'https://zoom.us/j/1234567890', 20, 13, '2024-2025', 2, 'online', 'completed', '2024-11-30 18:30:00', 1),
('B002', 'Advanced JavaScript', '2025-02-15', '2025-05-15', '11:00-13:00', 'Google Meet', 'https://meet.google.com/abc-defg-hij', 15, 12, '2024-2025', 2, 'online', 'completed', '2025-01-14 18:30:00', 1),
('B003', 'Python Programming', '2025-03-01', '2025-06-01', '14:00-16:00', 'Zoom', 'https://zoom.us/j/9876543210', 25, 20, '2024-2025', 3, 'online', 'ongoing', '2025-01-31 18:30:00', 1),
('B004', 'Data Science with Python', '2025-04-05', '2025-07-05', '16:00-18:00', 'Microsoft Teams', 'https://teams.microsoft.com/l/meetup-join/19%3ameeting_ABCDEFG', 20, 18, '2024-2025', 3, 'online', 'ongoing', '2025-03-04 18:30:00', 1),
('B005', 'Machine Learning', '2025-05-10', '2025-08-10', '09:00-11:00', 'Zoom', 'https://zoom.us/j/5555555555', 15, 10, '2024-2025', 4, 'online', 'ongoing', '2025-04-09 18:30:00', 1),
('B006', 'Mobile App Development', '2025-06-15', '2025-09-15', '11:00-13:00', 'Google Meet', 'https://meet.google.com/xyz-uvw-rst', 20, 15, '2024-2025', 4, 'online', 'ongoing', '2025-05-14 18:30:00', 1),
('B007', 'Database Design', '2025-07-01', '2025-10-01', '14:00-16:00', 'Zoom', 'https://zoom.us/j/1111111111', 15, 8, '2024-2025', 5, 'online', 'upcoming', '2025-05-31 18:30:00', 1),
('B008', 'Cloud Computing', '2025-08-05', '2025-11-05', '16:00-18:00', 'Microsoft Teams', 'https://teams.microsoft.com/l/meetup-join/19%3ameeting_HIJKLMN', 20, 5, '2024-2025', 5, 'online', 'upcoming', '2025-07-04 18:30:00', 1),
('B009', 'Cybersecurity Fundamentals', '2025-01-15', '2025-04-15', '09:00-11:00', 'Zoom', 'https://zoom.us/j/2222222222', 20, 18, '2024-2025', 2, 'offline', 'completed', '2024-12-14 18:30:00', 1),
('B010', 'UI/UX Design', '2025-02-20', '2025-05-20', '11:00-13:00', 'Google Meet', 'https://meet.google.com/qwe-rty-uio', 15, 12, '2024-2025', 3, 'offline', 'completed', '2025-01-19 18:30:00', 1),
('B011', 'Web Development Fundamentals', '2025-03-05', '2025-06-05', '14:00-16:00', 'Zoom', 'https://zoom.us/j/3333333333', 25, 22, '2024-2025', 4, 'offline', 'ongoing', '2025-02-04 18:30:00', 1),
('B012', 'Advanced JavaScript', '2025-04-10', '2025-07-10', '16:00-18:00', 'Microsoft Teams', 'https://teams.microsoft.com/l/meetup-join/19%3ameeting_OPQRSTU', 20, 16, '2024-2025', 5, 'offline', 'ongoing', '2025-03-09 18:30:00', 1),
('B013', 'Python Programming', '2025-05-15', '2025-08-15', '09:00-11:00', 'Zoom', 'https://zoom.us/j/4444444444', 15, 10, '2024-2025', 2, 'offline', 'ongoing', '2025-04-14 18:30:00', 1),
('B014', 'Data Science with Python', '2025-06-20', '2025-09-20', '11:00-13:00', 'Google Meet', 'https://meet.google.com/asd-fgh-jkl', 20, 14, '2024-2025', 3, 'offline', 'ongoing', '2025-05-19 18:30:00', 1),
('B015', 'Machine Learning', '2025-07-01', '2025-10-01', '14:00-16:00', 'Zoom', 'https://zoom.us/j/5555555555', 15, 7, '2024-2025', 4, 'offline', 'upcoming', '2025-05-31 18:30:00', 1),
('B016', 'Mobile App Development', '2025-01-05', '2025-04-05', '16:00-18:00', 'Microsoft Teams', 'https://teams.microsoft.com/l/meetup-join/19%3ameeting_VWXYZAB', 20, 18, '2024-2025', 5, 'online', 'completed', '2024-12-04 18:30:00', 1),
('B017', 'Database Design', '2025-02-10', '2025-05-10', '09:00-11:00', 'Zoom', 'https://zoom.us/j/6666666666', 15, 12, '2024-2025', 2, 'online', 'completed', '2025-01-09 18:30:00', 1),
('B018', 'Cloud Computing', '2025-03-15', '2025-06-15', '11:00-13:00', 'Google Meet', 'https://meet.google.com/zxc-vbn-mnb', 25, 20, '2024-2025', 3, 'online', 'ongoing', '2025-02-14 18:30:00', 1),
('B019', 'Cybersecurity Fundamentals', '2025-04-20', '2025-07-20', '14:00-16:00', 'Zoom', 'https://zoom.us/j/7777777777', 20, 16, '2024-2025', 4, 'online', 'ongoing', '2025-03-19 18:30:00', 1),
('B020', 'UI/UX Design', '2025-05-25', '2025-08-25', '16:00-18:00', 'Microsoft Teams', 'https://teams.microsoft.com/l/meetup-join/19%3ameeting_CDEFGHI', 15, 10, '2024-2025', 5, 'online', 'ongoing', '2025-04-24 18:30:00', 1),
('B021', 'Web Development Fundamentals', '2025-06-30', '2025-09-30', '09:00-11:00', 'Zoom', 'https://zoom.us/j/8888888888', 20, 12, '2024-2025', 2, 'online', 'completed', '2025-05-29 18:30:00', 1),
('B022', 'Advanced JavaScript', '2025-01-20', '2025-04-20', '11:00-13:00', 'Google Meet', 'https://meet.google.com/qaz-wsx-edc', 15, 12, '2024-2025', 3, 'offline', 'completed', '2024-12-19 18:30:00', 1),
('B023', 'Python Programming', '2025-02-25', '2025-05-25', '14:00-16:00', 'Zoom', 'https://zoom.us/j/9999999999', 25, 20, '2024-2025', 4, 'offline', 'completed', '2025-01-24 18:30:00', 1),
('B024', 'Data Science with Python', '2025-03-30', '2025-06-30', '16:00-18:00', 'Microsoft Teams', 'https://teams.microsoft.com/l/meetup-join/19%3ameeting_JKLMNOP', 20, 20, '2024-2025', 5, 'offline', 'ongoing', '2025-02-27 18:30:00', 1),
('B025', 'Machine Learning', '2025-05-05', '2025-08-05', '09:00-11:00', 'Zoom', 'https://zoom.us/j/1010101010', 15, 10, '2024-2025', 2, 'offline', 'ongoing', '2025-04-04 18:30:00', 1),
('B026', 'Networking', '2025-07-01', '2025-07-31', '09:00-11:00', 'Google Meet', 'https://meet.google.com/bgx-webq-pvg', 100, 20, '2025-26', 2, 'online', 'ongoing', '2025-07-07 07:41:06', 1),
('B027', 'Cybersecurity Essentials', '2025-08-01', '2025-09-01', '18:00-20:00', 'Google Meet', 'https://meet.google.com/example', 30, 0, '2025-2026', NULL, 'online', 'upcoming', '2025-07-08 07:18:08', 1),
('B028', 'python july batch - H/01', '2025-07-24', '2025-08-19', '9:00 - 10:00', 'Google Meet', 'https://zoom.us/j/8888888888', 15, 4, '2025-26', 2, 'online', 'upcoming', '2025-07-19 05:54:44', 1);

-- --------------------------------------------------------

--
-- Table structure for table `batch_uploads`
--

CREATE TABLE `batch_uploads` (
  `id` int(11) NOT NULL,
  `upload_id` int(11) NOT NULL,
  `batch_id` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_uploads`
--

INSERT INTO `batch_uploads` (`id`, `upload_id`, `batch_id`) VALUES
(1, 1, 'B003'),
(2, 2, 'B003');

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` int(11) NOT NULL,
  `conversation_type` enum('admin_student','admin_batch') NOT NULL,
  `admin_id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `batch_id` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_conversations`
--

INSERT INTO `chat_conversations` (`id`, `conversation_type`, `admin_id`, `student_id`, `batch_id`, `created_at`, `updated_at`) VALUES
(1, 'admin_student', 1, 'STD001', NULL, '2025-07-10 04:30:00', '2025-07-15 11:27:07'),
(2, 'admin_student', 1, 'STD002', NULL, '2025-07-10 05:30:00', '2025-07-15 05:38:38'),
(3, 'admin_batch', 1, NULL, 'B001', '2025-07-10 06:30:00', '2025-07-15 11:26:35'),
(22, 'admin_student', 1, 'STD027', NULL, '2025-07-18 05:54:36', '2025-07-18 05:56:12'),
(23, 'admin_batch', 1, NULL, 'B002', '2025-07-19 05:44:14', '2025-07-19 05:44:30'),
(24, 'admin_batch', 1, NULL, 'B020', '2025-07-19 06:15:06', '2025-07-19 18:33:46'),
(25, 'admin_student', 1, 'STD028', NULL, '2025-07-19 07:34:42', '2025-07-19 07:34:42'),
(26, 'admin_batch', 1, NULL, 'B004', '2025-07-19 07:34:54', '2025-07-19 07:34:58');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `conversation_id`, `sender_id`, `message`, `is_read`, `sent_at`) VALUES
(1, 1, 1, 'Hello Alice, how can I help you today?', 1, '2025-07-10 04:35:00'),
(2, 1, 6, 'Hi Admin, I\'m having trouble with the assignment.', 1, '2025-07-10 04:40:00'),
(3, 1, 1, 'Which part are you stuck on?', 1, '2025-07-10 04:42:00'),
(4, 1, 6, 'The JavaScript DOM manipulation part.', 1, '2025-07-10 04:45:00'),
(5, 2, 7, 'Admin, I need to request leave next week.', 1, '2025-07-10 05:35:00'),
(6, 2, 1, 'How many days will you be absent?', 1, '2025-07-10 05:40:00'),
(7, 2, 7, 'Just one day, on Wednesday.', 1, '2025-07-10 05:42:00'),
(8, 3, 1, 'Hello everyone in Batch B001!', 1, '2025-07-10 06:35:00'),
(9, 3, 6, 'Hi Admin!', 1, '2025-07-10 06:36:00'),
(10, 3, 7, 'Hello!', 1, '2025-07-10 06:36:00'),
(11, 3, 1, 'Just a reminder about the project deadline tomorrow.', 1, '2025-07-10 06:40:00'),
(12, 3, 1, 'Hello everyone', 1, '2025-07-15 05:38:25'),
(13, 2, 1, 'Hi', 0, '2025-07-15 05:38:38'),
(21, 3, 6, 'Welcome Raj! I can share my notes with you.', 1, '2025-07-10 02:42:00'),
(28, 3, 1, 'Hi', 1, '2025-07-15 07:34:54'),
(30, 3, 1, 'OK', 1, '2025-07-15 08:07:15'),
(31, 3, 57, 'Hi', 1, '2025-07-15 08:27:05'),
(32, 3, 57, 'What are you doing?', 1, '2025-07-15 08:27:58'),
(33, 1, 1, 'Hello Alice', 1, '2025-07-15 11:26:15'),
(34, 3, 1, 'Hello everyone', 1, '2025-07-15 11:26:28'),
(35, 3, 1, 'What are you doing?', 1, '2025-07-15 11:26:35'),
(36, 1, 6, 'Hello', 1, '2025-07-15 11:27:07'),
(37, 22, 1, 'Hi', 0, '2025-07-18 05:56:12'),
(38, 23, 1, 'hello students', 1, '2025-07-19 05:44:30'),
(39, 24, 1, 'hii', 1, '2025-07-19 06:15:09'),
(40, 24, 11, 'hllo', 1, '2025-07-19 06:16:18'),
(41, 26, 1, 'hiiii', 0, '2025-07-19 07:34:58'),
(42, 24, 57, 'Kya hua', 1, '2025-07-19 18:33:46');

-- --------------------------------------------------------

--
-- Table structure for table `chat_participants`
--

CREATE TABLE `chat_participants` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_participants`
--

INSERT INTO `chat_participants` (`id`, `conversation_id`, `user_id`, `joined_at`) VALUES
(1, 1, 1, '2025-07-10 04:30:00'),
(2, 1, 6, '2025-07-10 04:30:00'),
(3, 2, 1, '2025-07-10 05:30:00'),
(4, 2, 7, '2025-07-10 05:30:00'),
(5, 3, 1, '2025-07-10 06:30:00'),
(6, 3, 6, '2025-07-10 06:30:00'),
(7, 3, 7, '2025-07-10 06:30:00'),
(8, 3, 8, '2025-07-10 06:30:00'),
(9, 3, 9, '2025-07-10 06:30:00'),
(10, 3, 10, '2025-07-10 06:30:00'),
(22, 22, 1, '2025-07-18 05:54:36'),
(23, 22, 32, '2025-07-18 05:54:36'),
(24, 23, 1, '2025-07-19 05:44:14'),
(25, 23, 11, '2025-07-19 05:44:14'),
(26, 23, 12, '2025-07-19 05:44:14'),
(27, 23, 13, '2025-07-19 05:44:14'),
(28, 23, 14, '2025-07-19 05:44:14'),
(29, 23, 15, '2025-07-19 05:44:14'),
(30, 24, 1, '2025-07-19 06:15:06'),
(31, 24, 11, '2025-07-19 06:15:06'),
(32, 24, 12, '2025-07-19 06:15:06'),
(33, 24, 13, '2025-07-19 06:15:06'),
(34, 24, 57, '2025-07-19 06:15:06'),
(35, 25, 1, '2025-07-19 07:34:42'),
(36, 25, 33, '2025-07-19 07:34:42'),
(37, 26, 1, '2025-07-19 07:34:54'),
(38, 26, 21, '2025-07-19 07:34:54'),
(39, 26, 22, '2025-07-19 07:34:54'),
(40, 26, 23, '2025-07-19 07:34:54'),
(41, 26, 25, '2025-07-19 07:34:54'),
(42, 26, 59, '2025-07-19 07:34:54');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `name`) VALUES
(1, 'Web Development Fundamentals'),
(2, 'Advanced JavaScript'),
(3, 'Python Programming'),
(4, 'Data Science with Python'),
(5, 'Machine Learning'),
(6, 'Mobile App Development'),
(7, 'Database Design'),
(8, 'Cloud Computing'),
(9, 'Cybersecurity Fundamentals'),
(10, 'UI/UX Design');

-- --------------------------------------------------------

--
-- Table structure for table `exam_students`
--

CREATE TABLE `exam_students` (
  `exam_id` varchar(10) NOT NULL,
  `student_name` varchar(50) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `is_malpractice` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_students`
--

INSERT INTO `exam_students` (`exam_id`, `student_name`, `score`, `is_malpractice`, `notes`) VALUES
('EXAM001', 'Alice Williams', 85.50, 0, 'Good performance on practical section'),
('EXAM001', 'Bob Miller', 78.25, 0, 'Needs improvement on JavaScript questions'),
('EXAM001', 'Charlie Wilson', 92.00, 0, 'Excellent work'),
('EXAM001', 'David Moore', 65.75, 0, 'Struggled with CSS concepts'),
('EXAM001', 'Eva Taylor', 88.00, 0, 'Strong understanding of HTML'),
('EXAM002', 'Frank Anderson', 91.25, 0, 'Excellent grasp of advanced concepts'),
('EXAM002', 'Grace Thomas', 84.50, 0, 'Good work, minor syntax errors'),
('EXAM002', 'Henry Jackson', 76.75, 1, 'Caught sharing code with another student'),
('EXAM002', 'Ivy White', 82.00, 0, 'Solid understanding of promises'),
('EXAM002', 'Jack Harris', 89.50, 0, 'Very creative solutions'),
('EXAM003', 'Karen Martin', 94.00, 0, 'Exceptional problem-solving skills'),
('EXAM003', 'Leo Garcia', 79.25, 0, 'Needs more practice with OOP'),
('EXAM003', 'Mia Martinez', 86.75, 0, 'Good work on data structures'),
('EXAM003', 'Noah Robinson', 72.50, 0, 'Struggled with recursion problems'),
('EXAM003', 'Olivia Clark', 90.00, 0, 'Excellent implementation of algorithms'),
('EXAM004', 'Oscar Evans', 87.25, 0, 'Strong practical skills'),
('EXAM004', 'Penny Edwards', 83.50, 0, 'Good theoretical knowledge'),
('EXAM004', 'Quentin Collins', 95.00, 0, 'Outstanding performance'),
('EXAM004', 'Rose Stewart', 81.75, 0, 'Needs more practice with tools'),
('EXAM004', 'Simon Sanchez', 89.00, 0, 'Excellent analysis skills'),
('EXAM005', 'Tara Morris', 85.00, 0, 'Creative design solutions'),
('EXAM005', 'Ulysses Rogers', 77.50, 1, 'Used unauthorized design templates'),
('EXAM005', 'Vera Reed', 92.25, 0, 'Excellent user flow designs'),
('EXAM005', 'Wade Cook', 80.75, 1, 'Copied color scheme from another student'),
('EXAM005', 'Xander Morgan', 88.50, 0, 'Strong portfolio presentation'),
('EXAM007', 'Peter Rodriguez', 84.25, 0, 'Good normalization skills'),
('EXAM007', 'Quinn Lewis', 91.50, 0, 'Excellent query optimization'),
('EXAM007', 'Rachel Lee', 78.75, 0, 'Needs more practice with joins'),
('EXAM007', 'Samuel Walker', 82.00, 0, 'Solid understanding of indexes'),
('EXAM007', 'Tina Hall', 86.25, 0, 'Good work on transaction concepts'),
('EXAM009', 'Adam Scott', 83.50, 0, 'Good HTML/CSS implementation'),
('EXAM009', 'Bella Green', 88.75, 0, 'Excellent JavaScript functionality'),
('EXAM009', 'Caleb Adams', 76.25, 0, 'Needs more responsive design practice'),
('EXAM009', 'Diana Baker', 90.00, 0, 'Outstanding project'),
('EXAM009', 'Ethan Gonzalez', 81.50, 0, 'Good work, minor bugs'),
('EXAM010', 'Fiona Nelson', 93.25, 0, 'Excellent algorithm implementation'),
('EXAM010', 'George Carter', 85.75, 0, 'Good work on data structures'),
('EXAM010', 'Hannah Mitchell', 79.50, 1, 'Caught using external code without attribution'),
('EXAM010', 'Ian Perez', 87.00, 0, 'Strong problem-solving approach'),
('EXAM010', 'Julia Roberts', 91.75, 0, 'Very clean and efficient code');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `student_name` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `batch_id` varchar(10) NOT NULL,
  `is_regular` enum('Yes','No') DEFAULT NULL,
  `class_rating` tinyint(1) DEFAULT NULL,
  `assignment_understanding` tinyint(1) DEFAULT NULL,
  `practical_understanding` tinyint(1) DEFAULT NULL,
  `satisfied` tinyint(1) DEFAULT NULL,
  `suggestions` text DEFAULT NULL,
  `course_name` varchar(50) NOT NULL,
  `rating` tinyint(1) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `feedback_text` text DEFAULT NULL,
  `action_taken` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `date`, `student_name`, `email`, `batch_id`, `is_regular`, `class_rating`, `assignment_understanding`, `practical_understanding`, `satisfied`, `suggestions`, `course_name`, `rating`, `feedback_text`, `action_taken`) VALUES
(1, '2025-04-01', 'Alice Williams', 'alice.williams@student.com', 'B001', 'Yes', 5, 4, 5, 5, 'More practical examples would be helpful', 'Web Development Fundamentals', 4, 'Great course overall, learned a lot about web development basics.', 'Instructor added more practical examples to the curriculum'),
(2, '2025-04-02', 'Bob Miller', 'bob.miller@student.com', 'B001', 'Yes', 4, 3, 4, 4, 'Pace was a bit fast sometimes', 'Web Development Fundamentals', 4, 'Good course, but sometimes the instructor went too fast through concepts.', 'Instructor adjusted pace based on feedback'),
(3, '2025-04-03', 'Charlie Wilson', 'charlie.wilson@student.com', 'B001', 'Yes', 5, 5, 5, 5, 'No suggestions, perfect as is', 'Web Development Fundamentals', 5, 'Excellent course, instructor was very knowledgeable and helpful.', NULL),
(4, '2025-04-04', 'David Moore', 'david.moore@student.com', 'B001', 'Yes', 3, 3, 3, 3, 'Need more support for beginners', 'Web Development Fundamentals', 3, 'Course was okay, but as a complete beginner I struggled sometimes.', 'Added beginner resources to course materials'),
(5, '2025-04-05', 'Eva Taylor', 'eva.taylor@student.com', 'B001', 'Yes', 5, 4, 5, 5, 'More group projects would be nice', 'Web Development Fundamentals', 5, 'Really enjoyed the course, learned a lot and had fun doing it.', 'Added one more group project to curriculum'),
(6, '2025-05-01', 'Frank Anderson', 'frank.anderson@student.com', 'B002', 'Yes', 5, 5, 5, 5, 'More advanced topics would be great', 'Advanced JavaScript', 5, 'Fantastic course, really deepened my understanding of JavaScript.', 'Instructor provided additional advanced resources'),
(7, '2025-05-02', 'Grace Thomas', 'grace.thomas@student.com', 'B002', 'Yes', 4, 4, 4, 4, 'Some concepts could use more explanation', 'Advanced JavaScript', 4, 'Very good course, but some advanced concepts were hard to grasp.', 'Instructor added more explanation for complex topics'),
(8, '2025-05-03', 'Henry Jackson', 'henry.jackson@student.com', 'B002', 'Yes', 5, 5, 5, 5, 'No suggestions, loved it', 'Advanced JavaScript', 5, 'One of the best courses I\'ve taken, instructor was amazing.', NULL),
(9, '2025-05-04', 'Ivy White', 'ivy.white@student.com', 'B002', 'Yes', 4, 3, 4, 4, 'More practical exercises would help', 'Advanced JavaScript', 4, 'Good course, but would benefit from more hands-on practice.', 'Added two more practical exercises'),
(10, '2025-05-05', 'Jack Harris', 'jack.harris@student.com', 'B002', 'Yes', 5, 5, 5, 5, 'Maybe extend the course duration', 'Advanced JavaScript', 5, 'Excellent content, wish the course was longer to cover even more!', NULL),
(11, '2025-06-01', 'Karen Martin', 'karen.martin@student.com', 'B003', 'Yes', 5, 4, 5, 5, 'More real-world examples would be great', 'Python Programming', 5, 'Really enjoying the course, Python is fascinating!', 'Instructor added more real-world case studies'),
(12, '2025-06-02', 'Leo Garcia', 'leo.garcia@student.com', 'B003', 'Yes', 4, 4, 4, 4, 'Sometimes the lectures run over time', 'Python Programming', 4, 'Good course, but lectures sometimes go longer than scheduled.', 'Instructor became more time-conscious'),
(13, '2025-06-03', 'Mia Martinez', 'mia.martinez@student.com', 'B003', 'Yes', 5, 5, 5, 5, 'No suggestions, perfect course', 'Python Programming', 5, 'Absolutely love this course and the instructor\'s teaching style.', 'Okay'),
(14, '2025-06-04', 'Noah Robinson', 'noah.robinson@student.com', 'B003', 'Yes', 3, 3, 3, 3, 'Need more support for beginners', 'Python Programming', 3, 'Course is good but challenging for someone new to programming.', 'Added beginner-friendly resources and office hours'),
(15, '2025-06-05', 'Olivia Clark', 'olivia.clark@student.com', 'B003', 'Yes', 5, 5, 5, 5, 'More group coding sessions would be fun', 'Python Programming', 5, 'Fantastic course, learned so much about Python!', 'Added weekly group coding sessions'),
(16, '2025-04-10', 'Oscar Evans', 'oscar.evans@student.com', 'B009', 'Yes', 5, 5, 5, 5, 'More hands-on security exercises', 'Cybersecurity Fundamentals', 5, 'Excellent course, very relevant to today\'s security challenges.', 'Added two more security labs'),
(17, '2025-04-11', 'Penny Edwards', 'penny.edwards@student.com', 'B009', 'Yes', 4, 4, 4, 4, 'Some topics could use more depth', 'Cybersecurity Fundamentals', 4, 'Very informative course, but some topics felt rushed.', 'Instructor provided additional reading materials'),
(18, '2025-04-12', 'Quentin Collins', 'quentin.collins@student.com', 'B009', 'Yes', 5, 5, 5, 5, 'No suggestions, loved everything', 'Cybersecurity Fundamentals', 5, 'Best cybersecurity course I\'ve taken, instructor was brilliant.', NULL),
(19, '2025-04-13', 'Rose Stewart', 'rose.stewart@student.com', 'B009', 'Yes', 4, 3, 4, 4, 'More real-world case studies would help', 'Cybersecurity Fundamentals', 4, 'Great course, but more practical examples would be beneficial.', 'Added three new case studies'),
(20, '2025-04-14', 'Simon Sanchez', 'simon.sanchez@student.com', 'B009', 'Yes', 5, 5, 5, 5, 'Maybe include a certification exam', 'Cybersecurity Fundamentals', 5, 'Extremely valuable course, learned so much about security.', 'Considering adding certification option for next batch'),
(0, '2025-07-01', 'Raj Mishra', 'mishraraj1206@gmail.com', 'B001', 'Yes', 4, 4, 4, 0, 'safasfasfas', 'Web Development Fundamentals', NULL, 'asfasfasfa', 'Okay'),
(0, '2025-07-04', 'Raj Mishra', 'mishraraj1206@gmail.com', 'B001', NULL, 4, 5, 3, 0, 'asdasdasd', 'Web Development Fundamentals', NULL, 'asdasdasd', 'Okay'),
(0, '2025-07-19', 'Raj Mishra', 'mishraraj1206@gmail.com', 'B020', 'Yes', 4, 5, 5, 0, 'lplplplplp', 'UI/UX Design', NULL, 'lplplplplplplpllpp', NULL),
(0, '2025-07-19', 'Raj Mishra', 'mishraraj1206@gmail.com', 'B020', 'Yes', 4, 5, 5, 0, 'lplplplplp', 'UI/UX Design', NULL, 'lplplplplplplpllpp', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('feedback','message') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proctored_exams`
--

CREATE TABLE `proctored_exams` (
  `exam_id` varchar(10) NOT NULL,
  `batch_id` varchar(10) NOT NULL,
  `exam_date` date NOT NULL,
  `mode` enum('Online','Offline') NOT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Minutes',
  `proctor_name` varchar(50) DEFAULT NULL,
  `malpractice_cases` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proctored_exams`
--

INSERT INTO `proctored_exams` (`exam_id`, `batch_id`, `exam_date`, `mode`, `duration`, `proctor_name`, `malpractice_cases`) VALUES
('EXAM001', 'B001', '2025-02-15', 'Online', 120, 'John Smith', 0),
('EXAM002', 'B002', '2025-03-20', 'Online', 90, 'Emily Johnson', 1),
('EXAM003', 'B003', '2025-04-25', 'Online', 120, 'Michael Brown', 0),
('EXAM004', 'B009', '2025-02-28', 'Offline', 180, 'Sarah Davis', 0),
('EXAM005', 'B010', '2025-04-05', 'Online', 90, 'John Smith', 2),
('EXAM006', 'B011', '2025-05-10', 'Online', 120, 'Emily Johnson', 0),
('EXAM007', 'B016', '2025-03-15', 'Offline', 180, 'Michael Brown', 1),
('EXAM008', 'B017', '2025-04-20', 'Online', 90, 'Sarah Davis', 0),
('EXAM009', 'B022', '2025-03-25', 'Offline', 120, 'John Smith', 0),
('EXAM010', 'B023', '2025-05-05', 'Online', 180, 'Emily Johnson', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` varchar(12) NOT NULL,
  `batch_id` varchar(10) NOT NULL,
  `month` date NOT NULL,
  `generated_on` datetime DEFAULT current_timestamp(),
  `report_type` enum('Monthly','Exam','Feedback') NOT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `batch_id`, `month`, `generated_on`, `report_type`, `file_path`) VALUES
('REP202506001', 'B001', '2025-06-01', '2025-06-30 10:00:00', 'Monthly', '/reports/monthly/B001_202506.pdf'),
('REP202506002', 'B002', '2025-06-01', '2025-06-30 10:15:00', 'Monthly', '/reports/monthly/B002_202506.pdf'),
('REP202506003', 'B003', '2025-06-01', '2025-06-30 10:30:00', 'Monthly', '/reports/monthly/B003_202506.pdf'),
('REP202506EX1', 'B001', '2025-06-15', '2025-06-16 11:00:00', 'Exam', '/reports/exams/EXAM003_results.pdf'),
('REP202506FB1', 'B001', '2025-06-01', '2025-06-25 14:00:00', 'Feedback', '/reports/feedback/B001_summary.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `batch_id` varchar(10) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `topic` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_cancelled` tinyint(1) DEFAULT 0,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `batch_id`, `schedule_date`, `start_time`, `end_time`, `topic`, `description`, `is_cancelled`, `cancellation_reason`, `created_at`, `created_by`) VALUES
(23, 'B001', '2025-01-10', '09:00:00', '11:00:00', 'Introduction to HTML', 'Basic HTML tags and structure', 0, NULL, '2025-07-04 13:25:57', 1),
(24, 'B001', '2025-01-12', '09:00:00', '11:00:00', 'HTML Forms and Tables', 'Creating forms and tables in HTML', 0, NULL, '2025-07-04 13:25:57', 1),
(25, 'B001', '2025-01-15', '09:00:00', '11:00:00', 'Introduction to CSS', 'Basic CSS styling and selectors', 0, NULL, '2025-07-04 13:25:57', 1),
(26, 'B001', '2025-01-17', '09:00:00', '11:00:00', 'CSS Layouts', 'Box model and positioning', 0, NULL, '2025-07-04 13:25:57', 1),
(27, 'B001', '2025-01-19', '09:00:00', '11:00:00', 'Responsive Design', 'Media queries and responsive techniques', 0, NULL, '2025-07-04 13:25:57', 1),
(28, 'B001', '2025-01-22', '09:00:00', '11:00:00', 'Introduction to JavaScript', 'Basic JavaScript syntax', 0, NULL, '2025-07-04 13:25:57', 1),
(29, 'B001', '2025-01-24', '09:00:00', '11:00:00', 'DOM Manipulation', 'Working with the Document Object Model', 0, NULL, '2025-07-04 13:25:57', 1),
(30, 'B001', '2025-01-26', '09:00:00', '11:00:00', 'JavaScript Events', 'Event handling in JavaScript', 0, NULL, '2025-07-04 13:25:57', 1),
(31, 'B001', '2025-01-29', '09:00:00', '11:00:00', 'Introduction to Bootstrap', 'Using Bootstrap framework', 0, NULL, '2025-07-04 13:25:57', 1),
(32, 'B001', '2025-01-31', '09:00:00', '11:00:00', 'Project Work', 'Building a small website', 0, NULL, '2025-07-04 13:25:57', 1),
(33, 'B001', '2025-02-02', '09:00:00', '11:00:00', 'Project Presentation', 'Students present their projects', 0, NULL, '2025-07-04 13:25:57', 1),
(34, 'B001', '2025-02-05', '09:00:00', '11:00:00', 'Review Session', 'Review of all concepts', 0, NULL, '2025-07-04 13:25:57', 1),
(35, 'B001', '2025-02-07', '09:00:00', '11:00:00', 'Final Exam Preparation', 'Practice questions and Q&A', 0, NULL, '2025-07-04 13:25:57', 1),
(36, 'B001', '2025-02-09', '09:00:00', '11:00:00', 'Final Exam', 'Web Development Fundamentals Exam', 0, NULL, '2025-07-04 13:25:57', 1),
(37, 'B001', '2025-02-12', '09:00:00', '11:00:00', 'Exam Review', 'Going over exam results', 0, NULL, '2025-07-04 13:25:57', 1),
(38, 'B001', '2025-02-14', '09:00:00', '11:00:00', 'Advanced Topics', 'Introduction to backend concepts', 0, NULL, '2025-07-04 13:25:57', 1),
(39, 'B001', '2025-02-16', '09:00:00', '11:00:00', 'Career Guidance', 'Web development career paths', 0, NULL, '2025-07-04 13:25:57', 1),
(40, 'B001', '2025-02-19', '09:00:00', '11:00:00', 'Guest Lecture', 'Industry professional shares insights', 0, NULL, '2025-07-04 13:25:57', 1),
(41, 'B001', '2025-02-21', '09:00:00', '11:00:00', 'Portfolio Building', 'Creating student portfolios', 0, NULL, '2025-07-04 13:25:57', 1),
(42, 'B001', '2025-02-23', '09:00:00', '11:00:00', 'Final Project Work', 'Working on final projects', 0, NULL, '2025-07-04 13:25:57', 1),
(43, 'B001', '2025-02-26', '09:00:00', '11:00:00', 'Final Project Presentations', 'Students present final projects', 0, NULL, '2025-07-04 13:25:57', 1),
(44, 'B001', '2025-02-28', '09:00:00', '11:00:00', 'Course Wrap-up', 'Course review and feedback', 0, NULL, '2025-07-04 13:25:57', 1),
(45, 'B001', '2025-03-03', '09:00:00', '11:00:00', 'Extra Session', 'Additional Q&A time', 0, NULL, '2025-07-04 13:25:57', 1),
(46, 'B001', '2025-03-05', '09:00:00', '11:00:00', 'Certification', 'Certificate distribution', 0, NULL, '2025-07-04 13:25:57', 1),
(47, 'B002', '2025-02-15', '11:00:00', '13:00:00', 'JavaScript Review', 'Review of core JavaScript concepts', 0, NULL, '2025-07-04 13:25:57', 1),
(48, 'B002', '2025-02-17', '11:00:00', '13:00:00', 'ES6 Features', 'Arrow functions, template literals, etc.', 0, NULL, '2025-07-04 13:25:57', 1),
(49, 'B002', '2025-02-19', '11:00:00', '13:00:00', 'Destructuring', 'Object and array destructuring', 0, NULL, '2025-07-04 13:25:57', 1),
(50, 'B002', '2025-02-21', '11:00:00', '13:00:00', 'Promises', 'Working with asynchronous code', 0, NULL, '2025-07-04 13:25:57', 1),
(51, 'B002', '2025-02-23', '11:00:00', '13:00:00', 'Async/Await', 'Modern asynchronous patterns', 0, NULL, '2025-07-04 13:25:57', 1),
(52, 'B002', '2025-02-25', '11:00:00', '13:00:00', 'Modules', 'ES6 modules and imports/exports', 0, NULL, '2025-07-04 13:25:57', 1),
(53, 'B002', '2025-02-27', '11:00:00', '13:00:00', 'Functional Programming', 'Pure functions, higher-order functions', 0, NULL, '2025-07-04 13:25:57', 1),
(54, 'B002', '2025-03-01', '11:00:00', '13:00:00', 'Closures', 'Understanding closures in JavaScript', 0, NULL, '2025-07-04 13:25:57', 1),
(55, 'B002', '2025-03-03', '11:00:00', '13:00:00', 'Prototypes', 'Prototypal inheritance', 0, NULL, '2025-07-04 13:25:57', 1),
(56, 'B002', '2025-03-05', '11:00:00', '13:00:00', 'Classes', 'ES6 class syntax', 0, NULL, '2025-07-04 13:25:57', 1),
(57, 'B002', '2025-03-07', '11:00:00', '13:00:00', 'Error Handling', 'Try/catch and error management', 0, NULL, '2025-07-04 13:25:57', 1),
(58, 'B002', '2025-03-09', '11:00:00', '13:00:00', 'Regular Expressions', 'Pattern matching with regex', 0, NULL, '2025-07-04 13:25:57', 1),
(59, 'B002', '2025-03-11', '11:00:00', '13:00:00', 'Working with APIs', 'Fetch API and AJAX', 0, NULL, '2025-07-04 13:25:57', 1),
(60, 'B002', '2025-03-13', '11:00:00', '13:00:00', 'Local Storage', 'Client-side data storage', 0, NULL, '2025-07-04 13:25:57', 1),
(61, 'B002', '2025-03-15', '11:00:00', '13:00:00', 'Project Work', 'Building a JavaScript application', 0, NULL, '2025-07-04 13:25:57', 1),
(62, 'B002', '2025-03-17', '11:00:00', '13:00:00', 'Project Work', 'Continued project development', 0, NULL, '2025-07-04 13:25:57', 1),
(63, 'B002', '2025-03-19', '11:00:00', '13:00:00', 'Code Review', 'Reviewing student projects', 0, NULL, '2025-07-04 13:25:57', 1),
(64, 'B002', '2025-03-21', '11:00:00', '13:00:00', 'Testing JavaScript', 'Introduction to testing', 0, NULL, '2025-07-04 13:25:57', 1),
(65, 'B002', '2025-03-23', '11:00:00', '13:00:00', 'Debugging', 'Debugging techniques and tools', 0, NULL, '2025-07-04 13:25:57', 1),
(66, 'B002', '2025-03-25', '11:00:00', '13:00:00', 'Performance Optimization', 'Making JavaScript faster', 0, NULL, '2025-07-04 13:25:57', 1),
(67, 'B002', '2025-03-27', '11:00:00', '13:00:00', 'Security Best Practices', 'Writing secure JavaScript', 0, NULL, '2025-07-04 13:25:57', 1),
(68, 'B002', '2025-03-29', '11:00:00', '13:00:00', 'Final Project Presentations', 'Students present projects', 0, NULL, '2025-07-04 13:25:57', 1),
(69, 'B002', '2025-03-31', '11:00:00', '13:00:00', 'Course Wrap-up', 'Review and feedback', 0, NULL, '2025-07-04 13:25:57', 1),
(70, 'B002', '2025-04-02', '11:00:00', '13:00:00', 'Certification', 'Certificate distribution', 0, NULL, '2025-07-04 13:25:57', 1),
(71, 'B003', '2025-03-01', '14:00:00', '16:00:00', 'Introduction to Python', 'Python syntax and basics', 0, NULL, '2025-07-04 13:25:57', 1),
(72, 'B003', '2025-03-03', '14:00:00', '16:00:00', 'Variables and Data Types', 'Working with different data types', 0, NULL, '2025-07-04 13:25:57', 1),
(73, 'B003', '2025-03-05', '14:00:00', '16:00:00', 'Control Flow', 'If statements and loops', 0, NULL, '2025-07-04 13:25:57', 1),
(74, 'B003', '2025-03-07', '14:00:00', '16:00:00', 'Functions', 'Defining and using functions', 0, NULL, '2025-07-04 13:25:57', 1),
(75, 'B003', '2025-03-09', '14:00:00', '16:00:00', 'Lists and Tuples', 'Working with sequences', 0, NULL, '2025-07-04 13:25:57', 1),
(76, 'B003', '2025-03-11', '14:00:00', '16:00:00', 'Dictionaries and Sets', 'Key-value pairs and unique collections', 0, NULL, '2025-07-04 13:25:57', 1),
(77, 'B003', '2025-03-13', '14:00:00', '16:00:00', 'File Handling', 'Reading and writing files', 0, NULL, '2025-07-04 13:25:57', 1),
(78, 'B003', '2025-03-15', '14:00:00', '16:00:00', 'Exception Handling', 'Try/except blocks', 0, NULL, '2025-07-04 13:25:57', 1),
(79, 'B003', '2025-03-17', '14:00:00', '16:00:00', 'Modules and Packages', 'Organizing Python code', 0, NULL, '2025-07-04 13:25:57', 1),
(80, 'B003', '2025-03-19', '14:00:00', '16:00:00', 'Object-Oriented Programming', 'Classes and objects', 0, NULL, '2025-07-04 13:25:57', 1),
(81, 'B003', '2025-03-21', '14:00:00', '16:00:00', 'Inheritance and Polymorphism', 'OOP advanced concepts', 0, NULL, '2025-07-04 13:25:57', 1),
(82, 'B003', '2025-03-23', '14:00:00', '16:00:00', 'Working with APIs', 'Making HTTP requests', 0, NULL, '2025-07-04 13:25:57', 1),
(83, 'B003', '2025-03-25', '14:00:00', '16:00:00', 'Database Connectivity', 'SQLite with Python', 0, NULL, '2025-07-04 13:25:57', 1),
(84, 'B003', '2025-03-27', '14:00:00', '16:00:00', 'Virtual Environments', 'Managing project dependencies', 0, NULL, '2025-07-04 13:25:57', 1),
(85, 'B003', '2025-03-29', '14:00:00', '16:00:00', 'Project Work', 'Building a Python application', 0, NULL, '2025-07-04 13:25:57', 1),
(86, 'B003', '2025-03-31', '14:00:00', '16:00:00', 'Project Work', 'Continued development', 0, NULL, '2025-07-04 13:25:57', 1),
(87, 'B003', '2025-04-02', '14:00:00', '16:00:00', 'Testing in Python', 'Unit testing basics', 0, NULL, '2025-07-04 13:25:57', 1),
(88, 'B003', '2025-04-04', '14:00:00', '16:00:00', 'Debugging', 'Python debugging techniques', 0, NULL, '2025-07-04 13:25:57', 1),
(89, 'B003', '2025-04-06', '14:00:00', '16:00:00', 'Performance Optimization', 'Making Python code faster', 0, NULL, '2025-07-04 13:25:57', 1),
(90, 'B003', '2025-04-08', '14:00:00', '16:00:00', 'Security Best Practices', 'Writing secure Python code', 0, NULL, '2025-07-04 13:25:57', 1),
(91, 'B003', '2025-04-10', '14:00:00', '16:00:00', 'Final Project Presentations', 'Students present projects', 0, NULL, '2025-07-04 13:25:57', 1),
(92, 'B003', '2025-04-12', '14:00:00', '16:00:00', 'Course Wrap-up', 'Review and feedback', 0, NULL, '2025-07-04 13:25:57', 1),
(93, 'B003', '2025-04-14', '14:00:00', '16:00:00', 'Certification', 'Certificate distribution', 0, NULL, '2025-07-04 13:25:57', 1),
(94, 'B004', '2025-04-05', '16:00:00', '18:00:00', 'Python for Data Science', 'Python basics for data analysis', 0, NULL, '2025-07-04 13:25:57', 1),
(95, 'B004', '2025-04-07', '16:00:00', '18:00:00', 'NumPy Fundamentals', 'Numerical computing with NumPy', 0, NULL, '2025-07-04 13:25:57', 1),
(96, 'B004', '2025-04-09', '16:00:00', '18:00:00', 'Pandas Introduction', 'Data manipulation with Pandas', 0, NULL, '2025-07-04 13:25:57', 1),
(97, 'B004', '2025-04-11', '16:00:00', '18:00:00', 'Data Cleaning', 'Handling missing data', 0, NULL, '2025-07-04 13:25:57', 1),
(98, 'B004', '2025-04-13', '16:00:00', '18:00:00', 'Data Visualization', 'Matplotlib and Seaborn', 0, NULL, '2025-07-04 13:25:57', 1),
(99, 'B004', '2025-04-15', '16:00:00', '18:00:00', 'Exploratory Data Analysis', 'Understanding data patterns', 0, NULL, '2025-07-04 13:25:57', 1),
(100, 'B004', '2025-04-17', '16:00:00', '18:00:00', 'Statistical Analysis', 'Descriptive statistics', 0, NULL, '2025-07-04 13:25:57', 1),
(101, 'B004', '2025-04-19', '16:00:00', '18:00:00', 'Probability Distributions', 'Understanding distributions', 0, NULL, '2025-07-04 13:25:57', 1),
(102, 'B004', '2025-04-21', '16:00:00', '18:00:00', 'Hypothesis Testing', 'Statistical significance', 0, NULL, '2025-07-04 13:25:57', 1),
(103, 'B004', '2025-04-23', '16:00:00', '18:00:00', 'Regression Analysis', 'Linear regression', 0, NULL, '2025-07-04 13:25:57', 1),
(104, 'B004', '2025-04-25', '16:00:00', '18:00:00', 'Classification', 'Logistic regression', 0, NULL, '2025-07-04 13:25:57', 1),
(105, 'B004', '2025-04-27', '16:00:00', '18:00:00', 'Clustering', 'K-means algorithm', 0, NULL, '2025-07-04 13:25:57', 1),
(106, 'B004', '2025-04-29', '16:00:00', '18:00:00', 'Dimensionality Reduction', 'PCA technique', 0, NULL, '2025-07-04 13:25:57', 1),
(107, 'B004', '2025-05-01', '16:00:00', '18:00:00', 'Time Series Analysis', 'Working with time series data', 0, NULL, '2025-07-04 13:25:57', 1),
(108, 'B004', '2025-05-03', '16:00:00', '18:00:00', 'Natural Language Processing', 'Text processing basics', 0, NULL, '2025-07-04 13:25:57', 1),
(109, 'B004', '2025-05-05', '16:00:00', '18:00:00', 'Project Work', 'Data science project', 0, NULL, '2025-07-04 13:25:57', 1),
(110, 'B004', '2025-05-07', '16:00:00', '18:00:00', 'Project Work', 'Continued development', 0, NULL, '2025-07-04 13:25:57', 1),
(111, 'B004', '2025-05-09', '16:00:00', '18:00:00', 'Model Evaluation', 'Metrics for model performance', 0, NULL, '2025-07-04 13:25:57', 1),
(112, 'B004', '2025-05-11', '16:00:00', '18:00:00', 'Deployment', 'Deploying models', 0, NULL, '2025-07-04 13:25:57', 1),
(113, 'B004', '2025-05-13', '16:00:00', '18:00:00', 'Final Project Presentations', 'Students present projects', 0, NULL, '2025-07-04 13:25:57', 1),
(114, 'B004', '2025-05-15', '16:00:00', '18:00:00', 'Course Wrap-up', 'Review and feedback', 0, NULL, '2025-07-04 13:25:57', 1),
(115, 'B004', '2025-05-17', '16:00:00', '18:00:00', 'Certification', 'Certificate distribution', 0, NULL, '2025-07-04 13:25:57', 1),
(116, 'B005', '2025-05-10', '09:00:00', '11:00:00', 'Introduction to ML', 'Machine learning basics', 0, NULL, '2025-07-04 13:25:57', 1),
(117, 'B005', '2025-05-12', '09:00:00', '11:00:00', 'Supervised Learning', 'Regression and classification', 0, NULL, '2025-07-04 13:25:57', 1),
(118, 'B005', '2025-05-14', '09:00:00', '11:00:00', 'Unsupervised Learning', 'Clustering techniques', 0, NULL, '2025-07-04 13:25:57', 1),
(119, 'B005', '2025-05-16', '09:00:00', '11:00:00', 'Feature Engineering', 'Preparing data for ML', 0, NULL, '2025-07-04 13:25:57', 1),
(120, 'B005', '2025-05-18', '09:00:00', '11:00:00', 'Model Evaluation', 'Metrics and validation', 0, NULL, '2025-07-04 13:25:57', 1),
(121, 'B005', '2025-05-20', '09:00:00', '11:00:00', 'Decision Trees', 'Tree-based models', 0, NULL, '2025-07-04 13:25:57', 1),
(122, 'B005', '2025-05-22', '09:00:00', '11:00:00', 'Ensemble Methods', 'Random forests and boosting', 0, NULL, '2025-07-04 13:25:57', 1),
(123, 'B005', '2025-05-24', '09:00:00', '11:00:00', 'Neural Networks', 'Introduction to deep learning', 0, NULL, '2025-07-04 13:25:57', 1),
(124, 'B005', '2025-05-26', '09:00:00', '11:00:00', 'Project Work', 'Building ML models', 0, NULL, '2025-07-04 13:25:57', 1),
(125, 'B005', '2025-05-28', '09:00:00', '11:00:00', 'Project Work', 'Continued development', 0, NULL, '2025-07-04 13:25:57', 1),
(126, 'B005', '2025-05-30', '09:00:00', '11:00:00', 'Hyperparameter Tuning', 'Optimizing models', 0, NULL, '2025-07-04 13:25:57', 1),
(127, 'B005', '2025-06-01', '09:00:00', '11:00:00', 'Model Deployment', 'Putting models into production', 0, NULL, '2025-07-04 13:25:57', 1),
(128, 'B005', '2025-06-03', '09:00:00', '11:00:00', 'Final Project Presentations', 'Students present projects', 0, NULL, '2025-07-04 13:25:57', 1),
(129, 'B005', '2025-06-05', '09:00:00', '11:00:00', 'Course Wrap-up', 'Review and feedback', 0, NULL, '2025-07-04 13:25:57', 1),
(130, 'B005', '2025-06-07', '09:00:00', '11:00:00', 'Certification', 'Certificate distribution', 0, NULL, '2025-07-04 13:25:57', 1),
(131, 'B001', '2025-07-06', '09:00:00', '11:00:00', 'HTML CSS', '', 0, NULL, '2025-07-04 17:44:05', 1);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `enrollment_date` date NOT NULL,
  `current_status` enum('active','dropped','transferred','completed') NOT NULL DEFAULT 'active',
  `course_enrolled` varchar(200) DEFAULT NULL,
  `batch_name` varchar(100) DEFAULT NULL,
  `dropout_date` date DEFAULT NULL,
  `dropout_reason` text DEFAULT NULL,
  `dropout_processed_by` int(11) DEFAULT NULL,
  `dropout_processed_at` datetime DEFAULT NULL,
  `father_name` varchar(200) DEFAULT NULL,
  `father_phone_number` varchar(20) DEFAULT NULL,
  `father_email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `first_name`, `last_name`, `email`, `phone_number`, `date_of_birth`, `enrollment_date`, `current_status`, `course_enrolled`, `batch_name`, `dropout_date`, `dropout_reason`, `dropout_processed_by`, `dropout_processed_at`, `father_name`, `father_phone_number`, `father_email`, `password_hash`, `last_login`, `profile_picture`) VALUES
('STD001', 6, 'Alice', 'Williams', 'alice.williams@student.com', '1234567890', '2000-01-01', '2025-01-05', 'dropped', 'Web Development Fundamentals', 'B024', '2025-07-19', 'yes', 1, '2025-07-19 13:07:13', 'Robert Williams', '1234567891', 'robert.williams@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 08:00:00', '../uploads/profile_pictures/student_6_1752569750.jpg'),
('STD002', 7, 'Bob', 'Miller', 'bob.miller@student.com', '1234567892', '2000-02-02', '2025-01-05', 'active', 'Web Development Fundamentals', 'B024', NULL, NULL, NULL, NULL, 'John Miller', '1234567893', 'john.miller@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 08:15:00', NULL),
('STD003', 8, 'Charlie', 'Wilson', 'charlie.wilson@student.com', '1234567894', '2000-03-03', '2025-01-05', 'active', 'Web Development Fundamentals', 'B001', NULL, NULL, NULL, NULL, 'David Wilson', '1234567895', 'david.wilson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 08:30:00', NULL),
('STD004', 9, 'David', 'Moore', 'david.moore@student.com', '1234567896', '2000-04-04', '2025-01-05', 'active', 'Web Development Fundamentals', 'B001', NULL, NULL, NULL, NULL, 'Richard Moore', '1234567897', 'richard.moore@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 08:45:00', NULL),
('STD005', 10, 'Eva', 'Taylor', 'eva.taylor@student.com', '1234567898', '2000-05-05', '2025-01-05', 'active', 'Web Development Fundamentals', 'B001', NULL, NULL, NULL, NULL, 'Michael Taylor', '1234567899', 'michael.taylor@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 09:00:00', NULL),
('STD006', 11, 'Frank', 'Anderson', 'frank.anderson@student.com', '1234567800', '2000-06-06', '2025-01-10', 'active', 'Advanced JavaScript', 'B020', NULL, NULL, NULL, NULL, 'James Anderson', '1234567801', 'james.anderson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 09:15:00', NULL),
('STD007', 12, 'Grace', 'Thomas', 'grace.thomas@student.com', '1234567802', '2000-07-07', '2025-01-10', 'active', 'Advanced JavaScript', 'B020', NULL, NULL, NULL, NULL, 'William Thomas', '1234567803', 'william.thomas@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 09:30:00', NULL),
('STD008', 13, 'Henry', 'Jackson', 'henry.jackson@student.com', '1234567804', '2000-08-08', '2025-01-10', 'active', 'Advanced JavaScript', 'B020', NULL, NULL, NULL, NULL, 'Charles Jackson', '1234567805', 'charles.jackson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 09:45:00', NULL),
('STD009', 14, 'Ivy', 'White', 'ivy.white@student.com', '1234567806', '2000-09-09', '2025-01-10', 'active', 'Advanced JavaScript', 'B002', NULL, NULL, NULL, NULL, 'Joseph White', '1234567807', 'joseph.white@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 10:00:00', NULL),
('STD010', 15, 'Jack', 'Harris', 'jack.harris@student.com', '1234567808', '2000-10-10', '2025-01-10', 'active', 'Advanced JavaScript', 'B002', NULL, NULL, NULL, NULL, 'Thomas Harris', '1234567809', 'thomas.harris@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 10:15:00', NULL),
('STD011', 16, 'Karen', 'Martin', 'karen.martin@student.com', '1234567810', '2000-11-11', '2025-02-15', 'active', 'Python Programming', 'B003', NULL, NULL, NULL, NULL, 'Daniel Martin', '1234567811', 'daniel.martin@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 10:30:00', '../uploads/profile_pictures/student_STD011_1751737681.jpg'),
('STD012', 17, 'Leo', 'Garcia', 'leo.garcia@student.com', '1234567812', '2000-12-12', '2025-02-15', 'active', 'Python Programming', 'B003', NULL, NULL, NULL, NULL, 'Paul Garcia', '1234567813', 'paul.garcia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 10:45:00', NULL),
('STD013', 18, 'Mia', 'Martinez', 'mia.martinez@student.com', '1234567814', '2001-01-01', '2025-02-15', 'active', 'Python Programming', 'B003', NULL, NULL, NULL, NULL, 'Mark Martinez', '1234567815', 'mark.martinez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 11:00:00', NULL),
('STD014', 19, 'Noah', 'Robinson', 'noah.robinson@student.com', '1234567816', '2001-02-02', '2025-02-15', 'active', 'Python Programming', 'B003', NULL, NULL, NULL, NULL, 'Donald Robinson', '1234567817', 'donald.robinson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 11:15:00', NULL),
('STD015', 20, 'Olivia', 'Clark', 'olivia.clark@student.com', '1234567818', '2001-03-03', '2025-02-15', 'active', 'Python Programming', 'B003', NULL, NULL, NULL, NULL, 'George Clark', '1234567819', 'george.clark@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 11:30:00', NULL),
('STD016', 21, 'Peter', 'Rodriguez', 'peter.rodriguez@student.com', '1234567820', '2001-04-04', '2025-03-01', 'active', 'Data Science with Python', 'B004', NULL, NULL, NULL, NULL, 'Kenneth Rodriguez', '1234567821', 'kenneth.rodriguez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 11:45:00', NULL),
('STD017', 22, 'Quinn', 'Lewis', 'quinn.lewis@student.com', '1234567822', '2001-05-05', '2025-03-01', 'active', 'Data Science with Python', 'B004', NULL, NULL, NULL, NULL, 'Steven Lewis', '1234567823', 'steven.lewis@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 12:00:00', NULL),
('STD018', 23, 'Rachel', 'Lee', 'rachel.lee@student.com', '1234567824', '2001-06-06', '2025-03-01', 'active', 'Data Science with Python', 'B004', NULL, NULL, NULL, NULL, 'Edward Lee', '1234567825', 'edward.lee@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 12:15:00', NULL),
('STD019', 24, 'Samuel', 'Walker', 'samuel.walker@student.com', '1234567826', '2001-07-07', '2025-03-01', 'active', 'Data Science with Python', 'B028', NULL, NULL, NULL, NULL, 'Brian Walker', '1234567827', 'brian.walker@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 12:30:00', NULL),
('STD020', 25, 'Tina', 'Hall', 'tina.hall@student.com', '1234567828', '2001-08-08', '2025-03-01', 'active', 'Data Science with Python', 'B004', NULL, NULL, NULL, NULL, 'Ronald Hall', '1234567829', 'ronald.hall@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 12:45:00', NULL),
('STD021', 26, 'Umar', 'Allen', 'umar.allen@student.com', '1234567830', '2001-09-09', '2025-04-05', 'active', 'Machine Learning', 'B005', NULL, NULL, NULL, NULL, 'Anthony Allen', '1234567831', 'anthony.allen@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 13:00:00', NULL),
('STD022', 27, 'Victoria', 'Young', 'victoria.young@student.com', '1234567832', '2001-10-10', '2025-04-05', 'active', 'Machine Learning', 'B005', NULL, NULL, NULL, NULL, 'Kevin Young', '1234567833', 'kevin.young@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 13:15:00', NULL),
('STD023', 28, 'William', 'Hernandez', 'william.hernandez@student.com', '1234567834', '2001-11-11', '2025-04-05', 'active', 'Machine Learning', 'B005', NULL, NULL, NULL, NULL, 'Jason Hernandez', '1234567835', 'jason.hernandez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 13:30:00', NULL),
('STD024', 29, 'Xena', 'King', 'xena.king@student.com', '1234567836', '2001-12-12', '2025-04-05', 'active', 'Machine Learning', 'B005', NULL, NULL, NULL, NULL, 'Matthew King', '1234567837', 'matthew.king@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 13:45:00', NULL),
('STD025', 30, 'Yusuf', 'Wright', 'yusuf.wright@student.com', '1234567838', '2002-01-01', '2025-04-05', 'active', 'Machine Learning', 'B005', NULL, NULL, NULL, NULL, 'Gary Wright', '1234567839', 'gary.wright@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 14:00:00', NULL),
('STD026', 31, 'Zoe', 'Lopez', 'zoe.lopez@student.com', '1234567840', '2002-02-02', '2025-05-10', 'active', 'Mobile App Development', 'B006', NULL, NULL, NULL, NULL, 'Jeffrey Lopez', '1234567841', 'jeffrey.lopez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 14:15:00', NULL),
('STD027', 32, 'Adam', 'Scott', 'adam.scott@student.com', '1234567842', '2002-03-03', '2025-05-10', 'active', 'Mobile App Development', 'B006', NULL, NULL, NULL, NULL, 'Timothy Scott', '1234567843', 'timothy.scott@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 14:30:00', NULL),
('STD028', 33, 'Bella', 'Green', 'bella.green@student.com', '1234567844', '2002-04-04', '2025-05-10', 'active', 'Mobile App Development', 'B006', NULL, NULL, NULL, NULL, 'Ryan Green', '1234567845', 'ryan.green@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 14:45:00', NULL),
('STD029', 34, 'Caleb', 'Adams', 'caleb.adams@student.com', '1234567846', '2002-05-05', '2025-05-10', 'active', 'Mobile App Development', 'B006', NULL, NULL, NULL, NULL, 'Jacob Adams', '1234567847', 'jacob.adams@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 15:00:00', NULL),
('STD030', 35, 'Diana', 'Baker', 'diana.baker@student.com', '1234567848', '2002-06-06', '2025-05-10', 'active', 'Mobile App Development', 'B006', NULL, NULL, NULL, NULL, 'Nicholas Baker', '1234567849', 'nicholas.baker@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 15:15:00', NULL),
('STD031', 36, 'Ethan', 'Gonzalez', 'ethan.gonzalez@student.com', '1234567850', '2002-07-07', '2025-06-15', 'active', 'Database Design', 'B007', NULL, NULL, NULL, NULL, 'Eric Gonzalez', '1234567851', 'eric.gonzalez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 15:30:00', NULL),
('STD032', 37, 'Fiona', 'Nelson', 'fiona.nelson@student.com', '1234567852', '2002-08-08', '2025-06-15', 'active', 'Database Design', 'B007', NULL, NULL, NULL, NULL, 'Stephen Nelson', '1234567853', 'stephen.nelson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 15:45:00', NULL),
('STD033', 38, 'George', 'Carter', 'george.carter@student.com', '1234567854', '2002-09-09', '2025-06-15', 'active', 'Database Design', 'B007', NULL, NULL, NULL, NULL, 'Andrew Carter', '1234567855', 'andrew.carter@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 16:00:00', NULL),
('STD034', 39, 'Hannah', 'Mitchell', 'hannah.mitchell@student.com', '1234567856', '2002-10-10', '2025-06-15', 'active', 'Database Design', 'B007', NULL, NULL, NULL, NULL, 'Joshua Mitchell', '1234567857', 'joshua.mitchell@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 16:15:00', NULL),
('STD035', 40, 'Ian', 'Perez', 'ian.perez@student.com', '1234567858', '2002-11-11', '2025-06-15', 'active', 'Database Design', 'B007', NULL, NULL, NULL, NULL, 'Brandon Perez', '1234567859', 'brandon.perez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 16:30:00', NULL),
('STD036', 41, 'Julia', 'Roberts', 'julia.roberts@student.com', '1234567860', '2002-12-12', '2025-07-01', 'active', 'Cloud Computing', 'B008', NULL, NULL, NULL, NULL, 'Justin Roberts', '1234567861', 'justin.roberts@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 16:45:00', NULL),
('STD037', 42, 'Kevin', 'Turner', 'kevin.turner@student.com', '1234567862', '2003-01-01', '2025-07-01', 'active', 'Cloud Computing', 'B008', NULL, NULL, NULL, NULL, 'Benjamin Turner', '1234567863', 'benjamin.turner@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 17:00:00', NULL),
('STD038', 43, 'Lily', 'Phillips', 'lily.phillips@student.com', '1234567864', '2003-02-02', '2025-07-01', 'active', 'Cloud Computing', 'B008', NULL, NULL, NULL, NULL, 'Samuel Phillips', '1234567865', 'samuel.phillips@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 17:15:00', NULL),
('STD039', 44, 'Max', 'Campbell', 'max.campbell@student.com', '1234567866', '2003-03-03', '2025-07-01', 'active', 'Cloud Computing', 'B008', NULL, NULL, NULL, NULL, 'Gregory Campbell', '1234567867', 'gregory.campbell@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 17:30:00', NULL),
('STD040', 45, 'Nora', 'Parker', 'nora.parker@student.com', '1234567868', '2003-04-04', '2025-07-01', 'active', 'Cloud Computing', 'B008', NULL, NULL, NULL, NULL, 'Alexander Parker', '1234567869', 'alexander.parker@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 17:45:00', NULL),
('STD041', 46, 'Oscar', 'Evans', 'oscar.evans@student.com', '1234567870', '2003-05-05', '2025-01-15', 'active', 'Cybersecurity Fundamentals', 'B009', NULL, NULL, NULL, NULL, 'Frank Evans', '1234567871', 'frank.evans@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 18:00:00', NULL),
('STD042', 47, 'Penny', 'Edwards', 'penny.edwards@student.com', '1234567872', '2003-06-06', '2025-01-15', 'active', 'Cybersecurity Fundamentals', 'B009', NULL, NULL, NULL, NULL, 'Patrick Edwards', '1234567873', 'patrick.edwards@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 18:15:00', NULL),
('STD043', 48, 'Quentin', 'Collins', 'quentin.collins@student.com', '1234567874', '2003-07-07', '2025-01-15', 'active', 'Cybersecurity Fundamentals', 'B009', NULL, NULL, NULL, NULL, 'Raymond Collins', '1234567875', 'raymond.collins@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 18:30:00', NULL),
('STD044', 49, 'Rose', 'Stewart', 'rose.stewart@student.com', '1234567876', '2003-08-08', '2025-01-15', 'active', 'Cybersecurity Fundamentals', 'B028', NULL, NULL, NULL, NULL, 'Jack Stewart', '1234567877', 'jack.stewart@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 18:45:00', NULL),
('STD045', 50, 'Simon', 'Sanchez', 'simon.sanchez@student.com', '1234567878', '2003-09-09', '2025-01-15', 'active', 'Cybersecurity Fundamentals', 'B028', NULL, NULL, NULL, NULL, 'Dennis Sanchez', '1234567879', 'dennis.sanchez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 19:00:00', NULL),
('STD046', 51, 'Tara', 'Morris', 'tara.morris@student.com', '1234567880', '2003-10-10', '2025-02-20', 'active', 'UI/UX Design', 'B010', NULL, NULL, NULL, NULL, 'Jerry Morris', '1234567881', 'jerry.morris@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 19:15:00', NULL),
('STD047', 52, 'Ulysses', 'Rogers', 'ulysses.rogers@student.com', '1234567882', '2003-11-11', '2025-02-20', 'active', 'UI/UX Design', 'B010', NULL, NULL, NULL, NULL, 'Walter Rogers', '1234567883', 'walter.rogers@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 19:30:00', NULL),
('STD048', 53, 'Vera', 'Reed', 'vera.reed@student.com', '1234567884', '2003-12-12', '2025-02-20', 'active', 'UI/UX Design', 'B010', NULL, NULL, NULL, NULL, 'Henry Reed', '1234567885', 'henry.reed@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 19:45:00', NULL),
('STD049', 54, 'Wade', 'Cook', 'wade.cook@student.com', '1234567886', '2004-01-01', '2025-02-20', 'active', 'UI/UX Design', 'B010', NULL, NULL, NULL, NULL, 'Peter Cook', '1234567887', 'peter.cook@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 20:00:00', NULL),
('STD050', 55, 'Xander', 'Morgan', 'xander.morgan@student.com', '1234567888', '2004-02-02', '2025-02-20', 'active', 'UI/UX Design', 'B010', NULL, NULL, NULL, NULL, 'Arthur Morgan', '1234567889', 'arthur.morgan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-06-30 20:15:00', NULL),
('STD051', 57, 'Raj', 'Mishra', 'mishraraj1206@gmail.com', '8209289088', '2002-07-12', '2025-07-04', 'active', NULL, 'B020', NULL, NULL, NULL, NULL, 'Sanjay Mishra', '09462427558', 'mishraraj1206@gmail.com', '', NULL, '../uploads/profile_pictures/student_57_1751651837.jpg'),
('STD052', 58, 'John', 'Doe', 'john.doe@example.com', '9876543210', '2000-01-15', '2025-07-08', 'active', NULL, 'B001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL),
('STD053', 59, 'Kunj', 'Bihari', 'BihariKuj@example.com', '9876543210', '2000-01-15', '2025-07-18', 'active', NULL, 'B004', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_batch_history`
--

CREATE TABLE `student_batch_history` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `from_batch_id` varchar(10) NOT NULL,
  `to_batch_id` varchar(10) NOT NULL,
  `transfer_date` datetime NOT NULL DEFAULT current_timestamp(),
  `transferred_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_batch_history`
--

INSERT INTO `student_batch_history` (`id`, `student_id`, `from_batch_id`, `to_batch_id`, `transfer_date`, `transferred_by`) VALUES
(1, 'STD001', 'B001', 'B011', '2025-03-01 10:00:00', 1),
(2, 'STD002', 'B001', 'B011', '2025-03-01 10:00:00', 1),
(3, 'STD006', 'B002', 'B012', '2025-04-01 11:00:00', 1),
(4, 'STD007', 'B002', 'B012', '2025-04-01 11:00:00', 1),
(5, 'STD011', 'B001', 'B013', '2025-05-01 12:00:00', 1),
(6, 'STD001', 'B001', 'B003', '2025-07-03 16:46:38', 1),
(7, 'STD002', 'B001', 'B003', '2025-07-03 16:46:38', 1),
(8, 'STD001', 'B003', 'B024', '2025-07-15 17:06:34', 1),
(9, 'STD002', 'B003', 'B024', '2025-07-15 17:06:34', 1),
(10, 'STD002', 'B001', 'B028', '2025-07-19 11:35:38', 1),
(11, 'STD011', 'B001', 'B028', '2025-07-19 11:35:38', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `student_dashboard` (
`student_id` varchar(50)
,`student_name` varchar(201)
,`batch_id` varchar(10)
,`course_name` varchar(50)
,`start_date` date
,`end_date` date
,`time_slot` varchar(50)
,`mode` enum('online','offline')
,`status` enum('upcoming','ongoing','completed','cancelled')
,`present_count` bigint(21)
,`total_attendance` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `document_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `document_type` enum('aadhaar','pancard','tenth_marksheet','twelfth_marksheet','other') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_documents`
--

INSERT INTO `student_documents` (`document_id`, `student_id`, `document_type`, `file_path`, `uploaded_at`) VALUES
(1, 'STD051', 'aadhaar', '../uploads/student_documents/STD051_aadhaar_1752981995_CV_2.pdf', '2025-07-20 03:26:35'),
(2, 'STD051', 'pancard', '../uploads/student_documents/STD051_pancard_1752982085_Front Page.pdf', '2025-07-20 03:28:05'),
(3, 'STD051', 'tenth_marksheet', '../uploads/student_documents/STD051_tenth_marksheet_1752982203_Preoperative care.pdf', '2025-07-20 03:30:03'),
(4, 'STD051', 'twelfth_marksheet', '../uploads/student_documents/STD051_twelfth_marksheet_1752982227_cg2.pdf', '2025-07-20 03:30:27');

-- --------------------------------------------------------

--
-- Table structure for table `student_status_log`
--

CREATE TABLE `student_status_log` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `action` enum('dropped','reactivated','transferred') NOT NULL,
  `reason` text DEFAULT NULL,
  `processed_by` int(11) NOT NULL,
  `processed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_status_log`
--

INSERT INTO `student_status_log` (`id`, `student_id`, `action`, `reason`, `processed_by`, `processed_at`) VALUES
(1, 'STD046', 'dropped', 'Yes', 1, '2025-07-07 15:09:06'),
(2, 'STD001', 'dropped', 'qweert', 1, '2025-07-16 20:34:30'),
(3, 'STD027', 'reactivated', 'Yes', 1, '2025-07-16 20:34:41'),
(4, 'STD001', 'reactivated', 'Okay', 1, '2025-07-16 20:34:58'),
(5, 'STD027', 'dropped', 'Yuhin', 1, '2025-07-16 20:41:04'),
(6, 'STD051', 'reactivated', 'my wish', 1, '2025-07-19 11:27:56'),
(7, 'STD001', 'dropped', 'vh', 1, '2025-07-19 11:28:43'),
(8, 'STD001', 'reactivated', 'my wish', 1, '2025-07-19 13:06:49'),
(9, 'STD001', 'dropped', 'yes', 1, '2025-07-19 13:07:13'),
(10, 'STD044', 'reactivated', 'my wish', 1, '2025-07-20 22:34:10'),
(11, 'STD027', 'reactivated', 'Yes', 1, '2025-07-20 22:36:19');

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `qualifications` text DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `years_of_experience` int(11) DEFAULT 0,
  `bio` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`id`, `user_id`, `name`, `email`, `phone`, `address`, `qualifications`, `specialization`, `joining_date`, `years_of_experience`, `bio`, `profile_picture`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, 'John Smith', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-07-21 07:17:43', '2025-07-21 07:17:43'),
(2, 3, 'Emily Johnson', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-07-21 07:17:43', '2025-07-21 07:17:43'),
(3, 4, 'Michael Brown', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-07-21 07:17:43', '2025-07-21 07:17:43'),
(4, 5, 'Sarah Davis', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-07-21 07:17:43', '2025-07-21 07:17:43');

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('Test','Assignment','Notes','Other') NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uploads`
--

INSERT INTO `uploads` (`id`, `title`, `description`, `file_path`, `file_type`, `uploaded_by`, `uploaded_at`) VALUES
(1, 'Programs', 'Pdf file', '../uploads/content/6876131b0a0af_JAVA Programs.pdf', '', 1, '2025-07-15 08:36:43'),
(2, 'chvjvhlj', 'jkgiugigig', '../uploads/content/68763c8bc50cc_Unit_2_Cyber_Offenses.pdf', '', 1, '2025-07-15 11:33:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','mentor','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `account_locked` tinyint(1) NOT NULL DEFAULT 0,
  `last_failed_login` datetime DEFAULT NULL,
  `login_attempt_limit` int(11) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`, `last_login`, `status`, `failed_login_attempts`, `account_locked`, `last_failed_login`, `login_attempt_limit`) VALUES
(1, 'Admin', 'admin@asdacademy.com', '$2y$10$ntreS1YDMMWRhNp.Z5eXt.Xqe.HURladla28tRhviV5IOnO.xkED2', 'admin', '2025-07-06 18:54:06', NULL, 'active', 0, 0, NULL, 3),
(2, 'John Smith', 'john.smith@asdacademy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mentor', '2024-12-31 13:00:00', '2025-06-30 09:15:00', 'active', 0, 0, NULL, 5),
(3, 'Emily Johnson', 'emily.johnson@asdacademy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mentor', '2024-12-31 13:00:00', '2025-06-30 09:30:00', 'active', 0, 0, NULL, 5),
(4, 'Michael Brown', 'michael.brown@asdacademy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mentor', '2024-12-31 13:00:00', '2025-06-30 09:45:00', 'active', 0, 0, NULL, 5),
(5, 'Sarah Davis', 'sarah.davis@asdacademy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mentor', '2024-12-31 13:00:00', '2025-06-30 10:00:00', 'active', 0, 0, NULL, 5),
(6, 'Alice Williams', 'alice.williams@student.com', '$2y$10$rq/H7Nia6CJps1uwLBi/ReDugSQpy6FkKrhCiC4N8jWywRclRt54C', 'student', '2025-01-14 13:00:00', '2025-06-30 08:00:00', 'active', 0, 0, NULL, 5),
(7, 'Bob Miller', 'bob.miller@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 08:15:00', 'active', 0, 0, NULL, 5),
(8, 'Charlie Wilson', 'charlie.wilson@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 08:30:00', 'active', 0, 0, NULL, 5),
(9, 'David Moore', 'david.moore@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 08:45:00', 'active', 0, 0, NULL, 5),
(10, 'Eva Taylor', 'eva.taylor@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 09:00:00', 'active', 0, 0, NULL, 5),
(11, 'Frank Anderson', 'frank.anderson@student.com', '$2y$10$hLufnG587tLyJEyrEnvv4.ATq7WcuCT2UWC6QtOKlnNVzNs6Cfr1.', 'student', '2025-01-14 13:00:00', '2025-06-30 09:15:00', 'active', 0, 0, NULL, 5),
(12, 'Grace Thomas', 'grace.thomas@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 09:30:00', 'active', 0, 0, NULL, 5),
(13, 'Henry Jackson', 'henry.jackson@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 09:45:00', 'active', 0, 0, NULL, 5),
(14, 'Ivy White', 'ivy.white@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 10:00:00', 'active', 0, 0, NULL, 5),
(15, 'Jack Harris', 'jack.harris@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 10:15:00', 'active', 0, 0, NULL, 5),
(16, 'John Doe', 'john.doe@example.com', '$2y$10$NGMhUvvOJL2Ko8F0/U9L5.OdCWZiCKb1JxB.YWUb6K713.QNe3TuO', 'student', '2025-07-08 08:55:20', NULL, 'active', 0, 0, NULL, 5),
(17, 'Leo Garcia', 'leo.garcia@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 10:45:00', 'active', 0, 0, NULL, 5),
(18, 'Mia Martinez', 'mia.martinez@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 11:00:00', 'active', 0, 0, NULL, 5),
(19, 'Noah Robinson', 'noah.robinson@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 11:15:00', 'active', 0, 0, NULL, 5),
(20, 'Olivia Clark', 'olivia.clark@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 11:30:00', 'active', 0, 0, NULL, 5),
(21, 'Peter Rodriguez', 'peter.rodriguez@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 11:45:00', 'active', 0, 0, NULL, 5),
(22, 'Quinn Lewis', 'quinn.lewis@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 12:00:00', 'active', 0, 0, NULL, 5),
(23, 'Rachel Lee', 'rachel.lee@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 12:15:00', 'active', 0, 0, NULL, 5),
(24, 'Samuel Walker', 'samuel.walker@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 12:30:00', 'active', 0, 0, NULL, 5),
(25, 'Tina Hall', 'tina.hall@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 12:45:00', 'active', 0, 0, NULL, 5),
(26, 'Umar Allen', 'umar.allen@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 13:00:00', 'active', 0, 0, NULL, 5),
(27, 'Victoria Young', 'victoria.young@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 13:15:00', 'active', 0, 0, NULL, 5),
(28, 'William Hernandez', 'william.hernandez@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 13:30:00', 'active', 0, 0, NULL, 5),
(29, 'Xena King', 'xena.king@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 13:45:00', 'active', 0, 0, NULL, 5),
(30, 'Yusuf Wright', 'yusuf.wright@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 14:00:00', 'active', 0, 0, NULL, 5),
(31, 'Zoe Lopez', 'zoe.lopez@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 14:15:00', 'active', 0, 0, NULL, 5),
(32, 'Adam Scott', 'adam.scott@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 14:30:00', 'active', 0, 0, NULL, 5),
(33, 'Bella Green', 'bella.green@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 14:45:00', 'active', 0, 0, NULL, 5),
(34, 'Caleb Adams', 'caleb.adams@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 15:00:00', 'active', 0, 0, NULL, 5),
(35, 'Diana Baker', 'diana.baker@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 15:15:00', 'active', 0, 0, NULL, 5),
(36, 'Ethan Gonzalez', 'ethan.gonzalez@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 15:30:00', 'active', 0, 0, NULL, 5),
(37, 'Fiona Nelson', 'fiona.nelson@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 15:45:00', 'active', 0, 0, NULL, 5),
(38, 'George Carter', 'george.carter@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 16:00:00', 'active', 0, 0, NULL, 5),
(39, 'Hannah Mitchell', 'hannah.mitchell@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 16:15:00', 'active', 0, 0, NULL, 5),
(40, 'Ian Perez', 'ian.perez@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 16:30:00', 'active', 0, 0, NULL, 5),
(41, 'Julia Roberts', 'julia.roberts@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 16:45:00', 'active', 0, 0, NULL, 5),
(42, 'Kevin Turner', 'kevin.turner@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 17:00:00', 'active', 0, 0, NULL, 5),
(43, 'Lily Phillips', 'lily.phillips@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 17:15:00', 'active', 0, 0, NULL, 5),
(44, 'Max Campbell', 'max.campbell@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 17:30:00', 'active', 0, 0, NULL, 5),
(45, 'Nora Parker', 'nora.parker@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 17:45:00', 'active', 0, 0, NULL, 5),
(46, 'Oscar Evans', 'oscar.evans@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 18:00:00', 'active', 0, 0, NULL, 5),
(47, 'Penny Edwards', 'penny.edwards@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 18:15:00', 'active', 0, 0, NULL, 5),
(48, 'Quentin Collins', 'quentin.collins@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 18:30:00', 'active', 0, 0, NULL, 5),
(49, 'Rose Stewart', 'rose.stewart@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 18:45:00', 'active', 0, 0, NULL, 5),
(50, 'Simon Sanchez', 'simon.sanchez@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 19:00:00', 'active', 0, 0, NULL, 5),
(51, 'Tara Morris', 'tara.morris@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 19:15:00', 'active', 0, 0, NULL, 5),
(52, 'Ulysses Rogers', 'ulysses.rogers@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 19:30:00', 'active', 0, 0, NULL, 5),
(53, 'Vera Reed', 'vera.reed@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 19:45:00', 'active', 0, 0, NULL, 5),
(54, 'Wade Cook', 'wade.cook@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 20:00:00', 'active', 0, 0, NULL, 5),
(55, 'Xander Morgan', 'xander.morgan@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '2025-01-14 13:00:00', '2025-06-30 20:15:00', 'active', 0, 0, NULL, 5),
(57, 'Raj Mishra', 'mishraraj1206@gmail.com', '$2y$10$F1lJ50a4ABy6QNR8AFBrIO7Y65CSB0G7eF5NF44oWQj6fMNpzAIjO', 'student', '2025-07-04 10:17:27', NULL, 'active', 0, 0, NULL, 5),
(59, 'Kunj Bihari', 'BihariKuj@example.com', '$2y$10$A2h.AWwhDwZA2SDnCriDgex0NPGvFTg7LCW6uFBBOR6dIpjMnzBty', 'student', '2025-07-18 05:09:35', NULL, 'active', 0, 0, NULL, 5);

-- --------------------------------------------------------

--
-- Structure for view `student_dashboard`
--
DROP TABLE IF EXISTS `student_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_dashboard`  AS SELECT `s`.`student_id` AS `student_id`, concat(`s`.`first_name`,' ',`s`.`last_name`) AS `student_name`, `b`.`batch_id` AS `batch_id`, `b`.`course_name` AS `course_name`, `b`.`start_date` AS `start_date`, `b`.`end_date` AS `end_date`, `b`.`time_slot` AS `time_slot`, `b`.`mode` AS `mode`, `b`.`status` AS `status`, (select count(0) from `attendance` `a` where `a`.`student_name` = concat(`s`.`first_name`,' ',`s`.`last_name`) and `a`.`batch_id` = `b`.`batch_id` and `a`.`status` = 'Present') AS `present_count`, (select count(0) from `attendance` `a` where `a`.`student_name` = concat(`s`.`first_name`,' ',`s`.`last_name`) and `a`.`batch_id` = `b`.`batch_id`) AS `total_attendance` FROM (`students` `s` join `batches` `b` on(`s`.`batch_name` = `b`.`batch_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `idx_attendance_search` (`date`,`batch_id`,`student_name`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `fk_batch_mentor` (`batch_mentor_id`),
  ADD KEY `fk_batch_creator` (`created_by`);

--
-- Indexes for table `batch_uploads`
--
ALTER TABLE `batch_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_batch_upload` (`upload_id`),
  ADD KEY `fk_upload_batch` (`batch_id`);

--
-- Indexes for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `chat_participants`
--
ALTER TABLE `chat_participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_students`
--
ALTER TABLE `exam_students`
  ADD PRIMARY KEY (`exam_id`,`student_name`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `proctored_exams`
--
ALTER TABLE `proctored_exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_report_batch` (`batch_id`),
  ADD KEY `idx_report_month` (`month`),
  ADD KEY `idx_report_type` (`report_type`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_schedule_batch` (`batch_id`),
  ADD KEY `fk_schedule_creator` (`created_by`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_student_name` (`last_name`,`first_name`),
  ADD KEY `idx_student_status` (`current_status`),
  ADD KEY `idx_course_batch` (`course_enrolled`,`batch_name`),
  ADD KEY `fk_student_user` (`user_id`),
  ADD KEY `fk_student_batch` (`batch_name`),
  ADD KEY `fk_dropout_processed_by` (`dropout_processed_by`);

--
-- Indexes for table `student_batch_history`
--
ALTER TABLE `student_batch_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `from_batch_id` (`from_batch_id`),
  ADD KEY `to_batch_id` (`to_batch_id`),
  ADD KEY `transferred_by` (`transferred_by`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_status_log`
--
ALTER TABLE `student_status_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trainer_user` (`user_id`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_upload_user` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=214;

--
-- AUTO_INCREMENT for table `batch_uploads`
--
ALTER TABLE `batch_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `chat_participants`
--
ALTER TABLE `chat_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `student_batch_history`
--
ALTER TABLE `student_batch_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_status_log`
--
ALTER TABLE `student_status_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`);

--
-- Constraints for table `batches`
--
ALTER TABLE `batches`
  ADD CONSTRAINT `fk_batch_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_batch_mentor` FOREIGN KEY (`batch_mentor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `batch_uploads`
--
ALTER TABLE `batch_uploads`
  ADD CONSTRAINT `fk_batch_upload` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_upload_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`);

--
-- Constraints for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD CONSTRAINT `chat_conversations_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `chat_conversations_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `chat_conversations_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`);

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`),
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `chat_participants`
--
ALTER TABLE `chat_participants`
  ADD CONSTRAINT `chat_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`),
  ADD CONSTRAINT `chat_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `exam_students`
--
ALTER TABLE `exam_students`
  ADD CONSTRAINT `exam_students_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `proctored_exams` (`exam_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `proctored_exams`
--
ALTER TABLE `proctored_exams`
  ADD CONSTRAINT `proctored_exams_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`);

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `fk_schedule_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`batch_id`),
  ADD CONSTRAINT `fk_schedule_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_dropout_processed_by` FOREIGN KEY (`dropout_processed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_student_batch` FOREIGN KEY (`batch_name`) REFERENCES `batches` (`batch_id`);

--
-- Constraints for table `student_batch_history`
--
ALTER TABLE `student_batch_history`
  ADD CONSTRAINT `student_batch_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `student_batch_history_ibfk_2` FOREIGN KEY (`from_batch_id`) REFERENCES `batches` (`batch_id`),
  ADD CONSTRAINT `student_batch_history_ibfk_3` FOREIGN KEY (`to_batch_id`) REFERENCES `batches` (`batch_id`);

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_status_log`
--
ALTER TABLE `student_status_log`
  ADD CONSTRAINT `student_status_log_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `student_status_log_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `fk_upload_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
