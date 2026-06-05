<?php
// FIX 1: Correct the file paths and include functions.php
require_once __DIR__ . '/../includes/db_connect.php';
// require_once __DIR__ . '/../includes/functions.php'; 
include __DIR__ . '/partials/header.php';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $admin_id = $_SESSION['admin_id'];
    $_SESSION['flash_message_type'] = 'error';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $cost = $_POST['cost'] ?? 0;
        $quantity = $_POST['stock_quantity'] ?? 0;
        $threshold = $_POST['low_stock_threshold'] ?? 10;
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory_items (name, unit, cost, stock_quantity, low_stock_threshold) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $unit, $cost, $quantity, $threshold]);
            $item_id = $pdo->lastInsertId();

            $log_stmt = $pdo->prepare("INSERT INTO inventory_logs (inventory_item_id, admin_id, change_type, quantity_change, old_quantity, new_quantity, notes) VALUES (?, ?, 'created', ?, 0, ?, ?)");
            $log_stmt->execute([$item_id, $admin_id, $quantity, $quantity, "Item created"]);
            
            $pdo->commit();
            $_SESSION['flash_message'] = "Ingredient added successfully!";
            $_SESSION['flash_message_type'] = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = "Failed to add ingredient.";
        }
    } 
    elseif ($action === 'update_stock') {
        $id = $_POST['id'] ?? 0;
        $quantity_to_add = $_POST['quantity_to_add'] ?? 0;
        
        if ($quantity_to_add > 0) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT stock_quantity FROM inventory_items WHERE id = ?");
                $stmt->execute([$id]);
                $old_quantity = $stmt->fetchColumn();

                $new_quantity = $old_quantity + $quantity_to_add;

                $stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = ? WHERE id = ?");
                $stmt->execute([$new_quantity, $id]);

                $log_stmt = $pdo->prepare("INSERT INTO inventory_logs (inventory_item_id, admin_id, change_type, quantity_change, old_quantity, new_quantity, notes) VALUES (?, ?, 'added_stock', ?, ?, ?, 'Manual stock addition')");
                $log_stmt->execute([$id, $admin_id, $quantity_to_add, $old_quantity, $new_quantity]);

                $pdo->commit();
                $_SESSION['flash_message'] = "Stock updated successfully!";
                $_SESSION['flash_message_type'] = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_message'] = "Failed to update stock.";
            }
        } else {
             $_SESSION['flash_message'] = "Please enter a valid quantity to add.";
        }
    } 
    elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE id=?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = "Ingredient deleted successfully.";
        $_SESSION['flash_message_type'] = 'success';
    }
    
    header('Location: inventory.php');
    // exit();
}

// --- Display Flash Messages ---
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_message_type = $_SESSION['flash_message_type'] ?? 'error';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

// --- Fetch Data for Display ---
$items = $pdo->query("SELECT * FROM inventory_items ORDER BY name ASC")->fetchAll();
$low_stock_items = array_filter($items, function($item) {
    return $item['stock_quantity'] <= $item['low_stock_threshold'];
});
?>

<div class="container mx-auto px-4" x-data="{ stockModalOpen: false, currentItem: {}, quantityToAdd: 0 }">
    <h1 class="text-3xl font-bold mb-6">Manage Inventory</h1>

    <?php if ($flash_message): ?>
        <div class="p-4 mb-6 rounded-lg <?= $flash_message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700' ?>">
            <p><?= e($flash_message) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($low_stock_items)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-lg shadow-md" role="alert">
        <div class="flex"><div class="py-1"><i class="fas fa-exclamation-triangle fa-lg mr-4"></i></div>
            <div>
                <p class="font-bold">Low Stock Alert!</p>
                <ul class="list-disc list-inside mt-2 text-sm">
                    <?php foreach ($low_stock_items as $item): ?>
                        <li><strong><?= e($item['name']) ?></strong> (<?= e($item['stock_quantity']) ?> / <?= e($item['low_stock_threshold']) ?> <?= e($item['unit']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4">Add New Ingredient</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input name="name" placeholder="Ingredient Name" class="form-input" required>
                <input name="unit" placeholder="Unit (e.g., gram, ml)" class="form-input" required>
                <input type="number" name="cost" placeholder="Cost per Unit (Ks)" class="form-input" step="0.01" required>
                <input type="number" name="stock_quantity" placeholder="Current Stock" class="form-input" step="0.01" required>
                <input type="number" name="low_stock_threshold" placeholder="Low Stock Alert At" class="form-input" step="0.01" required>
            </div>
            <button type="submit" class="mt-4 btn-brand">Add Ingredient</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Current Stock</h2>
        <table class="w-full text-left">
            <thead><tr class="bg-gray-100"><th class="p-3">Name</th><th class="p-3">Cost</th><th class="p-3">Stock</th><th class="p-3">Unit</th><th class="p-3">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr class="border-b">
                    <td class="p-3 font-medium"><?= e($item['name']) ?></td>
                    <td class="p-3 text-gray-600"><?= number_format($item['cost'], 2) ?> Ks</td>
                    <td class="p-3 font-semibold <?= $item['stock_quantity'] <= $item['low_stock_threshold'] ? 'text-red-500' : '' ?>"><?= e($item['stock_quantity']) ?></td>
                    <td class="p-3"><?= e($item['unit']) ?></td>
                    <td class="p-3">
                        <button @click="stockModalOpen = true; quantityToAdd = 0; currentItem = { id: <?= $item['id'] ?>, name: '<?= e($item['name']) ?>', quantity: <?= $item['stock_quantity'] ?>, unit: '<?= e($item['unit']) ?>' }" class="text-xs bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">Add Stock</button>
                        <form method="POST" class="inline-block ml-2" onsubmit="return confirm('Are you sure?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $item['id'] ?>"><button type="submit" class="text-red-500 hover:underline">Delete</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div x-show="stockModalOpen" @keydown.escape.window="stockModalOpen = false" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div @click.away="stockModalOpen = false" class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
            <h2 class="text-2xl font-bold mb-4">Add Stock for <span x-text="currentItem.name" class="text-blue-600"></span></h2>
            <div class="space-y-4 mb-6 text-lg">
                <div class="flex justify-between"><span>Old Stock:</span><strong x-text="currentItem.quantity"></strong></div>
                <div class="flex justify-between"><span>Stock to Add:</span><strong class="text-green-600" x-text="`+ ${parseFloat(quantityToAdd) || 0}`"></strong></div>
                <div class="flex justify-between border-t pt-2 font-bold"><span>New Total Stock:</span><span x-text="parseFloat(currentItem.quantity) + (parseFloat(quantityToAdd) || 0)"></span></div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="id" :value="currentItem.id">
                <div>
                    <label for="quantity_to_add" class="block font-semibold">Quantity to Add (<span x-text="currentItem.unit"></span>)</label>
                    <input type="number" name="quantity_to_add" id="quantity_to_add" class="form-input mt-1" step="0.01" required placeholder="e.g., 500" x-model="quantityToAdd">
                </div>
                <div class="mt-6 flex justify-end space-x-4">
                    <button type="button" @click="stockModalOpen = false" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="btn-brand">Confirm Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>