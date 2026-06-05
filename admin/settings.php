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

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">General Settings</h1>

    <!-- Display Messages -->
    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($success_message) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <!-- General Settings -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-2xl font-bold mb-4">Business Rules</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="tax_percentage" class="block text-gray-700 font-semibold">Tax Rate (%)</label>
                    <input type="number" name="tax_percentage" id="tax_percentage" value="<?= e($tax_percentage) ?>" class="w-full mt-1 p-2 border rounded" step="0.1">
                </div>
                <div>
                    <label for="loyalty_points_per_100_kyats" class="block text-gray-700 font-semibold">Loyalty Points Awarded</label>
                    <input type="number" name="loyalty_points_per_100_kyats" id="loyalty_points_per_100_kyats" value="<?= e($loyalty_points) ?>" class="w-full mt-1 p-2 border rounded">
                    <p class="text-sm text-gray-500 mt-1">Points awarded for every 100 Kyats spent.</p>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-2xl font-bold mb-4">Online Payment Methods</h2>
            <div class="space-y-6">
                <?php foreach ($payment_methods as $key => $method): ?>
                <div class="border p-4 rounded-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold"><?= e($method['name']) ?></h3>
                        <label class="flex items-center">
                            <input type="checkbox" name="payment_methods[<?= e($key) ?>][enabled]" value="1" <?= $method['enabled'] ? 'checked' : '' ?> class="mr-2 h-5 w-5"> Enable
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="hidden" name="payment_methods[<?= e($key) ?>][name]" value="<?= e($method['name']) ?>">
                        <div>
                            <label class="block text-sm text-gray-600">Account Name</label>
                            <input type="text" name="payment_methods[<?= e($key) ?>][account_name]" value="<?= e($method['account_name']) ?>" class="w-full mt-1 p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600">Phone Number / Account ID</label>
                            <input type="text" name="payment_methods[<?= e($key) ?>][phone]" value="<?= e($method['phone']) ?>" class="w-full mt-1 p-2 border rounded">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="bg-blue-500 text-white py-3 px-8 rounded-lg hover:bg-blue-700 font-bold">Save All Settings</button>
        </div>
    </form>
</div>

<?php include 'partials/footer.php'; ?>