<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
$page_title = 'Loyalty Rewards | Pai Cafe Yangon';
$page_description = 'Earn Pai Cafe loyalty points and redeem food, drinks and exclusive café rewards in Thuwunna, Yangon.';
$page_canonical = APP_URL . '/rewards.php';
include 'includes/header.php';

// Fetch all active loyalty rewards from the database
$rewards_stmt = $pdo->prepare("SELECT * FROM loyalty_rewards WHERE is_active = 1 ORDER BY points_cost ASC");
$rewards_stmt->execute();
$rewards = $rewards_stmt->fetchAll();
?>

<div class="max-w-6xl mx-auto">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-800">🎁 Loyalty Rewards</h1>
        <p class="text-lg text-gray-600 mt-2">Earn points with every purchase and redeem these amazing rewards!</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php if (empty($rewards)): ?>
            <p class="col-span-full text-center text-gray-500">No rewards are available at the moment. Please check back soon!</p>
        <?php else: ?>
            <?php foreach ($rewards as $reward): ?>
            <div class="reward-card bg-gradient-to-br from-purple-500 to-indigo-600 text-white p-6 rounded-xl shadow-lg flex flex-col text-center transform hover:-translate-y-2 transition-transform duration-300">
                <div class="text-4xl mb-4">🎁</div>
                <h3 class="text-xl font-bold mb-2 flex-grow"><?= e($reward['title']) ?></h3>
                <p class="text-indigo-200 text-sm mb-4"><?= e($reward['description']) ?></p>
                <div class="mt-auto bg-white text-indigo-600 font-bold py-2 px-4 rounded-full">
                    <?= e($reward['points_cost']) ?> Points
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="text-center mt-12 bg-gray-100 p-8 rounded-lg">
        <h2 class="text-2xl font-bold">How to Earn Points?</h2>
        <p class="text-gray-600 mt-2">Simply provide your phone number when you order in-store, or log in to order online. You'll automatically earn points for every purchase!</p>
        <a href="/register.php" class="btn-brand mt-4">Sign Up Now & Start Earning!</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
