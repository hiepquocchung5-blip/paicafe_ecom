<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Only an authenticated kitchen account can change the kitchen queue.
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'kitchen') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
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

if ($order_id > 0) {
    try {
        if (!ensure_order_item_preparation_schema($pdo)) {
            throw new RuntimeException('Preparation tracking is not available.');
        }

        $pdo->beginTransaction();
        $order_stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? FOR UPDATE");
        $order_stmt->execute([$order_id]);
        $order_status = $order_stmt->fetchColumn();

        if ($order_status !== 'processing') {
            throw new RuntimeException('Order is not currently being prepared.');
        }

        $remaining_stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND prepared_at IS NULL");
        $remaining_stmt->execute([$order_id]);
        if ((int)$remaining_stmt->fetchColumn() > 0) {
            throw new RuntimeException('Complete every item before marking the order ready.');
        }

        $stmt = $pdo->prepare("UPDATE orders SET status = 'ready_for_pickup' WHERE id = ? AND status = 'processing'");
        $stmt->execute([$order_id]);

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Order marked as ready for pickup.']);
        } else {
            throw new RuntimeException('Order is not currently being prepared.');
        }
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Kitchen completion failed for order {$order_id}: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Unable to mark the order ready. Please try again.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Order ID.']);
}
?>
