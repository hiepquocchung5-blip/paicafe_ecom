<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

try {
    // Fetch all available products with their category name
    $stmt = $pdo->query("
        SELECT p.id, p.name_en, p.price, p.image, c.name_en as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_available = 1
        ORDER BY c.name_en, p.name_en
    ");
    
    $products = $stmt->fetchAll();
    
    echo json_encode(['status' => 'success', 'products' => $products]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>