<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$data = json_decode(file_get_contents('php://input'), true);
$combo_id = $data['combo_id'] ?? 0;

if ($combo_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Combo ID.']);
    exit();
}

try {
    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // FIX 1: A combo is added with a `combo_` prefix to distinguish it from regular products
    $cart_key = 'combo_' . $combo_id;

    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]++; // Increment quantity
    } else {
        $_SESSION['cart'][$cart_key] = 1; // Add new combo
    }

    // FIX 2: Correctly calculate the total quantity of all items in the cart for the header count
   $total_quantity = 0;
    foreach ($_SESSION['cart'] as $item_id => $quantity) {
        if (strpos($item_id, 'combo_') === 0) {
            $combo_id = substr($item_id, 6);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM combo_products WHERE combo_id = ?");
            $stmt->execute([$combo_id]);
            $products_in_combo = $stmt->fetchColumn();
            $total_quantity += $products_in_combo * $quantity;
        } else {
            $total_quantity += $quantity;
        }
    }

    echo json_encode([
        'status' => 'success', 
        'message' => 'Combo deal added to your cart!',
        'cart_count' => $total_quantity // Send back the total quantity
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>