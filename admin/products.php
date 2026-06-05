<?php
// We must include functions.php for security and helper functions
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php'; // This includes all the admin UI

$errors = [];

// --- Handle Form Submissions (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name_en = trim($_POST['name_en'] ?? '');
    $price = $_POST['price'] ?? 0;
    $action = $_POST['action'] ?? '';

    if (empty($name_en) || empty($price)) {
        $errors[] = "Product Name and Price are required.";
    }

    if (empty($errors)) {
        $params = [
            trim($_POST['name_en'] ?? ''),
            trim($_POST['name_mm'] ?? ''),
            trim($_POST['description_en'] ?? ''),
            trim($_POST['description_mm'] ?? ''),
            $_POST['price'] ?? 0,
            !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            trim($_POST['image_url'] ?? ''),
            isset($_POST['is_available']) ? 1 : 0,
            isset($_POST['is_special_today']) ? 1 : 0
        ];

        try {
            if ($action === 'create') {
                $sql = "INSERT INTO products (name_en, name_mm, description_en, description_mm, price, category_id, image, is_available, is_special_today) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['flash_message'] = 'Product created successfully.';
            } elseif ($action === 'update') {
                $sql = "UPDATE products SET name_en=?, name_mm=?, description_en=?, description_mm=?, price=?, category_id=?, image=?, is_available=?, is_special_today=? WHERE id=?";
                $params[] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['flash_message'] = 'Product updated successfully.';
            }
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_message_type'] = 'error';
        }
    } else {
        $_SESSION['flash_message'] = implode(' ', $errors);
        $_SESSION['flash_message_type'] = 'error';
    }
    header('Location: products.php'); // Redirect to clear form
    // exit();
}

// --- Handle Delete Action ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash_message'] = 'Product deleted successfully.';
    header('Location: products.php');
    // exit();
}

// --- Pagination Logic ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$search_term = $_GET['search'] ?? '';

// --- Fetch Data for Display ---
$product_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $product_to_edit = $stmt->fetch();
}

$categories = $pdo->query("SELECT id, name_en FROM categories ORDER BY name_en")->fetchAll();

// Get total count for pagination
$sql_count = "SELECT COUNT(*) FROM products WHERE name_en LIKE ?";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute(['%' . $search_term . '%']);
$total_products = $stmt_count->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Fetch paginated products
$sql = "
    SELECT p.*, c.name_en as category_name,
           (SELECT SUM(r.quantity_used * i.cost) 
            FROM recipes r 
            JOIN inventory_items i ON r.inventory_item_id = i.id 
            WHERE r.product_id = p.id) as cogs
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.name_en LIKE :search
    ORDER BY p.id DESC
    LIMIT :limit OFFSET :offset
";
// FIX: Use bindValue to explicitly set parameter types for LIMIT and OFFSET
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search_term . '%');
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// --- Display Flash Messages ---
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_message_type = $_SESSION['flash_message_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
?>

<!-- Alpine.js container for managing form visibility -->
<div class="container mx-auto px-4" x-data="{ showForm: <?= $product_to_edit ? 'true' : 'false' ?> }">
    
    <!-- Sticky Header for Search and Add -->
    <div class="sticky top-0 z-10 bg-gray-100 py-4 mb-6">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
            <h1 class="text-3xl font-bold">Manage Products</h1>
            <div class="flex items-center gap-4">
                <form method="GET" class="flex-grow">
                    <div class="relative">
                        <input type="text" name="search" placeholder="Search by name..." value="<?= e($search_term) ?>" class="form-input w-full md:w-64 pl-10">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </form>
                <button @click="showForm = !showForm" class="btn-brand flex-shrink-0">
                    <i class="fas" :class="showForm ? 'fa-times' : 'fa-plus'"></i>
                    <span class="hidden sm:inline ml-2" x-text="showForm ? 'Close Form' : 'Add New'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($flash_message): ?>
        <div class="p-4 mb-6 rounded-lg <?= $flash_message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700' ?>">
            <p><?= e($flash_message) ?></p>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Product Form (Collapsible) -->
    <div x-show="showForm" x-transition class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4"><?= $product_to_edit ? 'Edit Product' : 'Add New Product' ?></h2>
        <form action="products.php" method="POST">
            <input type="hidden" name="action" value="<?= $product_to_edit ? 'update' : 'create' ?>">
            <?php if ($product_to_edit): ?>
                <input type="hidden" name="id" value="<?= e($product_to_edit['id']) ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700">Name (EN)</label>
                    <input type="text" name="name_en" class="form-input" value="<?= e($product_to_edit['name_en'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="block text-gray-700">Name (MM)</label>
                    <input type="text" name="name_mm" class="form-input" value="<?= e($product_to_edit['name_mm'] ?? '') ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700">Description (EN)</label>
                    <textarea name="description_en" class="form-input"><?= e($product_to_edit['description_en'] ?? '') ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700">Description (MM)</label>
                    <textarea name="description_mm" class="form-input"><?= e($product_to_edit['description_mm'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-gray-700">Price (Ks)</label>
                    <input type="number" name="price" step="1" class="form-input" value="<?= e($product_to_edit['price'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="block text-gray-700">Category</label>
                    <select name="category_id" class="form-input bg-white">
                        <option value="">Select a category</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= isset($product_to_edit) && $product_to_edit['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                <?= e($category['name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700">Image URL</label>
                    <input type="text" name="image_url" placeholder="https://example.com/image.jpg" class="form-input" value="<?= e($product_to_edit['image'] ?? '') ?>">
                    <?php if (isset($product_to_edit) && !empty($product_to_edit['image'])): ?>
                        <img src="<?= e($product_to_edit['image']) ?>" alt="Current Image" class="h-20 w-20 object-cover rounded mt-2">
                    <?php endif; ?>
                </div>
                <div class="md:col-span-2 flex space-x-6">
                    <label class="flex items-center"><input type="checkbox" name="is_available" value="1" class="mr-2" <?= isset($product_to_edit) ? ($product_to_edit['is_available'] ? 'checked' : '') : 'checked' ?>> Is Available</label>
                    <label class="flex items-center"><input type="checkbox" name="is_special_today" value="1" class="mr-2" <?= isset($product_to_edit) && $product_to_edit['is_special_today'] ? 'checked' : '' ?>> Today's Special</label>
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn-brand"><?= $product_to_edit ? 'Update Product' : 'Create Product' ?></button>
                <?php if ($product_to_edit): ?>
                    <a href="products.php" class="ml-4 text-gray-600 hover:underline">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Product List -->
    <div class="bg-white rounded-lg shadow-md">
        
        <!-- MOBILE VIEW: Cards (hidden on large screens) -->
        <div class="lg:hidden">
            <?php if (empty($products)): ?>
                <p class="p-6 text-center text-gray-500">No products found for "<?= e($search_term) ?>".</p>
            <?php endif; ?>
            <?php foreach($products as $product): ?>
            <div class="flex items-center p-4 border-b">
                <img src="<?= e($product['image'] ?: '/assets/uploads/placeholder.png') ?>" class="w-16 h-16 object-cover rounded-lg mr-4">
                <div class="flex-grow">
                    <p class="font-bold text-gray-800"><?= e($product['name_en']) ?></p>
                    <p class="text-sm text-orange-600 font-semibold"><?= number_format($product['price']) ?> Ks</p>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $product['is_available'] ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' ?>">
                        <?= $product['is_available'] ? 'Available' : 'Unavailable' ?>
                    </span>
                </div>
                <div class="flex flex-col space-y-2">
                    <a href="products.php?action=edit&id=<?= e($product['id']) ?>" class="btn-outline text-xs py-1 px-2">Edit</a>
                    <a href="products.php?action=delete&id=<?= e($product['id']) ?>" class="btn-danger text-xs py-1 px-2" onclick="return confirm('Are you sure?');">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- DESKTOP VIEW: Table (hidden on small screens) -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100"><th class="p-3">Image</th><th class="p-3">Name</th><th class="p-3">Category</th><th class="p-3">Price</th><th class="p-3">Cost</th><th class="p-3">Profit</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="8" class="p-6 text-center text-gray-500">No products found for "<?= e($search_term) ?>".</td></tr>
                    <?php endif; ?>
                    <?php foreach($products as $product): 
                        $profit = $product['price'] - ($product['cogs'] ?? 0);
                    ?>
                    <tr class="border-b">
                        <td class="p-3"><img src="<?= e($product['image'] ?: '/assets/uploads/placeholder.png') ?>" alt="<?= e($product['name_en']) ?>" class="h-12 w-12 object-cover rounded"></td>
                        <td class="p-3 font-medium"><?= e($product['name_en']) ?></td>
                        <td class="p-3 text-gray-600"><?= e($product['category_name'] ?? 'N/A') ?></td>
                        <td class="p-3"><?= number_format($product['price']) ?> Ks</td>
                        <td class="p-3 text-red-600"><?= number_format($product['cogs'] ?? 0, 2) ?> Ks</td>
                        <td class="p-3 font-bold <?= $profit > 0 ? 'text-green-600' : 'text-red-600' ?>"><?= number_format($profit, 2) ?> Ks</td>
                        <td class="p-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $product['is_available'] ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' ?>"><?= $product['is_available'] ? 'Available' : 'Unavailable' ?></span>
                            <?php if ($product['is_special_today']): ?><span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-200 text-yellow-800 ml-1">Special</span><?php endif; ?>
                        </td>
                        <td class="p-3 flex space-x-4">
                            <a href="products.php?action=edit&id=<?= e($product['id']) ?>" class="text-blue-500 hover:underline">Edit</a>
                            <a href="products.php?action=delete&id=<?= e($product['id']) ?>" class="text-red-500 hover:underline" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Links -->
        <div class="p-4 flex justify-between items-center">
            <div><span class="text-sm text-gray-600">Page <?= $page ?> of <?= $total_pages ?> (Total: <?= $total_products ?> products)</span></div>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= e($search_term) ?>" class="btn-outline text-sm py-1 px-3">&larr; Previous</a>
                <?php endif; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= e($search_term) ?>" class="btn-brand text-sm py-1 px-3">Next &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

