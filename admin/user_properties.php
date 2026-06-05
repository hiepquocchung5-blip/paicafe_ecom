<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle settings update
    if (isset($_POST['update_settings'])) {
        $points_rate = $_POST['loyalty_points_per_100_kyats'] ?? 1;
        $daily_points = $_POST['daily_login_points'] ?? 5;
        
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$points_rate, 'loyalty_points_per_100_kyats']);
        $stmt->execute([$daily_points, 'daily_login_points']);
        
        $_SESSION['flash_message'] = "Settings updated successfully.";
        $_SESSION['flash_message_type'] = 'success';
    }

    // Handle manual points addition
    if (isset($_POST['add_points'])) {
        $user_id_to_update = $_POST['user_id'] ?? 0;
        $points_to_add = 10;

        $stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
        $stmt->execute([$points_to_add, $user_id_to_update]);
        
        // Log this action
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action) VALUES (?, ?)");
        $log_stmt->execute([$_SESSION['admin_id'], "Added {$points_to_add} points to user ID {$user_id_to_update}"]);

        $_SESSION['flash_message'] = "Successfully added 10 points.";
        $_SESSION['flash_message_type'] = 'success';
    }
    
    header('Location: user_properties.php');
    // exit();
}

// Fetch settings for display
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$points_rate = $settings['loyalty_points_per_100_kyats'] ?? 1;
$daily_login_points = $settings['daily_login_points'] ?? 5;

// Fetch all users with their order count
$users = $pdo->query("
    SELECT u.*, COUNT(o.id) as order_count
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">User Properties & Management</h1>
    
    <!-- Flash Message Display -->
    <?php
    if (isset($_SESSION['flash_message'])) {
        $flash_message = $_SESSION['flash_message'];
        $flash_message_type = $_SESSION['flash_message_type'] ?? 'success';
        echo "<div class='p-4 mb-6 rounded-lg " . ($flash_message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700') . "'><p>" . e($flash_message) . "</p></div>";
        unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
    }
    ?>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <!-- General Settings -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">Points Settings</h2>
            <div class="mb-4">
                <label for="loyalty_points_per_100_kyats" class="block text-gray-700 font-semibold">Points per 100 Ks Spent</label>
                <input type="number" name="loyalty_points_per_100_kyats" value="<?= e($points_rate) ?>" class="form-input mt-1">
            </div>
             <div class="mb-4">
                <label for="daily_login_points" class="block text-gray-700 font-semibold">Daily Login Reward</label>
                <input type="number" name="daily_login_points" value="<?= e($daily_login_points) ?>" class="form-input mt-1">
                <p class="text-sm text-gray-500 mt-1">Points awarded for a user's first login of the day.</p>
            </div>
            <button type="submit" name="update_settings" class="btn-brand">Save Settings</button>
        </div>
    </form>

    <!-- User Management Table -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">User Management</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr class="bg-gray-100"><th class="p-3">User</th><th class="p-3">Address</th><th class="p-3">Stats</th><th class="p-3">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="border-b">
                        <td class="p-3"><div class="font-medium"><?= e($user['username']) ?></div><div class="text-sm text-gray-500"><?= e($user['phone_number']) ?></div></td>
                        <td class="p-3 text-sm"><?= e($user['street_address']) ?>, <?= e($user['city']) ?></td>
                        <td class="p-3 text-center"><div class="font-semibold"><?= e($user['loyalty_points']) ?> pts</div><div class="text-xs text-gray-500"><?= e($user['order_count']) ?> orders</div></td>
                        <td class="p-3">
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="add_points" class="text-xs bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">+10 Points</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>