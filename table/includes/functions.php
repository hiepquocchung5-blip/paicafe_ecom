<?php
// Start session on all pages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if a user is logged in
function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check if an admin is logged in
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Redirect if not logged in
function require_login() {
    if (!is_user_logged_in()) {
        header('Location: /login.php');
        exit();
    }
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit();
    }
}

// Sanitize output to prevent XSS
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Language Handling (Simplified)
// In a real app, you would load this from files
$translations = [
    'en' => [
        'welcome' => 'Welcome to Paicafe',
        'menu' => 'Menu',
        'home' => 'Home',
        'login' => 'Login',
        'logout' => 'Logout',
        'register' => 'Register',
        'profile' => 'Profile'
    ],
    'mm' => [
        'welcome' => 'Paicafe မှ ကြိုဆိုပါတယ်',
        'menu' => 'မီနူး',
        'home' => 'ပင်မစာမျက်နှာ',
        'login' => 'လော့ဂ်အင်',
        'logout' => 'ထွက်ရန်',
        'register' => 'မှတ်ပုံတင်ပါ',
        'profile' => 'ကိုယ်ရေးအကျဉ်း'
    ]
];

function lang($key) {
    global $translations;
    $lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
    return $translations[$lang][$key] ?? $key;
}
?>