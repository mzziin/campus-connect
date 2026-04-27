-- ============================================================
-- Campus Connect — Complete Database Setup
-- Compatible with: MariaDB 10.4+ / MySQL 8.x via XAMPP
-- Run this file via phpMyAdmin (Import tab) or MySQL CLI
-- ============================================================

-- Create the database
CREATE DATABASE IF NOT EXISTS `campus_connect`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `campus_connect`;

-- ============================================================
-- Drop tables in reverse-dependency order (safe re-run)
-- ============================================================

DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `conversations`;
DROP TABLE IF EXISTS `book_inquiries`;
DROP TABLE IF EXISTS `book_images`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `book_conditions`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- Table 1: users
-- ============================================================
CREATE TABLE `users` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name`       VARCHAR(150) NOT NULL,
    `email`           VARCHAR(200) NOT NULL UNIQUE,
    `password_hash`   VARCHAR(255) NOT NULL,
    `phone`           VARCHAR(20) DEFAULT NULL,
    `department`      VARCHAR(100) DEFAULT NULL,
    `college_id`      VARCHAR(50) DEFAULT NULL UNIQUE,
    `account_status`  ENUM('pending','approved','rejected','banned') NOT NULL DEFAULT 'pending',
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 2: admins
-- ============================================================
CREATE TABLE `admins` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`        VARCHAR(100) NOT NULL UNIQUE,
    `email`           VARCHAR(200) NOT NULL UNIQUE,
    `password_hash`   VARCHAR(255) NOT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 3: categories
-- ============================================================
CREATE TABLE `categories` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL UNIQUE,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 4: book_conditions
-- ============================================================
CREATE TABLE `book_conditions` (
    `id`      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `label`   VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 5: books
-- ============================================================
CREATE TABLE `books` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `seller_id`       INT UNSIGNED NOT NULL,
    `category_id`     INT UNSIGNED NOT NULL,
    `condition_id`    INT UNSIGNED NOT NULL,
    `title`           VARCHAR(255) NOT NULL,
    `author`          VARCHAR(150) DEFAULT NULL,
    `description`     TEXT DEFAULT NULL,
    `price`           DECIMAL(8,2) DEFAULT NULL,
    `listing_type`    ENUM('sell','giveaway') NOT NULL DEFAULT 'sell',
    `status`          ENUM('available','sold','deleted') NOT NULL DEFAULT 'available',
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`seller_id`)    REFERENCES `users`(`id`)           ON DELETE RESTRICT,
    FOREIGN KEY (`category_id`)  REFERENCES `categories`(`id`)      ON DELETE RESTRICT,
    FOREIGN KEY (`condition_id`) REFERENCES `book_conditions`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 6: book_images
-- ============================================================
CREATE TABLE `book_images` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `book_id`     INT UNSIGNED NOT NULL,
    `image_path`  VARCHAR(300) NOT NULL,
    `is_primary`  TINYINT(1) NOT NULL DEFAULT 0,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 7: book_inquiries
-- ============================================================
CREATE TABLE `book_inquiries` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `book_id`     INT UNSIGNED NOT NULL,
    `buyer_id`    INT UNSIGNED NOT NULL,
    `message`     TEXT DEFAULT NULL,
    `status`      ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `unique_inquiry` (`book_id`, `buyer_id`),

    FOREIGN KEY (`book_id`)  REFERENCES `books`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 8: conversations
-- ============================================================
CREATE TABLE `conversations` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `inquiry_id`  INT UNSIGNED NOT NULL UNIQUE,
    `book_id`     INT UNSIGNED NOT NULL,
    `seller_id`   INT UNSIGNED NOT NULL,
    `buyer_id`    INT UNSIGNED NOT NULL,
    `status`      ENUM('active','completed') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`inquiry_id`) REFERENCES `book_inquiries`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`book_id`)    REFERENCES `books`(`id`)          ON DELETE RESTRICT,
    FOREIGN KEY (`seller_id`)  REFERENCES `users`(`id`)          ON DELETE RESTRICT,
    FOREIGN KEY (`buyer_id`)   REFERENCES `users`(`id`)          ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 9: messages
-- ============================================================
CREATE TABLE `messages` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT UNSIGNED NOT NULL,
    `sender_id`       INT UNSIGNED NOT NULL,
    `body`            TEXT NOT NULL,
    `sent_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`)       REFERENCES `users`(`id`)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 10: transactions
-- ============================================================
CREATE TABLE `transactions` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT UNSIGNED NOT NULL UNIQUE,
    `book_id`         INT UNSIGNED NOT NULL,
    `seller_id`       INT UNSIGNED NOT NULL,
    `buyer_id`        INT UNSIGNED NOT NULL,
    `status`          ENUM('completed') NOT NULL DEFAULT 'completed',
    `rating`          TINYINT UNSIGNED DEFAULT NULL,
    `feedback`        TEXT DEFAULT NULL,
    `completed_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`book_id`)         REFERENCES `books`(`id`)         ON DELETE RESTRICT,
    FOREIGN KEY (`seller_id`)       REFERENCES `users`(`id`)         ON DELETE RESTRICT,
    FOREIGN KEY (`buyer_id`)        REFERENCES `users`(`id`)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 11: reports
-- ============================================================
CREATE TABLE `reports` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id`       INT UNSIGNED NOT NULL,
    `report_type`       ENUM('book','user') NOT NULL,
    `reported_book_id`  INT UNSIGNED DEFAULT NULL,
    `reported_user_id`  INT UNSIGNED DEFAULT NULL,
    `reason`            VARCHAR(255) NOT NULL,
    `details`           TEXT DEFAULT NULL,
    `status`            ENUM('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`reporter_id`)      REFERENCES `users`(`id`)  ON DELETE RESTRICT,
    FOREIGN KEY (`reported_book_id`) REFERENCES `books`(`id`)  ON DELETE SET NULL,
    FOREIGN KEY (`reported_user_id`) REFERENCES `users`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SEED DATA
-- ============================================================

-- ------------------------------------------------------------
-- Categories
-- ------------------------------------------------------------
INSERT INTO `categories` (`name`) VALUES
('Engineering'),
('Science'),
('Mathematics'),
('Arts & Humanities'),
('Commerce'),
('Medical'),
('Computer Science'),
('Law'),
('Social Sciences'),
('Languages');

-- ------------------------------------------------------------
-- Book Conditions
-- ------------------------------------------------------------
INSERT INTO `book_conditions` (`label`) VALUES
('New'),
('Like New'),
('Good'),
('Fair'),
('Poor');

-- ------------------------------------------------------------
-- Default Admin
-- Password: Admin@1234
-- ------------------------------------------------------------
INSERT INTO `admins` (`username`, `email`, `password_hash`) VALUES
('admin', 'admin@campusconnect.local', '$2y$10$XIji9w31iWAnRJclaRtJIeCQWDSFPlFXq7XClsg6OY1mXFeSsiR92');

-- ------------------------------------------------------------
-- Test Users (password for ALL users is: password)
-- ------------------------------------------------------------
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `phone`, `department`, `college_id`, `account_status`) VALUES
(1, 'Rahul Menon',   'rahul@student.com',   '$2y$10$.Eq4w7CZoOTHkMCL6Ixd..dbvX7VwtGQXC0alQg3MTjqxkqNC.GnW', '9876543210', 'Computer Science', 'CS2024001', 'approved'),
(2, 'Priya Nair',    'priya@student.com',   '$2y$10$.Eq4w7CZoOTHkMCL6Ixd..dbvX7VwtGQXC0alQg3MTjqxkqNC.GnW', '9876543211', 'Electronics',      'EC2024001', 'approved'),
(3, 'Arjun Das',     'arjun@student.com',   '$2y$10$.Eq4w7CZoOTHkMCL6Ixd..dbvX7VwtGQXC0alQg3MTjqxkqNC.GnW', '9876543212', 'Mechanical',       'ME2024001', 'approved'),
(4, 'Sneha Sharma',  'sneha@student.com',   '$2y$10$.Eq4w7CZoOTHkMCL6Ixd..dbvX7VwtGQXC0alQg3MTjqxkqNC.GnW', '9876543213', 'Civil',            'CE2024001', 'pending');

-- ------------------------------------------------------------
-- Sample Books
-- (seller_id=1 is Rahul, seller_id=3 is Arjun)
-- ------------------------------------------------------------
INSERT INTO `books` (`id`, `seller_id`, `category_id`, `condition_id`, `title`, `author`, `description`, `price`, `listing_type`, `status`) VALUES
(1, 1, 1, 2, 'Engineering Mathematics',  'H.K. Dass',         'Complete textbook for B.Tech 1st and 2nd semester. Includes solved examples.', 150.00, 'sell',     'available'),
(2, 1, 7, 1, 'Introduction to Algorithms', 'Cormen, Leiserson', 'Classic algorithms textbook, 3rd edition. Like new condition.',                 500.00, 'sell',     'available'),
(3, 3, 1, 3, 'Mechanics of Materials',   'R.C. Hibbeler',     'Slightly highlighted but all pages intact.',                                    0.00,   'giveaway', 'available'),
(4, 3, 7, 2, 'Data Structures Using C',  'Reema Thareja',     'Good condition, covers all DS topics.',                                         200.00, 'sell',     'sold');

-- ------------------------------------------------------------
-- Book Images (sample — files don't exist on disk, just for DB integrity)
-- ------------------------------------------------------------
INSERT INTO `book_images` (`book_id`, `image_path`, `is_primary`) VALUES
(1, 'uploads/books/eng_maths_cover.jpg', 1),
(1, 'uploads/books/eng_maths_pages.jpg', 0),
(2, 'uploads/books/algorithms_cover.jpg', 1),
(3, 'uploads/books/mechanics_cover.jpg', 1),
(4, 'uploads/books/ds_cover.jpg', 1);

-- ------------------------------------------------------------
-- Sample Inquiry: Priya (user 2) inquired about Arjun's (user 3) book 4
-- Status: accepted → conversation created → transaction completed
-- ------------------------------------------------------------
INSERT INTO `book_inquiries` (`id`, `book_id`, `buyer_id`, `message`, `status`) VALUES
(1, 4, 2, 'Hi, is this Data Structures book still available? I need it for my exams.', 'accepted');

-- ------------------------------------------------------------
-- Conversation for the accepted inquiry
-- ------------------------------------------------------------
INSERT INTO `conversations` (`id`, `inquiry_id`, `book_id`, `seller_id`, `buyer_id`, `status`) VALUES
(1, 1, 4, 3, 2, 'completed');

-- ------------------------------------------------------------
-- Messages in the conversation
-- ------------------------------------------------------------
INSERT INTO `messages` (`conversation_id`, `sender_id`, `body`, `sent_at`) VALUES
(1, 2, 'Hi Arjun, when can we meet for the book?',          NOW() - INTERVAL 3 HOUR),
(1, 3, 'I am free tomorrow at 4pm near the library.',        NOW() - INTERVAL 2 HOUR),
(1, 2, 'Perfect, I will be there. Thanks!',                  NOW() - INTERVAL 1 HOUR);

-- ------------------------------------------------------------
-- Completed Transaction (with rating from buyer)
-- ------------------------------------------------------------
INSERT INTO `transactions` (`conversation_id`, `book_id`, `seller_id`, `buyer_id`, `status`, `rating`, `feedback`) VALUES
(1, 4, 3, 2, 'completed', 4, 'Good condition book. Smooth handover at the library.');

-- ------------------------------------------------------------
-- Sample Report: Priya reported book 3 as having wrong condition
-- Status: pending (for admin to review)
-- ------------------------------------------------------------
INSERT INTO `reports` (`reporter_id`, `report_type`, `reported_book_id`, `reported_user_id`, `reason`, `details`, `status`) VALUES
(2, 'book', 3, NULL, 'Fake listing', 'The book condition listed as Good but it has torn pages.', 'pending');


-- ============================================================
-- SETUP COMPLETE
-- ============================================================
-- Admin Login:  admin@campusconnect.local / Admin@1234
-- User Logins:  rahul@student.com / password
--               priya@student.com / password
--               arjun@student.com / password
--               sneha@student.com / password  (pending approval)
-- ============================================================
