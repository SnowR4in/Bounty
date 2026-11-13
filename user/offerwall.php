<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/site_settings.php';
$settings = get_site_settings($pdo);

if (!offerwall_page_enabled($settings)) {
    header('Location: /user/');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$api_key = trim((string)($settings['offerwall_api_key'] ?? ''));
$secret  = trim((string)($settings['offerwall_secret_key'] ?? ''));
$owme_on = isset($settings['offerwall_enabled']) && $settings['offerwall_enabled'] == '1';
$ou_on   = ou_is_enabled($settings);

$signedUrl = '';
$config_error = '';
if ($owme_on) {
    if ($api_key === '' || $secret === '') {
        $config_error = 'Offers are not available yet. Please check back later.';
    } else {
        $signedUrl = owme_signed_url($api_key, (string)$user_id, $secret);
    }
}

$ou_walls = $ou_on ? ou_walls_for_user($settings, $user_id) : [];
$ou_live   = ($ou_on && ou_feature_enabled($settings, 'live'))   ? ou_enabled_offers($pdo, $settings, true)  : [];
$ou_offers = ($ou_on && ou_feature_enabled($settings, 'offers')) ? ou_enabled_offers($pdo, $settings, false) : [];

function ou_render_offer_card(array $settings, array $o, int $user_id, bool $live): void {
    $url = ou_offer_url($settings, $o, $user_id);
    $img = (string)($o['image_url'] ?? '');
    ?>
    <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="bg-surface-card rounded-lg border <?php echo $live ? 'border-primary/40' : 'border-white/10'; ?> p-5 hover:border-primary/60 transition-colors flex flex-col">
        <div class="flex items-center gap-3 mb-3">
            <?php if ($img !== '' && preg_match('#^https://#i', $img)): ?>
                <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="w-10 h-10 rounded-lg object-cover" loading="lazy" referrerpolicy="no-referrer">
            <?php else: ?>
                <span class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center text-primary"><i class="fa-solid fa-clipboard-list"></i></span>
            <?php endif; ?>
            <h3 class="text-sm font-bold text-zinc-100 leading-snug flex-1"><?php echo htmlspecialchars($o['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php if ($live): ?><span class="text-[9px] font-bold uppercase tracking-wide bg-red-500/15 text-red-400 border border-red-500/30 rounded px-1.5 py-0.5">Live</span><?php endif; ?>
        </div>
        <?php if (!empty($o['description'])): ?>
            <p class="text-xs text-zinc-500 mb-3"><?php echo htmlspecialchars($o['description'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <div class="mt-auto flex items-center justify-between text-xs text-zinc-400">
            <span><?php if (!empty($o['loi'])): ?><i class="fa-regular fa-clock mr-1"></i><?php echo (int)$o['loi']; ?> min<?php endif; ?></span>
            <span class="font-mono font-bold text-primary text-sm"><?php echo htmlspecialchars(ou_reward_label($o['reward'], $o['reward_type'])); ?></span>
        </div>
    </a>
    <?php
}

include 'includes/header.php';
?>

<div class="relative z-10 pt-24 pb-16 min-h-screen">
    <div class="container-custom">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <div class="section-label">Earn</div>
                <h1 class="text-3xl font-extrabold text-zinc-100 tracking-tight mt-2">Offerwall</h1>
                <p class="text-zinc-500 mt-2">Complete offers and surveys to earn USDT. Rewards are credited automatically.</p>
            </div>
        </div>

        <?php if ($ou_live): ?>
            <div class="mb-10">
                <h2 class="text-lg font-bold text-zinc-100 mb-4"><span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-2 animate-pulse"></span>Live Surveys</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                    <?php foreach ($ou_live as $o) ou_render_offer_card($settings, $o, $user_id, true); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($ou_offers): ?>
            <div class="mb-10">
                <h2 class="text-lg font-bold text-zinc-100 mb-4">Featured Surveys</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                    <?php foreach ($ou_offers as $o) ou_render_offer_card($settings, $o, $user_id, false); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($ou_on && $ou_walls): ?>
            <div class="mb-10">
                <h2 class="text-lg font-bold text-zinc-100 mb-1">Survey Walls</h2>
                <p class="text-xs text-zinc-500 mb-4">Pay is set per survey by the provider. The exact reward is shown on the survey and credited to your balance when you finish.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    <?php foreach ($ou_walls as $wall): ?>
                        <a href="<?php echo htmlspecialchars($wall['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="bg-surface-card rounded-lg border border-white/10 overflow-hidden hover:border-white/20 transition-colors group flex flex-col h-full micro-anim">
                            <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center overflow-hidden" style="height: 110px;">
                                <i class="fa-solid <?php echo htmlspecialchars($wall['icon']); ?> text-4xl text-primary/40"></i>
                                <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-zinc-300 text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide z-10 font-mono">
                                    Survey
                                </div>
                            </div>
                            <div class="p-4 flex flex-col flex-grow">
                                <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-1 truncate"><?php echo htmlspecialchars($wall['name']); ?></h3>
                                <p class="text-[11px] text-zinc-500 mb-3"><?php echo htmlspecialchars($wall['description']); ?></p>
                                <div class="mt-auto flex items-center justify-between">
                                    <span class="text-[10px] text-primary uppercase tracking-wider font-semibold font-mono">Reward: Varies</span>
                                    <div class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center group-hover:bg-primary group-hover:text-zinc-950 transition-colors">
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($owme_on): ?>
            <?php if ($config_error !== ''): ?>
                <div class="bg-surface-card rounded-lg border border-white/10 p-12 text-center">
                    <i class="fa-solid fa-table-cells text-5xl text-zinc-600 mb-4 block"></i>
                    <h2 class="text-xl font-bold text-zinc-100 mb-2">Offerwall Unavailable</h2>
                    <p class="text-zinc-500"><?php echo htmlspecialchars($config_error); ?></p>
                </div>
            <?php else: ?>
                <div class="bg-surface-card rounded-lg border border-white/10 overflow-hidden">
                    <iframe
                        src="<?php echo htmlspecialchars($signedUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        scrolling="yes"
                        frameborder="0"
                        style="width:100%;height:800px;border:0;"
                        title="Offers"
                    ></iframe>
                </div>
            <?php endif; ?>
        <?php elseif (!$ou_on || (!$ou_walls && !$ou_offers && !$ou_live)): ?>
            <div class="bg-surface-card rounded-lg border border-white/10 p-12 text-center">
                <i class="fa-solid fa-table-cells text-5xl text-zinc-600 mb-4 block"></i>
                <h2 class="text-xl font-bold text-zinc-100 mb-2">Offerwall Unavailable</h2>
                <p class="text-zinc-500">No offerwall providers are enabled.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
