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
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-fuchsia-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Voucher Control</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Coupons</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Create limited-use discounts for checkout and POS.</p>
        </div>
        <div class="liquid-surface rounded-2xl px-5 py-4 border">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Coupons</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($coupons) ?></p>
        </div>
    </div>
    
    <?php
    if (isset($_SESSION['flash_message'])) {
        echo '<div class="p-4 mb-6 text-sm font-bold ' . ($_SESSION['flash_message_type'] == 'success' ? 'text-green-700 bg-green-100 border-l-4 border-green-500' : 'text-red-700 bg-red-100 border-l-4 border-red-500') . ' rounded-2xl" role="alert">' . e($_SESSION['flash_message']) . '</div>';
        unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
    }
    ?>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white">Create New Coupon</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Expiry and max usage are checked by the shared coupon validator.</p>
            </div>
            <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-fuchsia-500/10 text-fuchsia-500">
                <i class="fas fa-ticket-alt"></i>
            </div>
        </div>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div><label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Code</label><input type="text" name="code" placeholder="SUMMER10" class="form-input uppercase" required></div>
            <div><label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Type</label><select name="discount_type" class="form-input bg-white"><option value="fixed">Fixed Amount</option><option value="percentage">Percentage</option></select></div>
            <div><label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Value</label><input type="number" name="discount_value" placeholder="500 or 10" class="form-input" step="0.01" required></div>
            <div><label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Expiry</label><input type="date" name="expiry_date" class="form-input" title="Expiry Date"></div>
            <div><label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Max Uses</label><input type="number" name="max_uses" placeholder="Optional" class="form-input"></div>
            <button type="submit" name="create_coupon" class="btn-brand w-full lg:w-auto">Create Coupon</button>
        </form>
    </div>
    
    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">Existing Coupons</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr><th class="p-4">Code</th><th class="p-4">Discount</th><th class="p-4">Expiry</th><th class="p-4">Usage</th><th class="p-4 text-right">Action</th></tr></thead>
                <tbody>
                    <?php foreach($coupons as $coupon): ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-4 font-mono font-black text-slate-800 dark:text-white"><?= e($coupon['code']) ?></td>
                        <td class="p-4 font-bold">
                            <?= number_format($coupon['discount_value']) ?>
                            <?= $coupon['discount_type'] === 'percentage' ? '%' : 'Ks' ?>
                        </td>
                        <td class="p-4 text-slate-600 dark:text-slate-300"><?= e($coupon['expiry_date'] ? date('d M Y', strtotime($coupon['expiry_date'])) : 'No expiry') ?></td>
                        <td class="p-4"><span class="px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-black"><?= e($coupon['uses_count']) ?> / <?= e($coupon['max_uses'] ?? '∞') ?></span></td>
                        <td class="p-4 text-right">
                            <form method="POST" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                                <button type="submit" name="delete_coupon" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Delete">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($coupons)): ?>
                    <tr><td colspan="5" class="p-10 text-center text-slate-500">No coupons created yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>
