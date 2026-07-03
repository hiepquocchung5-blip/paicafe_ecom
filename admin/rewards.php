<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

$errors = [];
$success_message = '';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM loyalty_rewards WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = "Reward deleted successfully.";
        }
        header('Location: rewards.php');
        exit();
    }

    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $points_cost = (int)($_POST['points_cost'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title) || $points_cost <= 0) {
        $errors[] = "Reward title and a valid points cost are required.";
    }

    if (empty($errors)) {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO loyalty_rewards (title, description, points_cost, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description, $points_cost, $is_active]);
            $success_message = "Reward created successfully.";
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE loyalty_rewards SET title=?, description=?, points_cost=?, is_active=? WHERE id=?");
            $stmt->execute([$title, $description, $points_cost, $is_active, $id]);
            $success_message = "Reward updated successfully.";
        }
    }
}

$success_message = $_SESSION['flash_message'] ?? $success_message;
unset($_SESSION['flash_message']);

// --- Fetch Data for Display ---
$reward_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM loyalty_rewards WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $reward_to_edit = $stmt->fetch();
}

$rewards = $pdo->query("SELECT * FROM loyalty_rewards ORDER BY points_cost ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-amber-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Loyalty Program</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Rewards</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Create point-based rewards customers can redeem.</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="liquid-surface rounded-2xl px-5 py-4 border">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Rewards</p>
                <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($rewards) ?></p>
            </div>
            <div class="liquid-surface rounded-2xl px-5 py-4 border">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Active</p>
                <p class="text-3xl font-black text-emerald-500 leading-none mt-1"><?= count(array_filter($rewards, static fn($reward) => (int)$reward['is_active'] === 1)) ?></p>
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
                <h2 class="text-2xl font-black text-slate-800 dark:text-white"><?= $reward_to_edit ? 'Edit Reward' : 'Add New Reward' ?></h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Set a clear title and a point cost customers can understand.</p>
            </div>
            <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500">
                <i class="fas fa-trophy"></i>
            </div>
        </div>
        <form action="rewards.php" method="POST">
            <input type="hidden" name="action" value="<?= $reward_to_edit ? 'update' : 'create' ?>">
            <?php if ($reward_to_edit): ?>
                <input type="hidden" name="id" value="<?= e($reward_to_edit['id']) ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Reward Title</label>
                    <input type="text" name="title" id="title" class="form-input" value="<?= e($reward_to_edit['title'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="points_cost" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Points Cost</label>
                    <input type="number" name="points_cost" id="points_cost" class="form-input" value="<?= e($reward_to_edit['points_cost'] ?? '') ?>" required>
                </div>
                <div class="md:col-span-2">
                    <label for="description" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Description</label>
                    <textarea name="description" id="description" class="form-input"><?= e($reward_to_edit['description'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-6 flex flex-wrap justify-between items-center gap-4">
                <button type="submit" class="btn-brand">
                    <?= $reward_to_edit ? 'Update Reward' : 'Add Reward' ?>
                </button>
                <label class="flex items-center text-sm font-bold text-slate-600 dark:text-slate-300">
                    <input type="checkbox" name="is_active" value="1" class="mr-2 h-5 w-5 accent-teal-600" <?= isset($reward_to_edit) ? ($reward_to_edit['is_active'] ? 'checked' : '') : 'checked' ?>> Active
                </label>
            </div>
        </form>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">Existing Rewards</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="p-4">Title</th>
                        <th class="p-4">Cost</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rewards as $reward): ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-4">
                            <p class="font-black text-slate-800 dark:text-white"><?= e($reward['title']) ?></p>
                            <?php if (!empty($reward['description'])): ?>
                                <p class="text-xs text-slate-500 mt-1 line-clamp-1"><?= e($reward['description']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 font-black text-amber-500"><?= e($reward['points_cost']) ?> pts</td>
                        <td class="p-4">
                            <span class="px-3 py-1 text-xs font-black rounded-full <?= $reward['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $reward['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center justify-end gap-2">
                            <a href="rewards.php?action=edit&id=<?= e($reward['id']) ?>" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white transition-colors" title="Edit">
                                <i class="fas fa-pen text-xs"></i>
                            </a>
                            <form method="POST" action="rewards.php" onsubmit="return confirm('Are you sure you want to delete this reward?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e($reward['id']) ?>">
                                <button type="submit" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Delete">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rewards)): ?>
                    <tr><td colspan="4" class="p-10 text-center text-slate-500">No rewards configured yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
