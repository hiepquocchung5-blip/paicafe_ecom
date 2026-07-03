<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php'; 
require_admin_login();

// Handle form submissions for Create, Update, Delete
$action = $_POST['action'] ?? 'view';
$id = $_POST['id'] ?? null;
$name_en = $_POST['name_en'] ?? '';
$name_mm = $_POST['name_mm'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}

// Fetch category for editing if ID is present
$category_to_edit = null;
if (($_GET['action'] ?? '') === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $category_to_edit = $stmt->fetch();
}

// Fetch all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll();

// This is a simplified header for the admin area

?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-teal-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Menu Structure</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Categories</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Organize menu groups for public ordering and POS filtering.</p>
        </div>
        <div class="liquid-surface rounded-2xl px-5 py-4 border">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Categories</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($categories) ?></p>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-5 py-4 rounded-2xl mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-circle-check"></i>
                <p class="font-bold"><?= e($message) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white"><?= $category_to_edit ? 'Edit Category' : 'Add New Category' ?></h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Keep names short so they scan well on mobile menus.</p>
            </div>
            <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-500/10 text-teal-500">
                <i class="fas fa-tags"></i>
            </div>
        </div>
        <form action="categories.php" method="POST">
            <input type="hidden" name="action" value="<?= $category_to_edit ? 'update' : 'create' ?>">
            <?php if ($category_to_edit): ?>
                <input type="hidden" name="id" value="<?= e($category_to_edit['id']) ?>">
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <div>
                    <label for="name_en" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Name (English)</label>
                    <input type="text" name="name_en" id="name_en" class="form-input" value="<?= e($category_to_edit['name_en'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="name_mm" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Name (Myanmar)</label>
                    <input type="text" name="name_mm" id="name_mm" class="form-input" value="<?= e($category_to_edit['name_mm'] ?? '') ?>" required>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                 <button type="submit" class="btn-brand">
                    <?= $category_to_edit ? 'Update Category' : 'Add Category' ?>
                </button>
                <?php if ($category_to_edit): ?>
                    <a href="categories.php" class="text-xs font-black uppercase tracking-widest text-slate-400 hover:text-red-500">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">Existing Categories</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="p-4">ID</th>
                        <th class="p-4">Name (EN)</th>
                        <th class="p-4">Name (MM)</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-4 text-xs font-mono text-slate-400">#<?= e($category['id']) ?></td>
                        <td class="p-4 font-bold text-slate-800 dark:text-white"><?= e($category['name_en']) ?></td>
                        <td class="p-4 text-slate-600 dark:text-slate-300"><?= e($category['name_mm']) ?></td>
                        <td class="p-4">
                            <div class="flex items-center justify-end gap-2">
                            <a href="categories.php?action=edit&id=<?= e($category['id']) ?>" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white transition-colors" title="Edit">
                                <i class="fas fa-pen text-xs"></i>
                            </a>
                            <form method="POST" action="categories.php" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e($category['id']) ?>">
                                <button type="submit" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Delete">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                    <tr><td colspan="4" class="p-10 text-center text-slate-500">No categories yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
