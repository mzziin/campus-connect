-- Campus Connect Database Schema
-- MySQL 8.x, InnoDB, utf8mb4_unicode_ci

-- Table 1: users
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(150) NOT NULL,
    email           VARCHAR(200) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    phone           VARCHAR(20) DEFAULT NULL,
    department      VARCHAR(100) DEFAULT NULL,
    college_id      VARCHAR(50) DEFAULT NULL UNIQUE,
    account_status  ENUM('pending','approved','rejected','banned') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: admins
CREATE TABLE admins (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(100) NOT NULL UNIQUE,
    email           VARCHAR(200) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 3: categories
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 4: book_conditions
CREATE TABLE book_conditions (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label   VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 5: books
CREATE TABLE books (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id       INT UNSIGNED NOT NULL,
    category_id     INT UNSIGNED NOT NULL,
    condition_id    INT UNSIGNED NOT NULL,
    title           VARCHAR(255) NOT NULL,
    author          VARCHAR(150) DEFAULT NULL,
    description     TEXT DEFAULT NULL,
    price           DECIMAL(8,2) DEFAULT NULL,
    listing_type    ENUM('sell','giveaway') NOT NULL DEFAULT 'sell',
    status          ENUM('available','sold','deleted') NOT NULL DEFAULT 'available',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (seller_id)    REFERENCES users(id)           ON DELETE RESTRICT,
    FOREIGN KEY (category_id)  REFERENCES categories(id)      ON DELETE RESTRICT,
    FOREIGN KEY (condition_id) REFERENCES book_conditions(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 6: book_images
CREATE TABLE book_images (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id     INT UNSIGNED NOT NULL,
    image_path  VARCHAR(300) NOT NULL,
    is_primary  TINYINT(1) NOT NULL DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 7: book_inquiries
CREATE TABLE book_inquiries (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id     INT UNSIGNED NOT NULL,
    buyer_id    INT UNSIGNED NOT NULL,
    message     TEXT DEFAULT NULL,
    status      ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_inquiry (book_id, buyer_id),

    FOREIGN KEY (book_id)  REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 8: conversations
CREATE TABLE conversations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inquiry_id  INT UNSIGNED NOT NULL UNIQUE,
    book_id     INT UNSIGNED NOT NULL,
    seller_id   INT UNSIGNED NOT NULL,
    buyer_id    INT UNSIGNED NOT NULL,
    status      ENUM('active','completed') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (inquiry_id) REFERENCES book_inquiries(id) ON DELETE RESTRICT,
    FOREIGN KEY (book_id)    REFERENCES books(id)          ON DELETE RESTRICT,
    FOREIGN KEY (seller_id)  REFERENCES users(id)          ON DELETE RESTRICT,
    FOREIGN KEY (buyer_id)   REFERENCES users(id)          ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 9: messages
CREATE TABLE messages (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    sender_id       INT UNSIGNED NOT NULL,
    body            TEXT NOT NULL,
    sent_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id)       REFERENCES users(id)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 10: transactions
CREATE TABLE transactions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL UNIQUE,
    book_id         INT UNSIGNED NOT NULL,
    seller_id       INT UNSIGNED NOT NULL,
    buyer_id        INT UNSIGNED NOT NULL,
    status          ENUM('completed') NOT NULL DEFAULT 'completed',
    rating          TINYINT UNSIGNED DEFAULT NULL,
    feedback        TEXT DEFAULT NULL,
    completed_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE RESTRICT,
    FOREIGN KEY (book_id)         REFERENCES books(id)         ON DELETE RESTRICT,
    FOREIGN KEY (seller_id)       REFERENCES users(id)         ON DELETE RESTRICT,
    FOREIGN KEY (buyer_id)        REFERENCES users(id)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 11: reports
CREATE TABLE reports (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id       INT UNSIGNED NOT NULL,
    report_type       ENUM('book','user') NOT NULL,
    reported_book_id  INT UNSIGNED DEFAULT NULL,
    reported_user_id  INT UNSIGNED DEFAULT NULL,
    reason            VARCHAR(255) NOT NULL,
    details           TEXT DEFAULT NULL,
    status            ENUM('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (reporter_id)      REFERENCES users(id)  ON DELETE RESTRICT,
    FOREIGN KEY (reported_book_id) REFERENCES books(id)  ON DELETE SET NULL,
    FOREIGN KEY (reported_user_id) REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
