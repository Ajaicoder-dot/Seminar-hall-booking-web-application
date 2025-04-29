-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2025 at 03:20 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `university`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hall_id` int(11) NOT NULL,
  `organizer_name` varchar(255) NOT NULL,
  `organizer_email` varchar(255) NOT NULL,
  `organizer_department` varchar(255) NOT NULL,
  `organizer_contact` varchar(255) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Event','Class','Other') NOT NULL,
  `program_purpose` text NOT NULL,
  `from_date` date NOT NULL,
  `end_date` date NOT NULL,
  `timing` enum('FN','AN') NOT NULL,
  `time_slots` varchar(255) DEFAULT NULL,
  `queries` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ccc_hall_bookings`
--

CREATE TABLE `ccc_hall_bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hall_id` int(11) NOT NULL,
  `organizer_name` varchar(255) NOT NULL,
  `organizer_email` varchar(255) NOT NULL,
  `organizer_department` varchar(255) NOT NULL,
  `department_id` int(11) NOT NULL,
  `organizer_contact` varchar(20) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` varchar(50) NOT NULL,
  `program_purpose` text NOT NULL,
  `from_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_hours` int(11) NOT NULL,
  `advance_payment` decimal(10,2) NOT NULL DEFAULT 5000.00,
  `total_amount` decimal(10,2) NOT NULL,
  `queries` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('Pending','Approved','Cancelled','Rejected','Completed') DEFAULT 'Pending',
  `payment_status` enum('Not Paid','Partially Paid','Fully Paid','Refunded') DEFAULT 'Not Paid',
  `cancellation_reason` text DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `damage_details` text DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ccc_hall_bookings`
--

INSERT INTO `ccc_hall_bookings` (`booking_id`, `user_id`, `hall_id`, `organizer_name`, `organizer_email`, `organizer_department`, `department_id`, `organizer_contact`, `program_name`, `program_type`, `program_purpose`, `from_date`, `end_date`, `start_time`, `end_time`, `duration_hours`, `advance_payment`, `total_amount`, `queries`, `created_at`, `updated_at`, `status`, `payment_status`, `cancellation_reason`, `reject_reason`, `damage_details`, `refund_amount`) VALUES
(1, 1, 99, 'keshiii', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '9361685137', 'nil', '0', 'fare', '2025-04-19', '2025-04-19', '09:30:00', '10:30:00', 2, '5000.00', '1000.00', 'n', '2025-04-18 08:14:12', '2025-04-18 09:21:46', '', 'Fully Paid', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ccc_hall_payments`
--

CREATE TABLE `ccc_hall_payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('Advance','Final','Refund') NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` datetime NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ccc_hall_payments`
--

INSERT INTO `ccc_hall_payments` (`payment_id`, `booking_id`, `amount`, `payment_type`, `payment_method`, `transaction_id`, `payment_date`, `receipt_number`, `notes`) VALUES
(1, 1, '5000.00', 'Advance', NULL, NULL, '2025-04-18 13:44:13', NULL, NULL),
(2, 1, '6000.00', 'Final', 'UPI', NULL, '2025-04-18 13:54:45', 'nil', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contact_us`
--

CREATE TABLE `contact_us` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `city` varchar(100) NOT NULL,
  `remarks` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_us`
--

INSERT INTO `contact_us` (`id`, `name`, `email`, `phone`, `city`, `remarks`, `submitted_at`) VALUES
(1, 'vishnu', 'vijay@gmail.com', '9898898989', 'puducherry', 'nil', '2025-03-10 06:49:07'),
(2, 'ramesh', 'sekar@gmail.com', '9898898989', 'puducherry', 'nil', '2025-03-10 14:46:48'),
(3, 'ajaisekar1', 'ajaisekar@gmail.com', '9898898989', 'puducherry', 'nil', '2025-03-23 13:59:41'),
(4, 'AJAI', 'ajaiofficial06@gmail.com', '9898898989', 'puducherry', 'NIL', '2025-04-02 15:24:17');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(10) NOT NULL,
  `school_id` int(11) DEFAULT NULL,
  `department_name` varchar(150) NOT NULL,
  `hod_name` varchar(255) NOT NULL,
  `hod_contact_mobile` varchar(15) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `hod_contact_email` varchar(255) NOT NULL,
  `hod_intercom` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `school_id`, `department_name`, `hod_name`, `hod_contact_mobile`, `designation`, `hod_contact_email`, `hod_intercom`) VALUES
(1, 1, 'Department of Tamil', 'Dr. M. Karunanidhiiiii', '769696969999', 'Head Of Departmentt', 'nidhikaruna.tam@pondiuni.ac.in', '+91-413-2654516899'),
(2, 2, 'Department of Management Studies', 'Dr. R. Kasilingam', '', 'Head Of Department', 'head.dms@pondiuni.ac.in', '+914132654399'),
(3, 2, 'Department of Management Studies – Karaikal Campus', 'Dr. C. Madhavaiah', '', 'Head Of Department', 'head.kcm@pondiuni.ac.in', '+914368231029'),
(4, 2, 'Department of Commerce', 'Dr. P. Natarajan', '', 'Head Of Department', 'head.com@pondiuni.ac.in', '+914132654694'),
(5, 2, 'Department of Commerce – Karaikal Campus', 'Dr. V. Arulmurugan', '', 'Head Of Department', 'arulmurugan.kcm@pondiuni.edu.in', '+914132654364'),
(6, 2, 'Department of Economics', 'Dr. Prasant Kumar Panda', '', 'Head Of Department', 'head.eco@pondiuni.ac.in', '+914132654669'),
(7, 2, 'Department of Tourism Studies', 'Dr. R. C. Anu Chandran', '', ' Head of Department', 'anu.chandran48@gmail.com', '+914132654729\n'),
(8, 2, 'Department of Banking Technology', 'Dr. V. Mariappan', '', 'Head of Department', 'vmaris.btm@pondiuni.edu.in', '+914132654536'),
(9, 2, 'Department of International Business', 'Dr. P. G. Arul', '', 'Head of Department', 'head.ibm@pondiuni.ac.in', '+914132654643'),
(10, 2, 'Department of Management Studies Port Blair Campus', 'Dr. T. Ganesh', '', 'Head of Department', 'coordinatormbapb@pondiuni.ac.in', '03192295544'),
(11, 3, 'Department of Statistics', 'Dr. Navin Chandra', '', 'Head of Department', 'nc.stat@pondiuni.ac.in', '+914132654390'),
(12, 3, 'Department of Mathematics', 'Dr. A. Joseph Kennedy', '', 'Head of Department', 'kennedy.pondi@gmail.com', '+914132654702'),
(13, 4, 'Department of Coastal Disaster Management', 'Dr. S. Balaji', '', 'Head of Department', 'hodcdm@gmail.com', '03192261520'),
(14, 4, 'Department of Applied Psychology', 'Dr. Sibnath Deb', '', 'Head of Department', 'sibnath23@gmail.com', '03192261520'),
(15, 4, 'Department of Earth Sciences', 'Dr. K. Srinivasamoorthy', '', 'Head of Department', 'moorthy.esc@pondiuni.edu.in', '+914132654490'),
(16, 4, 'Department of Chemistry', 'Dr. Bala. Manimaran', '', 'Head of Department', 'head.che@pondiuni.edu.in', '+914132654410'),
(17, 4, 'Department of Physics', 'Dr. R. Sivakumar', '', 'Head of Department', 'head.phy@pondiuni.ac.in', '+914132654402 /609'),
(18, 5, 'Department of Bioinformatics', 'Dr. P. T. V. Lakshmi', '', 'Head of Department', 'head@bicpu.edu.in', '+914132654589'),
(19, 5, 'Department of Microbiology', 'Dr. Maheswaran Mani', '', 'Head of Department', 'mahes.mib@pondiuni.edu.in', ' +91-413-2654-868 / 870'),
(20, 5, 'Department of Food Science and Technology', 'Dr. S. Haripriya', '', 'Head of Department', 'head.fst@pondiuni.ac.in', '+914132654625'),
(21, 5, 'Department of Ocean Studies and Marine Biology', 'Dr. Gadi Padmavati', '', 'Head of Department', 'padma190@rediffmail.com', '3192262307'),
(22, 5, 'Department of Ecology and Environmental Sciences', 'Dr. S. M. Sundarapandian', '', 'Head of Department', 'head.ees@pondiuni.ac.in', '+91413265432020'),
(23, 5, 'Department of Biotechnology', 'Dr. B. Sudhakar', '', 'Head of Department', 'baluchamy@yahoo.com', '+914132654788'),
(24, 5, 'Department of Biochemistry and Molecular Biology', 'Dr. C. Thirunavukkarasu', '', 'Head of Department', 'head.bmb@pondiuni.ac.in', '+914132654972'),
(25, 6, 'Centre for Foreign Language', '', '', 'Head of Department', '', '0'),
(26, 6, 'Department of Physical Education and Sports', 'Dr. G. Vinod Kumar', '', 'Head of Department', 'head.pes@pondiuni.ac.in', '+914132654845'),
(27, 6, 'Department of Philosophy', 'Dr. Velmurugan. K', '', 'Head of Department', 'velmurugank@pondiuni.ac.in', '+914132654340'),
(28, 6, 'Department of Sanskrit', 'Dr. J. Krishnan', '', 'Head of Department', 'jkrishnan63@yahoo.co.in', '+914132654358'),
(29, 6, 'Department of Hindi', 'Dr. C. Jaya Sankar Babu', '', 'Head of Department', 'dept.of.hindi.12@gmail.com', '+914132654352'),
(30, 6, 'Department of French', 'Dr. Sarmila Acharif', '', 'Head of Department', 'm_sharmi@yahoo.com', '+914132654352'),
(31, 6, 'Department of English', 'Dr. T. Marx', '', 'Head of Department', 'drtmarx@gmail.com', '+914132654803'),
(32, 6, 'Escande Chair in Asian Christian Studies', '', '', 'Head of Department', '', ''),
(33, 7, 'Centre for Maritime Studies', 'Prof. A. Subramanyam Raju', '', 'Head of Department', 'adluriraju@rediffmail.com', '+914132654587'),
(34, 7, 'Centre for European Studies', 'Dr. Kamalaveni', '', 'Head of Department', 'kamalaveni@pondiuni.ac.in', '+914132654'),
(35, 7, 'Centre for Study of Social Exclusion & Inclusive Policy', 'Dr. A. Chidambaram', '', 'Head of Department', 'balajasst@gmail.com', '+914132654380'),
(36, 7, 'UMISARC – Centre for South Asian Studies', 'Prof. A. Subramanyam Rajuuua', '7696969699', 'Head Of Departmentt', 'adluriraju@rediffmail.com', '+914132654587888'),
(37, 7, 'Centre for Women’s Studies', 'Dr. Aashita', '', 'Head of Department', 'aashita.pu@pondiuni.ac.in', '+914132654820'),
(38, 7, 'Department of Social Work', 'Dr. K. Anbu', '', 'Head of Department', 'anbucovai@gmail.com', '+914132654956, 9486313164'),
(39, 7, 'Department of Politics and International Studies', 'Dr. Nanda Kishor M.S', '', 'Head of Department', 'head.pol@pondiuni.ac.in', '+914132654333'),
(40, 7, 'Department of History', 'Dr. N. Chandramouli', '', 'Head of Department', 'c.navuluri@gmail.com', '+914132654384'),
(41, 7, 'Department of Sociology', 'Dr. C. Aruna', '', 'Head of Department', 'mathivanan.pu@gmail.com', '+914132654384'),
(42, 7, 'Department of Anthropology', 'Dr. Valerie Dkhar', '', 'Head of Department', 'valz2203@pondiuni.ac.in', '+914132654765'),
(43, 8, 'Centre for Pollution Control and Environmental Engineering', 'Dr. S. Gajalakshmi', '', 'Head of Department', 'office.cpee@pondiuni.ac.in', '+914132654362'),
(44, 8, 'Department of Computer Science – Karaikal Campus', 'Dr. S. Bhuvaneswari', '', 'Head of Department', 'arafatbegam@gmail.com', '+914368231030'),
(45, 8, 'Department of Electronics Engineering', 'Dr. T. Shanmuganantham', '', 'Head of Department', 'shanmuga.dee@pondiuni.edu.in', '+914132654992'),
(46, 8, 'Department of Computer Science', 'Dr. S. K. V. Jayakumar', '', 'Head of Department', 'hodcspu@gmail.com', '+9104132654990'),
(47, 9, 'Centre for Adult and Continuing Education', '', '', 'Head of Department', 'default@default.in', '0'),
(48, 10, 'Department of Performing Arts', 'Dr. P. Sridharan', '', 'Head of Department', 'drsridharpu@gmail.com', '+914132654646 '),
(49, 11, 'School of Law', 'Dr. S. Victor Anandkumar', '', 'Head of Department', 'schooloflawpu@gmail.com', '+914132654910'),
(50, 12, 'Department of Electronic Media and Mass Communication', 'Dr. Radhika Khanna', '', 'Head of Department', 'office.demmc@pondiuni.ac.in', '+914132654680 '),
(51, 12, 'Department of Library and Information Science', 'Dr. M. Karunanidhii', '7696969699', 'hod', 'hod@gmail.com', '676676776557'),
(52, 13, 'Centre for Nano Sciences & Technology', 'Dr. R. Kasilingam', '7696969699', 'Head Of Departmentt', 'hod@gmail.com', '041325594943'),
(53, 13, 'Department of Green Energy Technology', '', '', '', '', '0'),
(54, 1, 'Subramania Bharathi School of Tamil Language & Literature', 'Dr. S. Sudalai Muthu', '', 'Dean', 'dean.tam@pondiuni.ac.in', '+914132654483'),
(55, 2, 'Department Of Management Studies', 'Dr. R. Kasilingam', '', ' Head of Department', '\r\nhead.dms@pondiuni.ac.in', '+914132654399'),
(56, 2, 'Department of Commerce', 'Dr. P. Natarajan', '', 'Head of Department', 'head.com@pondiuni.ac.in', '\r\n+91-413-2654-694'),
(57, 2, 'Department of Economics', 'Dr. Prasant Kumar Panda', '', 'Head of Department', 'head.eco@pondiuni.ac.in', '+914132654669'),
(58, 2, 'Department of Tourism Studies', 'Dr. R. C. Anu Chandran', '', 'Head of Department', 'anu.chandran48@gmail.com', '+914132654729'),
(61, 2, 'ajai', 'Dr. M. Karunanidhiiiii', '7696969699', 'hod', 'ajaiofficial06@gmail.com', '676676776557');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification`
--

CREATE TABLE `email_verification` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `department_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `halls`
--

CREATE TABLE `halls` (
  `hall_id` int(11) NOT NULL,
  `hall_type` int(11) DEFAULT NULL,
  `hall_name` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL,
  `features` text DEFAULT NULL,
  `floor_name` varchar(50) DEFAULT NULL,
  `zone` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `room_availability` enum('Yes','No') DEFAULT NULL,
  `belong_to` enum('Department','School','Administration') DEFAULT NULL,
  `incharge_name` varchar(255) NOT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `incharge_email` varchar(255) NOT NULL,
  `incharge_phone` varchar(20) NOT NULL,
  `school_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `panorama_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `halls`
--

INSERT INTO `halls` (`hall_id`, `hall_type`, `hall_name`, `capacity`, `features`, `floor_name`, `zone`, `image`, `room_availability`, `belong_to`, `incharge_name`, `designation`, `incharge_email`, `incharge_phone`, `school_id`, `department_id`, `archived`, `is_archived`, `archived_at`, `panorama_url`) VALUES
(78, 2, 'COMPUTER SCIENCE SEMINAR HALLs', 234, '[\"AC\",\"Projector\",\"WiFi\",\"Audio System\"]', 'Second Floor', 'South', 'images/adu.jpg', 'Yes', 'Department', 'DR. JAYAKUMAR ', 'Admin', 'ajai55620@gmail.com', '63835323627', 8, 46, 0, 0, NULL, NULL),
(80, 1, 'AUDITORIUM 11', 200, '[\"AC\",\"Projector\",\"WiFi\",\"Audio System\",\"Smart Board\"]', 'Ground Floor', 'West', 'images/pondi.jpg', 'Yes', 'Department', 'DEAN', 'Dean', 'ajai55620@gmail.com', '93616042481', 2, 9, 0, 0, NULL, NULL),
(81, 1, 'SEMINAR HALLl', 100, '[\"AC\",\"Projector\",\"WiFi\",\"Audio System\",\"Smart Board\",\"White Board\"]', 'Ground Floor', 'West', 'images/adu.jpg', 'Yes', 'Department', 'HOD ', 'HOD', 'ajai55620@gmail.com', '93616042481', 3, 11, 0, 0, NULL, NULL),
(82, 1, 'SEMINAR HALL  II', 100, '[\"WiFi\",\"White Board\"]', 'First Floor', 'North', 'images/adu.jpg', 'Yes', 'Department', 'DEAN', 'Dean', 'hod@gmail.com', '936160424812', 1, 1, 0, 0, NULL, NULL),
(83, 1, 'SH#', 100, '[\"AC\",\"Projector\",\"WiFi\",\"Smart Board\"]', 'First Floor', 'West', 'images/adu.jpg', 'Yes', 'Department', 'KEVIN', 'HOD', 'Kevin@gmail.com', '9933448855', 1, 1, 0, 0, NULL, NULL),
(84, 1, 'SH5', 50, '[\"AC\",\"Projector\",\"WiFi\"]', 'Ground Floor', 'East', 'images/adu.jpg', 'Yes', 'Department', 'RAMESH', 'HOD', 'ramesh@gmail.com', '9361685137', 7, 39, 0, 0, NULL, NULL),
(86, 1, 'SH9', 100, '[\"AC\",\"Projector\",\"WiFi\"]', 'Ground Floor', 'West', 'images/adu.jpg', 'Yes', 'Department', 'ajai', 'HOD', 'kishor1560145@gmail.com', '9933448855hu', 1, 1, 0, 0, NULL, NULL),
(88, 1, 'ss', 99, '[\"AC\"]', 'Ground Floor', 'West', 'images/adu.jpg', 'Yes', 'Department', 'hjh', 'HOD', 'ajai@gmail.com', '99888097979', 2, 3, 0, 0, NULL, NULL),
(93, 2, 'ajaii', 100, '[\"AC\",\"Projector\",\"Audio System\",\"Smart Board\",\"White Board\"]', 'First Floor', 'West', 'images/adu.jpg', 'Yes', 'Department', 'kishoreeee', 'Admin', 'hod@gmail.com', '9361685137', 11, 49, 0, 0, NULL, NULL),
(94, 3, 'DEEBAN', 100, '[\"AC\",\"WiFi\",\"Audio System\",\"Smart Board\",\"White Board\"]', 'First Floor', 'East', 'images/adu.jpg', 'Yes', 'Department', 'Deeban', 'Admin', 'Kevin@gmail.com', '9933448855', 7, 33, 0, 0, NULL, NULL),
(95, 3, 'LECTURE HALL', 200, '[\"AC\"]', 'Ground Floor', 'East', 'images/adu.jpg', 'Yes', 'Department', 'ajai', 'Admin', 'Kevin@gmail.com', '9933448855', 12, 50, 0, 0, NULL, NULL),
(96, 2, 'management  hall', 100, '[\"Projector\",\"WiFi\"]', 'Ground Floor', 'East', 'images/adu.jpg', 'Yes', 'Department', 'DR. JAYAKUMAR ', 'Dean', 'hod@gmail.com', '9361604248', 10, 48, 0, 0, NULL, NULL),
(98, 2, 'COMPUTER ', 32, '[\"Projector\"]', 'Ground Floor', 'East', 'images/adu.jpg', 'Yes', 'School', 'ajai', 'Admin', 'ajai55620@gmail.com', '9933448855', 11, NULL, 0, 0, NULL, NULL),
(99, 2, 'CCC Auditorium', 500, '[\"AC\",\"Projector\",\"WiFi\",\"Audio System\",\"Smart Board\",\"White Board\"]', 'Ground Floor', 'Central', 'images/ccc_hall.jpg', 'Yes', 'Administration', 'CCC Hall Administrator', 'Admin', 'ccc.admin@pondiuni.edu.in', '04132654123', NULL, NULL, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hall_bookings`
--

CREATE TABLE `hall_bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hall_id` int(11) NOT NULL,
  `organizer_name` varchar(255) NOT NULL,
  `organizer_email` varchar(255) NOT NULL,
  `organizer_department` varchar(255) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `organizer_contact` varchar(20) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Event','Class','Other') NOT NULL,
  `program_purpose` text NOT NULL,
  `from_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `queries` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('Pending','Approved','Cancelled','Rejected') DEFAULT 'Pending',
  `cancellation_reason` text DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `forwarded` tinyint(1) DEFAULT 0,
  `forwarded_to` int(11) DEFAULT NULL,
  `forwarded_by_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hall_bookings`
--

INSERT INTO `hall_bookings` (`booking_id`, `user_id`, `hall_id`, `organizer_name`, `organizer_email`, `organizer_department`, `department_id`, `organizer_contact`, `program_name`, `program_type`, `program_purpose`, `from_date`, `end_date`, `start_time`, `end_time`, `queries`, `created_at`, `updated_at`, `status`, `cancellation_reason`, `reject_reason`, `forwarded`, `forwarded_to`, `forwarded_by_id`) VALUES
(1, 1, 78, 'ARUN', 'sekar@gmail.com', 'Department of Physics', NULL, '9361685137', 'CLASS', 'Class', 'CLASS', '2025-03-29', '2025-03-29', '09:30:00', '10:30:00', 'NIL', '2025-03-28 13:40:42', '2025-04-17 06:14:30', 'Pending', NULL, NULL, 0, NULL, NULL),
(2, 1, 80, 'UDHYA', 'ajaiofficial06@gmail.com', 'Department of Bioinformatics', NULL, '9361685137', 'CLASS', 'Class', 'CLASS', '2025-03-31', '2025-03-31', '10:30:00', '11:30:00', 'NIL', '2025-03-28 13:43:54', '2025-04-14 14:07:09', 'Pending', NULL, NULL, 0, NULL, NULL),
(3, 1, 78, 'DEEPAN', 'ajaiofficial06@gmail.com', 'Department of Chemistry', NULL, '9361685137', 'CLASS', 'Class', 'CLASS', '2025-03-31', '2025-03-31', '14:25:00', '15:25:00', 'nil', '2025-03-30 08:55:36', '2025-04-17 06:15:40', 'Pending', NULL, 'nio', 0, NULL, NULL),
(4, 1, 78, 'JEEVA', 'ajaiofficial06@gmail.com', 'Department of Coastal Disaster Management', NULL, '9361685137', 'CLASS', 'Other', 'nil', '2025-03-31', '2025-03-31', '09:30:00', '10:30:00', 'nil', '2025-03-30 13:23:21', '2025-04-17 06:16:23', 'Pending', NULL, NULL, 0, NULL, NULL),
(5, 1, 93, 'AJAI', 'ajaiofficial06@gmail.com', 'Department of Applied Psychology', NULL, '9361685137', 'CLASS', 'Class', 'class', '2025-04-02', '2025-04-02', '09:30:00', '10:30:00', 'nil', '2025-04-01 06:33:20', '2025-04-14 13:57:58', 'Pending', NULL, 'nil', 1, 49, 3),
(6, 1, 94, 'kevinnnn', 'ajaiofficial06@gmail.com', 'Department of Chemistry', NULL, '78678510478', 'CLASS', 'Other', 'CLASS', '2025-04-03', '2025-04-03', '08:55:00', '09:55:00', 'open class before 10 min', '2025-04-02 15:26:47', '2025-04-17 06:16:58', 'Pending', NULL, NULL, 0, NULL, NULL),
(7, 8, 93, 'ghandhi', 'ajaiofficial06@gmail.com', 'Department of Applied Psychology', NULL, '78678510478', 'CLASS', 'Other', 'nil', '2025-04-05', '2025-04-05', '09:30:00', '10:30:00', 'nil', '2025-04-04 08:23:48', '2025-04-14 13:58:37', 'Pending', NULL, NULL, 1, 49, 8),
(8, 1, 78, 'prabu', 'ajaiofficial06@gmail.com', 'Department of International Business', NULL, '78678510478', 'CLASSll', 'Class', 'class', '2025-04-15', '2025-04-15', '10:30:00', '11:30:00', 'nil', '2025-04-14 08:26:11', '2025-04-14 13:57:27', 'Pending', NULL, NULL, 1, 46, 3),
(9, 1, 93, 'keshi', 'ajaiofficial06@gmail.com', '16', NULL, '99999999999', 'CLASS', 'Other', 'nil', '2025-05-22', '2025-05-22', '10:30:00', '11:30:00', 'nil', '2025-04-14 07:24:04', '2025-04-14 13:57:36', 'Pending', NULL, NULL, 1, 49, 3),
(10, 1, 80, 'keshi', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '78678510478', 'CLASS', 'Other', 'nj', '2025-12-20', '2025-12-20', '10:30:00', '11:30:00', 'mil', '2025-04-14 07:31:20', '2025-04-14 13:56:59', 'Pending', NULL, NULL, 0, NULL, NULL),
(11, 1, 80, 'jeeva', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '78678510478', 'CLASS', 'Class', 'bb', '2025-04-16', '2025-04-16', '10:30:00', '23:30:00', 'nil', '2025-04-14 07:40:28', '2025-04-14 13:57:06', 'Pending', NULL, 'nil', 0, NULL, NULL),
(12, 8, 93, 'keshiii', 'ramesh@gmail.com', 'Department of Tamil', 1, '9361685137', 'CLASSll', 'Other', 'nil', '2025-04-16', '2025-04-16', '10:30:00', '11:30:00', 'nil', '2025-04-14 08:11:09', '2025-04-14 13:58:35', 'Pending', NULL, NULL, 1, 49, 8),
(13, 1, 93, 'ajai', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '9361685137', 'CLASS', 'Class', 'n', '2025-05-05', '2025-05-05', '10:30:00', '11:30:00', 'nil', '2025-04-14 11:44:39', '2025-04-14 13:57:32', 'Pending', NULL, NULL, 1, 49, 3),
(14, 1, 93, 'arun', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '9361685137', 'CLASS', 'Class', 'nil', '2025-04-16', '2025-04-16', '12:30:00', '13:03:00', 'nil', '2025-04-15 07:27:49', '2025-04-15 07:27:49', 'Pending', NULL, NULL, 0, NULL, NULL),
(15, 1, 93, 'kevin', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '78678510478', 'CLASS', 'Class', 'nil', '2025-04-16', '2025-04-16', '14:30:00', '15:30:00', 'nil', '2025-04-15 10:12:20', '2025-04-15 10:12:20', 'Pending', NULL, NULL, 0, NULL, NULL),
(16, 1, 93, 'sumathiiiii', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '99999999999', 'CLASS', 'Other', ' j', '2025-05-22', '2025-05-22', '13:30:00', '14:30:00', 'nil', '2025-04-15 15:13:58', '2025-04-15 15:13:58', 'Pending', NULL, NULL, 0, NULL, NULL),
(17, 1, 93, 'ajai', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '9367435333', 'CLASS', 'Other', 'nol', '2025-06-22', '2025-06-22', '11:30:00', '12:30:00', 'nil', '2025-04-16 14:11:48', '2025-04-16 14:11:48', 'Pending', NULL, NULL, 0, NULL, NULL),
(18, 1, 93, 'sumathi', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, 'f', 'CLASSll', 'Other', 'nmn', '2025-04-19', '2025-04-19', '07:46:00', '08:46:00', 'nil', '2025-04-16 14:17:12', '2025-04-16 14:17:12', 'Pending', NULL, NULL, 0, NULL, NULL),
(19, 1, 93, 'ajai', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '9367435333', 'CLASS', 'Other', 'nol', '2025-06-21', '2025-06-21', '11:30:00', '12:30:00', 'nil', '2025-04-16 14:34:32', '2025-04-16 14:34:32', 'Pending', NULL, NULL, 0, NULL, NULL),
(20, 1, 93, 'ajai', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '9367435333', 'CLASS', 'Other', 'nol', '2025-06-29', '2025-06-29', '11:30:00', '12:30:00', 'nil', '2025-04-16 14:39:48', '2025-04-17 06:17:36', 'Pending', NULL, NULL, 1, 49, 3),
(21, 1, 93, 'ajai', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '9367435333', 'CLASS', 'Other', 'nol', '2025-06-30', '2025-06-30', '11:30:00', '12:30:00', 'nil', '2025-04-16 14:42:36', '2025-04-16 14:42:36', 'Pending', NULL, NULL, 0, NULL, NULL),
(22, 1, 93, 'ajai', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '9367435333', 'CLASS', 'Other', 'nol', '2025-06-02', '2025-06-02', '11:30:00', '12:30:00', 'nil', '2025-04-16 14:54:04', '2025-04-16 14:54:04', 'Pending', NULL, NULL, 0, NULL, NULL),
(23, 1, 99, 'ajai', 'ajaiofficial06@gmail.com', 'Department of International Business', NULL, '9361685137', 'nil', '', 'nil', '2025-04-19', '2025-04-19', '13:37:00', '14:37:00', 'nil', '2025-04-18 08:07:08', '2025-04-18 08:07:08', 'Pending', NULL, NULL, 0, NULL, NULL),
(24, 1, 93, 'moha', 'ajaiofficial06@gmail.com', 'Department of International Business', 9, '99999999999', 'cultural', 'Other', 'nil', '2025-06-02', '2025-06-02', '09:30:00', '10:30:00', 'nil', '2025-04-18 09:34:51', '2025-04-18 09:34:51', 'Pending', NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hall_type`
--

CREATE TABLE `hall_type` (
  `hall_type_id` int(11) NOT NULL,
  `type_name` enum('Seminar Hall','Auditorium','Lecture Hall Room','Conference Hall') NOT NULL,
  `updated_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hall_type`
--

INSERT INTO `hall_type` (`hall_type_id`, `type_name`, `updated_date`) VALUES
(1, 'Seminar Hall', '2024-12-31'),
(2, 'Auditorium', '2024-12-31'),
(3, 'Lecture Hall Room', '2024-12-31'),
(4, 'Conference Hall', '2024-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `phd_notifications`
--

CREATE TABLE `phd_notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `department` varchar(100) DEFAULT NULL,
  `research_area` varchar(255) DEFAULT NULL,
  `additional_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phd_notifications`
--

INSERT INTO `phd_notifications` (`id`, `title`, `description`, `date`, `department`, `research_area`, `additional_details`) VALUES
(1, 'PhD Admission Open', 'Applications invited for PhD programs in Computer Science', '2025-03-25 16:47:43', 'Computer Science', 'Artificial Intelligence', 'Candidates with strong research background are encouraged to apply'),
(2, 'Research Scholarship', 'Fully funded PhD positions available in Quantum Physics', '2025-03-25 16:47:43', 'Physics', 'Quantum Mechanics', 'Stipend and research grant provided'),
(3, 'Interdisciplinary Research Opportunity', 'PhD positions available in Computational Biology', '2025-03-25 16:47:43', 'Biotechnology', 'Computational Methods in Biology', 'Collaborative research with leading international institutions'),
(4, 'ajai', 'nil', '2025-03-25 17:15:42', 'nil', 'nil', 'nil');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `school_id` int(11) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `dean_name` varchar(255) NOT NULL,
  `dean_contact_number` varchar(15) NOT NULL,
  `dean_email` varchar(255) NOT NULL,
  `dean_intercome` varchar(255) NOT NULL,
  `dean_status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`school_id`, `school_name`, `dean_name`, `dean_contact_number`, `dean_email`, `dean_intercome`, `dean_status`) VALUES
(1, 'Subramania Bharathi School of Tamil Language & Literature', 'Dr. S. Sudalai Muthu8888899', '965744334', 'dean.tam@pondiuni.ac.in', ' +91-413-2654 482', 'permanent'),
(2, 'School of Management', 'Dr. Malabika Deoo', '0', 'dean.mgt@pondiuni.edu.in', '+91-413-2654664', 'permanent.'),
(3, 'Ramanujan School of Mathematical Sciences', 'Dr. Rajeswari Seshadri', '0', 'dean.mcs@pondiuni.edu.in', '+91-413-2654-647', 'permanent'),
(4, 'School of Physical, Chemical and Applied Sciences', 'Dr. K. Anbalagan', '0', 'dean.pca@pondiuni.ac.in', ' +91-413-2654-859', 'permanent'),
(5, 'School of Life Science', 'Dr. H. Prathap Kumar Shetty', '0', 'puslsdean@gmail.com', ' +91-413-2654-568', 'permanent'),
(6, 'School of Humanities', 'Prof. Clement Sagayaradja Lourdes', '0', 'dean.hum@pondiuni.edu.in', '+91-413-2654-596', 'permanent'),
(7, 'School of Social Sciences and International Studies', 'Dr. G. Chandhrikaaaaa', '0413 277834838', 'dean.sss@pondiuni.ac.in', '+91-413-2654-815', 'permanent'),
(8, 'School of Engineering and Technology', 'Dr. S. Sivasathya', '0', 'dean.set@pondiuni.ac.in', '+91-413-2654-309', 'permanent'),
(9, 'School of Education', 'Dr. E. Sreekala', '0', 'dean.edu@pondiuni.ac.in', '91-413-2654-613', 'permanent'),
(10, 'School of Performing Arts', 'Dr. P. Sridharan', '0', 'dean.spa@pondiuni.edu.in', '+91-413-2654-800', 'permanent'),
(11, 'School of Law', 'Dr. S. Victor Anandkumar', '0', 'dean.sol@pondiuni.edu.in', '+91-413-2654-911', 'permanent'),
(12, 'School of Media and Communication', 'Dr. R. Sevukan', '0', 'dean.smc@pondiuni.edu.in', '+91-413-2654-47', 'permanent'),
(13, 'Madanjeet School of Green Energy Technologies', 'Dr. A. Subramania', '0', 'dean.get@pondiuni.edu.in', '+91-413-2654-939', 'permanent');

-- --------------------------------------------------------

--
-- Table structure for table `university_circulars`
--

CREATE TABLE `university_circulars` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `issue_date` date NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_circulars`
--

INSERT INTO `university_circulars` (`id`, `title`, `description`, `issue_date`, `department`, `created_by`, `created_at`) VALUES
(1, 'Academic Calendar Update', 'Important changes in academic schedule for upcoming semester', '2024-06-01', 'Academic Affairs', 'Registrar Office', '2025-03-25 16:47:43'),
(2, 'Research Grant Announcement', 'New funding opportunities for faculty research', '2024-05-15', 'Research Development', 'Research Committee', '2025-03-25 16:47:43'),
(3, 'Campus Infrastructure Improvements', 'Upcoming renovation plans for university facilities', '2024-04-20', 'Administration', 'Vice Chancellor', '2025-03-25 16:47:43');

-- --------------------------------------------------------

--
-- Table structure for table `university_events`
--

CREATE TABLE `university_events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `organizer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_events`
--

INSERT INTO `university_events` (`id`, `title`, `description`, `date`, `location`, `organizer`, `created_at`) VALUES
(1, 'Research Symposium 2024', 'Annual research conference for interdisciplinary studies', '2024-07-15', 'Main Auditorium', 'Research Department', '2025-03-25 16:47:43'),
(2, 'Innovation Workshop', 'Hands-on workshop on emerging technologies', '2024-08-20', 'Science Block', 'Technology Center', '2025-03-25 16:47:43'),
(3, 'International Conference', 'Global perspectives in academic research', '2024-09-10', 'Conference Center', 'International Relations Officee', '2025-03-25 16:47:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('HOD','Dean','Professor','Admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department_id` int(11) NOT NULL,
  `otp` int(6) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `department_id`, `otp`, `email_verified`) VALUES
(1, 'AJAI SEKAR', 'ajaiofficial06@gmail.com', '$2y$10$PBtbLTeoS/.dZ1WZuPprGeXnthaJucCuE4EJ/JQEXKZvODSI9SBXe', 'Professor', '2025-03-28 08:32:11', 9, NULL, 0),
(2, 'AJAI', 'ajaiofficial06@gmail.com', '$2y$10$vJdchMKr0EfK/ZIs0LHSQesLzVdvBIq8xvgCk6X0eJne33t1LJQ/6', 'Admin', '2025-03-28 08:32:57', 14, NULL, 0),
(3, 'AJAI SEKAR', 'ajaiofficial06@gmail.com', '$2y$10$m9ONmDOWvFJ4wLPTEgWc/una7dtBW9OVJqJe0AZi5umNz/CZ13dWa', 'HOD', '2025-04-04 14:45:50', 9, NULL, 0),
(8, 'ramesh', 'ramesh@gmail.com', '$2y$10$Pjly1qjlZd4UHaE2IljpYefZN9vYHJp2A5BzD5zfLolQvKaXSN0rO', 'HOD', '2025-04-09 14:33:23', 1, NULL, 0),
(9, 'deppan', 'deeban@gmail.com', '$2y$10$eeFCn4lGqQfdTcPCJGTBfeoPlhXurinT8GsmVuKG5bJ1lR8mE5RbO', 'HOD', '2025-04-14 10:18:54', 46, NULL, 0),
(10, 'Kishore', 'kishor@gmail.com', '$2y$10$oeFDGC3TOeXCUxwfH1RtAeQWot2Fu5.qfVveqyku.mEtMXLlm.DT2', 'HOD', '2025-04-14 11:40:02', 49, NULL, 0),
(11, 'praven', 'praven@gmail.com', '$2y$10$36miRhvoQ09Py4bRzMKQXerax/C03FBMEtTQxS896x0Sb4Yz1Oa..', 'HOD', '2025-04-14 14:28:29', 33, NULL, 0),
(12, 'ramesh', 'grayman9361685@gmail.com', '$2y$10$WeR.cvwjTvJaJPecfzS.c.9SjOH1ynH/FEFQK7iyJ62PJNUjQ5Ztu', 'Professor', '2025-04-18 07:30:34', 13, NULL, 1),
(13, 'kishor', 'grayman9361685@gmail.com', '$2y$10$q542kAg/39D8CkesUhU/JeUWsW3AD7UVkZCpbxq2N1Ji3cqZ9uWr2', 'HOD', '2025-04-18 07:36:10', 16, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_requests`
--

CREATE TABLE `user_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Pending','Processing','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `admin_response` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_requests`
--

INSERT INTO `user_requests` (`id`, `user_id`, `request_type`, `subject`, `message`, `status`, `admin_response`, `created_at`, `responded_at`) VALUES
(1, 1, 'Feedback', 'nil', 'nil', 'Rejected', 'nil', '2025-04-01 18:01:06', '2025-04-01 18:03:31'),
(2, 1, 'Feedback', 'nil', 'nil', 'Processing', 'ok', '2025-04-12 20:18:16', '2025-04-12 20:19:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `bookings_ibfk_2` (`hall_id`);

--
-- Indexes for table `ccc_hall_bookings`
--
ALTER TABLE `ccc_hall_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_ccc_user` (`user_id`),
  ADD KEY `fk_ccc_hall` (`hall_id`),
  ADD KEY `fk_ccc_department` (`department_id`);

--
-- Indexes for table `ccc_hall_payments`
--
ALTER TABLE `ccc_hall_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_ccc_booking` (`booking_id`);

--
-- Indexes for table `contact_us`
--
ALTER TABLE `contact_us`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `email_verification`
--
ALTER TABLE `email_verification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `halls`
--
ALTER TABLE `halls`
  ADD PRIMARY KEY (`hall_id`),
  ADD KEY `hall_type` (`hall_type`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `hall_bookings`
--
ALTER TABLE `hall_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_user` (`user_id`),
  ADD KEY `fk_hall` (`hall_id`),
  ADD KEY `fk_forwarded_to` (`forwarded_to`);

--
-- Indexes for table `hall_type`
--
ALTER TABLE `hall_type`
  ADD PRIMARY KEY (`hall_type_id`);

--
-- Indexes for table `phd_notifications`
--
ALTER TABLE `phd_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`school_id`);

--
-- Indexes for table `university_circulars`
--
ALTER TABLE `university_circulars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `university_events`
--
ALTER TABLE `university_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email_role` (`email`,`role`);

--
-- Indexes for table `user_requests`
--
ALTER TABLE `user_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `ccc_hall_bookings`
--
ALTER TABLE `ccc_hall_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ccc_hall_payments`
--
ALTER TABLE `ccc_hall_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_us`
--
ALTER TABLE `contact_us`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `email_verification`
--
ALTER TABLE `email_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `halls`
--
ALTER TABLE `halls`
  MODIFY `hall_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `hall_bookings`
--
ALTER TABLE `hall_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `hall_type`
--
ALTER TABLE `hall_type`
  MODIFY `hall_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `phd_notifications`
--
ALTER TABLE `phd_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `school_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `university_circulars`
--
ALTER TABLE `university_circulars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `university_events`
--
ALTER TABLE `university_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_requests`
--
ALTER TABLE `user_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`hall_id`);

--
-- Constraints for table `ccc_hall_bookings`
--
ALTER TABLE `ccc_hall_bookings`
  ADD CONSTRAINT `fk_ccc_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_ccc_hall` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`hall_id`),
  ADD CONSTRAINT `fk_ccc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ccc_hall_payments`
--
ALTER TABLE `ccc_hall_payments`
  ADD CONSTRAINT `fk_ccc_booking` FOREIGN KEY (`booking_id`) REFERENCES `ccc_hall_bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `halls`
--
ALTER TABLE `halls`
  ADD CONSTRAINT `halls_ibfk_1` FOREIGN KEY (`hall_type`) REFERENCES `hall_type` (`hall_type_id`),
  ADD CONSTRAINT `halls_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`),
  ADD CONSTRAINT `halls_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `hall_bookings`
--
ALTER TABLE `hall_bookings`
  ADD CONSTRAINT `fk_forwarded_to` FOREIGN KEY (`forwarded_to`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_hall` FOREIGN KEY (`hall_id`) REFERENCES `halls` (`hall_id`),
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_requests`
--
ALTER TABLE `user_requests`
  ADD CONSTRAINT `user_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
