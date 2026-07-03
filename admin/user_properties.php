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
    exit();
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

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-sky-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Customer Loyalty</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">User Properties</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Manage loyalty settings and customer point adjustments.</p>
        </div>
        <div class="liquid-surface rounded-2xl px-5 py-4 border">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Customers</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($users) ?></p>
        </div>
    </div>
    
    <!-- Flash Message Display -->
    <?php
    if (isset($_SESSION['flash_message'])) {
        $flash_message = $_SESSION['flash_message'];
        $flash_message_type = $_SESSION['flash_message_type'] ?? 'success';
        echo "<div class='p-4 mb-6 rounded-2xl " . ($flash_message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700') . "'><p class='font-bold'>" . e($flash_message) . "</p></div>";
        unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
    }
    ?>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <!-- General Settings -->
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white">Points Settings</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Changes affect future loyalty awards.</p>
                </div>
                <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-500/10 text-sky-500">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
            <div class="mb-4">
                <label for="loyalty_points_per_100_kyats" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Points per 100 Ks Spent</label>
                <input type="number" name="loyalty_points_per_100_kyats" value="<?= e($points_rate) ?>" class="form-input mt-1">
            </div>
             <div class="mb-4">
                <label for="daily_login_points" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Daily Login Reward</label>
                <input type="number" name="daily_login_points" value="<?= e($daily_login_points) ?>" class="form-input mt-1">
                <p class="text-xs text-slate-500 mt-2">Points awarded for a user's first login of the day.</p>
            </div>
            <button type="submit" name="update_settings" class="btn-brand">Save Settings</button>
        </div>
    </form>

    <!-- User Management Table -->
    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">User Management</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr><th class="p-4">User</th><th class="p-4">Address</th><th class="p-4 text-center">Stats</th><th class="p-4 text-right">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-4"><div class="font-black text-slate-800 dark:text-white"><?= e($user['username']) ?></div><div class="text-sm text-slate-500"><?= e($user['phone_number']) ?></div></td>
                        <td class="p-4 text-sm text-slate-600 dark:text-slate-300"><?= e($user['street_address']) ?>, <?= e($user['city']) ?></td>
                        <td class="p-4 text-center"><div class="font-black text-sky-500"><?= e($user['loyalty_points']) ?> pts</div><div class="text-xs text-slate-500"><?= e($user['order_count']) ?> orders</div></td>
                        <td class="p-4 text-right">
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="add_points" class="h-9 px-3 rounded-xl bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500 hover:text-white text-xs font-black uppercase tracking-widest transition-colors">+10 Points</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="4" class="p-10 text-center text-slate-500">No customers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>
