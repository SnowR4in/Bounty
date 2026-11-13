<?php
$page_title = "Statistics";
include 'includes/header.php';

$allowed_tabs = [];
if (isset($_site['timed_faucet_enabled']) && $_site['timed_faucet_enabled'] == '1') $allowed_tabs[] = 'timed';
if (isset($_site['instant_faucet_enabled']) && $_site['instant_faucet_enabled'] == '1') $allowed_tabs[] = 'instant';
if (isset($_site['video_enabled']) && $_site['video_enabled'] == '1') $allowed_tabs[] = 'video';
if (isset($_site['ptc_enabled']) && $_site['ptc_enabled'] == '1') $allowed_tabs[] = 'ptc';
if (isset($_site['offerwallapi_tasks_enabled']) && $_site['offerwallapi_tasks_enabled'] == '1') $allowed_tabs[] = 'tasks';
if (offerwall_page_enabled($_site)) $allowed_tabs[] = 'offerwall';
if (isset($_site['shortlinks_enabled']) && $_site['shortlinks_enabled'] == '1') $allowed_tabs[] = 'shortlinks';
if (isset($_site['daily_bonus_enabled']) && $_site['daily_bonus_enabled'] == '1') $allowed_tabs[] = 'daily_bonus';
if (isset($_site['wheel_enabled']) && $_site['wheel_enabled'] == '1') $allowed_tabs[] = 'wheel';

$current_tab = $_GET['tab'] ?? ($allowed_tabs[0] ?? '');
if (!in_array($current_tab, $allowed_tabs)) {
    $current_tab = $allowed_tabs[0] ?? '';
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "";
$queryParams = [];
$queryParamsCount = [];

if ($search !== '') {
    $whereClause = " WHERE (u.username LIKE ? OR u.faucetpay_email LIKE ?) ";
    $queryParams = ["%$search%", "%$search%"];
    $queryParamsCount = ["%$search%", "%$search%"];
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$total_records = 0;
$history_data = [];

if ($current_tab !== '') {
    switch ($current_tab) {
        case 'timed':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM faucet_claims t LEFT JOIN users u ON t.user_id = u.id" . ($whereClause ? $whereClause . " AND t.faucet_type='timed'" : " WHERE t.faucet_type='timed'"));
            $stmt->execute($queryParamsCount);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT t.reward_usdt, t.claimed_at as created_at, u.username, u.faucetpay_email FROM faucet_claims t LEFT JOIN users u ON t.user_id = u.id" . ($whereClause ? $whereClause . " AND t.faucet_type='timed'" : " WHERE t.faucet_type='timed'") . " ORDER BY t.claimed_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($queryParams);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'instant':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM faucet_claims t LEFT JOIN users u ON t.user_id = u.id" . ($whereClause ? $whereClause . " AND t.faucet_type='instant'" : " WHERE t.faucet_type='instant'"));
            $stmt->execute($queryParamsCount);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT t.reward_usdt, t.claimed_at as created_at, u.username, u.faucetpay_email FROM faucet_claims t LEFT JOIN users u ON t.user_id = u.id" . ($whereClause ? $whereClause . " AND t.faucet_type='instant'" : " WHERE t.faucet_type='instant'") . " ORDER BY t.claimed_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($queryParams);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'video':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM video_claims t LEFT JOIN users u ON t.user_id = u.id" . $whereClause);
            $stmt->execute($queryParamsCount);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT t.reward_usdt, t.status, t.campaign_name, t.created_at, u.username, u.faucetpay_email FROM video_claims t LEFT JOIN users u ON t.user_id = u.id" . $whereClause . " ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($queryParams);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'ptc':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ptc_claims t JOIN users u ON t.user_id = u.id" . $whereClause);
            $stmt->execute($queryParamsCount);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT t.reward_usdt, t.status, t.campaign_name, t.created_at, u.username, u.faucetpay_email FROM ptc_claims t JOIN users u ON t.user_id = u.id" . $whereClause . " ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($queryParams);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'tasks':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_claims t LEFT JOIN users u ON t.user_id = u.id" . $whereClause);
            $stmt->execute($queryParamsCount);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT t.reward_usdt, t.status, t.campaign_name, t.claimed_at as created_at, u.username, u.faucetpay_email FROM task_claims t LEFT JOIN users u ON t.user_id = u.id" . $whereClause . " ORDER BY t.claimed_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($queryParams);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'shortlinks':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM shortlink_claims t LEFT JOIN users u ON t.user_id = u.id" . $whereClause);
            $stmt->execute($queryParamsCount);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT t.reward_usdt, t.status, t.campaign_name, t.created_at, u.username, u.faucetpay_email FROM shortlink_claims t LEFT JOIN users u ON t.user_id = u.id" . $whereClause . " ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($queryParams);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'offerwall':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM offerwall_claims t LEFT JOIN users u ON t.user_id = u.id" . $whereClause);
            $stmt->execute($queryParamsCount);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT t.reward_usdt, t.status, t.campaign_name, t.created_at, u.username, u.faucetpay_email FROM offerwall_claims t LEFT JOIN users u ON t.user_id = u.id" . $whereClause . " ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($queryParams);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'daily_bonus':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_bonus_claims t LEFT JOIN users u ON t.user_id = u.id" . $whereClause);
            $stmt->execute($queryParamsCount);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT t.reward_usdt, t.day_number, t.created_at, u.username, u.faucetpay_email FROM daily_bonus_claims t LEFT JOIN users u ON t.user_id = u.id" . $whereClause . " ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($queryParams);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'wheel':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM wheel_spins t LEFT JOIN users u ON t.user_id = u.id" . $whereClause);
            $stmt->execute($queryParamsCount);
            $total_records = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT t.reward_value as reward_usdt, t.reward_type, t.created_at, u.username, u.faucetpay_email FROM wheel_spins t LEFT JOIN users u ON t.user_id = u.id" . $whereClause . " ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($queryParams);
            $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
}

$total_pages = ceil($total_records / $limit);

function getTabName($tab) {
    switch($tab) {
        case 'timed': return 'Timed Faucet';
        case 'instant': return 'Instant Faucet';
        case 'video': return 'Videos';
        case 'ptc': return 'PTC Ads';
        case 'tasks': return 'Tasks';
        case 'offerwall': return 'Offerwall';
        case 'shortlinks': return 'Shortlinks';
        case 'daily_bonus': return 'Daily Bonus';
        case 'wheel': return 'Wheel Spins';
        default: return ucfirst($tab);
    }
}

function getPaginationQuery($page, $tab, $search) {
    $q = "?tab=" . urlencode($tab) . "&page=" . $page;
    if ($search !== '') {
        $q .= "&search=" . urlencode($search);
    }
    return $q;
}
?>

<main class="flex-1 p-4 lg:p-8">
    <div class="max-w-6xl mx-auto space-y-6">

        <div class="rounded-lg border border-white/10 bg-surface-card p-6 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div>
                <h3 class="font-bold text-zinc-100 text-lg tracking-tight">Global Statistics</h3>
                <p class="text-sm text-zinc-500 mt-1">View the history of all user activities.</p>
            </div>
            <form method="GET" action="" class="flex w-full md:w-auto items-center gap-2">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($current_tab) ?>">
                <div class="relative w-full md:w-64">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-zinc-500">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search Username/Email..." class="w-full bg-white/[0.03] border border-white/10 text-zinc-200 placeholder-zinc-600 text-sm rounded-lg focus:border-primary/60 focus:ring-primary/20 block pl-10 p-2.5 outline-none transition-colors">
                </div>
                <button type="submit" class="bg-primary hover:bg-primaryHover text-zinc-950 px-4 py-2.5 rounded-lg font-bold transition-colors">
                    Search
                </button>
                <?php if ($search !== ''): ?>
                <a href="<?= ADMIN_ROUTE ?>/statistics?tab=<?= htmlspecialchars($current_tab) ?>" class="bg-white/5 hover:bg-white/10 border border-white/10 text-zinc-300 px-4 py-2.5 rounded-lg font-bold transition-colors">
                    Clear
                </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="rounded-lg border border-white/10 bg-surface-card p-4">
            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                <?php foreach ($allowed_tabs as $tab): ?>
                    <a href="<?= ADMIN_ROUTE ?>/statistics?tab=<?= $tab ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= ($current_tab === $tab) ? 'bg-primary text-zinc-950' : 'bg-white/5 text-zinc-400 hover:text-primary hover:bg-white/10' ?>">
                        <?= getTabName($tab) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
            <div class="p-6 border-b border-white/10 bg-white/[0.03]">
                <h2 class="text-lg font-bold text-zinc-100 tracking-tight"><?= getTabName($current_tab) ?> Statistics</h2>
            </div>

            <div class="overflow-x-auto table-scroll">
                <table class="w-full text-left border-collapse min-w-[700px] text-sm">
                    <thead>
                        <tr class="border-b border-white/10 text-xs uppercase tracking-wider text-zinc-400">
                            <th class="p-4 font-medium">User</th>
                            <th class="p-4 font-medium">Date & Time</th>

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
                                <td colspan="6" class="p-8 text-center text-zinc-500 font-medium">
                                    <i class="fa-solid fa-ghost text-4xl mb-3 text-zinc-600"></i>
                                    <p>No activity found for this category or filter.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history_data as $row): ?>
                            <tr class="border-b border-white/10 hover:bg-white/[0.02] transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-zinc-100"><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></div>
                                    <div class="text-xs text-zinc-500 font-mono mt-0.5" title="<?= htmlspecialchars($row['faucetpay_email']) ?>"><?= htmlspecialchars($row['faucetpay_email']) ?></div>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-zinc-100"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></p>
                                    <p class="text-xs text-zinc-500"><?php echo date('H:i:s', strtotime($row['created_at'])); ?></p>
                                </td>

                                <?php if (in_array($current_tab, ['video', 'ptc', 'tasks', 'shortlinks', 'offerwall'])): ?>
                                <td class="p-4 font-medium text-zinc-300">
                                    <?php
                                    if ($current_tab === 'video' && empty($row['campaign_name'])) {
                                        echo 'OfferwallApi Video';
                                    } elseif ($current_tab === 'offerwall' && empty($row['campaign_name'])) {
                                        echo 'Offerwall.me';
                                    } else {
                                        echo htmlspecialchars(!empty($row['campaign_name']) ? $row['campaign_name'] : 'N/A');
                                    }
                                    ?>
                                </td>
                                <?php elseif ($current_tab === 'daily_bonus'): ?>
                                <td class="p-4 font-medium text-zinc-300">
                                    Day <?= (int)$row['day_number'] ?>
                                </td>
                                <?php elseif ($current_tab === 'wheel'): ?>
                                <td class="p-4 font-medium text-zinc-300">
                                    <span class="<?= $row['reward_type'] === 'Energy' ? 'text-amber-400' : 'text-primary' ?> font-bold"><?= htmlspecialchars($row['reward_type']) ?></span>
                                </td>
                                <?php endif; ?>

                                <td class="p-4 text-right">
                                    <?php if (isset($row['reward_type']) && $row['reward_type'] === 'Energy'): ?>
                                        <span class="font-bold text-amber-400 font-mono">+<?= (int)$row['reward_usdt'] ?> Energy</span>
                                    <?php else: ?>
                                        <span class="font-bold text-primary font-mono">+<?= number_format((float)$row['reward_usdt'], 6) ?> USDT</span>
                                    <?php endif; ?>
                                </td>

                                <?php if (in_array($current_tab, ['video', 'ptc', 'shortlinks', 'tasks', 'offerwall'])): ?>
                                <td class="p-4 text-right">
                                    <?php if ($row['status'] === 'completed'): ?>
                                        <span class="bg-primary/10 text-primary border border-primary/25 px-2.5 py-1 rounded-lg text-xs font-bold">Completed</span>
                                    <?php elseif ($row['status'] === 'pending'): ?>
                                        <span class="bg-amber-500/10 text-amber-400 border border-amber-500/25 px-2.5 py-1 rounded-lg text-xs font-bold">Pending</span>
                                    <?php else: ?>
                                        <span class="bg-red-500/10 text-red-400 border border-red-500/25 px-2.5 py-1 rounded-lg text-xs font-bold"><?= ucfirst($row['status']) ?></span>
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
                <p class="text-sm text-zinc-500 font-medium">Page <?= $page ?> of <?= $total_pages ?></p>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="<?= ADMIN_ROUTE ?>/statistics<?= getPaginationQuery($page - 1, $current_tab, $search) ?>" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-sm font-bold text-zinc-300 hover:border-primary/40 hover:text-primary transition-all">Previous</a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?= ADMIN_ROUTE ?>/statistics<?= getPaginationQuery($page + 1, $current_tab, $search) ?>" class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-sm font-bold text-zinc-300 hover:border-primary/40 hover:text-primary transition-all">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
