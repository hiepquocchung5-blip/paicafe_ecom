<?php
require_once __DIR__ . '/includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// Handle marking a redemption as 'fulfilled'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fulfill_redemption'])) {
    $redemption_id = $_POST['redemption_id'];
    $stmt = $pdo->prepare("UPDATE reward_redemptions SET status = 'fulfilled' WHERE id = ?");
    $stmt->execute([$redemption_id]);
    header('Location: redemptions.php'); // Refresh the page
    exit();
}

// Fetch all redemptions, joining with user and reward tables to get names
$stmt = $pdo->query("
    SELECT 
        rr.id, rr.points_spent, rr.redeemed_at, rr.status,
        u.username, u.phone_number,
        lr.title as reward_title
    FROM reward_redemptions rr
    JOIN users u ON rr.user_id = u.id
    JOIN loyalty_rewards lr ON rr.reward_id = lr.id
    ORDER BY rr.redeemed_at DESC
");
$redemptions = $stmt->fetchAll();
?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Reward Redemptions Log</h1>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3">User</th>
                        <th class="p-3">Reward</th>
                        <th class="p-3">Points Spent</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($redemptions as $redemption): ?>
                    <tr class="border-b">
                        <td class="p-3">
                            <div class="font-medium"><?= e($redemption['username']) ?></div>
                            <div class="text-sm text-gray-500"><?= e($redemption['phone_number']) ?></div>
                        </td>
                        <td class="p-3"><?= e($redemption['reward_title']) ?></td>
                        <td class="p-3 font-semibold text-red-500">-<?= e($redemption['points_spent']) ?></td>
                        <td class="p-3 text-sm text-gray-600"><?= date('d M Y, h:i A', strtotime($redemption['redeemed_at'])) ?></td>
                        <td class="p-3">
                             <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $redemption['status'] == 'pending' ? 'bg-yellow-200 text-yellow-800' : 'bg-green-200 text-green-800' ?>">
                                <?= e(ucfirst($redemption['status'])) ?>
                            </span>
                        </td>
                        <td class="p-3">
                            <?php if ($redemption['status'] === 'pending'): ?>
                                <form method="POST">
                                    <input type="hidden" name="redemption_id" value="<?= $redemption['id'] ?>">
                                    <button type="submit" name="fulfill_redemption" class="text-xs bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">Mark Fulfilled</button>
                                </form>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>