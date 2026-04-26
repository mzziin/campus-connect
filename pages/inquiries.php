<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo     = get_db();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $inquiry_id = $_POST['inquiry_id'] ?? '';
    $book_id    = $_POST['book_id'] ?? '';

    if ($action === 'accept') {
        $stmt = $pdo->prepare("SELECT seller_id FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();

        if (!$book || $book['seller_id'] != $user_id) {
            flash('error', 'Permission denied.');
            redirect('inquiries.php');
        }

        $stmt = $pdo->prepare("SELECT * FROM book_inquiries WHERE id = ? AND book_id = ?");
        $stmt->execute([$inquiry_id, $book_id]);
        $inquiry = $stmt->fetch();

        if (!$inquiry || $inquiry['status'] !== 'pending') {
            flash('error', 'Inquiry not found or already processed.');
            redirect('inquiries.php');
        }

        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE book_inquiries SET status='accepted' WHERE id=? AND book_id=?")->execute([$inquiry_id, $book_id]);
            $pdo->prepare("INSERT INTO conversations (inquiry_id, book_id, seller_id, buyer_id) VALUES (?,?,?,?)")->execute([$inquiry_id, $book_id, $user_id, $inquiry['buyer_id']]);
            $pdo->prepare("UPDATE book_inquiries SET status='rejected' WHERE book_id=? AND id != ? AND status='pending'")->execute([$book_id, $inquiry_id]);
            $pdo->commit();
            flash('success', 'Inquiry accepted! You can now chat with the buyer.');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'Something went wrong. Please try again.');
        }
        redirect('inquiries.php');

    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("SELECT seller_id FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();

        if (!$book || $book['seller_id'] != $user_id) {
            flash('error', 'Permission denied.');
            redirect('inquiries.php');
        }

        $pdo->prepare("UPDATE book_inquiries SET status='rejected' WHERE id=? AND book_id IN (SELECT id FROM books WHERE seller_id=?)")->execute([$inquiry_id, $user_id]);
        flash('success', 'Inquiry rejected.');
        redirect('inquiries.php');
    }
}

$status_filter = $_GET['status'] ?? '';

$stmt = $pdo->prepare("SELECT bi.*, u.full_name AS buyer_name, bk.title AS book_title, bk.status AS book_status
FROM book_inquiries bi
JOIN users u ON bi.buyer_id = u.id
JOIN books bk ON bi.book_id = bk.id
WHERE bk.seller_id = ?
ORDER BY bi.created_at DESC");
$stmt->execute([$user_id]);
$all_inquiries = $stmt->fetchAll();

$inquiries = $status_filter
    ? array_filter($all_inquiries, fn($i) => $i['status'] === $status_filter)
    : $all_inquiries;

$counts = ['all' => count($all_inquiries)];
foreach ($all_inquiries as $i) {
    $counts[$i['status']] = ($counts[$i['status']] ?? 0) + 1;
}

$page_title = 'Inquiries — Campus Connect';
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

        .pill{font-weight:700;font-size:.82rem;letter-spacing:.06em;text-transform:uppercase;padding:6px 16px;border:2px solid var(--black);text-decoration:none;color:var(--black);background:#fff;transition:background .12s,color .12s;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;}
        .pill:hover{background:#e5e3de;}
        .pill.active{background:var(--black);color:#fff;}

        .inquiry-card{background:#fff;border:2px solid var(--black);transition:box-shadow .12s;}
        .inquiry-card:hover{box-shadow:4px 4px 0 var(--black);}

        .badge{font-weight:700;font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;padding:3px 8px;border:1.5px solid currentColor;display:inline-block;white-space:nowrap;}
        .badge-green{color:#15803d;border-color:#16a34a;background:#f0fdf4;}
        .badge-red{color:var(--red);border-color:var(--red);background:#fef2f2;}
        .badge-yellow{color:#92400e;border-color:#f59e0b;background:#fffbeb;}
        .badge-blue{color:#1d4ed8;border-color:#3b82f6;background:#eff6ff;}
        .badge-gray{color:#374151;border-color:#9ca3af;background:#f9fafb;}

        .btn-accept{background:#16a34a;color:#fff;font-weight:700;font-size:.82rem;letter-spacing:.08em;text-transform:uppercase;padding:8px 18px;border:2px solid var(--black);box-shadow:3px 3px 0 var(--black);cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:transform .1s,box-shadow .1s;}
        .btn-accept:hover{transform:translate(-1px,-1px);box-shadow:4px 4px 0 var(--black);}

        .btn-reject{background:#fff;color:var(--red);font-weight:700;font-size:.82rem;letter-spacing:.08em;text-transform:uppercase;padding:8px 18px;border:2px solid var(--red);cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:background .12s;}
        .btn-reject:hover{background:#fef2f2;}

        .btn-chat{background:var(--black);color:#fff;font-weight:700;font-size:.82rem;letter-spacing:.08em;text-transform:uppercase;padding:8px 18px;border:2px solid var(--black);box-shadow:3px 3px 0 var(--black);cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:transform .1s,box-shadow .1s;}
        .btn-chat:hover{transform:translate(-1px,-1px);box-shadow:4px 4px 0 var(--black);}

        .flash-success{background:#f0fdf4;border:2px solid #16a34a;color:#15803d;padding:12px 16px;font-family:'Space Mono',monospace;font-size:.8rem;}
        .flash-error{background:#fef2f2;border:2px solid var(--red);color:var(--red);padding:12px 16px;font-family:'Space Mono',monospace;font-size:.8rem;}

        .empty-state{background:#fff;border:2px solid var(--black);padding:60px;text-align:center;}

        .count-chip{background:var(--yellow);color:var(--black);font-family:'Space Mono',monospace;font-size:.65rem;font-weight:700;padding:1px 6px;border-radius:0;}
        .pill.active .count-chip{background:#fff;color:var(--black);}
    </style>
</head>
<body>
<?php require_once '../includes/header.php'; ?>

    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="mb-6 <?= $flash['type']==='success'?'flash-success':'flash-error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Page header -->
    <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
        <div>
            <p class="mono" style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;color:#888;margin-bottom:4px;">Seller Dashboard</p>
            <h1 style="font-weight:900;font-size:clamp(1.8rem,4vw,2.8rem);text-transform:uppercase;line-height:1;">Inquiries Received</h1>
        </div>
        <a href="book_add.php" style="background:var(--red);color:#fff;font-weight:700;font-size:.85rem;letter-spacing:.1em;text-transform:uppercase;padding:10px 20px;border:2px solid var(--black);box-shadow:3px 3px 0 var(--black);text-decoration:none;display:inline-block;transition:transform .1s,box-shadow .1s;" class="hover:translate-x-[-2px]">+ Add New Listing</a>
    </div>

    <!-- Filter tabs -->
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:28px;">
        <a href="inquiries.php" class="pill <?= $status_filter===''?'active':'' ?>">
            All <span class="count-chip"><?= $counts['all'] ?? 0 ?></span>
        </a>
        <a href="inquiries.php?status=pending" class="pill <?= $status_filter==='pending'?'active':'' ?>">
            Pending <span class="count-chip"><?= $counts['pending'] ?? 0 ?></span>
        </a>
        <a href="inquiries.php?status=accepted" class="pill <?= $status_filter==='accepted'?'active':'' ?>">
            Accepted <span class="count-chip"><?= $counts['accepted'] ?? 0 ?></span>
        </a>
        <a href="inquiries.php?status=rejected" class="pill <?= $status_filter==='rejected'?'active':'' ?>">
            Rejected <span class="count-chip"><?= $counts['rejected'] ?? 0 ?></span>
        </a>
    </div>

    <?php if (empty($inquiries)): ?>
        <div class="empty-state">
            <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.5" style="margin:0 auto 16px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p style="font-weight:900;font-size:1.1rem;text-transform:uppercase;color:#aaa;margin-bottom:8px;">No inquiries found</p>
            <p class="mono" style="font-size:.75rem;color:#bbb;">
                <?= $status_filter ? 'No '.e($status_filter).' inquiries.' : 'List a book to start receiving inquiries.' ?>
            </p>
        </div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($inquiries as $inquiry): ?>
                <?php
                    $stmt = $pdo->prepare("SELECT image_path FROM book_images WHERE book_id = ? AND is_primary = 1 LIMIT 1");
                    $stmt->execute([$inquiry['book_id']]);
                    $cover = $stmt->fetch();
                ?>
                <div class="inquiry-card">
                    <div style="display:flex;gap:0;">

                        <!-- Cover image strip -->
                        <div style="width:80px;flex-shrink:0;border-right:2px solid var(--black);">
                            <?php if ($cover): ?>
                                <img src="/campus-connect/<?= e($cover['image_path']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
                            <?php else: ?>
                                <div style="width:100%;height:100%;min-height:100px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#ccc" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Content -->
                        <div style="flex:1;padding:18px 20px;display:flex;flex-direction:column;gap:10px;">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                                <div>
                                    <h3 style="font-weight:700;font-size:1.1rem;margin-bottom:2px;"><?= e($inquiry['book_title']) ?></h3>
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                        <div style="width:24px;height:24px;border-radius:50%;background:var(--yellow);border:1.5px solid var(--black);display:inline-flex;align-items:center;justify-content:center;font-weight:900;font-size:.7rem;">
                                            <?= strtoupper(substr($inquiry['buyer_name'],0,1)) ?>
                                        </div>
                                        <span style="font-size:.9rem;color:#555;font-weight:600;"><?= e($inquiry['buyer_name']) ?></span>
                                        <span class="mono" style="font-size:.65rem;color:#aaa;"><?= time_ago($inquiry['created_at']) ?></span>
                                    </div>
                                </div>
                                <?php
                                    $bc = match($inquiry['status']) {
                                        'pending'  => 'badge-yellow',
                                        'accepted' => 'badge-green',
                                        'rejected' => 'badge-red',
                                        default    => 'badge-gray'
                                    };
                                ?>
                                <span class="badge <?= $bc ?>"><?= ucfirst(e($inquiry['status'])) ?></span>
                            </div>

                            <?php if ($inquiry['message']): ?>
                                <div style="background:var(--off-white);border-left:3px solid var(--black);padding:10px 14px;">
                                    <p class="mono" style="font-size:.78rem;color:#444;line-height:1.6;">"<?= e(substr($inquiry['message'],0,200)) ?><?= strlen($inquiry['message'])>200?'…':'' ?>"</p>
                                </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:4px;">
                                <?php if ($inquiry['status']==='pending' && $inquiry['book_status']==='available'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="accept">
                                        <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                        <input type="hidden" name="book_id" value="<?= $inquiry['book_id'] ?>">
                                        <button type="submit" class="btn-accept" onclick="return confirm('Accept this inquiry? Other pending inquiries for this book will be rejected.')">
                                            ✓ Accept
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="inquiry_id" value="<?= $inquiry['id'] ?>">
                                        <input type="hidden" name="book_id" value="<?= $inquiry['book_id'] ?>">
                                        <button type="submit" class="btn-reject" onclick="return confirm('Reject this inquiry?')">
                                            ✕ Reject
                                        </button>
                                    </form>

                                <?php elseif ($inquiry['status']==='accepted'): ?>
                                    <?php
                                        $conv_stmt = $pdo->prepare("SELECT id FROM conversations WHERE inquiry_id = ?");
                                        $conv_stmt->execute([$inquiry['id']]);
                                        $conv = $conv_stmt->fetch();
                                    ?>
                                    <?php if ($conv): ?>
                                        <a href="chat.php?conversation_id=<?= $conv['id'] ?>" class="btn-chat">
                                            💬 Go to Chat
                                        </a>
                                    <?php endif; ?>

                                <?php elseif ($inquiry['status']==='rejected'): ?>
                                    <span class="mono" style="font-size:.75rem;color:#aaa;">No further action needed.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>