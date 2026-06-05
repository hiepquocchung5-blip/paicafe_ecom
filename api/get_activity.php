<?php
// Set the content type to JSON for API responses
header('Content-Type: application/json');

// FIX: Use the correct relative paths to go up from /api/ to /includes/
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// --- SECURITY CHECK ---
// We must start the session to check admin login status
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!is_admin_logged_in()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// --- Pagination Logic ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$logs_per_page = 15; // Show 15 logs at a time in the feed
$offset = ($page - 1) * $logs_per_page;

try {
    // Get total count for pagination
    $total_logs_stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs");
    $total_logs = $total_logs_stmt->fetchColumn();
    $total_pages = ceil($total_logs / $logs_per_page);

    // Fetch the paginated logs
    $stmt = $pdo->prepare("
        SELECT a.action, a.log_time, ad.username
        FROM activity_logs a
        LEFT JOIN admins ad ON a.admin_id = ad.id
        ORDER BY a.log_time DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $logs_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send a successful response
    echo json_encode([
        'status' => 'success',
        'activities' => $activities,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $total_pages,
            'totalLogs' => $total_logs
        ]
    ]);

} catch (Exception $e) {
    // Send a server error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'A database error occurred: ' . $e->getMessage()
    ]);
}
?>
