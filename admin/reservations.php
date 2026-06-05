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

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Manage Reservations</h1>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3">Customer</th>
                        <th class="p-3">Reservation Details</th>
                        <th class="p-3">Guests</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reservations as $res): 
                         $status_colors = [
                            'pending' => 'bg-yellow-200 text-yellow-800',
                            'confirmed' => 'bg-green-200 text-green-800',
                            'cancelled' => 'bg-red-200 text-red-800',
                            'completed' => 'bg-blue-200 text-blue-800',
                        ];
                        $color = $status_colors[$res['status']] ?? 'bg-gray-200';
                    ?>
                    <tr class="border-b">
                        <td class="p-3">
                            <div class="font-medium"><?= e($res['username']) ?></div>
                            <div class="text-sm text-gray-500"><?= e($res['phone_number']) ?></div>
                        </td>
                        <td class="p-3">
                            <div class="font-semibold"><?= e($res['table_number']) ?></div>
                            <div class="text-sm text-gray-600">
                                <?= date('D, d M Y', strtotime($res['reservation_date'])) ?> at <?= date('h:i A', strtotime($res['reservation_time'])) ?>
                            </div>
                        </td>
                        <td class="p-3"><?= e($res['number_of_guests']) ?></td>
                        <td class="p-3">
                            <span class="px-3 py-1 text-sm font-semibold rounded-full <?= $color ?>">
                                <?= e(ucfirst($res['status'])) ?>
                            </span>
                        </td>
                        <td class="p-3">
                            <form method="POST">
                                <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                <input type="hidden" name="table_id" value="<?= $res['table_id'] ?>">
                                <select name="status" class="form-input p-1 bg-white text-sm">
                                    <option value="pending" <?= $res['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $res['status'] == 'confirmed' ? 'selected' : '' ?>>Confirm</option>
                                    <option value="cancelled" <?= $res['status'] == 'cancelled' ? 'selected' : '' ?>>Cancel</option>
                                    <option value="completed" <?= $res['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                                <button type="submit" name="update_status" class="mt-1 bg-gray-600 text-white px-3 py-1 text-xs rounded hover:bg-gray-700">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>