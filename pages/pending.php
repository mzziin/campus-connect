<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

require_login();

// Check if user's status has been updated in database
$pdo = get_db();
$stmt = $pdo->prepare("SELECT account_status FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user && $user['account_status'] === 'approved') {
    // Update session status
    $_SESSION['user_status'] = 'approved';
    flash('success', 'Your account has been approved! Welcome to Campus Connect.');
    redirect('home.php');
} elseif ($user && $user['account_status'] === 'rejected') {
    // Update session status
    $_SESSION['user_status'] = 'rejected';
    flash('error', 'Your account has been rejected by the admin.');
    redirect('login.php');
}

$page_title = 'Pending Approval — Campus Connect';
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

        .btn-ghost {
            background: transparent;
            color: var(--black);
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 8px 20px;
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
            box-shadow: 6px 6px 0 var(--black);
        }

        .page-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 900;
            font-size: 2.5rem;
            text-transform: uppercase;
            letter-spacing: -0.01em;
            line-height: 1;
        }

        .flash-info { background:#eff6ff; border:2px solid #3b82f6; color:#1d4ed8; padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.8rem; }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>

    <main class="max-w-2xl mx-auto px-4 py-12">
        <?php $flash = get_flash(); ?>
        <?php if ($flash): ?>
            <div class="<?= $flash['type'] === 'success' ? 'flash-success' : 'flash-info' ?> mb-6">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="card p-10 text-center">
            <div class="mb-8">
                <div style="width:80px; height:80px; margin:0 auto; background:var(--yellow); border:2px solid var(--black); display:flex; align-items:center; justify-content:center; transform:rotate(3deg);">
                    <svg style="width:40px; height:40px; color:var(--black);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            <h1 class="page-title mb-3">Account Pending Approval</h1>
            <div style="width:40px; height:3px; background:var(--red); margin:0 auto 20px;"></div>

            <p style="font-family:'Space Mono',monospace; font-size:0.9rem; color:#666; line-height:1.7; margin-bottom:32px;">
                Your account is waiting for admin approval. Please check back later.
            </p>

            <p style="font-family:'Space Mono',monospace; font-size:0.8rem; color:#888; line-height:1.6; margin-bottom:40px;">
                You will be able to access all features once your account is approved by an administrator.
            </p>

            <div class="flex justify-center gap-4">
                <a href="pending.php" class="btn-primary">Refresh Status</a>
                <a href="logout.php" class="btn-ghost">Logout</a>
            </div>
        </div>
    </main>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>
