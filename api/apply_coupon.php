<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';
// ... (include functions.php and start session)

$data = json_decode(file_get_contents('php://input'), true);
$code = strtoupper(trim($data['code'] ?? ''));
$subtotal = $data['subtotal'] ?? 0;

if (empty($code)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a coupon code.']);
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid coupon code.']);
    exit();
}
// ... (Add checks for expiry date and max uses)

$discount = 0;
if ($coupon['discount_type'] === 'percentage') {
    $discount = ($subtotal * $coupon['discount_value']) / 100;
} else {
    $discount = $coupon['discount_value'];
}

echo json_encode([
    'status' => 'success', 
    'discount' => $discount, 
    'message' => 'Coupon applied successfully!'
]);
?>