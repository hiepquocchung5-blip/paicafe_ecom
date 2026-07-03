<?php
header('Content-Type: application/json');
require_once __DIR__ .'/../../includes/db_connect.php';
require_once __DIR__ .'/../../includes/functions.php'; // For session handling

$data = json_decode(file_get_contents('php://input'), true);
$code = strtoupper(trim($data['code'] ?? ''));
$subtotal = $data['subtotal'] ?? 0;

if (empty($code)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a coupon code.']);
    exit();
}

$stmt = $pdo->prepare("
    SELECT * FROM coupons 
    WHERE code = ? 
      AND is_active = 1
      AND (expiry_date IS NULL OR expiry_date >= CURDATE())
      AND (max_uses IS NULL OR uses_count < max_uses)
    LIMIT 1
");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid, expired, or fully used coupon code.']);
    exit();
}

$discount = 0;
if ($coupon['discount_type'] === 'percentage') {
    $discount = ($subtotal * $coupon['discount_value']) / 100;
} else {
    $discount = $coupon['discount_value'];
}

$discount = min((float)$subtotal, max(0, (float)$discount));

echo json_encode([
    'status' => 'success', 
    'discount' => $discount, 
    'message' => 'Coupon applied successfully!'
]);
?>
