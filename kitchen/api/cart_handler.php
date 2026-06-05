<?php
// Add these two lines for debugging to see the real error
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// FIX 1: Correct the path to go up one directory to find the 'includes' folder.
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$response = ['status' => 'error', 'message' => 'Invalid request'];

// This part of your code is likely correct, but the db_connect was failing.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $product_id = $data['product_id'] ?? 0;

    if ($action === 'add' && $product_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_available = 1");
            $stmt->execute([$product_id]);
            if ($stmt->fetch()) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]++;
                } else {
                    $_SESSION['cart'][$product_id] = 1;
                }
                // Recalculate cart count for accuracy
                $cart_item_count = 0;
                foreach($_SESSION['cart'] as $quantity) {
                    $cart_item_count += $quantity;
                }
                // Use the new total quantity for the count
                $response = ['status' => 'success', 'message' => 'Item added to cart.', 'cart_count' => $cart_item_count];

            } else {
                $response['message'] = 'Product not available.';
            }
        } catch(PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>