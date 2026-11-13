<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/site_settings.php';
$_site = get_site_settings($pdo);
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
    if (file_exists(__DIR__ . '/sec_module_generator.php')) {
        include_once __DIR__ . '/sec_module_generator.php';
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
            <a href="<?= BASE_URL ?>/" class="flex items-center gap-2">
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
            <nav class="flex flex-col gap-2 font-medium text-zinc-400">
                <?php if (isset($_site['video_enabled']) && $_site['video_enabled'] == '1'): ?>
                <a href="#video" class="hover:text-primary transition-colors py-2 flex items-center gap-3 group">
                    <span class="w-9 h-9 rounded-lg bg-primary/10 border border-primary/20 text-primary flex items-center justify-center text-sm shrink-0 transition-all group-hover:bg-primary/20 group-hover:shadow-glow-sm">
                        <i class="fa-solid fa-circle-play"></i>
                    </span>
                    <span>Video</span>
                </a>
                <?php endif; ?>
                <?php if (isset($_site['ptc_enabled']) && $_site['ptc_enabled'] == '1'): ?>
                <a href="#ptc" class="hover:text-primary transition-colors py-2 flex items-center gap-3 group">
                    <span class="w-9 h-9 rounded-lg bg-primary/10 border border-primary/20 text-primary flex items-center justify-center text-sm shrink-0 transition-all group-hover:bg-primary/20 group-hover:shadow-glow-sm">
                        <i class="fa-solid fa-mouse-pointer"></i>
                    </span>
                    <span>PTC Ads</span>
                </a>
                <?php endif; ?>
                <?php if (offerwall_page_enabled($_site)): ?>
                <a href="#offerwall" class="hover:text-primary transition-colors py-2 flex items-center gap-3 group">
                    <span class="w-9 h-9 rounded-lg bg-primary/10 border border-primary/20 text-primary flex items-center justify-center text-sm shrink-0 transition-all group-hover:bg-primary/20 group-hover:shadow-glow-sm">
                        <i class="fa-solid fa-table-cells"></i>
                    </span>
                    <span>Offerwall</span>
                </a>
                <?php endif; ?>
                <?php if (isset($_site['shortlinks_enabled']) && $_site['shortlinks_enabled'] == '1'): ?>
                <a href="#shortlinks" class="hover:text-primary transition-colors py-2 flex items-center gap-3 group">
                    <span class="w-9 h-9 rounded-lg bg-primary/10 border border-primary/20 text-primary flex items-center justify-center text-sm shrink-0 transition-all group-hover:bg-primary/20 group-hover:shadow-glow-sm">
                        <i class="fa-solid fa-link"></i>
                    </span>
                    <span>Shortlinks</span>
                </a>
                <?php endif; ?>
                <?php if (isset($_site['wheel_enabled']) && $_site['wheel_enabled'] == '1'): ?>
                <a href="#wheel" class="hover:text-primary transition-colors py-2 flex items-center gap-3 group">
                    <span class="w-9 h-9 rounded-lg bg-primary/10 border border-primary/20 text-primary flex items-center justify-center text-sm shrink-0 transition-all group-hover:bg-primary/20 group-hover:shadow-glow-sm">
                        <i class="fa-solid fa-dharmachakra"></i>
                    </span>
                    <span>Wheel of Fortune</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="p-4 border-t border-white/10">
            <a href="<?= BASE_URL ?>/" class="block text-center w-full bg-primary hover:bg-primaryHover text-zinc-950 font-semibold py-3 px-4 rounded-lg transition-colors">
                Start Earning
            </a>
        </div>
    </div>

    <header id="main-header" class="fixed top-0 w-full z-50 bg-transparent border-transparent transition-all duration-300">
        <div class="container-custom h-16 md:h-20 flex items-center justify-between">
            <a href="<?= BASE_URL ?>/" class="flex items-center gap-2 lg:gap-3">
                <?php if ($_site['logo_type'] === 'image' && !empty($_site['logo_image'])): ?>
                <img src="<?php echo htmlspecialchars($_site['logo_image']); ?>" alt="Logo" class="w-8 h-8 md:w-10 md:h-10 object-contain">
                <?php else: ?>
                <img src="<?= BASE_URL ?>/assets/iconhd.png" alt="USDT" class="w-8 h-8 md:w-10 md:h-10 object-contain logo-flip">
                <span class="text-xl md:text-2xl font-extrabold tracking-tight text-zinc-100"><?php echo htmlspecialchars($_site['logo_text1']); ?><span class="text-primary"><?php echo htmlspecialchars($_site['logo_text2']); ?></span></span>
                <?php endif; ?>
            </a>

            <nav class="hidden md:flex items-center gap-5 lg:gap-7 font-medium text-sm text-zinc-400">
                <?php if (isset($_site['video_enabled']) && $_site['video_enabled'] == '1'): ?>
                <a href="#video" class="hover:text-primary transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-play text-primary/70 text-xs"></i> Video
                </a>
                <?php endif; ?>
                <?php if (isset($_site['ptc_enabled']) && $_site['ptc_enabled'] == '1'): ?>
                <a href="#ptc" class="hover:text-primary transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-mouse-pointer text-primary/70 text-xs"></i> PTC Ads
                </a>
                <?php endif; ?>
                <?php if (offerwall_page_enabled($_site)): ?>
                <a href="#offerwall" class="hover:text-primary transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-table-cells text-primary/70 text-xs"></i> Offerwall
                </a>
                <?php endif; ?>
                <?php if (isset($_site['shortlinks_enabled']) && $_site['shortlinks_enabled'] == '1'): ?>
                <a href="#shortlinks" class="hover:text-primary transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-link text-primary/70 text-xs"></i> Shortlinks
                </a>
                <?php endif; ?>
                <?php if (isset($_site['wheel_enabled']) && $_site['wheel_enabled'] == '1'): ?>
                <a href="#wheel" class="hover:text-primary transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-dharmachakra text-primary/70 text-xs"></i> Wheel of Fortune
                </a>
                <?php endif; ?>
            </nav>

            <div class="flex items-center gap-3 md:gap-4">
                <a href="<?= BASE_URL ?>/" class="hidden md:block bg-white/5 hover:bg-white/10 text-zinc-200 hover:text-primary font-semibold py-2 px-4 rounded-lg border border-white/10 transition-all text-sm">
                    Start Earning
                </a>
                <button id="mobile-menu-open" class="md:hidden p-2 text-zinc-400 hover:text-primary transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </header>
