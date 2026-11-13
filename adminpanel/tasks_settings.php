<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_ROUTE . '/login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");

        $tasks_enabled = isset($_POST['offerwallapi_tasks_enabled']) && $_POST['offerwallapi_tasks_enabled'] == '1' ? '1' : '0';
        $stmt->execute(['offerwallapi_tasks_enabled', $tasks_enabled, $tasks_enabled]);

        $owme_tasks_enabled = isset($_POST['offerwall_tasks_enabled']) && $_POST['offerwall_tasks_enabled'] == '1' ? '1' : '0';
        $stmt->execute(['offerwall_tasks_enabled', $owme_tasks_enabled, $owme_tasks_enabled]);

        if (isset($_POST['ref_percent_tasks'])) {
            $val = max(0, min(100, floatval($_POST['ref_percent_tasks'])));
            $stmt->execute(['ref_percent_tasks', $val, $val]);
        }

        $tasks_tiers = [];
        if (isset($_POST['tasks_amounts']) && is_array($_POST['tasks_amounts'])) {
            for ($i = 0; $i < count($_POST['tasks_amounts']); $i++) {
                $tasks_tiers[] = [
                    'amount' => max(0, floatval($_POST['tasks_amounts'][$i])),
                    'percent' => max(0, floatval($_POST['tasks_percents'][$i]))
                ];
            }
        }
        $stmt->execute(['level_bonus_tasks', json_encode($tasks_tiers), json_encode($tasks_tiers)]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Tasks configuration saved successfully.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

include 'includes/header.php';

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('offerwallapi_tasks_enabled', 'offerwall_tasks_enabled', 'ref_percent_tasks', 'level_bonus_tasks', 'offerwallapi_api_key', 'offerwallapi_secret_key', 'offerwall_api_key', 'offerwall_token')");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$tasks_json = $settings['level_bonus_tasks'] ?? '[]';
$offerwallapi_configured = !empty($settings['offerwallapi_api_key']) && !empty($settings['offerwallapi_secret_key']);
$offerwallme_configured = !empty($settings['offerwall_api_key']) && !empty($settings['offerwall_token']);
?>

<div class="p-6 lg:p-8">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-zinc-100 tracking-tight">Tasks Integration</h2>
        <p class="text-zinc-500 mt-1">Microtasks are provided by the OfferwallApi Tasks feed.</p>
    </div>

    <form id="tasks-form" class="rounded-lg border border-white/10 bg-surface-card p-6 md:p-8">
        <div class="mb-8 pb-8 border-b border-white/10 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-zinc-100">Module Status</h3>
                <p class="text-sm text-zinc-500">Enable or disable the Tasks page for users.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="offerwallapi_tasks_enabled" value="1" class="sr-only peer" <?php echo (!isset($settings['offerwallapi_tasks_enabled']) || $settings['offerwallapi_tasks_enabled'] == '1') ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
        </div>

        <div class="mb-8 p-4 rounded-lg border border-primary/25 bg-primary/10 text-sm text-zinc-300">
            <i class="fa-solid fa-circle-info mr-1"></i>
            Microtasks come from the OfferwallApi Tasks feed. API credentials (API Key, Bearer Token, Secret Key) are configured in
            <a href="<?php echo ADMIN_ROUTE; ?>/ptc_settings" class="font-bold underline hover:text-primary">PTC settings</a> and shared across all OfferwallApi feeds.
            <?php if ($offerwallapi_configured): ?>
                <span class="ml-1 inline-flex items-center gap-1 bg-primary/15 text-primary px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-check"></i> Configured</span>
            <?php else: ?>
                <span class="ml-1 inline-flex items-center gap-1 bg-amber-500/10 text-amber-400 border border-amber-500/25 px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-triangle-exclamation"></i> API keys not configured</span>
            <?php endif; ?>
        </div>

        <div class="mb-8 pb-8 border-b border-white/10 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-zinc-100">Offerwall.me Tasks Feed</h3>
                <p class="text-sm text-zinc-500 mt-1">Merge microtasks from the Offerwall.me provider into the Tasks page alongside the primary feed.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="offerwall_tasks_enabled" value="1" class="sr-only peer" <?php echo (isset($settings['offerwall_tasks_enabled']) && $settings['offerwall_tasks_enabled'] == '1') ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
        </div>

        <div class="mb-8 p-4 rounded-lg border border-primary/25 bg-primary/10 text-sm text-zinc-300">
            <i class="fa-solid fa-circle-info mr-1"></i>
            Offerwall.me credentials (API Key, Bearer Token, Secret Key) are configured in
            <a href="<?php echo ADMIN_ROUTE; ?>/ptc_settings" class="font-bold underline hover:text-primary">PTC settings</a> and shared across all Offerwall.me feeds.
            Since 1 October 2026 Offerwall.me requires signed user IDs — API requests are signed automatically with your Secret Key (Private).
            <?php if ($offerwallme_configured): ?>
                <span class="ml-1 inline-flex items-center gap-1 bg-primary/15 text-primary px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-check"></i> Configured</span>
            <?php else: ?>
                <span class="ml-1 inline-flex items-center gap-1 bg-amber-500/10 text-amber-400 border border-amber-500/25 px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-triangle-exclamation"></i> Keys not configured</span>
            <?php endif; ?>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-medium text-zinc-300 mb-1">Tasks Referral Percent (%)</label>
            <div class="relative w-full md:w-1/2">
                <input type="number" step="0.1" name="ref_percent_tasks" value="<?php echo htmlspecialchars($settings['ref_percent_tasks'] ?? '10'); ?>" class="w-full pl-4 pr-10 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                    <span class="text-zinc-500"><i class="fa-solid fa-percent"></i></span>
                </div>
            </div>
            <p class="text-xs text-zinc-500 mt-1">Percentage of the task reward paid to the referrer.</p>
        </div>

        <div class="border-t border-white/10 pt-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-zinc-100">Task Completion XP Tiers</h3>
                <button type="button" data-add-tier="tasks" class="bg-primary hover:bg-primaryHover text-zinc-950 px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors">
                    <i class="fa-solid fa-plus mr-1"></i> Add Tier
                </button>
            </div>
            <p class="text-sm text-zinc-500 mb-4">Define XP gained based on the task reward amount. If the reward is UP TO the "Amount", the user gets the specified XP percent. Tiers are evaluated from top to bottom, so order them ascending.</p>
            <div id="tasks_tiers_container" class="space-y-3"></div>
        </div>

        <div class="mt-8 pt-8 border-t border-white/10 flex justify-end">
            <button type="submit" id="save-btn" class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-8 rounded-lg transition-colors flex items-center gap-2">
                <i class="fa-solid fa-save"></i> Save Configuration
            </button>
        </div>
    </form>

    <div class="mt-8 bg-surface-raised border border-white/10 rounded-lg p-6 md:p-8">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-link"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-zinc-100 mb-1">OfferwallApi Postback Configuration</h3>
                <p class="text-sm text-zinc-400 mb-4">Completed microtasks are credited via the shared OfferwallApi postback endpoint.</p>
                <div class="bg-surface px-4 py-3 rounded-lg border border-white/10 font-mono text-sm text-primary break-all select-all">
                    <?php
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $domain = $_SERVER['HTTP_HOST'] ?? 'yourdomain.com';
                    echo $protocol . "://" . $domain . "/postback.php";
                    ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE, 'tiers' => ['tasks' => json_decode($tasks_json, true) ?: []]]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-settings.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
