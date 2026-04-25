<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo = get_db();
$book_id = $_GET['id'] ?? null;

if (!$book_id) {
    flash('error', 'Book not found.');
    redirect('home.php');
}

// Get book details
$stmt = $pdo->prepare("SELECT b.*, c.name AS category, bc.label AS condition_label, u.full_name AS seller_name, u.id AS seller_id
FROM books b
JOIN categories c ON b.category_id = c.id
JOIN book_conditions bc ON b.condition_id = bc.id
JOIN users u ON b.seller_id = u.id
WHERE b.id = ? AND b.status != 'deleted'");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    flash('error', 'Book not found.');
    redirect('home.php');
}

// Get book images
$stmt = $pdo->prepare("SELECT * FROM book_images WHERE book_id = ? ORDER BY is_primary DESC, uploaded_at ASC");
$stmt->execute([$book_id]);
$images = $stmt->fetchAll();

// Check if user has already sent an inquiry
$has_inquiry = false;
$inquiry_status = null;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT * FROM book_inquiries WHERE book_id = ? AND buyer_id = ?");
    $stmt->execute([$book_id, $_SESSION['user_id']]);
    $inquiry = $stmt->fetch();
    if ($inquiry) {
        $has_inquiry = true;
        $inquiry_status = $inquiry['status'];
    }
}

// Handle inquiry submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_inquiry') {
    $message = $_POST['message'] ?? '';

    // Business rule: cannot inquire about own book
    if ($book['seller_id'] == $_SESSION['user_id']) {
        flash('error', 'You cannot inquire about your own book.');
    } elseif ($book['status'] !== 'available') {
        flash('error', 'This book is not available for inquiry.');
    } elseif ($has_inquiry) {
        flash('error', 'You have already sent an inquiry for this book.');
    } elseif (empty($message)) {
        flash('error', 'Please enter a message.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO book_inquiries (book_id, buyer_id, message, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$book_id, $_SESSION['user_id'], $message]);
            flash('success', 'Your inquiry has been sent to the seller.');
            redirect('book_detail.php?id=' . $book_id);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                flash('error', 'You have already sent an inquiry for this book.');
            } else {
                flash('error', 'Something went wrong. Please try again.');
            }
        }
    }
}

$page_title = e($book['title']) . ' — Campus Connect';
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

    <main class="max-w-5xl mx-auto px-4 py-8">
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

        <!-- Breadcrumb -->
        <nav class="text-sm text-gray-500 mb-6">
            <a href="home.php" class="hover:text-blue-600">Home</a>
            <span class="mx-2">></span>
            <a href="home.php?category_id=<?= $book['category_id'] ?>" class="hover:text-blue-600"><?= e($book['category']) ?></a>
            <span class="mx-2">></span>
            <span class="text-gray-900"><?= e($book['title']) ?></span>
        </nav>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- LEFT: Image gallery -->
            <div>
                <?php if (!empty($images)): ?>
                    <div class="mb-4">
                        <img id="main-image" src="/campus-connect/<?= e($images[0]['image_path']) ?>" alt="<?= e($book['title']) ?>" 
                            class="w-full h-80 object-cover rounded-xl">
                    </div>
                    <?php if (count($images) > 1): ?>
                        <div class="flex gap-2 overflow-x-auto">
                            <?php foreach ($images as $img): ?>
                                <img src="/campus-connect/<?= e($img['image_path']) ?>" alt="<?= e($book['title']) ?>"
                                    class="w-20 h-20 object-cover rounded-lg cursor-pointer hover:opacity-80 flex-shrink-0"
                                    onclick="document.getElementById('main-image').src = this.src">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="w-full h-80 bg-gray-100 rounded-xl flex items-center justify-center">
                        <span class="text-gray-400">No Image</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Book info -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-medium">
                        <?= e($book['category']) ?>
                    </span>
                    <span class="text-xs px-2 py-1 rounded <?= get_status_badge_class($book['condition_label']) ?>">
                        <?= e($book['condition_label']) ?>
                    </span>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2"><?= e($book['title']) ?></h1>

                <?php if ($book['author']): ?>
                    <p class="text-gray-600 mb-4"><?= e($book['author']) ?></p>
                <?php endif; ?>

                <div class="text-3xl font-bold text-blue-600 mb-4">
                    <?= format_price($book['price'], $book['listing_type']) ?>
                </div>

                <?php if ($book['description']): ?>
                    <div class="mb-6">
                        <p class="text-gray-600"><?= e($book['description']) ?></p>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <p class="text-sm text-gray-500">Listed by <span class="font-medium text-gray-700"><?= e($book['seller_name']) ?></span></p>
                    <p class="text-sm text-gray-500"><?= time_ago($book['created_at']) ?></p>
                </div>

                <!-- Inquiry section -->
                <?php if ($book['status'] === 'available' && $book['seller_id'] != $_SESSION['user_id']): ?>
                    <?php if ($has_inquiry): ?>
                        <div class="bg-gray-50 border border-gray-200 text-gray-700 px-4 py-3 rounded-lg">
                            You have already sent an inquiry.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="send_inquiry">
                            <div class="mb-4">
                                <textarea name="message" rows="3" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="I'm interested in this book. Is it still available?"></textarea>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors">
                                Send Inquiry
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Report this book link -->
        <div class="mt-8">
            <a href="report.php?type=book&id=<?= $book['id'] ?>" class="text-sm text-gray-500 hover:text-gray-700">
                Report this book
            </a>
        </div>
    </main>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
