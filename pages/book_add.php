<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo = get_db();

// Get categories and conditions
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

$conditions_stmt = $pdo->query("SELECT * FROM book_conditions ORDER BY id");
$conditions = $conditions_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $condition_id = $_POST['condition_id'] ?? '';
    $description = $_POST['description'] ?? '';
    $listing_type = $_POST['listing_type'] ?? 'sell';
    $price = $_POST['price'] ?? '';

    // Validation
    if (empty($title)) {
        flash('error', 'Title is required.');
    } elseif (empty($category_id)) {
        flash('error', 'Category is required.');
    } elseif (empty($condition_id)) {
        flash('error', 'Condition is required.');
    } elseif ($listing_type === 'sell' && empty($price)) {
        flash('error', 'Price is required for sell listings.');
    } elseif (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        flash('error', 'At least one image is required.');
    } elseif (count($_FILES['images']['name']) > 5) {
        flash('error', 'Maximum 5 images allowed.');
    } else {
        try {
            $pdo->beginTransaction();

            // Insert book
            $stmt = $pdo->prepare("INSERT INTO books (seller_id, category_id, condition_id, title, author, description, price, listing_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available')");
            $stmt->execute([
                $_SESSION['user_id'],
                $category_id,
                $condition_id,
                $title,
                $author ?: null,
                $description ?: null,
                $listing_type === 'sell' ? $price : null,
                $listing_type
            ]);

            $book_id = $pdo->lastInsertId();

            // Upload images
            $uploaded_count = 0;
            if (!isset($_FILES['images']) || empty($_FILES['images']['tmp_name'][0])) {
                throw new Exception('No images were uploaded.');
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'type' => $_FILES['images']['type'][$key],
                        'tmp_name' => $_FILES['images']['tmp_name'][$key],
                        'error' => $_FILES['images']['error'][$key],
                        'size' => $_FILES['images']['size'][$key],
                    ];

                    $upload_result = upload_book_image($file);

                    if ($upload_result['success']) {
                        $is_primary = ($uploaded_count === 0) ? 1 : 0;
                        $stmt = $pdo->prepare("INSERT INTO book_images (book_id, image_path, is_primary) VALUES (?, ?, ?)");
                        $stmt->execute([$book_id, $upload_result['path'], $is_primary]);
                        $uploaded_count++;
                    } else {
                        throw new Exception($upload_result['error']);
                    }
                } else {
                    throw new Exception('Upload error code: ' . $_FILES['images']['error'][$key]);
                }
            }

            if ($uploaded_count === 0) {
                throw new Exception('No images were uploaded successfully.');
            }

            $pdo->commit();
            flash('success', 'Book listed successfully!');
            redirect('dashboard.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', $e->getMessage());
        }
    }
}

$page_title = 'List a Book — Campus Connect';
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
        <h1 class="text-2xl font-bold text-gray-900 mb-6">List a Book</h1>

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

        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Section: Book Details -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Book Details</h2>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" value="<?= e($_POST['title'] ?? '') ?>" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Author</label>
                    <input type="text" name="author" value="<?= e($_POST['author'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
                        <select name="condition_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <option value="">Select Condition</option>
                            <?php foreach ($conditions as $cond): ?>
                            <option value="<?= $cond['id'] ?>" <?= ($_POST['condition_id'] ?? '') == $cond['id'] ? 'selected' : '' ?>>
                                <?= e($cond['label']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Describe the book..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Section: Listing Type -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Listing Type</h2>
                
                <div class="mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="listing_type" value="sell" <?= ($_POST['listing_type'] ?? 'sell') == 'sell' ? 'checked' : '' ?> required>
                        <span class="text-sm text-gray-700">For Sale</span>
                    </label>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="listing_type" value="giveaway" <?= ($_POST['listing_type'] ?? '') == 'giveaway' ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Giveaway (Free)</span>
                    </label>
                </div>

                <div id="price-field" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (₹)</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?= e($_POST['price'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Section: Upload Images -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Upload Images</h2>
                
                <div class="mb-4">
                    <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                <p class="text-sm text-gray-500">First image will be the cover. Max 2MB each. 1-5 images allowed.</p>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors">
                List Book
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">
            <a href="home.php" class="text-blue-600 hover:underline">Back to Browse</a>
        </p>
    </main>

    <script>
        document.querySelectorAll('input[name="listing_type"]').forEach(r => {
            r.addEventListener('change', () => {
                document.getElementById('price-field').classList.toggle('hidden', r.value === 'giveaway');
            });
        });
        
        // Initialize on page load
        const selectedType = document.querySelector('input[name="listing_type"]:checked');
        if (selectedType && selectedType.value === 'giveaway') {
            document.getElementById('price-field').classList.add('hidden');
        }
    </script>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
