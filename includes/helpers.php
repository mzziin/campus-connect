<?php
// Helper Functions
// Campus Connect - Utility Functions

/**
 * Set a flash message
 */
function flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    // If URL is relative and doesn't start with / or http, prepend the base path
    if (!str_starts_with($url, '/') && !str_starts_with($url, 'http')) {
        // Remove any leading ../ or ./
        $url = preg_replace('#^\.\.?/#', '', $url);
        $url = '/campus-connect/' . ltrim($url, '/');
    }
    // If URL starts with / but not /campus-connect/, prepend it
    elseif (str_starts_with($url, '/') && !str_starts_with($url, '/campus-connect/')) {
        $url = '/campus-connect' . $url;
    }
    header("Location: $url");
    exit;
}

/**
 * Sanitize output for HTML
 */
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format price
 */
function format_price($price, $type = 'sell') {
    if ($type === 'giveaway') {
        return 'Free';
    }
    if ($price === null || $price == 0) {
        return 'Free';
    }
    return '₹ ' . number_format($price, 2);
}

/**
 * Format date
 */
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format datetime
 */
function format_datetime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

/**
 * Truncate text
 */
function truncate($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Get current URL
 */
function current_url() {
    return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Check if current page matches
 */
function is_current_page($path) {
    return $_SERVER['REQUEST_URI'] === $path;
}

/**
 * Convert datetime to relative time (e.g., "2 hours ago")
 */
function time_ago(string $datetime): string {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes == 1 ? '1 minute ago' : "$minutes minutes ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours == 1 ? '1 hour ago' : "$hours hours ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days == 1 ? '1 day ago' : "$days days ago";
    } else {
        return format_date($datetime);
    }
}

/**
 * Get Tailwind classes for status badge
 */
function get_status_badge_class(string $status): string {
    $classes = [
        'available' => 'bg-green-100 text-green-800',
        'sold' => 'bg-gray-100 text-gray-800',
        'deleted' => 'bg-red-100 text-red-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'accepted' => 'bg-blue-100 text-blue-800',
        'rejected' => 'bg-red-100 text-red-800',
        'active' => 'bg-green-100 text-green-800',
        'completed' => 'bg-gray-100 text-gray-800',
        'pending_approval' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'banned' => 'bg-red-100 text-red-800',
        'reviewed' => 'bg-blue-100 text-blue-800',
        'dismissed' => 'bg-gray-100 text-gray-600',
    ];

    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

/**
 * Get transaction status badge class
 */
function get_transaction_status_badge_class(string $status): string {
    $classes = [
        'in_progress' => 'bg-yellow-100 text-yellow-700',
        'completed' => 'bg-green-100 text-green-700',
    ];

    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

/**
 * Upload book image
 */
function upload_book_image(array $file): array {
    // Check upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error.'];
    }

    // Validate file size (max 2MB)
    $max_size = 2 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size must be less than 2MB.'];
    }

    // Validate MIME type
    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/webp'];
    $mime_type = mime_content_type($file['tmp_name']);
    if (!in_array($mime_type, $allowed_mime_types)) {
        return ['success' => false, 'error' => 'Only JPEG, PNG, and WebP images are allowed.'];
    }

    // Extract extension from MIME type
    $mime_extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $extension = $mime_extensions[$mime_type];

    // Generate unique filename
    $filename = bin2hex(random_bytes(8)) . '.' . $extension;
    $destination = __DIR__ . '/../uploads/books/' . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to save file.'];
    }

    return ['success' => true, 'path' => 'uploads/books/' . $filename];
}
