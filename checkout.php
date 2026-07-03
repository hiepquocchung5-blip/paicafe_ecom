<?php
// By including functions.php first, we guarantee the session is started.
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

$table_number = null; 

// --- Authorization and Session Logic ---
if (!is_user_logged_in() && !isset($_SESSION['table_id'])) {
    header('Location: /login.php'); 
    exit();
}

if (isset($_SESSION['table_id'])) {
    $stmt = $pdo->prepare("SELECT table_number FROM tables WHERE id = ?");
    $stmt->execute([$_SESSION['table_id']]);
    $table_number = $stmt->fetchColumn();
}

if (empty($_SESSION['cart'])) {
    header('Location: /menu.php');
    exit();
}

$is_qr_order = isset($_SESSION['table_id']);
$is_delivery_order = !$is_qr_order;

// --- Fetch User's Saved Address ---
$user_address = null;
if (is_user_logged_in()) {
    $stmt = $pdo->prepare("SELECT street_address, city, country FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_address = $stmt->fetch();
}

// --- Corrected Cart Calculation Logic ---
$subtotal = 0;
$discounts_total = 0;
$products_in_cart = []; // This will hold ALL individual products, including those from combos
$combos_in_cart = [];
if (!empty($_SESSION['cart'])) {
    $product_ids = [];
    $combo_ids = [];
    foreach ($_SESSION['cart'] as $key => $quantity) {
        if (strpos($key, 'combo_') === 0) { $combo_ids[] = substr($key, 6); } 
        else { $product_ids[] = $key; }
    }
    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll();
        foreach ($products as $product) {
            $products_in_cart[$product['id']] = $product; // Store full product data
            $subtotal += $product['price'] * $_SESSION['cart'][$product['id']];
        }
    }
    if (!empty($combo_ids)) {
        $placeholders = implode(',', array_fill(0, count($combo_ids), '?'));
        $stmt = $pdo->prepare("SELECT c.*, SUM(p.price) as original_total FROM combos c JOIN combo_products cp ON c.id = cp.combo_id JOIN products p ON cp.product_id = p.id WHERE c.id IN ($placeholders) GROUP BY c.id");
        $stmt->execute($combo_ids);
        $combos = $stmt->fetchAll();
        foreach ($combos as $combo) {
            $quantity = $_SESSION['cart']['combo_' . $combo['id']];
            $subtotal += $combo['original_total'] * $quantity;
            $discounts_total += $combo['discount_amount'] * $quantity;
            
            // Also fetch the individual products within the combo to add them to the order_items table
            $combo_prods_stmt = $pdo->prepare("SELECT p.* FROM products p JOIN combo_products cp ON p.id = cp.product_id WHERE cp.combo_id = ?");
            $combo_prods_stmt->execute([$combo['id']]);
            while ($p = $combo_prods_stmt->fetch()) {
                $products_in_cart[$p['id']] = $p;
            }
        }
    }
}
$tax_rate_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_percentage'");
$tax_rate = $tax_rate_stmt->fetchColumn();
$taxable_amount = $subtotal - $discounts_total;
$tax_amount = ($taxable_amount * $tax_rate) / 100;
$final_amount = $taxable_amount + $tax_amount;

$errors = [];

// --- Handle Order Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    $transaction_id = $_POST['transaction_id'] ?? null;
    $customer_phone = $_POST['customer_phone'] ?? null;
    
    $delivery_street = trim($_POST['shipping_street'] ?? '');
    $delivery_city = trim($_POST['shipping_city'] ?? '');
    $delivery_country = trim($_POST['shipping_country'] ?? '');

    if ($payment_method !== 'Cash' && empty($transaction_id)) {
        $errors[] = "Transaction ID is required for online payments.";
    }
    
    if ($is_delivery_order && empty($delivery_street)) {
        if ($user_address && !empty($user_address['street_address'])) {
            $delivery_street = $user_address['street_address'];
            $delivery_city = $user_address['city'];
            $delivery_country = $user_address['country'];
        } else {
            $errors[] = "A delivery address is required for this order.";
        }
    }
    
    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            if ($is_qr_order && isset($_SESSION['table_id'])) {
                $status_stmt = $pdo->prepare("UPDATE tables SET status = 'in_use' WHERE id = ? AND status = 'free'");
                $status_stmt->execute([$_SESSION['table_id']]);
            }
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id && !empty($customer_phone)) {
                 $user_stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
                 $user_stmt->execute([$customer_phone]);
                 $user_id = $user_stmt->fetchColumn() ?: null;
            }
            $order_type = $is_qr_order ? 'qr' : 'web';
            $status = ($payment_method === 'Cash') ? 'processing' : 'pending_approval';
            // FIX 1: Pass the correct discount total to the orders table
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, table_id, customer_phone_for_points, total_amount, discount_amount, tax_amount, final_amount, order_type, status, delivery_street, delivery_city, delivery_country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'] ?? null, $_SESSION['table_id'] ?? null, $_POST['customer_phone'] ?? null, $subtotal, $discounts_total, $tax_amount, $final_amount, $is_qr_order ? 'qr' : 'web', $_POST['payment_method'] === 'Cash' ? 'processing' : 'pending_approval', $_POST['shipping_street'] ?? '', $_POST['shipping_city'] ?? '', $_POST['shipping_country'] ?? '']);
            $order_id = $pdo->lastInsertId();

            // FIX 2: Loop through ALL products in the cart, including those from combos
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_per_item) VALUES (?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $key => $quantity) {
                if (strpos($key, 'combo_') === 0) {
                    $combo_id = substr($key, 6);
                    $prods_in_combo_stmt = $pdo->prepare("SELECT product_id FROM combo_products WHERE combo_id = ?");
                    $prods_in_combo_stmt->execute([$combo_id]);
                    $prods = $prods_in_combo_stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach($prods as $prod_id) {
                        $item_stmt->execute([$order_id, $prod_id, $quantity, $products_in_cart[$prod_id]['price']]);
                    }
                } else {
                    $item_stmt->execute([$order_id, $key, $quantity, $products_in_cart[$key]['price']]);
                }
            }
            
            // ... (Payment insertion and redirect logic is the same)
            $pdo->commit();
            header("Location: /order_status.php?order_id=" . $order_id);
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "A critical error occurred: " . $e->getMessage();
        }
    }
}

$online_payment_methods_json = $pdo->query("SELECT setting_value FROM settings WHERE setting_key LIKE 'payment_method_%'")->fetchAll(PDO::FETCH_COLUMN);
$online_payment_methods = array_map('json_decode', $online_payment_methods_json, array_fill(0, count($online_payment_methods_json), true));

include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Checkout</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-8">
                <?php if ($is_delivery_order): ?>
                <div class="bg-white p-6 rounded-lg shadow-md" x-data="{ useDifferentAddress: <?= ($user_address && !empty($user_address['street_address'])) ? 'false' : 'true' ?> }">
                    <h2 class="text-2xl font-bold mb-4">Delivery Address</h2>
                    <?php if ($user_address && !empty($user_address['street_address'])): ?>
        <div class="bg-gray-50 p-4 rounded-lg border">
            <p class="font-semibold">Your saved address:</p>
            <p><?= e($user_address['street_address']) ?>, <?= e($user_address['city']) ?>, <?= e($user_address['country']) ?></p>
            <label class="inline-flex items-center mt-3">
                <input type="checkbox" x-model="useDifferentAddress" class="h-4 w-4">
                <span class="ml-2">Use a different address</span>
            </label>
        </div>
    <?php endif; ?>

    <div x-show="useDifferentAddress" class="mt-4 space-y-2" style="display: none;">
        <input type="text" name="shipping_street" placeholder="Street Address" class="form-input" :required="useDifferentAddress">
        <input type="text" name="shipping_city" placeholder="City" class="form-input" :required="useDifferentAddress">
        <input type="text" name="shipping_country" placeholder="Country" class="form-input" :required="useDifferentAddress">
    </div>
</div>

                <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-md" 
                     x-data="{ 
                         selectedPayment: '<?= $is_qr_order ? 'Cash' : ($online_payment_methods[0]['name'] ?? 'Cash') ?>',
                         customerPhone: '',
                         foundUser: null,
                         lookupMessage: '',
                         debounce: null,
                         lookupUser() {
                             clearTimeout(this.debounce);
                             if (this.customerPhone.length < 9) {
                                 this.foundUser = null;
                                 this.lookupMessage = '';
                                 return;
                             }
                             this.lookupMessage = 'Searching...';
                             this.debounce = setTimeout(() => {
                                 fetch(`/api/get_user_by_phone.php?phone=${encodeURIComponent(this.customerPhone)}`)
                                     .then(res => res.json())
                                     .then(data => {
                                         if (data.status === 'success') {
                                             this.foundUser = data.user;
                                             this.lookupMessage = '';
                                         } else {
                                             this.foundUser = null;
                                             this.lookupMessage = data.message;
                                         }
                                     });
                             }, 500);
                         }
                     }">
                    <h2 class="text-2xl font-bold mb-4">Payment</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block font-semibold">Select Payment Method</label>
                            <div class="space-y-2 mt-2">
                                <?php $is_first_online = true; ?>
                                <?php if ($is_qr_order): ?>
                                <label class="flex items-center p-3 border rounded-lg has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400"><input type="radio" name="payment_method" value="Cash" class="mr-3" x-model="selectedPayment" checked> Pay with Cash</label>
                                <?php endif; ?>
                                <?php foreach ($online_payment_methods as $method): if(!$method['enabled']) continue; ?>
                                <label class="flex items-center p-3 border rounded-lg has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400"><input type="radio" name="payment_method" value="<?= e($method['name']) ?>" class="mr-3" x-model="selectedPayment" <?php if (!$is_qr_order && $is_first_online) { echo 'checked'; $is_first_online = false; } ?>> <?= e($method['name']) ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div x-show="selectedPayment !== 'Cash'" class="border-t pt-4" style="display:none;">
                            <?php foreach ($online_payment_methods as $method): if(!$method['enabled']) continue; ?>
                                <div x-show="selectedPayment === '<?= e($method['name']) ?>'" class="bg-gray-50 p-3 rounded-lg">
                                    <h3 class="font-semibold text-gray-800">Please pay to this <?= e($method['name']) ?> account:</h3>
                                    <p class="mt-1"><strong>Account:</strong> <?= e($method['account_name']) ?></p>
                                    <p><strong>Phone/ID:</strong> <?= e($method['phone']) ?></p>
                                </div>
                            <?php endforeach; ?>
                            <div class="mt-4">
                                <label for="transaction_id" class="block font-medium">Transaction ID</label>
                                <input type="text" name="transaction_id" id="transaction_id" class="form-input mt-1" placeholder="Enter your transaction ID here">
                            </div>
                        </div>
                        <?php if ($is_qr_order): ?>
                        <div>
                            <label for="customer_phone" class="block font-medium">Phone Number (Optional for points)</label>
                            <input type="tel" name="customer_phone" id="customer_phone" class="form-input mt-1" placeholder="Enter phone to get points" x-model="customerPhone" @input.debounce.500ms="lookupUser()">
                            <div class="mt-2 text-sm">
                                <template x-if="foundUser">
                                    <div class="bg-green-100 text-green-800 p-2 rounded-md">
                                        Welcome back, <strong x-text="foundUser.username"></strong>! Current points: <strong x-text="foundUser.loyalty_points"></strong>.
                                    </div>
                                </template>
                                <template x-if="lookupMessage">
                                    <p class="text-gray-500" x-text="lookupMessage"></p>
                                </template>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md sticky top-8">
                    <h2 class="text-2xl font-bold border-b pb-3 mb-4">Order Summary</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between"><span>Subtotal</span><span><?= number_format($subtotal, 2) ?> Ks</span></div>
                        <div class="flex justify-between text-red-500"><span>Combo Discount</span><span>-<?= number_format($discounts_total, 2) ?> Ks</span></div>
                        <div class="flex justify-between"><span>Tax (<?= e($tax_rate) ?>%)</span><span><?= number_format($tax_amount, 2) ?> Ks</span></div>
                        <div class="flex justify-between font-bold text-xl border-t pt-3"><span>Total</span><span><?= number_format($final_amount, 2) ?> Ks</span></div>
                    </div>
                    <button type="submit" class="w-full btn-brand py-3 mt-6">Place Order</button>
                </div>

                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
