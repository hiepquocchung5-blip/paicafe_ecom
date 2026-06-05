<?php
include __DIR__ . '/partials/header.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'developer') {
    die("Access Denied.");
}

$message = '';
$errors = [];

// --- Handle Database Maintenance ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['optimize_db'])) {
    try {
        $tables_stmt = $pdo->query("SHOW TABLES");
        $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $pdo->query("OPTIMIZE TABLE `{$table}`");
        }
        $message = "All database tables have been optimized successfully!";
    } catch (Exception $e) {
        $errors[] = "Database optimization failed: " . $e->getMessage();
    }
}

// --- System Health Checks ---
$db_status = ['ok' => true, 'message' => 'Connected successfully'];
try { $pdo->query("SELECT 1"); } catch (PDOException $e) {
    $db_status = ['ok' => false, 'message' => 'Connection Failed'];
}
$uploads_dir = dirname(__DIR__) . '/assets/uploads'; // Corrected path
$permissions_status = ['ok' => is_writable($uploads_dir), 'message' => is_writable($uploads_dir) ? 'Writable' : 'Not Writable!'];
$pdo_mysql_status = ['ok' => extension_loaded('pdo_mysql'), 'message' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Not Loaded!'];
$disk_free_space = @disk_free_space("/");
$disk_total_space = @disk_total_space("/");
if ($disk_total_space > 0) {
    $disk_free_percent = ($disk_free_space / $disk_total_space) * 100;
    $disk_space_status = ['ok' => $disk_free_percent > 10, 'message' => sprintf("%.1f%% Free", $disk_free_percent)];
} else {
    $disk_space_status = ['ok' => false, 'message' => 'Cannot determine disk space'];
}


// --- Fetch recent error logs (with a check to see if the table exists) ---
$error_logs = [];
$logs_status = ['ok' => true, 'message' => 'Readable'];
try {
    $pdo->query("SELECT 1 FROM `error_logs` LIMIT 1");
    $error_logs = $pdo->query("SELECT * FROM error_logs ORDER BY log_time DESC LIMIT 20")->fetchAll();
} catch (Exception $e) {
    $logs_status = ['ok' => false, 'message' => '`error_logs` table not found!'];
}
?>

<div class="container mx-auto px-4" x-data="{ phpInfoModal: false }">
    <h1 class="text-3xl font-bold mb-6">Developer Dashboard</h1>

    <?php if ($message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($message) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4">System Health Check</h2>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="border-l-4 <?= $db_status['ok'] ? 'border-green-500' : 'border-red-500' ?> p-3 bg-gray-50 rounded"><p class="font-semibold">DB Connection</p><p class="text-sm text-gray-600"><?= e($db_status['message']) ?></p></div>
            <div class="border-l-4 <?= $permissions_status['ok'] ? 'border-green-500' : 'border-red-500' ?> p-3 bg-gray-50 rounded"><p class="font-semibold">Uploads Writable</p><p class="text-sm text-gray-600"><?= e($permissions_status['message']) ?></p></div>
            <div class="border-l-4 <?= $pdo_mysql_status['ok'] ? 'border-green-500' : 'border-red-500' ?> p-3 bg-gray-50 rounded"><p class="font-semibold">PDO MySQL</p><p class="text-sm text-gray-600"><?= e($pdo_mysql_status['message']) ?></p></div>
            <div class="border-l-4 <?= $disk_space_status['ok'] ? 'border-green-500' : 'border-red-500' ?> p-3 bg-gray-50 rounded"><p class="font-semibold">Disk Space</p><p class="text-sm text-gray-600"><?= e($disk_space_status['message']) ?></p></div>
            <div class="border-l-4 <?= $logs_status['ok'] ? 'border-green-500' : 'border-red-500' ?> p-3 bg-gray-50 rounded"><p class="font-semibold">Error Log Table</p><p class="text-sm text-gray-600"><?= e($logs_status['message']) ?></p></div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">Database Maintenance</h2>
            <div class="space-y-4">
                <div><p class="text-gray-600 mb-2">Optimize tables to improve performance.</p><form method="POST"><button type="submit" name="optimize_db" class="btn-brand" onclick="return confirm('Optimize all tables?');">Optimize Database</button></form></div>
                <div class="border-t pt-4"><p class="text-gray-600 mb-2">Download a full backup of the database.</p><a href="backup_db.php" class="btn-outline">Download .sql Backup</a></div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">System Information</h2>
            <div class="space-y-2"><p><strong>PHP Version:</strong> <?= e(phpversion()) ?></p><p><strong>DB Driver:</strong> <?= e($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) ?></p></div>
            <button @click="phpInfoModal = true" class="mt-4 btn-outline text-sm py-2 px-4">View Full PHP Info</button>
        </div>
    </div>
    
    <!-- NEW: Grid for Live Activity and Error Logs -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
        
        <!-- Live Activity Column (Moved from index.php) -->
        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md" x-data="{ activities: [], pagination: {}, loading: true, fetchActivity(page = 1) { this.loading = true; fetch(`/api/get_activity.php?page=${page}`).then(res => res.json()).then(data => { if(data.status === 'success') { this.activities = data.activities; this.pagination = data.pagination; } this.loading = false; }); } }" x-init="fetchActivity()">
            <h2 class="text-2xl font-bold mb-4">Live Activity</h2>
            <div class="space-y-4 min-h-[300px] h-96 overflow-y-auto pos-scroll">
                <template x-if="loading"><p class="text-gray-500">Loading feed...</p></template>
                <template x-if="!loading && activities.length > 0"><div class="space-y-4"><template x-for="act in activities" :key="act.log_time"><div class="flex items-start text-sm"><i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i><div><p><span x-html="act.action"></span> <span x-show="act.username">by <strong x-text="act.username"></strong></span></p><p class="text-xs text-gray-500" x-text="new Date(act.log_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })"></p></div></div></template></div></template>
                <template x-if="!loading && activities.length === 0"><p class="text-gray-500">No recent activity.</p></template>
            </div>
            <div class="mt-4 pt-4 border-t flex justify-between items-center text-sm"><span class="text-gray-600">Page <strong x-text="pagination.currentPage || 1"></strong> of <strong x-text="pagination.totalPages || 1"></strong></span><div class="flex space-x-1"><button @click="fetchActivity(1)" :disabled="pagination.currentPage <= 1" class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">&lt;&lt;&lt;</button><button @click="fetchActivity(pagination.currentPage - 1)" :disabled="pagination.currentPage <= 1" class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">&lt;</button><button @click="fetchActivity(pagination.currentPage + 1)" :disabled="pagination.currentPage >= pagination.totalPages" class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">&gt;</button><button @click="fetchActivity(pagination.totalPages)" :disabled="pagination.currentPage >= pagination.totalPages" class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">&gt;&gt;&gt;</button></div></div>
        </div>
        
        <!-- Recent Error Logs -->
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">Recent Error Logs</h2>
            <a href="error_log_viewer.php" class="btn-outline text-sm py-1 px-3 mb-4 inline-block">View Full Log</a>
            <div class="overflow-x-auto h-96 border rounded-lg bg-gray-50">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-200 sticky top-0">
                        <tr><th class="p-2">Time</th><th class="p-2">Message</th><th class="p-2">File</th><th class="p-2">Line</th></tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php if (empty($error_logs) && $logs_status['ok']): ?>
                            <tr><td colspan="4" class="p-4 text-center text-gray-500">No errors have been logged.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($error_logs as $log): ?>
                        <tr class="border-b"><td class="p-2 whitespace-nowrap"><?= e(date('d M Y, h:i A', strtotime($log['log_time']))) ?></td><td class="p-2 font-mono text-red-600"><?= e($log['error_message']) ?></td><td class="p-2"><?= e(basename($log['file_path'])) ?></td><td class="p-2"><?= e($log['line_number']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

