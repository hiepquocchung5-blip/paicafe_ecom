<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_login();

$current_page = 'profile';

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_address'])) {
        $street_address = trim($_POST['street_address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $stmt = $pdo->prepare("UPDATE users SET street_address = ?, city = ?, country = ? WHERE id = ?");
        if ($stmt->execute([$street_address, $city, $country, $user_id])) {
            $success_message = "Address updated successfully!";
        } else {
            $errors[] = "Failed to update address.";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        if ($user_data && password_verify($current_password, $user_data['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->execute([$hashed_password, $user_id]);
                    $success_message = "Password updated successfully!";
                } else { $errors[] = "New password must be at least 6 characters long."; }
            } else { $errors[] = "New passwords do not match."; }
        } else { $errors[] = "Incorrect current password."; }
    }
}

// --- Fetch All User Data for Display ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll();

$redemptions_stmt = $pdo->prepare("SELECT rr.redeemed_at, rr.points_spent, rr.status, lr.title as reward_title FROM reward_redemptions rr JOIN loyalty_rewards lr ON rr.reward_id = lr.id WHERE rr.user_id = ? ORDER BY rr.redeemed_at DESC");
$redemptions_stmt->execute([$user_id]);
$redemptions = $redemptions_stmt->fetchAll();

$favorites_stmt = $pdo->prepare("SELECT p.* FROM products p JOIN user_favorites uf ON p.id = uf.product_id WHERE uf.user_id = ?");
$favorites_stmt->execute([$user_id]);
$favorites = $favorites_stmt->fetchAll();

include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto" x-data="{ activeTab: 'profile' }">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">

        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <div class="w-24 h-24 rounded-full mx-auto bg-orange-100 flex items-center justify-center mb-4">
                    <span class="text-4xl text-orange-500 font-bold"><?= e(strtoupper(substr($user['username'], 0, 1))) ?></span>
                </div>
                <h2 class="text-2xl font-bold"><?= e($user['username']) ?></h2>
                <p class="text-gray-500 text-sm"><?= e($user['email']) ?></p>
                <div class="mt-6 bg-orange-500 text-white p-4 rounded-lg">
                    <p class="text-lg font-semibold">Loyalty Points</p>
                    <p class="text-4xl font-bold"><?= e($user['loyalty_points']) ?></p>
                </div>
            </div>
        </div>

        <div class="md:col-span-3">
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex space-x-6">
                    <button @click="activeTab = 'profile'" :class="{ 'border-orange-500 text-orange-600': activeTab === 'profile', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'profile' }" class="py-4 px-1 border-b-2 font-medium text-sm">Details & Address</button>
                    <button @click="activeTab = 'orders'" :class="{ 'border-orange-500 text-orange-600': activeTab === 'orders', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'orders' }" class="py-4 px-1 border-b-2 font-medium text-sm">Order History</button>
                    <button @click="activeTab = 'redemptions'" :class="{ 'border-orange-500 text-orange-600': activeTab === 'redemptions', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'redemptions' }" class="py-4 px-1 border-b-2 font-medium text-sm">Redemptions</button>
                    <button @click="activeTab = 'favorites'" :class="{ 'border-orange-500 text-orange-600': activeTab === 'favorites', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'favorites' }" class="py-4 px-1 border-b-2 font-medium text-sm">Favorites</button>
                </nav>
            </div>

            <?php if ($success_message): ?><div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= e($success_message) ?></div><?php endif; ?>
            <?php if (!empty($errors)): ?><div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>

            <div x-show="activeTab === 'profile'" class="space-y-8">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-bold mb-4">Your Address</h2>
                    <form method="POST">
                        <div class="space-y-4"><input type="text" name="street_address" placeholder="Street Address" class="form-input" value="<?= e($user['street_address']) ?>"><input type="text" name="city" placeholder="City" class="form-input" value="<?= e($user['city']) ?>"><input type="text" name="country" placeholder="Country" class="form-input" value="<?= e($user['country']) ?>"></div>
                        <button type="submit" name="update_address" class="btn-brand mt-4">Save Address</button>
                    </form>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-bold mb-4">Change Password</h2>
                    <form method="POST">
                        <div class="space-y-4"><input type="password" name="current_password" placeholder="Current Password" class="form-input" required><input type="password" name="new_password" placeholder="New Password" class="form-input" required><input type="password" name="confirm_password" placeholder="Confirm New Password" class="form-input" required></div>
                        <button type="submit" name="change_password" class="btn-brand mt-4">Update Password</button>
                    </form>
                </div>
            </div>
            
            <div x-show="activeTab === 'orders'" class="bg-white p-6 rounded-lg shadow-md" style="display: none;"><h2 class="text-2xl font-bold mb-4">Your Order History</h2><div class="overflow-x-auto"><table class="w-full text-left"><thead><tr class="bg-gray-100"><th class="p-3">ID</th><th class="p-3">Date</th><th class="p-3">Total</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead><tbody><?php foreach($orders as $order): ?><tr class="border-b"><td class="p-3 font-semibold">#<?= e($order['id']) ?></td><td class="p-3 text-sm"><?= date('d M Y', strtotime($order['created_at'])) ?></td><td class="p-3"><?= number_format($order['final_amount'], 2) ?> Ks</td><td class="p-3"><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $order['status'] == 'completed' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800' ?>"><?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></span></td><td class="p-3 flex items-center space-x-3"><a href="order_status.php?order_id=<?= $order['id']?>" class="text-blue-500 hover:underline text-sm">View</a><?php if ($order['status'] === 'completed'): ?><button onclick="reorder(<?= $order['id'] ?>)" class="btn-outline text-xs py-1 px-3">Order Again</button><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div>
            <div x-show="activeTab === 'redemptions'" class="bg-white p-6 rounded-lg shadow-md" style="display: none;"><h2 class="text-2xl font-bold mb-4">Your Redemption History</h2><div class="overflow-x-auto"><table class="w-full text-left"><thead><tr class="bg-gray-100"><th class="p-3">Date</th><th class="p-3">Reward</th><th class="p-3">Points Used</th><th class="p-3">Status</th></tr></thead><tbody><?php foreach($redemptions as $r): ?><tr class="border-b"><td class="p-3 text-sm"><?= date('d M Y', strtotime($r['redeemed_at'])) ?></td><td class="p-3 font-medium"><?= e($r['reward_title']) ?></td><td class="p-3 text-red-500 font-semibold">-<?= e($r['points_spent']) ?></td><td class="p-3"><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $r['status'] == 'fulfilled' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800' ?>"><?= e(ucwords($r['status'])) ?></span></td></tr><?php endforeach; ?></tbody></table></div></div>
            <div x-show="activeTab === 'favorites'" class="bg-white p-6 rounded-lg shadow-md" style="display: none;"><h2 class="text-2xl font-bold mb-4">Your Favorite Items</h2><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><?php foreach ($favorites as $fav): ?><a href="product_details.php?id=<?= $fav['id'] ?>" class="flex items-center p-3 border rounded-lg hover:bg-gray-50"><img src="<?= e($fav['image'] ?: '/assets/uploads/placeholder.png') ?>" class="w-16 h-16 object-cover rounded mr-4"><div><p class="font-semibold"><?= e($fav['name_en']) ?></p><p class="text-sm text-gray-600"><?= e($fav['price']) ?> Ks</p></div></a><?php endforeach; ?></div></div>

        </div>
    </div>
</div>

<script>
// The reorder script is the same
function reorder(orderId) {
    if (!confirm('This will clear your current cart. Continue?')) return;
    fetch('/api/reorder.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_id: orderId })})
    .then(res => res.json()).then(data => {
        if (data.status === 'success') {
            document.getElementById('cart-count').textContent = data.cart_count;
            if (document.getElementById('mobile-cart-count')) {
                document.getElementById('mobile-cart-count').textContent = data.cart_count;
            }
            window.location.href = '/cart.php';
        } else { alert('Error: ' + data.message); }
    }).catch(err => alert('An error occurred.'));
}
</script>

<?php include 'includes/footer.php'; ?>