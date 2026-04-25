<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_admin();

$pdo = get_db();

// Handle report actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $report_id = $_POST['report_id'] ?? '';

    if ($action === 'review') {
        $stmt = $pdo->prepare("UPDATE reports SET status='reviewed' WHERE id=?");
        $stmt->execute([$report_id]);
        flash('success', 'Report marked as reviewed.');
    } elseif ($action === 'dismiss') {
        $stmt = $pdo->prepare("UPDATE reports SET status='dismissed' WHERE id=?");
        $stmt->execute([$report_id]);
        flash('success', 'Report dismissed.');
    }

    redirect('reports.php');
}

// Get filter
$status_filter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT r.*, reporter.full_name AS reporter_name,
       (SELECT title FROM books WHERE id = r.reported_book_id) AS book_title,
       (SELECT full_name FROM users WHERE id = r.reported_user_id) AS reported_user_name
FROM reports r
JOIN users reporter ON r.reporter_id = reporter.id";

$params = [];

if ($status_filter) {
    $sql .= " WHERE r.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$page_title = 'Review Reports — Campus Connect';
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
                <a href="reports.php" class="text-blue-600 hover:text-blue-700 font-medium">Reports</a>
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

        <h1 class="text-2xl font-bold text-gray-900 mb-6">Review Reports</h1>

        <!-- Filter -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-500 self-center mr-2">Filter by status:</span>
                <a href="reports.php" class="<?= $status_filter === '' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">All</a>
                <a href="reports.php?status=pending" class="<?= $status_filter === 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Pending</a>
                <a href="reports.php?status=reviewed" class="<?= $status_filter === 'reviewed' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Reviewed</a>
                <a href="reports.php?status=dismissed" class="<?= $status_filter === 'dismissed' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Dismissed</a>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reported Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reporter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?= $report['report_type'] === 'book' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' ?>">
                                        <?= ucfirst(e($report['report_type'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= $report['report_type'] === 'book' ? e($report['book_title'] ?? 'N/A') : e($report['reported_user_name'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= e($report['reporter_name']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= e($report['reason']) ?></div>
                                    <?php if ($report['details']): ?>
                                        <div class="text-xs text-gray-500 mt-1"><?= e(truncate($report['details'], 50)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?= match($report['status']) {
                                            'pending' => 'bg-yellow-100 text-yellow-700',
                                            'reviewed' => 'bg-blue-100 text-blue-700',
                                            'dismissed' => 'bg-gray-100 text-gray-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        } ?>">
                                        <?= ucfirst(e($report['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= format_date($report['created_at']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($report['status'] === 'pending'): ?>
                                        <div class="flex gap-2">
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="action" value="review">
                                                <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                <button type="submit" onclick="return confirm('Mark this report as reviewed?')"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">Review</button>
                                            </form>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="action" value="dismiss">
                                                <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                <button type="submit" onclick="return confirm('Dismiss this report?')"
                                                    class="bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">Dismiss</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($reports)): ?>
                <div class="p-12 text-center text-gray-500">No reports found.</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
