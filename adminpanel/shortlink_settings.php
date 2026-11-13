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
        $keys = [
            'shortlinks_enabled',
            'offerwallapi_shortlinks_enabled',
            'offerwall_shortlinks_enabled',
            'offerwall_api_key',
            'offerwall_secret_key',
            'offerwall_token',
            'offerwall_ip_whitelist'
        ];

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");

        foreach ($keys as $key) {
            if ($key === 'shortlinks_enabled' || $key === 'offerwallapi_shortlinks_enabled' || $key === 'offerwall_shortlinks_enabled') {
                $val = isset($_POST[$key]) && $_POST[$key] == '1' ? '1' : '0';
            } else {
                $val = isset($_POST[$key]) ? trim($_POST[$key]) : '';
            }
            $stmt->execute([$key, $val, $val]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Shortlinks configuration saved successfully.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

include 'includes/header.php';

$offerwallapi_configured = !empty($_site['offerwallapi_api_key']);
?>

<div class="p-6 lg:p-8">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-zinc-100 tracking-tight">Shortlinks Integration</h2>
        <p class="text-zinc-500 mt-1">Configure providers for the Shortlinks module (OfferwallApi primary, Offerwall.me secondary).</p>
    </div>

    <form id="shortlinks-form" class="rounded-lg border border-white/10 bg-surface-card p-6 md:p-8">
        <div class="mb-8 pb-8 border-b border-white/10 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-zinc-100">Module Status</h3>
                <p class="text-sm text-zinc-500">Enable or disable the Shortlinks page for users.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="shortlinks_enabled" value="1" class="sr-only peer" <?php echo (isset($_site['shortlinks_enabled']) && $_site['shortlinks_enabled'] == '1') ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
        </div>

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-zinc-100">OfferwallApi — Primary</h3>
                <p class="text-sm text-zinc-500 mt-1">Shortlinks feed from OfferwallApi.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="offerwallapi_shortlinks_enabled" value="1" class="sr-only peer" <?php echo (!isset($_site['offerwallapi_shortlinks_enabled']) || $_site['offerwallapi_shortlinks_enabled'] == '1') ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
        </div>

        <div class="mb-12 p-4 rounded-lg border border-primary/25 bg-primary/10 text-sm text-zinc-300">
            <i class="fa-solid fa-circle-info mr-1"></i>
            OfferwallApi credentials (API Key, Bearer Token, Secret Key) and the postback IP whitelist are configured in
            <a href="<?php echo ADMIN_ROUTE; ?>/ptc_settings" class="font-bold underline hover:text-primary">PTC settings</a> and shared across all OfferwallApi feeds.
            <?php if ($offerwallapi_configured): ?>
                <span class="ml-1 inline-flex items-center gap-1 bg-primary/15 text-primary px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-check"></i> Configured</span>
            <?php else: ?>
                <span class="ml-1 inline-flex items-center gap-1 bg-amber-500/10 text-amber-400 border border-amber-500/25 px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-triangle-exclamation"></i> API key not configured</span>
            <?php endif; ?>
        </div>

        <div class="mb-6 flex items-center justify-between border-t border-white/10 pt-8">
            <div>
                <h3 class="text-lg font-bold text-zinc-100">Offerwall.me — Secondary</h3>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="offerwall_shortlinks_enabled" value="1" class="sr-only peer" <?php echo (isset($_site['offerwall_shortlinks_enabled']) && $_site['offerwall_shortlinks_enabled'] == '1') ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
        </div>

        <div class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">API Key (Public)</label>
                <input type="text" name="offerwall_api_key" value="<?php echo htmlspecialchars($_site['offerwall_api_key'] ?? ''); ?>" placeholder="Your Public API Key" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
            </div>

            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">Secret Key (Private)</label>
                <input type="password" name="offerwall_secret_key" value="<?php echo htmlspecialchars($_site['offerwall_secret_key'] ?? ''); ?>" placeholder="Your Secret Key" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <p class="text-xs text-zinc-500 mt-1">Used to verify the authenticity of postback requests.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">Bearer Token (API Calls)</label>
                <input type="password" name="offerwall_token" value="<?php echo htmlspecialchars($_site['offerwall_token'] ?? ''); ?>" placeholder="Your Bearer Token" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
            </div>

            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">Postback IP Whitelist</label>
                <input type="hidden" name="offerwall_ip_whitelist" id="offerwall_ip_whitelist" value="<?php echo htmlspecialchars($_site['offerwall_ip_whitelist'] ?? "95.216.65.163\n2a01:4f9:2b:1dc::2"); ?>">
                <div class="flex flex-wrap gap-2 mb-2 empty:hidden" id="offerwall_ip_tags"></div>
                <div class="flex gap-2">
                    <input type="text" id="offerwall_ip_input" placeholder="Add IP Address" class="flex-1 px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono text-sm">
                    <button type="button" data-ip-add="offerwall" class="bg-white/5 hover:bg-white/10 text-zinc-200 border border-white/10 font-bold py-2 px-4 rounded-lg transition-colors text-sm">Add</button>
                </div>
                <p class="text-xs text-zinc-500 mt-1">IP addresses allowed to send postbacks.</p>
            </div>
        </div>

        <div class="mt-8 pt-8 border-t border-white/10 flex justify-end">
            <button type="submit" id="save-btn" class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-8 rounded-lg transition-colors flex items-center gap-2">
                <i class="fa-solid fa-save"></i> Save Configuration
            </button>
        </div>
    </form>

    <div class="mt-8 bg-surface-raised border border-white/10 rounded-lg p-6 md:p-8">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-12 h-12 bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-link"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-zinc-100 mb-1">Offerwall.me Postback Configuration</h3>
                <p class="text-sm text-zinc-400 mb-4">Set up this Postback URL in your Offerwall.me Placement Settings.</p>
                <div class="bg-surface px-4 py-3 rounded-lg border border-white/10 font-mono text-sm text-primary break-all select-all">
                    <?php
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $domain = $_SERVER['HTTP_HOST'] ?? 'yourdomain.com';
                    echo $protocol . "://" . $domain . "/postback_offerwall.php";
                    ?>
                </div>
                <p class="text-xs text-zinc-500 mt-3">OfferwallApi shortlinks are credited through the main <span class="font-mono text-primary">/postback.php</span> endpoint shown in PTC settings.</p>
            </div>
        </div>
    </div>
</div>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-settings.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
