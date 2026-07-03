<?php
// FIX: Load session FIRST, then functions, then db
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions_pos.php';
require_once __DIR__ . '/includes/db_connect.php';

// Get the current page filename
$current_page_name = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Paicafe POS</title>
    <link rel="stylesheet" href="/admin/assets/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- FIX: Alpine.js 'defer' attribute is critical -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- FIX: Define Alpine functions in the <head> so they exist before the <body> loads -->
    <script>
        function posDashboard() {
            return {
                currentTime: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
                currentDate: new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }),
                init() {
                    setInterval(() => {
                        this.currentTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    }, 1000); // Update every second
                }
            }
        }
    </script>
    
    <style>
        .dashboard-bg { background-color: #3B0764; background-image: radial-gradient(circle at 1% 1%, #DA70D6, rgba(218, 112, 214, 0) 50%), radial-gradient(circle at 99% 1%, #FFC0CB, rgba(255, 192, 203, 0) 50%), radial-gradient(circle at 50% 99%, #3B0764, rgba(59, 7, 100, 0) 50%); background-size: cover; background-attachment: fixed; }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease; }
        .glass-card:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2); transform: translateY(-5px); }
        #rotate-overlay { display: none; position: fixed; inset: 0; background-color: #1a202c; color: white; z-index: 100; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
        #rotate-overlay i { font-size: 5rem; margin-bottom: 1rem; animation: rotate-anim 2.5s ease-in-out infinite; }
        @keyframes rotate-anim { 0% { transform: rotate(0deg); } 40% { transform: rotate(90deg); } 60% { transform: rotate(90deg); } 100% { transform: rotate(0deg); } }
        @media (max-width: 768px) and (orientation: portrait) { #rotate-overlay { display: flex; } .dashboard-container, .pos-container { display: none; } }
        body { background-color: transparent; }
        .pos-scroll::-webkit-scrollbar { width: 5px; }
        .pos-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .pos-scroll::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        @media print { body > *:not(.voucher-print-area) { display: none !important; } .no-print { display: none !important; } .voucher-print-area { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none !important; border: none !important; display: block; visibility: visible; } }
    </style>
</head>
<body class="h-full">


---
### **4. The Main Dashboard (`index.php`)**

This file is now fixed. The `x-data="posDashboard()"` call will work, and the login check will correctly show either the dashboard or the "Staff Login" button.

**File: `/admin/pos/index.php` (Fully Corrected)**
```php
<?php
// This header file is the new, local one
include __DIR__ . '/header.php';

// Fetch data for the notification panel
$pending_orders_count = 0;
$ready_for_pickup_count = 0;

if (is_admin_logged_in()) {
    $pending_orders_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_approval'")->fetchColumn();
    $ready_for_pickup_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'ready_for_pickup'")->fetchColumn();
}
?>

<!-- "Please Rotate" Overlay -->
<div id="rotate-overlay">
    <i class="fas fa-mobile-alt"></i>
    <h2 class="text-2xl font-bold">Please Rotate Your Device</h2>
    <p class="mt-2 text-lg">The POS interface is best viewed in landscape mode.</p>
</div>

<!-- Main POS Container -->
<!-- This x-data call will now work correctly -->
<div class="dashboard-container dashboard-bg w-full h-full p-4 md:p-6 text-white overflow-y-auto" x-data="posDashboard()">
    
    <!-- Top Bar: Header Info -->
    <div class="flex justify-between items-center mb-6">
        <div class="text-left">
            <h1 class="text-3xl md:text-4xl font-bold">Paicafe POS</h1>
            <p class="text-lg text-pink-200" x-text="currentDate"></p>
        </div>
        <div class="text-right">
            <div class="text-4xl lg:text-5xl font-bold" x-text="currentTime"></div>
            <div class="mt-2">
                <?php if (is_admin_logged_in()): ?>
                    <a href="logout.php" class="text-gray-300 hover:text-white text-lg">
                        <i class="fas fa-user-circle"></i> <?= e($_SESSION['admin_username']) ?> (Logout)
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-brand text-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i> Staff Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (is_admin_logged_in()): ?>
    <!-- Logged In State: Show Dashboard -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        <!-- Left Column: Notifications -->
        <div class="lg:col-span-1 bg-black bg-opacity-20 p-6 rounded-xl">
            <h2 class="text-2xl font-bold mb-4 border-b border-white/10 pb-2">Notifications</h2>
            <div class="space-y-4">
                <a href="orders.php" class="flex items-center p-3 rounded-lg hover:bg-black/20 transition-colors">
                    <i class="fas fa-hourglass-half fa-lg text-yellow-300 mr-4"></i>
                    <div>
                        <p class="font-semibold">New Pending Orders</p>
                        <p class="text-2xl font-bold"><?= $pending_orders_count ?></p>
                    </div>
                </a>
                <a href="orders.php" class="flex items-center p-3 rounded-lg hover:bg-black/20 transition-colors">
                    <i class="fas fa-bell fa-lg text-purple-300 mr-4"></i>
                    <div>
                        <p class="font-semibold">Ready for Pickup</p>
                        <p class="text-2xl font-bold"><?= $ready_for_pickup_count ?></p>
                    </div>
                </a>
                <a href="../reservations.php" target="_blank" class="flex items-center p-3 rounded-lg hover:bg-black/20 transition-colors">
                    <i class="fas fa-calendar-check fa-lg text-blue-300 mr-4"></i>
                    <div><p class="font-semibold">Reservations</p></div>
                </a>
            </div>
        </div>

        <!-- Right Column: Main Grid -->
        <div class="lg:col-span-3">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <a href="pos.php" class="glass-card rounded-xl p-6 text-center h-40 flex flex-col justify-center">
                    <i class="fas fa-cash-register fa-3x text-pink-300 mb-3"></i>
                    <p class="font-semibold">New Sale</p>
                </a>
                <a href="orders.php" class="glass-card rounded-xl p-6 text-center h-40 flex flex-col justify-center">
                    <i class="fas fa-receipt fa-3x text-pink-300 mb-3"></i>
                    <p class="font-semibold">All Orders</p>
                </a>
                <a href="floor_plan.php" class="glass-card rounded-xl p-6 text-center h-40 flex flex-col justify-center">
                    <i class="fas fa-map-marked-alt fa-3x text-pink-300 mb-3"></i>
                    <p class="font-semibold">Floor Plan</p>
                </a>
                <a href="inventory.php" class="glass-card rounded-xl p-6 text-center h-40 flex flex-col justify-center">
                    <i class="fas fa-boxes-stacked fa-3x text-pink-300 mb-3"></i>
                    <p class="font-semibold">Inventory</p>
                </a>
                
                <!-- Links to main admin panel open in a new tab -->
                <a href="../products.php" target="_blank" class="glass-card rounded-xl p-6 text-center h-40 flex flex-col justify-center">
                    <i class="fas fa-box fa-3x text-pink-300 mb-3"></i>
                    <p class="font-semibold">Products</p>
                </a>
                <a href="../coupons.php" target="_blank" class="glass-card rounded-xl p-6 text-center h-40 flex flex-col justify-center">
                    <i class="fas fa-ticket-alt fa-3x text-pink-300 mb-3"></i>
                    <p class="font-semibold">Coupons</p>
                </a>
                <a href="../user_properties.php" target="_blank" class="glass-card rounded-xl p-6 text-center h-40 flex flex-col justify-center">
                    <i class="fas fa-users-cog fa-3x text-pink-300 mb-3"></i>
                    <p class="font-semibold">Customers</p>
                </a>
                <a href="../reports.php" target="_blank" class="glass-card rounded-xl p-6 text-center h-40 flex flex-col justify-center">
                    <i class="fas fa-chart-line fa-3x text-pink-300 mb-3"></i>
                    <p class="font-semibold">Reports</p>
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Logged Out State -->
    <div class="text-center">
        <h2 class="text-2xl font-semibold">Please log in to access the POS system.</h2>
        <a href="login.php" class="btn-brand text-lg mt-6">
            <i class="fas fa-sign-in-alt mr-2"></i> Staff Login
        </a>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
