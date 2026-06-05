<?php 
// FIX: The database connection must be included here for the cart count logic to work.
require_once 'db_connect.php';
require_once 'functions.php'; 

// --- Calculate Total Cart Quantity ---
$total_cart_items = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item_id => $quantity) {
        if (strpos($item_id, 'combo_') === 0) {
            $combo_id = substr($item_id, 6);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM combo_products WHERE combo_id = ?");
            $stmt->execute([$combo_id]);
            $products_in_combo = $stmt->fetchColumn();
            $total_cart_items += $products_in_combo * $quantity;
        } else {
            $total_cart_items += $quantity;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paicafe - Halal Coffee & Meals in Yangon | QR Menu Ordering</title>
    <link rel="icon" type="image/png" href="/assets/public_photo/coffee.png" />
    <meta name="description" content="Scan QR for easy ordering at Paicafe. Enjoy fresh halal food, loyalty rewards, and daily specials. Open 9AM-6PM.">
    <meta name="keywords" content="halal cafe Yangon, QR menu Myanmar, coffee shop Thuwunna">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        window.addEventListener('load', function() {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => { loader.style.display = 'none'; }, 500);
            }
        });
    </script>
    
    <script type="application/ld+json">
                        {
                      "@context": "https://schema.org",
                      "@type": "Restaurant",
                      "name": "Paicafe",
              "address": {
                                    "@type": "PostalAddress",
                        "streetAddress": "No.88, Tantumar Main Street, Thuwunna Tsp",
                        "addressLocality": "Yangon",
                        "addressCountry": "MM"
                      },
                      "telephone": "+95 9 8 9 0 9 0 7 7 2 4",
                      "url": "https://paicafe.online",
                      "servesCuisine": "Halal, Cafe"
                    }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen <?= ($_SESSION['lang'] ?? 'en') === 'mm' ? 'lang-mm' : '' ?>">
    <div id="page-loader">
        <div class="spinner"></div>
    </div>
    
    <nav class="bg-white shadow-md hidden md:block"> 
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            
            <a class="flex items-center" href="/home.php">
                <img src="/assets/uploads/pai.jpg" alt="Paicafe Logo" class="h-10 mr-2">
                <span class="text-xl font-bold text-orange-600">Paicafe</span>
            </a>
            
            <div class="flex items-center space-x-4">
                <a href="/home.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Home</a>
                <a href="/menu.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Menu</a>
                <a href="/rewards.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Rewards</a>
                <?php if (is_user_logged_in()): ?>
                    <a href="/profile.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Profile</a>
                    <a href="/logout.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Logout</a>
                <?php else: ?>
                    <a href="/login.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Login</a>
                    <a href="/register.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">Register</a>
                <?php endif; ?>
                 <a href="/cart.php" class="px-3 py-2 text-gray-700 hover:text-orange-600">
                    Cart (<span id="cart-count"><?= $total_cart_items ?></span>)
                 </a>
                 
                 <div class="border-l pl-4 flex items-center space-x-2 text-green-600">
                   <img width="50" height="50" src="https://img.icons8.com/ios/50/EA580C/halal-sign.png" alt="halal-sign"/>
                 </div>
            </div>
        </div>
    </nav>
    
    <!--Mobile floating bar-->
<div class="md:hidden fixed bottom-0 left-0 right-0 mobile-floating-bar z-50 bg-white border-t border-gray-200">
  <div class="flex justify-around items-center h-16">
    <a href="/home.php" class="flex flex-col items-center justify-center text-gray-600 hover:text-orange-400 <?php echo ($current_page == 'home') ? 'active' : ''; ?>" aria-label="Home">
      <i class="fas fa-home text-lg"></i>
      <span class="text-xs mt-1 hidden sm:block">Home</span>
    </a>
    <a href="/menu.php" class="flex flex-col items-center justify-center text-gray-600 hover:text-orange-400 <?php echo ($current_page == 'menu') ? 'active' : ''; ?>" aria-label="Menu">
      <i class="fas fa-utensils text-lg"></i>
      <span class="text-xs mt-1 hidden sm:block">Menu</span>
    </a>
    <a href="/cart.php" class="cart-link flex flex-col items-center justify-center text-gray-600 hover:text-orange-400 <?php echo ($current_page == 'cart') ? 'active' : ''; ?>" aria-label="Cart" id="mobile-cart-link">
        <div class="relative">
            <i class="fas fa-shopping-cart text-lg"></i>
            <?php if ($total_cart_items > 0): ?>
            <span id="mobile-cart-count" class="absolute -top-2 -right-3 bg-red-600 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center font-bold">
              <?= $total_cart_items ?>
            </span>
            <?php endif; ?>
        </div>
        <span class="text-xs mt-1 hidden sm:block">Cart</span>
    </a>
    <?php if (is_user_logged_in()): ?>
      <a href="/profile.php" class="flex flex-col items-center justify-center text-gray-600 hover:text-orange-400 <?php echo ($current_page == 'profile') ? 'active' : ''; ?>" aria-label="Profile">
        <i class="fas fa-user text-lg"></i>
        <span class="text-xs mt-1 hidden sm:block">Profile</span>
      </a>
    <?php else: ?>
      <a href="/login.php" class="flex flex-col items-center justify-center text-gray-600 hover:text-orange-400 <?php echo ($current_page == 'login') ? 'active' : ''; ?>" aria-label="Login">
        <i class="fas fa-sign-in-alt text-lg"></i>
        <span class="text-xs mt-1 hidden sm:block">Login</span>
      </a>
    <?php endif; ?>
    <a href="/user_guide.php" class="flex flex-col items-center justify-center text-gray-600 hover:text-orange-400 <?php echo ($current_page == 'user_guide') ? 'active' : ''; ?>" aria-label="User Guide">
      <i class="fas fa-book text-lg"></i>
      <span class="text-xs mt-1 hidden sm:block">User Guide</span>
    </a>
  </div>
</div>

    <main class="container mx-auto px-6 py-8 flex-grow">