<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo     = get_db();
$book_id = $_GET['id'] ?? null;

if (!$book_id) { flash('error', 'Book not found.'); redirect('home.php'); }

$stmt = $pdo->prepare("SELECT b.*, c.name AS category, bc.label AS condition_label,
    u.full_name AS seller_name, u.id AS seller_id
FROM books b
JOIN categories c ON b.category_id = c.id
JOIN book_conditions bc ON b.condition_id = bc.id
JOIN users u ON b.seller_id = u.id
WHERE b.id = ? AND b.status != 'deleted'");
$stmt->execute([$book_id]);
$book = $stmt->fetch();
if (!$book) { flash('error', 'Book not found.'); redirect('home.php'); }

$stmt = $pdo->prepare("SELECT * FROM book_images WHERE book_id = ? ORDER BY is_primary DESC, uploaded_at ASC");
$stmt->execute([$book_id]);
$images = $stmt->fetchAll();

$has_inquiry = false;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT * FROM book_inquiries WHERE book_id = ? AND buyer_id = ?");
    $stmt->execute([$book_id, $_SESSION['user_id']]);
    if ($stmt->fetch()) $has_inquiry = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        // Verify ownership before deletion
        if ($book['seller_id'] != $_SESSION['user_id']) {
            flash('error', 'You do not have permission to delete this book.');
            redirect('book_detail.php?id=' . $book_id);
        }
        
        try {
            $pdo->beginTransaction();
            
            // Delete images from server and database
            $stmt = $pdo->prepare("SELECT image_path FROM book_images WHERE book_id = ?");
            $stmt->execute([$book_id]);
            $book_images = $stmt->fetchAll();
            
            foreach ($book_images as $image) {
                $image_path = '../' . $image['image_path'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Delete book images from database
            $stmt = $pdo->prepare("DELETE FROM book_images WHERE book_id = ?");
            $stmt->execute([$book_id]);
            
            // Delete related inquiries
            $stmt = $pdo->prepare("DELETE FROM book_inquiries WHERE book_id = ?");
            $stmt->execute([$book_id]);
            
            // Mark book as deleted (soft delete)
            $stmt = $pdo->prepare("UPDATE books SET status = 'deleted', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$book_id]);
            
            $pdo->commit();
            flash('success', 'Book listing deleted successfully.');
            redirect('home.php');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Failed to delete book listing.');
            redirect('book_detail.php?id=' . $book_id);
        }
    } elseif ($action === 'send_inquiry') {
        $message = $_POST['message'] ?? '';
        if ($book['seller_id'] == $_SESSION['user_id'])      flash('error', 'You cannot inquire about your own book.');
        elseif ($book['status'] !== 'available')              flash('error', 'This book is not available.');
        elseif ($has_inquiry)                                 flash('error', 'You already sent an inquiry.');
        elseif (empty($message))                              flash('error', 'Please enter a message.');
        else {
            try {
                $stmt = $pdo->prepare("INSERT INTO book_inquiries (book_id, buyer_id, message, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$book_id, $_SESSION['user_id'], $message]);
                flash('success', 'Inquiry sent!');
                redirect('book_detail.php?id=' . $book_id);
            } catch (PDOException $e) {
                flash('error', 'Something went wrong.');
            }
        }
    }
}

$similar_stmt = $pdo->prepare("SELECT b.*, bc.label AS condition_label,
    (SELECT image_path FROM book_images WHERE book_id=b.id AND is_primary=1 LIMIT 1) AS cover_image
FROM books b JOIN book_conditions bc ON b.condition_id = bc.id
WHERE b.category_id = ? AND b.id != ? AND b.status = 'available'
ORDER BY b.created_at DESC LIMIT 4");
$similar_stmt->execute([$book['category_id'], $book_id]);
$similar = $similar_stmt->fetchAll();

$rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM transactions WHERE seller_id = ?");
$rating_stmt->execute([$book['seller_id']]);
$seller_rating = $rating_stmt->fetch();

$page_title = e($book['title']) . ' — Campus Connect';

function condition_style(string $label): string {
    return match(strtolower($label)) {
        'new'          => 'background:#dcfce7;color:#15803d;border:2px solid #16a34a;',
        'like new'     => 'background:#dbeafe;color:#1d4ed8;border:2px solid #3b82f6;',
        'good'         => 'background:#fef9c3;color:#854d0e;border:2px solid #ca8a04;',
        'fair'         => 'background:#ffedd5;color:#c2410c;border:2px solid #f97316;',
        'poor','used'  => 'background:#fee2e2;color:#b91c1c;border:2px solid #ef4444;',
        default        => 'background:#f3f4f6;color:#374151;border:2px solid #9ca3af;',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Barlow+Condensed:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root{--red:#E01B1B;--yellow:#F5C518;--black:#111111;--off-white:#F2F0EB;}
        *{font-family:'Barlow Condensed',sans-serif;box-sizing:border-box;}
        body{background:var(--off-white);color:var(--black);}
        .mono{font-family:'Space Mono',monospace;}

        .img-main{width:100%;aspect-ratio:3/4;object-fit:cover;border:2px solid var(--black);display:block;}
        .img-thumb{width:70px;height:70px;object-fit:cover;border:2px solid var(--black);cursor:pointer;opacity:.65;transition:opacity .15s,box-shadow .15s;flex-shrink:0;}
        .img-thumb:hover,.img-thumb.active{opacity:1;box-shadow:3px 3px 0 var(--black);}

        .info-box{background:#fff;border:2px solid var(--black);padding:20px 22px;}
        .seller-card{background:#fff;border:2px solid var(--black);padding:20px;}

        .btn-red{display:block;width:100%;background:var(--red);color:#fff;font-weight:900;font-size:1.1rem;letter-spacing:.15em;text-transform:uppercase;padding:14px;border:2px solid var(--black);box-shadow:4px 4px 0 var(--black);cursor:pointer;text-align:center;text-decoration:none;transition:transform .1s,box-shadow .1s;}
        .btn-red:hover{transform:translate(-2px,-2px);box-shadow:6px 6px 0 var(--black);}
        .btn-yellow{display:block;width:100%;background:var(--yellow);color:var(--black);font-weight:900;font-size:1rem;letter-spacing:.12em;text-transform:uppercase;padding:12px;border:2px solid var(--black);box-shadow:4px 4px 0 var(--black);cursor:pointer;text-align:center;text-decoration:none;transition:transform .1s,box-shadow .1s;}
        .btn-yellow:hover{transform:translate(-2px,-2px);box-shadow:6px 6px 0 var(--black);}

        .tag{font-weight:700;font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;padding:4px 10px;border:1.5px solid currentColor;display:inline-block;}

        .textarea-field{width:100%;border:2px solid var(--black);background:#fff;padding:10px 14px;font-family:'Space Mono',monospace;font-size:.8rem;outline:none;resize:vertical;transition:box-shadow .15s;color:var(--black);}
        .textarea-field:focus{box-shadow:3px 3px 0 var(--black);}
        .textarea-field::placeholder{color:#aaa;}

        .similar-card{background:#fff;border:2px solid var(--black);overflow:hidden;transition:transform .12s,box-shadow .12s;text-decoration:none;color:var(--black);display:block;}
        .similar-card:hover{transform:translate(-2px,-2px);box-shadow:4px 4px 0 var(--black);}
        .similar-card img{width:100%;aspect-ratio:3/4;object-fit:cover;border-bottom:2px solid var(--black);}
        .similar-no-img{width:100%;aspect-ratio:3/4;background:#f3f4f6;display:flex;align-items:center;justify-content:center;border-bottom:2px solid var(--black);}

        .flash-success{background:#f0fdf4;border:2px solid #16a34a;color:#15803d;padding:12px 16px;font-family:'Space Mono',monospace;font-size:.8rem;}
        .flash-error{background:#fef2f2;border:2px solid var(--red);color:var(--red);padding:12px 16px;font-family:'Space Mono',monospace;font-size:.8rem;}
    </style>
</head>
<body>
<?php require_once '../includes/header.php'; ?>

    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="mb-6 <?= $flash['type']==='success'?'flash-success':'flash-error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <nav class="mono" style="font-size:.72rem;color:#888;margin-bottom:24px;letter-spacing:.05em;text-transform:uppercase;">
        <a href="home.php" style="color:#888;text-decoration:none;">Home</a>
        <span style="margin:0 8px;">›</span>
        <a href="home.php?category_id=<?= $book['category_id'] ?>" style="color:#888;text-decoration:none;"><?= e($book['category']) ?></a>
        <span style="margin:0 8px;">›</span>
        <span style="color:var(--black);"><?= e($book['title']) ?></span>
    </nav>

    <!-- Main grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-14">

        <!-- LEFT: Gallery -->
        <div>
            <div style="position:relative;margin-bottom:10px;">
                <?php if (!empty($images)): ?>
                    <img id="main-img" class="img-main" src="/campus-connect/<?= e($images[0]['image_path']) ?>" alt="<?= e($book['title']) ?>">
                <?php else: ?>
                    <div class="img-main" style="background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                <?php endif; ?>
                <span style="position:absolute;top:12px;right:12px;font-weight:700;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;padding:4px 10px;<?= condition_style($book['condition_label']) ?>">
                    <?= e(strtoupper($book['condition_label'])) ?>
                </span>
            </div>
            <?php if (count($images) > 1): ?>
                <div style="display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;">
                    <?php foreach ($images as $i => $img): ?>
                        <img class="img-thumb <?= $i===0?'active':'' ?>"
                             src="/campus-connect/<?= e($img['image_path']) ?>" alt=""
                             onclick="switchImg(this,'/campus-connect/<?= e($img['image_path']) ?>')">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Info -->
        <div style="display:flex;flex-direction:column;gap:18px;">

            <!-- Tags + Title -->
            <div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                    <span class="tag" style="color:#1d4ed8;border-color:#3b82f6;background:#eff6ff;"><?= e($book['category']) ?></span>
                    <?php if ($book['listing_type']==='giveaway'): ?>
                        <span class="tag" style="color:#15803d;border-color:#16a34a;background:#dcfce7;">Free Giveaway</span>
                    <?php endif; ?>
                </div>
                <h1 style="font-weight:900;font-size:clamp(1.8rem,4vw,2.6rem);line-height:1;text-transform:uppercase;margin-bottom:8px;"><?= e($book['title']) ?></h1>
                <?php if ($book['author']): ?>
                    <p style="font-size:1rem;color:#555;font-weight:600;">by <?= e($book['author']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Price box -->
            <div class="info-box">
                <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div>
                        <div class="mono" style="font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:#888;margin-bottom:4px;">Selling Price</div>
                        <div style="font-weight:900;font-size:2.4rem;color:var(--red);line-height:1;"><?= format_price($book['price'], $book['listing_type']) ?></div>
                        <?php if ($book['listing_type']==='sell'&&$book['price']): ?>
                            <div class="mono" style="font-size:.7rem;color:#888;margin-top:2px;">+ estimated shipping</div>
                        <?php endif; ?>
                    </div>
                    <div style="text-align:right;">
                        <div class="mono" style="font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:#888;margin-bottom:4px;">Distance</div>
                        <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="var(--red)" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span style="font-weight:700;font-size:.95rem;">On Campus</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <?php if ($book['description']): ?>
                <div>
                    <div style="font-weight:700;font-size:.78rem;letter-spacing:.12em;text-transform:uppercase;border-bottom:2px solid var(--black);padding-bottom:6px;margin-bottom:10px;">Description</div>
                    <p class="mono" style="font-size:.78rem;line-height:1.8;color:#444;"><?= e($book['description']) ?></p>
                </div>
            <?php endif; ?>

            <!-- Seller card -->
            <div class="seller-card">
                <div class="mono" style="font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:#888;margin-bottom:12px;border-bottom:1px solid #eee;padding-bottom:8px;">Seller Info</div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:var(--yellow);border:2px solid var(--black);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.1rem;flex-shrink:0;">
                        <?= strtoupper(substr($book['seller_name'],0,1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:1.05rem;"><?= e($book['seller_name']) ?></div>
                        <?php if ($seller_rating['total']>0): $avg=round($seller_rating['avg_rating'],1); ?>
                            <div style="display:flex;align-items:center;gap:2px;">
                                <?php for($s=1;$s<=5;$s++): ?><span style="color:var(--yellow);font-size:.9rem;opacity:<?= $s<=$avg?'1':'.25' ?>;">★</span><?php endfor; ?>
                                <span class="mono" style="font-size:.68rem;color:#888;margin-left:4px;">(<?= $avg ?>)</span>
                            </div>
                        <?php else: ?>
                            <span class="mono" style="font-size:.68rem;color:#aaa;">No ratings yet</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($book['status']==='available' && $book['seller_id']!=$_SESSION['user_id']): ?>
                    <?php if ($has_inquiry): ?>
                        <div style="background:#f3f4f6;border:2px solid #d1d5db;padding:12px;font-weight:600;font-size:.9rem;color:#555;text-align:center;">✓ Inquiry Already Sent</div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="send_inquiry">
                            <textarea name="message" rows="3" required class="textarea-field" style="margin-bottom:10px;" placeholder="Hi! I'm interested. Is it still available?"></textarea>
                            <a class="btn-yellow" style="cursor:pointer;" onclick="this.closest('form').submit()"> Message Seller</a>
                        </form>
                    <?php endif; ?>
                <?php elseif ($book['seller_id']==$_SESSION['user_id']): ?>
                    <div style="background:#fef9c3;border:2px solid #ca8a04;padding:12px;font-weight:600;font-size:.9rem;color:#854d0e;text-align:center;">This is your listing</div>
                <?php endif; ?>

                <a href="report.php?type=book&id=<?= $book['id'] ?>" class="mono" style="font-size:.7rem;color:#bbb;text-decoration:none;">Report this listing</a>
            </div>
        </div>
    </div>

    <!-- Similar Books -->
    <?php if (!empty($similar)): ?>
        <div style="border-top:2px solid var(--black);padding-top:40px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:24px;">
                <div style="width:8px;height:8px;background:var(--red);"></div>
                <h2 style="font-weight:900;font-size:1.6rem;text-transform:uppercase;">Similar Textbooks</h2>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <?php foreach ($similar as $sb): ?>
                    <a href="book_detail.php?id=<?= $sb['id'] ?>" class="similar-card">
                        <?php if ($sb['cover_image']): ?>
                            <img src="/campus-connect/<?= e($sb['cover_image']) ?>" alt="<?= e($sb['title']) ?>">
                        <?php else: ?>
                            <div class="similar-no-img"><svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                        <?php endif; ?>
                        <div style="padding:12px;">
                            <p style="font-weight:700;font-size:.95rem;line-height:1.2;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;margin-bottom:4px;"><?= e($sb['title']) ?></p>
                            <p style="font-weight:900;font-size:1rem;color:var(--red);"><?= format_price($sb['price'], $sb['listing_type'] ?? 'sell') ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
<script>
function switchImg(thumb, src) {
    document.getElementById('main-img').src = src;
    document.querySelectorAll('.img-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}
</script>
</body>
</html>