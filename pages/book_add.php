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
                $listing_type === 'sell' ? number_format((float)$price, 2, '.', '') : null,
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Barlow+Condensed:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --red: #E01B1B; --yellow: #F5C518; --black: #111111; --off-white: #F2F0EB; }
        * { font-family: 'Barlow Condensed', sans-serif; }
        .mono { font-family: 'Space Mono', monospace; }
        body { background-color: var(--off-white); color: var(--black); }

        .input-field {
            width: 100%;
            border: 2px solid var(--black);
            background: #fff;
            padding: 10px 14px;
            font-family: 'Space Mono', monospace;
            font-size: 0.85rem;
            outline: none;
            transition: box-shadow 0.15s;
        }
        .input-field:focus { box-shadow: 3px 3px 0 var(--black); }

        .btn-primary {
            background: var(--red);
            color: #fff;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 8px 20px;
            border: 2px solid var(--black);
            box-shadow: 3px 3px 0 var(--black);
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.1s;
            display: inline-block;
            text-decoration: none;
        }
        .btn-primary:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 4px 0 var(--black);
        }
        .btn-primary:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--black);
        }

        .btn-ghost {
            background: transparent;
            color: var(--black);
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 6px 16px;
            border: 2px solid var(--black);
            cursor: pointer;
            transition: background 0.15s;
            display: inline-block;
            text-decoration: none;
        }
        .btn-ghost:hover { background: #e5e3de; }

        .card {
            background: #fff;
            border: 2px solid var(--black);
            box-shadow: 4px 4px 0 var(--black);
        }

        .label {
            display: block;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 6px;
            color: var(--black);
        }

        .page-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 900;
            font-size: 2.5rem;
            text-transform: uppercase;
            letter-spacing: -0.01em;
            line-height: 1;
        }

        .section-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--black);
            padding-bottom: 6px;
            margin-bottom: 16px;
        }

        .flash-success { background: #f0fdf4; border: 2px solid #16a34a; color: #15803d; padding: 12px 16px; }
        .flash-error   { background: #fef2f2; border: 2px solid var(--red); color: var(--red); padding: 12px 16px; }
        .flash-warning { background: #fffbeb; border: 2px solid #f59e0b; color: #92400e; padding: 12px 16px; }
        .flash-info    { background: #eff6ff; border: 2px solid #3b82f6; color: #1d4ed8; padding: 12px 16px; }

        select.input-field { appearance: none; cursor: pointer; }
        textarea.input-field { resize: vertical; }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>

    <main class="max-w-2xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="page-title mb-2">List a Book</h1>
            <div style="width: 40px; height: 3px; background: var(--red);"></div>
            <p class="mono mt-3" style="font-size: 0.8rem; color: #666;">Add your book to the campus marketplace.</p>
        </div>

        <?php $flash = get_flash(); ?>
        <?php if ($flash): ?>
            <div class="<?= match($flash['type']) { 'success' => 'flash-success', 'error' => 'flash-error', 'warning' => 'flash-warning', default => 'flash-info' } ?> mb-6">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Section: Book Details -->
            <div class="card p-6 mb-6">
                <h2 class="section-title">Book Details</h2>

                <div class="mb-4">
                    <label class="label">Title</label>
                    <input type="text" name="title" value="<?= e($_POST['title'] ?? '') ?>" required class="input-field" placeholder="Book title">
                </div>

                <div class="mb-4">
                    <label class="label">Author</label>
                    <input type="text" name="author" value="<?= e($_POST['author'] ?? '') ?>" class="input-field" placeholder="Author name">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label">Category</label>
                        <select name="category_id" class="input-field" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="label">Condition</label>
                        <select name="condition_id" class="input-field" required>
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
                    <label class="label">Description</label>
                    <textarea name="description" rows="4" class="input-field" placeholder="Describe the book..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Section: Listing Type -->
            <div class="card p-6 mb-6">
                <h2 class="section-title">Listing Type</h2>

                <div class="mb-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="radio" name="listing_type" value="sell" <?= ($_POST['listing_type'] ?? 'sell') == 'sell' ? 'checked' : '' ?> required style="width: 18px; height: 18px; accent: var(--red);">
                        <span style="font-weight: 600; font-size: 0.9rem;">For Sale</span>
                    </label>
                </div>

                <div class="mb-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="radio" name="listing_type" value="giveaway" <?= ($_POST['listing_type'] ?? '') == 'giveaway' ? 'checked' : '' ?> style="width: 18px; height: 18px; accent: var(--red);">
                        <span style="font-weight: 600; font-size: 0.9rem;">Giveaway (Free)</span>
                    </label>
                </div>

                <div id="price-field" class="mb-4">
                    <label class="label">Price (₹)</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?= e($_POST['price'] ?? '') ?>" class="input-field" placeholder="0.00">
                </div>
            </div>

            <!-- Section: Upload Images -->
            <div class="card p-6 mb-6">
                <h2 class="section-title">Upload Images</h2>

                <div class="mb-4">
                    <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="input-field" required>
                </div>
                <p class="mono" style="font-size: 0.75rem; color: #666;">First image will be the cover. Max 2MB each. 1-5 images allowed.</p>
            </div>

            <button type="submit" class="btn-primary w-full">List Book</button>
        </form>

        <p class="mt-6 text-center">
            <a href="home.php" class="btn-ghost">Back to Browse</a>
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
