<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

// Handle status updates from the form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $reservation_id = $_POST['reservation_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $reservation_id]);

    // If confirmed, also update the physical table status
    if ($new_status === 'confirmed') {
        $table_id = $_POST['table_id'];
        $stmt = $pdo->prepare("UPDATE tables SET status = 'reserved' WHERE id = ?");
        $stmt->execute([$table_id]);
    }

    header('Location: reservations.php');
    exit();
}

// Fetch all reservations with user and table details
$reservations = $pdo->query("
    SELECT 
        r.id, r.reservation_date, r.reservation_time, r.number_of_guests, r.notes, r.status, r.table_id,
        u.username, u.phone_number,
        t.table_number
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN tables t ON r.table_id = t.id
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
")->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-cyan-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Guest Scheduling</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Reservations</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Review table bookings and update guest arrival status.</p>
        </div>
        <div class="liquid-surface rounded-2xl px-5 py-4 border">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Reservations</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($reservations) ?></p>
        </div>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">Booking Queue</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="p-4">Customer</th>
                        <th class="p-4">Reservation Details</th>
                        <th class="p-4">Guests</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reservations as $res): 
                         $status_colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'confirmed' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                            'completed' => 'bg-blue-100 text-blue-800',
                        ];
                        $color = $status_colors[$res['status']] ?? 'bg-gray-200';
                    ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-4">
                            <div class="font-black text-slate-800 dark:text-white"><?= e($res['username']) ?></div>
                            <div class="text-sm text-slate-500"><?= e($res['phone_number']) ?></div>
                        </td>
                        <td class="p-4">
                            <div class="font-black text-slate-800 dark:text-white"><?= e($res['table_number']) ?></div>
                            <div class="text-sm text-slate-500">
                                <?= date('D, d M Y', strtotime($res['reservation_date'])) ?> at <?= date('h:i A', strtotime($res['reservation_time'])) ?>
                            </div>
                        </td>
                        <td class="p-4 font-black text-slate-700 dark:text-slate-200"><?= e($res['number_of_guests']) ?></td>
                        <td class="p-4">
                            <span class="px-3 py-1 text-xs font-black rounded-full <?= $color ?>">
                                <?= e(ucfirst($res['status'])) ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <form method="POST" class="flex items-center gap-2">
                                <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                <input type="hidden" name="table_id" value="<?= $res['table_id'] ?>">
                                <select name="status" class="form-input p-2 bg-white text-sm">
                                    <option value="pending" <?= $res['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $res['status'] == 'confirmed' ? 'selected' : '' ?>>Confirm</option>
                                    <option value="cancelled" <?= $res['status'] == 'cancelled' ? 'selected' : '' ?>>Cancel</option>
                                    <option value="completed" <?= $res['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                                <button type="submit" name="update_status" class="h-10 px-4 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-950 text-xs font-black uppercase tracking-widest">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($reservations)): ?>
                    <tr><td colspan="5" class="p-10 text-center text-slate-500">No reservations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
