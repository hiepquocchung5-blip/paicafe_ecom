<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'kitchen') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Kitchen login required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => 'error', 'message' => 'POST requests only.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = filter_var($data['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$item_id = filter_var($data['item_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$is_prepared = filter_var($data['is_prepared'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if (!$order_id || !$item_id || $is_prepared === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid preparation update.']);
    exit();
}

if (!ensure_order_item_preparation_schema($pdo)) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Preparation tracking is not available.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE order_items oi
        JOIN orders o ON o.id = oi.order_id
        SET oi.prepared_at = CASE WHEN ? = 1 THEN NOW() ELSE NULL END
        WHERE oi.id = ? AND oi.order_id = ? AND o.status = 'processing'
    ");
    $stmt->execute([$is_prepared ? 1 : 0, $item_id, $order_id]);

    if ($stmt->rowCount() === 0) {
        $verify = $pdo->prepare("
            SELECT o.status, CASE WHEN oi.prepared_at IS NOT NULL THEN 1 ELSE 0 END is_prepared
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE oi.id = ? AND oi.order_id = ?
        ");
        $verify->execute([$item_id, $order_id]);
        $current = $verify->fetch();
        if (!$current || $current['status'] !== 'processing') {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'This order is no longer being prepared.']);
            exit();
        }
    }

    echo json_encode([
        'status' => 'success',
        'item_id' => $item_id,
        'is_prepared' => $is_prepared,
    ]);
} catch (Throwable $e) {
    error_log("Kitchen item update failed for order {$order_id}, item {$item_id}: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to update this item. Please try again.']);
}
