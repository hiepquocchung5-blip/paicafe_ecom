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
    // exit();
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
<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Combo Meal Builder</h1>
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4">Create New Combo Deal</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_combo">
            <input type="text" name="name" placeholder="Combo Name (e.g., Breakfast Special)" class="form-input mb-4" required>
            <textarea name="description" placeholder="Description" class="form-input mb-4"></textarea>
            <input type="number" name="discount_amount" placeholder="Discount Amount (Ks)" class="form-input mb-4" step="0.01" required>
            <div>
                <label class="block font-semibold mb-2">Select Products for this Combo:</label>
                <select name="product_ids[]" multiple class="form-input h-48 bg-white">
                    <?php foreach($products as $product): ?>
                        <option value="<?= $product['id'] ?>"><?= e($product['name_en']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">Hold Ctrl (or Cmd on Mac) to select multiple items.</p>
            </div>
            <button type="submit" class="btn-brand mt-4">Create Combo</button>
        </form>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Existing Combo Deals</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3">Combo Name</th>
                        <th class="p-3">Included Items</th>
                        <th class="p-3">Discount</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($combos as $combo): ?>
                    <tr class="border-b">
                        <td class="p-3 font-medium"><?= e($combo['name']) ?></td>
                        <td class="p-3 text-sm text-gray-600"><?= e($combo['product_names']) ?></td>
                        <td class="p-3 font-semibold text-red-500"><?= number_format($combo['discount_amount'], 2) ?> Ks</td>
                        <td class="p-3">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this combo?');">
                                <input type="hidden" name="action" value="delete_combo">
                                <input type="hidden" name="combo_id" value="<?= $combo['id'] ?>">
                                <button type="submit" class="text-red-500 hover:underline">Delete</button>
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