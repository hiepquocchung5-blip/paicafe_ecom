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

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Inventory Change Logs</h1>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3">Date</th>
                        <th class="p-3">Item</th>
                        <th class="p-3">Action</th>
                        <th class="p-3">Change</th>
                        <th class="p-3">Stock Details (Old -> New)</th>
                        <th class="p-3">Performed By</th>
                        <th class="p-3">Notes</th>
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
                    <tr class="border-b text-sm">
                        <td class="p-3 text-gray-600"><?= date('d M Y, h:i A', strtotime($log['log_date'])) ?></td>
                        <td class="p-3 font-medium"><?= e($log['item_name']) ?></td>
                        <td class="p-3 font-semibold <?= $action_style ?>"><?= e(ucwords(str_replace('_', ' ', $log['change_type']))) ?></td>
                        <td class="p-3 font-bold <?= $action_style ?>"><?= $change_sign . e($log['quantity_change']) ?></td>
                        <td class="p-3"><?= e($log['old_quantity']) ?> &rarr; <?= e($log['new_quantity']) ?></td>
                        <td class="p-3 text-blue-600"><?= e($log['admin_username']) ?></td>
                        <td class="p-3 text-gray-500"><?= e($log['notes']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>