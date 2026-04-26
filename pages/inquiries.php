<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo = get_db();
$user_id = $_SESSION['user_id'];

// Handle accept/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $inquiry_id = $_POST['inquiry_id'] ?? '';
    $book_id = $_POST['book_id'] ?? '';

    if ($action === 'accept') {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT seller_id FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();

        if (!$book || $book['seller_id'] != $user_id) {
            flash('error', 'You do not have permission to accept this inquiry.');
            redirect('inquiries.php');
        }

        // Get inquiry details
        $stmt = $pdo->prepare("SELECT * FROM book_inquiries WHERE id = ? AND book_id = ?");
        $stmt->execute([$inquiry_id, $book_id]);
        $inquiry = $stmt->fetch();

        if (!$inquiry) {
            flash('error', 'Inquiry not found.');
            redirect('inquiries.php');
        }

        if ($inquiry['status'] !== 'pending') {
            flash('error', 'This inquiry has already been processed.');
            redirect('inquiries.php');
        }

        // ATOMIC TRANSACTION - Critical Operation
        try {
            $pdo->beginTransaction();

            // 1. Accept this inquiry
            $stmt = $pdo->prepare("UPDATE book_inquiries SET status='accepted' WHERE id=? AND book_id=?");
            $stmt->execute([$inquiry_id, $book_id]);

            // 2. Create the conversation
            $stmt = $pdo->prepare("INSERT INTO conversations (inquiry_id, book_id, seller_id, buyer_id) VALUES (?,?,?,?)");
            $stmt->execute([$inquiry_id, $book_id, $user_id, $inquiry['buyer_id']]);

            // 3. Reject all other pending inquiries for this book
            $stmt = $pdo->prepare("UPDATE book_inquiries SET status='rejected' WHERE book_id=? AND id != ? AND status='pending'");
            $stmt->execute([$book_id, $inquiry_id]);

            $pdo->commit();
            flash('success', 'Inquiry accepted. You can now chat with the buyer.');
            redirect('inquiries.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Something went wrong. Please try again.');
            redirect('inquiries.php');
        }
    } elseif ($action === 'reject') {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT seller_id FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();

        if (!$book || $book['seller_id'] != $user_id) {
            flash('error', 'You do not have permission to reject this inquiry.');
            redirect('inquiries.php');
        }

        // Reject the inquiry
        $stmt = $pdo->prepare("UPDATE book_inquiries SET status='rejected' WHERE id=? AND book_id IN (SELECT id FROM books WHERE seller_id=?)");
        $stmt->execute([$inquiry_id, $user_id]);

        flash('success', 'Inquiry rejected.');
        redirect('inquiries.php');
    }
}

// Get seller's inquiries
$stmt = $pdo->prepare("SELECT bi.*, u.full_name AS buyer_name, bk.title AS book_title, bk.status AS book_status
FROM book_inquiries bi
JOIN users u ON bi.buyer_id = u.id
JOIN books bk ON bi.book_id = bk.id
WHERE bk.seller_id = ?
ORDER BY bi.created_at DESC");
$stmt->execute([$user_id]);
$inquiries = $stmt->fetchAll();

$page_title = 'Inquiries — Campus Connect';
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

        <h1 class="text-2xl font-bold text-gray-900 mb-6">Inquiries Received</h1>

        <!-- Filter tabs -->
        <div class="flex gap-2 mb-6">
            <a href="inquiries.php" class="bg-blue-600 text-white px-4 py-2 rounded-full text-sm font-medium">All</a>
            <a href="inquiries.php?status=pending" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-full text-sm font-medium">Pending</a>
            <a href="inquiries.php?status=accepted" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-full text-sm font-medium">Accepted</a>
            <a href="inquiries.php?status=rejected" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-full text-sm font-medium">Rejected</a>
        </div>

        <?php if (empty($inquiries)): ?>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                <p class="text-gray-500">No inquiries received yet.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($inquiries as $inquiry): ?>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                        <div class="flex gap-4">
                            <!-- Book thumbnail -->
                            <?php
                            $stmt = $pdo->prepare("SELECT image_path FROM book_images WHERE book_id = ? AND is_primary = 1 LIMIT 1");
                            $stmt->execute([$inquiry['book_id']]);
                            $cover = $stmt->fetch();
                            ?>
                            <?php if ($cover): ?>
                                <img src="/campus-connect/<?= e($cover['image_path']) ?>" alt="" class="w-16 h-16 object-cover rounded-lg flex-shrink-0">
                            <?php else: ?>
                                <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <span class="text-gray-400 text-xs">No img</span>
                                </div>
                            <?php endif; ?>

                            <div class="flex-1">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="font-semibold text-gray-900"><?= e($inquiry['book_title']) ?></h3>
                                        <p class="text-sm text-gray-500">Buyer: <?= e($inquiry['buyer_name']) ?></p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= get_status_badge_class($inquiry['status']) ?>">
                                        <?= ucfirst(e($inquiry['status'])) ?>
                                    </span>
                                </div>

                                <?php if ($inquiry['message']): ?>
                                    <p class="text-sm text-gray-600 mb-2"><?= e(truncate($inquiry['message'], 100)) ?></p>
                                <?php endif; ?>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400"><?= time_ago($inquiry['created_at']) ?></span>

                                    <?php if ($inquiry['status'] === 'pending' && $inquiry['book_status'] === 'available'): ?>
                                        <div class="flex gap-2">
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="action" value="accept">
                                                <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                <input type="hidden" name="book_id" value="<?= $inquiry['book_id'] ?>">
                                                <button type="submit" onclick="return confirm('Accept this inquiry?')"
                                                    class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-1.5 px-3 rounded transition-colors">
                                                    Accept
                                                </button>
                                            </form>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                                <input type="hidden" name="book_id" value="<?= $inquiry['book_id'] ?>">
                                                <button type="submit" onclick="return confirm('Reject this inquiry?')"
                                                    class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-1.5 px-3 rounded transition-colors">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif ($inquiry['status'] === 'accepted'): ?>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT id FROM conversations WHERE inquiry_id = ?");
                                        $stmt->execute([$inquiry['id']]);
                                        $conv = $stmt->fetch();
                                        if ($conv): ?>
                                            <a href="chat.php?conversation_id=<?= $conv['id'] ?>" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-3 rounded transition-colors">
                                                Go to Chat
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
