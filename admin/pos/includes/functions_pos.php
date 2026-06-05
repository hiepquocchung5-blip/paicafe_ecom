<?php
/**
 * POS Portal - Local Functions
 * Contains only the functions needed for the POS portal to run.
 */

// Simple HTML escaping
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function format_currency($amount, $currency = 'Ks') {
    return number_format($amount, 0) . ' ' . $currency;
}

// --- Admin Authentication ---
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: login.php'); // Redirect to local login
        exit();
    }
}

// --- Admin Helper Functions ---
function get_setting($pdo, $key, $default = null) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn() ?? $default;
}

