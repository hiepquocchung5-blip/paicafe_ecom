<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';
require_once '../includes/functions.php'; // For session handling

// Secure this endpoint to ensure only logged-in admins can use it
if (!is_admin_logged_in()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$cart = $data['cart'] ?? [];
$payment_method = $data['payment_method'] ?? 'Cash';
$customer_phone = $data['customer_phone'] ?? null;

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
    $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $db_products = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $subtotal = 0;
    foreach ($cart as $product_id => $item) {
        if (!isset($db_products[$product_id])) {
            throw new Exception("Product with ID {$product_id} not found.");
        }
        $subtotal += $db_products[$product_id] * $item['quantity'];
    }

    $tax_rate_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tax_percentage'");
    $tax_rate = $tax_rate_stmt->fetchColumn();
    $tax_amount = ($subtotal * $tax_rate) / 100;
    $final_amount = $subtotal + $tax_amount;

    // 2. Find the user ID if a phone number is provided
    $user_id = null;
    if (!empty($customer_phone)) {
        $user_stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
        $user_stmt->execute([$customer_phone]);
        $user_id = $user_stmt->fetchColumn();
    }

    // 3. Insert the order
    $order_stmt = $pdo->prepare("
        INSERT INTO orders (user_id, customer_phone_for_points, total_amount, tax_amount, final_amount, order_type, status) 
        VALUES (?, ?, ?, ?, ?, 'pos', 'processing')
    ");
    $order_stmt->execute([$user_id, $customer_phone, $subtotal, $tax_amount, $final_amount]);
    $order_id = $pdo->lastInsertId();

    // 4. Insert order items
    $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_per_item) VALUES (?, ?, ?, ?)");
    foreach ($cart as $product_id => $item) {
        $item_stmt->execute([$order_id, $product_id, $item['quantity'], $db_products[$product_id]]);
    }

    // 5. Record the payment
    $payment_stmt = $pdo->prepare("
        INSERT INTO payments (order_id, payment_method, status, amount, processed_by_admin_id) 
        VALUES (?, ?, 'approved', ?, ?)
    ");
    $payment_stmt->execute([$order_id, $payment_method, $final_amount, $_SESSION['admin_id']]);

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