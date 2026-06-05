<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/partials/header.php';

$errors = [];
$success_message = '';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $description = trim($_POST['description'] ?? '');
        $amount = $_POST['amount'] ?? 0;
        $expense_date = $_POST['expense_date'] ?? date('Y-m-d');

        if (empty($description) || $amount <= 0) {
            $errors[] = "A valid description and amount are required.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, expense_date) VALUES (?, ?, ?)");
            $stmt->execute([$description, $amount, $expense_date]);
            $success_message = "Expense recorded successfully.";
        }
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        $success_message = "Expense deleted successfully.";
    }
}

// Fetch all expenses for display, newest first
$expenses = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC")->fetchAll();
?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Manage Expenses</h1>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($success_message) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold mb-4">Add New Expense</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input name="description" placeholder="Expense Description (e.g., Rent)" class="form-input" required>
                <input type="number" name="amount" placeholder="Amount (Ks)" class="form-input" step="0.01" required>
                <input type="date" name="expense_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
            </div>
            <button type="submit" class="mt-4 btn-brand">Add Expense</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4">Expense History</h2>
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-100"><th class="p-3">Date</th><th class="p-3">Description</th><th class="p-3">Amount</th><th class="p-3">Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $expense): ?>
                <tr class="border-b">
                    <td class="p-3"><?= e(date('d M Y', strtotime($expense['expense_date']))) ?></td>
                    <td class="p-3 font-medium"><?= e($expense['description']) ?></td>
                    <td class="p-3 text-red-600 font-semibold"><?= number_format($expense['amount'], 2) ?> Ks</td>
                    <td class="p-3">
                        <form method="POST" onsubmit="return confirm('Are you sure?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                            <button type="submit" class="text-red-500 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'partials/footer.php'; ?>