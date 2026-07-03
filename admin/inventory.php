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
    exit();
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

<div class="max-w-7xl mx-auto" x-data="{ stockModalOpen: false, currentItem: {}, quantityToAdd: 0 }">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-lime-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Stock Control</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Inventory</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Track ingredient cost, stock quantity, and low-stock thresholds.</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="liquid-surface rounded-2xl px-5 py-4 border">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Items</p>
                <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($items) ?></p>
            </div>
            <div class="liquid-surface rounded-2xl px-5 py-4 border">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Low Stock</p>
                <p class="text-3xl font-black text-red-500 leading-none mt-1"><?= count($low_stock_items) ?></p>
            </div>
        </div>
    </div>

    <?php if ($flash_message): ?>
        <div class="p-4 mb-6 rounded-2xl <?= $flash_message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700' ?>">
            <p class="font-bold"><?= e($flash_message) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($low_stock_items)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-2xl shadow-md" role="alert">
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

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white">Add New Ingredient</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Set a threshold so low-stock alerts are useful.</p>
            </div>
            <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-lime-500/10 text-lime-500">
                <i class="fas fa-boxes-stacked"></i>
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div><label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Ingredient</label><input name="name" placeholder="Name" class="form-input" required></div>
                <div><label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Unit</label><input name="unit" placeholder="gram, ml" class="form-input" required></div>
                <div><label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Cost</label><input type="number" name="cost" placeholder="Ks" class="form-input" step="0.01" required></div>
                <div><label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Current Stock</label><input type="number" name="stock_quantity" placeholder="0" class="form-input" step="0.01" required></div>
                <div><label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Alert At</label><input type="number" name="low_stock_threshold" placeholder="10" class="form-input" step="0.01" required></div>
            </div>
            <button type="submit" class="mt-4 btn-brand">Add Ingredient</button>
        </form>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">Current Stock</h2>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead><tr><th class="p-4">Name</th><th class="p-4">Cost</th><th class="p-4">Stock</th><th class="p-4">Unit</th><th class="p-4 text-right">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr class="border-b border-slate-100 dark:border-slate-800">
                    <td class="p-4 font-black text-slate-800 dark:text-white"><?= e($item['name']) ?></td>
                    <td class="p-4 text-slate-600 dark:text-slate-300"><?= number_format($item['cost'], 2) ?> Ks</td>
                    <td class="p-4 font-black <?= $item['stock_quantity'] <= $item['low_stock_threshold'] ? 'text-red-500' : 'text-emerald-500' ?>"><?= e($item['stock_quantity']) ?></td>
                    <td class="p-4"><?= e($item['unit']) ?></td>
                    <td class="p-4 text-right">
                        <button @click="stockModalOpen = true; quantityToAdd = 0; currentItem = { id: <?= $item['id'] ?>, name: '<?= e($item['name']) ?>', quantity: <?= $item['stock_quantity'] ?>, unit: '<?= e($item['unit']) ?>' }" class="h-9 px-3 rounded-xl bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500 hover:text-white text-xs font-black uppercase tracking-widest transition-colors">Add</button>
                        <form method="POST" class="inline-block ml-2" onsubmit="return confirm('Are you sure?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $item['id'] ?>"><button type="submit" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Delete"><i class="fas fa-trash text-xs"></i></button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="5" class="p-10 text-center text-slate-500">No inventory items yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    
    <div x-show="stockModalOpen" @keydown.escape.window="stockModalOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" x-cloak>
        <div @click.away="stockModalOpen = false" class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl p-8 rounded-[2rem] shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md">
            <h2 class="text-2xl font-black text-slate-800 dark:text-white mb-4">Add Stock for <span x-text="currentItem.name" class="text-blue-500"></span></h2>
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
                    <button type="button" @click="stockModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold">Cancel</button>
                    <button type="submit" class="btn-brand">Confirm Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
