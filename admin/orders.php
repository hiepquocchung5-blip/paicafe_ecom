<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php';

// --- Permission Check ---
if (!has_permission('manage_orders')) {
    die('Access Denied. You do not have permission to manage orders.');
}

$message = '';
$message_type = 'success';
$errors = [];

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        $order_id = (int)($_POST['order_id'] ?? 0);
        if ($order_id <= 0) throw new Exception("Invalid Order ID.");

        if (isset($_POST['approve_order'])) {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ? AND status = 'pending_approval'");
            $stmt->execute([$order_id]);
            $stmt = $pdo->prepare("UPDATE payments SET status = 'approved', processed_by_admin_id = ? WHERE order_id = ?");
            $stmt->execute([$_SESSION['admin_id'], $order_id]);
            $message = "Order #{$order_id} payment approved.";
            log_activity($pdo, "Approved payment for Order #$order_id");
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
                log_activity($pdo, "Completed Order #$order_id");
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
            $message_type = 'error';
            log_activity($pdo, "Cancelled Order #$order_id");
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = $e->getMessage();
    }
}

// --- Filtering Logic ---
$selected_date_str = $_GET['date'] ?? date('Y-m-d');
$selected_date = new DateTime($selected_date_str);

$first_order_date = $pdo->query("SELECT MIN(DATE(created_at)) FROM orders")->fetchColumn() ?? date('Y-m-d');
$last_order_date = $pdo->query("SELECT MAX(DATE(created_at)) FROM orders")->fetchColumn() ?? date('Y-m-d');

$prev_date_link = (clone $selected_date)->modify('-1 day')->format('Y-m-d');
$next_date_link = (clone $selected_date)->modify('+1 day')->format('Y-m-d');

$sql = "
    SELECT 
        o.*, u.username, u.phone_number, t.table_number
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE DATE(o.created_at) = ?
    ORDER BY FIELD(o.status, 'pending_approval', 'ready_for_pickup', 'processing', 'completed', 'cancelled'), o.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$selected_date->format('Y-m-d')]);
$orders = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-orange-600 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.4em] text-slate-500 dark:text-slate-400">Order Management</h2>
            </div>
            <h1 class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter leading-none">Command Center</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Overseeing transmissions for <?= $selected_date->format('l, F j') ?>.</p>
        </div>

        <div class="flex items-center space-x-2 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md p-2 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <a href="?date=<?= e($first_order_date) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-orange-500 transition-all">
                <i class="fas fa-angles-left text-xs"></i>
            </a>
            <a href="?date=<?= e($prev_date_link) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-orange-500 transition-all">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            
            <div class="px-4">
                <input type="date" value="<?= $selected_date->format('Y-m-d') ?>" 
                       onchange="window.location.href='?date=' + this.value"
                       class="bg-transparent border-none font-black text-xs uppercase tracking-widest text-slate-800 dark:text-white focus:ring-0 cursor-pointer">
            </div>

            <a href="?date=<?= e($next_date_link) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-orange-500 transition-all">
                <i class="fas fa-chevron-right text-xs"></i>
            </a>
            <a href="?date=<?= e($last_order_date) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-orange-500 transition-all">
                <i class="fas fa-angles-right text-xs"></i>
            </a>
            <a href="?" class="px-6 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl font-black text-[10px] uppercase tracking-widest transition-all shadow-lg shadow-orange-600/20 ml-2">Today</a>
        </div>
    </div>

    <!-- Feedback Messages -->
    <?php if ($message): ?>
        <div class="mb-8 p-5 rounded-2xl border flex items-center space-x-4 <?= $message_type === 'success' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' : 'bg-red-500/10 border-red-500/20 text-red-500' ?>">
            <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
            <p class="text-xs font-black uppercase tracking-widest"><?= e($message) ?></p>
        </div>
    <?php endif; ?>

    <!-- Orders Terminal -->
    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl shadow-slate-200/50 dark:shadow-none">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Transmission</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Origin</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Destination</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Valuation</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Protocol</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Directive</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="p-20 text-center">
                                <div class="opacity-20 mb-4 text-slate-400 dark:text-white">
                                    <i class="fas fa-radar text-6xl"></i>
                                </div>
                                <p class="text-xs font-mono uppercase tracking-widest text-slate-400">Zero active signals detected for this sector.</p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $order): 
                        $status_styles = [
                            'pending_approval' => 'text-yellow-500 bg-yellow-500/10 border-yellow-500/20', 
                            'processing' => 'text-blue-500 bg-blue-500/10 border-blue-500/20',
                            'ready_for_pickup' => 'text-purple-500 bg-purple-500/10 border-purple-500/20', 
                            'completed' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
                            'cancelled' => 'text-red-500 bg-red-500/10 border-red-500/20',
                        ];
                        $style = $status_styles[$order['status']] ?? 'text-slate-400 bg-slate-400/10 border-slate-400/20';
                    ?>
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-200">
                        <td class="p-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-2 h-2 rounded-full <?= strpos($order['status'], 'pending') !== false ? 'bg-orange-600 animate-ping' : 'bg-slate-300 dark:bg-slate-700' ?>"></div>
                                <div>
                                    <p class="text-sm font-black text-slate-800 dark:text-white group-hover:text-orange-600 transition-colors">#<?= sprintf("%05d", $order['id']) ?></p>
                                    <p class="text-[9px] font-mono text-slate-400 dark:text-slate-500 uppercase"><?= date('H:i:s', strtotime($order['updated_at'])) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase leading-none"><?= e($order['username'] ?? 'Guest Node') ?></p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-mono"><?= e($order['phone_number'] ?? $order['customer_phone_for_points'] ?: 'UNKNOWN_PH') ?></p>
                        </td>
                        <td class="p-6">
                            <?php if ($order['order_type'] === 'web'): ?>
                                <p class="text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase leading-tight">
                                    <?= e($order['delivery_street']) ?><br>
                                    <span class="text-slate-400"><?= e($order['delivery_city']) ?></span>
                                </p>
                            <?php else: ?>
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-couch text-[10px] text-slate-400"></i>
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase"><?= e($order['table_number'] ?? 'Takeaway') ?></p>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="p-6">
                            <p class="text-sm font-black text-slate-800 dark:text-white"><?= number_format($order['final_amount']) ?> <span class="text-[10px] text-slate-400 font-normal">KS</span></p>
                        </td>
                        <td class="p-6">
                            <span class="inline-flex items-center px-3 py-1 text-[9px] font-black uppercase rounded-full border tracking-widest <?= $style ?>">
                                <?= str_replace('_', ' ', $order['status']) ?>
                            </span>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <?php if ($order['status'] === 'pending_approval'): ?>
                                    <form method="POST" onsubmit="return confirm('Authorize protocol?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="approve_order" class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all" title="Approve">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'ready_for_pickup' || $order['status'] === 'processing'): ?>
                                    <form method="POST" onsubmit="return confirm('Execute completion?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="complete_order" class="w-8 h-8 flex items-center justify-center rounded-lg bg-purple-500/10 text-purple-500 hover:bg-purple-500 hover:text-white transition-all" title="Complete">
                                            <i class="fas fa-flag-checkered text-xs"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <a href="voucher_view.php?order_id=<?= e($order['id']) ?>" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white transition-all" title="View Audit">
                                    <i class="fas fa-file-invoice text-xs"></i>
                                </a>

                                <?php if (!in_array($order['status'], ['completed', 'cancelled'])): ?>
                                    <form method="POST" onsubmit="return confirm('Abort transmission?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="cancel_order" class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all" title="Abort">
                                            <i class="fas fa-ban text-xs"></i>
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
</div>

<?php include 'partials/footer.php'; ?>
