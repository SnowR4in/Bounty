<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/site_settings.php';
$settings = get_site_settings($pdo);

if (!isset($settings['ptc_enabled']) || $settings['ptc_enabled'] == '0') {
    header('Location: /user/');
    exit;
}

$user_id = intval($_SESSION['user_id']);

function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

$userIP = getUserIP();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$tasks = [];

if (isset($settings['offerwallapi_ptc_enabled']) && $settings['offerwallapi_ptc_enabled'] == '1') {
    $feed = owapi_fetch_feed('ptc', $settings, $user_id, $userIP);
    if (!empty($feed['items'])) {
        foreach ($feed['items'] as $item) {
            $tasks[] = $item;
        }
    }
}

if (isset($settings['offerwall_ptc_enabled']) && $settings['offerwall_ptc_enabled'] == '1') {
    foreach (owme_fetch_list('api.php', $settings, (string)$user_id, $userIP) as $t) {
        $tasks[] = $t;
    }
}

$error_msg = empty($tasks) ? "No PTC Ads available at the moment. Please check back later." : null;

include 'includes/header.php';

$page_data = [
    'offers' => $tasks,
    'userId' => (string)$user_id
];
?>

<div class="relative z-10 pt-24 pb-16 min-h-screen">
    <div class="container-custom">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <div class="section-label">Earn</div>
                <h1 class="text-3xl font-extrabold text-zinc-100 tracking-tight mt-2">PTC Ads</h1>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:w-auto">
                    <select id="sortFilter" class="appearance-none w-full sm:w-auto bg-white/[0.03] border border-white/10 text-zinc-200 px-4 py-2 pr-8 rounded-lg text-sm font-medium hover:bg-white/5 transition-colors outline-none cursor-pointer focus:border-primary/60">
                        <option value="desc">Price High to Low</option>
                        <option value="asc">Price Low to High</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-zinc-500">
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error_msg): ?>
            <div class="bg-surface-card rounded-lg border border-white/10 p-12 text-center">
                <i class="fa-solid fa-hand-pointer text-5xl text-zinc-600 mb-4 block"></i>
                <h2 class="text-xl font-bold text-zinc-100 mb-2">No PTC Ads Available</h2>
                <p class="text-zinc-500">There are currently no ads available for your region. Please check back later!</p>
            </div>
        <?php else: ?>
            <div id="offersGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 gap-4 sm:gap-6"></div>

            <div id="noOffersMatch" class="hidden bg-surface-card rounded-lg border border-white/10 p-12 text-center mt-6">
                <i class="fa-solid fa-filter-circle-xmark text-5xl text-zinc-600 mb-4 block"></i>
                <h2 class="text-xl font-bold text-zinc-100 mb-2">No Ads Match</h2>
                <p class="text-zinc-500">Try changing your filter settings to see more ads.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="prematureOverlay" class="fixed inset-0 z-[200] bg-surface/95 backdrop-blur-sm flex flex-col items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <i class="fa-solid fa-clock text-6xl text-primary mb-4 animate-pulse"></i>
    <h2 class="text-3xl font-bold text-zinc-100 mb-2">You returned too early!</h2>
    <p class="text-zinc-400 mb-6 text-center max-w-md text-lg">You need to view the ad for at least <span id="prematureSeconds" class="font-bold text-primary text-xl font-mono"></span> more seconds to earn your reward.</p>
    <div class="flex gap-4">
        <a href="#" id="reopenAdBtn" target="_blank" class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-8 rounded-lg transition-all">
            Reopen Ad
        </a>
        <button id="cancelAdBtn" class="bg-white/5 hover:bg-white/10 text-zinc-200 border border-white/10 font-bold py-3 px-8 rounded-lg transition-all">
            Cancel
        </button>
    </div>
</div>

<div class="container-custom pb-8"><div id="ad-container-general_bottom_ads" class="flex justify-center empty:hidden w-full min-h-[90px]"></div></div>

<script>window.OW_PAGE_DATA = <?php echo json_encode($page_data, JSON_HEX_TAG); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/pages/user-ptc.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
