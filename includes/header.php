<?php 
// FIX: The database connection must be included here for the cart count logic to work.
require_once 'db_connect.php';
require_once 'functions.php'; 

$current_page = $current_page ?? pathinfo($_SERVER['SCRIPT_NAME'] ?? '', PATHINFO_FILENAME);

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
    <script>
        (function () {
            const storedTheme = localStorage.getItem('paicafe-theme') || localStorage.getItem('darkMode');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const useDark = storedTheme ? (storedTheme === 'dark' || storedTheme === 'true') : prefersDark;
            document.documentElement.classList.toggle('dark', useDark);
            document.documentElement.classList.toggle('light', !useDark);
            document.documentElement.dataset.theme = useDark ? 'dark' : 'light';
        })();
    </script>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
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
<body class="bg-gray-50 dark:bg-slate-950 text-gray-800 dark:text-slate-100 flex flex-col min-h-screen transition-colors duration-300 <?= ($_SESSION['lang'] ?? 'en') === 'mm' ? 'lang-mm' : '' ?>">
    <div id="page-loader">
        <div class="coffee-loader" role="status" aria-label="Loading Paicafe">
            <svg class="coffee-loader__svg" viewBox="0 0 220 180" aria-hidden="true">
                <defs>
                    <linearGradient id="coffeeGradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#b45309" />
                        <stop offset="100%" stop-color="#7c2d12" />
                    </linearGradient>
                    <clipPath id="cupFillClip">
                        <path d="M71 76h82l-9 74H80z" />
                    </clipPath>
                </defs>
                <g class="coffee-loader__pot">
                    <path d="M132 28h48l10 24v34h-42c-13 0-24-11-24-24V36c0-4 3-8 8-8z" fill="#f8fafc" stroke="#ea580c" stroke-width="6" />
                    <path d="M178 52c18 3 29 13 29 28 0 18-15 31-36 31h-13" fill="none" stroke="#ea580c" stroke-width="8" stroke-linecap="round" />
                    <path d="M124 53H99c-10 0-17 7-17 17" fill="none" stroke="#ea580c" stroke-width="8" stroke-linecap="round" />
                    <path d="M143 28l-4-14h36l-4 14" fill="#fed7aa" stroke="#ea580c" stroke-width="5" stroke-linejoin="round" />
                </g>
                <path class="coffee-loader__pour" d="M91 72C86 86 86 98 91 112" fill="none" stroke="url(#coffeeGradient)" stroke-width="9" stroke-linecap="round" />
                <g class="coffee-loader__cup">
                    <path d="M62 70h100l-11 88a12 12 0 0 1-12 10H85a12 12 0 0 1-12-10z" fill="#fff7ed" stroke="#ea580c" stroke-width="7" stroke-linejoin="round" />
                    <path d="M157 91h14c13 0 23 10 23 23s-10 23-23 23h-18" fill="none" stroke="#ea580c" stroke-width="7" stroke-linecap="round" />
                    <g clip-path="url(#cupFillClip)">
                        <rect class="coffee-loader__fill" x="70" y="148" width="85" height="76" fill="url(#coffeeGradient)" />
                    </g>
                    <path d="M72 88c18 8 32-8 50 0 12 5 22 3 34-3" fill="none" stroke="#fdba74" stroke-width="4" stroke-linecap="round" opacity=".8" />
                </g>
                <g class="coffee-loader__steam" fill="none" stroke="#fb923c" stroke-width="5" stroke-linecap="round">
                    <path d="M84 48c-8-10 8-15 0-26" />
                    <path d="M111 45c-8-10 8-15 0-26" />
                    <path d="M138 48c-8-10 8-15 0-26" />
                </g>
            </svg>
            <div class="coffee-loader__brand">PAICAFE</div>
        </div>
    </div>
    
    <nav class="bg-white dark:bg-slate-900 shadow-md hidden md:block border-b border-transparent dark:border-slate-800 transition-colors duration-300"> 
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            
            <a class="flex items-center" href="/home.php">
                <img src="/assets/uploads/pai.jpg" alt="Paicafe Logo" class="h-10 mr-2">
                <span class="text-xl font-bold text-orange-600">Paicafe</span>
            </a>
            
            <div class="flex items-center space-x-4">
                <a href="/home.php" class="px-3 py-2 text-gray-700 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400">Home</a>
                <a href="/menu.php" class="px-3 py-2 text-gray-700 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400">Menu</a>
                <a href="/rewards.php" class="px-3 py-2 text-gray-700 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400">Rewards</a>
                <?php if (is_user_logged_in()): ?>
                    <a href="/profile.php" class="px-3 py-2 text-gray-700 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400">Profile</a>
                    <a href="/logout.php" class="px-3 py-2 text-gray-700 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400">Logout</a>
                <?php else: ?>
                    <a href="/login.php" class="px-3 py-2 text-gray-700 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400">Login</a>
                    <a href="/register.php" class="px-3 py-2 text-gray-700 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400">Register</a>
                <?php endif; ?>
                 <a href="/cart.php" class="px-3 py-2 text-gray-700 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400">
                    Cart (<span id="cart-count"><?= $total_cart_items ?></span>)
                 </a>
                 <button type="button" class="theme-toggle h-10 w-10 rounded-full border border-gray-200 dark:border-slate-700 bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400 transition-colors" aria-label="Toggle color theme" title="Toggle theme">
                    <i class="fas fa-moon theme-icon-moon"></i>
                    <i class="fas fa-sun theme-icon-sun hidden"></i>
                 </button>
                 
                 <div class="border-l pl-4 flex items-center space-x-2 text-green-600">
                   <img width="50" height="50" src="https://img.icons8.com/ios/50/EA580C/halal-sign.png" alt="halal-sign"/>
                 </div>
            </div>
        </div>
    </nav>
    
    <!--Mobile floating bar-->
<div class="md:hidden fixed bottom-0 left-0 right-0 mobile-floating-bar z-50 bg-white dark:bg-slate-900 border-t border-gray-200 dark:border-slate-800 transition-colors duration-300">
  <div class="flex justify-around items-center h-16">
    <a href="/home.php" class="flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'home') ? 'active' : ''; ?>" aria-label="Home">
      <i class="fas fa-home text-lg"></i>
      <span class="text-xs mt-1 hidden sm:block">Home</span>
    </a>
    <a href="/menu.php" class="flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'menu') ? 'active' : ''; ?>" aria-label="Menu">
      <i class="fas fa-utensils text-lg"></i>
      <span class="text-xs mt-1 hidden sm:block">Menu</span>
    </a>
    <a href="/cart.php" class="cart-link flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'cart') ? 'active' : ''; ?>" aria-label="Cart" id="mobile-cart-link">
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
      <a href="/profile.php" class="flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'profile') ? 'active' : ''; ?>" aria-label="Profile">
        <i class="fas fa-user text-lg"></i>
        <span class="text-xs mt-1 hidden sm:block">Profile</span>
      </a>
    <?php else: ?>
      <a href="/login.php" class="flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'login') ? 'active' : ''; ?>" aria-label="Login">
        <i class="fas fa-sign-in-alt text-lg"></i>
        <span class="text-xs mt-1 hidden sm:block">Login</span>
      </a>
    <?php endif; ?>
    <a href="/user_guide.php" class="flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'user_guide') ? 'active' : ''; ?>" aria-label="User Guide">
      <i class="fas fa-book text-lg"></i>
      <span class="text-xs mt-1 hidden sm:block">Guide</span>
    </a>
    <button type="button" class="theme-toggle flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400" aria-label="Toggle color theme">
      <i class="fas fa-moon text-lg theme-icon-moon"></i>
      <i class="fas fa-sun text-lg theme-icon-sun hidden"></i>
      <span class="text-xs mt-1 hidden sm:block">Theme</span>
    </button>
  </div>
</div>

    <main class="container mx-auto px-6 py-8 flex-grow">
