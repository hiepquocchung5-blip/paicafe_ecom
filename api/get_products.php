<?php
header('Content-Type: application/json');
// FIX: Use the correct relative path (../) to find the main includes
require_once __DIR__ . '/../includes/db_connect.php'; 
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

try {
    $products = $pdo->query("
        SELECT p.id, p.name_en, p.price, p.image, c.name_en as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_available = 1
        ORDER BY c.name_en, p.name_en
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'products' => $products]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>