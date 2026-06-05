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

// --- Fetch Data Conditionally ---
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_approval'")->fetchColumn();
$ready_for_pickup = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'ready_for_pickup'")->fetchColumn();
$todays_revenue = $can_view_reports ? ($pdo->query("SELECT SUM(final_amount) FROM orders WHERE status = 'completed' AND DATE(created_at) = CURDATE()")->fetchColumn() ?? 0) : 0;
$total_products = $pdo->query("SELECT COUNT(*) FROM products WHERE is_available = 1")->fetchColumn();
$recent_orders = $can_manage_orders ? $pdo->query("SELECT * FROM orders ORDER BY updated_at DESC LIMIT 5")->fetchAll() : [];
$recent_redemptions = $can_manage_rewards ? $pdo->query("SELECT u.username, lr.title, rr.redeemed_at FROM reward_redemptions rr JOIN users u ON rr.user_id = u.id JOIN loyalty_rewards lr ON rr.reward_id = lr.id ORDER BY rr.redeemed_at DESC LIMIT 5")->fetchAll() : [];
?>

<style>
    .node-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(234, 88, 12, 0.2);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .node-card:hover {
        background: rgba(234, 88, 12, 0.05);
        border-color: #EA580C;
        box-shadow: 0 0 20px rgba(234, 88, 12, 0.15);
        transform: translateY(-2px);
    }
    .node-icon {
        color: #F97316;
        filter: drop-shadow(0 0 5px rgba(249, 115, 22, 0.4));
    }
    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 8px #10b981;
    }
</style>

<div class="container mx-auto px-4 py-6" x-data="dashboardNotifications()" x-init="init()">
    
    <!-- SYSTEM NODES / PORTAL SWITCHER -->
    <div class="mb-10">
        <div class="flex items-center space-x-2 mb-4">
            <i class="fas fa-network-wired text-orange-600"></i>
            <h2 class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500">System Nodes Access</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Node: Kitchen -->
            <a href="https://paikitchen.paicafe.online" target="_blank" class="node-card rounded-xl p-4 flex items-center justify-between group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-orange-600/10 rounded-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-fire-burner node-icon text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-black text-white uppercase tracking-wider leading-none">Kitchen System</p>
                        <p class="text-[9px] text-gray-500 font-mono mt-1">paikitchen.paicafe.online</p>
                    </div>
                </div>
                <div class="status-dot animate-pulse"></div>
            </a>

            <!-- Node: Table System -->
            <a href="https://paitable.paicafe.online" target="_blank" class="node-card rounded-xl p-4 flex items-center justify-between group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-orange-600/10 rounded-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-chair node-icon text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-black text-white uppercase tracking-wider leading-none">Table View</p>
                        <p class="text-[9px] text-gray-500 font-mono mt-1">paitable.paicafe.online</p>
                    </div>
                </div>
                <div class="status-dot animate-pulse"></div>
            </a>

            <!-- Node: Public Website -->
            <a href="https://paicafe.online" target="_blank" class="node-card rounded-xl p-4 flex items-center justify-between group border-dashed">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-blue-600/10 rounded-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-globe node-icon text-blue-500 text-xl" style="filter: drop-shadow(0 0 5px rgba(59, 130, 246, 0.4));"></i>
                    </div>
                    <div>
                        <p class="text-xs font-black text-white uppercase tracking-wider leading-none">Public Site</p>
                        <p class="text-[9px] text-gray-500 font-mono mt-1">paicafe.online</p>
                    </div>
                </div>
                <i class="fas fa-external-link-alt text-[10px] text-gray-600 group-hover:text-blue-400"></i>
            </a>
        </div>
    </div>

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-black text-white tracking-tighter uppercase">Command Dashboard</h1>
        <div class="bg-orange-600/10 border border-orange-600/20 px-3 py-1 rounded text-[10px] font-mono font-bold text-orange-500 tracking-widest">
            TERMINAL_SECURE
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between border-b-4 border-yellow-400">
            <div><h2 class="text-gray-600 text-sm font-bold uppercase tracking-tighter">Pending Approval</h2><p class="text-3xl font-black"><?= e($pending_orders) ?></p></div>
            <i class="fas fa-hourglass-half fa-2x text-yellow-400 opacity-20"></i>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between border-b-4 border-purple-500">
            <div><h2 class="text-gray-600 text-sm font-bold uppercase tracking-tighter">Ready for Pickup</h2><p class="text-3xl font-black"><?= e($ready_for_pickup) ?></p></div>
            <i class="fas fa-bell fa-2x text-purple-500 opacity-20"></i>
        </div>
        <?php if ($can_view_reports): ?>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between border-b-4 border-emerald-500">
            <div><h2 class="text-gray-600 text-sm font-bold uppercase tracking-tighter">Today's Revenue</h2><p class="text-3xl font-black"><?= number_format($todays_revenue) ?> Ks</p></div>
            <i class="fas fa-coins fa-2x text-emerald-500 opacity-20"></i>
        </div>
        <?php endif; ?>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between border-b-4 border-orange-600">
            <div><h2 class="text-gray-600 text-sm font-bold uppercase tracking-tighter">Active Products</h2><p class="text-3xl font-black"><?= e($total_products) ?></p></div>
            <i class="fas fa-box fa-2x text-orange-600 opacity-20"></i>
        </div>
    </div>
    
    <!-- Recent Orders & Redemptions Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        
        <?php if ($can_manage_orders): ?>
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-black uppercase tracking-tight mb-4 flex items-center">
                <i class="fas fa-receipt mr-2 text-orange-600"></i> Recent Orders
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead><tr class="bg-gray-50"><th class="p-3 text-[10px] uppercase font-black text-gray-400">ID</th><th class="p-3 text-[10px] uppercase font-black text-gray-400">Status</th><th class="p-3 text-[10px] uppercase font-black text-gray-400">Time</th></tr></thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                            <tr><td colspan="3" class="p-4 text-center text-gray-500 font-mono text-xs italic">No transmissions detected.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recent_orders as $order): 
                            $status_colors = [
                                'pending_approval' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 
                                'processing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'ready_for_pickup' => 'bg-purple-100 text-purple-800 border-purple-200', 
                                'completed' => 'bg-green-100 text-green-800 border-green-200',
                                'cancelled' => 'bg-red-100 text-red-800 border-red-200',
                            ];
                            $color = $status_colors[$order['status']] ?? 'bg-gray-100';
                        ?>
                        <tr class="border-b hover:bg-gray-50 transition-colors">
                            <td class="p-3 font-mono font-bold text-sm text-gray-700">#<?= e($order['id']) ?></td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded border <?= $color ?>">
                                    <?= e(str_replace('_', ' ', $order['status'])) ?>
                                </span>
                            </td>
                            <td class="p-3 text-xs text-gray-500 font-mono"><?= date('h:i A', strtotime($order['updated_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($can_manage_rewards): ?>
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-black uppercase tracking-tight mb-4 flex items-center">
                <i class="fas fa-gift mr-2 text-orange-600"></i> Recent Redemptions
            </h2>
            <div class="space-y-3">
                <?php if (empty($recent_redemptions)): ?>
                    <p class="text-gray-500 font-mono text-xs italic p-4 text-center">No reward activations logged.</p>
                <?php else: ?>
                    <?php foreach($recent_redemptions as $r): ?>
                    <div class="flex justify-between items-center p-3 border rounded-lg bg-gray-50 border-gray-100">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-orange-600/10 flex items-center justify-center">
                                <i class="fas fa-user text-[10px] text-orange-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-800 leading-none"><?= e($r['username']) ?></p>
                                <p class="text-[10px] text-orange-600 font-black uppercase mt-1 tracking-tighter"><?= e($r['title']) ?></p>
                            </div>
                        </div>
                        <span class="text-[10px] font-mono text-gray-400"><?= date('h:i A', strtotime($r['redeemed_at'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function dashboardNotifications() {
    return {
        init() {
            this.checkForNewRedemptions();
            setInterval(() => this.checkForNewRedemptions(), 10000);
        },
        checkForNewRedemptions() {
            fetch('/api/get_new_redemptions.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.new_redemptions.length > 0) {
                        data.new_redemptions.forEach(redemption => {
                            Toastify({
                                text: `🎁 NEW REWARD REDEEMED\n${redemption.username}: ${redemption.title}`,
                                duration: 10000,
                                gravity: "top",
                                position: "right",
                                style: { 
                                    background: "linear-gradient(135deg, #EA580C, #F97316)",
                                    fontFamily: "Poppins",
                                    fontWeight: "800",
                                    borderRadius: "8px",
                                    boxShadow: "0 10px 30px rgba(0,0,0,0.3)"
                                },
                                onClick: function(){ location.href = '/admin/redemptions.php'; }
                            }).showToast();
                        });
                    }
                })
                .catch(error => console.error('Signal Interrupted:', error));
        }
    }
}
</script>

<?php include 'partials/footer.php'; ?>