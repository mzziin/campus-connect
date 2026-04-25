<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo = get_db();
$user_id = $_SESSION['user_id'];

$report_type = $_GET['type'] ?? '';
$report_id = $_GET['id'] ?? '';

if (!in_array($report_type, ['book', 'user']) || !$report_id) {
    flash('error', 'Invalid report request.');
    redirect('home.php');
}

// Get the item being reported
if ($report_type === 'book') {
    $stmt = $pdo->prepare("SELECT b.*, u.full_name AS seller_name FROM books b JOIN users u ON b.seller_id = u.id WHERE b.id = ?");
    $stmt->execute([$report_id]);
    $item = $stmt->fetch();
    if (!$item) {
        flash('error', 'Book not found.');
        redirect('home.php');
    }
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$report_id]);
    $item = $stmt->fetch();
    if (!$item) {
        flash('error', 'User not found.');
        redirect('home.php');
    }
}

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = $_POST['reason'] ?? '';
    $details = $_POST['details'] ?? '';

    if (empty($reason)) {
        flash('error', 'Reason is required.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO reports (reporter_id, report_type, reported_book_id, reported_user_id, reason, details, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $user_id,
                $report_type,
                $report_type === 'book' ? $report_id : null,
                $report_type === 'user' ? $report_id : null,
                $reason,
                $details ?: null
            ]);
            flash('success', 'Report submitted. Admin will review it.');
            redirect('home.php');
        } catch (PDOException $e) {
            flash('error', 'Something went wrong. Please try again.');
        }
    }
}

$page_title = 'Report — Campus Connect';
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
    <?php require_once '../includes/header.php'; ?>

    <main class="max-w-2xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Report <?= e(ucfirst($report_type)) ?></h1>

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

        <!-- Item Being Reported -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
            <h2 class="text-sm font-medium text-gray-500 mb-2">Reporting</h2>
            <?php if ($report_type === 'book'): ?>
                <h3 class="font-semibold text-gray-900"><?= e($item['title']) ?></h3>
                <p class="text-sm text-gray-500">Seller: <?= e($item['seller_name']) ?></p>
            <?php else: ?>
                <h3 class="font-semibold text-gray-900"><?= e($item['full_name']) ?></h3>
                <p class="text-sm text-gray-500">Email: <?= e($item['email']) ?></p>
            <?php endif; ?>
        </div>

        <form method="POST" action="">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <select name="reason" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <option value="">Select a reason</option>
                        <option value="Inappropriate content">Inappropriate content</option>
                        <option value="Fake listing">Fake listing</option>
                        <option value="Spam">Spam</option>
                        <option value="Harassment">Harassment</option>
                        <option value="Fraud">Fraud</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Details (optional)</label>
                    <textarea name="details" rows="4"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Please provide more details about your report..."></textarea>
                </div>
            </div>

            <button type="submit" onclick="return confirm('Are you sure you want to submit this report?')"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors">
                Submit Report
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">
            <a href="javascript:history.back()" class="text-blue-600 hover:underline">Cancel</a>
        </p>
    </main>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
