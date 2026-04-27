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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Barlow+Condensed:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --red: #E01B1B; --yellow: #F5C518; --black: #111111; --off-white: #F2F0EB; }
        * { font-family: 'Barlow Condensed', sans-serif; }
        .mono { font-family: 'Space Mono', monospace; }
        body { background-color: var(--off-white); color: var(--black); }

        .btn-primary {
            background: var(--red);
            color: #fff;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 8px 20px;
            border: 2px solid var(--black);
            box-shadow: 3px 3px 0 var(--black);
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.1s;
            display: inline-block;
            text-decoration: none;
        }
        .btn-primary:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 4px 0 var(--black);
        }
        .btn-primary:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--black);
        }

        .btn-ghost {
            background: transparent;
            color: var(--black);
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 6px 16px;
            border: 2px solid var(--black);
            cursor: pointer;
            transition: background 0.15s;
            display: inline-block;
            text-decoration: none;
        }
        .btn-ghost:hover { background: #e5e3de; }

        .card {
            background: #fff;
            border: 2px solid var(--black);
            box-shadow: 4px 4px 0 var(--black);
        }

        .stat-card {
            background: #fff;
            border: 2px solid var(--black);
            padding: 22px 24px;
            position: relative;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 100%;
            height: 100%;
            background: var(--black);
            z-index: -1;
        }
        .stat-card.highlight { background: var(--yellow); }

        .page-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 900;
            font-size: 2.5rem;
            text-transform: uppercase;
            letter-spacing: -0.01em;
            line-height: 1;
        }

        .section-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--black);
            padding-bottom: 6px;
            margin-bottom: 16px;
        }

        .flash-success { background: #f0fdf4; border: 2px solid #16a34a; color: #15803d; padding: 12px 16px; }
        .flash-error   { background: #fef2f2; border: 2px solid var(--red); color: var(--red); padding: 12px 16px; }
        .flash-warning { background: #fffbeb; border: 2px solid #f59e0b; color: #92400e; padding: 12px 16px; }
        .flash-info    { background: #eff6ff; border: 2px solid #3b82f6; color: #1d4ed8; padding: 12px 16px; }

        .nav-link {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #ccc;
            text-decoration: none;
            padding: 4px 0;
            border-bottom: 2px solid transparent;
            transition: border-color 0.15s;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--red);
            border-bottom-color: var(--red);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav style="background:#111; border-bottom: 3px solid var(--red);">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between" style="height:60px;">
            <!-- Logo -->
            <a href="index.php" class="flex items-center gap-2" style="text-decoration:none;">
                <div style="background:var(--yellow); width:32px; height:32px; display:flex; align-items:center; justify-content:center; font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:1.1rem; color:#111; border:2px solid #fff;">A</div>
                <span style="font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:1.1rem; letter-spacing:0.1em; text-transform:uppercase; color:#fff;">Campus Connect Admin</span>
            </a>

            <!-- Desktop Nav -->
            <div class="hidden md:flex items-center gap-6">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                $is_active = function($page) use ($current_page) {
                    return $current_page === $page ? 'color:var(--red);' : 'color:#ccc;';
                };
                ?>
                <a href="index.php" style="<?= $is_active('index.php') ?>" class="nav-link">Dashboard</a>
                <a href="users.php" style="color:#ccc;" class="nav-link hover:text-white transition-colors">Users</a>
                <a href="books.php" style="color:#ccc;" class="nav-link hover:text-white transition-colors">Books</a>
                <a href="reports.php" style="color:#ccc;" class="nav-link hover:text-white transition-colors">Reports</a>
                <div style="width:1px; height:20px; background:#444;"></div>
                <span class="mono" style="font-size:0.85rem; color:#aaa;"><?= e($_SESSION['admin_username']) ?></span>
                <a href="logout.php" class="btn-ghost" style="color:#ccc; border-color:#555;">Logout</a>
            </div>

            <!-- Mobile Menu Toggle -->
            <input type="checkbox" id="mobile-menu" class="hidden peer">
            <label for="mobile-menu" class="md:hidden cursor-pointer" style="color:#fff;">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </label>
        </div>

        <!-- Mobile Nav -->
        <div class="hidden peer-checked:block md:hidden" style="border-top:2px solid #333; background:#1a1a1a; padding:16px;">
            <a href="index.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:var(--red); text-decoration:none;">Dashboard</a>
            <a href="users.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#ccc; text-decoration:none;">Users</a>
            <a href="books.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#ccc; text-decoration:none;">Books</a>
            <a href="reports.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#ccc; text-decoration:none;">Reports</a>
            <a href="logout.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#e87676; text-decoration:none;">Logout</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php $flash = get_flash(); ?>
        <?php if ($flash): ?>
            <div class="<?= match($flash['type']) { 'success' => 'flash-success', 'error' => 'flash-error', 'warning' => 'flash-warning', default => 'flash-info' } ?> mb-6">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="mb-8">
            <h1 class="page-title mb-2">Admin Dashboard</h1>
            <div style="width: 40px; height: 3px; background: var(--red);"></div>
            <p class="mono mt-3" style="font-size: 0.8rem; color: #666;">Overview of campus marketplace statistics.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card">
                <p class="mono" style="font-size: 0.75rem; color: #666; text-transform: uppercase; letter-spacing: 0.05em;">Pending Users</p>
                <p style="font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:2.5rem; line-height:1; margin-top:8px;"><?= $pending_users ?></p>
                <a href="users.php?status=pending" style="color:var(--red); text-decoration:none; font-weight:700; font-size:0.8rem; margin-top:12px; display:inline-block;">View Users →</a>
            </div>

            <div class="stat-card highlight">
                <p class="mono" style="font-size: 0.75rem; color: #666; text-transform: uppercase; letter-spacing: 0.05em;">Approved Users</p>
                <p style="font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:2.5rem; line-height:1; margin-top:8px;"><?= $approved_users ?></p>
                <a href="users.php?status=approved" style="color:var(--red); text-decoration:none; font-weight:700; font-size:0.8rem; margin-top:12px; display:inline-block;">View Users →</a>
            </div>

            <div class="stat-card">
                <p class="mono" style="font-size: 0.75rem; color: #666; text-transform: uppercase; letter-spacing: 0.05em;">Available Books</p>
                <p style="font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:2.5rem; line-height:1; margin-top:8px;"><?= $total_books ?></p>
                <a href="books.php?status=available" style="color:var(--red); text-decoration:none; font-weight:700; font-size:0.8rem; margin-top:12px; display:inline-block;">Manage Books →</a>
            </div>

            <div class="stat-card">
                <p class="mono" style="font-size: 0.75rem; color: #666; text-transform: uppercase; letter-spacing: 0.05em;">Pending Reports</p>
                <p style="font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:2.5rem; line-height:1; margin-top:8px;"><?= $pending_reports ?></p>
                <a href="reports.php?status=pending" style="color:var(--red); text-decoration:none; font-weight:700; font-size:0.8rem; margin-top:12px; display:inline-block;">View Reports →</a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card p-6">
            <h2 class="section-title">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="users.php" class="btn-primary text-center">Manage Users</a>
                <a href="reports.php" class="btn-primary text-center">Review Reports</a>
                <a href="../pages/home.php" class="btn-ghost text-center">View Site</a>
            </div>
        </div>
    </main>
</body>
</html>
