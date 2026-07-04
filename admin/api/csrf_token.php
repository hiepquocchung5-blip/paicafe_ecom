<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/functions.php';

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Your admin session expired. Please sign in again.',
    ]);
    exit();
}

echo json_encode([
    'status' => 'success',
    'csrf_token' => csrf_token(),
]);
exit();
