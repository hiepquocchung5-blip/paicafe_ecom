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
    <?php
    $seo_title = $page_title ?? 'Pai Cafe Yangon | Halal Cafe & Restaurant in Thuwunna';
    $seo_description = $page_description ?? 'Pai Cafe serves specialty coffee and fresh halal meals in Thuwunna, Thingangyun, Yangon. Browse the menu, order online or reserve a table.';
    $seo_keywords = $page_keywords ?? 'Pai Cafe, cafe Yangon, halal restaurant Yangon, coffee shop Thuwunna, cafe Thingangyun';
    $seo_canonical = $page_canonical ?? ('https://paicafes.com' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
    $seo_image = $page_image ?? 'https://paicafes.com/assets/uploads/bgg.png';
    $private_pages = ['login', 'register', 'cart', 'checkout', 'profile', 'order_status', 'contact_developer'];
    $seo_robots = $page_robots ?? (in_array($current_page, $private_pages, true)
        ? 'noindex, nofollow, noarchive'
        : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
    ?>
    <title><?= e($seo_title) ?></title>
    <link rel="icon" type="image/png" href="/assets/public_photo/coffee.png" />
    <meta name="description" content="<?= e($seo_description) ?>">
    <meta name="keywords" content="<?= e($seo_keywords) ?>">
    <meta name="robots" content="<?= e($seo_robots) ?>">
    <meta name="author" content="Pai Cafe & Lounge">
    <meta name="geo.region" content="MM-06">
    <meta name="geo.placename" content="Thuwunna, Thingangyun, Yangon">
    <link rel="canonical" href="<?= e($seo_canonical) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Padauk:wght@400;700&family=Poppins:wght@400;600;700&display=swap">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Pai Cafe & Lounge">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="<?= e($seo_title) ?>">
    <meta property="og:description" content="<?= e($seo_description) ?>">
    <meta property="og:url" content="<?= e($seo_canonical) ?>">
    <meta property="og:image" content="<?= e($seo_image) ?>">
    <meta property="og:image:alt" content="Pai Cafe and Lounge in Thuwunna, Yangon">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($seo_title) ?>">
    <meta name="twitter:description" content="<?= e($seo_description) ?>">
    <meta name="twitter:image" content="<?= e($seo_image) ?>">
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
                      "@id": "https://paicafes.com/#restaurant",
                      "name": "Paicafe",
              "address": {
                                    "@type": "PostalAddress",
                        "streetAddress": "No.88, Tantumar Main Street, Thuwunna Tsp",
                        "addressLocality": "Yangon",
                        "addressCountry": "MM"
                      },
                      "telephone": "+95 9 8 9 0 9 0 7 7 2 4",
                      "url": "https://paicafes.com",
                      "image": "https://paicafes.com/assets/uploads/bgg.png",
                      "priceRange": "$$",
                      "servesCuisine": ["Halal", "Cafe", "Coffee", "Burgers", "Asian"],
                      "acceptsReservations": true,
                      "areaServed": ["Thuwunna", "Thingangyun", "Yangon"],
                      "openingHoursSpecification": [{"@type":"OpeningHoursSpecification","dayOfWeek":["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],"opens":"09:00","closes":"18:00"}],
                      "geo": {"@type":"GeoCoordinates","latitude":16.8175613,"longitude":96.1947021},
                      "hasMap": "https://www.google.com/maps/search/?api=1&query=Pai+Cafe+%26+Lounge+Yangon"
                    }
    </script>
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        '@id' => APP_URL . '/#website',
        'url' => APP_URL . '/',
        'name' => 'Pai Cafe & Lounge',
        'inLanguage' => ['en', 'my'],
        'publisher' => ['@id' => APP_URL . '/#restaurant'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <?php if (!empty($page_schema)): ?>
    <script type="application/ld+json"><?= json_encode($page_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
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
    
    <nav class="cafe-navbar bg-white dark:bg-slate-900 shadow-md hidden md:block border-b border-transparent dark:border-slate-800 transition-colors duration-300">
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
    <a href="/home.php" class="flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'home') ? 'active' : ''; ?>" aria-label="Home" <?= $current_page === 'home' ? 'aria-current="page"' : '' ?>>
      <i class="fas fa-home text-lg"></i>
      <span class="text-xs mt-1">Home</span>
    </a>
    <a href="/menu.php" class="mobile-menu-action flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'menu') ? 'active' : ''; ?>" aria-label="Open menu" <?= $current_page === 'menu' ? 'aria-current="page"' : '' ?>>
      <span class="mobile-menu-action__icon">
        <svg class="cutlery-emblem" viewBox="0 0 64 64" aria-hidden="true">
          <defs><linearGradient id="steel" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fff7ed"/><stop offset=".45" stop-color="#cbd5e1"/><stop offset="1" stop-color="#fff"/></linearGradient><linearGradient id="plate" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fb923c"/><stop offset="1" stop-color="#9a3412"/></linearGradient></defs>
          <circle class="cutlery-plate-shadow" cx="32" cy="34" r="25" fill="#431407" opacity=".28"/>
          <circle class="cutlery-plate" cx="32" cy="31" r="24" fill="url(#plate)" stroke="#fed7aa" stroke-width="1.5"/>
          <circle cx="32" cy="31" r="17" fill="none" stroke="#fff7ed" stroke-opacity=".32" stroke-width="2"/>
          <g class="cutlery-tools" fill="none" stroke="url(#steel)" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M23 17v13M18.5 17v8c0 5 9 5 9 0v-8M23 30v16"/>
            <path d="M40 17c-6 6-6 15 0 18v11M40 17v18"/>
          </g>
          <path class="cutlery-shine" d="M17 19c7-9 19-12 29-5" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" opacity=".55"/>
        </svg>
      </span>
      <span class="text-xs mt-1">Menu</span>
    </a>
    <a href="/cart.php" class="cart-link flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'cart') ? 'active' : ''; ?>" aria-label="Cart" id="mobile-cart-link">
        <div class="relative">
            <i class="fas fa-shopping-cart text-lg"></i>
            <span id="mobile-cart-count" class="absolute -top-2 -right-3 bg-red-600 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center font-bold <?= $total_cart_items > 0 ? '' : 'hidden' ?>">
              <?= $total_cart_items ?>
            </span>
        </div>
        <span class="text-xs mt-1">Cart</span>
    </a>
    <?php if (is_user_logged_in()): ?>
      <a href="/profile.php" class="flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'profile') ? 'active' : ''; ?>" aria-label="Profile">
        <i class="fas fa-user text-lg"></i>
        <span class="text-xs mt-1">Profile</span>
      </a>
    <?php else: ?>
      <a href="/login.php" class="flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'login') ? 'active' : ''; ?>" aria-label="Login">
        <i class="fas fa-sign-in-alt text-lg"></i>
        <span class="text-xs mt-1">Login</span>
      </a>
    <?php endif; ?>
    <a href="/user_guide.php" class="flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400 <?php echo ($current_page == 'user_guide') ? 'active' : ''; ?>" aria-label="User Guide">
      <i class="fas fa-book text-lg"></i>
      <span class="text-xs mt-1">Guide</span>
    </a>
    <button type="button" class="theme-toggle knife-handle-action flex flex-col items-center justify-center text-gray-600 dark:text-slate-400 hover:text-orange-400" aria-label="Toggle color theme">
      <i class="fas fa-moon text-lg theme-icon-moon"></i>
      <i class="fas fa-sun text-lg theme-icon-sun hidden"></i>
      <span class="text-xs mt-1">Theme</span>
    </button>
  </div>
</div>

    <main class="cafe-main container mx-auto px-6 py-8 flex-grow">
