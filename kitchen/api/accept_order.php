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
$order_id = filter_var($data['order_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID.']);
    exit();
}

try {
    $pdo->beginTransaction();

    $order_stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
    $order_stmt->execute([$order_id]);
    $current_status = $order_stmt->fetchColumn();

    if ($current_status === false) {
        throw new RuntimeException('Order not found.');
    }

    if ($current_status === 'pending_approval') {
        $update_order = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ? AND status = 'pending_approval'");
        $update_order->execute([$order_id]);

        $update_payment = $pdo->prepare("UPDATE payments SET status = 'approved', processed_by_admin_id = ? WHERE order_id = ? AND status = 'pending'");
        $update_payment->execute([$_SESSION['admin_id'], $order_id]);

        log_activity($pdo, "Kitchen accepted Order #{$order_id}");
    } elseif ($current_status !== 'processing') {
        throw new RuntimeException('Order is no longer waiting for acceptance.');
    }

    $pdo->commit();
    echo json_encode([
        'status' => 'success',
        'message' => $current_status === 'processing' ? 'Order was already accepted.' : 'Order accepted.',
        'order_status' => 'processing',
    ]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Kitchen accept failed for order {$order_id}: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to accept the order. Please try again.']);
}
