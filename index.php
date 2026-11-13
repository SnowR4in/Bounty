<?php
session_start();
require_once 'includes/db_connect.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/user/');
    exit;
}
if (isset($_GET['ref']) && is_numeric($_GET['ref'])) {
    $_SESSION['referred_by'] = intval($_GET['ref']);
    setcookie('referred_by', intval($_GET['ref']), time() + (86400 * 30), "/");
}

include 'includes/landingheader.php';
$site_ads = ['login_ads' => [], 'payments_ads' => [], 'floating_ads' => []];
$ads_file = 'includes/ads.json';
if (file_exists($ads_file)) {
    $raw_ads = json_decode(file_get_contents($ads_file), true);
    if (isset($_site['landing_ads_enabled']) && $_site['landing_ads_enabled'] == '1') {
        $site_ads['login_ads'] = $raw_ads['login_ads'] ?? [];
        $site_ads['payments_ads'] = $raw_ads['payments_ads'] ?? [];
    }
    if (isset($_site['floating_ads_landing_enabled']) && $_site['floating_ads_landing_enabled'] == '1') {
        $site_ads['floating_ads'] = $raw_ads['floating_ads'] ?? [];
    }
}

$landing_file = 'pages/landing_content.json';
$default_landing = [
    'hero' => ['title1' => 'Earn USDT in', 'title2' => 'Minutes.', 'subtitle' => 'Turn your free time into Tether USDT by completing simple tasks, viewing PTC ads, and claiming from our instant faucet.'],
    'video' => ['title' => 'Watch & Earn Videos', 'subtitle' => 'Get paid to watch short videos. Sit back, press play, and earn Tether USDT for every video you watch. New videos are added regularly, so your earnings never stop.'],
    'ptc' => ['title' => 'PTC Ads (Paid-To-Click)', 'subtitle' => 'The easiest way to start earning. Simply visit sponsored websites, wait for the timer to finish, and get paid instantly. Discover new crypto projects, faucets, and useful websites while growing your own wallet.'],
    'offerwall' => ['title' => 'Offerwall', 'subtitle' => 'Complete offers, surveys, and partner tasks in one place. Finish an offer and your USDT reward is credited automatically via secure postback.'],
    'shortlinks' => ['title' => 'Shortlinks', 'subtitle' => 'Boost your earnings by solving shortlinks. Navigate through partner links, complete captchas, and reach the destination URL to claim higher rewards. A great way to increase your daily earning potential.'],
    'wheel' => ['title' => 'Wheel of Fortune', 'subtitle' => 'Spin the wheel and test your luck! Use your collected energy to spin the wheel for a chance to win massive USDT rewards. The higher the energy, the more spins you get.'],
    'why' => ['title' => 'Why Tether USDT?', 'subtitle' => 'Tether (USDT) is the world\'s leading stablecoin: 1 USDT always equals 1 US dollar. Enjoy instant payouts, a low minimum withdrawal, and zero price volatility - what you earn is what you keep.', 'btn_text' => 'Official Site', 'btn_link' => 'https://tether.to'],
    'footer' => ['text' => 'BountyEarn helps members earn Tether USDT rewards by completing GPT offers, tasks, ads, and campaigns from trusted advertisers.']
];
$landing_content = [];
if (file_exists($landing_file)) {
    $landing_content = json_decode(file_get_contents($landing_file), true) ?? [];
}

$landing_content = array_replace_recursive($default_landing, $landing_content);

$min_wd_raw = floatval($_site['min_withdrawal'] ?? 0.01);
if ($min_wd_raw <= 0) $min_wd_raw = 0.01;
$min_wd_fmt = rtrim(rtrim(number_format($min_wd_raw, 8, '.', ''), '0'), '.');
?>
    <main class="flex-1 relative z-10">
        <section class="relative pt-24 pb-12 md:pt-32 md:pb-20 overflow-hidden">
            <div class="container-custom relative z-10 max-w-6xl mx-auto">
                <div class="grid lg:grid-cols-2 gap-10 lg:gap-12 xl:gap-16 items-center">

                    <div class="text-center lg:text-left">
                        <div class="section-label justify-center lg:justify-start mb-6">Earn</div>

                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-zinc-100 tracking-tight mb-5 leading-[1.1]">
                            <?php echo htmlspecialchars($landing_content['hero']['title1']); ?><br class="hidden md:block">
                            <span class="text-gradient-primary"><?php echo htmlspecialchars($landing_content['hero']['title2']); ?></span>
                        </h1>

                        <p class="text-base md:text-lg text-zinc-400 mb-8 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                            <?php echo htmlspecialchars($landing_content['hero']['subtitle']); ?>
                        </p>

                        <div class="flex flex-wrap justify-center lg:justify-start gap-2.5 mb-9">
                            <span class="inline-flex items-center gap-2 bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2 text-xs font-semibold text-zinc-300">
                                <i class="fa-solid fa-bolt text-primary text-[11px]"></i> Instant payouts
                            </span>
                            <span class="inline-flex items-center gap-2 bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2 text-xs font-semibold text-zinc-300">
                                <i class="fa-solid fa-shield-halved text-primary text-[11px]"></i> Secure &amp; free
                            </span>
                            <span class="inline-flex items-center gap-2 bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2 text-xs font-semibold text-zinc-300">
                                <i class="fa-solid fa-wallet text-primary text-[11px]"></i> USDT wallet
                            </span>
                            <span class="inline-flex items-center gap-2 bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2 text-xs font-semibold text-zinc-300">
                                <i class="fa-solid fa-coins text-primary text-[11px]"></i> 5 ways to earn
                            </span>
                        </div>

                        <div class="flex items-center justify-center lg:justify-start gap-6 sm:gap-8 text-left">
                            <div>
                                <p class="font-mono text-lg font-bold text-primary"><?php echo htmlspecialchars($min_wd_fmt); ?> <span class="text-zinc-400 text-xs">USDT</span></p>
                                <p class="text-[10px] uppercase tracking-[0.15em] text-zinc-500 font-medium">Min payout</p>
                            </div>
                            <div class="w-px h-9 bg-white/10"></div>
                            <div>
                                <p class="font-mono text-lg font-bold text-zinc-100">1:1</p>
                                <p class="text-[10px] uppercase tracking-[0.15em] text-zinc-500 font-medium">USDT / USD</p>
                            </div>
                            <div class="w-px h-9 bg-white/10"></div>
                            <div>
                                <p class="font-mono text-lg font-bold text-zinc-100">24/7</p>
                                <p class="text-[10px] uppercase tracking-[0.15em] text-zinc-500 font-medium">Earning</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-8 items-center w-full">
                        <div class="relative w-full max-w-xl">
                            <div class="absolute -inset-4 bg-primary/10 blur-3xl rounded-full pointer-events-none" aria-hidden="true"></div>
                            <div class="relative bg-white/[0.02] p-6 md:p-8 rounded-lg border border-white/10 w-full overflow-hidden shadow-glow">
                                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primaryDeep to-primary"></div>
                                <i class="fa-brands fa-tether absolute -right-5 -bottom-10 text-[150px] leading-none text-primary/[0.05] rotate-12 pointer-events-none select-none" aria-hidden="true"></i>

                                <div class="relative">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg shrink-0">
                                            <i class="fa-solid fa-right-to-bracket"></i>
                                        </span>
                                        <h2 class="text-xl font-bold text-zinc-100">Login or Join</h2>
                                    </div>
                                    <p class="text-sm text-zinc-500 mb-6">Log in with your email. New here? Just pick a password &mdash; we'll create your BountyEarn account.</p>

                                    <?php if (isset($_SESSION['login_error'])): ?>
                                        <div class="mb-4 p-3 bg-red-500/10 border border-red-500/25 text-red-400 rounded-lg text-sm">
                                            <?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <form action="user_login.php" method="POST" class="flex flex-col gap-4">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <i class="fa-solid fa-envelope text-zinc-500"></i>
                                            </div>
                                            <input type="email" name="email" placeholder="EMAIL" autocomplete="email"
                                                class="w-full pl-11 pr-4 py-3.5 bg-white/[0.03] border border-white/10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/60 transition-all text-zinc-200 font-medium placeholder-zinc-600"
                                                required>
                                        </div>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <i class="fa-solid fa-lock text-zinc-500"></i>
                                            </div>
                                            <input type="password" name="password" placeholder="PASSWORD" autocomplete="current-password"
                                                class="w-full pl-11 pr-4 py-3.5 bg-white/[0.03] border border-white/10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/60 transition-all text-zinc-200 font-medium placeholder-zinc-600"
                                                required>
                                        </div>
                                        <button type="submit" class="w-full bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3.5 px-6 rounded-lg transition-all flex items-center justify-center gap-2 group hover:shadow-glow-sm">
                                            <span>Let's go</span>
                                            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                                        </button>
                                    </form>

                                    <div class="mt-5 pt-5 border-t border-white/5 text-sm text-zinc-500 flex items-center justify-center lg:justify-start gap-1.5 flex-wrap">
                                        <i class="fa-solid fa-circle-info text-zinc-600"></i>
                                        <span>New to BountyEarn? Enter your email and password to create an account instantly.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="ad-container-login_ads" class="w-full flex justify-center min-h-[90px] max-w-xl">
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <section class="py-12 md:py-16 border-t border-white/10">
            <div class="container-custom">
                <div class="space-y-16">

                    <?php if (isset($_site['video_enabled']) && $_site['video_enabled'] == '1'): ?>
                    <div id="video" class="flex flex-col md:flex-row-reverse items-center gap-8 scroll-mt-24">
                        <div class="flex-1">
                            <div class="w-12 h-12 bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl mb-4"><i class="fa-solid fa-circle-play"></i></div>
                            <h3 class="text-2xl font-semibold tracking-tight text-zinc-100 mb-3"><?php echo htmlspecialchars($landing_content['video']['title']); ?></h3>
                            <p class="text-zinc-400 leading-relaxed"><?php echo htmlspecialchars($landing_content['video']['subtitle']); ?></p>
                        </div>
                        <div class="flex-1 w-full flex justify-center items-center gap-4 flex-wrap">
                            <a href="#" class="bg-white/[0.02] rounded-lg border border-white/10 overflow-hidden group flex flex-col w-48 text-left micro-anim">
                                <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center" style="height: 110px;">
                                    <i class="fa-solid fa-film text-4xl text-zinc-500"></i>
                                    <div class="absolute bottom-2 left-2 bg-black/60 backdrop-blur-md text-white text-[9px] font-bold px-1.5 py-0.5 rounded uppercase flex items-center gap-1 z-10">
                                        <i class="fa-solid fa-video text-[8px]"></i> Video
                                    </div>
                                </div>
                                <div class="p-4 flex flex-col flex-grow relative z-10">
                                    <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-1 truncate">Featured Video</h3>
                                    <p class="text-[11px] text-zinc-500 font-medium mb-3">Watch to the end to earn</p>
                                    <div class="mt-auto flex items-center justify-between">
                                        <span class="font-mono text-primary font-extrabold text-xs">+0.10 USDT</span>
                                        <div class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="bg-white/[0.02] rounded-lg border border-white/10 overflow-hidden group flex flex-col w-48 text-left micro-anim">
                                <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center" style="height: 110px;">
                                    <i class="fa-solid fa-circle-play text-4xl text-zinc-500"></i>
                                    <div class="absolute bottom-2 left-2 bg-black/60 backdrop-blur-md text-white text-[9px] font-bold px-1.5 py-0.5 rounded uppercase flex items-center gap-1 z-10">
                                        <i class="fa-solid fa-video text-[8px]"></i> Video
                                    </div>
                                </div>
                                <div class="p-4 flex flex-col flex-grow relative z-10">
                                    <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-1 truncate">Trending Clip</h3>
                                    <p class="text-[11px] text-zinc-500 font-medium mb-3">Watch to the end to earn</p>
                                    <div class="mt-auto flex items-center justify-between">
                                        <span class="font-mono text-primary font-extrabold text-xs">+0.15 USDT</span>
                                        <div class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_site['ptc_enabled']) && $_site['ptc_enabled'] == '1'): ?>
                    <div id="ptc" class="flex flex-col md:flex-row items-center gap-8 scroll-mt-24">
                        <div class="flex-1">
                            <div class="w-12 h-12 bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl mb-4"><i class="fa-solid fa-mouse-pointer"></i></div>
                            <h3 class="text-2xl font-semibold tracking-tight text-zinc-100 mb-3"><?php echo htmlspecialchars($landing_content['ptc']['title']); ?></h3>
                            <p class="text-zinc-400 leading-relaxed"><?php echo htmlspecialchars($landing_content['ptc']['subtitle']); ?></p>
                        </div>
                        <div class="flex-1 w-full flex justify-center items-center gap-4 flex-wrap">
                            <a href="#" class="bg-white/[0.02] rounded-lg border border-white/10 overflow-hidden group flex flex-col w-48 micro-anim text-left">
                                <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center overflow-hidden" style="height: 110px;">
                                    <i class="fa-solid fa-hand-pointer text-4xl text-zinc-500"></i>
                                    <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-white text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide z-10">
                                        PTC Ad
                                    </div>
                                </div>
                                <div class="p-4 flex flex-col flex-grow relative z-10">
                                    <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-1 truncate">Crypto Faucet</h3>
                                    <p class="text-[11px] text-primary font-medium mb-3"><i class="fa-regular fa-clock"></i> 15 seconds</p>
                                    <div class="mt-auto flex items-center justify-between">
                                        <span class="font-mono text-primary font-extrabold text-xs">+0.05 USDT</span>
                                        <div class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="bg-white/[0.02] rounded-lg border border-white/10 overflow-hidden group flex flex-col w-48 micro-anim text-left">
                                <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center overflow-hidden" style="height: 110px;">
                                    <i class="fa-solid fa-hand-pointer text-4xl text-zinc-500"></i>
                                    <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-white text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide z-10">
                                        PTC Ad
                                    </div>
                                </div>
                                <div class="p-4 flex flex-col flex-grow relative z-10">
                                    <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-1 truncate">Earn Free BTC</h3>
                                    <p class="text-[11px] text-primary font-medium mb-3"><i class="fa-regular fa-clock"></i> 30 seconds</p>
                                    <div class="mt-auto flex items-center justify-between">
                                        <span class="font-mono text-primary font-extrabold text-xs">+0.10 USDT</span>
                                        <div class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (offerwall_page_enabled($_site)): ?>
                    <div id="offerwall" class="flex flex-col md:flex-row items-center gap-8 scroll-mt-24">
                        <div class="flex-1">
                            <div class="w-12 h-12 bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl mb-4"><i class="fa-solid fa-table-cells"></i></div>
                            <h3 class="text-2xl font-semibold tracking-tight text-zinc-100 mb-3"><?php echo htmlspecialchars($landing_content['offerwall']['title'] ?? 'Offerwall'); ?></h3>
                            <p class="text-zinc-400 leading-relaxed"><?php echo htmlspecialchars($landing_content['offerwall']['subtitle'] ?? 'Complete offers and surveys to earn USDT.'); ?></p>
                        </div>
                        <div class="flex-1 w-full flex justify-center items-center gap-4 flex-wrap">
                            <a href="#" class="bg-white/[0.02] rounded-lg border border-white/10 overflow-hidden group flex flex-col w-48 micro-anim text-left">
                                <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center overflow-hidden" style="height: 110px;">
                                    <i class="fa-solid fa-table-cells text-4xl text-zinc-500"></i>
                                    <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-white text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide z-10">
                                        Offer
                                    </div>
                                </div>
                                <div class="p-4 flex flex-col flex-grow relative z-10">
                                    <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-1 truncate">Complete Offer</h3>
                                    <p class="text-[11px] text-primary font-medium mb-3"><i class="fa-solid fa-gift"></i> Instant credit</p>
                                    <div class="mt-auto flex items-center justify-between">
                                        <span class="font-mono text-primary font-extrabold text-xs">+0.50 USDT</span>
                                        <div class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_site['shortlinks_enabled']) && $_site['shortlinks_enabled'] == '1'): ?>
                    <div id="shortlinks" class="flex flex-col md:flex-row-reverse items-center gap-8 scroll-mt-24">
                        <div class="flex-1">
                            <div class="w-12 h-12 bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl mb-4"><i class="fa-solid fa-link"></i></div>
                            <h3 class="text-2xl font-semibold tracking-tight text-zinc-100 mb-3"><?php echo htmlspecialchars($landing_content['shortlinks']['title']); ?></h3>
                            <p class="text-zinc-400 leading-relaxed"><?php echo htmlspecialchars($landing_content['shortlinks']['subtitle']); ?></p>
                        </div>
                        <div class="flex-1 w-full flex justify-center items-center gap-4 flex-wrap">
                            <a href="#" class="bg-white/[0.02] rounded-lg border border-white/10 overflow-hidden group flex flex-col w-48 micro-anim text-left">
                                <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center overflow-hidden" style="height: 110px;">
                                    <i class="fa-solid fa-link text-4xl text-zinc-500"></i>
                                    <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-white text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide z-10">
                                        Shortlink
                                    </div>
                                </div>
                                <div class="p-4 flex flex-col flex-grow relative z-10">
                                    <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-1 truncate">iscut.eu</h3>
                                    <p class="text-[11px] text-zinc-500 font-medium mb-3">Complete link to earn</p>
                                    <div class="mb-3 w-full bg-white/10 rounded-full h-1.5">
                                        <div class="bg-primary h-1.5 rounded-full" style="width: 100%"></div>
                                    </div>
                                    <p class="text-[10px] text-zinc-500 font-bold mb-3">1/1 visits left today</p>
                                    <div class="mt-auto flex items-center justify-between">
                                        <span class="font-mono text-primary font-extrabold text-xs">+0.50 USDT</span>
                                        <div class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>

                            <a href="#" class="bg-white/[0.02] rounded-lg border border-white/10 overflow-hidden group flex flex-col w-48 micro-anim text-left">
                                <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center overflow-hidden" style="height: 110px;">
                                    <i class="fa-solid fa-link text-4xl text-zinc-500"></i>
                                    <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-white text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide z-10">
                                        Shortlink
                                    </div>
                                </div>
                                <div class="p-4 flex flex-col flex-grow relative z-10">
                                    <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-1 truncate">llinklab.me</h3>
                                    <p class="text-[11px] text-zinc-500 font-medium mb-3">Complete link to earn</p>
                                    <div class="mb-3 w-full bg-white/10 rounded-full h-1.5">
                                        <div class="bg-primary h-1.5 rounded-full" style="width: 33%"></div>
                                    </div>
                                    <p class="text-[10px] text-zinc-500 font-bold mb-3">1/3 visits left today</p>
                                    <div class="mt-auto flex items-center justify-between">
                                        <span class="font-mono text-primary font-extrabold text-xs">+0.80 USDT</span>
                                        <div class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_site['wheel_enabled']) && $_site['wheel_enabled'] == '1'): ?>
                    <div id="wheel" class="flex flex-col md:flex-row items-center gap-8 scroll-mt-24">
                        <div class="flex-1">
                            <div class="w-12 h-12 bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl mb-4"><i class="fa-solid fa-dharmachakra"></i></div>
                            <h3 class="text-2xl font-semibold tracking-tight text-zinc-100 mb-3"><?php echo htmlspecialchars($landing_content['wheel']['title']); ?></h3>
                            <p class="text-zinc-400 leading-relaxed"><?php echo htmlspecialchars($landing_content['wheel']['subtitle']); ?></p>
                        </div>
                        <div class="flex-1 w-full flex flex-col items-center justify-center p-6 bg-white/[0.02] rounded-lg border border-white/10 relative overflow-hidden h-[300px]">
                            <div class="w-48 h-48 flex-shrink-0 rounded-full border-4 border-surface-line shadow-xl relative overflow-hidden z-10 animate-[spin_6s_linear_infinite] flex items-center justify-center mb-6">
                                <div class="absolute inset-0" style="background: conic-gradient(
                                    #6ee7b7 0deg 60deg,
                                    #34d399 60deg 120deg,
                                    #10b981 120deg 180deg,
                                    #059669 180deg 240deg,
                                    #0d9488 240deg 300deg,
                                    #14b8a6 300deg 360deg
                                );"></div>
                                <div class="absolute w-8 h-8 bg-surface-raised rounded-full z-20 border-2 border-white/10"></div>
                            </div>
                            <button class="mt-4 relative z-20 bg-primary text-zinc-950 font-bold py-2 px-6 rounded-lg hover:scale-105 transition-transform text-sm">Spin & Win</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <section class="py-12 md:py-16 bg-white/[0.02] border-y border-white/10 relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 rounded-full bg-primary/20 blur-3xl"></div>

            <div class="container-custom relative z-10 text-center max-w-4xl mx-auto">
                <h2 class="text-2xl md:text-3xl font-bold tracking-tight text-zinc-100 mb-4"><?php echo htmlspecialchars($landing_content['why']['title']); ?></h2>
                <p class="text-zinc-400 text-lg mb-6 leading-relaxed">
                    <?php echo htmlspecialchars($landing_content['why']['subtitle']); ?>
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="<?php echo htmlspecialchars($landing_content['why']['btn_link']); ?>" target="_blank" class="bg-white/5 hover:bg-white/10 text-zinc-200 px-5 py-2 rounded-lg font-medium transition-colors border border-white/10">
                        <i class="fa-solid fa-globe mr-2"></i> <?php echo htmlspecialchars($landing_content['why']['btn_text']); ?>
                    </a>
                </div>
            </div>
        </section>
        <div class="container-custom py-8">
            <div id="ad-container-payments_ads" class="w-full flex justify-center min-h-[90px]">
            </div>
        </div>
        <section class="py-12 mb-12">
            <div class="container-custom">
                <div class="bg-white/[0.02] rounded-lg border border-white/10 overflow-hidden">
                    <div class="p-6 border-b border-white/10 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <h2 class="text-xl font-semibold tracking-tight text-zinc-100">Last 10 Payments</h2>
                        <span class="bg-primary/10 text-primary border border-primary/25 rounded font-mono text-[10px] uppercase tracking-wider px-2 py-0.5">Live Updates</span>
                    </div>

                    <div class="w-full overflow-x-auto table-scroll">
                        <table class="w-full text-left whitespace-nowrap min-w-[800px]">
                            <thead class="bg-white/[0.02] text-zinc-400 text-xs uppercase tracking-wider font-medium">
                                <tr>
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Amount</th>
                                    <th class="px-6 py-4">TxID</th>
                                    <th class="px-6 py-4">Time</th>
                                </tr>
                            </thead>
<?php
$stmt = $pdo->query("SELECT id, faucetpay_email, amount, txid, COALESCE(processed_at, created_at) AS processed_at FROM withdrawals WHERE status = 'approved' ORDER BY id DESC LIMIT 10");
$last_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = ['y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) {
        $string = array_slice($string, 0, 1);
    }
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
                            <tbody class="divide-y divide-white/10 text-sm text-zinc-300">
                                <?php if (empty($last_payments)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-zinc-500">No payments have been processed yet.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($last_payments as $p): ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4 text-xs text-zinc-500">#<?php echo $p['id']; ?></td>
                                        <?php
                                        $masked_email = $p['faucetpay_email'];
                                        $atPos = strpos($masked_email, '@');
                                        if ($atPos !== false && $atPos > 0) {
                                            $masked_email = substr($masked_email, 0, 1) . '***' . substr($masked_email, $atPos);
                                        }
                                        ?>
                                        <td class="px-6 py-4 font-mono text-xs" title="Payout email">
                                            <?php echo htmlspecialchars($masked_email); ?>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-primary"><?php echo rtrim(rtrim(number_format($p['amount'], 8), '0'), '.'); ?> USDT</td>
                                        <td class="px-6 py-4">
                                            <span class="font-mono text-xs" title="<?php echo htmlspecialchars($p['txid']); ?>">
                                                <?php echo htmlspecialchars(substr($p['txid'], 0, 8)) . '...'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs whitespace-nowrap">
                                            <?php echo empty($p['processed_at']) ? 'just now' : time_elapsed_string($p['processed_at']); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <script>window.OW_FLOATING_ADS = <?php echo json_encode($site_ads['floating_ads']); ?>;</script>
    <script>window.OW_LANDING_ADS = <?php echo json_encode(['login_ads' => $site_ads['login_ads'], 'payments_ads' => $site_ads['payments_ads']]); ?>;</script>
    <script src="<?= BASE_URL ?>/assets/js/pages/landing.js?v=<?= time() ?>"></script>
<?php include 'includes/landingfooter.php'; ?>
