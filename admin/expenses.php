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
$total_expenses = array_sum(array_map(static fn($expense) => (float)$expense['amount'], $expenses));
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-red-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Finance Control</h2>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black text-slate-800 dark:text-white tracking-tight leading-none">Expenses</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Track operating costs with clean daily records.</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="liquid-surface rounded-2xl px-5 py-4 border">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Entries</p>
                <p class="text-3xl font-black text-slate-800 dark:text-white leading-none mt-1"><?= count($expenses) ?></p>
            </div>
            <div class="liquid-surface rounded-2xl px-5 py-4 border">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total</p>
                <p class="text-2xl font-black text-red-500 leading-none mt-1"><?= number_format($total_expenses) ?> Ks</p>
            </div>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-2xl" role="alert"><p class="font-bold"><?= e($success_message) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-2xl" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 lg:p-8 shadow-2xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white">Add New Expense</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Record clear descriptions for better reporting later.</p>
            </div>
            <div class="hidden sm:flex h-12 w-12 items-center justify-center rounded-2xl bg-red-500/10 text-red-500">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Description</label>
                    <input name="description" placeholder="Rent, utilities, supplies..." class="form-input" required>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Amount</label>
                    <input type="number" name="amount" placeholder="Amount (Ks)" class="form-input" step="0.01" required>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Date</label>
                    <input type="date" name="expense_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <button type="submit" class="mt-4 btn-brand">Add Expense</button>
        </form>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="px-6 lg:px-8 py-5 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-black text-slate-800 dark:text-white">Expense History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr><th class="p-4">Date</th><th class="p-4">Description</th><th class="p-4">Amount</th><th class="p-4 text-right">Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-4 text-sm font-bold text-slate-600 dark:text-slate-300"><?= e(date('d M Y', strtotime($expense['expense_date']))) ?></td>
                        <td class="p-4 font-bold text-slate-800 dark:text-white"><?= e($expense['description']) ?></td>
                        <td class="p-4 text-red-500 font-black"><?= number_format($expense['amount'], 2) ?> Ks</td>
                        <td class="p-4 text-right">
                        <form method="POST" onsubmit="return confirm('Are you sure?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                            <button type="submit" class="h-9 w-9 inline-flex items-center justify-center rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Delete">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($expenses)): ?>
                    <tr><td colspan="4" class="p-10 text-center text-slate-500">No expenses recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
