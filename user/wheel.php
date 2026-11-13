<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'wheel_enabled'");
$wheel_enabled = $stmt->fetchColumn();

if ($wheel_enabled != '1') {
    header('Location: /user/');
    exit;
}

$_SESSION['wheel_token'] = bin2hex(random_bytes(32));

$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'wheel_energy_cost'");
$energy_cost = intval($stmt->fetchColumn() ?: 10);

$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'wheel_captcha'");
$wheel_captcha = $stmt->fetchColumn() ?: 'none';

$stmt = $pdo->query("SELECT * FROM wheel_segments ORDER BY display_order ASC");
$db_segments = $stmt->fetchAll();

$js_segments = [];
foreach ($db_segments as $seg) {
    if ($seg['reward_type'] === 'USDT') {
        $label = ($seg['reward_value'] > 0) ? rtrim(rtrim(sprintf('%.6f', $seg['reward_value']), '0'), '.') . ' USDT' : 'LOSE';
    } else {
        $label = ($seg['reward_value'] > 0) ? '+' . intval($seg['reward_value']) . ' Energy' : 'LOSE';
    }

    $hex = str_replace('#', '', $seg['color']);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    $textColor = ($yiq >= 128) ? '#000000' : '#ffffff';

    $js_segments[] = [
        'id' => $seg['id'],
        'text' => $label,
        'color' => $seg['color'],
        'textCol' => $textColor,
        'weight' => floatval($seg['win_chance'])
    ];
}

$_page_title = 'Wheel of Fortune';
require_once 'includes/header.php';

$page_data = [
    'segments' => $js_segments,
    'energyCost' => $energy_cost,
    'userEnergy' => intval($user_data['energy'] ?? 0),
    'wheelToken' => $_SESSION['wheel_token'],
    'wheelCaptchaType' => $wheel_captcha,
    'ads' => null
];

if (isset($settings['wheel_ads_enabled']) && $settings['wheel_ads_enabled'] == '1') {
    $ads_file = __DIR__ . '/../includes/ads.json';
    $wheel_ads = ['wheel_top_ads' => [], 'wheel_left_ads' => [], 'wheel_right_ads' => [], 'wheel_bottom_ads' => []];
    if (file_exists($ads_file)) {
        $raw_ads = json_decode(file_get_contents($ads_file), true);
        $wheel_ads['wheel_top_ads'] = $raw_ads['wheel_top_ads'] ?? [];
        $wheel_ads['wheel_left_ads'] = $raw_ads['wheel_left_ads'] ?? [];
        $wheel_ads['wheel_right_ads'] = $raw_ads['wheel_right_ads'] ?? [];
        $wheel_ads['wheel_bottom_ads'] = $raw_ads['wheel_bottom_ads'] ?? [];
    }
    $page_data['ads'] = $wheel_ads;
}
?>

<main class="flex-1 relative z-10">
    <section class="pt-20 md:pt-28 pb-8 md:pb-12">
        <div class="container-custom relative z-10">
            <div id="ad-container-wheel_top_ads" class="flex justify-center empty:hidden w-full min-h-[90px] mb-8"></div>

            <div class="flex flex-wrap gap-4 mb-8 max-w-[600px] mx-auto justify-center">
        <div class="bg-surface-card border border-white/10 rounded-lg px-6 py-3 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center">
                <i class="fa-solid fa-bolt text-lg"></i>
            </div>
            <div>
                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider font-mono">Your Energy</div>
                <div class="text-lg font-extrabold text-zinc-100 font-mono" id="userEnergyDisplay"><?php echo number_format($user_data['energy'] ?? 0); ?></div>
            </div>
        </div>

        <div class="bg-surface-card border border-white/10 rounded-lg px-6 py-3 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 text-zinc-300 flex items-center justify-center">
                <i class="fa-solid fa-coins text-lg"></i>
            </div>
            <div>
                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider font-mono">Cost Per Spin</div>
                <div class="text-lg font-extrabold text-zinc-100 font-mono"><?php echo number_format($energy_cost); ?> Energy</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 items-center max-w-6xl mx-auto">
        <div class="hidden xl:flex justify-end w-full">
            <div id="ad-container-wheel_left_ads" class="empty:hidden min-h-[90px]"></div>
        </div>

        <div class="wheel-wrapper w-full mx-auto">
            <div class="game-container" id="gameContainer">
                <div class="pointer-container" id="pointer">
                    <div class="pointer"></div>
                </div>
                <div id="floatingTextContainer"></div>
                <canvas id="wheelCanvas"></canvas>
                <button class="spin-btn" id="spinBtn" type="button">SPIN</button>
            </div>
        </div>

        <div class="hidden xl:flex justify-start w-full">
            <div id="ad-container-wheel_right_ads" class="empty:hidden min-h-[90px]"></div>
        </div>
    </div>

    <div id="ad-container-wheel_bottom_ads" class="flex justify-center empty:hidden w-full min-h-[90px] mt-8"></div>
        </div>
    </section>
</main>

<div class="modal-overlay" id="modal">
  <div class="modal-content">
    <div class="modal-icon" id="modalIcon">🎉</div>
    <h2 id="modalTitle">WINNER!</h2>
    <p id="winnerMsg">Prize</p>
    <button class="claim-again-btn" id="modalClaimBtn" type="button">Claim Again</button>
  </div>
</div>

<?php if ($wheel_captcha === 'google' || $wheel_captcha === 'turnstile'): ?>
<div class="modal-overlay" id="captchaModal">
  <div class="modal-content">
    <h2 class="mb-4">Security Check</h2>
    <p class="text-sm text-zinc-400 mb-4">Please solve the captcha to spin the wheel.</p>

    <?php echo captcha_render($wheel_captcha, $settings); ?>

    <div class="flex gap-2">
        <button class="bg-white/5 hover:bg-white/10 text-zinc-200 border border-white/10 font-bold py-3 px-4 rounded-lg flex-1 transition-colors" data-captcha-modal-close type="button">Cancel</button>
        <button class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-4 rounded-lg flex-1 transition-colors" id="captchaSubmitBtn" type="button">Confirm Spin</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>window.OW_PAGE_DATA = <?php echo json_encode($page_data); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/pages/user-wheel.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>
