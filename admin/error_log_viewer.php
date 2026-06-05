<?php
// include __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// --- SECURITY: Strictly for developers ---
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'developer') {
    die("Access Denied. This area is for developers only.");
}

$error_log_path_admin  = dirname(__DIR__) . '/admin/error_log';
$error_log_path_public = dirname(__DIR__) . '/error_log';

// Handle Clear Log action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_log'])) {
    $target = $_POST['clear_log']; // 'admin' or 'public'
    $file_to_clear = ($target === 'public') ? $error_log_path_public : $error_log_path_admin;

    if (file_exists($file_to_clear)) {
        file_put_contents($file_to_clear, ''); // empty the file
    }
    header("Location: error_log_viewer.php?cleared={$target}");
    exit();
}

// --- Load logs ---
$log_entries_admin  = [];
$log_entries_public = [];

// Admin log
if (file_exists($error_log_path_admin) && filesize($error_log_path_admin) > 0) {
    $lines = file($error_log_path_admin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $log_entries_admin = array_reverse($lines);
}

// Public log
if (file_exists($error_log_path_public) && filesize($error_log_path_public) > 0) {
    $lines = file($error_log_path_public, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $log_entries_public = array_reverse($lines);
}
?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Server Error Log Viewer</h1>

    <!-- Success message -->
    <?php if (isset($_GET['cleared'])): ?>
        <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800 border border-green-300">
            ✅ Successfully cleared the 
            <strong><?= e(ucfirst($_GET['cleared'])) ?> error log</strong>.
        </div>
    <?php endif; ?>

    <!-- Admin log -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Admin Error Log</h2>
        <form method="POST" onsubmit="return confirm('Are you sure you want to clear the ADMIN error log?');">
            <button type="submit" name="clear_log" value="admin" class="btn-danger">
                <i class="fas fa-trash-alt mr-2"></i>Clear Admin Log
            </button>
        </form>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <p class="text-sm text-gray-600 mb-4">
            Displaying entries from: <code class="bg-gray-200 p-1 rounded"><?= e($error_log_path_admin) ?></code>
        </p>
        <div class="bg-gray-800 text-white font-mono text-sm p-4 rounded-lg h-96 overflow-y-auto">
            <?php if (empty($log_entries_admin)): ?>
                <p class="text-gray-400">The admin error log file is empty.</p>
            <?php else: ?>
                <?php foreach ($log_entries_admin as $entry): ?>
                    <div><?= e($entry) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Public log -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Public Error Log</h2>
        <form method="POST" onsubmit="return confirm('Are you sure you want to clear the PUBLIC error log?');">
            <button type="submit" name="clear_log" value="public" class="btn-danger">
                <i class="fas fa-trash-alt mr-2"></i>Clear Public Log
            </button>
        </form>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <p class="text-sm text-gray-600 mb-4">
            Displaying entries from: <code class="bg-gray-200 p-1 rounded"><?= e($error_log_path_public) ?></code>
        </p>
        <div class="bg-gray-800 text-white font-mono text-sm p-4 rounded-lg h-96 overflow-y-auto">
            <?php if (empty($log_entries_public)): ?>
                <p class="text-gray-400">The public error log file is empty.</p>
            <?php else: ?>
                <?php foreach ($log_entries_public as $entry): ?>
                    <div><?= e($entry) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
