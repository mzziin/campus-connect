<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo = get_db();
$user_id = $_SESSION['user_id'];

// Calculate stats
$active_listings_stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE seller_id = ? AND status = 'available'");
$active_listings_stmt->execute([$user_id]);
$active_listings = $active_listings_stmt->fetchColumn();

$books_sold_stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE seller_id = ? AND status = 'sold'");
$books_sold_stmt->execute([$user_id]);
$books_sold = $books_sold_stmt->fetchColumn();

$active_conversations_stmt = $pdo->prepare("SELECT COUNT(*) FROM conversations WHERE (seller_id = ? OR buyer_id = ?) AND status = 'active'");
$active_conversations_stmt->execute([$user_id, $user_id]);
$active_conversations = $active_conversations_stmt->fetchColumn();

$avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) FROM transactions WHERE seller_id = ?");
$avg_rating_stmt->execute([$user_id]);
$avg_rating = $avg_rating_stmt->fetchColumn();
$avg_rating = $avg_rating ? round($avg_rating, 1) : 'N/A';

// Get user's books
$stmt = $pdo->prepare("SELECT b.*, c.name AS category, bc.label AS condition_label,
       (SELECT image_path FROM book_images WHERE book_id=b.id AND is_primary=1 LIMIT 1) AS cover_image
FROM books b
JOIN categories c ON b.category_id = c.id
JOIN book_conditions bc ON b.condition_id = bc.id
WHERE b.seller_id = ?
ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$my_books = $stmt->fetchAll();

// Get user's conversations and pending inquiries
$stmt = $pdo->prepare("
    SELECT cv.id, cv.book_id, cv.seller_id, cv.buyer_id, cv.status, cv.created_at,
           b.title AS book_title,
           seller.full_name AS seller_name,
           buyer.full_name AS buyer_name,
           'conversation' AS type
    FROM conversations cv
    JOIN books b ON cv.book_id = b.id
    JOIN users seller ON cv.seller_id = seller.id
    JOIN users buyer ON cv.buyer_id = buyer.id
    WHERE (cv.seller_id = ? OR cv.buyer_id = ?)

    UNION ALL

    SELECT bi.id, bi.book_id, b.seller_id AS seller_id, bi.buyer_id, bi.status, bi.created_at,
           b.title AS book_title,
           seller.full_name AS seller_name,
           buyer.full_name AS buyer_name,
           'inquiry' AS type
    FROM book_inquiries bi
    JOIN books b ON bi.book_id = b.id
    JOIN users seller ON b.seller_id = seller.id
    JOIN users buyer ON bi.buyer_id = buyer.id
    WHERE bi.buyer_id = ? AND bi.status = 'pending'

    ORDER BY created_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

// Get user's transactions
$stmt = $pdo->prepare("SELECT t.*, b.title AS book_title,
       seller.full_name AS seller_name,
       buyer.full_name AS buyer_name
FROM transactions t
JOIN books b ON t.book_id = b.id
JOIN users seller ON t.seller_id = seller.id
JOIN users buyer ON t.buyer_id = buyer.id
WHERE (t.seller_id = ? OR t.buyer_id = ?)
ORDER BY t.completed_at DESC");
$stmt->execute([$user_id, $user_id]);
$transactions = $stmt->fetchAll();

$page_title = 'Dashboard — Campus Connect';
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

    <main class="max-w-6xl mx-auto px-4 py-8">
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

        <h1 class="text-2xl font-bold text-gray-900 mb-6">Welcome, <?= e($_SESSION['user_name']) ?>!</h1>

        <!-- Stats cards row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <p class="text-sm text-gray-500 font-medium">My Active Listings</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $active_listings ?></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <p class="text-sm text-gray-500 font-medium">Books Sold</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $books_sold ?></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <p class="text-sm text-gray-500 font-medium">Active Conversations</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $active_conversations ?></p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <p class="text-sm text-gray-500 font-medium">Average Rating</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $avg_rating ?></p>
            </div>
        </div>

        <!-- Section: My Book Listings -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-8">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">My Book Listings</h2>
            </div>
            <?php if (empty($my_books)): ?>
                <div class="p-8 text-center text-gray-500">
                    <p>You haven't listed any books yet.</p>
                    <a href="book_add.php" class="text-blue-600 hover:underline mt-2 inline-block">List your first book</a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cover</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($my_books as $book): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($book['cover_image']): ?>
                                            <img src="/campus-connect/<?= e($book['cover_image']) ?>" alt="" class="w-12 h-12 object-cover rounded">
                                        <?php else: ?>
                                            <div class="w-12 h-12 bg-gray-100 rounded flex items-center justify-center">
                                                <span class="text-gray-400 text-xs">No img</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= e($book['title']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= e($book['category']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= format_price($book['price'], $book['listing_type']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= get_status_badge_class($book['status']) ?>">
                                            <?= ucfirst(e($book['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="book_detail.php?id=<?= $book['id'] ?>" class="text-blue-600 hover:underline">View</a>
                                        <?php if ($book['status'] === 'available'): ?>
                                            <span class="text-gray-300 mx-2">|</span>
                                            <a href="inquiries.php" class="text-blue-600 hover:underline">Inquiries</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section: My Conversations -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">My Conversations</h2>
            </div>
            <?php if (empty($conversations)): ?>
                <div class="p-8 text-center text-gray-500">
                    <p>No active conversations.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Other Party</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($conversations as $conv): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= e($conv['book_title']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?= $conv['type'] === 'inquiry' ? e($conv['seller_name']) : ($conv['seller_id'] == $user_id ? e($conv['buyer_name']) : e($conv['seller_name'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($conv['type'] === 'inquiry'): ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?= get_status_badge_class($conv['status']) ?>">
                                                <?= ucfirst(e($conv['status'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?= get_status_badge_class($conv['status']) ?>">
                                                <?= ucfirst(e($conv['status'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($conv['type'] === 'inquiry'): ?>
                                            <?php if ($conv['status'] === 'pending'): ?>
                                                <span class="text-gray-400 text-sm">Pending</span>
                                            <?php elseif ($conv['status'] === 'accepted'): ?>
                                                <a href="chat.php?conversation_id=<?= $conv['id'] ?>"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-3 rounded transition-colors">
                                                    Open Chat
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-sm">Rejected</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="chat.php?conversation_id=<?= $conv['id'] ?>"
                                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-3 rounded transition-colors">
                                                Open Chat
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section: My Transactions -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm mt-6">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">My Transactions</h2>
            </div>
            <?php if (empty($transactions)): ?>
                <div class="p-8 text-center text-gray-500">
                    <p>No transactions yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Other Party</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $txn['seller_id'] == $user_id ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
                                            <?= $txn['seller_id'] == $user_id ? 'Sold' : 'Bought' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= e($txn['book_title']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?= $txn['seller_id'] == $user_id ? e($txn['buyer_name']) : e($txn['seller_name']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= get_transaction_status_badge_class($txn['status']) ?>">
                                            <?= $txn['status'] === 'in_progress' ? 'In Progress' : 'Completed' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= format_datetime($txn['completed_at']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($txn['rating']): ?>
                                            <span class="text-yellow-500"><?= str_repeat('★', $txn['rating']) ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-400">Not rated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($txn['buyer_id'] == $user_id && $txn['rating'] === null): ?>
                                            <a href="transaction_review.php?id=<?= $txn['id'] ?>"
                                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-3 rounded transition-colors">
                                                Review
                                            </a>
                                        <?php elseif ($txn['feedback']): ?>
                                            <span class="text-gray-500 text-xs"><?= e(substr($txn['feedback'], 0, 30)) ?>...</span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
