<?php
header('Content-Type: application/json');
// FIX: Added correct paths and admin login check
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';
require_admin_login(); // <-- This is essential

$phone = $_GET['phone'] ?? '';

if (empty($phone)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Phone number is required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT username, loyalty_points FROM users WHERE phone_number = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'not_found', 'message' => 'No user found with this phone number.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    // Send a generic message to the user, but log the specific error
    error_log($e->getMessage()); 
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>