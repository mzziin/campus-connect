<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

redirect_if_admin_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username'         => $_POST['username'] ?? '',
        'email'            => $_POST['email'] ?? '',
        'password'         => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
    ];

    $result = register_admin($data);

    if ($result['success']) {
        flash('success', 'Admin account created successfully. Please login.');
        redirect('admin/login.php');
    } else {
        flash('error', $result['error']);
    }
}

$page_title = 'Register — Campus Connect';
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
        .mono { font-family: 'Space Mono', monospace; }
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
            width: 100%;
            background: var(--yellow);
            color: var(--black);
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 900;
            font-size: 1.1rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 14px 24px;
            border: 2px solid var(--black);
            box-shadow: 4px 4px 0 var(--black);
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.1s;
            display: block;
            text-align: center;
            text-decoration: none;
        }
        .btn-primary:hover { transform: translate(-2px,-2px); box-shadow: 6px 6px 0 var(--black); }
        .btn-primary:active { transform: translate(2px,2px); box-shadow: 2px 2px 0 var(--black); }

        .flash-success { background:#f0fdf4; border:2px solid #16a34a; color:#15803d; padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.8rem; }
        .flash-error   { background:#fef2f2; border:2px solid var(--red); color:var(--red); padding:12px 16px; font-family:'Space Mono',monospace; font-size:0.8rem; }

        .left-panel {
            background: var(--red);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100%;
        }

        .tag-badge {
            background: var(--yellow);
            color: var(--black);
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 8px 16px;
            border: 2px solid var(--black);
            display: inline-block;
            transform: rotate(-1.5deg);
        }
        .tag-badge:nth-child(2) { transform: rotate(1deg); }

        select.input-field { appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23111' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
    </style>
</head>
<body>

<div class="min-h-screen flex items-stretch">

    <!-- Left Panel -->
    <div class="hidden lg:flex lg:w-5/12 left-panel flex-col">
        <!-- Top: Logo -->
        <div>
            <div class="flex items-center gap-2 mb-12">
                <div style="background:#fff; width:36px; height:36px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:1.2rem; color:var(--red);">C</div>
                <span style="font-weight:900; font-size:1rem; letter-spacing:0.12em; text-transform:uppercase; color:#fff;">Campus Connect</span>
            </div>

            <div style="font-weight:900; font-size:3.8rem; line-height:0.95; text-transform:uppercase; color:#fff; margin-bottom:24px;">
                THE<br>STUDENT<br>ZINE IS<br>CALLING.
            </div>

            <p style="font-family:'Space Mono',monospace; font-size:0.85rem; color:rgba(255,255,255,0.8); line-height:1.7; max-width:280px; margin-bottom:32px;">
                Stop overpaying for textbooks. Start sharing the campus knowledge.
            </p>

            <div class="flex flex-col gap-3">
                <div class="tag-badge">100% Student Verified</div>
                <div class="tag-badge">Join 5000+ Students</div>
            </div>
        </div>

        <!-- Bottom: Decorative text -->
        <div style="margin-top:auto; padding-top:40px;">
            <div style="border-top:2px solid rgba(255,255,255,0.2); padding-top:16px; font-family:'Space Mono',monospace; font-size:0.7rem; color:rgba(255,255,255,0.4); letter-spacing:0.08em; text-transform:uppercase;">
                Authenticity Guaranteed — For Students By Students — Since 2024
            </div>
        </div>
    </div>

    <!-- Right Panel: Form -->
    <div class="flex-1 flex items-center justify-center px-6 py-10" style="background:var(--off-white);">
        <div style="width:100%; max-width:480px;">

            <!-- Mobile logo -->
            <div class="lg:hidden flex items-center gap-2 mb-8">
                <div style="background:var(--red); width:32px; height:32px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:1rem; color:#fff;">C</div>
                <span style="font-weight:900; font-size:1rem; letter-spacing:0.1em; text-transform:uppercase; color:var(--black);">Campus Connect</span>
            </div>

            <!-- Header -->
            <div style="margin-bottom:32px;">
                <div style="font-family:'Space Mono',monospace; font-size:0.7rem; letter-spacing:0.15em; text-transform:uppercase; color:#888; margin-bottom:8px;">Issue No. 04 // Join The Collective</div>
                <h1 style="font-weight:900; font-size:2.4rem; text-transform:uppercase; line-height:1; color:var(--black); margin-bottom:4px;">Initialize Profile</h1>
                <div style="width:40px; height:3px; background:var(--red); margin-bottom:12px;"></div>
                <p style="font-family:'Space Mono',monospace; font-size:0.78rem; color:#666; line-height:1.6;">Create your credentials to access the internal campus exchange network.</p>
            </div>

            <!-- Flash -->
            <?php $flash = get_flash(); ?>
            <?php if ($flash): ?>
                <div class="<?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?> mb-6">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="label">Username</label>
                    <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required
                           class="input-field" placeholder="admin">
                </div>

                <div class="mb-4">
                    <label class="label">Email</label>
                    <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required
                           class="input-field" placeholder="admin@campus.edu">
                </div>

                <div class="mb-6">
                    <label class="label">Password</label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="password" required minlength="8"
                               class="input-field" placeholder="••••••••" style="padding-right:44px;">
                        <button type="button" onclick="togglePass('password', this)" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#888;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5,12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="label">Confirm Password</label>
                    <input type="password" name="confirm_password" required
                           class="input-field" placeholder="••••••••">
                </div>

                <button type="submit" class="btn-primary mb-4">Create Admin Account</button>

                <p style="font-family:'Space Mono',monospace; font-size:0.75rem; color:#666; text-align:center;">
                    Already have an admin account? <a href="login.php" style="color:var(--black); font-weight:700; text-decoration:underline;">Login here</a>
                </p>
            </form>

            <!-- Trust badges -->
            <div style="margin-top:32px; padding-top:20px; border-top:1px solid #ddd; display:flex; gap:20px; align-items:center;">
                <div style="display:flex; align-items:center; gap:6px; font-family:'Space Mono',monospace; font-size:0.65rem; color:#888; letter-spacing:0.05em; text-transform:uppercase;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#16a34a;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Secure Enrollment
                </div>
                <div style="display:flex; align-items:center; gap:6px; font-family:'Space Mono',monospace; font-size:0.65rem; color:#888; letter-spacing:0.05em; text-transform:uppercase;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#2563eb;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Edu-Verified Only
                </div>
            </div>

            <!-- Est. stamp -->
            <div style="margin-top:24px; text-align:right;">
                <div style="display:inline-block; border:2px solid var(--black); padding:6px 12px; transform:rotate(1deg);">
                    <span style="font-family:'Space Mono',monospace; font-size:0.65rem; letter-spacing:0.1em; text-transform:uppercase; color:var(--black);">Est. 2024 — Vol. 1</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>