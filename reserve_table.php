<?php
require_once 'includes/functions.php'; // Includes session start
require_once 'includes/db_connect.php';

// --- SECURITY: Require user to be logged in to make a reservation ---
require_login(); 

$errors = [];
$success_message = '';

// Fetch all tables that are not under maintenance
$tables = $pdo->query("SELECT id, table_number FROM tables WHERE status != 'maintenance' ORDER BY table_number ASC")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_id = $_POST['table_id'] ?? 0;
    $reservation_date = $_POST['reservation_date'] ?? '';
    $reservation_time = $_POST['reservation_time'] ?? '';
    $number_of_guests = $_POST['number_of_guests'] ?? 1;
    $notes = trim($_POST['notes'] ?? '');

    if (empty($table_id) || empty($reservation_date) || empty($reservation_time)) {
        $errors[] = "Please select a table, date, and time for your reservation.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO reservations (user_id, table_id, reservation_date, reservation_time, number_of_guests, notes) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $_SESSION['user_id'],
                $table_id,
                $reservation_date,
                $reservation_time,
                $number_of_guests,
                $notes
            ]);
            $success_message = "Your reservation request has been sent! We will confirm it shortly.";
        } catch (PDOException $e) {
            $errors[] = "Sorry, there was a problem sending your request. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-center mb-8">Reserve a Table</h1>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p class="font-bold">Success!</p>
            <p><?= e($success_message) ?></p>
        </div>
    <?php elseif (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-lg shadow-md">
        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="table_id" class="block text-gray-700 font-semibold">Select Table</label>
                    <select name="table_id" id="table_id" class="form-input mt-1 bg-white" required>
                        <option value="">-- Choose a table --</option>
                        <?php foreach($tables as $table): ?>
                            <option value="<?= $table['id'] ?>"><?= e($table['table_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="number_of_guests" class="block text-gray-700 font-semibold">Number of Guests</label>
                    <input type="number" name="number_of_guests" id="number_of_guests" min="1" max="10" value="2" class="form-input mt-1" required>
                </div>
                 <div>
                    <label for="reservation_date" class="block text-gray-700 font-semibold">Date</label>
                    <input type="date" name="reservation_date" id="reservation_date" min="<?= date('Y-m-d') ?>" class="form-input mt-1" required>
                </div>
                 <div>
                    <label for="reservation_time" class="block text-gray-700 font-semibold">Time</label>
                    <input type="time" name="reservation_time" id="reservation_time" min="09:00" max="21:00" class="form-input mt-1" required>
                </div>
                <div class="md:col-span-2">
                    <label for="notes" class="block text-gray-700 font-semibold">Special Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3" class="form-input mt-1" placeholder="e.g., Birthday celebration, near the window"></textarea>
                </div>
            </div>
            <div class="mt-8 text-center">
                <button type="submit" class="btn-brand w-full md:w-auto">Send Reservation Request</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>