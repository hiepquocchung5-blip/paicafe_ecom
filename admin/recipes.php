<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// Get the selected product ID from the URL, default to 0 if not set
$selected_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$success_message = '';
$errors = [];

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_ingredient') {
        $product_id = $_POST['product_id'] ?? 0;
        $inventory_item_id = $_POST['inventory_item_id'] ?? 0;
        $quantity_used = $_POST['quantity_used'] ?? 0;

        if ($product_id > 0 && $inventory_item_id > 0 && $quantity_used > 0) {
            $stmt = $pdo->prepare("INSERT INTO recipes (product_id, inventory_item_id, quantity_used) VALUES (?, ?, ?)");
            if ($stmt->execute([$product_id, $inventory_item_id, $quantity_used])) {
                $success_message = 'Ingredient added to recipe.';
            } else {
                $errors[] = 'Failed to add ingredient.';
            }
        } else {
            $errors[] = 'Invalid data provided.';
        }
        // Redirect to keep the same product selected
        header('Location: recipes.php?product_id=' . $product_id);
        exit();

    } elseif ($action === 'delete_ingredient') {
        $recipe_id = $_POST['recipe_id'] ?? 0;
        $product_id = $_POST['product_id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = ?");
        $stmt->execute([$recipe_id]);
        header('Location: recipes.php?product_id=' . $product_id);
        exit();
    }
}

// Fetch all products for the main dropdown
$products = $pdo->query("SELECT id, name_en FROM products ORDER BY name_en ASC")->fetchAll();

// Fetch all inventory items for the ingredient dropdown
$inventory_items = $pdo->query("SELECT id, name, unit FROM inventory_items ORDER BY name ASC")->fetchAll();

// Fetch the recipe for the currently selected product
$recipe_ingredients = [];
if ($selected_product_id > 0) {
    $stmt = $pdo->prepare("
        SELECT r.id as recipe_id, i.name, i.unit, r.quantity_used
        FROM recipes r
        JOIN inventory_items i ON r.inventory_item_id = i.id
        WHERE r.product_id = ?
        ORDER BY i.name
    ");
    $stmt->execute([$selected_product_id]);
    $recipe_ingredients = $stmt->fetchAll();
}
?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Manage Recipes</h1>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <form method="GET" id="productSelectorForm">
            <label for="product_id" class="block text-gray-700 font-semibold mb-2">1. Select a Product to Manage Its Recipe</label>
            <select name="product_id" id="product_id" class="w-full p-2 border rounded" onchange="document.getElementById('productSelectorForm').submit();">
                <option value="0">-- Choose a Product --</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>" <?= $selected_product_id == $product['id'] ? 'selected' : '' ?>>
                        <?= e($product['name_en']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($selected_product_id > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">2. Add Ingredient to Recipe</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_ingredient">
                <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">
                <div class="mb-4">
                    <label for="inventory_item_id" class="block text-gray-700">Ingredient</label>
                    <select name="inventory_item_id" id="inventory_item_id" class="w-full mt-1 p-2 border rounded" required>
                        <option value="">-- Select an ingredient --</option>
                        <?php foreach ($inventory_items as $item): ?>
                            <option value="<?= $item['id'] ?>"><?= e($item['name']) ?> (<?= e($item['unit']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="quantity_used" class="block text-gray-700">Quantity Used</label>
                    <input type="number" name="quantity_used" id="quantity_used" class="w-full mt-1 p-2 border rounded" step="0.01" required>
                </div>
                <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">Add to Recipe</button>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">Current Recipe</h2>
            <?php if (empty($recipe_ingredients)): ?>
                <p class="text-gray-500">No ingredients have been added to this recipe yet.</p>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach ($recipe_ingredients as $ingredient): ?>
                    <li class="flex justify-between items-center p-2 border rounded">
                        <span>
                            <span class="font-medium"><?= e($ingredient['name']) ?>:</span>
                            <?= e($ingredient['quantity_used']) ?> <?= e($ingredient['unit']) ?>
                        </span>
                        <form method="POST" onsubmit="return confirm('Remove this ingredient?');">
                            <input type="hidden" name="action" value="delete_ingredient">
                            <input type="hidden" name="recipe_id" value="<?= $ingredient['recipe_id'] ?>">
                            <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">
                            <button type="submit" class="text-red-500 text-sm hover:underline">Remove</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>