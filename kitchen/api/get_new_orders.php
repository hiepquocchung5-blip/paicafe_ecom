<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

try {
    // Fetch orders that are in the 'processing' state
    $stmt = $pdo->prepare("
        SELECT 
            o.id, o.table_id, t.table_number
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.id
        WHERE o.status = 'processing'
        ORDER BY o.created_at ASC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    $result = [];
    foreach ($orders as $order) {
        $items_stmt = $pdo->prepare("
            SELECT oi.quantity, p.name_en
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$order['id']]);
        $order['items'] = $items_stmt->fetchAll();
        $result[] = $order;
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>