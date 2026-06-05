<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

$errors = [];
$success_message = '';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
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

// --- Handle Delete Action ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM loyalty_rewards WHERE id = ?");
    $stmt->execute([$id]);
    $success_message = "Reward deleted successfully.";
    // Redirect to clean the URL
    header('Location: rewards.php');
    exit();
}

// --- Fetch Data for Display ---
$reward_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM loyalty_rewards WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $reward_to_edit = $stmt->fetch();
}

$rewards = $pdo->query("SELECT * FROM loyalty_rewards ORDER BY points_cost ASC")->fetchAll();
?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Manage Loyalty Rewards</h1>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($success_message) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4"><?= $reward_to_edit ? 'Edit Reward' : 'Add New Reward' ?></h2>
        <form action="rewards.php" method="POST">
            <input type="hidden" name="action" value="<?= $reward_to_edit ? 'update' : 'create' ?>">
            <?php if ($reward_to_edit): ?>
                <input type="hidden" name="id" value="<?= e($reward_to_edit['id']) ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-gray-700">Reward Title</label>
                    <input type="text" name="title" id="title" class="form-input" value="<?= e($reward_to_edit['title'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="points_cost" class="block text-gray-700">Points Cost</label>
                    <input type="number" name="points_cost" id="points_cost" class="form-input" value="<?= e($reward_to_edit['points_cost'] ?? '') ?>" required>
                </div>
                <div class="md:col-span-2">
                    <label for="description" class="block text-gray-700">Description (Optional)</label>
                    <textarea name="description" id="description" class="form-input"><?= e($reward_to_edit['description'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-between items-center">
                <button type="submit" class="btn-brand">
                    <?= $reward_to_edit ? 'Update Reward' : 'Add Reward' ?>
                </button>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" class="mr-2 h-5 w-5" <?= isset($reward_to_edit) ? ($reward_to_edit['is_active'] ? 'checked' : '') : 'checked' ?>> Active
                </label>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Existing Rewards</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3">Title</th>
                        <th class="p-3">Cost</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rewards as $reward): ?>
                    <tr class="border-b">
                        <td class="p-3 font-medium"><?= e($reward['title']) ?></td>
                        <td class="p-3"><?= e($reward['points_cost']) ?> points</td>
                        <td class="p-3">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $reward['is_active'] ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' ?>">
                                <?= $reward['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="p-3 flex space-x-4">
                            <a href="rewards.php?action=edit&id=<?= e($reward['id']) ?>" class="text-blue-500 hover:underline">Edit</a>
                            <a href="rewards.php?action=delete&id=<?= e($reward['id']) ?>" class="text-red-500 hover:underline" onclick="return confirm('Are you sure you want to delete this reward?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>