<?php

include __DIR__ . '/partials/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_coupon'])) {
        $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, expiry_date, max_uses) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            strtoupper($_POST['code']), 
            $_POST['discount_type'], 
            $_POST['discount_value'], 
            empty($_POST['expiry_date']) ? null : $_POST['expiry_date'], 
            empty($_POST['max_uses']) ? null : $_POST['max_uses']
        ]);
        $_SESSION['flash_message'] = "Coupon created successfully!";
    }
    
    if (isset($_POST['delete_coupon'])) {
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$_POST['coupon_id']]);
        $_SESSION['flash_message'] = "Coupon deleted successfully!";
    }

    $_SESSION['flash_message_type'] = 'success';
    header('Location: coupons.php');
    exit();
}

// Fetch existing coupons to display them
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
?>
<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Manage Coupons</h1>
    
    <?php
    if (isset($_SESSION['flash_message'])) {
        echo '<div class="p-4 mb-4 text-sm ' . ($_SESSION['flash_message_type'] == 'success' ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100') . ' rounded-lg" role="alert">' . e($_SESSION['flash_message']) . '</div>';
        unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
    }
    ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4">Create New Coupon</h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <input type="text" name="code" placeholder="Code (e.g., SUMMER10)" class="form-input" required>
            <select name="discount_type" class="form-input bg-white"><option value="fixed">Fixed Amount</option><option value="percentage">Percentage</option></select>
            <input type="number" name="discount_value" placeholder="Value (e.g., 500 or 10)" class="form-input" step="0.01" required>
            <input type="date" name="expiry_date" class="form-input" title="Expiry Date">
            <input type="number" name="max_uses" placeholder="Max Uses (optional)" class="form-input">
            <button type="submit" name="create_coupon" class="btn-brand w-full lg:w-auto">Create Coupon</button>
        </form>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Existing Coupons</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr class="bg-gray-100"><th class="p-3">Code</th><th class="p-3">Discount</th><th class="p-3">Expiry</th><th class="p-3">Usage</th><th class="p-3">Action</th></tr></thead>
                <tbody>
                    <?php foreach($coupons as $coupon): ?>
                    <tr class="border-b">
                        <td class="p-3 font-mono font-bold"><?= e($coupon['code']) ?></td>
                        <td class="p-3">
                            <?= number_format($coupon['discount_value']) ?>
                            <?= $coupon['discount_type'] === 'percentage' ? '%' : 'Ks' ?>
                        </td>
                        <td class="p-3"><?= e($coupon['expiry_date'] ? date('d M Y', strtotime($coupon['expiry_date'])) : 'No expiry') ?></td>
                        <td class="p-3"><?= e($coupon['uses_count']) ?> / <?= e($coupon['max_uses'] ?? '∞') ?></td>
                        <td class="p-3">
                            <form method="POST" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                                <button type="submit" name="delete_coupon" class="text-red-500 hover:underline">Delete</button>
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