<?php
function renderOrderTable($orders) {
?>
<table class="w-full text-left">
    <thead>
        <tr class="bg-gray-100"><th class="p-3">Details</th><th class="p-3">Customer</th><th class="p-3">Destination</th><th class="p-3">Amount</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): 
            $status_colors = [
                'pending_approval' => 'bg-yellow-200 text-yellow-800', 'processing' => 'bg-blue-200 text-blue-800',
                'ready_for_pickup' => 'bg-purple-200 text-purple-800', 'completed' => 'bg-green-200 text-green-800',
                'cancelled' => 'bg-red-200 text-red-800',
            ];
            $color = $status_colors[$order['status']] ?? 'bg-gray-200';
        ?>
        <tr class="border-b">
            <td class="p-3"><div class="font-bold">#<?= e($order['id']) ?></div><div class="text-xs text-gray-500"><?= date('d M Y, h:i A', strtotime($order['updated_at'])) ?></div></td>
            <td class="p-3"><div class="font-medium"><?= e($order['username'] ?? 'Guest') ?></div><div class="text-xs text-gray-500"><?= e($order['phone_number'] ?? $order['customer_phone_for_points']) ?></div></td>
            <td class="p-3 text-sm"><?php if ($order['order_type'] === 'web'): ?><strong class="block"><?= e($order['delivery_street']) ?></strong><span><?= e($order['delivery_city']) ?></span><?php else: ?><strong class="block"><?= e($order['table_number'] ?? 'Takeaway') ?></strong><?php endif; ?></td>
            <td class="p-3 font-semibold"><?= number_format($order['final_amount'], 2) ?> Ks</td>
            <td class="p-3"><span class="px-3 py-1 text-sm font-semibold rounded-full <?= $color ?>"><?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></span></td>
            <td class="p-3">
                <div class="flex items-center space-x-2">
                    <?php if ($order['status'] === 'pending_approval'): ?>
                        <form method="POST" onsubmit="return confirm('Approve payment?');"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><button type="submit" name="approve_order" class="text-xs bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">Approve</button></form>
                    <?php endif; ?>
                    <?php if ($order['status'] === 'ready_for_pickup'): ?>
                        <form method="POST" onsubmit="return confirm('Mark as completed?');"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><button type="submit" name="complete_order" class="text-xs bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600">Complete</button></form>
                    <?php endif; ?>
                    <a href="voucher_view.php?order_id=<?= e($order['id']) ?>" target="_blank" class="text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">View</a>
                    <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                        <form method="POST" onsubmit="return confirm('Cancel this order?');"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><button type="submit" name="cancel_order" class="text-xs bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Cancel</button></form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php } ?>