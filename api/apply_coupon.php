<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$data = json_decode(file_get_contents('php://input'), true);
$data = is_array($data) ? $data : [];
$result = validate_coupon($pdo, $data['code'] ?? '', $data['subtotal'] ?? 0);

echo json_encode([
    'status' => $result['status'],
    'discount' => $result['discount'] ?? 0,
    'message' => $result['message'],
]);
