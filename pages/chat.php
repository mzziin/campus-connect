<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_approved();

$pdo             = get_db();
$user_id         = $_SESSION['user_id'];
$conversation_id = $_GET['conversation_id'] ?? null;

if (!$conversation_id) { flash('error', 'Conversation not found.'); redirect('dashboard.php'); }

$stmt = $pdo->prepare("SELECT cv.*, b.title AS book_title, b.id AS book_id, b.price AS book_price,
       seller.full_name AS seller_name, buyer.full_name AS buyer_name
FROM conversations cv
JOIN books b ON cv.book_id = b.id
JOIN users seller ON cv.seller_id = seller.id
JOIN users buyer ON cv.buyer_id = buyer.id
WHERE cv.id = ?");
$stmt->execute([$conversation_id]);
$conversation = $stmt->fetch();

if (!$conversation || ($conversation['seller_id'] != $user_id && $conversation['buyer_id'] != $user_id)) {
    flash('error', 'Access denied.');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_message') {
        $message = trim($_POST['message'] ?? '');
        if (empty($message)) {
            flash('error', 'Message cannot be empty.');
        } elseif ($conversation['status'] === 'completed') {
            flash('error', 'Cannot send messages in a completed conversation.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)");
            $stmt->execute([$conversation_id, $user_id, $message]);
        }
        redirect('chat.php?conversation_id=' . $conversation_id);
    }

    if ($action === 'complete' && $user_id == $conversation['seller_id'] && $conversation['status'] === 'active') {
        $stmt = $pdo->prepare("SELECT id FROM transactions WHERE conversation_id = ?");
        $stmt->execute([$conversation_id]);
        if (!$stmt->fetch()) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO transactions (conversation_id, book_id, seller_id, buyer_id, rating, feedback, status) VALUES (?, ?, ?, ?, NULL, NULL, 'completed')");
                $stmt->execute([$conversation_id, $conversation['book_id'], $conversation['seller_id'], $conversation['buyer_id']]);
                $pdo->prepare("UPDATE books SET status='sold' WHERE id=?")->execute([$conversation['book_id']]);
                $pdo->prepare("UPDATE conversations SET status='completed' WHERE id=?")->execute([$conversation_id]);
                $pdo->commit();
                flash('success', 'Transaction marked complete. The buyer can now leave a review.');
            } catch (Exception $e) {
                $pdo->rollBack();
                flash('error', 'Something went wrong.');
            }
        }
        redirect('chat.php?conversation_id=' . $conversation_id);
    }
}

$stmt = $pdo->prepare("SELECT m.*, u.full_name AS sender_name
FROM messages m JOIN users u ON m.sender_id = u.id
WHERE m.conversation_id = ? ORDER BY m.sent_at ASC");
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll();

$other_name = $conversation['seller_id'] == $user_id ? $conversation['buyer_name'] : $conversation['seller_name'];

// Get all user's conversations for the sidebar
$sidebar_stmt = $pdo->prepare("SELECT cv.id, cv.status, cv.seller_id, cv.buyer_id,
    b.title AS book_title, b.price AS book_price,
    seller.full_name AS seller_name, buyer.full_name AS buyer_name,
    (SELECT body FROM messages WHERE conversation_id=cv.id ORDER BY sent_at DESC LIMIT 1) AS last_msg,
    (SELECT sent_at FROM messages WHERE conversation_id=cv.id ORDER BY sent_at DESC LIMIT 1) AS last_msg_time
FROM conversations cv
JOIN books b ON cv.book_id = b.id
JOIN users seller ON cv.seller_id = seller.id
JOIN users buyer ON cv.buyer_id = buyer.id
WHERE (cv.seller_id = ? OR cv.buyer_id = ?)
ORDER BY last_msg_time DESC");
$sidebar_stmt->execute([$user_id, $user_id]);
$sidebar_convs = $sidebar_stmt->fetchAll();

$page_title = 'Messages — Campus Connect';
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

        .chat-wrap{display:grid;grid-template-columns:300px 1fr;gap:0;border:2px solid var(--black);background:#fff;height:calc(100vh - 200px);min-height:500px;}

        /* Sidebar */
        .sidebar{border-right:2px solid var(--black);display:flex;flex-direction:column;overflow:hidden;}
        .sidebar-head{padding:16px;border-bottom:2px solid var(--black);display:flex;align-items:center;justify-content:space-between;}
        .conv-item{padding:14px 16px;border-bottom:1px solid #eee;cursor:pointer;text-decoration:none;color:var(--black);display:block;transition:background .12s;}
        .conv-item:hover{background:#f9f8f5;}
        .conv-item.active{background:var(--yellow);}
        .conv-list{overflow-y:auto;flex:1;}

        /* Chat area */
        .chat-area{display:flex;flex-direction:column;overflow:hidden;}
        .chat-head{padding:14px 20px;border-bottom:2px solid var(--black);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fff;}
        .messages-pane{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:14px;background:var(--off-white);}

        /* Bubbles */
        .bubble-me{align-self:flex-end;max-width:70%;}
        .bubble-other{align-self:flex-start;max-width:70%;}
        .bubble-me .bubble-body{background:var(--black);color:#fff;padding:12px 16px;font-family:'Space Mono',monospace;font-size:.78rem;line-height:1.6;}
        .bubble-other .bubble-body{background:var(--yellow);color:var(--black);padding:12px 16px;font-family:'Space Mono',monospace;font-size:.78rem;line-height:1.6;}
        .bubble-time{font-family:'Space Mono',monospace;font-size:.65rem;color:#aaa;margin-top:4px;}
        .bubble-me .bubble-time{text-align:right;}

        /* Input area */
        .chat-input-area{padding:14px 16px;border-top:2px solid var(--black);display:flex;gap:10px;align-items:flex-end;background:#fff;flex-shrink:0;}
        .msg-input{flex:1;border:2px solid var(--black);background:var(--off-white);padding:10px 14px;font-family:'Space Mono',monospace;font-size:.8rem;outline:none;resize:none;min-height:44px;max-height:120px;transition:box-shadow .15s;}
        .msg-input:focus{box-shadow:3px 3px 0 var(--black);}
        .msg-input::placeholder{color:#aaa;}

        .btn-send{background:var(--red);color:#fff;font-weight:900;font-size:.85rem;letter-spacing:.1em;text-transform:uppercase;padding:10px 20px;border:2px solid var(--black);box-shadow:3px 3px 0 var(--black);cursor:pointer;white-space:nowrap;transition:transform .1s,box-shadow .1s;}
        .btn-send:hover{transform:translate(-1px,-1px);box-shadow:4px 4px 0 var(--black);}

        .flash-success{background:#f0fdf4;border:2px solid #16a34a;color:#15803d;padding:12px 16px;font-family:'Space Mono',monospace;font-size:.8rem;}
        .flash-error{background:#fef2f2;border:2px solid var(--red);color:var(--red);padding:12px 16px;font-family:'Space Mono',monospace;font-size:.8rem;}

        .book-chip{display:flex;align-items:center;gap:8px;background:var(--off-white);border:1.5px solid var(--black);padding:6px 12px;font-size:.78rem;font-weight:700;text-decoration:none;color:var(--black);max-width:240px;}
        .book-chip:hover{background:#e5e3de;}

        .online-dot{width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block;margin-right:4px;}

        .btn-complete{width:100%;background:#fff;color:var(--black);font-weight:700;font-size:.85rem;letter-spacing:.1em;text-transform:uppercase;padding:10px;border:2px solid var(--black);cursor:pointer;transition:background .12s;}
        .btn-complete:hover{background:var(--yellow);}

        @media(max-width:640px){
            .chat-wrap{grid-template-columns:1fr;height:auto;}
            .sidebar{display:none;}
        }
    </style>
</head>
<body>
<?php require_once '../includes/header.php'; ?>

    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="mb-4 <?= $flash['type']==='success'?'flash-success':'flash-error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <h1 style="font-weight:900;font-size:1.8rem;text-transform:uppercase;">Messages</h1>
        <a href="dashboard.php" style="font-family:'Space Mono',monospace;font-size:.75rem;color:#888;text-decoration:none;">← Back to Dashboard</a>
    </div>

    <div class="chat-wrap">

        <!-- ── SIDEBAR ── -->
        <div class="sidebar">
            <div class="sidebar-head">
                <span style="font-weight:900;font-size:1.1rem;text-transform:uppercase;">Messages</span>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </div>
            <div class="conv-list">
                <?php foreach ($sidebar_convs as $sc): ?>
                    <?php $sc_other = $sc['seller_id']==$user_id ? $sc['buyer_name'] : $sc['seller_name']; ?>
                    <a href="chat.php?conversation_id=<?= $sc['id'] ?>" class="conv-item <?= $sc['id']==$conversation_id?'active':'' ?>">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:50%;background:<?= $sc['id']==$conversation_id?'var(--black)':'#eee' ?>;border:1.5px solid var(--black);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.85rem;color:<?= $sc['id']==$conversation_id?'var(--yellow)':'var(--black)' ?>;flex-shrink:0;">
                                <?= strtoupper(substr($sc_other,0,1)) ?>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                                    <span style="font-weight:700;font-size:.9rem;"><?= e($sc_other) ?></span>
                                    <?php if ($sc['last_msg_time']): ?>
                                        <span class="mono" style="font-size:.62rem;color:#888;flex-shrink:0;"><?= time_ago($sc['last_msg_time']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:.78rem;color:#666;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                                    <?= $sc['last_msg'] ? e(substr($sc['last_msg'],0,40)) : '<em style="color:#aaa;">No messages yet</em>' ?>
                                </div>
                                <?php if ($sc['book_title']): ?>
                                    <div style="margin-top:4px;">
                                        <span style="font-size:.65rem;font-weight:700;background:#eee;padding:2px 6px;border:1px solid #ccc;font-family:'Space Mono',monospace;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;display:inline-block;max-width:160px;"><?= e(substr($sc['book_title'],0,20)) ?>...</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($sidebar_convs)): ?>
                    <div style="padding:32px;text-align:center;color:#aaa;font-size:.9rem;">No conversations yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── CHAT AREA ── -->
        <div class="chat-area">

            <!-- Header -->
            <div class="chat-head">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:50%;background:var(--yellow);border:2px solid var(--black);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1rem;">
                        <?= strtoupper(substr($other_name,0,1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:1rem;"><?= e($other_name) ?></div>
                        <div class="mono" style="font-size:.65rem;color:#888;">
                            <?php if ($conversation['status']==='active'): ?>
                                <span class="online-dot"></span>Active Now
                            <?php else: ?>
                                <span style="color:#aaa;">Conversation Closed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:10px;">
                    <!-- Book chip -->
                    <a href="book_detail.php?id=<?= $conversation['book_id'] ?>" class="book-chip">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <div style="min-width:0;">
                            <div class="mono" style="font-size:.58rem;text-transform:uppercase;letter-spacing:.06em;color:#888;">Discussing</div>
                            <div style="font-size:.8rem;font-weight:700;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:140px;"><?= e($conversation['book_title']) ?></div>
                            <div style="font-weight:900;color:var(--red);font-size:.85rem;"><?= $conversation['book_price'] ? '₹'.number_format($conversation['book_price'],2) : 'Free' ?></div>
                        </div>
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            <!-- Messages -->
            <div class="messages-pane" id="messages-pane">
                <?php if (empty($messages)): ?>
                    <div style="text-align:center;color:#aaa;padding:40px;font-size:.9rem;font-weight:600;text-transform:uppercase;">No messages yet. Say hello!</div>
                <?php else: ?>
                    <!-- Date separator -->
                    <div style="text-align:center;"><span class="mono" style="font-size:.65rem;background:#fff;border:1px solid #ddd;padding:4px 12px;color:#888;">Today</span></div>

                    <?php foreach ($messages as $msg): ?>
                        <div class="<?= $msg['sender_id']==$user_id?'bubble-me':'bubble-other' ?>">
                            <div class="bubble-body"><?= e($msg['body']) ?></div>
                            <div class="bubble-time"><?= date('g:i A', strtotime($msg['sent_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Input / Complete -->
            <?php if ($conversation['status']==='completed'): ?>
                <div style="padding:16px 20px;border-top:2px solid var(--black);background:#f9f8f5;text-align:center;">
                    <span class="mono" style="font-size:.78rem;color:#888;">This conversation has been completed.</span>
                </div>
            <?php else: ?>
                <div class="chat-input-area">
                    <!-- Attach icon placeholder -->
                    <button type="button" style="background:none;border:none;cursor:pointer;color:#aaa;padding:8px;flex-shrink:0;">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <form method="POST" action="" style="display:flex;gap:10px;flex:1;align-items:flex-end;">
                        <input type="hidden" name="action" value="send_message">
                        <textarea name="message" id="msg-input" class="msg-input" rows="1" placeholder="Type a message..." required
                                  onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.closest('form').submit();}"></textarea>
                        <button type="submit" class="btn-send">Send ➤</button>
                    </form>
                </div>

                <!-- Complete button (seller only) -->
                <?php if ($user_id==$conversation['seller_id']): ?>
                    <div style="padding:10px 16px;border-top:1px solid #eee;background:#fafafa;">
                        <form method="POST">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="btn-complete" onclick="return confirm('Mark this transaction as complete?')">
                                ✓ Mark Transaction as Complete
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<?php require_once '../includes/footer.php'; ?>
<script>
    // Auto-scroll to bottom
    const pane = document.getElementById('messages-pane');
    if (pane) pane.scrollTop = pane.scrollHeight;

    // Auto-resize textarea
    const ta = document.getElementById('msg-input');
    if (ta) {
        ta.addEventListener('input', () => {
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
        });
    }
</script>
</body>
</html>