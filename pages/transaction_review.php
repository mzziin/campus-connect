<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo = get_db();
$user_id = $_SESSION['user_id'];
$transaction_id = $_GET['id'] ?? null;

if (!$transaction_id) {
    flash('error', 'Transaction not found.');
    redirect('dashboard.php');
}

// Get transaction details and verify user is the buyer
$stmt = $pdo->prepare("SELECT t.*, b.title AS book_title,
       seller.full_name AS seller_name,
       buyer.full_name AS buyer_name
FROM transactions t
JOIN books b ON t.book_id = b.id
JOIN users seller ON t.seller_id = seller.id
JOIN users buyer ON t.buyer_id = buyer.id
WHERE t.id = ?");
$stmt->execute([$transaction_id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    flash('error', 'Transaction not found.');
    redirect('dashboard.php');
}

// Security check: verify user is the buyer
if ($transaction['buyer_id'] != $user_id) {
    flash('error', 'You do not have permission to review this transaction.');
    redirect('dashboard.php');
}

// Check if already rated
if ($transaction['rating'] !== null) {
    flash('error', 'You have already reviewed this transaction.');
    redirect('dashboard.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? '';
    $feedback = $_POST['feedback'] ?? '';

    // Validation
    if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        flash('error', 'Rating must be between 1 and 5.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE transactions SET rating = ?, feedback = ? WHERE id = ?");
            $stmt->execute([
                $rating,
                $feedback ?: null,
                $transaction_id
            ]);
            flash('success', 'Thank you for your review!');
            redirect('dashboard.php');
        } catch (Exception $e) {
            flash('error', 'Something went wrong. Please try again.');
        }
    }
}

$page_title = 'Review Transaction — Campus Connect';
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

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Review Transaction</h1>

            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600"><strong>Book:</strong> <?= e($transaction['book_title']) ?></p>
                <p class="text-sm text-gray-600"><strong>Seller:</strong> <?= e($transaction['seller_name']) ?></p>
                <p class="text-sm text-gray-600"><strong>Completed:</strong> <?= format_datetime($transaction['completed_at']) ?></p>
            </div>

            <form method="POST" action="">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Rating</label>
                    <div class="flex gap-2" id="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="cursor-pointer group">
                                <input type="radio" name="rating" value="<?= $i ?>" class="hidden peer" required>
                                <span class="star text-3xl text-gray-300 peer-checked:text-yellow-400 peer-checked:scale-110 transition-transform group-hover:text-yellow-300" data-value="<?= $i ?>">★</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Feedback (optional)</label>
                    <textarea name="feedback" rows="4"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Share your experience with this transaction..."></textarea>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors">
                    Submit Review
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-gray-600">
                <a href="dashboard.php" class="text-blue-600 hover:underline">Back to Dashboard</a>
            </p>
        </div>
    </main>

    <?php require_once '../includes/footer.php'; ?>

    <script>
        const starRating = document.getElementById('star-rating');
        const stars = starRating.querySelectorAll('.star');
        const inputs = starRating.querySelectorAll('input');

        stars.forEach((star, index) => {
            const value = parseInt(star.dataset.value);

            // Hover effect
            star.addEventListener('mouseenter', () => {
                stars.forEach((s, i) => {
                    if (i < value) {
                        s.classList.remove('text-gray-300');
                        s.classList.add('text-yellow-300');
                    } else {
                        s.classList.remove('text-yellow-300');
                        s.classList.add('text-gray-300');
                    }
                });
            });
        });

        starRating.addEventListener('mouseleave', () => {
            const selectedValue = parseInt(starRating.querySelector('input:checked')?.value || 0);
            stars.forEach((s, i) => {
                const starValue = parseInt(s.dataset.value);
                if (starValue <= selectedValue) {
                    s.classList.remove('text-gray-300');
                    s.classList.add('text-yellow-400');
                } else {
                    s.classList.remove('text-yellow-400', 'text-yellow-300');
                    s.classList.add('text-gray-300');
                }
            });
        });

        // On selection
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                const selectedValue = parseInt(input.value);
                stars.forEach((s, i) => {
                    const starValue = parseInt(s.dataset.value);
                    if (starValue <= selectedValue) {
                        s.classList.remove('text-gray-300');
                        s.classList.add('text-yellow-400');
                    } else {
                        s.classList.remove('text-yellow-400', 'text-yellow-300');
                        s.classList.add('text-gray-300');
                    }
                });
            });
        });
    </script>
</body>
</html>
