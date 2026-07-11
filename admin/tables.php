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
    exit();
}

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_message_type = $_SESSION['flash_message_type'] ?? 'error';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

$tables = $pdo->query("SELECT * FROM tables ORDER BY floor, table_number ASC")->fetchAll();
$base_url = "https://paicafes.com"; // FIX: Hardcode the base URL for consistency
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-purple-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Floor Operations</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Tables</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Manage QR tables, floor zones, and table status.</p>
        </div>
        <div class="liquid-surface rounded-2xl px-5 py-4 border">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Tables</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($tables) ?></p>
        </div>
    </div>

    <?php if ($flash_message): ?>
        <div class="p-4 mb-6 rounded-2xl <?= $flash_message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700' ?>">
            <p class="font-bold"><?= e($flash_message) ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white">Add New Table</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Each table receives a QR identifier for customer ordering.</p>
            </div>
            <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500">
                <i class="fas fa-qrcode"></i>
            </div>
        </div>
        <form action="tables.php" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-5 items-end">
            <input type="hidden" name="action" value="create">
            <div><label for="table_number" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Table Name</label><input type="text" name="table_number" id="table_number" class="form-input" required></div>
            <div><label for="floor" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Floor</label><input type="text" name="floor" id="floor" class="form-input" placeholder="Ground Floor" required></div>
            <div><label for="status" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Initial Status</label><select name="status" id="status" class="form-input bg-white"><option value="free">Free</option><option value="maintenance">Maintenance</option></select></div>
            <div><button type="submit" class="btn-brand w-full">Add Table</button></div>
        </form>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">Table Status Overview</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr><th class="p-4">Table</th><th class="p-4">Floor</th><th class="p-4">Status</th><th class="p-4">Change Status</th><th class="p-4 text-right">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($tables as $table):
                        $status_colors = ['free' => 'bg-green-100 text-green-800', 'in_use' => 'bg-blue-100 text-blue-800', 'needs_cleaning' => 'bg-yellow-100 text-yellow-800', 'reserved' => 'bg-purple-100 text-purple-800', 'maintenance' => 'bg-red-100 text-red-800'];
                        $color = $status_colors[$table['status']] ?? 'bg-gray-100';
                    ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-4 font-black text-slate-800 dark:text-white"><?= e($table['table_number']) ?></td>
                        <td class="p-4 text-slate-600 dark:text-slate-300"><?= e($table['floor']) ?></td>
                        <td class="p-4"><span class="px-3 py-1 text-xs font-black rounded-full <?= $color ?>"><?= e(ucwords(str_replace('_', ' ', $table['status']))) ?></span></td>
                        <td class="p-4">
                            <form method="POST" class="flex items-center space-x-2">
                                <input type="hidden" name="action" value="update_status"><input type="hidden" name="id" value="<?= $table['id'] ?>">
                                <select name="status" class="form-input p-2 bg-white text-sm">
                                    <option value="free" <?= $table['status'] == 'free' ? 'selected' : '' ?>>Free</option>
                                    <option value="needs_cleaning" <?= $table['status'] == 'needs_cleaning' ? 'selected' : '' ?>>Needs Cleaning</option>
                                    <option value="maintenance" <?= $table['status'] == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    <option value="reserved" <?= $table['status'] == 'reserved' ? 'selected' : '' ?>>Reserved</option>
                                </select>
                                <button type="submit" class="h-10 px-4 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-950 text-xs font-black uppercase tracking-widest flex-shrink-0">Update</button>
                            </form>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="print_qr.php?id=<?= e($table['id']) ?>" target="_blank" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-slate-500/10 text-slate-500 hover:bg-slate-700 hover:text-white transition-colors" title="Print QR"><i class="fas fa-qrcode text-xs"></i></a>
                                <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $table['id'] ?>"><button type="submit" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Delete"><i class="fas fa-trash text-xs"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tables)): ?>
                    <tr><td colspan="5" class="p-10 text-center text-slate-500">No tables configured yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
