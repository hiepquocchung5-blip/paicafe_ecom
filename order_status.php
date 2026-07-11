<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = null;
$order_items = [];
$table_number = null;
$points_earned = 0;

if ($order_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order) {
        if (is_user_logged_in() && $order['user_id'] != $_SESSION['user_id']) {
            $order = null; 
        } else {
            $items_stmt = $pdo->prepare("SELECT oi.quantity, oi.price_per_item, p.name_en FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $items_stmt->execute([$order_id]);
            $order_items = $items_stmt->fetchAll();
            
            if ($order['table_id']) {
                $table_stmt = $pdo->prepare("SELECT table_number FROM tables WHERE id = ?");
                $table_stmt->execute([$order['table_id']]);
                $table_number = $table_stmt->fetchColumn();
            }

            if ($order['user_id']) {
                $points_rate_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'loyalty_points_per_100_kyats'");
                $points_rate = $points_rate_stmt->fetchColumn() ?? 1;
                $points_earned = floor($order['final_amount'] / 100) * $points_rate;
            }
        }
    if (isset($_SESSION['table_id'])) {
        $was_qr_order = true; // Mark that this was a QR order
        unset($_SESSION['table_id']); // Clear the table ID from the session
    }
    }
}

include 'includes/header.php';
?>

<div class="max-w-md mx-auto px-4 sm:px-0" x-data="{ showShareModal: false }">
  <?php if ($order): ?>
    <div class="text-center mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Thank You!</h1>
      <p class="text-gray-600">Your order has been placed successfully.</p>
    </div>

    <div class="voucher-print-area bg-gradient-to-br from-orange-50 to-amber-100 p-4 sm:p-6 rounded-lg shadow-lg border-t-4 border-orange-500">
      <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-orange-900">Paicafe Voucher</h2>
        <p class="text-sm text-orange-800">Order Receipt</p>
      </div>
      <div class="border-t border-b border-dashed border-orange-300 py-4">
        <div class="flex justify-between text-orange-900 mb-2 text-sm sm:text-base">
          <span>Order ID:</span><span class="font-bold">#<?= e($order['id']) ?></span>
        </div>
        <div class="flex justify-between text-orange-900 text-sm sm:text-base">
          <span>Date:</span><span class="font-semibold"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
        </div>
        <?php if ($table_number): ?>
          <div class="flex justify-between text-orange-900 mt-2 text-sm sm:text-base">
            <span>Table:</span><span class="font-bold text-lg"><?= e($table_number) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <div class="py-4 overflow-x-auto">
        <h3 class="font-semibold text-orange-900 mb-2 text-base sm:text-lg">Items Ordered:</h3>
        <table class="w-full text-left text-orange-800 text-sm sm:text-base min-w-[300px]">
          <tbody>
            <?php foreach ($order_items as $item): ?>
              <tr class="border-b border-dashed border-orange-200">
                <td class="py-2"><?= e($item['quantity']) ?>x <?= e($item['name_en']) ?></td>
                <td class="text-right"><?= number_format($item['price_per_item'] * $item['quantity'], 2) ?> Ks</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="border-t-2 border-orange-300 pt-4 text-sm sm:text-base">
        <div class="flex justify-between text-orange-900"><span>Subtotal:</span><span><?= number_format($order['total_amount'], 2) ?> Ks</span></div>
        <div class="flex justify-between text-red-500"><span>Discount:</span><span>-<?= number_format($order['discount_amount'], 2) ?> Ks</span></div>
        <div class="flex justify-between text-orange-900"><span>Tax:</span><span><?= number_format($order['tax_amount'], 2) ?> Ks</span></div>
        <div class="flex justify-between font-bold text-xl text-orange-900 mt-2"><span>Total:</span><span><?= number_format($order['final_amount'], 2) ?> Ks</span></div>
      </div>

      <?php if ($points_earned > 0): ?>
        <div class="mt-4 pt-4 border-t-2 border-dashed border-orange-300 text-center">
          <p class="font-semibold text-green-700">You earned</p>
          <p class="text-3xl font-bold text-green-600"><?= e($points_earned) ?> Points</p>
          <p class="text-xs text-gray-500">from this order!</p>
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-8 flex justify-center space-x-4">
      <button onclick="window.print()" class="btn-outline flex items-center px-3 py-2 space-x-2 rounded border border-orange-500 text-orange-600 hover:bg-orange-100 transition">
        <i class="fas fa-print text-lg"></i>
        <span class="hidden sm:inline">Save Voucher</span>
      </button>
      <button @click="showShareModal = true" class="btn-brand flex items-center px-3 py-2 space-x-2 rounded bg-orange-600 text-white hover:bg-orange-700 transition">
        <i class="fas fa-share-alt text-lg"></i>
        <span class="hidden sm:inline">Share</span>
      </button>
      <a href="/menu.php" class="ml-4 inline-block text-gray-600 hover:text-orange-600 font-semibold">Order More</a>
    </div>

    <div x-show="showShareModal" @keydown.escape.window="showShareModal = false" 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" 
         style="display: none;">
      <div @click.away="showShareModal = false" 
           class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 text-center">
        <h3 class="text-2xl font-bold mb-4">Share the Love!</h3>
        <p class="text-gray-600 mb-6">Let your friends know about your order.</p>
        <div class="flex justify-center space-x-4">
          <?php
            $share_text = "Just had a great meal at Paicafe!";
            $share_url = "https://paicafes.com/order_status.php?order_id=" . $order_id;
          ?>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($share_url) ?>&quote=<?= urlencode($share_text) ?>" target="_blank" class="flex items-center justify-center w-16 h-16 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-colors text-2xl">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a href="https://api.whatsapp.com/send?text=<?= urlencode($share_text . ' Check it out: ' . $share_url) ?>" target="_blank" class="flex items-center justify-center w-16 h-16 bg-green-500 text-white rounded-full hover:bg-green-600 transition-colors text-2xl">
            <i class="fab fa-whatsapp"></i>
          </a>
        </div>
        <button @click="showShareModal = false" class="mt-6 text-gray-500 hover:underline">Close</button>
      </div>
    </div>
  <?php else: ?>
    <div class="bg-white p-8 rounded-lg shadow-md text-center max-w-md mx-auto">
      <h1 class="text-2xl font-bold text-red-600">Order Not Found</h1>
      <p class="text-gray-600 mt-2">Sorry, we couldn't find this order or you don't have permission to view it.</p>
    </div>
  <?php endif; ?>
  <?php if ($was_qr_order): ?>
        <script>
            // This script runs after the page has loaded
            window.addEventListener('load', function() {
                // Replace the current history state with the homepage URL
                // This prevents the user from using the "back" button to return here
                history.replaceState(null, '', '/home.php');
            });
        </script>
        <?php endif; ?>
</div>



<?php include 'includes/footer.php'; ?>