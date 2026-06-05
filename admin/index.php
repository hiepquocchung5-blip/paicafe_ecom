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
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;400;600;800&display=swap');
    
    .premium-glass {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
    }

    .dark .premium-glass {
        background: rgba(30, 41, 59, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    }

    .node-card {
        background: rgba(0, 0, 0, 0.02);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dark .node-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .node-card:hover {
        background: rgba(234, 88, 12, 0.08);
        border-color: rgba(234, 88, 12, 0.3);
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.2);
    }

    .dark .node-card:hover {
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5);
    }

    .stat-card {
        position: relative;
        overflow: hidden;
    }

    .stat-card::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(234, 88, 12, 0.05) 0%, transparent 70%);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .stat-card:hover::after {
        opacity: 1;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.1);
        border-radius: 10px;
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.1);
    }
</style>

<div class="max-w-7xl mx-auto px-6 py-10" x-data="dashboardNotifications()" x-init="init()">
    
    <!-- TOP NAVIGATION NODES -->
    <div class="mb-12">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-1.5 h-6 bg-orange-600 rounded-full"></div>
            <h2 class="text-xs font-black uppercase tracking-[0.4em] text-slate-500 dark:text-gray-500">Live Ecosystem Nodes</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <!-- Node: Kitchen -->
            <a href="https://paikitchen.paicafe.online" target="_blank" class="node-card premium-glass rounded-2xl p-5 flex items-center justify-between group">
                <div class="flex items-center space-x-5">
                    <div class="w-12 h-12 bg-orange-600/10 rounded-xl flex items-center justify-center group-hover:bg-orange-600 group-hover:rotate-[360deg] transition-all duration-700">
                        <i class="fas fa-fire-burner text-orange-500 text-xl group-hover:text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">KITCHEN OPS</p>
                        <p class="text-[10px] text-slate-500 dark:text-gray-500 font-mono mt-1 opacity-70 group-hover:opacity-100 transition-opacity">kitchen.paicafe.online</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.5)] animate-pulse"></div>
                </div>
            </a>

            <!-- Node: Table System -->
            <a href="https://paitable.paicafe.online" target="_blank" class="node-card premium-glass rounded-2xl p-5 flex items-center justify-between group">
                <div class="flex items-center space-x-5">
                    <div class="w-12 h-12 bg-blue-600/10 rounded-xl flex items-center justify-center group-hover:bg-blue-600 group-hover:rotate-[360deg] transition-all duration-700">
                        <i class="fas fa-couch text-blue-400 text-xl group-hover:text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">TABLE MATRIX</p>
                        <p class="text-[10px] text-slate-500 dark:text-gray-500 font-mono mt-1 opacity-70 group-hover:opacity-100 transition-opacity">table.paicafe.online</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.5)] animate-pulse"></div>
                </div>
            </a>

            <!-- Node: Public Website -->
            <a href="https://paicafe.online" target="_blank" class="node-card premium-glass rounded-2xl p-5 flex items-center justify-between group border-dashed">
                <div class="flex items-center space-x-5">
                    <div class="w-12 h-12 bg-slate-200 dark:bg-white/5 rounded-xl flex items-center justify-center group-hover:bg-slate-800 dark:group-hover:bg-white group-hover:text-white dark:group-hover:text-black transition-all duration-500">
                        <i class="fas fa-globe text-slate-400 dark:text-gray-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800 dark:text-white tracking-tight">STOREFRONT</p>
                        <p class="text-[10px] text-slate-500 dark:text-gray-500 font-mono mt-1 opacity-70">paicafe.online</p>
                    </div>
                </div>
                <i class="fas fa-arrow-up-right-from-square text-xs text-slate-400 group-hover:text-orange-500 transform group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform"></i>
            </a>
        </div>
    </div>

    <!-- MAIN DASHBOARD HEADER -->
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
        <div>
            <h1 class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter leading-none mb-2">Command Center</h1>
            <p class="text-slate-500 dark:text-gray-500 font-medium">Real-time business telemetry and synchronization.</p>
        </div>
        <div class="flex items-center space-x-3 bg-slate-100 dark:bg-gray-900/80 border border-slate-200 dark:border-white/5 px-5 py-2.5 rounded-2xl premium-glass">
            <div class="w-2 h-2 rounded-full bg-orange-600 animate-ping"></div>
            <span class="text-[10px] font-black font-mono tracking-widest text-orange-500">SYSTEM_UP_STABLE</span>
            <div class="h-4 w-[1px] bg-slate-300 dark:bg-white/10 mx-2"></div>
            <span class="text-[10px] font-mono text-slate-500 dark:text-gray-400 uppercase"><?= date('H:i:s') ?></span>
        </div>
    </div>

    <!-- STATS ENGINE -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div class="stat-card premium-glass p-7 rounded-3xl group border-l-4 border-l-yellow-500/50">
            <p class="text-[10px] font-black text-yellow-500/60 uppercase tracking-widest mb-1">Queue Status</p>
            <h3 class="text-3xl font-black text-slate-800 dark:text-white mb-4"><?= e($pending_orders) ?> <span class="text-xs font-normal text-slate-500 ml-1">PENDING</span></h3>
            <div class="w-full bg-slate-200 dark:bg-white/5 h-1 rounded-full overflow-hidden">
                <div class="bg-yellow-500 h-full w-[<?= min(100, $pending_orders * 10) ?>%]"></div>
            </div>
        </div>
        
        <div class="stat-card premium-glass p-7 rounded-3xl group border-l-4 border-l-purple-500/50">
            <p class="text-[10px] font-black text-purple-500/60 uppercase tracking-widest mb-1">Logistics</p>
            <h3 class="text-3xl font-black text-slate-800 dark:text-white mb-4"><?= e($ready_for_pickup) ?> <span class="text-xs font-normal text-slate-500 ml-1">READY</span></h3>
            <div class="w-full bg-slate-200 dark:bg-white/5 h-1 rounded-full overflow-hidden">
                <div class="bg-purple-500 h-full w-[<?= min(100, $ready_for_pickup * 10) ?>%]"></div>
            </div>
        </div>

        <?php if ($can_view_reports): ?>
        <div class="stat-card premium-glass p-7 rounded-3xl group border-l-4 border-l-emerald-500/50">
            <p class="text-[10px] font-black text-emerald-500/60 uppercase tracking-widest mb-1">Revenue Stream</p>
            <h3 class="text-3xl font-black text-slate-800 dark:text-white mb-4"><?= number_format($todays_revenue) ?> <span class="text-xs font-normal text-slate-500 ml-1">KS</span></h3>
            <div class="flex items-center text-[10px] text-emerald-400 font-bold">
                <i class="fas fa-caret-up mr-1"></i> LIVE TRACKING
            </div>
        </div>
        <?php endif; ?>

        <div class="stat-card premium-glass p-7 rounded-3xl group border-l-4 border-l-orange-600/50">
            <p class="text-[10px] font-black text-orange-600/60 uppercase tracking-widest mb-1">Active Assets</p>
            <h3 class="text-3xl font-black text-slate-800 dark:text-white mb-4"><?= e($total_products) ?> <span class="text-xs font-normal text-slate-500 ml-1">SKUS</span></h3>
            <div class="flex items-center text-[10px] text-orange-500 font-bold">
                <i class="fas fa-check-circle mr-1"></i> SYNCHRONIZED
            </div>
        </div>
    </div>
    
    <!-- DATA TERMINALS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- ORDERS TERMINAL -->
        <?php if ($can_manage_orders): ?>
        <div class="premium-glass rounded-[2rem] overflow-hidden flex flex-col">
            <div class="p-8 border-b border-slate-200 dark:border-white/5 flex items-center justify-between bg-slate-50/50 dark:bg-white/[0.01]">
                <div class="flex items-center space-x-3">
                    <div class="w-2 h-2 rounded-full bg-orange-600 shadow-[0_0_10px_#ea580c]"></div>
                    <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight uppercase">Recent Transmissions</h2>
                </div>
                <a href="orders.php" class="text-[10px] font-black text-orange-500 hover:text-orange-400 uppercase tracking-widest transition-colors">Audit All <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-white/5">
                            <th class="p-4 text-[10px] uppercase font-black text-slate-400 dark:text-gray-500 tracking-[0.2em]">Sequence</th>
                            <th class="p-4 text-[10px] uppercase font-black text-gray-500 tracking-[0.2em]">State</th>
                            <th class="p-4 text-[10px] uppercase font-black text-slate-400 dark:text-gray-500 tracking-[0.2em]">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="custom-scrollbar">
                        <?php if (empty($recent_orders)): ?>
                            <tr><td colspan="3" class="p-8 text-center text-slate-500 dark:text-gray-600 font-mono text-xs uppercase tracking-widest">No active transmissions detected.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recent_orders as $order): 
                            $status_colors = [
                                'pending_approval' => 'text-yellow-600 dark:text-yellow-500 border-yellow-500/20 bg-yellow-500/5', 
                                'processing' => 'text-blue-600 dark:text-blue-400 border-blue-400/20 bg-blue-400/5',
                                'ready_for_pickup' => 'text-purple-600 dark:text-purple-400 border-purple-400/20 bg-purple-400/5', 
                                'completed' => 'text-emerald-600 dark:text-emerald-400 border-emerald-400/20 bg-emerald-400/5',
                                'cancelled' => 'text-red-600 dark:text-red-400 border-red-400/20 bg-red-400/5',
                            ];
                            $color = $status_colors[$order['status']] ?? 'text-slate-500 dark:text-gray-400 border-white/10 bg-white/5';
                        ?>
                        <tr class="group hover:bg-slate-100 dark:hover:bg-white/[0.02] transition-all border-b border-slate-100 dark:border-white/[0.02]">
                            <td class="p-4">
                                <span class="font-mono font-black text-slate-800 dark:text-white group-hover:text-orange-500 transition-colors">#<?= sprintf("%05d", $order['id']) ?></span>
                            </td>
                            <td class="p-4">
                                <span class="px-3 py-1 text-[9px] font-black uppercase rounded-full border <?= $color ?> tracking-widest">
                                    <?= str_replace('_', ' ', $order['status']) ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="text-[10px] text-slate-400 dark:text-gray-500 font-mono group-hover:text-slate-800 dark:group-hover:text-white transition-colors"><?= date('H:i:s', strtotime($order['updated_at'])) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- LOYALTY TERMINAL -->
        <?php if ($can_manage_rewards): ?>
        <div class="premium-glass rounded-[2rem] overflow-hidden flex flex-col">
            <div class="p-8 border-b border-slate-200 dark:border-white/5 flex items-center justify-between bg-slate-50/50 dark:bg-white/[0.01]">
                <div class="flex items-center space-x-3">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_10px_#10b981]"></div>
                    <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tight uppercase">Reward Injections</h2>
                </div>
                <a href="redemptions.php" class="text-[10px] font-black text-emerald-500 hover:text-emerald-400 uppercase tracking-widest transition-colors">Audit All <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="p-6 space-y-4 max-h-[400px] overflow-y-auto custom-scrollbar">
                <?php if (empty($recent_redemptions)): ?>
                    <div class="p-12 text-center">
                        <i class="fas fa-ghost text-4xl text-slate-200 dark:text-white/5 mb-4"></i>
                        <p class="text-slate-400 dark:text-gray-600 font-mono text-xs uppercase tracking-widest">Quiet in the loyalty grid.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($recent_redemptions as $r): ?>
                    <div class="group flex justify-between items-center p-5 border border-slate-200 dark:border-white/5 rounded-2xl bg-slate-50 dark:bg-white/[0.02] hover:bg-slate-100 dark:hover:bg-white/[0.05] transition-all hover:border-emerald-500/30">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20 group-hover:bg-emerald-500 group-hover:rotate-12 transition-all">
                                <i class="fas fa-user-astronaut text-emerald-500 text-sm group-hover:text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-800 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors uppercase"><?= e($r['username']) ?></p>
                                <p class="text-[10px] text-slate-500 dark:text-gray-500 font-bold uppercase mt-1 tracking-tighter"><?= e($r['title']) ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-mono text-slate-400 dark:text-gray-500 group-hover:text-slate-800 dark:group-hover:text-white transition-colors"><?= date('H:i:s', strtotime($r['redeemed_at'])) ?></span>
                            <p class="text-[8px] text-emerald-500 font-black tracking-widest mt-1">REDEEMED</p>
                        </div>
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
        darkMode: localStorage.getItem('darkMode') === 'true',
        init() {
            this.checkForNewRedemptions();
            setInterval(() => {
                this.darkMode = localStorage.getItem('darkMode') === 'true';
                this.checkForNewRedemptions();
            }, 10000);
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

<?php include 'partials/footer.php'; ?>
