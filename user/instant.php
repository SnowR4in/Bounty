<?php
include 'includes/header.php';

if (!isset($settings['instant_faucet_enabled']) || $settings['instant_faucet_enabled'] != '1') {
    header('Location: /user/');
    exit;
}

$reward = floatval($settings['instant_faucet_reward'] ?? 0.001);
$energy = intval($settings['instant_faucet_energy'] ?? 1);
$cooldown = intval($settings['instant_faucet_cooldown'] ?? 10);
$time_text = $cooldown >= 60 ? floor($cooldown / 60) . ' Minutes' : $cooldown . ' Seconds';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare("SELECT UNIX_TIMESTAMP(claimed_at) FROM faucet_claims WHERE user_id = ? AND faucet_type = 'instant' ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$last_claim = $stmt->fetchColumn();

$time_remaining = 0;
if ($last_claim) {
    $elapsed = time() - $last_claim;
    if ($elapsed < $cooldown) {
        $time_remaining = $cooldown - $elapsed;
    }
}

$captcha_type = $settings['instant_faucet_captcha'] ?? 'none';

$user_level = 0;
$min_level = intval($settings['instant_faucet_min_level'] ?? 0);
$is_locked = false;
$earn_text = "";
if (isset($settings['level_system_enabled']) && $settings['level_system_enabled'] == '1') {
    $user_level = floor(sqrt(intval($user_data['xp'] ?? 0) / 100));
    if ($min_level > 0 && $user_level < $min_level) {
        $is_locked = true;

        $active_modules = [];
        if (isset($settings['video_enabled']) && $settings['video_enabled'] == '1') $active_modules[] = '<a href="' . BASE_URL . '/user/video" class="text-primary hover:underline font-semibold">Videos</a>';
        if (isset($settings['ptc_enabled']) && $settings['ptc_enabled'] == '1') $active_modules[] = '<a href="' . BASE_URL . '/user/ptc" class="text-primary hover:underline font-semibold">PTC ads</a>';
        if (isset($settings['shortlinks_enabled']) && $settings['shortlinks_enabled'] == '1') $active_modules[] = '<a href="' . BASE_URL . '/user/shortlinks" class="text-primary hover:underline font-semibold">Shortlinks</a>';
        if (isset($settings['offerwallapi_tasks_enabled']) && $settings['offerwallapi_tasks_enabled'] == '1') $active_modules[] = '<a href="' . BASE_URL . '/user/tasks" class="text-primary hover:underline font-semibold">Tasks</a>';
        if (offerwall_page_enabled($settings)) $active_modules[] = '<a href="' . BASE_URL . '/user/offerwall" class="text-primary hover:underline font-semibold">Offerwall</a>';

        if (count($active_modules) > 0) {
            if (count($active_modules) == 1) {
                $earn_text = "Complete " . $active_modules[0] . " to earn XP and level up!";
            } elseif (count($active_modules) == 2) {
                $earn_text = "Complete " . $active_modules[0] . " and " . $active_modules[1] . " to earn XP and level up!";
            } else {
                $last = array_pop($active_modules);
                $earn_text = "Complete " . implode(", ", $active_modules) . ", and " . $last . " to earn XP and level up!";
            }
        }
    }
}

$page_data = [
    'timeRemaining' => (int)$time_remaining,
    'csrfToken' => $_SESSION['csrf_token'],
    'ads' => null
];

if (isset($settings['faucet_ads_enabled']) && $settings['faucet_ads_enabled'] == '1') {
    $ads_file = __DIR__ . '/../includes/ads.json';
    $faucet_ads = ['faucet_top_ads' => [], 'faucet_left_ads' => [], 'faucet_right_ads' => [], 'faucet_captcha_ads' => [], 'faucet_bottom_ads' => []];
    if (file_exists($ads_file)) {
        $raw_ads = json_decode(file_get_contents($ads_file), true);
        $faucet_ads['faucet_top_ads'] = $raw_ads['faucet_top_ads'] ?? [];
        $faucet_ads['faucet_left_ads'] = $raw_ads['faucet_left_ads'] ?? [];
        $faucet_ads['faucet_right_ads'] = $raw_ads['faucet_right_ads'] ?? [];
        $faucet_ads['faucet_captcha_ads'] = $raw_ads['faucet_captcha_ads'] ?? [];
        $faucet_ads['faucet_bottom_ads'] = $raw_ads['faucet_bottom_ads'] ?? [];
    }
    $page_data['ads'] = $faucet_ads;
}
?>

<main class="flex-1 relative z-10">
    <section class="pt-20 md:pt-28 pb-8 md:pb-12">
        <div class="container-custom relative z-10">
            <div id="ad-container-faucet_top_ads" class="flex justify-center empty:hidden w-full min-h-[90px] mb-8"></div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 items-center max-w-6xl mx-auto w-full">
                <div class="hidden xl:flex justify-end w-full">
                    <div id="ad-container-faucet_left_ads" class="empty:hidden min-h-[90px]"></div>
                </div>

                <div class="max-w-2xl w-full mx-auto">
                <div class="bg-white/[0.02] border border-primary/25 p-6 md:p-8 rounded-lg text-center mb-8 relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 text-primary/10 text-9xl"><i class="fa-solid fa-bolt-lightning"></i></div>
                    <p class="text-lg text-zinc-300 font-medium relative z-10">
                        Earn <span class="font-bold text-primary font-mono"><?php echo number_format($reward, 6); ?> USDT</span> each <?php echo $time_text; ?>
                        <?php if ($energy > 0): ?>
                            + <span class="font-bold font-mono"><?php echo $energy; ?> Energy Points</span>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="text-center" id="faucet-container">
                    <button
                        id="bot-honeypot-btn"
                        type="button"
                        class="opacity-0 absolute -z-10"
                        style="left: -9999px;"
                        aria-hidden="true"
                        tabindex="-1"
                    >
                        Claim Bonus Instantly
                    </button>

                    <?php if ($is_locked): ?>
                        <div id="locked-state" class="py-8">
                            <div class="w-24 h-24 bg-red-500/10 border border-red-500/20 text-red-400 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-zinc-100 mb-4">Faucet Locked</h2>
                            <p class="text-zinc-400 mb-8 max-w-md mx-auto">This faucet activates when you reach Level <?php echo $min_level; ?>. <?php echo $earn_text; ?></p>
                            <a href="<?= BASE_URL ?>/user/" class="inline-block bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-8 rounded-lg transition-colors hover:-translate-y-1">
                                Earn XP Now
                            </a>
                        </div>
                    <?php elseif ($time_remaining > 0): ?>
                        <div id="wait-state">
                            <h2 class="text-xl font-bold text-zinc-100 mb-2">You need to wait</h2>
                            <div class="text-5xl font-mono font-bold text-primary mb-6" id="countdown-display">
                                <?php
                                    $m = floor($time_remaining / 60);
                                    $s = $time_remaining % 60;
                                    echo sprintf("%02d:%02d", $m, $s);
                                ?>
                            </div>

                            <label class="flex items-center justify-center gap-2 text-sm text-zinc-400 mb-6 cursor-pointer hover:text-zinc-200 transition-colors">
                                <input type="checkbox" id="sound-toggle" <?php echo $user_data['play_sound'] ? 'checked' : ''; ?> class="w-4 h-4 accent-primary cursor-pointer">
                                <i class="fa-solid fa-volume-high"></i> Play a sound when the timer finishes
                            </label>

                            <a href="<?= BASE_URL ?>/user/" class="inline-block bg-white/5 hover:bg-white/10 text-zinc-200 border border-white/10 font-semibold py-3 px-8 rounded-lg transition-colors">
                                Back to Dashboard
                            </a>
                        </div>
                    <?php else: ?>
                        <div id="ready-state">
                            <?php if ($captcha_type !== 'none'): ?>
                                <?php echo captcha_render($captcha_type, $settings); ?>

                                <div id="ad-container-faucet_captcha_ads" class="flex justify-center empty:hidden w-full min-h-[90px] mb-6"></div>

                                <button id="claim-btn" class="w-full sm:w-auto bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-4 px-12 rounded-lg text-lg transition-all hover:-translate-y-1">
                                    VERIFY &amp; CLAIM
                                </button>
                            <?php else: ?>
                                <div class="w-24 h-24 bg-primary/10 border border-primary/25 text-primary rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                                    <i class="fa-solid fa-bolt-lightning"></i>
                                </div>
                                <h2 class="text-2xl font-bold text-zinc-100 mb-6">Ready to Claim!</h2>

                                <div id="ad-container-faucet_captcha_ads" class="flex justify-center empty:hidden w-full min-h-[90px] mb-6"></div>

                                <button id="claim-btn" class="w-full sm:w-auto bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-4 px-12 rounded-lg text-lg transition-all hover:-translate-y-1">
                                    CLAIM NOW
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
                </div>

                <div class="hidden xl:flex justify-start w-full">
                    <div id="ad-container-faucet_right_ads" class="empty:hidden min-h-[90px]"></div>
                </div>
            </div>

            <div id="ad-container-faucet_bottom_ads" class="flex justify-center empty:hidden w-full min-h-[90px] mt-8"></div>
        </div>
    </section>
</main>

<audio id="notify-sound" src="https://cdn.pixabay.com/download/audio/2021/08/04/audio_0625c1539c.mp3?filename=success-1-6297.mp3" preload="auto"></audio>

<script>window.OW_PAGE_DATA = <?php echo json_encode($page_data); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/pages/user-instant.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
