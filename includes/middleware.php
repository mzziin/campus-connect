<?php
// Middleware - Route Protection Guards
// Campus Connect

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

/**
 * Require user to be logged in
 * Redirects to login page if not authenticated
 */
function require_login() {
    if (!is_logged_in()) {
        flash('error', 'Please login to access this page.');
        header('Location: pages/login.php');
        exit;
    }
}

/**
 * Require user to be approved
 * Redirects to pending page if account is not approved
 */
function require_approved() {
    require_login();
    if (!is_approved()) {
        header('Location: pages/pending.php');
        exit;
    }
}

/**
 * Require admin access
 * Redirects to admin login if not authenticated as admin
 */
function require_admin() {
    if (!is_admin()) {
        flash('error', 'Admin access required.');
        header('Location: admin/login.php');
        exit;
    }
}

/**
 * Redirect if already logged in
 * Used on login/register pages
 */
function redirect_if_logged_in() {
    if (is_logged_in()) {
        if (is_approved()) {
            header('Location: pages/home.php');
        } else {
            header('Location: pages/pending.php');
        }
        exit;
    }
}

/**
 * Redirect if already logged in as admin
 * Used on admin login page
 */
function redirect_if_admin_logged_in() {
    if (is_admin()) {
        header('Location: admin/index.php');
        exit;
    }
}
