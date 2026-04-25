-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2026 at 12:37 PM
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
-- Database: `campus_connect`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Site Admin', 'admin@campusconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-25 09:45:09'),
(2, 'Second Admin', 'admin2@campusconnect.com', 'hashed_password_here', '2026-04-25 09:53:27');

-- --------------------------------------------------------

--
-- Table structure for table `bookconditions`
--

CREATE TABLE `bookconditions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookconditions`
--

INSERT INTO `bookconditions` (`id`, `name`, `description`) VALUES
(1, 'New', 'Brand new, never used or opened'),
(2, 'Good', 'Minor wear on cover, all pages clean and readable'),
(3, 'Medium', 'Some highlights or notes, fully readable'),
(4, 'Used', 'Visible wear and tear, usable'),
(5, 'Poor', 'Heavy damage but content still readable');

-- --------------------------------------------------------

--
-- Table structure for table `bookimages`
--

CREATE TABLE `bookimages` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookimages`
--

INSERT INTO `bookimages` (`id`, `book_id`, `image_url`, `is_primary`, `uploaded_at`) VALUES
(1, 1, 'uploads/books/eng_maths_cover.jpg', 1, '2026-04-25 09:45:09'),
(2, 1, 'uploads/books/eng_maths_pages.jpg', 0, '2026-04-25 09:45:09');

-- --------------------------------------------------------

--
-- Table structure for table `bookinquiries`
--

CREATE TABLE `bookinquiries` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookinquiries`
--

INSERT INTO `bookinquiries` (`id`, `book_id`, `buyer_id`, `message`, `status`, `created_at`, `responded_at`) VALUES
(1, 1, 2, 'Hi, is this book still available? I need it for my exams.', 'accepted', '2026-04-25 09:45:09', '2026-04-25 09:45:09'),
(3, 4, 2, 'Is this available for this semester?', 'accepted', '2026-04-25 10:33:33', '2026-04-25 10:33:46');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `condition_id` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT 0,
  `pickup_location` varchar(150) DEFAULT NULL,
  `status` enum('available','reserved','sold') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `description`, `seller_id`, `category_id`, `condition_id`, `price`, `is_free`, `pickup_location`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Engineering Mathematics', 'H.K. Dass', 'Complete textbook for B.Tech 1st and 2nd semester.', 1, 1, 2, 150.00, 0, 'ABC College Main Gate', 'sold', '2026-04-25 09:45:09', '2026-04-25 09:45:09'),
(4, 'Data Structures', 'Mark Allen Weiss', NULL, 4, 1, 2, 200.00, 0, 'Library Block', 'sold', '2026-04-25 10:33:15', '2026-04-25 10:35:54');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(3, 'Arts & Humanities'),
(4, 'Commerce & Business'),
(1, 'Engineering'),
(6, 'Law'),
(2, 'Medical'),
(7, 'Other'),
(5, 'Science');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `inquiry_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `status` enum('active','closed','completed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `inquiry_id`, `book_id`, `buyer_id`, `seller_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 1, 'completed', '2026-04-25 09:45:09', '2026-04-25 09:45:09'),
(3, 3, 4, 2, 4, 'completed', '2026-04-25 10:34:34', '2026-04-25 10:35:54');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `message_text`, `is_read`, `created_at`) VALUES
(1, 1, 2, 'Hi Rahul, when can we meet?', 1, '2026-04-25 09:45:09'),
(2, 1, 1, 'I am free tomorrow at 4pm at the gate.', 1, '2026-04-25 09:45:09'),
(3, 1, 2, 'Perfect, I will be there.', 1, '2026-04-25 09:45:09'),
(4, 3, 2, 'Can we meet at 4pm today?', 0, '2026-04-25 10:35:22'),
(5, 3, 4, 'Yes, library entrance at 4pm.', 0, '2026-04-25 10:35:22');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `target_type` enum('user','book') NOT NULL,
  `target_id` int(11) NOT NULL,
  `reason` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `reporter_id`, `target_type`, `target_id`, `reason`, `description`, `status`, `reviewed_by`, `created_at`) VALUES
(1, 2, 'book', 2, 'Fake listing', 'The book condition shown in photos does not match the actual condition described.', 'resolved', 1, '2026-04-25 09:45:09');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `exchange_location` varchar(150) DEFAULT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL COMMENT '1 to 5 — filled by buyer after completion',
  `feedback_comment` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `conversation_id`, `book_id`, `exchange_location`, `status`, `completed_at`, `rating`, `feedback_comment`, `created_at`) VALUES
(1, 1, 1, 'ABC College Main Gate', 'completed', '2026-04-25 09:45:09', 4, 'Good condition. Smooth handover.', '2026-04-25 09:45:09'),
(2, 3, 4, 'Library Block Entrance', 'completed', '2026-04-25 10:35:42', 5, 'Exactly as described. Very helpful seller.', '2026-04-25 10:35:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `college` varchar(150) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `account_status` enum('pending','approved','suspended') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `profile_picture`, `college`, `state`, `district`, `city`, `password_hash`, `account_status`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 'Rahul Menon', 'rahul@student.com', '9876543210', NULL, 'ABC Engineering College', 'Kerala', 'Pathanamthitta', 'Thiruvalla', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'approved', 1, '2026-04-25 09:45:09', '2026-04-25 09:45:09', '2026-04-25 09:45:09'),
(2, 'Priya Nair', 'priya@student.com', '9876543211', NULL, 'ABC Engineering College', 'Kerala', 'Pathanamthitta', 'Adoor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'approved', 1, '2026-04-25 09:45:09', '2026-04-25 09:45:09', '2026-04-25 09:45:09'),
(3, 'Arjun Das', 'arjun@student.com', '9876543212', NULL, 'ABC Engineering College', 'Kerala', 'Pathanamthitta', 'Ranni', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'approved', 1, '2026-04-25 09:59:29', '2026-04-25 09:45:09', '2026-04-25 09:59:29'),
(4, 'Test Student', 'test@student.com', '9998887770', NULL, 'ABC College', NULL, NULL, 'Thiruvalla', 'hashed_pw', 'approved', 1, '2026-04-25 10:33:03', '2026-04-25 10:32:50', '2026-04-25 10:33:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admins_email_unique` (`email`);

--
-- Indexes for table `bookconditions`
--
ALTER TABLE `bookconditions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bookconditions_name_unique` (`name`);

--
-- Indexes for table `bookimages`
--
ALTER TABLE `bookimages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bookimages_book` (`book_id`);

--
-- Indexes for table `bookinquiries`
--
ALTER TABLE `bookinquiries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_inquiry` (`book_id`,`buyer_id`),
  ADD KEY `fk_inquiry_buyer` (`buyer_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_books_seller` (`seller_id`),
  ADD KEY `fk_books_category` (`category_id`),
  ADD KEY `fk_books_condition` (`condition_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_name_unique` (`name`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_conversation` (`inquiry_id`),
  ADD KEY `fk_conv_book` (`book_id`),
  ADD KEY `fk_conv_buyer` (`buyer_id`),
  ADD KEY `fk_conv_seller` (`seller_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_msg_conversation` (`conversation_id`),
  ADD KEY `fk_msg_sender` (`sender_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_report_reporter` (`reporter_id`),
  ADD KEY `fk_report_reviewer` (`reviewed_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_txn_conversation` (`conversation_id`),
  ADD KEY `fk_txn_book` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`),
  ADD KEY `fk_users_approved_by` (`approved_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookconditions`
--
ALTER TABLE `bookconditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bookimages`
--
ALTER TABLE `bookimages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookinquiries`
--
ALTER TABLE `bookinquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookimages`
--
ALTER TABLE `bookimages`
  ADD CONSTRAINT `fk_bookimages_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bookinquiries`
--
ALTER TABLE `bookinquiries`
  ADD CONSTRAINT `fk_inquiry_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inquiry_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_books_condition` FOREIGN KEY (`condition_id`) REFERENCES `bookconditions` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_books_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conv_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conv_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conv_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `bookinquiries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conv_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_report_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_report_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_txn_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_txn_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
