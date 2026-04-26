<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo     = get_db();
$user_id = $_SESSION['user_id'];

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
$avg_rating     = $avg_rating_stmt->fetchColumn();
$avg_rating_val = $avg_rating ? round($avg_rating, 1) : null;

$earnings_stmt = $pdo->prepare("SELECT SUM(b.price) FROM transactions t JOIN books b ON t.book_id = b.id WHERE t.seller_id = ? AND t.status = 'completed'");
$earnings_stmt->execute([$user_id]);
$total_earnings = $earnings_stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT b.*, c.name AS category, bc.label AS condition_label,
       (SELECT image_path FROM book_images WHERE book_id=b.id AND is_primary=1 LIMIT 1) AS cover_image
FROM books b
JOIN categories c ON b.category_id = c.id
JOIN book_conditions bc ON b.condition_id = bc.id
WHERE b.seller_id = ?
ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$my_books = $stmt->fetchAll();

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

$stmt = $pdo->prepare("SELECT t.*, b.title AS book_title,
       seller.full_name AS seller_name, buyer.full_name AS buyer_name
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
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Barlow+Condensed:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root{--red:#E01B1B;--yellow:#F5C518;--black:#111111;--off-white:#F2F0EB;}
        *{font-family:'Barlow Condensed',sans-serif;box-sizing:border-box;}
        body{background:var(--off-white);color:var(--black);}
        .mono{font-family:'Space Mono',monospace;}

        /* stat cards */
        .stat-card{background:#fff;border:2px solid var(--black);padding:22px 24px;position:relative;}
        .stat-card.highlight{background:var(--yellow);}
        .stat-card.dark{background:var(--black);color:#fff;}

        /* section card */
        .section-card{background:#fff;border:2px solid var(--black);}
        .section-head{padding:16px 20px;border-bottom:2px solid var(--black);display:flex;align-items:center;justify-content:space-between;}

        /* table */
        table{width:100%;border-collapse:collapse;}
        th{padding:10px 16px;text-align:left;font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;color:#888;border-bottom:2px solid var(--black);background:#fafafa;}
        td{padding:12px 16px;border-bottom:1px solid #eee;font-size:.9rem;vertical-align:middle;}
        tr:last-child td{border-bottom:none;}

        /* badges */
        .badge{font-weight:700;font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;padding:3px 8px;border:1.5px solid currentColor;display:inline-block;white-space:nowrap;}
        .badge-green{color:#15803d;border-color:#16a34a;background:#f0fdf4;}
        .badge-red{color:var(--red);border-color:var(--red);background:#fef2f2;}
        .badge-yellow{color:#92400e;border-color:#f59e0b;background:#fffbeb;}
        .badge-blue{color:#1d4ed8;border-color:#3b82f6;background:#eff6ff;}
        .badge-gray{color:#374151;border-color:#9ca3af;background:#f9fafb;}

        .btn-sm{font-weight:700;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase;padding:6px 14px;border:2px solid var(--black);text-decoration:none;display:inline-block;transition:background .12s,color .12s;cursor:pointer;}
        .btn-sm-red{background:var(--red);color:#fff;}
        .btn-sm-red:hover{background:#c01515;}
        .btn-sm-black{background:var(--black);color:#fff;}
        .btn-sm-black:hover{background:#333;}
        .btn-sm-yellow{background:var(--yellow);color:var(--black);}
        .btn-sm-yellow:hover{background:#e0b000;}

        .btn-add{background:var(--red);color:#fff;font-weight:700;font-size:.85rem;letter-spacing:.1em;text-transform:uppercase;padding:8px 20px;border:2px solid var(--black);box-shadow:3px 3px 0 var(--black);text-decoration:none;display:inline-block;transition:transform .1s,box-shadow .1s;}
        .btn-add:hover{transform:translate(-1px,-1px);box-shadow:4px 4px 0 var(--black);}

        .flash-success{background:#f0fdf4;border:2px solid #16a34a;color:#15803d;padding:12px 16px;font-family:'Space Mono',monospace;font-size:.8rem;}
        .flash-error{background:#fef2f2;border:2px solid var(--red);color:var(--red);padding:12px 16px;font-family:'Space Mono',monospace;font-size:.8rem;}

        .rank-bar-bg{background:#eee;height:10px;border:1.5px solid var(--black);position:relative;overflow:hidden;}
        .rank-bar-fill{height:100%;background:var(--yellow);transition:width .4s;}

        .empty-state{padding:48px;text-align:center;color:#aaa;}
        .empty-state p{font-size:1rem;font-weight:700;text-transform:uppercase;margin-bottom:8px;}
    </style>
</head>
<body>
<?php require_once '../includes/header.php'; ?>

    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="mb-6 <?= $flash['type']==='success'?'flash-success':'flash-error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Page header -->
    <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:28px;">
        <div>
            <p class="mono" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;color:#888;margin-bottom:4px;">Ready to level up your selling game today?</p>
            <h1 style="font-weight:900;font-size:clamp(2rem,5vw,3rem);text-transform:uppercase;line-height:1;">
                Hey, <span style="color:var(--red);font-style:italic;"><?= e(explode(' ', $_SESSION['user_name'])[0]) ?>!</span>
            </h1>
        </div>
        <a href="book_add.php" class="btn-add">+ List a Book</a>
    </div>

    <!-- ── RANK / KP BANNER ── -->
    <div style="background:#fff;border:2px solid var(--black);padding:24px 28px;margin-bottom:28px;display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center;">
        <div>
            <div class="mono" style="font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:#888;margin-bottom:6px;">Current Rank</div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                <span style="font-weight:900;font-size:1.6rem;text-transform:uppercase;">Campus Seller</span>
                <span style="background:var(--yellow);color:var(--black);font-weight:700;font-size:.72rem;letter-spacing:.08em;padding:3px 10px;border:1.5px solid var(--black);">Lvl <?= min(5, max(1, (int)($books_sold/5)+1)) ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#888;min-width:80px;">Bibliophile</span>
                <div class="rank-bar-bg" style="flex:1;">
                    <div class="rank-bar-fill" style="width:<?= min(100, ($books_sold % 5) * 20) ?>%;"></div>
                </div>
                <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#888;">Next: Legend</span>
            </div>
            <div class="mono" style="font-size:.68rem;color:#aaa;"><?= max(0, 5 - ($books_sold % 5)) ?> more sales to next level</div>
        </div>
        <div style="text-align:right;">
            <div class="mono" style="font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:#888;margin-bottom:4px;">Total Earnings</div>
            <div style="font-weight:900;font-size:2rem;color:var(--red);">₹<?= number_format($total_earnings, 2) ?></div>
            <div class="mono" style="font-size:.68rem;color:#aaa;">from <?= $books_sold ?> sold</div>
        </div>
    </div>

    <!-- ── STAT CARDS ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="stat-card highlight">
            <div class="mono" style="font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:#555;margin-bottom:6px;">Active Listings</div>
            <div style="font-weight:900;font-size:2.4rem;line-height:1;"><?= $active_listings ?></div>
        </div>
        <div class="stat-card">
            <div class="mono" style="font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:#888;margin-bottom:6px;">Books Sold</div>
            <div style="font-weight:900;font-size:2.4rem;line-height:1;"><?= $books_sold ?></div>
            <?php if ($books_sold > 0): ?><div class="mono" style="font-size:.65rem;color:#16a34a;margin-top:4px;">Top 10% on campus</div><?php endif; ?>
        </div>
        <div class="stat-card">
            <div class="mono" style="font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:#888;margin-bottom:6px;">Active Chats</div>
            <div style="font-weight:900;font-size:2.4rem;line-height:1;"><?= $active_conversations ?></div>
        </div>
        <div class="stat-card dark">
            <div class="mono" style="font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:#888;margin-bottom:6px;">Avg Rating</div>
            <div style="font-weight:900;font-size:2.4rem;line-height:1;color:var(--yellow);">
                <?= $avg_rating_val ? $avg_rating_val : '—' ?>
            </div>
            <?php if ($avg_rating_val): ?>
                <div style="display:flex;gap:2px;margin-top:4px;">
                    <?php for($s=1;$s<=5;$s++): ?><span style="color:var(--yellow);font-size:.8rem;opacity:<?= $s<=$avg_rating_val?'1':'.25' ?>;">★</span><?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── MY BOOK LISTINGS ── -->
    <div class="section-card mb-6">
        <div class="section-head">
            <h2 style="font-weight:900;font-size:1.2rem;text-transform:uppercase;">My Book Listings</h2>
            <a href="book_add.php" style="font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:.85rem;letter-spacing:.08em;text-transform:uppercase;color:var(--red);text-decoration:none;">+ Add New</a>
        </div>
        <?php if (empty($my_books)): ?>
            <div class="empty-state"><p>No listings yet</p><a href="book_add.php" style="color:var(--red);font-weight:700;font-size:.85rem;text-transform:uppercase;letter-spacing:.06em;">List your first book →</a></div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr>
                        <th>Cover</th><th>Title</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($my_books as $book): ?>
                        <tr>
                            <td>
                                <?php if ($book['cover_image']): ?>
                                    <img src="/campus-connect/<?= e($book['cover_image']) ?>" style="width:40px;height:52px;object-fit:cover;border:1.5px solid #ddd;" alt="">
                                <?php else: ?>
                                    <div style="width:40px;height:52px;background:#f3f4f6;border:1.5px solid #ddd;display:flex;align-items:center;justify-content:center;"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                                <?php endif; ?>
                            </td>
                            <td><span style="font-weight:700;"><?= e($book['title']) ?></span></td>
                            <td><span class="mono" style="font-size:.75rem;color:#666;"><?= e($book['category']) ?></span></td>
                            <td><span style="font-weight:900;color:var(--red);"><?= format_price($book['price'], $book['listing_type']) ?></span></td>
                            <td>
                                <?php $sc = match($book['status']) { 'available'=>'badge-green','sold'=>'badge-blue','deleted'=>'badge-red', default=>'badge-gray' }; ?>
                                <span class="badge <?= $sc ?>"><?= ucfirst(e($book['status'])) ?></span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="book_detail.php?id=<?= $book['id'] ?>" class="btn-sm btn-sm-black">View</a>
                                    <?php if ($book['status']==='available'): ?>
                                        <a href="inquiries.php" class="btn-sm btn-sm-yellow">Inquiries</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── CONVERSATIONS ── -->
    <div class="section-card mb-6">
        <div class="section-head">
            <h2 style="font-weight:900;font-size:1.2rem;text-transform:uppercase;">My Conversations</h2>
        </div>
        <?php if (empty($conversations)): ?>
            <div class="empty-state"><p>No active conversations</p></div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Book</th><th>Other Party</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($conversations as $conv): ?>
                        <tr>
                            <td><span style="font-weight:700;"><?= e($conv['book_title']) ?></span></td>
                            <td>
                                <span class="mono" style="font-size:.78rem;">
                                    <?= $conv['type']==='inquiry' ? e($conv['seller_name']) : ($conv['seller_id']==$user_id ? e($conv['buyer_name']) : e($conv['seller_name'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php $sc = match($conv['status']) { 'active'=>'badge-green','completed'=>'badge-gray','pending'=>'badge-yellow','accepted'=>'badge-blue','rejected'=>'badge-red', default=>'badge-gray' }; ?>
                                <span class="badge <?= $sc ?>"><?= ucfirst(e($conv['status'])) ?></span>
                            </td>
                            <td>
                                <?php if ($conv['type']==='inquiry'): ?>
                                    <?php if ($conv['status']==='pending'): ?>
                                        <span class="mono" style="font-size:.75rem;color:#aaa;">Awaiting seller</span>
                                    <?php elseif ($conv['status']==='accepted'): ?>
                                        <a href="chat.php?conversation_id=<?= $conv['id'] ?>" class="btn-sm btn-sm-black">Open Chat</a>
                                    <?php else: ?>
                                        <span class="mono" style="font-size:.75rem;color:#aaa;">Rejected</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="chat.php?conversation_id=<?= $conv['id'] ?>" class="btn-sm btn-sm-black">Open Chat</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── TRANSACTIONS ── -->
    <div class="section-card">
        <div class="section-head">
            <h2 style="font-weight:900;font-size:1.2rem;text-transform:uppercase;">My Transactions</h2>
        </div>
        <?php if (empty($transactions)): ?>
            <div class="empty-state"><p>No transactions yet</p></div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Type</th><th>Book</th><th>Other Party</th><th>Status</th><th>Completed</th><th>Rating</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td>
                                <span class="badge <?= $txn['seller_id']==$user_id?'badge-green':'badge-blue' ?>">
                                    <?= $txn['seller_id']==$user_id?'Sold':'Bought' ?>
                                </span>
                            </td>
                            <td><span style="font-weight:700;"><?= e($txn['book_title']) ?></span></td>
                            <td><span class="mono" style="font-size:.78rem;"><?= $txn['seller_id']==$user_id?e($txn['buyer_name']):e($txn['seller_name']) ?></span></td>
                            <td><span class="badge badge-gray"><?= ucfirst(e($txn['status'])) ?></span></td>
                            <td><span class="mono" style="font-size:.75rem;color:#666;"><?= format_date($txn['completed_at']) ?></span></td>
                            <td>
                                <?php if ($txn['rating']): ?>
                                    <div style="display:flex;gap:1px;"><?php for($s=1;$s<=5;$s++): ?><span style="color:var(--yellow);font-size:.85rem;opacity:<?= $s<=$txn['rating']?'1':'.25' ?>;">★</span><?php endfor; ?></div>
                                <?php else: ?>
                                    <span class="mono" style="font-size:.72rem;color:#aaa;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($txn['buyer_id']==$user_id && $txn['rating']===null): ?>
                                    <a href="transaction_review.php?id=<?= $txn['id'] ?>" class="btn-sm btn-sm-yellow">Review</a>
                                <?php else: ?><span style="color:#ddd;">—</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>