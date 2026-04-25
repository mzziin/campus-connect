<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_admin();

$pdo = get_db();

// Handle book actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $book_id = $_POST['book_id'] ?? '';

    if ($action === 'delete') {
        $stmt = $pdo->prepare("UPDATE books SET status='deleted' WHERE id=?");
        $stmt->execute([$book_id]);
        flash('success', 'Book deleted successfully.');
    } elseif ($action === 'restore') {
        $stmt = $pdo->prepare("UPDATE books SET status='available' WHERE id=?");
        $stmt->execute([$book_id]);
        flash('success', 'Book restored successfully.');
    }

    redirect('books.php');
}

// Get filter
$status_filter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT b.*, c.name AS category, bc.label AS condition_label,
       u.full_name AS seller_name, u.email AS seller_email
FROM books b
JOIN categories c ON b.category_id = c.id
JOIN book_conditions bc ON b.condition_id = bc.id
JOIN users u ON b.seller_id = u.id";

$params = [];

if ($status_filter) {
    $sql .= " WHERE b.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$page_title = 'Manage Books — Campus Connect';
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
                <a href="books.php" class="text-blue-600 hover:text-blue-700 font-medium">Books</a>
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

        <h1 class="text-2xl font-bold text-gray-900 mb-6">Manage Books</h1>

        <!-- Filter -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-500 self-center mr-2">Filter by status:</span>
                <a href="books.php" class="<?= $status_filter === '' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">All</a>
                <a href="books.php?status=available" class="<?= $status_filter === 'available' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Available</a>
                <a href="books.php?status=reserved" class="<?= $status_filter === 'reserved' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Reserved</a>
                <a href="books.php?status=sold" class="<?= $status_filter === 'sold' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Sold</a>
                <a href="books.php?status=deleted" class="<?= $status_filter === 'deleted' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">Deleted</a>
            </div>
        </div>

        <!-- Books Table -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Listed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= e($book['title']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= e($book['author'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= e($book['seller_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= e($book['seller_email']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= e($book['category']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= e($book['condition_label']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= format_price($book['price'], $book['listing_type']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?= match($book['status']) {
                                            'available' => 'bg-green-100 text-green-700',
                                            'reserved' => 'bg-yellow-100 text-yellow-700',
                                            'sold' => 'bg-blue-100 text-blue-700',
                                            'deleted' => 'bg-red-100 text-red-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        } ?>">
                                        <?= ucfirst(e($book['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= format_date($book['created_at']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($book['status'] === 'deleted'): ?>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="action" value="restore">
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                            <button type="submit" onclick="return confirm('Restore this book?')"
                                                class="bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">Restore</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                            <button type="submit" onclick="return confirm('Delete this book?')"
                                                class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($books)): ?>
                <div class="p-12 text-center text-gray-500">No books found.</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
