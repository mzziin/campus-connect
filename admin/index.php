<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_admin();

$pdo = get_db();

// Get statistics
$pending_users_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'pending'");
$pending_users = $pending_users_stmt->fetchColumn();

$approved_users_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'approved'");
$approved_users = $approved_users_stmt->fetchColumn();

$total_books_stmt = $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'available'");
$total_books = $total_books_stmt->fetchColumn();

$pending_reports_stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'");
$pending_reports = $pending_reports_stmt->fetchColumn();

$page_title = 'Admin Dashboard — Campus Connect';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
            <a href="index.php" class="text-xl font-bold text-blue-600">📚 Campus Connect Admin</a>
            <input type="checkbox" id="mobile-menu" class="hidden peer">
            <label for="mobile-menu" class="md:hidden cursor-pointer text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </label>
            <div class="hidden md:flex items-center gap-6">
                <a href="index.php" class="text-gray-700 hover:text-blue-600 font-medium">Dashboard</a>
                <a href="users.php" class="text-gray-700 hover:text-blue-600 font-medium">Users</a>
                <a href="books.php" class="text-gray-700 hover:text-blue-600 font-medium">Books</a>
                <a href="reports.php" class="text-gray-700 hover:text-blue-600 font-medium">Reports</a>
                <span class="text-gray-300">|</span>
                <span class="text-sm text-gray-600"><?= e($_SESSION['admin_username']) ?></span>
                <a href="logout.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">Logout</a>
            </div>
            <div class="hidden peer-checked:block absolute top-16 left-0 right-0 bg-white border-b border-gray-200 p-4 md:hidden">
                <a href="index.php" class="block py-2 text-gray-700 hover:text-blue-600">Dashboard</a>
                <a href="users.php" class="block py-2 text-gray-700 hover:text-blue-600">Users</a>
                <a href="books.php" class="block py-2 text-gray-700 hover:text-blue-600">Books</a>
                <a href="reports.php" class="block py-2 text-gray-700 hover:text-blue-600">Reports</a>
                <a href="logout.php" class="block py-2 text-gray-700 hover:text-blue-600">Logout</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php $flash = get_flash(); ?>
        <?php if ($flash): ?>
            <div class="mb-6 px-4 py-3 rounded-lg border <?php
                echo match($flash['type']) {
                    'success' => 'bg-green-50 border-green-200 text-green-800',
                    'error'   => 'bg-red-50 border-red-200 text-red-800',
                    'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                    default   => 'bg-blue-50 border-blue-200 text-blue-800',
                };
            ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <h1 class="text-2xl font-bold text-gray-900 mb-6">Admin Dashboard</h1>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <p class="text-sm text-gray-500 font-medium">Pending Users</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $pending_users ?></p>
                <a href="users.php" class="text-blue-600 hover:underline text-sm mt-2 inline-block">View Users</a>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <p class="text-sm text-gray-500 font-medium">Approved Users</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $approved_users ?></p>
                <a href="users.php" class="text-blue-600 hover:underline text-sm mt-2 inline-block">View Users</a>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <p class="text-sm text-gray-500 font-medium">Available Books</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $total_books ?></p>
                <a href="books.php" class="text-blue-600 hover:underline text-sm mt-2 inline-block">Manage Books</a>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <p class="text-sm text-gray-500 font-medium">Pending Reports</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $pending_reports ?></p>
                <a href="reports.php" class="text-blue-600 hover:underline text-sm mt-2 inline-block">View Reports</a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="users.php" class="bg-blue-50 hover:bg-blue-100 text-blue-700 p-4 rounded-lg text-center font-medium transition-colors">
                    Manage Users
                </a>
                <a href="reports.php" class="bg-blue-50 hover:bg-blue-100 text-blue-700 p-4 rounded-lg text-center font-medium transition-colors">
                    Review Reports
                </a>
                <a href="/pages/home.php" class="bg-blue-50 hover:bg-blue-100 text-blue-700 p-4 rounded-lg text-center font-medium transition-colors">
                    View Site
                </a>
            </div>
        </div>
    </main>
</body>
</html>
