<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php';

// --- Permission Check ---
if (!has_permission('manage_orders')) {
    die('Access Denied. You do not have permission to manage orders.');
}

$prep_tracking_available = ensure_order_item_preparation_schema($pdo);

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

            if ($order && $order['status'] === 'ready_for_pickup') {
                $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND status = 'ready_for_pickup'");
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
            } else {
                throw new RuntimeException('Only an order marked ready for pickup can be completed.');
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

$prepared_units_sql = $prep_tracking_available
    ? 'SUM(CASE WHEN prepared_at IS NOT NULL THEN quantity ELSE 0 END)'
    : '0';
$sql = "
    SELECT
        o.*, u.username, u.phone_number, t.table_number,
        COALESCE(kp.total_items, 0) total_items,
        COALESCE(kp.prepared_items, 0) prepared_items
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN (
        SELECT order_id, SUM(quantity) total_items, {$prepared_units_sql} prepared_items
        FROM order_items
        GROUP BY order_id
    ) kp ON kp.order_id = o.id
    WHERE DATE(o.created_at) = ?
    ORDER BY
        FIELD(o.status, 'pending_approval', 'processing', 'ready_for_pickup', 'completed', 'cancelled'),
        CASE WHEN o.status IN ('pending_approval', 'processing', 'ready_for_pickup') THEN o.created_at END ASC,
        o.created_at DESC
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
            <h1 class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter leading-none">Order Management</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Overseeing orders for <?= $selected_date->format('l, F j') ?>.</p>
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
            <a href="?date=<?= e($selected_date->format('Y-m-d')) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500 hover:text-white transition-all" title="Refresh kitchen progress" aria-label="Refresh kitchen progress">
                <i class="fas fa-rotate text-xs"></i>
            </a>
        </div>
    </div>

    <!-- Feedback Messages -->
    <?php if ($message): ?>
        <div class="mb-8 p-5 rounded-2xl border flex items-center space-x-4 <?= $message_type === 'success' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' : 'bg-red-500/10 border-red-500/20 text-red-500' ?>">
            <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
            <p class="text-xs font-black uppercase tracking-widest"><?= e($message) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$prep_tracking_available): ?>
        <div class="mb-8 p-5 rounded-2xl border flex items-center space-x-4 bg-amber-500/10 border-amber-500/20 text-amber-600">
            <i class="fas fa-triangle-exclamation"></i>
            <p class="text-xs font-black uppercase tracking-widest">Kitchen item progress is temporarily unavailable. Order statuses remain available.</p>
        </div>
    <?php endif; ?>

    <!-- Orders Terminal -->
    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl shadow-slate-200/50 dark:shadow-none">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Table / Order</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Customer</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Service Mode</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Kitchen Progress</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Total Price</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Status</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em] text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="p-20 text-center">
                                <div class="w-16 h-16 rounded-full bg-orange-500/10 flex items-center justify-center text-orange-500 mx-auto mb-4 animate-pulse">
                                    <i class="fas fa-mug-hot text-2xl"></i>
                                </div>
                                <p class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">No orders found</p>
                                <p class="text-xs text-slate-400 mt-1">There are no orders registered for this date.</p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $order): 
                        $status_styles = [
                            'pending_approval' => 'text-yellow-500 bg-yellow-500/10 border-yellow-500/20',
                            'processing' => 'text-orange-500 bg-orange-500/10 border-orange-500/20',
                            'ready_for_pickup' => 'text-purple-500 bg-purple-500/10 border-purple-500/20',
                            'completed' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
                            'cancelled' => 'text-red-500 bg-red-500/10 border-red-500/20',
                        ];
                        $status_labels = [
                            'pending_approval' => 'Pending',
                            'processing' => 'Preparing',
                            'ready_for_pickup' => 'Ready for pickup',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ];
                        $style = $status_styles[$order['status']] ?? 'text-slate-400 bg-slate-400/10 border-slate-400/20';
                        $total_items = (int)$order['total_items'];
                        $prepared_items = (int)$order['prepared_items'];
                        if (in_array($order['status'], ['ready_for_pickup', 'completed'], true)) {
                            $prepared_items = $total_items;
                        }
                        $remaining_items = max(0, $total_items - $prepared_items);
                        $prep_percent = $total_items > 0 ? (int)round(($prepared_items / $total_items) * 100) : 0;
                        if ($order['table_number']) {
                            $table_label = trim((string)$order['table_number']);
                            $order_location = preg_match('/^table\b/i', $table_label) ? $table_label : 'Table ' . $table_label;
                        } elseif ($order['order_type'] === 'pos') {
                            $order_location = 'Counter';
                        } else {
                            $order_location = 'Online';
                        }
                    ?>
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-200">
                        <td class="p-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-2 h-2 rounded-full <?= strpos($order['status'], 'pending') !== false ? 'bg-orange-600 animate-ping' : 'bg-slate-300 dark:bg-slate-700' ?>"></div>
                                <div>
                                    <p class="text-sm font-black text-slate-800 dark:text-white group-hover:text-orange-600 transition-colors"><?= e($order_location) ?></p>
                                    <p class="text-[10px] font-mono font-bold text-orange-500 uppercase mt-0.5">Order #<?= sprintf("%05d", $order['id']) ?></p>
                                    <p class="text-[9px] font-mono text-slate-400 dark:text-slate-500 uppercase mt-0.5"><?= date('H:i:s', strtotime($order['updated_at'])) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase leading-none"><?= e($order['username'] ?? 'Guest') ?></p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-mono"><?= e($order['phone_number'] ?? $order['customer_phone_for_points'] ?: 'No Phone') ?></p>
                        </td>
                        <td class="p-6">
                            <?php if ($order['order_type'] === 'web'): ?>
                                <div class="mb-1">
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full bg-blue-500/10 text-blue-600 text-[9px] font-black uppercase tracking-wider">
                                        <i class="fas fa-truck text-[9px]"></i> <span>Delivery</span>
                                    </span>
                                </div>
                                <p class="text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase leading-tight">
                                    <?= e($order['delivery_street']) ?><br>
                                    <span class="text-slate-400"><?= e($order['delivery_city']) ?></span>
                                </p>
                            <?php elseif ($order['order_type'] === 'takeaway'): ?>
                                <div class="mb-1">
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-600 text-[9px] font-black uppercase tracking-wider">
                                        <i class="fas fa-bag-shopping text-[9px]"></i> <span>Takeaway</span>
                                    </span>
                                </div>
                                <p class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase">Self Pickup</p>
                            <?php else: ?>
                                <div class="mb-1">
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 text-[9px] font-black uppercase tracking-wider">
                                        <i class="fas fa-chair text-[9px]"></i> <span>Dine In</span>
                                    </span>
                                </div>
                                <p class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase">Table <?= e($order['table_number'] ?? 'Takeaway') ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="p-6" style="min-width: 190px">
                            <?php if ($order['status'] === 'cancelled'): ?>
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Cancelled</p>
                            <?php else: ?>
                                <div class="flex items-center justify-between gap-3 mb-2">
                                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                        <?php if ($order['status'] === 'pending_approval'): ?>
                                            <?= $total_items ?> waiting
                                        <?php elseif ($remaining_items > 0): ?>
                                            <?= $remaining_items ?> remaining
                                        <?php else: ?>
                                            All prepared
                                        <?php endif; ?>
                                    </p>
                                    <span class="text-[9px] font-mono font-black text-slate-400"><?= $prep_percent ?>%</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden" role="progressbar" aria-label="Kitchen preparation progress" aria-valuenow="<?= $prep_percent ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full" style="width: <?= $prep_percent ?>%; background-color: <?= $remaining_items === 0 && $total_items > 0 ? '#10b981' : ($order['status'] === 'pending_approval' ? '#f59e0b' : '#f97316') ?>"></div>
                                </div>
                                <p class="text-[9px] text-slate-400 mt-2 font-mono"><?= $prepared_items ?> / <?= $total_items ?> items prepared</p>
                            <?php endif; ?>
                        </td>
                        <td class="p-6">
                            <p class="text-sm font-black text-slate-800 dark:text-white"><?= number_format($order['final_amount']) ?> <span class="text-[10px] text-slate-400 font-normal">KS</span></p>
                        </td>
                        <td class="p-6">
                            <span class="inline-flex items-center px-3 py-1 text-[9px] font-black uppercase rounded-full border tracking-widest <?= $style ?>">
                                <?= e($status_labels[$order['status']] ?? str_replace('_', ' ', $order['status'])) ?>
                            </span>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center space-x-2 opacity-60 group-hover:opacity-100 transition-opacity">
                                <?php if ($order['status'] === 'pending_approval'): ?>
                                    <form method="POST" onsubmit="return confirm('Approve payment for this order?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="approve_order" class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all" title="Approve Payment">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'ready_for_pickup'): ?>
                                    <form method="POST" onsubmit="return confirm('Mark order as completed?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="complete_order" class="w-8 h-8 flex items-center justify-center rounded-lg bg-purple-500/10 text-purple-500 hover:bg-purple-500 hover:text-white transition-all" title="Complete Order">
                                            <i class="fas fa-check-double text-xs"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <a href="voucher_view.php?order_id=<?= e($order['id']) ?>" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white transition-all" title="View Detail">
                                    <i class="fas fa-file-invoice text-xs"></i>
                                </a>

                                <?php if (!in_array($order['status'], ['completed', 'cancelled'])): ?>
                                    <form method="POST" onsubmit="return confirm('Cancel this order?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="cancel_order" class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all" title="Cancel Order">
                                            <i class="fas fa-trash-alt text-xs"></i>
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
