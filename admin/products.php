<?php
// We must include functions.php for security and helper functions
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php'; // This includes all the admin UI

// --- Permission Check ---
if (!has_permission('manage_products')) {
    die('Access Denied. You do not have permission to manage products.');
}

$errors = [];
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_message_type = $_SESSION['flash_message_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

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
            $_POST['discount_percentage'] ?? 0,
            !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            trim($_POST['image_url'] ?? ''),
            isset($_POST['is_available']) ? 1 : 0,
            isset($_POST['is_special_today']) ? 1 : 0
        ];

        try {
            if ($action === 'create') {
                $sql = "INSERT INTO products (name_en, name_mm, description_en, description_mm, price, discount_percentage, category_id, image, is_available, is_special_today) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['flash_message'] = 'Product created successfully.';
                log_activity($pdo, "Created product: " . $name_en);
            } elseif ($action === 'update') {
                $sql = "UPDATE products SET name_en=?, name_mm=?, description_en=?, description_mm=?, price=?, discount_percentage=?, category_id=?, image=?, is_available=?, is_special_today=? WHERE id=?";
                $params[] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['flash_message'] = 'Product updated successfully.';
                log_activity($pdo, "Updated product ID #$id");
            }
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_message_type'] = 'error';
        }
    } else {
        $_SESSION['flash_message'] = implode(' ', $errors);
        $_SESSION['flash_message_type'] = 'error';
    }
    header('Location: products.php');
    exit();
}

// --- Handle Delete Action ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = 'Product deleted successfully.';
        log_activity($pdo, "Deleted product ID #$id");
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Delete failed: This product might be linked to orders.';
        $_SESSION['flash_message_type'] = 'error';
    }
    header('Location: products.php');
    exit();
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

$sql_count = "SELECT COUNT(*) FROM products WHERE name_en LIKE ?";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute(['%' . $search_term . '%']);
$total_products = $stmt_count->fetchColumn();
$total_pages = ceil($total_products / $limit);

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
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search_term . '%');
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto" x-data="{ showForm: <?= $product_to_edit ? 'true' : 'false' ?> }">
    
    <!-- Header & Search -->
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-orange-600 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.4em] text-slate-500 dark:text-slate-400">Inventory Assets</h2>
            </div>
            <h1 class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter leading-none">Product Matrix</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Managing <?= number_format($total_products) ?> active SKUs across the network.</p>
        </div>

        <div class="flex items-center space-x-4">
            <form method="GET" class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-500 transition-colors"></i>
                <input type="text" name="search" placeholder="Search sequence..." value="<?= e($search_term) ?>" 
                       class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-2xl pl-12 pr-6 py-3 text-sm font-bold focus:outline-none focus:border-orange-500/50 w-72 transition-all">
            </form>
            <button @click="showForm = !showForm" 
                    class="bg-orange-600 hover:bg-orange-500 text-white px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all shadow-lg shadow-orange-600/20 flex items-center space-x-3">
                <i class="fas" :class="showForm ? 'fa-times' : 'fa-plus'"></i>
                <span x-text="showForm ? 'Abort Entry' : 'Inject Asset'"></span>
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($flash_message): ?>
        <div class="mb-8 p-5 rounded-2xl border flex items-center space-x-4 <?= $flash_message_type === 'success' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' : 'bg-red-500/10 border-red-500/20 text-red-500' ?>">
            <i class="fas <?= $flash_message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
            <p class="text-xs font-black uppercase tracking-widest"><?= e($flash_message) ?></p>
        </div>
    <?php endif; ?>

    <!-- Entry Terminal (Form) -->
    <div x-show="showForm" x-transition x-cloak 
         class="mb-12 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 p-10 shadow-2xl">
        <h2 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight mb-8">
            <?= $product_to_edit ? 'Edit Existing Protocol' : 'New Asset Protocol' ?>
        </h2>
        
        <form action="products.php" method="POST" class="space-y-8">
            <input type="hidden" name="action" value="<?= $product_to_edit ? 'update' : 'create' ?>">
            <?php if ($product_to_edit): ?>
                <input type="hidden" name="id" value="<?= e($product_to_edit['id']) ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Alias (EN)</label>
                    <input type="text" name="name_en" required value="<?= e($product_to_edit['name_en'] ?? '') ?>"
                           class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Alias (MM)</label>
                    <input type="text" name="name_mm" value="<?= e($product_to_edit['name_mm'] ?? '') ?>"
                           class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 transition-all">
                </div>
                <div class="md:col-span-2 space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Metadata / Description</label>
                    <textarea name="description_en" rows="3"
                              class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 transition-all"><?= e($product_to_edit['description_en'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 md:col-span-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Base Valuation (KS)</label>
                        <input type="number" name="price" required step="1" value="<?= e($product_to_edit['price'] ?? '') ?>"
                               class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Discount Ratio (%)</label>
                        <input type="number" name="discount_percentage" step="0.01" min="0" max="100" value="<?= e($product_to_edit['discount_percentage'] ?? '0') ?>"
                               class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Cluster / Category</label>
                        <select name="category_id" class="w-full bg-slate-100 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 transition-all appearance-none">
                            <option value="">UNCATEGORIZED</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= isset($product_to_edit) && $product_to_edit['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= e(strtoupper($category['name_en'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Visual Source (URL)</label>
                    <div class="flex items-center space-x-4">
                        <input type="text" name="image_url" placeholder="https://..." value="<?= e($product_to_edit['image'] ?? '') ?>"
                               class="flex-1 bg-slate-100 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 text-slate-800 dark:text-white font-bold focus:ring-2 focus:ring-orange-500/50 transition-all">
                        <?php if (!empty($product_to_edit['image'])): ?>
                            <img src="<?= e($product_to_edit['image']) ?>" class="h-14 w-14 object-cover rounded-xl border-2 border-white dark:border-slate-800 shadow-lg">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="md:col-span-2 flex items-center space-x-8 px-2">
                    <label class="flex items-center cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" name="is_available" value="1" class="sr-only" <?= isset($product_to_edit) ? ($product_to_edit['is_available'] ? 'checked' : '') : 'checked' ?>>
                            <div class="w-10 h-6 bg-slate-200 dark:bg-slate-800 rounded-full transition-colors group-hover:bg-slate-300 dark:group-hover:bg-slate-700"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform"></div>
                        </div>
                        <span class="ml-3 text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-300">Available</span>
                    </label>

                    <label class="flex items-center cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" name="is_special_today" value="1" class="sr-only" <?= isset($product_to_edit) && $product_to_edit['is_special_today'] ? 'checked' : '' ?>>
                            <div class="w-10 h-6 bg-slate-200 dark:bg-slate-800 rounded-full transition-colors group-hover:bg-slate-300 dark:group-hover:bg-slate-700"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform"></div>
                        </div>
                        <span class="ml-3 text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-300">Daily Special</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center space-x-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="submit" class="bg-slate-900 dark:bg-white text-white dark:text-slate-950 px-10 py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:scale-105 transition-all shadow-xl">
                    <?= $product_to_edit ? 'Update Matrix' : 'Authorize Asset' ?>
                </button>
                <?php if ($product_to_edit): ?>
                    <a href="products.php" class="text-[10px] font-black text-slate-400 hover:text-red-500 uppercase tracking-widest transition-colors">Cancel Protocol</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Inventory Terminal (List) -->
    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl shadow-slate-200/50 dark:shadow-none">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Visual</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Asset Specs</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Category</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Pricing Link</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Net Margin</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Operational</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.2em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach($products as $product): 
                        $discounted_price = $product['price'] - ($product['price'] * ($product['discount_percentage'] / 100));
                        $profit = $discounted_price - ($product['cogs'] ?? 0);
                    ?>
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-200">
                        <td class="p-6">
                            <div class="w-14 h-14 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border-2 border-white dark:border-slate-800 shadow-sm group-hover:scale-110 transition-transform duration-300">
                                <img src="<?= e($product['image'] ?: '/assets/uploads/placeholder.png') ?>" class="w-full h-full object-cover">
                            </div>
                        </td>
                        <td class="p-6">
                            <p class="text-sm font-black text-slate-800 dark:text-white group-hover:text-orange-600 transition-colors uppercase"><?= e($product['name_en']) ?></p>
                            <p class="text-[9px] font-mono text-slate-400 dark:text-slate-500 uppercase mt-1">ID: <?= sprintf("%05d", $product['id']) ?></p>
                        </td>
                        <td class="p-6">
                            <span class="text-[10px] font-black uppercase text-slate-500 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700">
                                <?= e($product['category_name'] ?? 'GLOBAL') ?>
                            </span>
                        </td>
                        <td class="p-6">
                            <?php if ($product['discount_percentage'] > 0): ?>
                                <p class="text-[10px] text-slate-400 line-through leading-none"><?= number_format($product['price']) ?></p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <p class="text-sm font-black text-orange-600"><?= number_format($discounted_price) ?></p>
                                    <span class="text-[8px] bg-red-600 text-white px-1.5 py-0.5 rounded font-black">-<?= (float)$product['discount_percentage'] ?>%</span>
                                </div>
                            <?php else: ?>
                                <p class="text-sm font-black text-slate-800 dark:text-white"><?= number_format($product['price']) ?> <span class="text-[10px] text-slate-400 font-normal">KS</span></p>
                            <?php endif; ?>
                        </td>
                        <td class="p-6">
                            <p class="text-xs font-black <?= $profit > 0 ? 'text-emerald-500' : 'text-red-500' ?>">
                                <?= number_format($profit) ?> <span class="text-[8px] opacity-50 uppercase">Margin</span>
                            </p>
                            <p class="text-[9px] font-mono text-slate-400 mt-1">COGS: <?= number_format($product['cogs'] ?? 0) ?></p>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 rounded-full <?= $product['is_available'] ? 'bg-emerald-500' : 'bg-red-500' ?>"></div>
                                <span class="text-[9px] font-black uppercase tracking-widest <?= $product['is_available'] ? 'text-emerald-500' : 'text-red-500' ?>">
                                    <?= $product['is_available'] ? 'Online' : 'Offline' ?>
                                </span>
                            </div>
                            <?php if ($product['is_special_today']): ?>
                                <span class="text-[8px] font-black uppercase tracking-widest text-orange-500 mt-1 block">★ Sync Feature</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="products.php?action=edit&id=<?= e($product['id']) ?>" 
                                   class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white transition-all">
                                    <i class="fas fa-pen-nib text-xs"></i>
                                </a>
                                <a href="products.php?action=delete&id=<?= e($product['id']) ?>" 
                                   onclick="return confirm('Confirm asset termination?');"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pager -->
        <div class="p-8 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/30 dark:bg-slate-950/30">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sector <?= $page ?> / <?= $total_pages ?></span>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= e($search_term) ?>" 
                       class="px-5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-orange-600 hover:text-white transition-all">Previous</a>
                <?php endif; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= e($search_term) ?>" 
                       class="px-5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-orange-600 hover:text-white transition-all">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* Switch Style */
    input:checked ~ .dot { transform: translateX(100%); background-color: #ea580c; }
    input:checked ~ .bg-slate-200 { background-color: rgba(234, 88, 12, 0.2); }
</style>

<?php include 'partials/footer.php'; ?>
