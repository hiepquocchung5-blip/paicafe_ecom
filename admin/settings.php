<?php
// require_once __DIR__ . '/../includes/db_connect.php';
// require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php';

$success_message = '';
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // Update Tax and Points settings
        $tax = $_POST['tax_percentage'];
        $points = $_POST['loyalty_points_per_100_kyats'];
        
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$tax, 'tax_percentage']);
        $stmt->execute([$points, 'loyalty_points_per_100_kyats']);

        // Update Payment Methods
        if (isset($_POST['payment_methods'])) {
            foreach ($_POST['payment_methods'] as $key => $details) {
                $json_value = json_encode([
                    'name' => $details['name'],
                    'account_name' => $details['account_name'],
                    'phone' => $details['phone'],
                    'enabled' => isset($details['enabled'])
                ]);
                $stmt->execute([$json_value, $key]);
            }
        }
        
        $pdo->commit();
        $success_message = "Settings updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Failed to update settings: " . $e->getMessage();
    }
}

// Fetch current settings from the database
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_raw = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$tax_percentage = $settings_raw['tax_percentage'] ?? '5';
$loyalty_points = $settings_raw['loyalty_points_per_100_kyats'] ?? '1';

$payment_methods = [];
foreach ($settings_raw as $key => $value) {
    if (strpos($key, 'payment_method_') === 0) {
        $payment_methods[$key] = json_decode($value, true);
    }
}
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-slate-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">System Control</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">General Settings</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Manage tax, loyalty earning, and online payment account details.</p>
        </div>
        <div class="liquid-surface rounded-2xl px-5 py-4 border">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Payment Methods</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($payment_methods) ?></p>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-2xl" role="alert"><p class="font-bold"><?= e($success_message) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-2xl" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <!-- General Settings -->
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white">Business Rules</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">These values affect checkout totals and reward points.</p>
                </div>
                <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-500/10 text-slate-500">
                    <i class="fas fa-sliders"></i>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="tax_percentage" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Tax Rate (%)</label>
                    <input type="number" name="tax_percentage" id="tax_percentage" value="<?= e($tax_percentage) ?>" class="form-input" step="0.1">
                </div>
                <div>
                    <label for="loyalty_points_per_100_kyats" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Loyalty Points Awarded</label>
                    <input type="number" name="loyalty_points_per_100_kyats" id="loyalty_points_per_100_kyats" value="<?= e($loyalty_points) ?>" class="form-input">
                    <p class="text-xs text-slate-500 mt-2">Points awarded for every 100 Kyats spent.</p>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white">Online Payment Methods</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Enabled methods appear in customer checkout.</p>
                </div>
                <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-500/10 text-teal-500">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
            <div class="space-y-6">
                <?php foreach ($payment_methods as $key => $method): ?>
                <div class="liquid-surface border p-5 rounded-2xl">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-black text-slate-800 dark:text-white"><?= e($method['name']) ?></h3>
                        <label class="flex items-center text-sm font-bold text-slate-600 dark:text-slate-300">
                            <input type="checkbox" name="payment_methods[<?= e($key) ?>][enabled]" value="1" <?= $method['enabled'] ? 'checked' : '' ?> class="mr-2 h-5 w-5 accent-teal-600"> Enable
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="hidden" name="payment_methods[<?= e($key) ?>][name]" value="<?= e($method['name']) ?>">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Account Name</label>
                            <input type="text" name="payment_methods[<?= e($key) ?>][account_name]" value="<?= e($method['account_name']) ?>" class="form-input">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Phone / Account ID</label>
                            <input type="text" name="payment_methods[<?= e($key) ?>][phone]" value="<?= e($method['phone']) ?>" class="form-input">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn-brand">Save All Settings</button>
        </div>
    </form>
</div>

<?php include 'partials/footer.php'; ?>
