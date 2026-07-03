<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/partials/header.php';

// Handle marking a redemption as 'fulfilled'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fulfill_redemption'])) {
    $redemption_id = (int)$_POST['redemption_id'];
    $stmt = $pdo->prepare("UPDATE reward_redemptions SET status = 'fulfilled' WHERE id = ?");
    $stmt->execute([$redemption_id]);
    log_activity($pdo, "Fulfilled redemption ID #$redemption_id");
    header('Location: redemptions.php'); 
    exit();
}

// Fetch all redemptions, joining with user and reward tables to get names
$stmt = $pdo->query("
    SELECT 
        rr.id, rr.points_spent, rr.redeemed_at, rr.status,
        u.username, u.phone_number,
        lr.title as reward_title
    FROM reward_redemptions rr
    JOIN users u ON rr.user_id = u.id
    JOIN loyalty_rewards lr ON rr.reward_id = lr.id
    ORDER BY rr.redeemed_at DESC
");
$redemptions = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.4em] text-slate-500 dark:text-slate-400">Loyalty Rewards</h2>
            </div>
            <h1 class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter leading-none">Redemptions Log</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-2">Managing the deployment of loyalty assets to verified users.</p>
        </div>
    </div>

    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-[2.5rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Customer</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Reward</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Points Used</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Timestamp</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Status</th>
                        <th class="p-6 text-[10px] uppercase font-black text-slate-400 tracking-[0.15em]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php if (empty($redemptions)): ?>
                        <tr><td colspan="6" class="p-20 text-center text-slate-400 text-xs font-bold uppercase tracking-wider">No reward redemptions found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($redemptions as $r): ?>
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-200">
                        <td class="p-6">
                            <div class="font-bold text-slate-800 dark:text-white uppercase"><?= e($r['username']) ?></div>
                            <div class="text-[10px] text-slate-400 font-mono mt-1"><?= e($r['phone_number']) ?></div>
                        </td>
                        <td class="p-6">
                            <span class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase"><?= e($r['reward_title']) ?></span>
                        </td>
                        <td class="p-6">
                            <span class="text-sm font-black text-red-500">-<?= e($r['points_spent']) ?> <span class="text-[9px] font-normal opacity-50">PTS</span></span>
                        </td>
                        <td class="p-6">
                            <span class="text-[10px] text-slate-400 font-mono uppercase"><?= date('d M Y, H:i', strtotime($r['redeemed_at'])) ?></span>
                        </td>
                        <td class="p-6">
                             <span class="inline-flex items-center px-3 py-1 text-[9px] font-black uppercase rounded-full border tracking-widest <?= $r['status'] == 'pending' ? 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20' : 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' ?>">
                                <?= e($r['status']) ?>
                            </span>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="POST" onsubmit="return confirm('Fulfill reward redemption?');">
                                        <input type="hidden" name="redemption_id" value="<?= $r['id'] ?>">
                                        <button type="submit" name="fulfill_redemption" class="bg-emerald-500 hover:bg-emerald-600 text-white text-[9px] font-black uppercase tracking-widest px-4 py-2 rounded-xl transition-all">Fulfill</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-[9px] font-black text-slate-300 dark:text-slate-700 uppercase tracking-widest">Completed</span>
                                <?php endif; ?>
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