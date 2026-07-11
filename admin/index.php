<?php
// The header now handles all includes and security
include __DIR__ . '/partials/header.php';

// --- Permission Checks ---
if (!has_permission('view_dashboard')) {
    die('Access Denied. You do not have permission to view this page.');
}
$can_view_reports = has_permission('view_reports');
$can_manage_orders = has_permission('manage_orders');
$can_manage_rewards = has_permission('manage_rewards');

// --- Handle Form Actions directly from Dashboard (Approve/Complete/Cancel) ---
$message = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage_orders) {
    $pdo->beginTransaction();
    try {
        $order_id = (int)($_POST['order_id'] ?? 0);
        if ($order_id <= 0) throw new Exception("Invalid Order ID.");

        if (isset($_POST['approve_order'])) {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ? AND status = 'pending_approval'");
            $stmt->execute([$order_id]);
            $stmt = $pdo->prepare("UPDATE payments SET status = 'approved', processed_by_admin_id = ? WHERE order_id = ?");
            $stmt->execute([$_SESSION['admin_id'], $order_id]);
            $message = "Order #{$order_id} payment approved successfully.";
            log_activity($pdo, "Approved payment for Order #$order_id from Dashboard");
        }

        if (isset($_POST['complete_order'])) {
            $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch();

            if ($order && $order['status'] !== 'completed') {
                $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
                $stmt->execute([$order_id]);

                // Award Loyalty Points
                $user_id_for_points = $order['user_id'];
                if (!$user_id_for_points && !empty($order['customer_phone_for_points'])) {
                    $user_stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
                    $user_stmt->execute([$order['customer_phone_for_points']]);
                    $user_id_for_points = $user_stmt->fetchColumn();
                }

                if ($user_id_for_points) {
                    $points_rate = (int)get_setting($pdo, 'loyalty_points_per_100_kyats', 1);
                    $points_earned = floor($order['final_amount'] / 100) * $points_rate;
                    if ($points_earned > 0) {
                        $update_points_stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
                        $update_points_stmt->execute([$points_earned, $user_id_for_points]);
                    }
                }

                if ($order['table_id']) {
                    $status_stmt = $pdo->prepare("UPDATE tables SET status = 'needs_cleaning' WHERE id = ?");
                    $status_stmt->execute([$order['table_id']]);
                }
                $message = "Order #{$order_id} marked as completed.";
                log_activity($pdo, "Completed Order #$order_id from Dashboard");
            }
        }

        if (isset($_POST['cancel_order'])) {
            $order_stmt = $pdo->prepare("SELECT table_id, status FROM orders WHERE id = ?");
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch();
            
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$order_id]);
            
            if ($order && $order['table_id']) {
                $status_stmt = $pdo->prepare("UPDATE tables SET status = 'free' WHERE id = ?");
                $status_stmt->execute([$order['table_id']]);
            }
            $message = "Order #{$order_id} cancelled.";
            log_activity($pdo, "Cancelled Order #$order_id from Dashboard");
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = $e->getMessage();
    }
}

// --- Fetch Data Conditionally (AFTER potential updates) ---
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_approval'")->fetchColumn();
$ready_for_pickup = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'ready_for_pickup'")->fetchColumn();
$todays_revenue = $can_view_reports ? ($pdo->query("SELECT SUM(final_amount) FROM orders WHERE status = 'completed' AND DATE(created_at) = CURDATE()")->fetchColumn() ?? 0) : 0;
$total_products = $pdo->query("SELECT COUNT(*) FROM products WHERE is_available = 1")->fetchColumn();

// Join users and tables to display richer data in recent transmissions
$recent_orders = $can_manage_orders ? $pdo->query("
    SELECT o.*, u.username, u.phone_number, t.table_number 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN tables t ON o.table_id = t.id 
    ORDER BY o.updated_at DESC LIMIT 5
")->fetchAll() : [];

$recent_redemptions = $can_manage_rewards ? $pdo->query("
    SELECT u.username, lr.title, rr.redeemed_at 
    FROM reward_redemptions rr 
    JOIN users u ON rr.user_id = u.id 
    JOIN loyalty_rewards lr ON rr.reward_id = lr.id 
    ORDER BY rr.redeemed_at DESC LIMIT 5
")->fetchAll() : [];

// Fetch hourly order volume for today to display in a beautiful chart
$hourly_data = $pdo->query("
    SELECT HOUR(created_at) as hour, COUNT(*) as count 
    FROM orders 
    WHERE DATE(created_at) = CURDATE() 
    GROUP BY HOUR(created_at)
    ORDER BY hour
")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

// Let's prepare data for operating hours (8 AM to 10 PM)
$chart_labels = [];
$chart_values = [];
for ($h = 8; $h <= 22; $h++) {
    $label = date("g a", mktime($h, 0, 0, 1, 1, 2000));
    $chart_labels[] = $label;
    $chart_values[] = (int)($hourly_data[$h] ?? 0);
}

// Calculate greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good Morning";
    $greeting_icon = "fa-sun text-amber-500 animate-spin-slow";
} elseif ($hour < 18) {
    $greeting = "Good Afternoon";
    $greeting_icon = "fa-cloud-sun text-orange-400";
} else {
    $greeting = "Good Evening";
    $greeting_icon = "fa-moon text-indigo-400";
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;400;600;800&display=swap');
    
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .premium-glass {
        background:
            linear-gradient(145deg, color-mix(in srgb, var(--liquid-glass-strong) 76%, transparent), color-mix(in srgb, var(--liquid-glass) 92%, transparent));
        backdrop-filter: blur(24px) saturate(142%);
        -webkit-backdrop-filter: blur(24px) saturate(142%);
        border: 1px solid var(--liquid-stroke);
        box-shadow: var(--liquid-shadow);
    }

    .dark .premium-glass {
        background:
            linear-gradient(145deg, color-mix(in srgb, var(--liquid-glass-strong) 76%, transparent), color-mix(in srgb, var(--liquid-glass) 92%, transparent));
        border: 1px solid var(--liquid-stroke);
        box-shadow: var(--liquid-shadow);
    }

    .glass-card-hover {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .glass-card-hover:hover {
        transform: translateY(-4px);
        background:
            linear-gradient(145deg, color-mix(in srgb, var(--liquid-glass-strong) 88%, transparent), color-mix(in srgb, var(--liquid-glass) 96%, transparent));
        border-color: color-mix(in srgb, var(--brand-primary) 42%, var(--liquid-stroke));
        box-shadow: 0 30px 90px rgba(15, 118, 110, 0.18), var(--liquid-shadow);
    }

    .dark .glass-card-hover:hover {
        background:
            linear-gradient(145deg, color-mix(in srgb, var(--liquid-glass-strong) 88%, transparent), color-mix(in srgb, var(--liquid-glass) 96%, transparent));
        border-color: color-mix(in srgb, var(--brand-secondary) 44%, var(--liquid-stroke));
        box-shadow: 0 30px 90px rgba(0, 0, 0, 0.38), var(--liquid-shadow);
    }

    /* Glows for specific cards */
    .glow-orange:hover {
        box-shadow: 0 0 30px rgba(234, 88, 12, 0.2), 0 20px 40px -10px rgba(234, 88, 12, 0.12) !important;
    }
    .glow-yellow:hover {
        box-shadow: 0 0 30px rgba(245, 158, 11, 0.2), 0 20px 40px -10px rgba(245, 158, 11, 0.12) !important;
    }
    .glow-purple:hover {
        box-shadow: 0 0 30px rgba(168, 85, 247, 0.2), 0 20px 40px -10px rgba(168, 85, 247, 0.12) !important;
    }
    .glow-emerald:hover {
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.2), 0 20px 40px -10px rgba(16, 185, 129, 0.12) !important;
    }
    .glow-blue:hover {
        box-shadow: 0 0 30px rgba(59, 130, 246, 0.2), 0 20px 40px -10px rgba(59, 130, 246, 0.12) !important;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.3);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(148, 163, 184, 0.5);
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(71, 85, 105, 0.3);
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(71, 85, 105, 0.5);
    }

    @keyframes pulse-ring {
        0% { transform: scale(0.9); opacity: 0.8; }
        50% { transform: scale(1.1); opacity: 0.4; }
        100% { transform: scale(1.3); opacity: 0; }
    }
    .pulse-ring-indicator {
        position: relative;
    }
    .pulse-ring-indicator::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: inherit;
        animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        top: 0;
        left: 0;
    }

    @keyframes spin-slow {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .animate-spin-slow {
        animation: spin-slow 20s linear infinite;
    }
</style>

<div class="max-w-7xl mx-auto px-2 py-6" x-data="dashboardNotifications()" x-init="init()">
    
    <!-- ECOSYSTEM LIVE NODES -->
    <div class="mb-10">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-1.5 h-6 bg-orange-600 rounded-full"></div>
            <h2 class="text-xs font-black uppercase tracking-[0.4em] text-slate-500 dark:text-slate-400">Live System Connections</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <!-- Node: Kitchen -->
            <a href="https://poskitchen.paicafes.com" target="_blank" class="node-card premium-glass glass-card-hover glow-orange rounded-2xl p-5 flex items-center justify-between group border">
                <div class="flex items-center space-x-5">
                    <div class="w-12 h-12 bg-orange-600/10 rounded-xl flex items-center justify-center group-hover:bg-orange-600 group-hover:rotate-[360deg] transition-all duration-700">
                        <i class="fas fa-fire-burner text-orange-500 text-xl group-hover:text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">KITCHEN SCREEN</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-1 opacity-70 group-hover:opacity-100 transition-opacity">poskitchen.paicafes.com</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-[9px] font-mono font-bold text-emerald-500 hidden group-hover:inline-block">28ms</span>
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 pulse-ring-indicator shadow-[0_0_12px_rgba(16,185,129,0.5)]"></div>
                </div>
            </a>

            <!-- Node: Table System -->
            <a href="https://postable.paicafes.com" target="_blank" class="node-card premium-glass glass-card-hover glow-blue rounded-2xl p-5 flex items-center justify-between group border">
                <div class="flex items-center space-x-5">
                    <div class="w-12 h-12 bg-blue-600/10 rounded-xl flex items-center justify-center group-hover:bg-blue-600 group-hover:rotate-[360deg] transition-all duration-700">
                        <i class="fas fa-couch text-blue-400 text-xl group-hover:text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">TABLE SERVICE</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-1 opacity-70 group-hover:opacity-100 transition-opacity">postable.paicafes.com</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-[9px] font-mono font-bold text-emerald-500 hidden group-hover:inline-block">14ms</span>
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 pulse-ring-indicator shadow-[0_0_12px_rgba(16,185,129,0.5)]"></div>
                </div>
            </a>

            <!-- Node: Public Website -->
            <a href="https://paicafes.com" target="_blank" class="node-card premium-glass glass-card-hover glow-emerald rounded-2xl p-5 flex items-center justify-between group border border-dashed">
                <div class="flex items-center space-x-5">
                    <div class="w-12 h-12 bg-slate-200 dark:bg-white/5 rounded-xl flex items-center justify-center group-hover:bg-slate-800 dark:group-hover:bg-white group-hover:text-white dark:group-hover:text-black transition-all duration-500">
                        <i class="fas fa-globe text-slate-400 dark:text-slate-400 text-xl group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">STOREFRONT</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-1 opacity-70 group-hover:opacity-100 transition-opacity">paicafes.com</p>
                    </div>
                </div>
                <i class="fas fa-arrow-up-right-from-square text-xs text-slate-400 group-hover:text-orange-500 transform group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform"></i>
            </a>
        </div>
    </div>

    <!-- MAIN DASHBOARD HEADER -->
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-2 text-xs font-black uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400 mb-2">
                <i class="fas <?= $greeting_icon ?> text-sm"></i>
                <span><?= $greeting ?></span>
            </div>
            <h1 class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter leading-none mb-1">Cafe Control Hub</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium">Real-time order manager and store operations dashboard.</p>
        </div>
        <div class="flex items-center space-x-3 bg-slate-100 dark:bg-gray-900/80 border border-slate-200 dark:border-white/5 px-5 py-2.5 rounded-2xl premium-glass shadow-sm">
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 pulse-ring-indicator shadow-[0_0_12px_rgba(16,185,129,0.5)]"></div>
            <span class="text-[10px] font-black tracking-widest text-emerald-500">SYSTEM ONLINE</span>
            <div class="h-4 w-[1px] bg-slate-300 dark:bg-white/10 mx-2"></div>
            <span class="text-[11px] font-mono font-bold text-slate-600 dark:text-slate-300" id="live-clock"><?= date('H:i:s') ?></span>
        </div>
    </div>

    <!-- STATS ENGINE -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        
        <!-- Stats Card: Queue -->
        <div class="stat-card premium-glass glass-card-hover glow-yellow p-6 rounded-3xl group border-t-4 border-yellow-500/70 relative overflow-hidden flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[10px] font-black text-yellow-600 dark:text-yellow-400 uppercase tracking-widest">Orders in Queue</p>
                    <h3 class="text-4xl font-black text-slate-800 dark:text-white mt-1"><?= e($pending_orders) ?></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-yellow-500/10 flex items-center justify-center text-yellow-500 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <i class="fas fa-hourglass-half text-xl"></i>
                </div>
            </div>
            <div>
                <div class="w-full bg-slate-200 dark:bg-white/5 h-1.5 rounded-full overflow-hidden mb-3">
                    <div class="bg-yellow-500 h-full rounded-full transition-all duration-500" style="width: <?= min(100, $pending_orders * 10) ?>%"></div>
                </div>
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-tight flex items-center">
                    <span class="w-2 h-2 rounded-full bg-yellow-500 mr-2 pulse-ring-indicator inline-block"></span>
                    <?= $pending_orders > 0 ? $pending_orders . ' ACTIVE PENDING' : 'NO PENDING ORDERS' ?>
                </span>
            </div>
        </div>
        
        <!-- Stats Card: Logistics -->
        <div class="stat-card premium-glass glass-card-hover glow-purple p-6 rounded-3xl group border-t-4 border-purple-500/70 relative overflow-hidden flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[10px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-widest">Ready for Pickup</p>
                    <h3 class="text-4xl font-black text-slate-800 dark:text-white mt-1"><?= e($ready_for_pickup) ?></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 flex items-center justify-center text-purple-500 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <i class="fas fa-bell text-xl"></i>
                </div>
            </div>
            <div>
                <div class="w-full bg-slate-200 dark:bg-white/5 h-1.5 rounded-full overflow-hidden mb-3">
                    <div class="bg-purple-500 h-full rounded-full transition-all duration-500" style="width: <?= min(100, $ready_for_pickup * 10) ?>%"></div>
                </div>
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-tight flex items-center">
                    <span class="w-2 h-2 rounded-full bg-purple-500 mr-2 pulse-ring-indicator inline-block"></span>
                    <?= $ready_for_pickup > 0 ? $ready_for_pickup . ' WAITING FOR COLLECTION' : 'NOTHING TO COLLECT' ?>
                </span>
            </div>
        </div>

        <!-- Stats Card: Revenue -->
        <?php if ($can_view_reports): ?>
        <div class="stat-card premium-glass glass-card-hover glow-emerald p-6 rounded-3xl group border-t-4 border-emerald-500/70 relative overflow-hidden flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Today's Revenue</p>
                    <h3 class="text-3xl font-black text-slate-800 dark:text-white mt-1"><?= number_format($todays_revenue) ?> <span class="text-xs font-normal">Ks</span></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <i class="fas fa-coins text-xl"></i>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center text-[10px] text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-wider mb-2">
                    <span>Target Achievement</span>
                    <span><?= min(100, round(($todays_revenue / 100000) * 100)) ?>%</span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-white/5 h-1.5 rounded-full overflow-hidden mb-3">
                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: <?= min(100, round(($todays_revenue / 100000) * 100)) ?>%"></div>
                </div>
                <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold tracking-tight">
                    TARGET: 100,000 Ks / DAY
                </span>
            </div>
        </div>
        <?php else: ?>
        <!-- Table Occupancy representation if financial reports are locked -->
        <?php 
            $active_tables = $pdo->query("SELECT COUNT(*) FROM tables WHERE status != 'free'")->fetchColumn(); 
            $total_tables = $pdo->query("SELECT COUNT(*) FROM tables")->fetchColumn() ?: 1; 
        ?>
        <div class="stat-card premium-glass glass-card-hover glow-emerald p-6 rounded-3xl group border-t-4 border-emerald-500/70 relative overflow-hidden flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Table Occupancy</p>
                    <h3 class="text-4xl font-black text-slate-800 dark:text-white mt-1"><?= $active_tables ?>/<?= $total_tables ?></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <i class="fas fa-couch text-xl"></i>
                </div>
            </div>
            <div>
                <div class="w-full bg-slate-200 dark:bg-white/5 h-1.5 rounded-full overflow-hidden mb-3">
                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: <?= min(100, round(($active_tables / $total_tables) * 100)) ?>%"></div>
                </div>
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-tight flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 pulse-ring-indicator inline-block"></span>
                    <?= $total_tables - $active_tables ?> FREE TABLES
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Card: Active SKUs -->
        <div class="stat-card premium-glass glass-card-hover glow-orange p-6 rounded-3xl group border-t-4 border-orange-600/70 relative overflow-hidden flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[10px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest">Menu Items (SKUs)</p>
                    <h3 class="text-4xl font-black text-slate-800 dark:text-white mt-1"><?= e($total_products) ?></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-orange-600/10 flex items-center justify-center text-orange-500 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    <i class="fas fa-cubes text-xl"></i>
                </div>
            </div>
            <div>
                <div class="w-full bg-slate-200 dark:bg-white/5 h-1.5 rounded-full overflow-hidden mb-3">
                    <div class="bg-orange-500 h-full rounded-full transition-all duration-500" style="width: 100%"></div>
                </div>
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-tight flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 pulse-ring-indicator inline-block"></span>
                    SYNCHRONIZED WITH STOCK
                </span>
            </div>
        </div>
    </div>

    <!-- CHARTS & ACTION HUB -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
        <!-- Sales Load Activity Chart -->
        <div class="lg:col-span-2 premium-glass rounded-[2rem] p-8 flex flex-col relative overflow-hidden border border-slate-200 dark:border-white/5">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-2 h-2 rounded-full bg-orange-600 shadow-[0_0_10px_#ea580c] animate-pulse"></div>
                    <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight uppercase">Today's Order Traffic</h2>
                </div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Hourly Order Volume</span>
            </div>
            <div class="relative w-full h-[220px]">
                <canvas id="todayHourlyChart"></canvas>
            </div>
        </div>
        
        <!-- Quick Console / Action Hub -->
        <div class="premium-glass rounded-[2rem] p-8 flex flex-col justify-between border border-slate-200 dark:border-white/5">
            <div>
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-2 h-2 rounded-full bg-blue-500 shadow-[0_0_10px_#3b82f6]"></div>
                    <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight uppercase">Quick Actions</h2>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <a href="pos.php" class="flex flex-col items-center justify-center p-4 rounded-2xl bg-emerald-500/5 hover:bg-emerald-500/10 border border-emerald-500/10 hover:border-emerald-500/30 transition-all text-center group">
                        <i class="fas fa-cash-register text-emerald-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-extrabold text-slate-800 dark:text-white">POS Terminal</span>
                    </a>
                    <a href="orders.php" class="flex flex-col items-center justify-center p-4 rounded-2xl bg-orange-500/5 hover:bg-orange-500/10 border border-orange-500/10 hover:border-orange-500/30 transition-all text-center group">
                        <i class="fas fa-receipt text-orange-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-extrabold text-slate-800 dark:text-white">Orders Feed</span>
                    </a>
                    <a href="products.php" class="flex flex-col items-center justify-center p-4 rounded-2xl bg-purple-500/5 hover:bg-purple-500/10 border border-purple-500/10 hover:border-purple-500/30 transition-all text-center group">
                        <i class="fas fa-box text-purple-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-extrabold text-slate-800 dark:text-white">Manage SKUs</span>
                    </a>
                    <?php if ($can_view_reports): ?>
                    <a href="sales_dashboard.php" class="flex flex-col items-center justify-center p-4 rounded-2xl bg-yellow-500/5 hover:bg-yellow-500/10 border border-yellow-500/10 hover:border-yellow-500/30 transition-all text-center group">
                        <i class="fas fa-chart-line text-yellow-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-extrabold text-slate-800 dark:text-white">Analytics</span>
                    </a>
                    <?php else: ?>
                    <a href="floor_plan.php" class="flex flex-col items-center justify-center p-4 rounded-2xl bg-yellow-500/5 hover:bg-yellow-500/10 border border-yellow-500/10 hover:border-yellow-500/30 transition-all text-center group">
                        <i class="fas fa-map-location-dot text-yellow-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-extrabold text-slate-800 dark:text-white">Floor Plan</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-6 border-t border-slate-200 dark:border-white/5 pt-4 flex items-center justify-between text-xs text-slate-500">
                <span>Active Admin: <?= e($_SESSION['admin_username']) ?></span>
                <span class="font-mono text-emerald-500 font-bold animate-pulse">● LIVE CONSOLE</span>
            </div>
        </div>
    </div>
    
    <!-- DATA TERMINALS (ORDERS & LOYALTY) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- ORDERS TERMINAL (Takes 2 columns) -->
        <?php if ($can_manage_orders): ?>
        <div class="lg:col-span-2 premium-glass rounded-[2rem] overflow-hidden flex flex-col border border-slate-200 dark:border-white/5 shadow-sm">
            <div class="p-6 border-b border-slate-200 dark:border-white/5 flex items-center justify-between bg-slate-50/50 dark:bg-white/[0.01]">
                <div class="flex items-center space-x-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-orange-600 pulse-ring-indicator shadow-[0_0_10px_#ea580c]"></div>
                    <h2 class="text-lg font-black text-slate-800 dark:text-white tracking-tight uppercase">Recent Orders</h2>
                </div>
                <a href="orders.php" class="text-[10px] font-black text-orange-500 hover:text-orange-400 uppercase tracking-widest transition-colors flex items-center">View All <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-white/5 bg-slate-100/50 dark:bg-slate-900/50">
                            <th class="py-4 px-6 text-[10px] uppercase font-black text-slate-400 dark:text-gray-500 tracking-[0.2em]">Order ID</th>
                            <th class="py-4 px-6 text-[10px] uppercase font-black text-slate-400 dark:text-gray-500 tracking-[0.2em]">Customer</th>
                            <th class="py-4 px-6 text-[10px] uppercase font-black text-slate-400 dark:text-gray-500 tracking-[0.2em]">Service Mode</th>
                            <th class="py-4 px-6 text-[10px] uppercase font-black text-slate-400 dark:text-gray-500 tracking-[0.2em]">Amount</th>
                            <th class="py-4 px-6 text-[10px] uppercase font-black text-slate-400 dark:text-gray-500 tracking-[0.2em]">Status</th>
                            <th class="py-4 px-6 text-[10px] uppercase font-black text-slate-400 dark:text-gray-500 tracking-[0.2em] text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="6" class="p-12 text-center text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-16 h-16 rounded-full bg-orange-500/10 flex items-center justify-center text-orange-500 animate-pulse">
                                            <i class="fas fa-mug-hot text-2xl"></i>
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-slate-800 dark:text-white text-base">No orders yet today</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">When customers place orders online or at the counter, they will appear here in real-time.</p>
                                        </div>
                                        <a href="pos.php" class="mt-2 inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold transition-all shadow-md">
                                            <i class="fas fa-cash-register mr-1.5"></i> Open POS Terminal
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($recent_orders as $order): 
                            $status_colors = [
                                'pending_approval' => 'text-yellow-600 dark:text-yellow-400 border-yellow-500/20 bg-yellow-500/5', 
                                'processing' => 'text-blue-600 dark:text-blue-400 border-blue-400/20 bg-blue-400/5',
                                'ready_for_pickup' => 'text-purple-600 dark:text-purple-400 border-purple-400/20 bg-purple-400/5', 
                                'completed' => 'text-emerald-600 dark:text-emerald-400 border-emerald-400/20 bg-emerald-400/5',
                                'cancelled' => 'text-red-600 dark:text-red-400 border-red-400/20 bg-red-400/5',
                            ];
                            $color = $status_colors[$order['status']] ?? 'text-slate-500 dark:text-gray-400 border-white/10 bg-white/5';
                            
                            // Route display
                            if ($order['order_type'] === 'web') {
                                $route_icon = '<i class="fas fa-truck text-sky-500 mr-1"></i>';
                                $route_text = e($order['delivery_city'] ?? 'Delivery');
                            } else {
                                $route_icon = '<i class="fas fa-chair text-emerald-500 mr-1"></i>';
                                $route_text = !empty($order['table_number']) ? 'T-' . e($order['table_number']) : 'Takeaway';
                            }
                        ?>
                        <tr class="group hover:bg-slate-100/50 dark:hover:bg-white/[0.01] transition-all border-b border-slate-200 dark:border-white/5">
                            <!-- Order ID -->
                            <td class="py-4 px-6">
                                <a href="voucher_view.php?order_id=<?= e($order['id']) ?>" target="_blank" class="font-mono font-black text-slate-800 dark:text-white hover:text-orange-500 transition-colors">
                                    #<?= sprintf("%05d", $order['id']) ?>
                                </a>
                                <span class="block text-[9px] text-slate-400 dark:text-slate-500 font-mono mt-0.5"><?= date('H:i', strtotime($order['updated_at'])) ?></span>
                            </td>
                            <!-- Customer -->
                            <td class="py-4 px-6">
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-tight block"><?= e($order['username'] ?? 'Walk-in Customer') ?></span>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono block mt-0.5"><?= e($order['phone_number'] ?? $order['customer_phone_for_points'] ?? 'N/A') ?></span>
                            </td>
                            <!-- Route -->
                            <td class="py-4 px-6 text-xs text-slate-600 dark:text-slate-300 font-semibold">
                                <div class="flex items-center">
                                    <?= $route_icon ?>
                                    <span><?= $route_text ?></span>
                                </div>
                            </td>
                            <!-- Amount -->
                            <td class="py-4 px-6">
                                <span class="text-sm font-black text-slate-800 dark:text-white"><?= number_format($order['final_amount'], 0) ?> <span class="text-[9px] font-normal text-slate-400">Ks</span></span>
                            </td>
                            <!-- Status -->
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 text-[9px] font-black uppercase rounded-full border <?= $color ?> tracking-widest inline-flex items-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5 animate-pulse"></span>
                                    <?= str_replace('_', ' ', $order['status']) ?>
                                </span>
                            </td>
                            <!-- Actions -->
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center space-x-2">
                                    <?php if ($order['status'] === 'pending_approval'): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Approve payment for Order #<?= $order['id'] ?>?');">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" name="approve_order" class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white rounded-lg text-[9px] font-extrabold uppercase tracking-wider transition-all shadow-sm">
                                                Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] === 'ready_for_pickup'): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Mark Order #<?= $order['id'] ?> as completed?');">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" name="complete_order" class="px-2.5 py-1 bg-purple-500 hover:bg-purple-600 active:scale-95 text-white rounded-lg text-[9px] font-extrabold uppercase tracking-wider transition-all shadow-sm">
                                                Complete
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <a href="print_receipt.php?order_id=<?= e($order['id']) ?>" target="_blank" class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-orange-500 rounded-lg text-xs transition-colors" title="Print Invoice">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    
                                    <a href="voucher_view.php?order_id=<?= e($order['id']) ?>" target="_blank" class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-blue-500 rounded-lg text-xs transition-colors" title="View Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Cancel Order #<?= $order['id'] ?>?');">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" name="cancel_order" class="p-1.5 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white rounded-lg text-xs transition-colors" title="Cancel Order">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- LOYALTY TERMINAL (Takes 1 column) -->
        <?php if ($can_manage_rewards): ?>
        <div class="premium-glass rounded-[2rem] overflow-hidden flex flex-col border border-slate-200 dark:border-white/5 shadow-sm">
            <div class="p-6 border-b border-slate-200 dark:border-white/5 flex items-center justify-between bg-slate-50/50 dark:bg-white/[0.01]">
                <div class="flex items-center space-x-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 pulse-ring-indicator shadow-[0_0_10px_#10b981]"></div>
                    <h2 class="text-lg font-black text-slate-800 dark:text-white tracking-tight uppercase">Recent Redemptions</h2>
                </div>
                <a href="redemptions.php" class="text-[10px] font-black text-emerald-500 hover:text-emerald-400 uppercase tracking-widest transition-colors flex items-center">View All <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            
            <div class="p-6 space-y-4 max-h-[380px] overflow-y-auto custom-scrollbar flex-1">
                <?php if (empty($recent_redemptions)): ?>
                    <div class="py-12 px-6 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <div class="w-16 h-16 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                                <i class="fas fa-gift text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-extrabold text-slate-800 dark:text-white text-sm">No rewards redeemed today</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">When loyalty club members redeem their points for cafe coupons, they will show up here.</p>
                            </div>
                            <a href="rewards.php" class="mt-2 inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[10px] font-bold transition-all shadow-sm">
                                <i class="fas fa-tags mr-1"></i> Manage Rewards
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($recent_redemptions as $r): ?>
                    <div class="group flex justify-between items-center p-4 border border-slate-200 dark:border-white/5 rounded-2xl bg-slate-50 dark:bg-white/[0.02] hover:bg-slate-100 dark:hover:bg-white/[0.05] transition-all hover:border-emerald-500/30">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20 group-hover:bg-emerald-500 group-hover:rotate-12 transition-all">
                                <i class="fas fa-user-astronaut text-emerald-500 text-sm group-hover:text-white"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-slate-800 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors uppercase truncate"><?= e($r['username']) ?></p>
                                <p class="text-[10px] text-slate-500 dark:text-gray-500 font-bold uppercase mt-1 tracking-tight truncate"><?= e($r['title']) ?></p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-2">
                            <span class="text-[9px] font-mono text-slate-400 dark:text-slate-500 block"><?= date('H:i', strtotime($r['redeemed_at'])) ?></span>
                            <span class="inline-block px-1.5 py-0.5 text-[8px] bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded font-black tracking-widest mt-1">REDEEMED</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Live Ticking Clock
document.addEventListener('DOMContentLoaded', () => {
    const clock = document.getElementById('live-clock');
    if (clock) {
        setInterval(() => {
            const now = new Date();
            clock.textContent = now.toTimeString().split(' ')[0];
        }, 1000);
    }
});

// Chart initialization
const ctx = document.getElementById('todayHourlyChart').getContext('2d');
const isDark = document.documentElement.classList.contains('dark');
const primaryColor = '#ea580c';
const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
const textColor = isDark ? '#94a3b8' : '#64748b';

// Dynamic gradients
const gradientFill = ctx.createLinearGradient(0, 0, 0, 200);
gradientFill.addColorStop(0, 'rgba(234, 88, 12, 0.35)');
gradientFill.addColorStop(1, 'rgba(234, 88, 12, 0.0)');

window.todayHourlyChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Orders Count',
            data: <?= json_encode($chart_values) ?>,
            borderColor: primaryColor,
            borderWidth: 3,
            backgroundColor: gradientFill,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: primaryColor,
            pointBorderColor: isDark ? '#0f172a' : '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: isDark ? '#1e293b' : '#ffffff',
                titleColor: isDark ? '#ffffff' : '#1e293b',
                bodyColor: isDark ? '#94a3b8' : '#64748b',
                borderColor: 'rgba(234, 88, 12, 0.3)',
                borderWidth: 1,
                padding: 10,
                displayColors: false,
                font: { family: 'Plus Jakarta Sans' },
                callbacks: {
                    label: function(context) {
                        return context.raw + ' order' + (context.raw === 1 ? '' : 's');
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: textColor, font: { family: 'Plus Jakarta Sans', size: 10 } }
            },
            y: {
                grid: { color: gridColor },
                ticks: { color: textColor, font: { family: 'Plus Jakarta Sans', size: 10 }, stepSize: 1 }
            }
        }
    }
});

function dashboardNotifications() {
    return {
        darkMode: document.documentElement.classList.contains('dark'),
        init() {
            this.checkForNewRedemptions();
            setInterval(() => {
                this.darkMode = document.documentElement.classList.contains('dark');
                this.checkForNewRedemptions();
            }, 10000);

            const syncChartTheme = (val) => {
                this.darkMode = val;
                const gridColor = val ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
                const textColor = val ? '#94a3b8' : '#64748b';
                
                if (window.todayHourlyChart) {
                    window.todayHourlyChart.options.scales.y.grid.color = gridColor;
                    window.todayHourlyChart.options.scales.x.ticks.color = textColor;
                    window.todayHourlyChart.options.scales.y.ticks.color = textColor;
                    window.todayHourlyChart.update();
                }
            };

            window.addEventListener('paicafe-theme-change', (event) => {
                syncChartTheme(Boolean(event.detail && event.detail.dark));
            });

            const observer = new MutationObserver(() => {
                syncChartTheme(document.documentElement.classList.contains('dark'));
            });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },
        checkForNewRedemptions() {
            // Point to the dedicated admin API location
            const apiUrl = 'api/get_new_redemptions.php';
            
            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success' && data.new_redemptions && data.new_redemptions.length > 0) {
                        data.new_redemptions.forEach(redemption => {
                            Toastify({
                                text: `🎁 REWARD TRIGGERED\n${redemption.username}: ${redemption.title}`,
                                duration: 10000,
                                gravity: "top",
                                position: "right",
                                style: { 
                                    background: this.darkMode ? "rgba(15, 23, 42, 0.9)" : "white",
                                    border: "1px solid rgba(16, 185, 129, 0.5)",
                                    backdropFilter: "blur(10px)",
                                    color: this.darkMode ? "#f8fafc" : "#1e293b",
                                    fontFamily: "Plus Jakarta Sans",
                                    fontWeight: "800",
                                    borderRadius: "20px",
                                    boxShadow: "0 20px 40px rgba(0,0,0,0.2)"
                                },
                                onClick: function(){ location.href = 'redemptions.php'; }
                            }).showToast();
                        });
                    }
                })
                .catch(error => {
                    console.error('Telemetry Interrupted:', error.message);
                });
        }
    }
}
</script>

<!-- Render Toast messages on page load if set -->
<?php if (!empty($message)): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const dark = document.documentElement.classList.contains('dark');
        Toastify({
            text: "⚙️ SYSTEM UPDATE\n<?= addslashes($message) ?>",
            duration: 6000,
            gravity: "top",
            position: "right",
            style: {
                background: "rgba(16, 185, 129, 0.95)",
                border: "1px solid rgba(255, 255, 255, 0.1)",
                backdropFilter: "blur(12px)",
                color: "#ffffff",
                fontFamily: "Plus Jakarta Sans",
                fontWeight: "800",
                borderRadius: "16px",
                boxShadow: "0 20px 40px rgba(0,0,0,0.2)"
            }
        }).showToast();
    });
</script>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const dark = document.documentElement.classList.contains('dark');
        <?php foreach ($errors as $error): ?>
        Toastify({
            text: "⚠️ ALERT\n<?= addslashes($error) ?>",
            duration: 6000,
            gravity: "top",
            position: "right",
            style: {
                background: "rgba(239, 68, 68, 0.95)",
                border: "1px solid rgba(255, 255, 255, 0.1)",
                backdropFilter: "blur(12px)",
                color: "#ffffff",
                fontFamily: "Plus Jakarta Sans",
                fontWeight: "800",
                borderRadius: "16px",
                boxShadow: "0 20px 40px rgba(0,0,0,0.2)"
            }
        }).showToast();
        <?php endforeach; ?>
    });
</script>
<?php endif; ?>

<?php include 'partials/footer.php'; ?>
