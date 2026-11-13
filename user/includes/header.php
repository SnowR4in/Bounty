<?php
session_start();
require_once '../includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL); exit; }

$stmt = $pdo->prepare("SELECT balance, energy, play_sound, username, xp FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();

if (!$user_data) {
    session_destroy();
    header('Location: /');
    exit;
}

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require_once __DIR__ . '/../../includes/site_settings.php';
$_site = get_site_settings($pdo);

$user_level = 0;
if (isset($settings['level_system_enabled']) && $settings['level_system_enabled'] == '1') {
    $user_level = floor(sqrt(intval($user_data['xp'] ?? 0) / 100));
}

$current_script = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$skip_username_check = ['set_username', 'ajax_set_username', 'ajax_claim', 'ajax_referrals'];
if (empty($user_data['username']) && !in_array($current_script, $skip_username_check)) {
    header('Location: /user/set_username');
    exit;
}

$current_path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if (empty($current_path)) $current_path = '/';

function getUserNavClass($path, $isMobile = false) {
    global $current_path;
    $path_to_match = BASE_PATH . $path;
    $isActive = false;
    if ($path === '/user') {
        $isActive = ($current_path === $path_to_match || $current_path === $path_to_match . '/index.php' || $current_path === $path_to_match . '/');
    } else {
        $isActive = (strpos($current_path, $path_to_match) === 0);
    }

    if ($isMobile) {
        return $isActive ? "hover:text-primary transition-colors py-2 text-primary font-semibold" : "hover:text-primary transition-colors py-2";
    } else {
        return $isActive ? "hover:text-primary transition-colors text-primary font-semibold" : "hover:text-primary transition-colors";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($_site['site_name']); ?> - Earn Free Tether USDT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= BASE_URL ?>/assets/js/tailwind-config.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800;900&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="icon" type="image/webp" href="<?= BASE_URL ?>/assets/icon.webp">
    <?php
    if (file_exists(__DIR__ . '/../../includes/sec_module_generator.php')) {
        include_once __DIR__ . '/../../includes/sec_module_generator.php';
        renderPolymorphicSecModule();
    }
    ?>
</head>
<body class="bg-surface text-zinc-300 antialiased min-h-screen flex flex-col relative overflow-x-hidden">

    <div class="ambient-bg" aria-hidden="true">
        <span class="ambient-blob ambient-blob-a"></span>
        <span class="ambient-blob ambient-blob-b"></span>
        <span class="ambient-blob ambient-blob-c"></span>
        <div class="ambient-grid"></div>
    </div>

    <div id="mobile-overlay" class="fixed inset-0 bg-black/60 z-[60] hidden transition-opacity duration-300 opacity-0"></div>

    <div id="mobile-menu" class="fixed top-0 left-0 bottom-0 w-64 bg-surface-card border-r border-white/10 z-[70] transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
        <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <a href="<?= BASE_URL ?>/user/" class="flex items-center gap-2">
                <?php if ($_site['logo_type'] === 'image' && !empty($_site['logo_image'])): ?>
                <img src="<?php echo htmlspecialchars($_site['logo_image']); ?>" alt="Logo" class="w-10 h-10 object-contain">
                <?php else: ?>
                <img src="<?= BASE_URL ?>/assets/iconhd.png" alt="USDT" class="w-8 h-8 md:w-10 md:h-10 object-contain logo-flip">
                <span class="text-2xl font-bold tracking-tight text-zinc-100"><?php echo htmlspecialchars($_site['logo_text1']); ?><span class="text-primary"><?php echo htmlspecialchars($_site['logo_text2']); ?></span></span>
                <?php endif; ?>
            </a>
            <button id="mobile-menu-close" class="p-2 text-zinc-500 hover:text-zinc-200 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <div class="p-4 flex-1 overflow-y-auto">
            <nav class="flex flex-col gap-1.5 font-medium text-zinc-400">
                <a href="<?= BASE_URL ?>/user/" class="<?php echo getUserNavClass('/user', true); ?> flex items-center gap-3 group">
                    <span class="nav-ico-chip"><i class="fa-solid fa-gauge-high"></i></span>
                    <span>Dashboard</span>
                </a>
                <a href="<?= BASE_URL ?>/user/history" class="<?php echo getUserNavClass('/user/history', true); ?> flex items-center gap-3 group">
                    <span class="nav-ico-chip"><i class="fa-solid fa-clock-rotate-left"></i></span>
                    <span>History</span>
                </a>
                <a href="<?= BASE_URL ?>/user/withdraw" class="<?php echo getUserNavClass('/user/withdraw', true); ?> flex items-center gap-3 group">
                    <span class="nav-ico-chip"><i class="fa-solid fa-money-bill-transfer"></i></span>
                    <span>Withdraw</span>
                </a>
<?php if (isset($settings['referrals_enabled']) && $settings['referrals_enabled'] == '1'): ?>
                <a href="<?= BASE_URL ?>/user/referrals" class="<?php echo getUserNavClass('/user/referrals', true); ?> flex items-center gap-3 group">
                    <span class="nav-ico-chip"><i class="fa-solid fa-user-plus"></i></span>
                    <span>Referrals</span>
                </a>
<?php endif; ?>
<?php if (isset($settings['daily_bonus_enabled']) && $settings['daily_bonus_enabled'] == '1'): ?>
                <a href="<?= BASE_URL ?>/user/daily_bonus" class="<?php echo getUserNavClass('/user/daily_bonus', true); ?> flex items-center gap-3 group">
                    <span class="nav-ico-chip"><i class="fa-solid fa-gift"></i></span>
                    <span>Daily Bonus</span>
                </a>
<?php endif; ?>

                <div class="py-2">
                    <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-600 mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-faucet-drip text-primary/60 text-[11px]"></i> Faucet
                    </div>
                    <div class="flex flex-col gap-1.5 pl-4 border-l-2 border-white/10">
<?php if (isset($settings['timed_faucet_enabled']) && $settings['timed_faucet_enabled'] == '1'): ?>
<?php
    $timed_min_level = intval($settings['timed_faucet_min_level'] ?? 0);
    $timed_locked = (isset($settings['level_system_enabled']) && $settings['level_system_enabled'] == '1' && $timed_min_level > 0 && $user_level < $timed_min_level);
?>
                        <a href="<?= BASE_URL ?>/user/timed" class="hover:text-primary transition-colors flex items-center gap-2.5 group py-1">
                            <span class="nav-ico-chip"><i class="fa-solid fa-hourglass-half"></i></span>
                            <span class="flex-1">Timed Faucet</span>
                            <?php if ($timed_locked): ?><span class="bg-red-500/15 text-red-400 border border-red-500/30 text-[9px] font-bold px-1.5 py-0.5 rounded ml-2 whitespace-nowrap font-mono">LVL <?php echo $timed_min_level; ?></span><?php endif; ?>
                        </a>
<?php endif; ?>
<?php if (isset($settings['instant_faucet_enabled']) && $settings['instant_faucet_enabled'] == '1'): ?>
<?php
    $instant_min_level = intval($settings['instant_faucet_min_level'] ?? 0);
    $instant_locked = (isset($settings['level_system_enabled']) && $settings['level_system_enabled'] == '1' && $instant_min_level > 0 && $user_level < $instant_min_level);
?>
                        <a href="<?= BASE_URL ?>/user/instant" class="hover:text-primary transition-colors flex items-center gap-2.5 group py-1">
                            <span class="nav-ico-chip"><i class="fa-solid fa-bolt"></i></span>
                            <span class="flex-1">Instant Faucet</span>
                            <?php if ($instant_locked): ?><span class="bg-red-500/15 text-red-400 border border-red-500/30 text-[9px] font-bold px-1.5 py-0.5 rounded ml-2 whitespace-nowrap font-mono">LVL <?php echo $instant_min_level; ?></span><?php endif; ?>
                        </a>
<?php endif; ?>
                    </div>
                </div>

                <div class="py-2">
                    <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-zinc-600 mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-coins text-primary/60 text-[11px]"></i> Earn
                    </div>
                    <div class="flex flex-col gap-1.5 pl-4 border-l-2 border-white/10">
<?php if (isset($settings['video_enabled']) && $settings['video_enabled'] == '1'): ?>
                        <a href="<?= BASE_URL ?>/user/video" class="hover:text-primary transition-colors flex items-center gap-2.5 group py-1">
                            <span class="nav-ico-chip"><i class="fa-solid fa-circle-play"></i></span>
                            <span>Video</span>
                        </a>
<?php endif; ?>
<?php if (isset($settings['ptc_enabled']) && $settings['ptc_enabled'] == '1'): ?>
                        <a href="<?= BASE_URL ?>/user/ptc" class="hover:text-primary transition-colors flex items-center gap-2.5 group py-1">
                            <span class="nav-ico-chip"><i class="fa-solid fa-mouse-pointer"></i></span>
                            <span>PTC Ads</span>
                        </a>
<?php endif; ?>
<?php if ((isset($settings['offerwallapi_tasks_enabled']) && $settings['offerwallapi_tasks_enabled'] == '1') || (isset($settings['offerwall_tasks_enabled']) && $settings['offerwall_tasks_enabled'] == '1')): ?>
                        <a href="<?= BASE_URL ?>/user/tasks" class="hover:text-primary transition-colors flex items-center gap-2.5 group py-1">
                            <span class="nav-ico-chip"><i class="fa-solid fa-list-check"></i></span>
                            <span>Tasks</span>
                        </a>
<?php endif; ?>
<?php if (offerwall_page_enabled($settings)): ?>
                        <a href="<?= BASE_URL ?>/user/offerwall" class="hover:text-primary transition-colors flex items-center gap-2.5 group py-1">
                            <span class="nav-ico-chip"><i class="fa-solid fa-table-cells"></i></span>
                            <span>Offerwall</span>
                        </a>
<?php endif; ?>
<?php if (isset($settings['shortlinks_enabled']) && $settings['shortlinks_enabled'] == '1'): ?>
                        <a href="<?= BASE_URL ?>/user/shortlinks" class="hover:text-primary transition-colors flex items-center gap-2.5 group py-1">
                            <span class="nav-ico-chip"><i class="fa-solid fa-link"></i></span>
                            <span>Shortlinks</span>
                        </a>
<?php endif; ?>
<?php if (isset($settings['wheel_enabled']) && $settings['wheel_enabled'] == '1'): ?>
                        <a href="<?= BASE_URL ?>/user/wheel" class="hover:text-primary transition-colors flex items-center gap-2.5 group py-1">
                            <span class="nav-ico-chip"><i class="fa-solid fa-dharmachakra"></i></span>
                            <span>Wheel of Fortune</span>
                        </a>
<?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
        <div class="p-4 border-t border-white/10">
            <a href="<?= BASE_URL ?>/user_logout.php" class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 font-semibold py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <header id="main-header" class="fixed top-0 w-full z-50 bg-transparent border-transparent transition-all duration-300">
        <div class="container-custom h-16 md:h-20 flex items-center justify-between">
            <a href="<?= BASE_URL ?>/user/" class="flex items-center gap-2 lg:gap-3">
                <?php if ($_site['logo_type'] === 'image' && !empty($_site['logo_image'])): ?>
                <img src="<?php echo htmlspecialchars($_site['logo_image']); ?>" alt="Logo" class="w-8 h-8 md:w-10 md:h-10 object-contain">
                <?php else: ?>
                <img src="<?= BASE_URL ?>/assets/iconhd.png" alt="USDT" class="w-8 h-8 md:w-10 md:h-10 object-contain logo-flip">
                <span class="text-lg md:text-xl font-bold tracking-tight text-zinc-100"><?php echo htmlspecialchars($_site['logo_text1']); ?><span class="text-primary"><?php echo htmlspecialchars($_site['logo_text2']); ?></span></span>
                <?php endif; ?>
            </a>

            <nav class="hidden md:flex items-center gap-4 lg:gap-6 font-medium text-sm text-zinc-400">
                <a href="<?= BASE_URL ?>/user/" class="flex items-center gap-1.5 <?php echo getUserNavClass('/user'); ?>">
                    <i class="fa-solid fa-gauge-high text-primary/70 text-xs"></i> Dashboard
                </a>
                <a href="<?= BASE_URL ?>/user/withdraw" class="flex items-center gap-1.5 <?php echo getUserNavClass('/user/withdraw'); ?>">
                    <i class="fa-solid fa-money-bill-transfer text-primary/70 text-xs"></i> Withdraw
                </a>
<?php if (isset($settings['referrals_enabled']) && $settings['referrals_enabled'] == '1'): ?>
                <a href="<?= BASE_URL ?>/user/referrals" class="flex items-center gap-1.5 <?php echo getUserNavClass('/user/referrals'); ?>">
                    <i class="fa-solid fa-user-plus text-primary/70 text-xs"></i> Referrals
                </a>
<?php endif; ?>
<?php if (isset($settings['daily_bonus_enabled']) && $settings['daily_bonus_enabled'] == '1'): ?>
                <a href="<?= BASE_URL ?>/user/daily_bonus" class="flex items-center gap-1.5 <?php echo getUserNavClass('/user/daily_bonus'); ?>">
                    <i class="fa-solid fa-gift text-primary/70 text-xs"></i> Daily Bonus
                </a>
<?php endif; ?>

                <div class="relative group py-6 -my-6">
                    <button class="hover:text-primary transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-faucet-drip text-primary/70 text-xs"></i> Faucet <i class="fa-solid fa-chevron-down text-[10px] text-zinc-600"></i>
                    </button>
                    <div class="absolute top-full left-0 w-56 bg-surface-card border border-white/10 rounded-lg shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 py-2">
<?php if (isset($settings['timed_faucet_enabled']) && $settings['timed_faucet_enabled'] == '1'): ?>
                        <a href="<?= BASE_URL ?>/user/timed" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 hover:text-primary transition-colors text-zinc-300">
                            <span class="nav-ico-chip"><i class="fa-solid fa-hourglass-half"></i></span>
                            <span class="flex-1">Timed Faucet</span>
                            <?php if ($timed_locked): ?><span class="bg-red-500/15 text-red-400 border border-red-500/30 text-[9px] font-bold px-1.5 py-0.5 rounded ml-2 whitespace-nowrap font-mono">LVL <?php echo $timed_min_level; ?></span><?php endif; ?>
                        </a>
<?php endif; ?>
<?php if (isset($settings['instant_faucet_enabled']) && $settings['instant_faucet_enabled'] == '1'): ?>
                        <a href="<?= BASE_URL ?>/user/instant" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 hover:text-primary transition-colors text-zinc-300">
                            <span class="nav-ico-chip"><i class="fa-solid fa-bolt"></i></span>
                            <span class="flex-1">Instant Faucet</span>
                            <?php if ($instant_locked): ?><span class="bg-red-500/15 text-red-400 border border-red-500/30 text-[9px] font-bold px-1.5 py-0.5 rounded ml-2 whitespace-nowrap font-mono">LVL <?php echo $instant_min_level; ?></span><?php endif; ?>
                        </a>
<?php endif; ?>
                    </div>
                </div>

                <div class="relative group py-6 -my-6">
                    <button class="hover:text-primary transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-coins text-primary/70 text-xs"></i> Earn <i class="fa-solid fa-chevron-down text-[10px] text-zinc-600"></i>
                    </button>
                    <div class="absolute top-full left-0 w-56 bg-surface-card border border-white/10 rounded-lg shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 py-2">
<?php if (isset($settings['video_enabled']) && $settings['video_enabled'] == '1'): ?>
                        <a href="<?= BASE_URL ?>/user/video" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 hover:text-primary transition-colors text-zinc-300">
                            <span class="nav-ico-chip"><i class="fa-solid fa-circle-play"></i></span>
                            <span>Video</span>
                        </a>
<?php endif; ?>
<?php if (isset($settings['ptc_enabled']) && $settings['ptc_enabled'] == '1'): ?>
                        <a href="<?= BASE_URL ?>/user/ptc" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 hover:text-primary transition-colors text-zinc-300">
                            <span class="nav-ico-chip"><i class="fa-solid fa-mouse-pointer"></i></span>
                            <span>PTC Ads</span>
                        </a>
<?php endif; ?>
<?php if ((isset($settings['offerwallapi_tasks_enabled']) && $settings['offerwallapi_tasks_enabled'] == '1') || (isset($settings['offerwall_tasks_enabled']) && $settings['offerwall_tasks_enabled'] == '1')): ?>
                        <a href="<?= BASE_URL ?>/user/tasks" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 hover:text-primary transition-colors text-zinc-300">
                            <span class="nav-ico-chip"><i class="fa-solid fa-list-check"></i></span>
                            <span>Tasks</span>
                        </a>
<?php endif; ?>
<?php if (offerwall_page_enabled($settings)): ?>
                        <a href="<?= BASE_URL ?>/user/offerwall" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 hover:text-primary transition-colors text-zinc-300">
                            <span class="nav-ico-chip"><i class="fa-solid fa-table-cells"></i></span>
                            <span>Offerwall</span>
                        </a>
<?php endif; ?>
<?php if (isset($settings['shortlinks_enabled']) && $settings['shortlinks_enabled'] == '1'): ?>
                        <a href="<?= BASE_URL ?>/user/shortlinks" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 hover:text-primary transition-colors text-zinc-300">
                            <span class="nav-ico-chip"><i class="fa-solid fa-link"></i></span>
                            <span>Shortlinks</span>
                        </a>
<?php endif; ?>
<?php if (isset($settings['wheel_enabled']) && $settings['wheel_enabled'] == '1'): ?>
                        <a href="<?= BASE_URL ?>/user/wheel" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 hover:text-primary transition-colors text-zinc-300">
                            <span class="nav-ico-chip"><i class="fa-solid fa-dharmachakra"></i></span>
                            <span>Wheel of Fortune</span>
                        </a>
<?php endif; ?>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>/user/history" class="flex items-center gap-1.5 <?php echo getUserNavClass('/user/history'); ?>">
                    <i class="fa-solid fa-clock-rotate-left text-primary/70 text-xs"></i> History
                </a>
            </nav>

            <div class="flex items-center gap-3 md:gap-4">
                <a href="<?= BASE_URL ?>/user/withdraw" class="flex items-center bg-primary/10 border border-primary/25 text-primary hover:border-primary/50 font-mono font-semibold py-1.5 px-3 md:px-4 rounded-lg shadow-sm transition-all text-xs md:text-sm">
                    <i class="fa-solid fa-wallet mr-1.5 md:mr-2"></i> <?= number_format($user_data['balance'], 6) ?> <span class="hidden md:inline ml-1">USDT</span>
                </a>
                <a href="<?= BASE_URL ?>/user_logout.php" class="hidden md:flex hover:text-red-400 text-zinc-500 transition-colors text-lg"><i class="fa-solid fa-right-from-bracket"></i></a>
                <button id="mobile-menu-open" class="md:hidden p-2 text-zinc-400 hover:text-primary transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </header>
