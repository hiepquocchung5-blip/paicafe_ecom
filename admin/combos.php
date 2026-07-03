<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// Fetch all products to populate the multi-select dropdown
$products = $pdo->query("SELECT id, name_en FROM products WHERE is_available = 1 ORDER BY name_en ASC")->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_combo') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $discount = $_POST['discount_amount'];
        $product_ids = $_POST['product_ids'] ?? [];

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO combos (name, description, discount_amount) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $discount]);
            $combo_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO combo_products (combo_id, product_id) VALUES (?, ?)");
            foreach ($product_ids as $product_id) {
                $stmt->execute([$combo_id, $product_id]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    
    if ($action === 'delete_combo') {
        $combo_id = $_POST['combo_id'];
        $stmt = $pdo->prepare("DELETE FROM combos WHERE id = ?");
        $stmt->execute([$combo_id]);
    }

    header("Location: combos.php");
    exit();
}

// Fetch existing combos with their included product names
$combos_stmt = $pdo->query("
    SELECT c.*, GROUP_CONCAT(p.name_en SEPARATOR ', ') as product_names
    FROM combos c
    LEFT JOIN combo_products cp ON c.id = cp.combo_id
    LEFT JOIN products p ON cp.product_id = p.id
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.name ASC
");
$combos = $combos_stmt->fetchAll();
?>
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Menu Bundles</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Combo Builder</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Bundle available products into discounted meal deals.</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="liquid-surface rounded-2xl px-5 py-4 border">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Combos</p>
                <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($combos) ?></p>
            </div>
            <div class="liquid-surface rounded-2xl px-5 py-4 border">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Products</p>
                <p class="text-3xl font-black text-indigo-500 leading-none mt-1"><?= count($products) ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white">Create New Combo Deal</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Use a simple discount amount so cashier totals stay predictable.</p>
            </div>
            <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-500">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_combo">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Combo Name</label>
                    <input type="text" name="name" placeholder="Breakfast Special" class="form-input" required>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Discount Amount</label>
                    <input type="number" name="discount_amount" placeholder="Ks" class="form-input" step="0.01" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Description</label>
                    <textarea name="description" placeholder="Short customer-facing description" class="form-input"></textarea>
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2 mt-5">Products</label>
                <select name="product_ids[]" multiple class="form-input h-48 bg-white">
                    <?php foreach($products as $product): ?>
                        <option value="<?= $product['id'] ?>"><?= e($product['name_en']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-slate-500 mt-2">Hold Ctrl or Cmd to select multiple products.</p>
            </div>
            <button type="submit" class="btn-brand mt-4">Create Combo</button>
        </form>
    </div>
    
    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">Existing Combo Deals</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="p-4">Combo Name</th>
                        <th class="p-4">Included Items</th>
                        <th class="p-4">Discount</th>
                        <th class="p-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($combos as $combo): ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-4 font-black text-slate-800 dark:text-white"><?= e($combo['name']) ?></td>
                        <td class="p-4 text-sm text-slate-600 dark:text-slate-300"><?= e($combo['product_names'] ?: 'No products assigned') ?></td>
                        <td class="p-4 font-black text-red-500"><?= number_format($combo['discount_amount'], 2) ?> Ks</td>
                        <td class="p-4 text-right">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this combo?');">
                                <input type="hidden" name="action" value="delete_combo">
                                <input type="hidden" name="combo_id" value="<?= $combo['id'] ?>">
                                <button type="submit" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Delete">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($combos)): ?>
                    <tr><td colspan="4" class="p-10 text-center text-slate-500">No combo deals yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'partials/footer.php'; ?>
