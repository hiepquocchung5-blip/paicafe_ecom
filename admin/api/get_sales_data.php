<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!is_admin_logged_in() || !has_permission('view_reports')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Report access is required.']);
    exit;
}

$start = (string)($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
$end = (string)($_GET['end_date'] ?? date('Y-m-d'));
$valid_date = static function (string $value): bool {
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
};

if (!$valid_date($start) || !$valid_date($end) || $start > $end) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Choose a valid date range.']);
    exit;
}

if ((strtotime($end) - strtotime($start)) > 366 * 86400) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Date range cannot exceed 366 days.']);
    exit;
}

try {
    $sales = $pdo->prepare("SELECT DATE(created_at) date, SUM(final_amount) total FROM orders WHERE status='completed' AND DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date");
    $sales->execute([$start, $end]);
    $types = $pdo->prepare("SELECT COALESCE(order_type,'other') order_type, COUNT(*) order_count FROM orders WHERE status!='cancelled' AND DATE(created_at) BETWEEN ? AND ? GROUP BY order_type ORDER BY order_count DESC");
    $types->execute([$start, $end]);
    $products = $pdo->prepare("SELECT p.name_en, SUM(oi.quantity) total_sold FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id WHERE o.status='completed' AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY p.id,p.name_en ORDER BY total_sold DESC LIMIT 5");
    $products->execute([$start, $end]);
    $summary = $pdo->prepare("SELECT COUNT(*) order_count, COALESCE(SUM(final_amount),0) revenue, COALESCE(AVG(final_amount),0) average_order FROM orders WHERE status='completed' AND DATE(created_at) BETWEEN ? AND ?");
    $summary->execute([$start, $end]);

    echo json_encode([
        'status' => 'success',
        'sales_by_day' => $sales->fetchAll(),
        'order_types' => $types->fetchAll(),
        'top_products' => $products->fetchAll(),
        'summary' => $summary->fetch(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('Sales dashboard API failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Sales data could not be loaded.']);
}
