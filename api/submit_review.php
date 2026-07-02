<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

if (!is_user_logged_in()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to leave a review.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request body.']);
    exit();
}

$product_id = (int)($data['product_id'] ?? 0);
$rating = (int)($data['rating'] ?? 0);
$comment = trim((string)($data['comment'] ?? ''));
$user_id = $_SESSION['user_id'];

if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit();
}

$comment_length = function_exists('mb_strlen') ? mb_strlen($comment) : strlen($comment);
if ($comment_length > 1000) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Review comments must be 1000 characters or fewer.']);
    exit();
}

try {
    $product_stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_available = 1");
    $product_stmt->execute([$product_id]);
    if (!$product_stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Product not found or unavailable.']);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$product_id, $user_id, $rating, $comment]);
    
    echo json_encode(['status' => 'success', 'message' => 'Thank you for your review!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error or you may have already reviewed this item.']);
}
?>
