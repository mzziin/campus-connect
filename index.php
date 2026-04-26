<?php
// Entry Point - Campus Connect
// Redirects based on authentication status

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (is_admin()) {
    // Admin is logged in
    redirect('admin/index.php');
} elseif (is_logged_in()) {
    if (is_approved()) {
        // User is logged in and approved
        redirect('pages/home.php');
    } else {
        // User is logged in but pending approval
        redirect('pages/pending.php');
    }
} else {
    // Not logged in
    redirect('pages/login.php');
}
