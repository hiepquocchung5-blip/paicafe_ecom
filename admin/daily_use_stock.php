<?php
// FIX: Correct the file paths to go up one directory to the main 'includes' folder.
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php';

$errors = [];
$success_message = '';

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_usage'])) {
    $inventory_item_id = $_POST['inventory_item_id'] ?? 0;
    $quantity_used = $_POST['quantity_used'] ?? 0;
    $notes = trim($_POST['notes'] ?? '');
    $admin_id = $_SESSION['admin_id'];

    if (empty($inventory_item_id) || $quantity_used <= 0) {
        $errors[] = "Please select an item and enter a valid quantity.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT name, stock_quantity FROM inventory_items WHERE id = ?");
        $stmt->execute([$inventory_item_id]);
        $item = $stmt->fetch();
        if (!$item || $item['stock_quantity'] < $quantity_used) {
            $errors[] = "Not enough stock for '" . e($item['name']) . "'. Only " . e($item['stock_quantity']) . " remaining.";
        }
    }
    
    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $old_quantity = $item['stock_quantity'];
            $new_quantity = $old_quantity - $quantity_used;

            $log_stmt = $pdo->prepare(
                "INSERT INTO inventory_logs (inventory_item_id, admin_id, change_type, quantity_change, old_quantity, new_quantity, notes) 
                 VALUES (?, ?, 'manual_usage', ?, ?, ?, ?)"
            );
            $log_stmt->execute([$inventory_item_id, $admin_id, $quantity_used, $old_quantity, $new_quantity, $notes]);

            $stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $inventory_item_id]);
            
            $pdo->commit();
            $success_message = "Stock usage logged successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    }
}

// Fetch inventory items for the dropdown
$inventory_items = $pdo->query("SELECT id, name, unit, stock_quantity FROM inventory_items ORDER BY name ASC")->fetchAll();

// Fetch usage logs from the correct table
$usage_logs = $pdo->query("
    SELECT 
        il.quantity_change as quantity_used, 
        il.notes, 
        il.log_date as usage_date,
        ii.name as item_name, 
        ii.unit
    FROM inventory_logs il
    JOIN inventory_items ii ON il.inventory_item_id = ii.id
    WHERE il.change_type = 'manual_usage'
    ORDER BY il.log_date DESC
    LIMIT 100
")->fetchAll();
?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Daily Stock Usage</h1>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($success_message) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4">Log New Stock Usage</h2>
        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="inventory_item_id" class="block text-gray-700">Inventory Item</label>
                    <select name="inventory_item_id" id="inventory_item_id" class="form-input mt-1 bg-white" required>
                        <option value="">-- Select an item --</option>
                        <?php foreach ($inventory_items as $item): ?>
                            <option value="<?= $item['id'] ?>">
                                <?= e($item['name']) ?> (<?= e($item['stock_quantity']) ?> <?= e($item['unit']) ?> in stock)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="quantity_used" class="block text-gray-700">Quantity Used</label>
                    <input type="number" name="quantity_used" id="quantity_used" class="form-input mt-1" step="0.01" required>
                </div>
                 <div>
                    <label for="notes" class="block text-gray-700">Notes (Who took it?)</label>
                    <input type="text" name="notes" id="notes" class="form-input mt-1" placeholder="e.g., John for morning prep">
                </div>
            </div>
            <button type="submit" name="log_usage" class="mt-4 btn-brand">Log Usage</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Recent Usage History</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100"><th class="p-3">Date</th><th class="p-3">Item Used</th><th class="p-3">Quantity</th><th class="p-3">Notes</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($usage_logs as $log): ?>
                    <tr class="border-b">
                        <td class="p-3 text-sm text-gray-600"><?= e(date('d M Y, h:i A', strtotime($log['usage_date']))) ?></td>
                        <td class="p-3 font-medium"><?= e($log['item_name']) ?></td>
                        <td class="p-3 font-semibold text-red-600">-<?= e($log['quantity_used']) ?> <?= e($log['unit']) ?></td>
                        <td class="p-3"><?= e($log['notes']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>