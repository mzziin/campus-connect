<?php
// Entry Point - Campus Connect
// Redirects based on authentication status

require_once __DIR__ . '/includes/auth.php';

if (is_admin()) {
    // Admin is logged in
    header('Location: admin/index.php');
    exit;
} elseif (is_logged_in()) {
    if (is_approved()) {
        // User is logged in and approved
        header('Location: pages/home.php');
        exit;
    } else {
        // User is logged in but pending approval
        header('Location: pages/pending.php');
        exit;
    }
} else {
    // Not logged in
    header('Location: pages/login.php');
    exit;
}
