<?php
include 'includes/header.php';

$stmtPTC = $pdo->prepare("SELECT COUNT(id) FROM ptc_claims WHERE user_id = ? AND status = 'completed' AND DATE(created_at) = CURDATE()");
$stmtPTC->execute([$_SESSION['user_id']]);
$ptcClicksToday = $stmtPTC->fetchColumn();

$stmtSL = $pdo->prepare("SELECT COUNT(id) FROM shortlink_claims WHERE user_id = ? AND status = 'completed' AND DATE(created_at) = CURDATE()");
$stmtSL->execute([$_SESSION['user_id']]);
$slClicksToday = $stmtSL->fetchColumn();

$stmtRef = $pdo->prepare("SELECT COUNT(id) FROM users WHERE referred_by = ?");
$stmtRef->execute([$_SESSION['user_id']]);
$activeReferrals = $stmtRef->fetchColumn();

$stmtFP = $pdo->prepare("SELECT faucetpay_email FROM users WHERE id = ?");
$stmtFP->execute([$_SESSION['user_id']]);
$fp_email = trim((string)$stmtFP->fetchColumn());
$fp_email_masked = '';
if ($fp_email !== '') {
    $at = strpos($fp_email, '@');
    if ($at === false) {
        $fp_email_masked = substr($fp_email, 0, 2) . '***';
    } else {
        $local = substr($fp_email, 0, $at);
        $fp_email_masked = substr($local, 0, min(2, strlen($local))) . '***' . substr($fp_email, $at);
    }
}

$page_data = ['ads' => null];
if (isset($settings['dashboard_ads_enabled']) && $settings['dashboard_ads_enabled'] == '1') {
    $ads_file = __DIR__ . '/../includes/ads.json';
    $dashboard_ads = ['dashboard_level_ads' => [], 'dashboard_ways_ads' => [], 'dashboard_top_ads' => []];
    if (file_exists($ads_file)) {
        $raw_ads = json_decode(file_get_contents($ads_file), true);
        $dashboard_ads['dashboard_level_ads'] = $raw_ads['dashboard_level_ads'] ?? [];
        $dashboard_ads['dashboard_ways_ads'] = $raw_ads['dashboard_ways_ads'] ?? [];
        $dashboard_ads['dashboard_top_ads'] = $raw_ads['dashboard_top_ads'] ?? [];
    }
    $page_data['ads'] = $dashboard_ads;
}
?>
    <main class="flex-1 relative z-10">
        <section class="pt-20 md:pt-28 pb-8 md:pb-12">
            <div class="container-custom relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <div class="lg:col-span-2 flex flex-col gap-8">
                        <div class="flex justify-center lg:justify-start items-center overflow-hidden w-full empty:hidden min-h-[90px]" id="ad-container-dashboard_top_ads"></div>

                        <?php
                        $stat_cards = [];
                        if (isset($settings['ptc_enabled']) && $settings['ptc_enabled'] == '1') {
                            $stat_cards[] = '
                            <div class="bg-white/[0.02] p-6 rounded-lg border border-white/10 flex flex-col justify-center micro-anim">
                                <i class="fa-solid fa-mouse-pointer text-2xl text-primary mb-3"></i>
                                <h3 class="text-zinc-400 text-sm font-medium">PTC Clicks Today</h3>
                                <p class="text-2xl font-bold text-zinc-100 mt-1 font-mono">' . (int)$ptcClicksToday . '</p>
                            </div>';
                        }
                        if (isset($settings['shortlinks_enabled']) && $settings['shortlinks_enabled'] == '1') {
                            $stat_cards[] = '
                            <div class="bg-white/[0.02] p-6 rounded-lg border border-white/10 flex flex-col justify-center micro-anim">
                                <i class="fa-solid fa-link text-2xl text-primary mb-3"></i>
                                <h3 class="text-zinc-400 text-sm font-medium">Shortlinks Today</h3>
                                <p class="text-2xl font-bold text-zinc-100 mt-1 font-mono">' . (int)$slClicksToday . '</p>
                            </div>';
                        }
                        if (isset($settings['referrals_enabled']) && $settings['referrals_enabled'] == '1') {
                            $stat_cards[] = '
                            <div class="bg-white/[0.02] p-6 rounded-lg border border-white/10 flex flex-col justify-center micro-anim">
                                <i class="fa-solid fa-users text-2xl text-primary mb-3"></i>
                                <h3 class="text-zinc-400 text-sm font-medium">Active Referrals</h3>
                                <p class="text-2xl font-bold text-zinc-100 mt-1 font-mono">' . (int)$activeReferrals . '</p>
                            </div>';
                        }

                        $sc = count($stat_cards);
                        if ($sc > 0):
                            $stat_grid_class = "grid grid-cols-1 sm:grid-cols-3 gap-4";
                            if ($sc == 2) $stat_grid_class = "grid grid-cols-1 sm:grid-cols-2 gap-4";
                            if ($sc == 1) $stat_grid_class = "grid grid-cols-1 gap-4";
                        ?>
                        <div class="<?php echo $stat_grid_class; ?>">
                            <?php echo implode("\n", $stat_cards); ?>
                        </div>
                        <?php endif; ?>

                        <?php
                        $earn_cards = [];
                        if (isset($settings['video_enabled']) && $settings['video_enabled'] == '1') {
                            $earn_cards[] = '
                                <a href="' . BASE_URL . '/user/video" class="bg-white/[0.02] p-5 rounded-lg border border-white/10 hover:border-white/20 transition-all text-center group flex flex-col justify-between h-full">
                                    <div>
                                        <div class="w-12 h-12 mx-auto bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform"><i class="fa-solid fa-video"></i></div>
                                        <h3 class="font-bold text-zinc-200 text-sm">Video Rewards</h3>
                                    </div>
                                    <p class="text-[10px] text-zinc-500 mt-2 uppercase tracking-wider font-semibold font-mono">Watch &amp; Earn</p>
                                </a>';
                        }
                        if (isset($settings['ptc_enabled']) && $settings['ptc_enabled'] == '1') {
                            $earn_cards[] = '
                                <a href="' . BASE_URL . '/user/ptc" class="bg-white/[0.02] p-5 rounded-lg border border-white/10 hover:border-white/20 transition-all text-center group flex flex-col justify-between h-full">
                                    <div>
                                        <div class="w-12 h-12 mx-auto bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform"><i class="fa-solid fa-mouse-pointer"></i></div>
                                        <h3 class="font-bold text-zinc-200 text-sm">PTC Ads</h3>
                                    </div>
                                    <p class="text-[10px] text-zinc-500 mt-2 uppercase tracking-wider font-semibold font-mono">Easy USDT</p>
                                </a>';
                        }
                        if (isset($settings['shortlinks_enabled']) && $settings['shortlinks_enabled'] == '1') {
                            $earn_cards[] = '
                                <a href="' . BASE_URL . '/user/shortlinks" class="bg-white/[0.02] p-5 rounded-lg border border-white/10 hover:border-white/20 transition-all text-center group flex flex-col justify-between h-full">
                                    <div>
                                        <div class="w-12 h-12 mx-auto bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform"><i class="fa-solid fa-link"></i></div>
                                        <h3 class="font-bold text-zinc-200 text-sm">Shortlinks</h3>
                                    </div>
                                    <p class="text-[10px] text-zinc-500 mt-2 uppercase tracking-wider font-semibold font-mono">Fast Claim</p>
                                </a>';
                        }
                        if (isset($settings['offerwallapi_tasks_enabled']) && $settings['offerwallapi_tasks_enabled'] == '1') {
                            $earn_cards[] = '
                                <a href="' . BASE_URL . '/user/tasks" class="bg-white/[0.02] p-5 rounded-lg border border-white/10 hover:border-white/20 transition-all text-center group flex flex-col justify-between h-full">
                                    <div>
                                        <div class="w-12 h-12 mx-auto bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform"><i class="fa-solid fa-list-check"></i></div>
                                        <h3 class="font-bold text-zinc-200 text-sm">Tasks</h3>
                                    </div>
                                    <p class="text-[10px] text-zinc-500 mt-2 uppercase tracking-wider font-semibold font-mono">Micro Tasks</p>
                                </a>';
                        }
                        if (offerwall_page_enabled($settings)) {
                            $earn_cards[] = '
                                <a href="' . BASE_URL . '/user/offerwall" class="bg-white/[0.02] p-5 rounded-lg border border-white/10 hover:border-white/20 transition-all text-center group flex flex-col justify-between h-full">
                                    <div>
                                        <div class="w-12 h-12 mx-auto bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform"><i class="fa-solid fa-table-cells"></i></div>
                                        <h3 class="font-bold text-zinc-200 text-sm">Offerwall</h3>
                                    </div>
                                    <p class="text-[10px] text-zinc-500 mt-2 uppercase tracking-wider font-semibold font-mono">Offers &amp; Surveys</p>
                                </a>';
                        }

                        $c = count($earn_cards);
                        if ($c > 0):
                            $grid_class = "grid grid-cols-2 md:grid-cols-4 gap-4";
                            if ($c == 3) $grid_class = "grid grid-cols-1 md:grid-cols-3 gap-4";
                            if ($c == 2) $grid_class = "grid grid-cols-2 gap-4";
                            if ($c == 1) $grid_class = "grid grid-cols-1 gap-4";
                        ?>
                        <div>
                            <div class="section-label mb-3">Earn</div>
                            <h2 class="text-xl font-semibold tracking-tight text-zinc-100 mb-4">Ways to Earn</h2>
                            <div class="<?php echo $grid_class; ?>">
                                <?php echo implode("\n", $earn_cards); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div id="ad-container-dashboard_ways_ads" class="mt-8 flex justify-center w-full min-h-[90px]"></div>

                    </div>

                    <div class="flex flex-col gap-6">
                        <div class="bg-white/[0.02] p-6 rounded-lg border border-white/10 flex items-center justify-around gap-4 min-h-[90px]">
                            <a href="<?= BASE_URL ?>/user/withdraw" class="text-center block hover:opacity-80 transition-opacity cursor-pointer">
                                <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-1 font-mono">Main Balance</p>
                                <p class="text-xl font-bold font-mono text-primary"><?= number_format($user_data['balance'], 6) ?> <span class="text-zinc-400 text-sm font-sans">USDT</span></p>
                            </a>
                            <?php if (isset($settings['wheel_enabled']) && $settings['wheel_enabled'] == '1'): ?>
                            <div class="w-px h-12 bg-white/10 hidden sm:block"></div>
                            <a href="<?= BASE_URL ?>/user/wheel" class="text-center hidden sm:block hover:opacity-80 transition-opacity cursor-pointer block">
                                <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-1 font-mono">Energy</p>
                                <p class="text-xl font-bold font-mono text-zinc-100"><?= htmlspecialchars($user_data['energy']) ?> <i class="fa-solid fa-bolt text-primary text-sm ml-1"></i></p>
                            </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($fp_email !== ''): ?>
                        <div class="bg-white/[0.02] p-4 rounded-lg border border-white/10 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-envelope-circle-check"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider font-mono">Payout Email</p>
                                <p class="text-sm font-semibold text-zinc-300 truncate"><?php echo htmlspecialchars($fp_email_masked); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($settings['level_system_enabled']) && $settings['level_system_enabled'] == '1'): ?>
                        <?php
                        $current_xp = isset($user_data['xp']) ? intval($user_data['xp']) : 0;
                        $current_level = floor(sqrt($current_xp / 100));
                        $current_level_base_xp = 100 * pow($current_level, 2);
                        $next_level = $current_level + 1;
                        $next_level_base_xp = 100 * pow($next_level, 2);

                        $xp_in_level = $current_xp - $current_level_base_xp;
                        $xp_required_for_next = $next_level_base_xp - $current_level_base_xp;
                        $progress_percent = min(100, max(0, ($xp_in_level / $xp_required_for_next) * 100));
                        $progress_percent_formatted = number_format($progress_percent, 1);
                        ?>
                        <div class="bg-white/[0.02] p-6 rounded-lg border border-white/10">
                            <div class="flex justify-between items-end mb-2">
                                <h3 class="font-bold text-zinc-100">Level <?= $current_level ?></h3>
                                <span class="text-xs text-primary font-semibold font-mono"><?= $progress_percent_formatted ?>% to Level <?= $next_level ?></span>
                            </div>
                            <div class="w-full bg-white/10 rounded-full h-2 mb-2">
                                <div class="bg-gradient-to-r from-primaryDeep to-primary h-2 rounded-full" style="width: <?= $progress_percent ?>%"></div>
                            </div>
                            <p class="text-xs text-zinc-500 text-right font-mono"><?= number_format($current_xp) ?> / <?= number_format($next_level_base_xp) ?> XP</p>
                        </div>
                        <?php endif; ?>

                        <div id="ad-container-dashboard_level_ads" class="w-full flex justify-center min-h-[50px]"></div>

                        <?php
                        $timed = isset($settings['timed_faucet_enabled']) && $settings['timed_faucet_enabled'] == '1';
                        $instant = isset($settings['instant_faucet_enabled']) && $settings['instant_faucet_enabled'] == '1';
                        $both = $timed && $instant;
                        $gridClass = $both ? "grid grid-cols-2 gap-4" : "grid grid-cols-1";
                        ?>
                        <div class="<?php echo $gridClass; ?>">
                            <?php if ($timed): ?>
                            <div class="bg-gradient-to-br from-primaryDeep to-primary p-5 rounded-lg text-zinc-950 text-center relative overflow-hidden flex flex-col justify-between h-full">
                                <div class="absolute -right-4 -top-4 text-zinc-950/10 text-5xl"><i class="fa-solid fa-clock"></i></div>
                                <h3 class="font-bold text-lg mb-4 relative z-10">Timed Faucet</h3>
                                <a href="<?= BASE_URL ?>/user/timed" class="block w-full bg-zinc-950 text-primary font-bold py-2 rounded-lg hover:bg-zinc-900 transition-all relative z-10 text-sm">Claim</a>
                            </div>
                            <?php endif; ?>

                            <?php if ($instant): ?>
                            <div class="bg-white/[0.02] border border-white/10 p-5 rounded-lg text-center relative overflow-hidden flex flex-col justify-between h-full">
                                <div class="absolute -right-4 -top-4 text-primary/10 text-5xl"><i class="fa-solid fa-bolt"></i></div>
                                <h3 class="font-bold text-lg mb-4 relative z-10 text-zinc-100">Instant Faucet</h3>
                                <a href="<?= BASE_URL ?>/user/instant" class="block w-full bg-primary text-zinc-950 font-bold py-2 rounded-lg hover:bg-primaryHover transition-all relative z-10 text-sm">Claim</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </main>

    <script>window.OW_PAGE_DATA = <?php echo json_encode($page_data); ?>;</script>
    <script src="<?= BASE_URL ?>/assets/js/pages/user-index.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
