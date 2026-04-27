<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo = get_db();
$user_id = $_SESSION['user_id'];
$conversation_id = $_GET['conversation_id'] ?? null;

if (!$conversation_id) {
    flash('error', 'Conversation not found.');
    redirect('dashboard.php');
}

// Get conversation details and verify user belongs to it
$stmt = $pdo->prepare("SELECT cv.*, b.title AS book_title, b.id AS book_id,
       seller.full_name AS seller_name,
       buyer.full_name AS buyer_name
FROM conversations cv
JOIN books b ON cv.book_id = b.id
JOIN users seller ON cv.seller_id = seller.id
JOIN users buyer ON cv.buyer_id = buyer.id
WHERE cv.id = ?");
$stmt->execute([$conversation_id]);
$conversation = $stmt->fetch();

if (!$conversation) {
    flash('error', 'Conversation not found.');
    redirect('dashboard.php');
}

// Security check: verify user is seller or buyer
if ($conversation['seller_id'] != $user_id && $conversation['buyer_id'] != $user_id) {
    flash('error', 'You do not have permission to view this conversation.');
    redirect('dashboard.php');
}

// Handle sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $message = $_POST['message'] ?? '';

    if (empty($message)) {
        flash('error', 'Message cannot be empty.');
    } elseif ($conversation['status'] === 'completed') {
        flash('error', 'Cannot send messages in a completed conversation.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)");
        $stmt->execute([$conversation_id, $user_id, $message]);
        flash('success', 'Message sent.');
    }
    redirect('chat.php?conversation_id=' . $conversation_id);
}

// Handle transaction completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete') {
    // Validation
    if ($user_id != $conversation['seller_id']) {
        flash('error', 'Only the seller can complete the transaction.');
    } elseif ($conversation['status'] !== 'active') {
        flash('error', 'This transaction has already been completed.');
    } else {
        // Check if transaction already exists
        $stmt = $pdo->prepare("SELECT id FROM transactions WHERE conversation_id = ?");
        $stmt->execute([$conversation_id]);
        if ($stmt->fetch()) {
            flash('error', 'Transaction already completed.');
        } else {
            // DB Transaction for completion
            try {
                $pdo->beginTransaction();

                // Insert transaction with null rating/feedback, status completed
                $stmt = $pdo->prepare("INSERT INTO transactions (conversation_id, book_id, seller_id, buyer_id, status) VALUES (?, ?, ?, ?, 'completed')");
                $stmt->execute([
                    $conversation_id,
                    $conversation['book_id'],
                    $conversation['seller_id'],
                    $conversation['buyer_id']
                ]);

                // Update book status to sold
                $stmt = $pdo->prepare("UPDATE books SET status='sold' WHERE id=?");
                $stmt->execute([$conversation['book_id']]);

                // Update conversation status to completed
                $stmt = $pdo->prepare("UPDATE conversations SET status='completed' WHERE id=?");
                $stmt->execute([$conversation_id]);

                $pdo->commit();
                flash('success', 'Transaction marked as complete. The buyer can now leave a review.');
            } catch (Exception $e) {
                $pdo->rollBack();
                flash('error', 'Something went wrong. Please try again.');
            }
        }
    }
    redirect('chat.php?conversation_id=' . $conversation_id);
}

// Get messages
$stmt = $pdo->prepare("SELECT m.*, u.full_name AS sender_name
FROM messages m
JOIN users u ON m.sender_id = u.id
WHERE m.conversation_id = ?
ORDER BY m.sent_at ASC");
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll();

$page_title = 'Chat — Campus Connect';
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

    <main class="max-w-4xl mx-auto px-4 py-8">
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

        <!-- Conversation Header -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-900"><?= e($conversation['book_title']) ?></h1>
                    <p class="text-sm text-gray-500">
                        Chatting with: <?= e($conversation['seller_id'] == $user_id ? $conversation['buyer_name'] : $conversation['seller_name']) ?>
                    </p>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full <?= get_status_badge_class($conversation['status']) ?>">
                    <?= ucfirst(e($conversation['status'])) ?>
                </span>
            </div>
        </div>

        <?php if ($conversation['status'] === 'completed'): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                This transaction has been completed.
            </div>
        <?php endif; ?>

        <!-- Messages -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6" style="max-height: 500px; overflow-y: auto;">
            <?php if (empty($messages)): ?>
                <p class="text-gray-500 text-center py-8">No messages yet. Start the conversation!</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($messages as $msg): ?>
                        <div class="flex <?= $msg['sender_id'] == $user_id ? 'justify-end' : 'justify-start' ?>">
                            <div class="<?= $msg['sender_id'] == $user_id ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800' ?> rounded-2xl px-4 py-2 max-w-xs lg:max-w-md">
                                <p class="text-sm"><?= e($msg['body']) ?></p>
                                <p class="text-xs <?= $msg['sender_id'] == $user_id ? 'text-blue-200' : 'text-gray-400' ?> mt-1">
                                    <?= format_datetime($msg['sent_at']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Send Message Form -->
        <?php if ($conversation['status'] === 'active'): ?>
            <form method="POST" action="" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
                <input type="hidden" name="action" value="send_message">
                <div class="flex gap-2">
                    <input type="text" name="message" placeholder="Type your message..." required
                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-6 rounded-lg transition-colors">
                        Send
                    </button>
                </div>
            </form>

            <!-- Complete Transaction Form (Seller Only) -->
            <?php if ($user_id == $conversation['seller_id'] && $conversation['status'] === 'active'): ?>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Mark Transaction as Complete</h3>
                    <p class="text-sm text-gray-500 mb-4">After marking complete, the buyer will be able to leave a review.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="complete">
                        <button type="submit" onclick="return confirm('Are you sure you want to complete this transaction? This cannot be undone.')"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors">
                            Complete Transaction
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
