<?php
header('Content-Type: application/json');

// FIX: Correct the file paths to go up one directory.
// The database connection must also be included BEFORE functions.php.
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_user_logged_in()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Login required to add favorites.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid product.']);
    exit();
}

try {
    // Check if the favorite already exists
    $stmt = $pdo->prepare("SELECT * FROM user_favorites WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    
    if ($stmt->fetch()) {
        // It exists, so remove it
        $delete_stmt = $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND product_id = ?");
        $delete_stmt->execute([$user_id, $product_id]);
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    } else {
        // It doesn't exist, so add it
        $insert_stmt = $pdo->prepare("INSERT INTO user_favorites (user_id, product_id) VALUES (?, ?)");
        $insert_stmt->execute([$user_id, $product_id]);
        echo json_encode(['status' => 'success', 'action' => 'added']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>