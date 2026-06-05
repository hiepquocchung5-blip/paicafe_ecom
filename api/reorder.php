<?php
header('Content-Type: application/json');

// FIX: Go up one directory to find the 'includes' folder.
require_once __DIR__ . '/../includes/functions.php'; 
require_once __DIR__ . '/../includes/db_connect.php';

// Only logged-in users can use this feature
if (!is_user_logged_in()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to reorder.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Order ID.']);
    exit();
}

try {
    // Security check: Make sure the order belongs to the current user
    $order_check_stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $order_check_stmt->execute([$order_id, $user_id]);
    if (!$order_check_stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to reorder this.']);
        exit();
    }
    
    // Fetch all items from the old order
    $items_stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $items_stmt->execute([$order_id]);
    $items = $items_stmt->fetchAll();

    if (empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'No items found in this order.']);
        exit();
    }
    
    // Clear the current cart before adding new items
    $_SESSION['cart'] = []; 

    // Add items from the old order to the session cart
    foreach ($items as $item) {
        $_SESSION['cart'][$item['product_id']] = $item['quantity'];
    }

    echo json_encode([
        'status' => 'success', 
        'message' => 'Items from your past order have been added to your cart!',
        'cart_count' => count($_SESSION['cart'])
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>