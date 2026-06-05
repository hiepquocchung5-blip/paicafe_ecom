<?php
// We must include functions.php for security and helper functions
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php'; // This includes all the admin UI

// --- Permission Check ---
if (!has_permission('manage_orders')) {
    die('Access Denied. You do not have permission to view vouchers.');
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = null;
$order_items = [];
$table_number = null;

if ($order_id > 0) {
    // Fetch all order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if ($order) {
        $items_stmt = $pdo->prepare("SELECT oi.quantity, oi.price_per_item, p.name_en FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $items_stmt->execute([$order_id]);
        $order_items = $items_stmt->fetchAll();

        if ($order['table_id']) {
            $table_stmt = $pdo->prepare("SELECT table_number FROM tables WHERE id = ?");
            $table_stmt->execute([$order['table_id']]);
            $table_number = $table_stmt->fetchColumn();
        }
    }
}
?>

<div class="max-w-xl mx-auto py-10">
    <?php if ($order): ?>
        
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 transition-colors duration-300">
            <!-- Receipt Header -->
            <div class="bg-slate-950 p-10 text-center relative overflow-hidden">
                <!-- Background decoration -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-orange-600/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-orange-600/5 rounded-full translate-y-1/2 -translate-x-1/2 blur-xl"></div>

                <div class="relative z-10">
                    <div class="w-16 h-16 bg-orange-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-orange-600/20">
                        <i class="fas fa-coffee text-white text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-black tracking-tighter text-white uppercase">Paicafe Online</h2>
                    <p class="text-[10px] font-black text-orange-500 uppercase tracking-[0.3em] mt-2">Audit Certificate</p>
                    
                    <div class="mt-8 flex items-center justify-between px-2">
                        <div class="text-left">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Sequence ID</p>
                            <p class="text-sm font-mono font-bold text-white">#<?= sprintf("%05d", $order['id']) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Temporal Link</p>
                            <p class="text-xs font-mono font-bold text-white"><?= date('d.M.Y / H:i', strtotime($order['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receipt Body -->
            <div class="p-10 space-y-8">
                <?php if ($table_number): ?>
                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Physical Location</span>
                        <span class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight">Table <?= e($table_number) ?></span>
                    </div>
                <?php endif; ?>

                <div>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-1">Asset Manifest</h3>
                    <div class="space-y-4">
                        <?php foreach ($order_items as $item): ?>
                        <div class="flex justify-between items-start group">
                            <div class="flex-grow pr-4">
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 transition-colors"><?= e($item['name_en']) ?></p>
                                <p class="text-[10px] text-slate-400 font-mono"><?= e($item['quantity']) ?> UNIT(S) x <?= number_format($item['price_per_item']) ?> KS</p>
                            </div>
                            <span class="text-sm font-black text-slate-800 dark:text-white"><?= number_format($item['price_per_item'] * $item['quantity']) ?> KS</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pt-8 border-t-2 border-dashed border-slate-100 dark:border-slate-800 space-y-3">
                    <div class="flex justify-between text-xs font-bold">
                        <span class="text-slate-400 uppercase tracking-widest">Subtotal Sum</span>
                        <span class="text-slate-700 dark:text-slate-300"><?= number_format($order['total_amount']) ?> KS</span>
                    </div>
                    
                    <?php if ($order['discount_amount'] > 0): ?>
                    <div class="flex justify-between text-xs font-bold text-emerald-500">
                        <span class="uppercase tracking-widest">Voucher Deduction (<?= e($order['coupon_code']) ?>)</span>
                        <span>-<?= number_format($order['discount_amount']) ?> KS</span>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between text-xs font-bold">
                        <span class="text-slate-400 uppercase tracking-widest">Protocol Tax (5%)</span>
                        <span class="text-slate-700 dark:text-slate-300"><?= number_format($order['tax_amount']) ?> KS</span>
                    </div>

                    <div class="flex justify-between items-end pt-6">
                        <div>
                            <p class="text-[10px] font-black text-orange-600 uppercase tracking-[0.2em] mb-1">Final Valuation</p>
                            <h3 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter leading-none"><?= number_format($order['final_amount']) ?> <span class="text-lg opacity-30">KS</span></h3>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-3 py-1 bg-slate-900 dark:bg-white text-white dark:text-slate-950 rounded-full text-[9px] font-black uppercase tracking-widest shadow-xl">Verified_Link</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Area -->
            <div class="p-8 bg-slate-50 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row gap-4">
                <a href="print_receipt.php?order_id=<?= e($order['id']) ?>&label=Tax" target="_blank" 
                   class="flex-1 bg-slate-900 dark:bg-white text-white dark:text-slate-950 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:scale-[1.02] active:scale-[0.98] transition-all text-center flex items-center justify-center">
                    <i class="fas fa-print mr-2"></i> Print (Tax)
                </a>
                <a href="print_receipt.php?order_id=<?= e($order['id']) ?>&label=Service+Charge" target="_blank" 
                   class="flex-1 bg-orange-600 text-white py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:scale-[1.02] active:scale-[0.98] transition-all text-center flex items-center justify-center shadow-lg shadow-orange-600/20">
                    <i class="fas fa-print mr-2"></i> Print (Svc)
                </a>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="orders.php" class="text-[10px] font-black text-slate-400 hover:text-orange-500 uppercase tracking-widest transition-colors">
                <i class="fas fa-chevron-left mr-1"></i> Return to Terminal
            </a>
        </div>

    <?php else: ?>
        <div class="bg-white dark:bg-slate-900 p-12 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-2xl text-center">
            <div class="w-20 h-20 bg-red-500/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-triangle-exclamation text-red-500 text-3xl"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">LINK SEVERED</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2 font-medium">The requested transmission data does not exist in our network.</p>
            <a href="orders.php" class="inline-block mt-8 px-8 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-950 rounded-2xl font-black text-[10px] uppercase tracking-widest">Back to Hub</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>
