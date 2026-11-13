<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/site_settings.php';
$settings = get_site_settings($pdo);

if (!isset($settings['daily_bonus_enabled']) || $settings['daily_bonus_enabled'] == '0') {
    header('Location: /user/');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT balance, daily_bonus_streak FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT DATEDIFF(CURDATE(), MAX(claim_date)) FROM daily_bonus_claims WHERE user_id = ?");
$stmt->execute([$user_id]);
$days_since_last_claim = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT * FROM daily_bonuses ORDER BY day_number ASC");
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);
$max_day = count($days) > 0 ? max(array_column($days, 'day_number')) : 0;

$current_streak = intval($user['daily_bonus_streak']);

$already_claimed_today = ($days_since_last_claim !== false && $days_since_last_claim !== null && (int)$days_since_last_claim === 0);
$last_claim_is_yesterday = ($days_since_last_claim !== false && $days_since_last_claim !== null && (int)$days_since_last_claim === 1);
$has_claimed_before = ($days_since_last_claim !== false && $days_since_last_claim !== null);

if (!$already_claimed_today && !$last_claim_is_yesterday && $has_claimed_before) {
    $current_streak = 0;
}
if (!$already_claimed_today && $current_streak >= $max_day && $max_day > 0) {
    $current_streak = 0;
}

$next_day_number = $current_streak + 1;

$stmt = $pdo->prepare("SELECT COALESCE(SUM(reward_usdt), 0) FROM faucet_claims WHERE user_id = ? AND DATE(claimed_at) = CURDATE()");
$stmt->execute([$user_id]);
$today_faucet = floatval($stmt->fetchColumn());

$stmt = $pdo->prepare("SELECT COALESCE(SUM(reward_usdt), 0) FROM video_claims WHERE user_id = ? AND status = 'completed' AND DATE(created_at) = CURDATE()");
$stmt->execute([$user_id]);
$today_video = floatval($stmt->fetchColumn());

$today_earned = $today_faucet + $today_video;

$target_day_config = null;
foreach ($days as $d) {
    if ($d['day_number'] == $next_day_number) {
        $target_day_config = $d;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_bonus'])) {
    header('Content-Type: application/json');
    try {
        if ($already_claimed_today) throw new Exception("You have already claimed your daily bonus today.");
        if (!$target_day_config) throw new Exception("No bonus configured for today.");

        $min_condition = floatval($target_day_config['min_condition_usdt']);
        $reward = floatval($target_day_config['reward_usdt']);

        if ($today_earned < $min_condition) throw new Exception("You have not met the minimum earning requirement for today.");

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid request. Please refresh the page.");
        }

        $pdo->beginTransaction();

        $ins = $pdo->prepare("INSERT IGNORE INTO daily_bonus_claims (user_id, day_number, reward_usdt, claim_date) VALUES (?, ?, ?, CURDATE())");
        $ins->execute([$user_id, $target_day_config['day_number'], $reward]);

        if ($ins->rowCount() === 0) {
            throw new Exception("You have already claimed your daily bonus today.");
        }

        $new_streak = $current_streak + 1;
        $upd = $pdo->prepare("UPDATE users SET balance = balance + ?, daily_bonus_streak = ? WHERE id = ?");
        $upd->execute([$reward, $new_streak, $user_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Successfully claimed +".rtrim(rtrim($reward, '0'), '.')." USDT!", 'reward' => $reward]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

include 'includes/header.php';

$page_data = [
    'csrfToken' => $_SESSION['csrf_token']
];
?>

<div class="relative z-10 pt-24 pb-12">
    <div class="container-custom">
        <div id="ad-container-general_top_ads" class="mb-8 flex justify-center empty:hidden w-full min-h-[90px]"></div>
        <div class="max-w-6xl mx-auto">

            <div class="mb-8">
                <div class="section-label">Rewards</div>
                <h1 class="text-3xl font-extrabold text-zinc-100 tracking-tight mt-2">Daily Bonus</h1>
            </div>

            <?php if (empty($days)): ?>
            <div class="bg-surface-card rounded-lg border border-white/10 p-12 text-center">
                <i class="fa-solid fa-ghost text-5xl text-zinc-600 mb-4 block"></i>
                <h3 class="text-xl font-bold text-zinc-100 mb-2">No Daily Bonuses Configured</h3>
                <p class="text-zinc-500">The administrator has not configured any daily bonus rewards yet.</p>
            </div>
            <?php else: ?>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7 gap-4 mb-10">
                <?php foreach ($days as $day):
                    $day_num = intval($day['day_number']);
                    $reward = rtrim(rtrim($day['reward_usdt'], '0'), '.');
                    $min_req = floatval($day['min_condition_usdt']);

                    $state = 'locked';
                    if ($already_claimed_today) {
                        if ($day_num <= $current_streak) $state = 'completed';
                    } else {
                        if ($day_num < $next_day_number) $state = 'completed';
                        else if ($day_num == $next_day_number) $state = 'current';
                    }

                    $bg_class = "bg-surface-card border-white/10";
                    $text_class = "text-zinc-300";
                    $icon_class = "text-zinc-600";
                    $icon = "fa-lock";

                    if ($state === 'completed') {
                        $bg_class = "bg-primary/10 border-primary/25";
                        $text_class = "text-primary";
                        $icon_class = "text-primary";
                        $icon = "fa-check-circle";
                    } else if ($state === 'current') {
                        $bg_class = "bg-primary/10 border-primary/40 ring-2 ring-primary ring-offset-2 ring-offset-surface";
                        $text_class = "text-primary";
                        $icon_class = "text-primary";
                        $icon = "fa-gift";
                    }
                ?>
                <div class="<?php echo $bg_class; ?> rounded-lg border p-4 text-center flex flex-col items-center justify-center transition-all relative overflow-hidden group">
                    <?php if ($state === 'current'): ?>
                        <div class="absolute -right-4 -top-4 w-12 h-12 bg-primary opacity-10 rounded-full blur-xl group-hover:opacity-20 transition-opacity"></div>
                    <?php endif; ?>

                    <p class="text-xs font-bold uppercase tracking-wider <?php echo ($state === 'completed' || $state === 'current') ? 'text-primary' : 'text-zinc-500'; ?> mb-2 font-mono">Day <?php echo $day_num; ?></p>
                    <i class="fa-solid <?php echo $icon; ?> text-2xl <?php echo $icon_class; ?> mb-3"></i>
                    <p class="font-extrabold <?php echo $text_class; ?> text-lg font-mono">+<?php echo $reward; ?></p>

                    <?php if ($min_req > 0 && $state !== 'completed'): ?>
                    <p class="text-[10px] text-zinc-500 mt-2 leading-tight border-t border-white/10 pt-2 font-mono">Req: <?php echo rtrim(rtrim($day['min_condition_usdt'], '0'), '.'); ?> USDT</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($already_claimed_today): ?>
            <div class="bg-primary/10 rounded-lg p-8 border border-primary/25 text-center">
                <div class="w-16 h-16 bg-primary text-zinc-950 rounded-lg flex items-center justify-center text-3xl mx-auto mb-4 shadow-lg shadow-primary/20">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h2 class="text-2xl font-bold text-zinc-100 mb-2">Come back tomorrow!</h2>
                <p class="text-zinc-400">You've already claimed your daily bonus for today. Keep up the great work and maintain your streak.</p>
            </div>
            <?php else: ?>
                <?php if ($target_day_config):
                    $req = floatval($target_day_config['min_condition_usdt']);
                    $progress = ($req > 0) ? min(100, ($today_earned / $req) * 100) : 100;
                    $can_claim = ($progress >= 100);
                ?>
                <div class="bg-surface-card rounded-lg border border-white/10 p-8 md:p-10 flex flex-col items-center text-center">
                    <h2 class="text-2xl font-bold text-zinc-100 mb-2">Day <?php echo $target_day_config['day_number']; ?> Reward</h2>
                    <p class="text-zinc-400 mb-8 max-w-lg">Claim your bonus of <strong class="text-primary font-mono">+<?php echo rtrim(rtrim($target_day_config['reward_usdt'], '0'), '.'); ?> USDT</strong>. <?php if ($req > 0) echo "You must earn at least " . rtrim(rtrim($target_day_config['min_condition_usdt'], '0'), '.') . " USDT today to unlock it."; ?></p>

                    <?php if ($req > 0): ?>
                    <div class="w-full max-w-md mb-8">
                        <div class="flex justify-between text-sm font-medium mb-2">
                            <span class="text-zinc-400">Progress</span>
                            <span class="text-primary font-mono"><?php echo number_format($progress, 1); ?>%</span>
                        </div>
                        <div class="w-full h-3 bg-white/10 rounded-full overflow-hidden">
                            <div class="h-full <?php echo $can_claim ? 'bg-primary' : 'bg-gradient-to-r from-primaryDeep to-primary'; ?> rounded-full transition-all duration-500" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <?php if (!$can_claim): ?>
                        <p class="text-xs text-zinc-500 mt-2 font-mono">Earn <?php echo number_format($req - $today_earned, 6); ?> more USDT today to unlock.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <button id="claim-btn" <?php echo $can_claim ? '' : 'disabled'; ?> class="w-full max-w-xs <?php echo $can_claim ? 'bg-primary hover:bg-primaryHover text-zinc-950 shadow-lg shadow-primary/20' : 'bg-white/5 border border-white/10 text-zinc-500 cursor-not-allowed'; ?> font-bold py-4 rounded-lg transition-all text-lg flex items-center justify-center gap-2">
                        <i class="fa-solid fa-gift"></i> Claim Bonus
                    </button>
                </div>
                <?php else: ?>
                <div class="bg-white/[0.03] rounded-lg p-8 border border-white/10 text-center text-zinc-500">
                    Next bonus day not configured.
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
    <div class="container-custom pb-8"><div id="ad-container-general_bottom_ads" class="flex justify-center empty:hidden w-full min-h-[90px]"></div></div>
</div>

<script>window.OW_PAGE_DATA = <?php echo json_encode($page_data); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/pages/user-daily-bonus.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
