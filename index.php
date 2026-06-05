<?php
// Get the requested URL path
$request_uri = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
$page = trim($request_uri, '/');

// Define the allowed public pages
$allowed_pages = [
    '' => 'home.php', // The homepage
    'menu' => 'menu.php',
    'cart' => 'cart.php',
    'checkout' => 'checkout.php',
    'login' => 'login.php',
    'register' => 'register.php',
    'profile' => 'profile.php',
    'rewards' => 'rewards.php',
    'reserve_table' => 'reserve_table.php',
    'product_details'=> 'product_details.php',
    'order_status'=>'order_status.php',
    '404'=> '404.php'
    // Add other public pages here
];

// Check if the requested page is in our list
if (array_key_exists($page, $allowed_pages)) {
    require $allowed_pages[$page];
} else {
    // If not, show the 404 page
    require '404.php';
}
?>