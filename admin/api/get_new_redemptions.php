<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!is_admin_logged_in()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    // Check for redemptions in the last 10 seconds with 'pending' status
    $stmt = $pdo->prepare("
        SELECT u.username, lr.title 
        FROM reward_redemptions rr
        JOIN users u ON rr.user_id = u.id
        JOIN loyalty_rewards lr ON rr.reward_id = lr.id
        WHERE rr.status = 'pending' AND rr.redeemed_at >= NOW() - INTERVAL 10 SECOND
    ");
    $stmt->execute();
    $new_redemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'new_redemptions' => $new_redemptions]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>