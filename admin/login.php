<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

redirect_if_admin_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = login_admin($email, $password);

    if ($result['success']) {
        flash('success', 'Welcome, Admin!');
        redirect('index.php');
    } else {
        flash('error', $result['error']);
    }
}

$page_title = 'Login — Campus Connect';
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
            padding: 12px 14px;
            font-family: 'Space Mono', monospace;
            font-size: 0.8rem;
            outline: none;
            transition: box-shadow 0.15s;
            color: var(--black);
        }
        .input-field:focus { box-shadow: 3px 3px 0 var(--black); }
        .input-field::placeholder { color: #bbb; text-transform: uppercase; letter-spacing: 0.05em; }

        .label {
            display: block;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 6px;
            color: var(--black);
        }

        .btn-login {
            width: 100%;
            background: var(--black);
            color: #fff;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 900;
            font-size: 1.15rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 14px 24px;
            border: 2px solid var(--black);
            box-shadow: 4px 4px 0 var(--red);
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.1s;
        }
        .btn-login:hover { transform: translate(-2px,-2px); box-shadow: 6px 6px 0 var(--red); }
        .btn-login:active { transform: translate(2px,2px); box-shadow: 2px 2px 0 var(--red); }

        .flash-success { background:#f0fdf4; border:2px solid #16a34a; color:#15803d; padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.78rem; }
        .flash-error   { background:#fef2f2; border:2px solid var(--red); color:var(--red); padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.78rem; }
        .flash-info    { background:#eff6ff; border:2px solid #3b82f6; color:#1d4ed8; padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.78rem; }
    </style>
</head>
<body>

<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div style="width:100%; max-width:440px;">

        <!-- Logo -->
        <div class="flex items-center gap-2 mb-10 justify-center">
            <div style="background:var(--red); width:36px; height:36px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:1.2rem; color:#fff; border:2px solid var(--black);">C</div>
            <span style="font-weight:900; font-size:1.1rem; letter-spacing:0.12em; text-transform:uppercase; color:var(--black);">Campus Connect</span>
        </div>

        <!-- Card -->
        <div style="background:#fff; border:2px solid var(--black); box-shadow:6px 6px 0 var(--black); padding:40px 36px;">

            <div style="margin-bottom:28px;">
                <h1 style="font-weight:900; font-size:2.2rem; text-transform:uppercase; line-height:1; color:var(--black); margin-bottom:4px;">Admin Portal</h1>
                <div style="width:32px; height:3px; background:var(--red); margin-bottom:10px;"></div>
                <p style="font-family:'Space Mono',monospace; font-size:0.75rem; color:#888;">Sign in to your Admin account.</p>
            </div>

            <!-- Flash -->
            <?php $flash = get_flash(); ?>
            <?php if ($flash): ?>
                <div class="<?= match($flash['type']) { 'success' => 'flash-success', 'error' => 'flash-error', default => 'flash-info' } ?> mb-6">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="label">Email</label>
                    <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required
                           class="input-field" placeholder="your@campus.edu">
                </div>

                <div class="mb-8">
                    <label class="label">Password</label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="password" required
                               class="input-field" placeholder="••••••••" style="padding-right:44px;">
                        <button type="button" onclick="togglePass()" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#888;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login mb-5">Login →</button>

                <p style="font-family:'Space Mono',monospace; font-size:0.75rem; color:#666; text-align:center;">
                    Don't have an account? <a href="register.php" style="color:var(--black); font-weight:700; text-decoration:underline;">Register</a>
                </p>
            </form>
        </div>

        <!-- Admin link -->
        <div style="text-align:center; margin-top:20px;">
            <a href="/campus-connect/pages/login.php" style="font-family:'Space Mono',monospace; font-size:0.7rem; color:#aaa; text-decoration:none; letter-spacing:0.05em;" class="hover:text-gray-600 transition-colors">User Login →</a>
        </div>
    </div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>