<?php
// We must include functions.php for security and helper functions
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php'; // This includes all the admin UI

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

<!-- This page no longer needs any custom <style> tags or <html>/<body> tags -->
<div class="container mx-auto px-4">
    <?php if ($order): ?>
        
        <!-- 1. SCREEN VIEW (The nice orange voucher) -->
        <div class="max-w-md mx-auto bg-gradient-to-br from-orange-50 to-amber-100 p-6 rounded-lg shadow-lg border-t-4 border-orange-500">
            <div class="text-center mb-6"><h2 class="text-2xl font-bold text-orange-900">Paicafe Voucher</h2><p class="text-sm text-orange-800">Order Receipt</p></div>
            <div class="border-t border-b border-dashed border-orange-300 py-4"><div class="flex justify-between text-orange-900 mb-2"><span>Order ID:</span><span class="font-bold">#<?= e($order['id']) ?></span></div><div class="flex justify-between text-orange-900"><span>Date:</span><span class="font-semibold"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span></div><?php if ($table_number): ?><div class="flex justify-between text-orange-900 mt-2"><span>Table:</span><span class="font-bold text-lg"><?= e($table_number) ?></span></div><?php endif; ?></div>
            <div class="py-4"><h3 class="font-semibold text-orange-900 mb-2">Items Ordered:</h3><table class="w-full text-left text-orange-800"><tbody><?php foreach ($order_items as $item): ?><tr class="border-b border-dashed border-orange-200"><td class="py-2"><?= e($item['quantity']) ?>x <?= e($item['name_en']) ?></td><td class="text-right"><?= number_format($item['price_per_item'] * $item['quantity'], 2) ?> Ks</td></tr><?php endforeach; ?></tbody></table></div>
            <div class="border-t-2 border-orange-300 pt-4">
                <div class="flex justify-between text-orange-900"><span>Subtotal:</span><span><?= number_format($order['total_amount'], 2) ?> Ks</span></div>
                <?php if ($order['discount_amount'] > 0): ?>
                <div class="flex justify-between text-red-500"><span>Discount (<?= e($order['coupon_code']) ?>):</span><span>-<?= number_format($order['discount_amount'], 2) ?> Ks</span></div>
                <?php endif; ?>
                <!-- This is just a preview, so the label is static -->
                <div class="flex justify-between text-orange-900"><span>Tax / Service:</span><span><?= number_format($order['tax_amount'], 2) ?> Ks</span></div>
                <div class="flex justify-between font-bold text-xl text-orange-900 mt-2"><span>Total:</span><span><?= number_format($order['final_amount'], 2) ?> Ks</span></div>
            </div>
        </div>

        <!-- NEW: Print Button Links -->
        <div class="mt-8 text-center space-x-4">
            <!-- This button links to the print page with the label "Tax" -->
            <a href="print_receipt.php?order_id=<?= e($order['id']) ?>&label=Tax" target="_blank" class="btn-outline">
                <i class="fas fa-print mr-2"></i> Print with Tax
            </a>
            <!-- This button links to the print page with the label "Service Charge" -->
            <a href="print_receipt.php?order_id=<?= e($order['id']) ?>&label=Service+Charge" target="_blank" class="btn-brand">
                <i class="fas fa-print mr-2"></i> Print with Service Charge
            </a>
        </div>

    <?php else: ?>
        <div class="bg-white p-8 rounded-lg shadow-md text-center">
            <h1 class="text-2xl font-bold text-red-600">Order Not Found</h1>
            <p class="text-gray-600 mt-2">Sorry, the requested order could not be found.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>