<?php
require_once '../includes/db_connect.php';
include 'includes/header.php';

$stmt = $pdo->prepare("SELECT balance, faucetpay_email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('min_withdrawal','instant_withdrawals_enabled','faucetpay_api_key')");
$ws = [];
foreach ($stmt->fetchAll() as $r) { $ws[$r['setting_key']] = $r['setting_value']; }
$min_wd  = floatval($ws['min_withdrawal'] ?? 0.01);
$instant = ($ws['instant_withdrawals_enabled'] ?? '1') === '1';
$fp_api_key = trim($ws['faucetpay_api_key'] ?? '');
$instant_active = $instant && $fp_api_key !== '';
$balance = floatval($user['balance']);
$payout_email = trim((string)($user['faucetpay_email'] ?? ''));
$progress = $min_wd > 0 ? min(100, ($balance / $min_wd) * 100) : 100;
$can_withdraw = $balance >= $min_wd;

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;

$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM withdrawals WHERE user_id = ?");
$total_stmt->execute([$_SESSION['user_id']]);
$total = (int)$total_stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $per_page));

$hist_stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$hist_stmt->execute([$_SESSION['user_id'], $per_page, $offset]);
$history = $hist_stmt->fetchAll();
?>

    <main class="flex-1 relative z-10">
        <section class="pt-24 md:pt-32 pb-8 md:pb-12">
            <div class="container-custom relative z-10 space-y-8">
            <div id="ad-container-general_top_ads" class="flex justify-center empty:hidden w-full min-h-[90px]"></div>

            <div>
                <div class="section-label">Withdraw</div>
                <h1 class="text-3xl font-extrabold text-zinc-100 tracking-tight mt-2">Withdraw USDT</h1>
            </div>

<div class="bg-surface-card rounded-lg border border-white/10 overflow-hidden">
    <div class="p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">

            <div class="flex-1">
                <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1 font-mono">Current Balance</p>
                <div class="flex items-baseline gap-2 mb-4">
                    <span class="text-4xl font-bold text-zinc-100 font-mono"><?php echo number_format($balance, 6); ?></span>
                    <span class="text-lg font-semibold text-primary">USDT</span>
                </div>

                <div class="mb-2">
                    <div class="flex justify-between text-xs text-zinc-500 mb-1.5">
                        <span>Progress to minimum withdrawal</span>
                        <span class="font-semibold font-mono text-primary">
                            <?php echo number_format($balance, 6); ?> / <?php echo number_format($min_wd, 6); ?> USDT
                        </span>
                    </div>
                    <div class="w-full bg-white/10 rounded-full h-3 overflow-hidden">
                        <div class="h-3 rounded-full transition-all duration-700 <?php echo $can_withdraw ? 'bg-primary' : 'bg-gradient-to-r from-primaryDeep to-primary'; ?>"
                             style="width: <?php echo number_format($progress, 2); ?>%"></div>
                    </div>
                    <p class="text-xs text-zinc-500 mt-1.5">
                        <?php if ($can_withdraw): ?>
                        <span class="text-primary font-semibold"><i class="fa-solid fa-check-circle mr-1"></i>You've reached the minimum withdrawal amount!</span>
                        <?php else: ?>
                        Need <strong class="text-zinc-300 font-mono"><?php echo number_format($min_wd - $balance, 6); ?> USDT</strong> more to withdraw.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="flex flex-col items-center gap-3 md:min-w-[220px]">
                <div class="w-full p-4 bg-white/[0.03] rounded-lg border border-white/10 text-center mb-1">
                    <p class="text-xs text-zinc-500 mb-0.5 font-mono">Payout Email</p>
                    <?php if ($payout_email !== ''): ?>
                    <p class="inline-flex items-center gap-1.5 max-w-full px-3 py-1 bg-primary/10 border border-primary/25 rounded-full text-sm font-semibold text-primary break-all">
                        <i class="fa-solid fa-envelope-circle-check text-xs"></i>
                        <span class="truncate"><?php echo htmlspecialchars($payout_email); ?></span>
                    </p>
                    <p class="text-[11px] text-zinc-500 mt-1.5 leading-snug">Paid to the email on your BountyEarn account.<br>Contact support to change it.</p>
                    <?php else: ?>
                    <p class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/5 border border-white/10 rounded-full text-sm font-semibold text-zinc-300">
                        <i class="fa-solid fa-triangle-exclamation text-xs"></i> Not set
                    </p>
                    <p class="text-[11px] text-zinc-500 mt-1.5 leading-snug">Contact support to link a payout email to your account.</p>
                    <?php endif; ?>
                </div>

                <?php if ($instant_active): ?>
                <div class="flex items-center gap-1.5 text-xs text-primary font-medium">
                    <i class="fa-solid fa-bolt"></i> Instant Withdrawals Active
                </div>
                <?php else: ?>
                <div class="flex items-center gap-1.5 text-xs text-zinc-400 font-medium">
                    <i class="fa-solid fa-clock"></i> Manual Review — Admin Will Process Your Withdrawal
                </div>
                <?php endif; ?>

                <button id="withdraw-btn"
                    <?php echo $can_withdraw ? '' : 'disabled'; ?>
                    class="w-full py-3.5 px-6 rounded-lg font-bold text-sm transition-all flex items-center justify-center gap-2
                    <?php echo $can_withdraw
                        ? 'bg-primary hover:bg-primaryHover text-zinc-950 hover:-translate-y-0.5 cursor-pointer'
                        : 'bg-white/5 border border-white/10 text-zinc-500 cursor-not-allowed'; ?>">
                    <i class="fa-solid fa-paper-plane"></i>
                    <?php echo $instant_active ? 'WITHDRAW NOW' : 'REQUEST WITHDRAWAL'; ?>
                </button>
                <p class="text-xs text-zinc-500 text-center font-mono">Minimum: <?php echo number_format($min_wd, 6); ?> USDT</p>
            </div>
        </div>
    </div>
</div>

<div id="withdraw-result" class="hidden"></div>

<div class="bg-surface-card rounded-lg border border-white/10 overflow-hidden">
    <div class="px-6 py-5 border-b border-white/10 bg-white/[0.03] flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-zinc-100">Your Withdrawals</h3>
                <p class="text-xs text-zinc-500 font-mono"><?php echo $total; ?> total record<?php echo $total != 1 ? 's' : ''; ?></p>
            </div>
        </div>
    </div>

    <?php if (empty($history)): ?>
    <div class="p-12 text-center text-zinc-500">
        <i class="fa-solid fa-inbox text-4xl mb-3 block text-zinc-600"></i>
        <p class="text-sm font-medium">No withdrawals yet.</p>
        <p class="text-xs mt-1">Your withdrawal history will appear here.</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs font-medium text-zinc-400 uppercase tracking-wide bg-white/[0.03]">
                    <th class="px-6 py-3 text-left">#</th>
                    <th class="px-6 py-3 text-left">Date</th>
                    <th class="px-6 py-3 text-right">Amount</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-left">Payout ID</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
            <?php foreach ($history as $i => $w): ?>
                <?php
                    $badges = [
                        'approved' => 'bg-primary/10 text-primary border border-primary/25',
                        'rejected' => 'bg-red-500/10 text-red-400 border border-red-500/20'
                    ];
                    $badge = $badges[$w['status']] ?? 'bg-white/5 text-zinc-300 border border-white/10';

                    $icons = [
                        'approved' => 'fa-check-circle',
                        'rejected' => 'fa-times-circle'
                    ];
                    $icon = $icons[$w['status']] ?? 'fa-clock';
                ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-4 text-zinc-500 text-xs font-mono"><?php echo $offset + $i + 1; ?></td>
                    <td class="px-6 py-4 text-zinc-300 whitespace-nowrap">
                        <?php echo date('d M Y, H:i', strtotime($w['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 text-right font-mono font-semibold text-primary">
                        <?php echo number_format($w['amount'], 6); ?>
                        <span class="text-zinc-500 text-xs ml-0.5">USDT</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $badge; ?>">
                            <i class="fa-solid <?php echo $icon; ?> text-xs"></i>
                            <?php echo ucfirst($w['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 font-mono text-xs text-zinc-500 max-w-[220px]">
                        <?php if (!empty($w['txid'])): ?>
                        <span class="flex items-center gap-1.5" title="<?php echo htmlspecialchars($w['txid']); ?>">
                            <i class="fa-solid fa-receipt text-xs opacity-60"></i>
                            <span class="truncate max-w-[160px] inline-block align-bottom"><?php echo htmlspecialchars($w['txid']); ?></span>
                        </span>
                        <?php else: ?>
                        <span class="text-zinc-600">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
    <div class="px-6 py-4 border-t border-white/10 flex items-center justify-between">
        <p class="text-xs text-zinc-500 font-mono">Page <?php echo $page; ?> of <?php echo $pages; ?></p>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="<?= BASE_URL ?>/user/withdraw?page=<?php echo $page - 1; ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-white/10 text-zinc-300 hover:border-primary/40 hover:text-primary transition">
                <i class="fa-solid fa-chevron-left mr-1"></i> Prev
            </a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end   = min($pages, $page + 2);
            for ($p = $start; $p <= $end; $p++):
            ?>
            <a href="<?= BASE_URL ?>/user/withdraw?page=<?php echo $p; ?>"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?php echo $p === $page ? 'bg-primary text-zinc-950' : 'border border-white/10 text-zinc-300 hover:border-primary/40 hover:text-primary'; ?>">
               <?php echo $p; ?>
            </a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
            <a href="<?= BASE_URL ?>/user/withdraw?page=<?php echo $page + 1; ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-white/10 text-zinc-300 hover:border-primary/40 hover:text-primary transition">
                Next <i class="fa-solid fa-chevron-right ml-1"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

            </div>
        </section>
        <div class="container-custom pb-8"><div id="ad-container-general_bottom_ads" class="flex justify-center empty:hidden w-full min-h-[90px]"></div></div>
    </main>

<script src="<?= BASE_URL ?>/assets/js/pages/user-withdraw.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
