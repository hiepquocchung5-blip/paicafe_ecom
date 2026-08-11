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

try {
    $prep_schema_available = ensure_order_item_preparation_schema($pdo);
    $prepared_select = $prep_schema_available
        ? 'CASE WHEN oi.prepared_at IS NOT NULL THEN 1 ELSE 0 END AS is_prepared'
        : '0 AS is_prepared';

    // Pending orders need an explicit kitchen acknowledgement before preparation.
    $stmt = $pdo->prepare("
        SELECT
            o.id, o.table_id, o.order_type, o.status, o.created_at, o.updated_at,
            t.table_number
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.id
        WHERE o.status IN ('pending_approval', 'processing')
        ORDER BY FIELD(o.status, 'pending_approval', 'processing'), o.created_at ASC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll();

    $items_stmt = $pdo->prepare("
        SELECT oi.id, oi.quantity, p.name_en, c.name_en category_name, {$prepared_select}
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");

    $result = [];
    foreach ($orders as $order) {
        $items_stmt->execute([$order['id']]);
        $order['items'] = $items_stmt->fetchAll();
        $order['preparation_tracking'] = $prep_schema_available;
        $result[] = $order;
    }

    echo json_encode($result);

} catch (Throwable $e) {
    error_log('Kitchen feed failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to load the kitchen queue.']);
}
?>
