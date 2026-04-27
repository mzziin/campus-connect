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

// Get book details and verify ownership
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND seller_id = ? AND status != 'deleted'");
$stmt->execute([$book_id, $_SESSION['user_id']]);
$book = $stmt->fetch();

if (!$book) {
    flash('error', 'Book not found or you do not have permission to edit it.');
    redirect('home.php');
}

// Get existing images
$stmt = $pdo->prepare("SELECT * FROM book_images WHERE book_id = ? ORDER BY is_primary DESC, uploaded_at ASC");
$stmt->execute([$book_id]);
$images = $stmt->fetchAll();

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
    $remove_images = isset($_POST['remove_images']) && $_POST['remove_images'] !== '' ? explode(',', $_POST['remove_images']) : [];

    // Validation
    if (empty($title)) {
        flash('error', 'Title is required.');
    } elseif (empty($category_id)) {
        flash('error', 'Category is required.');
    } elseif (empty($condition_id)) {
        flash('error', 'Condition is required.');
    } elseif ($listing_type === 'sell' && empty($price)) {
        flash('error', 'Price is required for sell listings.');
    } else {
        try {
            $pdo->beginTransaction();

            // Update book details
            $stmt = $pdo->prepare("UPDATE books SET category_id = ?, condition_id = ?, title = ?, author = ?, description = ?, price = ?, listing_type = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                $category_id,
                $condition_id,
                $title,
                $author ?: null,
                $description ?: null,
                $listing_type === 'sell' ? number_format((float)$price, 2, '.', '') : null,
                $listing_type,
                $book_id
            ]);

            // Remove selected images
            if (!empty($remove_images)) {
                // Count images before removal to ensure at least one remains
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM book_images WHERE book_id = ?");
                $stmt->execute([$book_id]);
                $current_image_count = $stmt->fetchColumn();
                
                // Check if removing all images
                if (count($remove_images) >= $current_image_count) {
                    throw new Exception('At least one image is required. You cannot remove all images.');
                }
                
                foreach ($remove_images as $image_id) {
                    // Get image path before deletion
                    $stmt = $pdo->prepare("SELECT image_path FROM book_images WHERE id = ? AND book_id = ?");
                    $stmt->execute([$image_id, $book_id]);
                    $image = $stmt->fetch();
                    
                    if ($image) {
                        // Delete file from server
                        $image_path = '../' . $image['image_path'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                        
                        // Delete from database
                        $stmt = $pdo->prepare("DELETE FROM book_images WHERE id = ? AND book_id = ?");
                        $stmt->execute([$image_id, $book_id]);
                    }
                }
            }

            // Upload new images
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                // Get current image count after any removals
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM book_images WHERE book_id = ?");
                $stmt->execute([$book_id]);
                $current_image_count = $stmt->fetchColumn();
                $new_images_count = count($_FILES['images']['name']);
                
                if ($current_image_count + $new_images_count > 5) {
                    throw new Exception('Maximum 5 images allowed.');
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
                            $stmt = $pdo->prepare("INSERT INTO book_images (book_id, image_path, is_primary) VALUES (?, ?, 0)");
                            $stmt->execute([$book_id, $upload_result['path']]);
                        } else {
                            throw new Exception('Image upload failed: ' . $upload_result['error']);
                        }
                    }
                }
            }

            // Final check: ensure at least one image remains
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM book_images WHERE book_id = ?");
            $stmt->execute([$book_id]);
            $final_image_count = $stmt->fetchColumn();
            
            if ($final_image_count == 0) {
                throw new Exception('At least one image is required.');
            }

            $pdo->commit();
            flash('success', 'Book updated successfully!');
            redirect('book_detail.php?id=' . $book_id);

        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', $e->getMessage());
        }
    }
}

$page_title = 'Edit Book — Campus Connect';
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
        :root { --red:#E01B1B; --yellow:#F5C518; --black:#111111; --off-white:#F2F0EB; }
        * { font-family: 'Barlow Condensed', sans-serif; }
        body { background-color: var(--off-white); color: var(--black); min-height: 100vh; }

        .input-field {
            width: 100%;
            border: 2px solid var(--black);
            background: #fff;
            padding: 10px 14px;
            font-family: 'Space Mono', monospace;
            font-size: 0.8rem;
            outline: none;
            transition: box-shadow 0.15s;
            color: var(--black);
        }
        .input-field:focus { box-shadow: 3px 3px 0 var(--black); }
        .input-field::placeholder { color: #aaa; text-transform: uppercase; letter-spacing: 0.05em; }

        .label {
            display: block;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 6px;
            color: var(--black);
        }

        .btn-primary {
            background: var(--red);
            color: #fff;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 10px 24px;
            border: 2px solid var(--black);
            box-shadow: 3px 3px 0 var(--black);
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.1s;
            display: inline-block;
            text-decoration: none;
        }
        .btn-primary:hover { transform: translate(-1px,-1px); box-shadow: 4px 4px 0 var(--black); }
        .btn-primary:active { transform: translate(2px,2px); box-shadow: 1px 1px 0 var(--black); }

        .btn-secondary {
            background: var(--yellow);
            color: var(--black);
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 8px 20px;
            border: 2px solid var(--black);
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.1s;
            display: inline-block;
            text-decoration: none;
        }
        .btn-secondary:hover { transform: translate(-1px,-1px); box-shadow: 4px 4px 0 var(--black); }

        .card {
            background: #fff;
            border: 2px solid var(--black);
            box-shadow: 4px 4px 0 var(--black);
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

        .flash-success { background:#f0fdf4; border:2px solid #16a34a; color:#15803d; padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.8rem; }
        .flash-error   { background:#fef2f2; border:2px solid var(--red); color:var(--red); padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.8rem; }

        .image-preview {
            position: relative;
            display: inline-block;
            border: 2px solid var(--black);
            box-shadow: 2px 2px 0 var(--black);
        }
        .image-preview img {
            width: 120px;
            height: 160px;
            object-fit: cover;
            display: block;
        }
        .image-preview .remove-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--red);
            color: #fff;
            border: 2px solid var(--black);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }
        .image-preview .remove-btn:hover {
            background: #cc0000;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <?php $flash = get_flash(); ?>
        <?php if ($flash): ?>
            <div class="<?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?> mb-6">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="mb-8">
            <h1 class="page-title mb-2">Edit Book</h1>
            <div style="width: 40px; height: 3px; background: var(--red);"></div>
            <p class="mono" style="font-size: 0.8rem; color: #666;">Update your book listing details.</p>
        </div>

        <div class="card p-8">
            <form method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Title -->
                    <div class="md:col-span-2">
                        <label class="label">Title *</label>
                        <input type="text" name="title" value="<?= e($book['title']) ?>" required
                               class="input-field" placeholder="Enter book title">
                    </div>

                    <!-- Author -->
                    <div>
                        <label class="label">Author</label>
                        <input type="text" name="author" value="<?= e($book['author'] ?? '') ?>"
                               class="input-field" placeholder="Enter author name">
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="label">Category *</label>
                        <select name="category_id" required class="input-field">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $book['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Condition -->
                    <div>
                        <label class="label">Condition *</label>
                        <select name="condition_id" required class="input-field">
                            <option value="">Select condition</option>
                            <?php foreach ($conditions as $condition): ?>
                                <option value="<?= $condition['id'] ?>" <?= $book['condition_id'] == $condition['id'] ? 'selected' : '' ?>>
                                    <?= e($condition['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Listing Type -->
                    <div>
                        <label class="label">Listing Type *</label>
                        <select name="listing_type" required class="input-field" onchange="togglePriceField(this.value)">
                            <option value="sell" <?= $book['listing_type'] === 'sell' ? 'selected' : '' ?>>For Sale</option>
                            <option value="giveaway" <?= $book['listing_type'] === 'giveaway' ? 'selected' : '' ?>>Free Giveaway</option>
                        </select>
                    </div>

                    <!-- Price -->
                    <div id="price-field">
                        <label class="label">Price *</label>
                        <input type="number" name="price" value="<?= e($book['price']) ?>" step="0.01" min="0"
                               class="input-field" placeholder="0.00">
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="label">Description</label>
                        <textarea name="description" rows="4" class="input-field"
                                  placeholder="Describe the book condition, edition, any notes..."><?= e($book['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Images Section -->
                <div class="mt-8">
                    <div class="section-title">Images</div>
                    
                    <!-- Existing Images -->
                    <?php if (!empty($images)): ?>
                        <div class="mb-6">
                            <p class="mono" style="font-size: 0.7rem; color: #666; margin-bottom: 12px;">Current images (click × to remove):</p>
                            <div class="flex flex-wrap gap-4">
                                <?php foreach ($images as $image): ?>
                                    <div class="image-preview" id="preview-<?= $image['id'] ?>">
                                        <img src="/campus-connect/<?= e($image['image_path']) ?>" alt="Book image">
                                        <div class="remove-btn" onclick="toggleImageRemove(<?= $image['id'] ?>)">×</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="remove_images" id="remove_images_input" value="">
                        </div>
                    <?php endif; ?>

                    <!-- New Images -->
                    <div>
                        <p class="mono" style="font-size: 0.7rem; color: #666; margin-bottom: 12px;">Add new images (optional):</p>
                        <input type="file" name="images[]" multiple accept="image/*" class="input-field">
                        <p class="mono" style="font-size: 0.65rem; color: #888; margin-top: 4px;">Maximum 5 images total. JPG, PNG, GIF only.</p>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex gap-4 mt-8">
                    <button type="submit" class="btn-primary">Update Book</button>
                    <a href="book_detail.php?id=<?= $book_id ?>" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <?php require_once '../includes/footer.php'; ?>

    <script>
    function togglePriceField(listingType) {
        const priceField = document.getElementById('price-field');
        const priceInput = priceField.querySelector('input');
        
        if (listingType === 'giveaway') {
            priceField.style.display = 'none';
            priceInput.removeAttribute('required');
        } else {
            priceField.style.display = 'block';
            priceInput.setAttribute('required', 'required');
        }
    }

    function toggleImageRemove(imageId) {
        const preview = document.getElementById('preview-' + imageId);
        const input = document.getElementById('remove_images_input');
        const removeIds = input.value ? input.value.split(',').map(id => parseInt(id)) : [];
        
        if (removeIds.includes(imageId)) {
            // Remove from list
            const index = removeIds.indexOf(imageId);
            removeIds.splice(index, 1);
            preview.style.opacity = '1';
        } else {
            // Add to list
            removeIds.push(imageId);
            preview.style.opacity = '0.5';
        }
        
        input.value = removeIds.join(',');
    }

    // Initialize price field visibility
    togglePriceField('<?= $book['listing_type'] ?>');
    </script>
</body>
</html>
