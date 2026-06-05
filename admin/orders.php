<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

$message = '';
$errors = [];

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        $order_id = $_POST['order_id'];

        if (isset($_POST['approve_order'])) {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ? AND status = 'pending_approval'");
            $stmt->execute([$order_id]);
            $stmt = $pdo->prepare("UPDATE payments SET status = 'approved', processed_by_admin_id = ? WHERE order_id = ?");
            $stmt->execute([$_SESSION['admin_id'], $order_id]);
            $message = "Order #{$order_id} approved.";
        }

        if (isset($_POST['complete_order'])) {
            // Fetch the full order details
            $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch();

            if ($order && $order['status'] !== 'completed') {
                // Update the order's status to 'completed'
                $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
                $stmt->execute([$order_id]);

                // --- NEW: Award Loyalty Points ---
                // Check if the order has a user ID or a guest phone number
                $user_id_for_points = $order['user_id'];
                if (!$user_id_for_points && !empty($order['customer_phone_for_points'])) {
                    // Find the user by the phone number provided at checkout
                    $user_stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
                    $user_stmt->execute([$order['customer_phone_for_points']]);
                    $user_id_for_points = $user_stmt->fetchColumn();
                }

                if ($user_id_for_points) {
                    // Fetch the points conversion rate from settings
                    $points_rate_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'loyalty_points_per_100_kyats'");
                    $points_rate = $points_rate_stmt->fetchColumn() ?? 1;
                    
                    // Calculate points based on the final amount
                    $points_earned = floor($order['final_amount'] / 100) * $points_rate;

                    if ($points_earned > 0) {
                        // Add the points to the user's account
                        $update_points_stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
                        $update_points_stmt->execute([$points_earned, $user_id_for_points]);
                    }
                }
                // --- END of Loyalty Points Logic ---

                // If the order had a table, set it to 'needs_cleaning'
                if ($order['table_id']) {
                    $status_stmt = $pdo->prepare("UPDATE tables SET status = 'needs_cleaning' WHERE id = ?");
                    $status_stmt->execute([$order['table_id']]);
                }
                $message = "Order #{$order_id} completed. Points awarded if applicable.";
            }
        }
        
        if (isset($_POST['cancel_order'])) {
            $order_stmt = $pdo->prepare("SELECT table_id, status FROM orders WHERE id = ?");
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch();
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$order_id]);
            if ($order && $order['table_id'] && $order['status'] === 'in_use') {
                $status_stmt = $pdo->prepare("UPDATE tables SET status = 'free' WHERE id = ?");
                $status_stmt->execute([$order['table_id']]);
            }
            $message = "Order #{$order_id} cancelled.";
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "An error occurred: " . $e->getMessage();
    }
}

// --- Daily Pagination and Filtering Logic ---
$selected_date_str = $_GET['date'] ?? date('Y-m-d');
try {
    $selected_date = new DateTime($selected_date_str);
} catch (Exception $e) {
    $selected_date = new DateTime(); // Default to today on invalid date
}

// Find the first and last order dates for the navigation bounds
$first_order_date = $pdo->query("SELECT MIN(DATE(created_at)) FROM orders")->fetchColumn() ?? date('Y-m-d');
$last_order_date = $pdo->query("SELECT MAX(DATE(created_at)) FROM orders")->fetchColumn() ?? date('Y-m-d');

// Create navigation links
$prev_date_link = (clone $selected_date)->modify('-1 day')->format('Y-m-d');
$next_date_link = (clone $selected_date)->modify('+1 day')->format('Y-m-d');

// --- Corrected SQL Query with Date Filter and Proper Sorting ---
$sql = "
    SELECT 
        o.id, o.user_id, o.table_id, o.total_amount, o.tax_amount, o.final_amount, o.discount_amount,
        o.order_type, o.status, o.created_at, o.updated_at,
        o.delivery_street, o.delivery_city, o.delivery_country,
        o.customer_phone_for_points,
        u.username, u.phone_number,
        t.table_number
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE DATE(o.created_at) = ?
    ORDER BY FIELD(o.status, 'pending_approval', 'ready_for_pickup', 'processing', 'completed', 'cancelled'), o.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$selected_date->format('Y-m-d')]);
$orders = $stmt->fetchAll();
?>

<div class="container mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        
        <h1 class="text-3xl font-bold">Manage Orders</h1>
        <div class="flex items-center space-x-2 bg-gray-100 p-2 rounded-lg shadow-sm">
            <a href="?date=<?= e($first_order_date) ?>" class="px-3 py-2 bg-white rounded-md hover:bg-gray-200" title="First Day">&lt;&lt;&lt;</a>
            <a href="?date=<?= e($prev_date_link) ?>" class="px-3 py-2 bg-white rounded-md hover:bg-gray-200" title="Previous Day">&lt;</a>
            <form class="flex items-center">
                <input type="date" name="date" value="<?= $selected_date->format('Y-m-d') ?>" onchange="this.form.submit()" class="form-input p-2 text-center">
            </form>
            <a href="?date=<?= e($next_date_link) ?>" class="px-3 py-2 bg-white rounded-md hover:bg-gray-200" title="Next Day">&gt;</a>
            <a href="?date=<?= e($last_order_date) ?>" class="px-3 py-2 bg-white rounded-md hover:bg-gray-200" title="Last Day">&gt;&gt;&gt;</a>
            <a href="?" class="px-3 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 ml-2" title="Jump to Today">Today</a>
        </div>
        
    </div>

    <?php if ($message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($message) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md">
         <h2 class="text-2xl font-bold mb-4">
            Displaying Orders for: <span class="text-orange-600"><?= $selected_date->format('l, F j, Y') ?></span>
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100"><th class="p-3">Details</th><th class="p-3">Customer</th><th class="p-3">Destination</th><th class="p-3">Amount</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): 
                        $status_colors = [
                            'pending_approval' => 'bg-yellow-200 text-yellow-800', 'processing' => 'bg-blue-200 text-blue-800',
                            'ready_for_pickup' => 'bg-purple-200 text-purple-800', 'completed' => 'bg-green-200 text-green-800',
                            'cancelled' => 'bg-red-200 text-red-800',
                        ];
                        $color = $status_colors[$order['status']] ?? 'bg-gray-200';
                    ?>
                    <tr class="border-b">
                        <td class="p-3"><div class="font-bold">#<?= e($order['id']) ?></div><div class="text-xs text-gray-500"><?= date('d M Y, h:i A', strtotime($order['updated_at'])) ?></div></td>
                        <td class="p-3"><div class="font-medium"><?= e($order['username'] ?? 'Guest') ?></div><div class="text-xs text-gray-500"><?= e($order['phone_number'] ?? $order['customer_phone_for_points']) ?></div></td>
                        <td class="p-3 text-sm"><?php if ($order['order_type'] === 'web'): ?><strong class="block"><?= e($order['delivery_street']) ?></strong><span><?= e($order['delivery_city']) ?></span><?php else: ?><strong class="block"><?= e($order['table_number'] ?? 'Takeaway') ?></strong><?php endif; ?></td>
                        <td class="p-3 font-semibold"><?= number_format($order['final_amount'], 2) ?> Ks</td>
                        <td class="p-3"><span class="px-3 py-1 text-sm font-semibold rounded-full <?= $color ?>"><?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></span></td>
                        <td class="p-3">
                            <div class="flex items-center space-x-2">
                                <?php if ($order['status'] === 'pending_approval'): ?>
                                    <form method="POST" onsubmit="return confirm('Approve payment?');"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><button type="submit" name="approve_order" class="text-xs bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">Approve</button></form>
                                <?php endif; ?>
                                <?php if ($order['status'] === 'ready_for_pickup'): ?>
                                    <form method="POST" onsubmit="return confirm('Mark as completed?');"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><button type="submit" name="complete_order" class="text-xs bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600">Complete</button></form>
                                <?php endif; ?>
                                <a href="voucher_view.php?order_id=<?= e($order['id']) ?>" target="_blank" class="text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">View</a>
                                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                    <form method="POST" onsubmit="return confirm('Cancel this order?');"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><button type="submit" name="cancel_order" class="text-xs bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Cancel</button></form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>