<?php
header('Content-Type: application/json');
require_once __DIR__ .'/../../includes/db_connect.php';
require_once __DIR__ .'/../../includes/functions.php'; // For session handling

// Secure this endpoint to ensure only logged-in admins can use it
if (!is_admin_logged_in()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$data = is_array($data) ? $data : [];

if (!verify_csrf_token($data['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token. Please refresh and try again.']);
    exit();
}

$cart = $data['cart'] ?? [];
$payment_method = $data['payment_method'] ?? 'Cash';
$customer_phone = $data['customer_phone'] ?? null;
$coupon_code = strtoupper(trim($data['coupon_code'] ?? ''));

if (empty($cart)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Cart cannot be empty.']);
    exit();
}

$pdo->beginTransaction();
try {
    // 1. Calculate totals from the database to ensure price accuracy
    $product_ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, price, discount_percentage FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products_lookup = [];
    foreach ($db_products as $row) {
        $products_lookup[$row['id']] = $row;
    }

    $subtotal = 0;
    $final_prices = [];
    foreach ($cart as $product_id => $item) {
        if (!isset($products_lookup[$product_id])) {
            throw new Exception("Product with ID {$product_id} not found.");
        }
        $base_price = $products_lookup[$product_id]['price'];
        $discount = $products_lookup[$product_id]['discount_percentage'];
        $final_price = $base_price - ($base_price * ($discount / 100));
        
        $subtotal += $final_price * $item['quantity'];
        $final_prices[$product_id] = $final_price;
    }

    $discount_amount = 0;
    $applied_coupon_code = null;
    if ($coupon_code !== '') {
        $coupon_result = validate_coupon($pdo, $coupon_code, $subtotal);
        if ($coupon_result['status'] !== 'success') {
            throw new Exception('The selected voucher is no longer valid.');
        }

        $discount_amount = $coupon_result['discount'];
        $applied_coupon_code = $coupon_code;
    }

    $tax_rate_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_percentage'");
    $tax_rate = (float)$tax_rate_stmt->fetchColumn();
    $taxable_amount = max(0, $subtotal - $discount_amount);
    $tax_amount = ($taxable_amount * $tax_rate) / 100;
    $final_amount = $taxable_amount + $tax_amount;

    // 2. Find the user ID if a phone number is provided
    $user_id = null;
    if (!empty($customer_phone)) {
        $user_stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
        $user_stmt->execute([$customer_phone]);
        $user_id = $user_stmt->fetchColumn();
    }

    // 3. Insert the order
    $order_stmt = $pdo->prepare("
        INSERT INTO orders (user_id, customer_phone_for_points, total_amount, tax_amount, discount_amount, coupon_code, final_amount, order_type, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pos', 'processing')
    ");
    $order_stmt->execute([$user_id, $customer_phone, $subtotal, $tax_amount, $discount_amount, $applied_coupon_code, $final_amount]);
    $order_id = $pdo->lastInsertId();

    // 4. Insert order items
    $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_per_item) VALUES (?, ?, ?, ?)");
    foreach ($cart as $product_id => $item) {
        $item_stmt->execute([$order_id, $product_id, $item['quantity'], $final_prices[$product_id]]);
    }

    // 5. Record the payment
    $payment_stmt = $pdo->prepare("
        INSERT INTO payments (order_id, payment_method, status, amount, processed_by_admin_id) 
        VALUES (?, ?, 'approved', ?, ?)
    ");
    $payment_stmt->execute([$order_id, $payment_method, $final_amount, $_SESSION['admin_id']]);

    if ($applied_coupon_code) {
        $coupon_use_stmt = $pdo->prepare("UPDATE coupons SET uses_count = uses_count + 1 WHERE code = ?");
        $coupon_use_stmt->execute([$applied_coupon_code]);
    }

    // 6. Award loyalty points if a user was found
    if ($user_id) {
        $settings_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'loyalty_points_per_100_kyats'");
        $points_rate = $settings_stmt->fetchColumn();
        $points_to_add = floor($final_amount / 100) * $points_rate;
        
        if ($points_to_add > 0) {
            $user_points_stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
            $user_points_stmt->execute([$points_to_add, $user_id]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => "Order #{$order_id} created successfully.", 'order_id' => $order_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
