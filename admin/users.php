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
                <a href="users.php" class="text-blue-600 hover:text-blue-700 font-medium">Users</a>
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

        <h1 class="text-2xl font-bold text-gray-900 mb-6">Manage Users</h1>

        <!-- Filter -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-500 self-center mr-2">Filter by status:</span>
                <a href="users.php" class="<?= $status_filter === '' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">All</a>
                <a href="users.php?status=pending" class="<?= $status_filter === 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Pending</a>
                <a href="users.php?status=approved" class="<?= $status_filter === 'approved' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Approved</a>
                <a href="users.php?status=rejected" class="<?= $status_filter === 'rejected' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Rejected</a>
                <a href="users.php?status=banned" class="<?= $status_filter === 'banned' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Banned</a>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">College ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= e($user['full_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= e($user['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= e($user['department'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= e($user['college_id'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?= match($user['account_status']) {
                                            'pending' => 'bg-yellow-100 text-yellow-700',
                                            'approved' => 'bg-green-100 text-green-700',
                                            'rejected' => 'bg-red-100 text-red-700',
                                            'banned' => 'bg-gray-100 text-gray-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        } ?>">
                                        <?= ucfirst(e($user['account_status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= format_date($user['created_at']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($user['account_status'] === 'pending'): ?>
                                        <div class="flex gap-2">
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" onclick="return confirm('Approve this user?')"
                                                    class="bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">Approve</button>
                                            </form>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" onclick="return confirm('Reject this user?')"
                                                    class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">Reject</button>
                                            </form>
                                        </div>
                                    <?php elseif ($user['account_status'] === 'approved'): ?>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="action" value="ban">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" onclick="return confirm('Ban this user?')"
                                                class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">Ban</button>
                                        </form>
                                    <?php elseif ($user['account_status'] === 'banned'): ?>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="action" value="unban">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" onclick="return confirm('Unban this user?')"
                                                class="bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">Unban</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($users)): ?>
                <div class="p-12 text-center text-gray-500">No users found.</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
