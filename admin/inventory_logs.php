<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// Fetch all inventory logs with item and admin names
$logs = $pdo->query("
    SELECT 
        il.log_date, il.change_type, il.quantity_change, il.old_quantity, il.new_quantity, il.notes,
        ii.name as item_name,
        a.username as admin_username
    FROM inventory_logs il
    JOIN inventory_items ii ON il.inventory_item_id = ii.id
    JOIN admins a ON il.admin_id = a.id
    ORDER BY il.log_date DESC
    LIMIT 200
")->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Inventory Audit</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Change Logs</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Recent stock movement history, limited to the latest 200 records.</p>
        </div>
        <div class="liquid-surface rounded-2xl px-5 py-4 border">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Records</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($logs) ?></p>
        </div>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="p-4">Date</th>
                        <th class="p-4">Item</th>
                        <th class="p-4">Action</th>
                        <th class="p-4">Change</th>
                        <th class="p-4">Stock</th>
                        <th class="p-4">By</th>
                        <th class="p-4">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): 
                        // Style based on change type
                        $action_style = '';
                        if ($log['change_type'] == 'added_stock' || $log['change_type'] == 'created') {
                            $action_style = 'text-green-600';
                            $change_sign = '+';
                        } else {
                            $action_style = 'text-red-600';
                            $change_sign = '-';
                        }
                    ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800 text-sm">
                        <td class="p-4 text-slate-500"><?= date('d M Y, h:i A', strtotime($log['log_date'])) ?></td>
                        <td class="p-4 font-black text-slate-800 dark:text-white"><?= e($log['item_name']) ?></td>
                        <td class="p-4 font-black <?= $action_style ?>"><?= e(ucwords(str_replace('_', ' ', $log['change_type']))) ?></td>
                        <td class="p-4 font-black <?= $action_style ?>"><?= $change_sign . e($log['quantity_change']) ?></td>
                        <td class="p-4 font-mono text-slate-600 dark:text-slate-300"><?= e($log['old_quantity']) ?> &rarr; <?= e($log['new_quantity']) ?></td>
                        <td class="p-4 text-blue-500 font-bold"><?= e($log['admin_username']) ?></td>
                        <td class="p-4 text-slate-500"><?= e($log['notes']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="p-10 text-center text-slate-500">No inventory logs yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
