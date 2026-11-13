<?php
include 'includes/header.php';

if (!isset($settings['referrals_enabled']) || $settings['referrals_enabled'] != '1') {
    header('Location: /user/');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT is_shadow_banned FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$is_shadow_banned = (int)$stmt->fetchColumn();

if ($is_shadow_banned === 1) {
    $total_referrals = 0;
    $earnings_data = [];
    $earnings = [];
    $total_earned = 0;
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referred_by = ?");
    $stmt->execute([$user_id]);
    $total_referrals = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM referral_earnings WHERE referrer_id = ? GROUP BY category");
    $stmt->execute([$user_id]);
    $earnings_data = $stmt->fetchAll();
    $earnings = [];
    $total_earned = 0;
    foreach ($earnings_data as $row) {
        $earnings[$row['category']] = $row['total'];
        $total_earned += $row['total'];
    }
}

$categories = [
    'faucet' => ['label' => 'Faucet', 'icon' => 'fa-faucet', 'color' => 'text-primary', 'bg' => 'bg-primary/10', 'percent' => $settings['ref_percent_faucet'] ?? 0],
    'ptc' => ['label' => 'PTC Ads', 'icon' => 'fa-mouse-pointer', 'color' => 'text-primary', 'bg' => 'bg-primary/10', 'percent' => $settings['ref_percent_ptc'] ?? 0],
    'shortlinks' => ['label' => 'Shortlinks', 'icon' => 'fa-link', 'color' => 'text-primary', 'bg' => 'bg-primary/10', 'percent' => $settings['ref_percent_shortlinks'] ?? 0],
    'tasks' => ['label' => 'Tasks', 'icon' => 'fa-list-check', 'color' => 'text-primary', 'bg' => 'bg-primary/10', 'percent' => $settings['ref_percent_tasks'] ?? 0],
    'video' => ['label' => 'Video', 'icon' => 'fa-video', 'color' => 'text-primary', 'bg' => 'bg-primary/10', 'percent' => $settings['ref_percent_video'] ?? 0],
    'offerwall' => ['label' => 'Offerwall', 'icon' => 'fa-table-cells', 'color' => 'text-primary', 'bg' => 'bg-primary/10', 'percent' => $settings['ref_percent_offerwall'] ?? 0],
    'deposits' => ['label' => 'Deposits', 'icon' => 'fa-wallet', 'color' => 'text-primary', 'bg' => 'bg-primary/10', 'percent' => $settings['ref_percent_deposits'] ?? 0],
];

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$domain = $_SERVER['HTTP_HOST'];
$referral_link = $protocol . "://" . $domain . "/?ref=" . $user_id;
?>

<main class="flex-1 relative z-10">
        <section class="pt-20 md:pt-28 pb-8 md:pb-12">
            <div class="container-custom relative z-10">
                <div id="ad-container-general_top_ads" class="mb-8 flex justify-center empty:hidden w-full min-h-[90px]"></div>
                <div class="w-full">

                    <div class="mb-8">
                        <div class="section-label">Referrals</div>
                        <h1 class="text-3xl font-extrabold text-zinc-100 tracking-tight mt-2">Referral Program</h1>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div class="bg-surface-card rounded-lg border border-white/10 p-6 flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-2xl shrink-0">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-400">Total Referrals</p>
                                <p class="text-2xl font-bold text-zinc-100 font-mono"><?php echo number_format($total_referrals); ?></p>
                            </div>
                        </div>

                        <div class="bg-surface-card rounded-lg border border-white/10 p-6 flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-2xl shrink-0">
                                <i class="fa-solid fa-sack-dollar"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-400">Total Earned</p>
                                <p class="text-2xl font-bold text-primary font-mono"><?php echo number_format($total_earned, 6); ?> <span class="text-sm text-zinc-400">USDT</span></p>
                            </div>
                        </div>

                        <div class="relative overflow-visible h-full w-full">
                            <div class="bg-surface-card rounded-lg border border-white/10 p-5 flex flex-col justify-center relative overflow-hidden w-full h-full">
                                <div class="absolute -right-4 -bottom-4 text-white/5 text-6xl pointer-events-none"><i class="fa-solid fa-percent"></i></div>
                                <h3 class="text-sm font-bold text-zinc-300 mb-2 relative z-10 uppercase tracking-wider">Commission Rates</h3>
                                <div class="flex flex-wrap justify-center gap-1.5 relative z-10 w-full">
                                    <?php foreach ($categories as $cat): ?>
                                        <?php if (floatval($cat['percent']) > 0): ?>
                                            <span class="commission-badge inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold <?php echo $cat['bg'] . ' ' . $cat['color']; ?> border border-primary/25 cursor-pointer hover:opacity-80 transition-opacity"
                                                  data-label="<?php echo htmlspecialchars($cat['label']); ?>"
                                                  data-percent="<?php echo $cat['percent']; ?>">
                                                <i class="fa-solid <?php echo $cat['icon']; ?>"></i> <?php echo $cat['percent']; ?>%
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div id="commission-tooltip" class="hidden absolute top-full mt-2 left-0 right-0 z-[9999] bg-surface-raised text-zinc-100 text-xs rounded-lg px-4 py-2.5 shadow-2xl pointer-events-none">
                                <div class="absolute -top-[5px] left-6 w-2.5 h-2.5 bg-surface-raised rotate-45"></div>
                                <span id="tooltip-text"></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-surface-card rounded-lg border border-white/10 p-6 md:p-8 mb-8 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full -z-10"></div>
                        <h3 class="text-lg font-bold text-zinc-100 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-link text-primary"></i> Your Referral Link
                        </h3>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input type="text" id="ref-link" value="<?php echo htmlspecialchars($referral_link); ?>" readonly class="flex-1 bg-white/[0.03] border border-white/10 text-zinc-200 font-mono text-sm px-4 py-3 rounded-lg focus:outline-none focus:border-primary/60 transition-colors">
                            <button id="copy-ref-link-btn" type="button" class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-6 rounded-lg transition-colors whitespace-nowrap">
                                <i class="fa-solid fa-copy mr-2"></i> Copy Link
                            </button>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-zinc-100 mb-4"><i class="fa-solid fa-chart-pie text-primary mr-2"></i> Earnings Breakdown</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <?php foreach ($categories as $key => $cat): ?>
                            <?php if (floatval($cat['percent']) > 0): ?>
                            <div class="bg-surface-card rounded-lg border border-white/10 p-5 flex items-center gap-4 micro-anim">
                                <div class="w-12 h-12 rounded-full <?php echo $cat['bg'] . ' ' . $cat['color']; ?> border border-primary/25 flex items-center justify-center text-xl shrink-0">
                                    <i class="fa-solid <?php echo $cat['icon']; ?>"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-1 font-mono"><?php echo $cat['label']; ?></p>
                                    <p class="text-lg font-bold text-zinc-100 leading-none font-mono"><?php echo number_format($earnings[$key] ?? 0, 6); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <div class="hidden lg:flex flex-col items-center justify-center col-span-3 bg-white/[0.02] border-2 border-dashed border-white/10 rounded-lg text-zinc-600 h-full min-h-[90px]">
                            <i class="fa-solid fa-bullhorn text-xl mb-1"></i>
                            <span class="text-xs font-bold font-mono">728x90 AD PLACEHOLDER</span>
                        </div>
                    </div>

                    <div class="bg-surface-card rounded-lg border border-white/10 overflow-hidden">
                        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-white/[0.03]">
                            <h3 class="text-lg font-bold text-zinc-100"><i class="fa-solid fa-list-ul text-primary mr-2"></i> Your Referrals</h3>
                        </div>
                        <div class="overflow-x-auto table-scroll">
                            <table class="w-full text-left border-collapse whitespace-nowrap">
                                <thead>
                                    <tr class="bg-white/[0.03] text-zinc-400 text-xs uppercase tracking-wider border-b border-white/10">
                                        <th class="px-6 py-4 font-medium">User ID</th>
                                        <th class="px-6 py-4 font-medium">Referral</th>
                                        <th class="px-6 py-4 font-medium">Earned</th>
                                        <th class="px-6 py-4 font-medium text-right">Join Date</th>
                                    </tr>
                                </thead>
                                <tbody id="referrals-table-body" class="divide-y divide-white/10 text-sm">
                                    <tr><td colspan="4" class="px-6 py-8 text-center text-zinc-500"><i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Loading referrals...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 border-t border-white/10 flex items-center justify-between" id="pagination-container">
                            <p class="text-sm text-zinc-500" id="pagination-info">Showing 0 of 0</p>
                            <div class="flex gap-2" id="pagination-buttons"></div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
        <div class="container-custom pb-8"><div id="ad-container-general_bottom_ads" class="flex justify-center empty:hidden w-full min-h-[90px]"></div></div>
    </main>

<script src="<?= BASE_URL ?>/assets/js/pages/user-referrals.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
