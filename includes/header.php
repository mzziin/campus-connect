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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Barlow+Condensed:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #E01B1B;
            --yellow: #F5C518;
            --black: #111111;
            --off-white: #F2F0EB;
            --border: 2px solid #111111;
        }
        * { font-family: 'Barlow Condensed', sans-serif; }
        .mono { font-family: 'Space Mono', monospace; }
        body { background-color: var(--off-white); color: var(--black); }

        .nav-link {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #111;
            text-decoration: none;
            padding: 4px 0;
            border-bottom: 2px solid transparent;
            transition: border-color 0.15s;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--red);
            border-bottom-color: var(--red);
        }

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

        .btn-secondary {
            background: var(--yellow);
            color: var(--black);
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
        .btn-secondary:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 4px 0 var(--black);
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

        .input-field {
            width: 100%;
            border: 2px solid var(--black);
            background: #fff;
            padding: 10px 14px;
            font-family: 'Space Mono', monospace;
            font-size: 0.85rem;
            outline: none;
            transition: box-shadow 0.15s;
        }
        .input-field:focus {
            box-shadow: 3px 3px 0 var(--black);
        }

        .badge {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.7rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 3px 8px;
            border: 1.5px solid currentColor;
            display: inline-block;
        }
        .badge-green  { color: #16a34a; border-color: #16a34a; background: #f0fdf4; }
        .badge-red    { color: var(--red); border-color: var(--red); background: #fef2f2; }
        .badge-yellow { color: #92400e; border-color: #f59e0b; background: #fffbeb; }
        .badge-blue   { color: #1d4ed8; border-color: #3b82f6; background: #eff6ff; }
        .badge-gray   { color: #374151; border-color: #6b7280; background: #f9fafb; }

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

        .stat-card {
            background: #fff;
            border: 2px solid var(--black);
            padding: 20px 24px;
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

        /* Flash messages */
        .flash-success { background: #f0fdf4; border: 2px solid #16a34a; color: #15803d; padding: 12px 16px; }
        .flash-error   { background: #fef2f2; border: 2px solid var(--red); color: var(--red); padding: 12px 16px; }
        .flash-warning { background: #fffbeb; border: 2px solid #f59e0b; color: #92400e; padding: 12px 16px; }
        .flash-info    { background: #eff6ff; border: 2px solid #3b82f6; color: #1d4ed8; padding: 12px 16px; }

        select.input-field { appearance: none; cursor: pointer; }
        textarea.input-field { resize: vertical; }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav style="background:#111; border-bottom: 3px solid var(--red);">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between" style="height:60px;">
            <!-- Logo -->
            <a href="home.php" class="flex items-center gap-2" style="text-decoration:none;">
                <div style="background:var(--red); width:32px; height:32px; display:flex; align-items:center; justify-content:center; font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:1.1rem; color:#fff;">C</div>
                <span style="font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:1.1rem; letter-spacing:0.1em; text-transform:uppercase; color:#fff;">Campus Connect</span>
            </a>

            <!-- Desktop Nav -->
            <div class="hidden md:flex items-center gap-6">
                <?php if (is_logged_in() && is_approved()): ?>
                    <a href="home.php" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; font-size:0.95rem; letter-spacing:0.08em; text-transform:uppercase; color:#ccc; text-decoration:none;" class="hover:text-white transition-colors">Buy Books</a>
                    <a href="book_add.php" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; font-size:0.95rem; letter-spacing:0.08em; text-transform:uppercase; color:#ccc; text-decoration:none;" class="hover:text-white transition-colors">Sell Books</a>
                    <a href="inquiries.php" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; font-size:0.95rem; letter-spacing:0.08em; text-transform:uppercase; color:#ccc; text-decoration:none;" class="hover:text-white transition-colors">Inquiries</a>
                    <a href="dashboard.php" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; font-size:0.95rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--red); text-decoration:none;">Dashboard</a>
                    <div style="width:1px; height:20px; background:#444;"></div>
                    <div style="position:relative;" id="user-menu-wrap">
                        <button onclick="document.getElementById('user-dropdown').classList.toggle('hidden')" style="background:none; border:none; cursor:pointer; display:flex; align-items:center; gap:8px;">
                            <div style="width:32px; height:32px; border-radius:50%; background:var(--yellow); border:2px solid #fff; display:flex; align-items:center; justify-content:center; font-family:'Barlow Condensed',sans-serif; font-weight:900; color:#111; font-size:0.9rem;">
                                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                            </div>
                        </button>
                        <div id="user-dropdown" class="hidden" style="position:absolute; right:0; top:40px; background:#fff; border:2px solid #111; box-shadow:4px 4px 0 #111; min-width:160px; z-index:100;">
                            <div style="padding:10px 16px; border-bottom:1px solid #eee; font-family:'Barlow Condensed',sans-serif; font-weight:700; font-size:0.9rem;"><?= e($_SESSION['user_name']) ?></div>
                            <a href="dashboard.php" style="display:block; padding:8px 16px; font-family:'Barlow Condensed',sans-serif; font-size:0.9rem; color:#111; text-decoration:none;" class="hover:bg-gray-100">Dashboard</a>
                            <a href="logout.php" style="display:block; padding:8px 16px; font-family:'Barlow Condensed',sans-serif; font-size:0.9rem; color:var(--red); text-decoration:none;" class="hover:bg-red-50">Logout</a>
                        </div>
                    </div>
                <?php elseif (is_logged_in()): ?>
                    <span style="font-family:'Barlow Condensed',sans-serif; color:#aaa; font-size:0.9rem;"><?= e($_SESSION['user_name']) ?></span>
                    <a href="logout.php" class="btn-ghost" style="color:#ccc; border-color:#555;">Logout</a>
                <?php else: ?>
                    <a href="login.php" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; font-size:0.95rem; letter-spacing:0.08em; text-transform:uppercase; color:#ccc; text-decoration:none;" class="hover:text-white transition-colors">Login</a>
                    <a href="register.php" class="btn-primary" style="font-size:0.85rem; padding:6px 18px;">Register</a>
                <?php endif; ?>
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
            <?php if (is_logged_in() && is_approved()): ?>
                <a href="home.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#ccc; text-decoration:none;">Buy Books</a>
                <a href="book_add.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#ccc; text-decoration:none;">Sell Books</a>
                <a href="inquiries.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#ccc; text-decoration:none;">Inquiries</a>
                <a href="dashboard.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:var(--red); text-decoration:none;">Dashboard</a>
                <a href="logout.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#e87676; text-decoration:none;">Logout</a>
            <?php elseif (is_logged_in()): ?>
                <a href="logout.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#e87676; text-decoration:none;">Logout</a>
            <?php else: ?>
                <a href="login.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#ccc; text-decoration:none;">Login</a>
                <a href="register.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:var(--yellow); text-decoration:none;">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
    <script>
        // Close dropdown on outside click
        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('user-menu-wrap');
            const dropdown = document.getElementById('user-dropdown');
            if (wrap && dropdown && !wrap.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>