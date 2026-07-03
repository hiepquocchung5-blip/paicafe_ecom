<?php
include __DIR__ . '/header.php';

$pending_orders_count = 0;
$ready_for_pickup_count = 0;

if (is_admin_logged_in()) {
    $pending_orders_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_approval'")->fetchColumn();
    $ready_for_pickup_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'ready_for_pickup'")->fetchColumn();
}
?>

<div id="rotate-overlay">
    <i class="fas fa-mobile-alt"></i>
    <h2 class="text-2xl font-bold">Please Rotate Your Device</h2>
    <p class="mt-2 text-lg">The POS interface is best viewed in landscape mode.</p>
</div>

<div class="dashboard-container dashboard-bg w-full h-full p-4 md:p-6 text-white overflow-y-auto" x-data="posDashboard()" x-init="init()">
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
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
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
        <div class="text-center">
            <h2 class="text-2xl font-semibold">Please log in to access the POS system.</h2>
            <a href="login.php" class="btn-brand text-lg mt-6">
                <i class="fas fa-sign-in-alt mr-2"></i> Staff Login
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
