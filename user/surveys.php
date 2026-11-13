<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/site_settings.php';
$settings = get_site_settings($pdo);

if (!isset($settings['surveys_enabled']) || $settings['surveys_enabled'] != '1') {
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

$configured = owme_is_configured($settings);
$surveys = [];
if ($configured) {
    foreach (owme_fetch_list('surveyapi.php', $settings, (string)$user_id, getUserIP()) as $s) {
        // Only send users to Offerwall.me survey links.
        $url = (string)($s['url'] ?? '');
        if (strpos($url, OFFERWALLME_BASE . '/') !== 0) continue;
        $surveys[] = $s;
    }
    usort($surveys, function ($a, $b) {
        return (float)($b['reward'] ?? 0) <=> (float)($a['reward'] ?? 0);
    });
}

function fmt_reward($n): string {
    $s = rtrim(rtrim(number_format((float)$n, 6, '.', ''), '0'), '.');
    return $s === '' ? '0' : $s;
}

include 'includes/header.php';
?>

<div class="relative z-10 pt-24 pb-16 min-h-screen">
    <div class="container-custom">
        <div class="mb-8">
            <div class="section-label">Earn</div>
            <h1 class="text-3xl font-extrabold text-zinc-100 tracking-tight mt-2">Surveys</h1>
            <p class="text-sm text-zinc-500 mt-2 max-w-2xl">Complete surveys and get paid. Rewards are credited automatically to your balance once a survey is completed.</p>
        </div>

        <?php if (!$configured): ?>
            <div class="bg-surface-card rounded-lg border border-white/10 p-10 text-center">
                <i class="fa-solid fa-triangle-exclamation text-3xl text-amber-400 mb-3"></i>
                <p class="text-zinc-300 font-semibold">Surveys are not configured yet.</p>
                <p class="text-sm text-zinc-500 mt-2">Surveys are not available yet. Please check back later.</p>
            </div>
        <?php elseif (empty($surveys)): ?>
            <div class="bg-surface-card rounded-lg border border-white/10 p-12 text-center">
                <i class="fa-solid fa-clipboard-list text-5xl text-zinc-600 mb-4 block"></i>
                <h2 class="text-xl font-bold text-zinc-100 mb-2">No Surveys Available</h2>
                <p class="text-zinc-500">There are no surveys for you right now. Please check back later!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                <?php foreach ($surveys as $s):
                    $title = (string)($s['title'] ?? $s['name'] ?? 'Survey');
                    $desc  = (string)($s['description'] ?? '');
                    $loi   = isset($s['loi']) ? (int)$s['loi'] : 0;
                    $conv  = isset($s['conversion_rate']) ? (float)$s['conversion_rate'] : 0;
                    $img   = (string)($s['image'] ?? '');
                    $imgOk = $img !== '' && preg_match('#^https://#i', $img);
                ?>
                <a href="<?php echo htmlspecialchars($s['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"
                   class="group bg-surface-card rounded-lg border border-white/10 p-5 hover:border-primary/50 transition-colors flex flex-col">
                    <div class="flex items-center gap-3 mb-3">
                        <?php if ($imgOk): ?>
                            <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="w-10 h-10 rounded-lg object-cover" loading="lazy" referrerpolicy="no-referrer">
                        <?php else: ?>
                            <span class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center text-primary"><i class="fa-solid fa-clipboard-list"></i></span>
                        <?php endif; ?>
                        <h3 class="text-sm font-bold text-zinc-100 leading-snug"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
                    </div>
                    <?php if ($desc !== ''): ?>
                        <p class="text-xs text-zinc-500 mb-3"><?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <div class="mt-auto flex items-center justify-between text-xs text-zinc-400">
                        <span class="flex items-center gap-3">
                            <?php if ($loi > 0): ?><span><i class="fa-regular fa-clock mr-1"></i><?php echo $loi; ?> min</span><?php endif; ?>
                            <?php if ($conv > 0): ?><span><i class="fa-solid fa-chart-line mr-1"></i><?php echo rtrim(rtrim(number_format($conv, 1), '0'), '.'); ?>%</span><?php endif; ?>
                        </span>
                        <span class="font-mono font-bold text-primary text-sm">+<?php echo fmt_reward($s['reward'] ?? 0); ?> USDT</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
