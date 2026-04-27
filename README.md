# Campus Connect

A college-exclusive, peer-to-peer book exchange platform built with PHP and MySQL. Students can list second-hand textbooks for sale or giveaway, browse available books, contact sellers, and complete offline transactions on campus.

## Features

- **User Registration & Approval**: Students register with their college ID and await admin approval before accessing the platform
- **Book Listings**: List books with multiple images, condition details, pricing, or giveaway options
- **Search & Browse**: Filter books by category, search by title/author
- **Inquiry System**: Send inquiries to sellers, accept/reject requests
- **Real-time Chat**: Built-in messaging system for buyer-seller communication
- **Transaction Management**: Track completed transactions and leave reviews/ratings
- **Admin Dashboard**: Approve users, manage book listings, review reports
- **Reporting System**: Report inappropriate users or book listings

## Tech Stack

- **Frontend**: HTML5, Tailwind CSS (CDN)
- **Backend**: Core PHP 8.x (procedural/modular)
- **Database**: MySQL 8.x
- **Authentication**: PHP Sessions + `password_hash()`
- **Server**: Apache (XAMPP)

## Installation

### Prerequisites

- XAMPP (or equivalent PHP/MySQL environment)
- PHP 8.x
- MySQL 8.x
- Apache web server

### Setup Steps

1. **Clone or download** the project to `htdocs/campus-connect/` in your XAMPP installation

2. **Create the database**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Create a new database named `campus_connect`
   - Import `schema.sql` to create all tables
   - Import `seed.sql` to populate categories and book conditions
   - Import `seed_admin.sql` to create the default admin account

3. **Configure database connection**:
   - Edit `config/db.php` if your database credentials differ from the defaults:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'campus_connect');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Set up file uploads**:
   - Ensure the `uploads/books/` directory exists and is writable
   - Set appropriate permissions (777 on development, or configure Apache user permissions)

5. **Start the servers**:
   - Start Apache and MySQL from XAMPP Control Panel

6. **Access the application**:
   - Main site: `http://localhost/campus-connect/`
   - Admin panel: `http://localhost/campus-connect/admin/`

### Default Admin Credentials

- **Username**: `admin`
- **Password**: `admin123`

*Change this immediately after first login via the database or by updating `seed_admin.sql` before import.*

## Project Structure

```
campus-connect/
├── config/
│   └── db.php                    # PDO database connection
├── includes/
│   ├── auth.php                  # Session helpers, login/logout functions
│   ├── middleware.php            # Route protection guard functions
│   ├── helpers.php               # Utility functions (flash, redirect, etc.)
│   ├── header.php                # Shared nav/header partial
│   └── footer.php                # Shared footer partial
├── admin/
│   ├── index.php                 # Admin dashboard
│   ├── users.php                 # Approve / reject users
│   ├── books.php                 # Manage book listings
│   ├── reports.php               # Review reported content
│   └── login.php                 # Admin-only login page
├── pages/
│   ├── register.php              # User registration
│   ├── login.php                 # User login
│   ├── home.php                  # Book listing / search
│   ├── book_detail.php           # Single book view + inquiry form
│   ├── book_add.php              # Add new book listing
│   ├── book_edit.php             # Edit existing listing
│   ├── dashboard.php             # User dashboard
│   ├── inquiries.php             # Manage received inquiries (seller view)
│   ├── chat.php                  # Conversation / messaging UI
│   ├── report.php                # Report a user or book
│   └── transaction_review.php    # Review and rate completed transactions
├── uploads/
│   └── books/                    # Uploaded book images
├── schema.sql                    # Full DB schema
├── seed.sql                      # Seed data for categories and conditions
├── seed_admin.sql                # Default admin account insert
└── index.php                     # Entry point — redirects to home or login
```

## User Roles

| Role | Description | Access |
|------|-------------|--------|
| Guest | Unregistered visitor | Registration page only |
| Pending User | Registered but not approved | Waiting screen only |
| Approved User | Fully active student | All buyer/seller features |
| Admin | Platform administrator | Admin dashboard + moderation |

## Database Schema

The application uses 11 main tables:

- **users**: Student accounts with approval status
- **admins**: Administrator accounts
- **categories**: Book categories (e.g., Engineering, Medical, Arts)
- **book_conditions**: Condition labels (e.g., New, Like New, Good, Fair, Poor)
- **books**: Book listings with seller, category, condition, and status
- **book_images**: Multiple images per book with primary image flag
- **book_inquiries**: Buyer inquiries to sellers
- **conversations**: Chat sessions between buyers and sellers
- **messages**: Individual messages within conversations
- **transactions**: Completed transactions with ratings and feedback
- **reports**: User-reported content for moderation

See `schema.sql` for the complete schema definition.

## Key Workflows

### Registration Flow
1. Student fills registration form (name, email, password, phone, department, college ID)
2. Account created with `account_status = 'pending'`
3. Admin approves/rejects via admin dashboard
4. Upon approval, student can access the platform

### Book Listing Flow
1. Seller adds book with title, author, description, price, condition, category
2. Uploads 1-5 images (first image is marked as primary)
3. Listing appears on home page for other students to browse
4. Seller can edit or delete their listings at any time

### Transaction Flow
1. Buyer sends inquiry with message
2. Seller accepts/rejects inquiry
3. If accepted, a conversation is created for messaging
4. Buyer and seller chat to arrange meetup
5. After exchange, transaction is marked as completed
6. Buyer can rate seller and leave feedback

## Security Features

- Password hashing using `password_hash()` (bcrypt)
- PDO prepared statements for all SQL queries (SQL injection prevention)
- HTML output escaping with `htmlspecialchars()` (XSS prevention)
- Session-based authentication
- Admin-only routes protected with middleware
- File upload validation (type, size limits)

## Development Notes

- All user-facing pages require `require_approved()` middleware
- All admin pages require `require_admin()` middleware
- Flash messages are used for one-time notifications between pages
- No online payment processing - all transactions are offline
- CSRF protection has been removed from the codebase

## License

This project is for educational purposes.