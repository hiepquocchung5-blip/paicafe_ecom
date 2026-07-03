<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// Only developers can manage permissions
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'developer') {
    die("Access Denied: You do not have permission to view this page.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $permissions_posted = $_POST['permissions'] ?? [];
    
    // Start a transaction
    $pdo->beginTransaction();
    try {
        // First, delete all existing permissions
        $pdo->query("DELETE FROM role_permissions");

        // Then, insert the new permissions from the form
        $stmt = $pdo->prepare("INSERT INTO role_permissions (user_type, permission_id) VALUES (?, ?)");
        foreach ($permissions_posted as $user_type => $permission_ids) {
            foreach ($permission_ids as $permission_id => $on) {
                $stmt->execute([$user_type, $permission_id]);
            }
        }
        $pdo->commit();
        $_SESSION['flash_message'] = "Permissions updated successfully.";
        $_SESSION['flash_message_type'] = 'success';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = "An error occurred while updating permissions.";
        $_SESSION['flash_message_type'] = 'error';
    }
    header('Location: permissions.php');
    exit();
}

// Fetch all permissions and roles
$permissions = $pdo->query("SELECT * FROM permissions ORDER BY name ASC")->fetchAll();
$roles = ['admin', 'staff', 'finance', 'kitchen']; // Developer role is excluded as it has all permissions

// Fetch current role permissions to pre-check the boxes
$role_permissions_raw = $pdo->query("SELECT * FROM role_permissions")->fetchAll();
$current_permissions = [];
foreach ($role_permissions_raw as $rp) {
    $current_permissions[$rp['user_type']][$rp['permission_id']] = true;
}
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-rose-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Access Control</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Role Permissions</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Developer-only permission matrix for admin roles.</p>
        </div>
        <div class="liquid-surface rounded-2xl px-5 py-4 border">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Permissions</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($permissions) ?></p>
        </div>
    </div>
    <form method="POST">
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="p-4">Permission</th>
                            <?php foreach ($roles as $role): ?>
                                <th class="p-4 text-center"><?= e(ucfirst($role)) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions as $permission): ?>
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="p-4 font-black text-slate-800 dark:text-white"><?= e(ucwords(str_replace('_', ' ', $permission['name']))) ?></td>
                            <?php foreach ($roles as $role): ?>
                                <td class="p-4 text-center">
                                    <input type="checkbox" name="permissions[<?= e($role) ?>][<?= $permission['id'] ?>]"
                                           class="h-5 w-5 accent-teal-600"
                                           <?= isset($current_permissions[$role][$permission['id']]) ? 'checked' : '' ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                <button type="submit" class="btn-brand">Save Permissions</button>
            </div>
        </div>
    </form>
</div>
<?php include 'partials/footer.php'; ?>
