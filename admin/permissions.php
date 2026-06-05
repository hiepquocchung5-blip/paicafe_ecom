<?php
require_once __DIR__ . '/includes/db_connect.php';
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

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Manage Role Permissions</h1>
    <form method="POST">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-3">Permission</th>
                            <?php foreach ($roles as $role): ?>
                                <th class="p-3 text-center"><?= e(ucfirst($role)) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions as $permission): ?>
                        <tr class="border-b">
                            <td class="p-3 font-medium"><?= e(ucwords(str_replace('_', ' ', $permission['name']))) ?></td>
                            <?php foreach ($roles as $role): ?>
                                <td class="p-3 text-center">
                                    <input type="checkbox" name="permissions[<?= e($role) ?>][<?= $permission['id'] ?>]"
                                           class="h-5 w-5"
                                           <?= isset($current_permissions[$role][$permission['id']]) ? 'checked' : '' ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn-brand">Save Permissions</button>
            </div>
        </div>
    </form>
</div>
<?php include 'partials/footer.php'; ?>