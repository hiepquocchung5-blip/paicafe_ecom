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

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-blue-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Team Access</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Admin Users</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Create staff accounts and manage admin roles.</p>
        </div>
        <div class="liquid-surface rounded-2xl px-5 py-4 border">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Team Members</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($admins) ?></p>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-2xl" role="alert">
            <p class="font-bold"><?= e($success_message) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-2xl" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white">Add New Admin User</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Assign the lowest role that still lets the user do their job.</p>
            </div>
            <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-500">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>
        <form action="admins.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="username" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Username</label>
                    <input type="text" name="username" id="username" class="form-input" required>
                </div>
                
                <div>
                    <label for="user_type" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Role</label>
                    <select name="user_type" id="user_type" class="form-input bg-white" required>
                        <option value="staff">Staff</option>
                        <option value="kitchen">Kitchen</option>
                        <option value="finance">Finance</option>
                        <option value="admin">Admin</option>
                        <option value="developer">Developer</option>
                    </select>
                </div>

                <div>
                    <label for="password" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Password</label>
                    <input type="password" name="password" id="password" class="form-input" required>
                </div>
                <div>
                    <label for="confirm_password" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-input" required>
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn-brand">Create User</button>
            </div>
        </form>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">Existing Admin Users</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="p-4">Username</th>
                        <th class="p-4">Role</th> <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-4 font-black text-slate-800 dark:text-white"><?= e($admin['username']) ?></td>
                        <td class="p-4">
                            <span class="px-3 py-1 text-xs font-black rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200">
                                <?= e(ucfirst($admin['user_type'])) ?>
                            </span>
                        </td>
                        <td class="p-4 text-right">
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                            <form action="admins.php" method="POST" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e($admin['id']) ?>">
                                <button type="submit" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Delete">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                            <?php else: ?>
                                <span class="px-3 py-1 text-xs font-black rounded-full bg-emerald-100 text-emerald-700">Current User</span>
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
