<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_admin();

$pdo = get_db();

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET account_status='approved' WHERE id=?");
        $stmt->execute([$user_id]);
        flash('success', 'User approved successfully.');
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE users SET account_status='rejected' WHERE id=?");
        $stmt->execute([$user_id]);
        flash('success', 'User rejected.');
    } elseif ($action === 'ban') {
        $stmt = $pdo->prepare("UPDATE users SET account_status='banned' WHERE id=?");
        $stmt->execute([$user_id]);
        flash('success', 'User banned.');
    } elseif ($action === 'unban') {
        $stmt = $pdo->prepare("UPDATE users SET account_status='approved' WHERE id=?");
        $stmt->execute([$user_id]);
        flash('success', 'User unbanned.');
    }

    redirect('users.php');
}

// Get filter
$status_filter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT * FROM users";
$params = [];

if ($status_filter) {
    $sql .= " WHERE account_status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'Manage Users — Campus Connect';
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
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 6px 16px;
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

        .btn-secondary {
            background: var(--yellow);
            color: var(--black);
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 6px 16px;
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

        .card {
            background: #fff;
            border: 2px solid var(--black);
            box-shadow: 4px 4px 0 var(--black);
        }

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

        .filter-btn {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 6px 14px;
            border: 2px solid var(--black);
            cursor: pointer;
            transition: all 0.15s;
            display: inline-block;
            text-decoration: none;
        }
        .filter-btn:hover { background: #e5e3de; }
        .filter-btn.active {
            background: var(--red);
            color: #fff;
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
                <a href="index.php" style="color:#ccc;" class="nav-link hover:text-white transition-colors">Dashboard</a>
                <a href="users.php" style="<?= $is_active('users.php') ?>" class="nav-link">Users</a>
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
            <a href="index.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:#ccc; text-decoration:none;">Dashboard</a>
            <a href="users.php" class="block py-2" style="font-family:'Barlow Condensed',sans-serif; font-weight:700; text-transform:uppercase; color:var(--red); text-decoration:none;">Users</a>
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
            <h1 class="page-title mb-2">Manage Users</h1>
            <div style="width: 40px; height: 3px; background: var(--red);"></div>
            <p class="mono mt-3" style="font-size: 0.8rem; color: #666;">Approve, reject, ban, or manage user accounts.</p>
        </div>

        <!-- Filter -->
        <div class="card p-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <span class="mono" style="font-size: 0.75rem; color: #666; text-transform: uppercase; letter-spacing: 0.05em;">Filter by status:</span>
                <a href="users.php" class="filter-btn <?= $status_filter === '' ? 'active' : '' ?>">All</a>
                <a href="users.php?status=pending" class="filter-btn <?= $status_filter === 'pending' ? 'active' : '' ?>">Pending</a>
                <a href="users.php?status=approved" class="filter-btn <?= $status_filter === 'approved' ? 'active' : '' ?>">Approved</a>
                <a href="users.php?status=rejected" class="filter-btn <?= $status_filter === 'rejected' ? 'active' : '' ?>">Rejected</a>
                <a href="users.php?status=banned" class="filter-btn <?= $status_filter === 'banned' ? 'active' : '' ?>">Banned</a>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full" style="border-collapse: collapse;">
                    <thead style="background: #f9fafb; border-bottom: 2px solid var(--black);">
                        <tr>
                            <th class="px-6 py-3 text-left mono" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #666;">Name</th>
                            <th class="px-6 py-3 text-left mono" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #666;">Email</th>
                            <th class="px-6 py-3 text-left mono" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #666;">Department</th>
                            <th class="px-6 py-3 text-left mono" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #666;">College ID</th>
                            <th class="px-6 py-3 text-left mono" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #666;">Status</th>
                            <th class="px-6 py-3 text-left mono" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #666;">Joined</th>
                            <th class="px-6 py-3 text-left mono" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #666;">Actions</th>
                        </tr>
                    </thead>
                    <tbody style="border-bottom: 1px solid #e5e7eb;">
                        <?php foreach ($users as $user): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td class="px-6 py-4">
                                    <div style="font-weight: 600; font-size: 0.9rem;"><?= e($user['full_name']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="mono" style="font-size: 0.8rem; color: #666;"><?= e($user['email']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div style="font-size: 0.9rem; color: #666;"><?= e($user['department'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="mono" style="font-size: 0.8rem; color: #666;"><?= e($user['college_id'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="badge 
                                        <?= match($user['account_status']) {
                                            'pending' => 'badge-yellow',
                                            'approved' => 'badge-green',
                                            'rejected' => 'badge-red',
                                            'banned' => 'badge-gray',
                                            default => 'badge-gray',
                                        } ?>">
                                        <?= ucfirst(e($user['account_status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 mono" style="font-size: 0.8rem; color: #666;">
                                    <?= format_date($user['created_at']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($user['account_status'] === 'pending'): ?>
                                        <div class="flex gap-2">
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" onclick="return confirm('Approve this user?')" class="btn-primary">Approve</button>
                                            </form>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" onclick="return confirm('Reject this user?')" class="btn-primary" style="background: var(--red);">Reject</button>
                                            </form>
                                        </div>
                                    <?php elseif ($user['account_status'] === 'approved'): ?>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="action" value="ban">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" onclick="return confirm('Ban this user?')" class="btn-primary">Ban</button>
                                        </form>
                                    <?php elseif ($user['account_status'] === 'banned'): ?>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="action" value="unban">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" onclick="return confirm('Unban this user?')" class="btn-secondary">Unban</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($users)): ?>
                <div class="p-12 text-center mono" style="font-size: 0.8rem; color: #666;">No users found.</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
