<?php 
require_once 'functions.php'; 
?>
<!DOCTYPE html>
<html lang="<?= isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paicafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-800">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a class="text-xl font-bold text-orange-600" href="/home.php">Paicafe</a>
            <div>
                <a href="/home.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Home</a>
                <a href="/menu.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Menu</a>
                <?php if (is_user_logged_in()): ?>
                    <a href="/profile.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Profile</a>
                    <a href="/logout.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Logout</a>
                <?php else: ?>
                    <a href="/login.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Login</a>
                    <a href="/register.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Register</a>
                <?php endif; ?>
                 <a href="/cart.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">
                    Cart (<span id="cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>)
                 </a>
            </div>
        </div>
    </nav>
    <main class="container mx-auto px-6 py-8">
