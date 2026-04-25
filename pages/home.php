<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo = get_db();

// Get search and filter parameters
$search = $_GET['q'] ?? '';
$category_filter = $_GET['category_id'] ?? '';
$type_filter = $_GET['type'] ?? '';

// Build query
$sql = "SELECT b.*, c.name AS category, bc.label AS condition_label,
       u.full_name AS seller_name,
       (SELECT image_path FROM book_images WHERE book_id=b.id AND is_primary=1 LIMIT 1) AS cover_image
FROM books b
JOIN categories c ON b.category_id = c.id
JOIN book_conditions bc ON b.condition_id = bc.id
JOIN users u ON b.seller_id = u.id
WHERE b.status = 'available'";

$params = [];

if (!empty($search)) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($category_filter)) {
    $sql .= " AND b.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($type_filter)) {
    $sql .= " AND b.listing_type = ?";
    $params[] = $type_filter;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories for filter
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

$page_title = 'Browse Books — Campus Connect';
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

        <!-- Search bar row -->
        <form method="GET" action="" class="mb-6">
            <div class="flex gap-2">
                <input type="text" name="q" placeholder="Search by title, author, or description..."
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    value="<?= e($search) ?>">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-6 rounded-lg transition-colors">
                    Search
                </button>
            </div>
        </form>

        <!-- Filter pills row -->
        <div class="flex flex-wrap gap-2 mb-6">
            <a href="home.php" class="<?= empty($category_filter) && empty($type_filter) ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">
                All
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="home.php?category_id=<?= $cat['id'] ?>" class="<?= $category_filter == $cat['id'] ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">
                    <?= e($cat['name']) ?>
                </a>
            <?php endforeach; ?>
            <a href="home.php?type=giveaway" class="<?= $type_filter === 'giveaway' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors">
                Free Giveaway
            </a>
        </div>

        <!-- Results count -->
        <p class="text-sm text-gray-500 mb-6">Showing <?= count($books) ?> book<?= count($books) !== 1 ? 's' : '' ?></p>

        <?php if (empty($books)): ?>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                <p class="text-gray-500 text-lg">No books found.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($books as $book): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                        <?php if ($book['cover_image']): ?>
                            <img class="w-full h-48 object-cover" src="/campus-connect/<?= e($book['cover_image']) ?>" alt="Book cover">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gray-100 flex items-center justify-center">
                                <span class="text-gray-400">No Image</span>
                            </div>
                        <?php endif; ?>
                        <div class="p-4">
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-medium">
                                <?= e($book['category']) ?>
                            </span>
                            <h3 class="font-semibold text-gray-900 mt-2 truncate"><?= e($book['title']) ?></h3>
                            <p class="text-sm text-gray-500 truncate"><?= e($book['author'] ?? 'Unknown Author') ?></p>
                            <div class="flex items-center justify-between mt-3">
                                <span class="font-bold text-blue-600"><?= format_price($book['price'], $book['listing_type']) ?></span>
                                <span class="text-xs px-2 py-1 rounded <?= get_status_badge_class($book['condition_label']) ?>">
                                    <?= e($book['condition_label']) ?>
                                </span>
                            </div>
                            <a href="book_detail.php?id=<?= $book['id'] ?>"
                               class="block mt-3 text-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                                View Book
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
