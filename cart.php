<?php
// By including functions.php first, we guarantee the session has started.
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

$table_number = null; 

// --- QR Code & Session Logic ---
if (isset($_GET['qr_table_id_menu'])) {
    $table_identifier = $_GET['qr_table_id_menu'];
    $stmt = $pdo->prepare("SELECT id, table_number FROM tables WHERE qr_code_identifier = ?");
    $stmt->execute([$table_identifier]);
    $table = $stmt->fetch();
    if ($table) {
        $_SESSION['table_id'] = $table['id'];
        $table_number = $table['table_number'];
        unset($_SESSION['user_id']);
    }
} elseif (isset($_SESSION['table_id'])) {
    $stmt = $pdo->prepare("SELECT table_number FROM tables WHERE id = ?");
    $stmt->execute([$_SESSION['table_id']]);
    $table_number = $stmt->fetchColumn();
}

// --- Cart Update Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_item'])) {
        unset($_SESSION['cart'][$_POST['product_id']]);
    }
    if (isset($_POST['update_quantity'])) {
        $quantity = (int)$_POST['quantity'];
        if ($quantity > 0) {
            $_SESSION['cart'][$_POST['product_id']] = $quantity;
        } else {
            unset($_SESSION['cart'][$_POST['product_id']]);
        }
    }
    header('Location: cart.php');
    exit();
}

// --- Cart Calculation Logic ---
$cart_items = [];
$subtotal = 0;
$discounts = [];
$cart_product_ids_for_upsell = []; // FIX: Define this array before it's used.
if (!empty($_SESSION['cart'])) {
    $product_ids = [];
    $combo_ids = [];

    foreach ($_SESSION['cart'] as $key => $quantity) {
        if (strpos($key, 'combo_') === 0) {
            $combo_ids[] = substr($key, 6);
        } else {
            $product_ids[] = $key;
            $cart_product_ids_for_upsell[] = $key; // Also add to the general list for the upsell query
        }
    }

    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll();
        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            $cart_items[] = ['id' => $product['id'], 'name' => $product['name_en'], 'price' => $product['price'], 'image' => $product['image'], 'quantity' => $quantity, 'is_combo' => false];
            $subtotal += $product['price'] * $quantity;
        }
    }
    
    if (!empty($combo_ids)) {
        $placeholders = implode(',', array_fill(0, count($combo_ids), '?'));
        $stmt = $pdo->prepare("SELECT c.*, SUM(p.price) as original_total, GROUP_CONCAT(p.name_en SEPARATOR ' + ') as product_names FROM combos c JOIN combo_products cp ON c.id = cp.combo_id JOIN products p ON cp.product_id = p.id WHERE c.id IN ($placeholders) GROUP BY c.id");
        $stmt->execute($combo_ids);
        $combos = $stmt->fetchAll();
        foreach ($combos as $combo) {
            $quantity = $_SESSION['cart']['combo_' . $combo['id']];
            $cart_items[] = ['id' => 'combo_' . $combo['id'], 'name' => $combo['name'], 'price' => ($combo['original_total'] - $combo['discount_amount']), 'image' => $combo['image'], 'quantity' => $quantity, 'is_combo' => true, 'description' => $combo['product_names']];
            $subtotal += $combo['original_total'] * $quantity;
            $discounts[] = ['name' => $combo['name'] . ' Discount', 'amount' => $combo['discount_amount'] * $quantity];
        }
    }
}
$final_total = $subtotal - array_sum(array_column($discounts, 'amount'));

// --- Upsell Logic ---
$upsell_products = [];
if (!empty($cart_product_ids_for_upsell)) { // FIX: Use the correct variable
    $placeholders = implode(',', array_fill(0, count($cart_product_ids_for_upsell), '?'));
    $sql = "SELECT p.id, p.name_en, p.price, p.image FROM products p WHERE p.is_available = 1 AND p.id NOT IN ($placeholders) ORDER BY RAND() LIMIT 3";
    $upsell_stmt = $pdo->prepare($sql);
    $upsell_stmt->execute($cart_product_ids_for_upsell);
    $upsell_products = $upsell_stmt->fetchAll();
}

$checkout_link = '/checkout.php';

include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
  <h1 class="text-3xl sm:text-4xl font-bold mb-6 text-center sm:text-left">Your Shopping Cart</h1>

  <?php if (empty($cart_items)): ?>
    <div class="bg-white p-8 rounded-lg shadow-md text-center">
      <i class="fas fa-shopping-cart text-5xl text-gray-300 mb-4"></i>
      <p class="text-xl font-semibold text-gray-700">Your cart is empty.</p>
      <p class="text-gray-500 mt-1">Looks like you haven't added anything yet.</p>
      <a href="/menu.php" class="inline-block mt-6 px-6 py-3 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition">Start Shopping</a>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Cart Items -->
      <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <?php if ($table_number): ?>
          <div class="bg-blue-100 text-blue-700 p-4 mb-6 rounded-lg" role="alert">
            <p class="font-bold">Ordering for: <span class="text-lg"><?= e($table_number) ?></span></p>
          </div>
        <?php endif; ?>

        <?php foreach ($cart_items as $item): ?>
          <div class="flex flex-col sm:flex-row items-center border-b py-4">
            <img src="<?= e($item['image'] ?: '/assets/uploads/placeholder.png') ?>" alt="<?= e($item['name']) ?>" class="w-20 h-20 object-cover rounded-lg mr-0 sm:mr-4 mb-3 sm:mb-0">
            <div class="flex-grow w-full sm:w-auto">
              <h2 class="text-lg font-bold"><?= e($item['name']) ?></h2>
              <?php if ($item['is_combo']): ?>
                <p class="text-xs text-gray-500">Includes: <?= e($item['description']) ?></p>
              <?php endif; ?>
              <p class="text-gray-600 font-semibold"><?= number_format($item['price']) ?> Ks</p>
              <form method="POST" class="mt-2">
                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                <button type="submit" name="remove_item" class="text-red-500 hover:underline text-sm">Remove</button>
              </form>
            </div>

            <div class="flex items-center mt-3 sm:mt-0">
              <form method="POST" class="flex items-center">
                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                <input type="number" name="quantity" value="<?= e($item['quantity']) ?>" min="1" class="w-16 text-center border rounded-md mx-4 py-1" onchange="this.form.submit()" aria-label="Quantity for <?= e($item['name']) ?>">
              </form>
            </div>

            <div class="text-lg font-semibold w-full sm:w-24 text-right mt-2 sm:mt-0"><?= number_format($item['price'] * $item['quantity'], 2) ?> Ks</div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Order Summary -->
      <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-lg shadow-md sticky top-8">
          <h2 class="text-2xl font-bold border-b pb-3 mb-4">Order Summary</h2>
          <div class="flex justify-between mb-2 text-gray-600">
            <span>Subtotal</span>
            <span><?= number_format($subtotal, 2) ?> Ks</span>
          </div>
          <div class="flex justify-between text-red-500">
            <span>Discount</span>
            <span>-<?= number_format(array_sum(array_column($discounts, 'amount')), 2) ?> Ks</span>
          </div>
          <div class="flex justify-between font-bold text-xl border-t pt-3">
            <span>Total</span>
            <span><?= number_format($final_total, 2) ?> Ks</span>
          </div>
          <a href="<?= e($checkout_link) ?>" class="mt-6 btn-brand w-full text-center inline-block px-6 py-3 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition">Proceed to Checkout</a>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($upsell_products)): ?>
    <div class="mt-16">
      <h2 class="text-3xl font-bold mb-6 text-center">You Might Also Like...</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($upsell_products as $product): ?>
          <div class="bg-white p-4 rounded-lg shadow-sm flex items-center transform hover:-translate-y-1 transition-transform">
            <img src="<?= e($product['image'] ?: '/assets/uploads/placeholder.png') ?>" alt="<?= e($product['name_en']) ?>" class="w-20 h-20 object-cover rounded mr-4">
            <div class="flex-grow">
              <a href="product_details.php?id=<?= $product['id'] ?>" class="font-semibold hover:text-orange-600"><?= e($product['name_en']) ?></a>
              <p class="text-sm text-orange-600 font-bold"><?= number_format($product['price']) ?> Ks</p>
            </div>
            <form method="POST">
              <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
              <input type="hidden" name="quantity" value="1">
              <button type="submit" name="update_quantity" class="text-blue-500 hover:underline text-sm font-semibold">Add</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>


<?php include 'includes/footer.php'; ?>