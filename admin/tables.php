<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php'; 
include __DIR__ . '/partials/header.php';

$errors = [];
$success_message = '';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;
    $table_number = trim($_POST['table_number'] ?? '');
    $floor = trim($_POST['floor'] ?? 'Ground Floor');
    $status = $_POST['status'] ?? 'free';

    if ($action === 'create') {
        if (!empty($table_number)) {
            $qr_identifier = uniqid('table_', true);
            $stmt = $pdo->prepare("INSERT INTO tables (table_number, floor, qr_code_identifier, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$table_number, $floor, $qr_identifier, $status]);
            $_SESSION['flash_message'] = "Table '{$table_number}' created successfully!";
            $_SESSION['flash_message_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = "Table name/number cannot be empty.";
            $_SESSION['flash_message_type'] = 'error';
        }
    } elseif ($action === 'update_status') {
        $stmt = $pdo->prepare("UPDATE tables SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $_SESSION['flash_message'] = "Table status updated successfully.";
        $_SESSION['flash_message_type'] = 'success';
    } 
    // FIX: The delete logic was inside an elseif, it should be separate
    elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM tables WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = "Table deleted successfully.";
        $_SESSION['flash_message_type'] = 'success';
    }
    header('Location: tables.php');
    // exit();
}

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_message_type = $_SESSION['flash_message_type'] ?? 'error';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

$tables = $pdo->query("SELECT * FROM tables ORDER BY floor, table_number ASC")->fetchAll();
$base_url = "https://paicafe.online"; // FIX: Hardcode the base URL for consistency
?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Manage Tables</h1>

    <?php if ($flash_message): ?>
        <div class="p-4 mb-6 rounded-lg <?= $flash_message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700' ?>">
            <p><?= e($flash_message) ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4">Add New Table</h2>
        <form action="tables.php" method="POST" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="action" value="create">
            <div class="flex-grow"><label for="table_number" class="block text-gray-700">Table Name</label><input type="text" name="table_number" id="table_number" class="form-input" required></div>
            <div class="flex-grow"><label for="floor" class="block text-gray-700">Floor</label><input type="text" name="floor" id="floor" class="form-input" placeholder="e.g., Ground Floor" required></div>
            <div><label for="status" class="block text-gray-700">Initial Status</label><select name="status" id="status" class="form-input bg-white"><option value="free">Free</option><option value="maintenance">Maintenance</option></select></div>
            <div><button type="submit" class="btn-brand">Add Table</button></div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Table Status Overview</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr class="bg-gray-100"><th class="p-3">Table</th><th class="p-3">Floor</th><th class="p-3">Status</th><th class="p-3">Change Status</th><th class="p-3">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($tables as $table): 
                        $status_colors = ['free' => 'bg-green-100 text-green-800', 'in_use' => 'bg-blue-100 text-blue-800', 'needs_cleaning' => 'bg-yellow-100 text-yellow-800', 'reserved' => 'bg-purple-100 text-purple-800', 'maintenance' => 'bg-red-100 text-red-800'];
                        $color = $status_colors[$table['status']] ?? 'bg-gray-100';
                    ?>
                    <tr class="border-b">
                        <td class="p-3 font-medium"><?= e($table['table_number']) ?></td>
                        <td class="p-3 text-gray-600"><?= e($table['floor']) ?></td>
                        <td class="p-3"><span class="px-3 py-1 text-sm font-semibold rounded-full <?= $color ?>"><?= e(ucwords(str_replace('_', ' ', $table['status']))) ?></span></td>
                        <td class="p-3">
                            <form method="POST" class="flex items-center space-x-2">
                                <input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="<?= $table['id'] ?>">
                                <select name="status" class="form-input p-2 bg-white text-sm">
                                    <option value="free" <?= $table['status'] == 'free' ? 'selected' : '' ?>>Free</option>
                                    <option value="needs_cleaning" <?= $table['status'] == 'needs_cleaning' ? 'selected' : '' ?>>Needs Cleaning</option>
                                    <option value="maintenance" <?= $table['status'] == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    <option value="reserved" <?= $table['status'] == 'reserved' ? 'selected' : '' ?>>Reserved</option>
                                </select>
                                <button type="submit" class="bg-gray-600 text-white px-3 py-2 text-sm rounded hover:bg-gray-700 flex-shrink-0">Update</button>
                            </form>
                        </td>
                        <td class="p-3">
                            <div class="flex items-center space-x-2">
                                <!-- NEW: Print QR Button -->
                                <a href="print_qr.php?id=<?= e($table['id']) ?>" target="_blank" class="text-xs bg-gray-500 text-white px-2 py-1 rounded hover:bg-gray-600">Print QR</a>
                                <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $table['id'] ?>"><button type="submit" class="text-red-500 hover:underline">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>