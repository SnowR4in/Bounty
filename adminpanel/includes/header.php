<?php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/site_settings.php';

if (!isset($_SESSION['admin_logged_in'])) { header('Location: ' . ADMIN_ROUTE . '/login'); exit; }
$_site = get_site_settings($pdo);

$current_path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if (empty($current_path)) $current_path = '/';

function getAdminNavClass($path) {
    global $current_path;
    $isActive = false;
    if ($path === ADMIN_ROUTE) {
        $isActive = ($current_path === ADMIN_ROUTE || $current_path === ADMIN_ROUTE . '/index.php');
    } else {
        $isActive = (strpos($current_path, $path) === 0);
    }
    return $isActive
        ? "flex items-center gap-3 px-4 py-2.5 rounded-lg bg-primary/10 border border-primary/25 text-primary font-semibold transition-colors"
        : "flex items-center gap-3 px-4 py-2.5 rounded-lg text-zinc-400 hover:bg-white/5 hover:text-primary transition-colors";
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($_site['site_name']); ?> - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= BASE_URL ?>/assets/js/tailwind-config.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800;900&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="icon" type="image/webp" href="<?= BASE_URL ?>/assets/icon.webp">
</head>
<body class="bg-surface text-zinc-300 antialiased min-h-screen flex relative overflow-x-hidden">

    <div class="ambient-bg" aria-hidden="true">
        <span class="ambient-blob ambient-blob-a"></span>
        <span class="ambient-blob ambient-blob-b"></span>
        <span class="ambient-blob ambient-blob-c"></span>
        <div class="ambient-grid"></div>
    </div>

    <div id="mobile-overlay" class="fixed inset-0 bg-black/60 z-[60] hidden transition-opacity duration-300 opacity-0 lg:hidden"></div>

    <aside id="admin-sidebar" class="fixed top-0 left-0 bottom-0 w-64 bg-surface-card border-r border-white/10 z-[70] transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col">
        <div class="h-16 md:h-20 border-b border-white/10 flex items-center justify-between px-6 shrink-0">
            <a href="#" class="flex items-center gap-2">
                <?php if ($_site['logo_type'] === 'image' && !empty($_site['logo_image'])): ?>
                <img src="<?php echo htmlspecialchars($_site['logo_image']); ?>" alt="Logo" class="w-10 h-10 object-contain">
                <?php else: ?>
                <img src="<?= BASE_URL ?>/assets/iconhd.png" alt="USDT" class="w-8 h-8 md:w-10 md:h-10 object-contain logo-flip">
                <span class="text-2xl font-bold tracking-tight text-zinc-100"><?php echo htmlspecialchars($_site['logo_text1']); ?><span class="text-primary"><?php echo htmlspecialchars($_site['logo_text2']); ?></span></span>
                <?php endif; ?>
            </a>
            <button id="sidebar-close" class="lg:hidden p-2 text-zinc-500 hover:text-zinc-200 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="p-4 flex-1 overflow-y-auto">
            <div class="space-y-1.5">
                <a href="<?php echo ADMIN_ROUTE; ?>/" class="<?php echo getAdminNavClass(ADMIN_ROUTE); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-chart-pie text-lg"></i></div>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/users" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/users'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-users text-lg"></i></div>
                    <span class="font-medium">Users</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/withdrawals" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/withdrawals'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-money-bill-transfer text-lg"></i></div>
                    <span class="font-medium">Withdrawals</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/statistics" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/statistics'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-chart-line text-lg"></i></div>
                    <span class="font-medium">Statistics</span>
                </a>
            </div>

            <div class="space-y-1.5 mt-8">
                <h4 class="px-4 font-mono text-[10px] font-semibold text-zinc-600 uppercase tracking-[0.2em] mb-3">Earning Modules</h4>
                <a href="<?php echo ADMIN_ROUTE; ?>/faucet_settings" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/faucet_settings'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-droplet text-lg"></i></div>
                    <span class="font-medium">Faucet</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/video_settings" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/video_settings'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-video text-lg"></i></div>
                    <span class="font-medium">Video</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/ptc_settings" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/ptc_settings'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-mouse-pointer text-lg"></i></div>
                    <span class="font-medium">PTC Ads</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/offerwall_settings" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/offerwall_settings'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-table-cells text-lg"></i></div>
                    <span class="font-medium">Offerwall</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/ou_offers" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/ou_offers'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-clipboard-list text-lg"></i></div>
                    <span class="font-medium">Survey Offers</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/tasks_settings" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/tasks_settings'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-list-ul text-lg"></i></div>
                    <span class="font-medium">Tasks</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/shortlink_settings" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/shortlink_settings'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-link text-lg"></i></div>
                    <span class="font-medium">Shortlinks</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/daily_bonus" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/daily_bonus'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-calendar-check text-lg"></i></div>
                    <span class="font-medium">Daily Bonus</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/wheel_settings" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/wheel_settings'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-dharmachakra text-lg"></i></div>
                    <span class="font-medium">Wheel of Fortune</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/level_settings" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/level_settings'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-arrow-up-right-dots text-lg"></i></div>
                    <span class="font-medium">Levels & Lock</span>
                </a>
            </div>

            <div class="space-y-1.5 mt-8 border-t border-white/10 pt-6">
                <h4 class="px-4 font-mono text-[10px] font-semibold text-zinc-600 uppercase tracking-[0.2em] mb-3">System</h4>
                <a href="<?php echo ADMIN_ROUTE; ?>/pages" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/pages'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-file-lines text-lg"></i></div>
                    <span class="font-medium">Custom Pages</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/general_settings" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/general_settings'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-sliders text-lg"></i></div>
                    <span class="font-medium">General Settings</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/security" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/security'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-shield-halved text-lg"></i></div>
                    <span class="font-medium">Security</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/ads_settings" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/ads_settings'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-rectangle-ad text-lg"></i></div>
                    <span class="font-medium">Site Ads</span>
                </a>
                <a href="<?php echo ADMIN_ROUTE; ?>/admins" class="<?php echo getAdminNavClass(ADMIN_ROUTE . '/admins'); ?>">
                    <div class="w-8 flex justify-center"><i class="fa-solid fa-user-tie text-lg"></i></div>
                    <span class="font-medium">Admins</span>
                </a>
            </div>
        </div>

        <div class="p-4 border-t border-white/10 shrink-0">
            <a href="<?= BASE_URL ?>/adminpanel/logout.php" class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 font-semibold py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-h-screen relative z-10 lg:ml-64 transition-all duration-300 w-full">
        <header class="h-16 md:h-20 bg-surface/90 backdrop-blur-md border-b border-white/10 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-40 w-full">
            <div class="flex items-center gap-4">
                <button id="sidebar-open" class="lg:hidden p-2 text-zinc-400 hover:text-primary transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h1 class="text-lg md:text-xl font-bold text-zinc-100 hidden sm:block">Admin Overview</h1>
            </div>

            <div class="flex items-center gap-3 md:gap-5">
                <div class="flex items-center gap-3">
                    <div class="hidden md:block text-right">
                        <p class="text-sm font-bold text-zinc-100 leading-none">Super Admin</p>
                        <p class="text-xs text-primary font-medium mt-1 font-mono">Management</p>
                    </div>
                    <div class="w-10 h-10 bg-gradient-to-tr from-primaryDeep to-primary text-zinc-950 rounded-lg flex items-center justify-center text-lg shadow-glow-sm">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </header>
