<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

if (!is_user_logged_in()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request body.']);
    exit();
}

$action = $data['action'] ?? '';
$review_id = (int)($data['review_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($review_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid review ID.']);
    exit();
}

// Verify that the review belongs to the user
$check_stmt = $pdo->prepare("SELECT id FROM reviews WHERE id = ? AND user_id = ?");
$check_stmt->execute([$review_id, $user_id]);
if (!$check_stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied: You do not own this review.']);
    exit();
}

if ($action === 'delete') {
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        echo json_encode(['status' => 'success', 'message' => 'Review deleted successfully.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete review.']);
    }
    exit();
} elseif ($action === 'edit') {
    $rating = (int)($data['rating'] ?? 0);
    $comment = trim((string)($data['comment'] ?? ''));
    
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid rating. Rating must be between 1 and 5.']);
        exit();
    }

    $comment_length = function_exists('mb_strlen') ? mb_strlen($comment) : strlen($comment);
    if ($comment_length > 1000) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Review comments must be 1000 characters or fewer.']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
        $stmt->execute([$rating, $comment, $review_id]);
        echo json_encode(['status' => 'success', 'message' => 'Review updated successfully.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update review.']);
    }
    exit();
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    exit();
}
?>
