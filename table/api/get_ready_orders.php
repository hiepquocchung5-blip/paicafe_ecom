<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

try {
    // This query fetches all orders that are 'ready_for_pickup'
    // It joins with the tables table to get the human-readable table number
    $stmt = $pdo->query("
        SELECT 
            o.id as order_id, 
            t.table_number
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.id
        WHERE o.status = 'ready_for_pickup'
        ORDER BY o.updated_at ASC
    ");
    
    $ready_orders = $stmt->fetchAll();
    
    echo json_encode(['status' => 'success', 'orders' => $ready_orders]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>