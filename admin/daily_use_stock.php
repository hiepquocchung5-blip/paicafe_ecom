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

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-orange-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Stock Consumption</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Daily Stock Usage</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Manually log stock used outside normal product recipes.</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="liquid-surface rounded-2xl px-5 py-4 border">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Items</p>
                <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($inventory_items) ?></p>
            </div>
            <div class="liquid-surface rounded-2xl px-5 py-4 border">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Usage Logs</p>
                <p class="text-3xl font-black text-orange-500 leading-none mt-1"><?= count($usage_logs) ?></p>
            </div>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-2xl" role="alert"><p class="font-bold"><?= e($success_message) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-2xl" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white">Log New Stock Usage</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Use notes to record who used the item and why.</p>
            </div>
            <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500/10 text-orange-500">
                <i class="fas fa-clipboard-list"></i>
            </div>
        </div>
        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="inventory_item_id" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Inventory Item</label>
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
                    <label for="quantity_used" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Quantity Used</label>
                    <input type="number" name="quantity_used" id="quantity_used" class="form-input mt-1" step="0.01" required>
                </div>
                 <div>
                    <label for="notes" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Notes</label>
                    <input type="text" name="notes" id="notes" class="form-input mt-1" placeholder="e.g., John for morning prep">
                </div>
            </div>
            <button type="submit" name="log_usage" class="mt-4 btn-brand">Log Usage</button>
        </form>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">Recent Usage History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr><th class="p-4">Date</th><th class="p-4">Item Used</th><th class="p-4">Quantity</th><th class="p-4">Notes</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($usage_logs as $log): ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-4 text-sm text-slate-500"><?= e(date('d M Y, h:i A', strtotime($log['usage_date']))) ?></td>
                        <td class="p-4 font-black text-slate-800 dark:text-white"><?= e($log['item_name']) ?></td>
                        <td class="p-4 font-black text-red-500">-<?= e($log['quantity_used']) ?> <?= e($log['unit']) ?></td>
                        <td class="p-4 text-slate-600 dark:text-slate-300"><?= e($log['notes']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($usage_logs)): ?>
                    <tr><td colspan="4" class="p-10 text-center text-slate-500">No manual stock usage logged yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
