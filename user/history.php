<?php
$page_title = "History";
include 'includes/header.php';

$allowed_tabs = [];
if (isset($settings['timed_faucet_enabled']) && $settings['timed_faucet_enabled'] == '1') $allowed_tabs[] = 'timed';
if (isset($settings['instant_faucet_enabled']) && $settings['instant_faucet_enabled'] == '1') $allowed_tabs[] = 'instant';
if (isset($settings['video_enabled']) && $settings['video_enabled'] == '1') $allowed_tabs[] = 'video';
if (isset($settings['ptc_enabled']) && $settings['ptc_enabled'] == '1') $allowed_tabs[] = 'ptc';
if (isset($settings['offerwallapi_tasks_enabled']) && $settings['offerwallapi_tasks_enabled'] == '1') $allowed_tabs[] = 'tasks';
if (offerwall_page_enabled($settings)) $allowed_tabs[] = 'offerwall';
if (isset($settings['shortlinks_enabled']) && $settings['shortlinks_enabled'] == '1') $allowed_tabs[] = 'shortlinks';
if (isset($settings['daily_bonus_enabled']) && $settings['daily_bonus_enabled'] == '1') $allowed_tabs[] = 'daily_bonus';
if (isset($settings['wheel_enabled']) && $settings['wheel_enabled'] == '1') $allowed_tabs[] = 'wheel';

$current_tab = $_GET['tab'] ?? ($allowed_tabs[0] ?? '');
$user_id = $_SESSION['user_id'];
if (!in_array($current_tab, $allowed_tabs)) {
    $current_tab = $allowed_tabs[0] ?? '';
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$total_records = 0;
$history_data = [];

if ($current_tab !== '') {
    switch ($current_tab) {
        case 'timed':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM faucet_claims WHERE user_id = ? AND faucet_type='timed'");
            $stmt->execute([$user_id]);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT reward_usdt, claimed_at as created_at FROM faucet_claims WHERE user_id = ? AND faucet_type='timed' ORDER BY claimed_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$user_id]);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'instant':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM faucet_claims WHERE user_id = ? AND faucet_type='instant'");
            $stmt->execute([$user_id]);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT reward_usdt, claimed_at as created_at FROM faucet_claims WHERE user_id = ? AND faucet_type='instant' ORDER BY claimed_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$user_id]);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'video':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM video_claims WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT reward_usdt, status, campaign_name, created_at FROM video_claims WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$user_id]);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'ptc':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_claims WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT reward_usdt, status, campaign_name, created_at FROM ptc_claims WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$user_id]);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'tasks':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_claims WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT reward_usdt, status, campaign_name, claimed_at as created_at FROM task_claims WHERE user_id = ? ORDER BY claimed_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$user_id]);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'shortlinks':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM shortlink_claims WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT reward_usdt, status, campaign_name, created_at FROM shortlink_claims WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$user_id]);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'offerwall':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM offerwall_claims WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT reward_usdt, status, campaign_name, created_at FROM offerwall_claims WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$user_id]);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'daily_bonus':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_bonus_claims WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT reward_usdt, day_number, created_at FROM daily_bonus_claims WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$user_id]);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'wheel':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM wheel_spins WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT reward_value as reward_usdt, reward_type, created_at FROM wheel_spins WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$user_id]);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
}

$total_pages = ceil($total_records / $limit);

function getTabName($tab) {
    switch ($tab) {
        case 'timed': return 'Timed Faucet';
        case 'instant': return 'Instant Faucet';
        case 'video': return 'Video Rewards';
        case 'ptc': return 'PTC Ads';
        case 'tasks': return 'Tasks';
        case 'offerwall': return 'Offerwall';
        case 'shortlinks': return 'Shortlinks';
        case 'daily_bonus': return 'Daily Bonus';
        case 'wheel': return 'Wheel Spins';
        default: return ucfirst($tab);
    }
}
?>

<main class="flex-1 p-4 lg:p-8 w-full mt-16 md:mt-20 relative z-10">
    <div class="max-w-6xl mx-auto space-y-6">

        <div id="ad-container-general_top_ads" class="flex justify-center empty:hidden w-full min-h-[90px] mb-6"></div>

        <div>
            <div class="section-label">History</div>
            <h1 class="text-3xl font-extrabold text-zinc-100 tracking-tight mt-2">Activity History</h1>
        </div>

        <div class="bg-surface-card p-4 rounded-lg border border-white/10">
            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                <?php foreach ($allowed_tabs as $tab): ?>
                    <a href="<?= BASE_URL ?>/user/history/<?= $tab ?>" class="whitespace-nowrap px-4 py-2 rounded-lg text-sm font-bold transition-all <?= ($current_tab === $tab) ? 'bg-primary text-zinc-950' : 'bg-white/5 text-zinc-300 hover:bg-white/10' ?>">
                        <?= getTabName($tab) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-surface-card rounded-lg border border-white/10 overflow-hidden">
            <div class="p-6 border-b border-white/10 bg-white/[0.03]">
                <h2 class="text-lg font-bold text-zinc-100"><?= getTabName($current_tab) ?> History</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-white/[0.03] border-b border-white/10 text-xs uppercase tracking-wider text-zinc-400">
                            <th class="p-4 font-medium">Date &amp; Time</th>

                            <?php if (in_array($current_tab, ['video', 'ptc', 'tasks', 'shortlinks', 'offerwall'])): ?>
                            <th class="p-4 font-medium">Campaign / Item</th>
                            <?php elseif ($current_tab === 'daily_bonus'): ?>
                            <th class="p-4 font-medium">Day Number</th>
                            <?php elseif ($current_tab === 'wheel'): ?>
                            <th class="p-4 font-medium">Reward Type</th>
                            <?php endif; ?>

                            <th class="p-4 font-medium text-right">Reward</th>

                            <?php if (in_array($current_tab, ['video', 'ptc', 'tasks', 'shortlinks', 'offerwall'])): ?>
                            <th class="p-4 font-medium text-right">Status</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php if (empty($history_data)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-zinc-500 font-medium">
                                    <i class="fa-solid fa-ghost text-4xl mb-3 text-zinc-600"></i>
                                    <p>No activity found for this category yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history_data as $row): ?>
                            <tr class="border-b border-white/10 hover:bg-white/[0.02] transition-colors">
                                <td class="p-4">
                                    <p class="font-bold text-zinc-200"><?= date('M d, Y', strtotime($row['created_at'])) ?></p>
                                    <p class="text-xs text-zinc-500 font-mono"><?= date('H:i:s', strtotime($row['created_at'])) ?></p>
                                </td>

                                <?php if (in_array($current_tab, ['video', 'ptc', 'shortlinks', 'offerwall'])): ?>
                                <td class="p-4 font-medium text-zinc-300">
                                    <?php
                                    if ($current_tab === 'video' && empty($row['campaign_name'])) {
                                        echo 'Video Reward';
                                    } elseif ($current_tab === 'offerwall' && empty($row['campaign_name'])) {
                                        echo 'Offer';
                                    } else {
                                        echo htmlspecialchars(!empty($row['campaign_name']) ? $row['campaign_name'] : 'N/A');
                                    }
                                    ?>
                                </td>
                                <?php elseif ($current_tab === 'tasks'): ?>
                                <td class="p-4 font-medium text-zinc-300">
                                    <?= htmlspecialchars(!empty($row['campaign_name']) ? $row['campaign_name'] : 'N/A') ?>
                                </td>
                                <?php elseif ($current_tab === 'daily_bonus'): ?>
                                <td class="p-4 font-medium text-zinc-300">
                                    Day <?= (int)$row['day_number'] ?>
                                </td>
                                <?php elseif ($current_tab === 'wheel'): ?>
                                <td class="p-4 font-medium text-zinc-300">
                                    <span class="<?= $row['reward_type'] === 'Energy' ? 'text-zinc-300' : 'text-primary' ?> font-bold"><?= htmlspecialchars($row['reward_type']) ?></span>
                                </td>
                                <?php endif; ?>

                                <td class="p-4 text-right">
                                    <?php if (isset($row['reward_type']) && $row['reward_type'] === 'Energy'): ?>
                                        <span class="font-bold text-zinc-300 font-mono">+<?= (int)$row['reward_usdt'] ?> Energy</span>
                                    <?php else: ?>
                                        <span class="font-bold text-primary font-mono">+<?= number_format((float)$row['reward_usdt'], 6) ?> USDT</span>
                                    <?php endif; ?>
                                </td>

                                <?php if (in_array($current_tab, ['video', 'ptc', 'tasks', 'shortlinks', 'offerwall'])): ?>
                                <td class="p-4 text-right">
                                    <?php if ($row['status'] === 'completed'): ?>
                                        <span class="bg-primary/10 text-primary border border-primary/25 px-2.5 py-1 rounded-lg text-xs font-bold">Completed</span>
                                    <?php elseif ($row['status'] === 'pending'): ?>
                                        <span class="bg-white/5 text-zinc-300 border border-white/10 px-2.5 py-1 rounded-lg text-xs font-bold">Pending</span>
                                    <?php else: ?>
                                        <span class="bg-red-500/10 text-red-400 border border-red-500/20 px-2.5 py-1 rounded-lg text-xs font-bold"><?= ucfirst($row['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>

                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="p-4 border-t border-white/10 bg-white/[0.03] flex items-center justify-between">
                <p class="text-sm text-zinc-500 font-medium font-mono">Page <?= $page ?> of <?= $total_pages ?></p>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="<?= BASE_URL ?>/user/history/<?= $current_tab ?>?page=<?= $page - 1 ?>" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-sm font-bold text-zinc-300 hover:border-primary/40 hover:text-primary transition-all">Previous</a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?= BASE_URL ?>/user/history/<?= $current_tab ?>?page=<?= $page + 1 ?>" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-sm font-bold text-zinc-300 hover:border-primary/40 hover:text-primary transition-all">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div id="ad-container-general_bottom_ads" class="flex justify-center empty:hidden w-full min-h-[90px] mt-6"></div>

    </div>
</main>

<?php include 'includes/footer.php'; ?>
