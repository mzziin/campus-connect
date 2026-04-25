<?php
// Header Component
// Campus Connect - Shared Navigation/Header
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Campus Connect') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
            <a href="home.php" class="text-xl font-bold text-blue-600">📚 Campus Connect</a>

            <!-- Mobile menu toggle -->
            <input type="checkbox" id="mobile-menu" class="hidden peer">
            <label for="mobile-menu" class="md:hidden cursor-pointer text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </label>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center gap-6">
                <?php if (is_logged_in() && is_approved()): ?>
                    <a href="home.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Home</a>
                    <a href="book_add.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Add Book</a>
                    <a href="inquiries.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Inquiries</a>
                    <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Dashboard</a>
                    <span class="text-sm text-gray-500"><?= e($_SESSION['user_name']) ?></span>
                    <a href="logout.php" class="text-sm text-red-500 hover:text-red-700">Logout</a>
                <?php elseif (is_logged_in()): ?>
                    <span class="text-sm text-gray-500"><?= e($_SESSION['user_name']) ?></span>
                    <a href="logout.php" class="text-sm text-red-500 hover:text-red-700">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Login</a>
                    <a href="register.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Register</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Navigation -->
            <div class="hidden peer-checked:block absolute top-16 left-0 right-0 bg-white border-b border-gray-200 p-4 md:hidden">
                <div class="flex flex-col gap-4">
                    <?php if (is_logged_in() && is_approved()): ?>
                        <a href="home.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Home</a>
                        <a href="book_add.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Add Book</a>
                        <a href="inquiries.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Inquiries</a>
                        <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Dashboard</a>
                        <span class="text-sm text-gray-500"><?= e($_SESSION['user_name']) ?></span>
                        <a href="logout.php" class="text-sm text-red-500 hover:text-red-700">Logout</a>
                    <?php elseif (is_logged_in()): ?>
                        <span class="text-sm text-gray-500"><?= e($_SESSION['user_name']) ?></span>
                        <a href="logout.php" class="text-sm text-red-500 hover:text-red-700">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Login</a>
                        <a href="register.php" class="text-gray-600 hover:text-blue-600 text-sm font-medium">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
