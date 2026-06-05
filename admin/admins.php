<?php
require_once __DIR__ . '/../includes/db_connect.php';
// require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php';

$errors = [];
$success_message = '';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Action: Create New Admin ---
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $user_type = trim($_POST['user_type'] ?? ''); // Get the user_type from the form
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Allowed roles to prevent invalid submissions
        $allowed_roles = ['developer', 'admin', 'staff', 'finance', 'kitchen'];

        // Validation
        if (empty($username) || empty($password) || empty($user_type)) {
            $errors[] = "Username, User Type, and Password are required.";
        }
        if (!in_array($user_type, $allowed_roles)) {
            $errors[] = "Invalid user type selected.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        
        // If no errors, create the admin
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Updated SQL to include user_type
            $stmt = $pdo->prepare("INSERT INTO admins (username, user_type, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $user_type, $hashed_password])) {
                $success_message = "Admin user '{$username}' created successfully!";
            } else {
                $errors[] = "Failed to create admin. Username might already exist.";
            }
        }
    }

    // --- Action: Delete Admin ---
    if ($action === 'delete') {
        $admin_id_to_delete = $_POST['id'] ?? 0;
        
        if ($admin_id_to_delete == $_SESSION['admin_id']) {
            $errors[] = "You cannot delete your own account.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$admin_id_to_delete]);
            $success_message = "Admin user deleted successfully.";
        }
    }
}

// Fetch all admins to display in the list, including their user_type
$stmt = $pdo->query("SELECT id, username, user_type FROM admins ORDER BY username ASC");
$admins = $stmt->fetchAll();
?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Manage Admins</h1>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?= e($success_message) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4">Add New Admin User</h2>
        <form action="admins.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="username" class="block text-gray-700">Username</label>
                    <input type="text" name="username" id="username" class="w-full mt-1 p-2 border rounded" required>
                </div>
                
                <div>
                    <label for="user_type" class="block text-gray-700">User Type / Role</label>
                    <select name="user_type" id="user_type" class="w-full mt-1 p-2 border rounded bg-white" required>
                        <option value="staff">Staff</option>
                        <option value="kitchen">Kitchen</option>
                        <option value="finance">Finance</option>
                        <option value="admin">Admin</option>
                        <option value="developer">Developer</option>
                    </select>
                </div>

                <div>
                    <label for="password" class="block text-gray-700">Password</label>
                    <input type="password" name="password" id="password" class="w-full mt-1 p-2 border rounded" required>
                </div>
                <div>
                    <label for="confirm_password" class="block text-gray-700">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="w-full mt-1 p-2 border rounded" required>
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">
                    Create User
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Existing Admin Users</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3">Username</th>
                        <th class="p-3">Role</th> <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr class="border-b">
                        <td class="p-3 font-medium"><?= e($admin['username']) ?></td>
                        <td class="p-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-800">
                                <?= e(ucfirst($admin['user_type'])) ?>
                            </span>
                        </td>
                        <td class="p-3">
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                            <form action="admins.php" method="POST" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e($admin['id']) ?>">
                                <button type="submit" class="text-red-500 hover:underline">Delete</button>
                            </form>
                            <?php else: ?>
                                <span class="text-gray-400 cursor-not-allowed">Current User</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>