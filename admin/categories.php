<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php'; 
require_admin_login();

// Handle form submissions for Create, Update, Delete
$action = $_POST['action'] ?? $_GET['action'] ?? 'view';
$id = $_POST['id'] ?? $_GET['id'] ?? null;
$name_en = $_POST['name_en'] ?? '';
$name_mm = $_POST['name_mm'] ?? '';
$message = '';

switch ($action) {
    case 'create':
        $stmt = $pdo->prepare("INSERT INTO categories (name_en, name_mm) VALUES (?, ?)");
        $stmt->execute([$name_en, $name_mm]);
        $message = "Category created successfully!";
        break;
    case 'update':
        $stmt = $pdo->prepare("UPDATE categories SET name_en = ?, name_mm = ? WHERE id = ?");
        $stmt->execute([$name_en, $name_mm, $id]);
        $message = "Category updated successfully!";
        break;
    case 'delete':
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Category deleted successfully!";
        break;
}

// Fetch category for editing if ID is present
$category_to_edit = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category_to_edit = $stmt->fetch();
}

// Fetch all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll();

// This is a simplified header for the admin area

?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Manage Categories</h1>
    
    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4"><?= $category_to_edit ? 'Edit Category' : 'Add New Category' ?></h2>
        <form action="categories.php" method="POST">
            <input type="hidden" name="action" value="<?= $category_to_edit ? 'update' : 'create' ?>">
            <?php if ($category_to_edit): ?>
                <input type="hidden" name="id" value="<?= e($category_to_edit['id']) ?>">
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="name_en" class="block text-gray-700">Name (English)</label>
                    <input type="text" name="name_en" id="name_en" class="w-full px-3 py-2 border rounded-lg" value="<?= e($category_to_edit['name_en'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="name_mm" class="block text-gray-700">Name (Myanmar)</label>
                    <input type="text" name="name_mm" id="name_mm" class="w-full px-3 py-2 border rounded-lg" value="<?= e($category_to_edit['name_mm'] ?? '') ?>" required>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                 <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">
                    <?= $category_to_edit ? 'Update Category' : 'Add Category' ?>
                </button>
                <?php if ($category_to_edit): ?>
                    <a href="categories.php" class="text-gray-600 hover:text-gray-800">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Existing Categories</h2>
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-3">ID</th>
                    <th class="p-3">Name (EN)</th>
                    <th class="p-3">Name (MM)</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr class="border-b">
                    <td class="p-3"><?= e($category['id']) ?></td>
                    <td class="p-3"><?= e($category['name_en']) ?></td>
                    <td class="p-3"><?= e($category['name_mm']) ?></td>
                    <td class="p-3">
                        <a href="categories.php?action=edit&id=<?= e($category['id']) ?>" class="text-blue-500 hover:underline mr-4">Edit</a>
                        <a href="categories.php?action=delete&id=<?= e($category['id']) ?>" class="text-red-500 hover:underline" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'partials/footer.php'; ?>