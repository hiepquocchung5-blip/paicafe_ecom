<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Secure this endpoint
if (!is_admin_logged_in()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? 0;

if ($order_id > 0) {
    try {
        // FIX: Change the status to 'ready_for_pickup' instead of 'completed'
        $stmt = $pdo->prepare("UPDATE orders SET status = 'ready_for_pickup' WHERE id = ? AND status = 'processing'");
        $stmt->execute([$order_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Order marked as ready for pickup.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Order not found or already completed.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Order ID.']);
}
?>