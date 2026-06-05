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
$product_id = $data['product_id'] ?? 0;
$rating = $data['rating'] ?? 0;
$comment = trim($data['comment'] ?? '');
$user_id = $_SESSION['user_id'];

if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$product_id, $user_id, $rating, $comment]);
    
    echo json_encode(['status' => 'success', 'message' => 'Thank you for your review!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error or you may have already reviewed this item.']);
}
?>