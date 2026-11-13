<?php
include 'includes/header.php';

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$new_users_today = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$total_withdrawals = $pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status='approved'")->fetchColumn() ?: 0;
$pending_withdrawals = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn();
$withdrawn_today = $pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status='approved' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

$today_video = $pdo->query("SELECT COUNT(*) FROM video_claims WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$today_ptc = $pdo->query("SELECT COUNT(*) FROM ptc_claims WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$today_tasks = $pdo->query("SELECT COUNT(*) FROM task_claims WHERE DATE(claimed_at) = CURDATE()")->fetchColumn();
$today_shortlinks = $pdo->query("SELECT COUNT(*) FROM shortlink_claims WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$today_offerwall = 0;
try {
    $today_offerwall = $pdo->query("SELECT COUNT(*) FROM offerwall_claims WHERE DATE(created_at) = CURDATE()")->fetchColumn();
} catch (Exception $e) {}

$chart_dates = [];
$chart_labels = [];
$chart_data = [
    'active_users' => [], 'new_users' => [], 'withdrawals' => [], 'withdrawals_paid' => [],
    'timed_claims' => [], 'timed_paid' => [], 'instant_claims' => [], 'instant_paid' => [],
    'video_completed' => [], 'video_paid' => [],
    'ptc_completed' => [], 'ptc_paid' => [], 'shortlinks_completed' => [], 'shortlinks_paid' => [],
    'tasks_completed' => [], 'tasks_paid' => [],
    'offerwall_completed' => [], 'offerwall_paid' => [],
    'wheel_spins' => [], 'wheel_paid' => [], 'daily_bonus_completed' => [], 'daily_bonus_paid' => [],
    'referral_paid' => []
];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_dates[] = $date;
    $chart_labels[] = date('M d', strtotime($date));

    foreach (array_keys($chart_data) as $key) {
        $chart_data[$key][$date] = 0;
    }
}

$date_limit = date('Y-m-d', strtotime('-6 days'));

function fillChartData($pdo_conn, $query, &$chart_data, $date_col, $count_col, $sum_col, $target_count, $target_sum) {
    $stmt = $pdo_conn->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $d = $row[$date_col];
        if ($target_count && isset($chart_data[$target_count][$d])) {
            $chart_data[$target_count][$d] = (int)$row[$count_col];
        }
        if ($target_sum && isset($chart_data[$target_sum][$d])) {
            $chart_data[$target_sum][$d] = round((float)$row[$sum_col], 6);
        }
    }
}

$ow_union = '';
try {
    $pdo->query("SELECT 1 FROM offerwall_claims LIMIT 1");
    $ow_union = "UNION SELECT DATE(created_at) as d, user_id FROM offerwall_claims WHERE created_at >= '$date_limit 00:00:00'\n    ";
} catch (Exception $e) {}

$q_active = "SELECT d, COUNT(DISTINCT user_id) as c FROM (
    SELECT DATE(claimed_at) as d, user_id FROM faucet_claims WHERE claimed_at >= '$date_limit 00:00:00'
    UNION SELECT DATE(created_at) as d, user_id FROM ptc_claims WHERE created_at >= '$date_limit 00:00:00'
    UNION SELECT DATE(claimed_at) as d, user_id FROM task_claims WHERE claimed_at >= '$date_limit 00:00:00'
    UNION SELECT DATE(created_at) as d, user_id FROM shortlink_claims WHERE created_at >= '$date_limit 00:00:00'
    UNION SELECT DATE(created_at) as d, user_id FROM video_claims WHERE created_at >= '$date_limit 00:00:00'
    " . $ow_union . "UNION SELECT DATE(created_at) as d, user_id FROM wheel_spins WHERE created_at >= '$date_limit 00:00:00'
    UNION SELECT DATE(l.created_at) as d, u.id as user_id FROM login_logs l JOIN users u ON l.email = u.faucetpay_email WHERE l.created_at >= '$date_limit 00:00:00'
) as activity GROUP BY d";
fillChartData($pdo, $q_active, $chart_data, 'd', 'c', null, 'active_users', null);
fillChartData($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c FROM users WHERE created_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', null, 'new_users', null);
fillChartData($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c, SUM(amount) as s FROM withdrawals WHERE status='approved' AND created_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', 's', 'withdrawals', 'withdrawals_paid');
fillChartData($pdo, "SELECT DATE(claimed_at) as d, COUNT(*) as c, SUM(reward_usdt) as s FROM faucet_claims WHERE faucet_type='timed' AND claimed_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', 's', 'timed_claims', 'timed_paid');
fillChartData($pdo, "SELECT DATE(claimed_at) as d, COUNT(*) as c, SUM(reward_usdt) as s FROM faucet_claims WHERE faucet_type='instant' AND claimed_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', 's', 'instant_claims', 'instant_paid');
fillChartData($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c, SUM(reward_usdt) as s FROM video_claims WHERE status='completed' AND created_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', 's', 'video_completed', 'video_paid');
fillChartData($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c, SUM(reward_usdt) as s FROM ptc_claims WHERE status='completed' AND created_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', 's', 'ptc_completed', 'ptc_paid');
fillChartData($pdo, "SELECT DATE(claimed_at) as d, COUNT(*) as c, SUM(reward_usdt) as s FROM task_claims WHERE status='completed' AND claimed_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', 's', 'tasks_completed', 'tasks_paid');
fillChartData($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c, SUM(reward_usdt) as s FROM shortlink_claims WHERE status='completed' AND created_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', 's', 'shortlinks_completed', 'shortlinks_paid');
try {
    fillChartData($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c, SUM(reward_usdt) as s FROM offerwall_claims WHERE status='completed' AND created_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', 's', 'offerwall_completed', 'offerwall_paid');
} catch (Exception $e) {}
fillChartData($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c, SUM(reward_value) as s FROM wheel_spins WHERE reward_type='USDT' AND created_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', 's', 'wheel_spins', 'wheel_paid');
fillChartData($pdo, "SELECT DATE(created_at) as d, COUNT(*) as c, SUM(reward_usdt) as s FROM daily_bonus_claims WHERE created_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', 'c', 's', 'daily_bonus_completed', 'daily_bonus_paid');
fillChartData($pdo, "SELECT DATE(created_at) as d, SUM(amount) as s FROM referral_earnings WHERE created_at >= '$date_limit 00:00:00' GROUP BY d", $chart_data, 'd', null, 's', null, 'referral_paid');

$js_data = [];
foreach ($chart_data as $key => $date_values) {
    $js_data[$key] = array_values($date_values);
}

$total_withdrawals_f = number_format($total_withdrawals, 6);
$withdrawn_today_f = number_format($withdrawn_today, 6);
$total_users_f = number_format($total_users);
$new_users_today_f = number_format($new_users_today);
$today_video_f = number_format($today_video);
$today_ptc_f = number_format($today_ptc);
$today_tasks_f = number_format($today_tasks);
$today_shortlinks_f = number_format($today_shortlinks);
$today_offerwall_f = number_format($today_offerwall);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="flex-1 p-4 lg:p-8 w-full">
    <div class="max-w-7xl mx-auto space-y-8">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            <div class="rounded-lg border border-white/10 bg-white/[0.02] p-6 micro-anim">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-xs text-zinc-500 font-bold uppercase tracking-wider mb-1">Total Users</p>
                        <h3 class="text-2xl font-bold text-zinc-100 font-mono"><?= $total_users_f ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <p class="text-sm text-primary font-medium"><i class="fa-solid fa-arrow-trend-up mr-1"></i> +<?= $new_users_today_f ?> today</p>
            </div>

            <div class="rounded-lg border border-white/10 bg-white/[0.02] p-6 micro-anim">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-xs text-zinc-500 font-bold uppercase tracking-wider mb-1">Total Withdrawals</p>
                        <h3 class="text-2xl font-bold text-zinc-100 font-mono"><?= $total_withdrawals_f ?> <span class="text-sm text-primary">USDT</span></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg">
                        <i class="fa-solid fa-money-bill-transfer"></i>
                    </div>
                </div>
                <p class="text-sm text-zinc-500 font-medium">All time processed</p>
            </div>

            <div class="rounded-lg border border-white/10 bg-white/[0.02] p-6 micro-anim">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-xs text-zinc-500 font-bold uppercase tracking-wider mb-1">Pending Payouts</p>
                        <h3 class="text-2xl font-bold text-zinc-100 font-mono"><?= $pending_withdrawals ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>
                <?php if ($pending_withdrawals > 0): ?>
                <p class="text-sm text-red-400 font-medium"><i class="fa-solid fa-circle-exclamation mr-1"></i> Requires attention</p>
                <?php else: ?>
                <p class="text-sm text-zinc-500 font-medium">All caught up</p>
                <?php endif; ?>
            </div>

            <div class="rounded-lg border border-white/10 bg-white/[0.02] p-6 micro-anim">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-xs text-zinc-500 font-bold uppercase tracking-wider mb-1">Withdrawn Today</p>
                        <h3 class="text-2xl font-bold text-zinc-100 font-mono"><?= $withdrawn_today_f ?> <span class="text-sm text-primary">USDT</span></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg">
                        <i class="fa-solid fa-calendar-day"></i>
                    </div>
                </div>
                <p class="text-sm text-zinc-500 font-medium">1 USDT = $1</p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4">
            <div class="rounded-lg border border-white/10 bg-white/[0.02] p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg shrink-0"><i class="fa-solid fa-video"></i></div>
                <div>
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider">Today Videos</p>
                    <h4 class="text-base font-bold text-zinc-100 font-mono"><?= $today_video_f ?></h4>
                </div>
            </div>

            <div class="rounded-lg border border-white/10 bg-white/[0.02] p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg shrink-0"><i class="fa-solid fa-hand-pointer"></i></div>
                <div>
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider">Today PTC Ads</p>
                    <h4 class="text-base font-bold text-zinc-100 font-mono"><?= $today_ptc_f ?></h4>
                </div>
            </div>

            <div class="rounded-lg border border-white/10 bg-white/[0.02] p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg shrink-0"><i class="fa-solid fa-list-ul"></i></div>
                <div>
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider">Today Tasks</p>
                    <h4 class="text-base font-bold text-zinc-100 font-mono"><?= $today_tasks_f ?></h4>
                </div>
            </div>

            <div class="rounded-lg border border-white/10 bg-white/[0.02] p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg shrink-0"><i class="fa-solid fa-link"></i></div>
                <div>
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider">Today Shortlinks</p>
                    <h4 class="text-base font-bold text-zinc-100 font-mono"><?= $today_shortlinks_f ?></h4>
                </div>
            </div>

            <div class="rounded-lg border border-white/10 bg-white/[0.02] p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg shrink-0"><i class="fa-solid fa-table-cells"></i></div>
                <div>
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider">Today Offerwall</p>
                    <h4 class="text-base font-bold text-zinc-100 font-mono"><?= $today_offerwall_f ?></h4>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-white/10 bg-white/[0.02] p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold text-zinc-100 tracking-tight">Activity Overview (Last 7 Days)</h3>
                    <p class="text-sm text-zinc-500">All amounts are shown directly in USDT (1 USDT = $1).</p>
                </div>
            </div>

            <div class="w-full" style="height: 450px;">
                <canvas id="statsChart"></canvas>
            </div>
        </div>

    </div>
</main>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['chart' => ['labels' => $chart_labels, 'series' => $js_data]]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-dashboard.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
