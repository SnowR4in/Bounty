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
            'offerwall_enabled',
            'offerwall_api_key',
            'offerwall_secret_key',
            'offerwall_token',
            'offerwall_ip_whitelist',
            'ref_percent_offerwall',
            'level_bonus_offerwall',
            'ou_enabled',
            'ou_app_id',
            'ou_uid',
            'ou_secret_key',
            'ou_source_id',
            'ou_ip_whitelist',
            'ou_wall_dynata_enabled',
            'ou_wall_bitlab_enabled',
            'ou_wall_cpx_enabled',
            'ou_offers_enabled',
            'ou_live_enabled'
        ];
        $toggle_keys = ['offerwall_enabled', 'ou_enabled', 'ou_wall_dynata_enabled', 'ou_wall_bitlab_enabled', 'ou_wall_cpx_enabled', 'ou_offers_enabled', 'ou_live_enabled'];

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");

        foreach ($keys as $key) {
            if (in_array($key, $toggle_keys, true)) {
                $val = isset($_POST[$key]) && $_POST[$key] == '1' ? '1' : '0';
            } elseif ($key === 'ref_percent_offerwall' || $key === 'level_bonus_offerwall') {
                $val = isset($_POST[$key]) ? (string)max(0, min(100, floatval($_POST[$key]))) : '0';
            } else {
                $val = isset($_POST[$key]) ? trim($_POST[$key]) : '';
            }
            $stmt->execute([$key, $val, $val]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Offerwall configuration saved successfully.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

include 'includes/header.php';

$offerwall_configured = !empty($_site['offerwall_api_key']) && !empty($_site['offerwall_secret_key']);
$ou_configured = !empty($_site['ou_app_id']);
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'] ?? 'yourdomain.com';
$postback_url = $protocol . "://" . $domain . "/postback_offerwall.php";
$ou_postback_url = $protocol . "://" . $domain . "/postback_opinionuniverse.php?sub_id={SID}&app_id={app_id}&status={STATUS}&payout={PAYOUT}&pubpayout={PUBPAYOUT}&txid={TransactionID}&ip={IP}&offerid={OFFERID}&offername={offername}&sig={SIG}";
?>

<div class="p-6 lg:p-8">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-zinc-100 tracking-tight">Offerwall Integration</h2>
        <p class="text-zinc-500 mt-1">Offerwall.me iframe plus OpinionUniverse survey walls (Dynata, Bitlab, CPX Research). Completions and chargebacks are credited via S2S postback.</p>
    </div>

    <form id="offerwall-form" class="rounded-lg border border-white/10 bg-surface-card p-6 md:p-8">
        <div class="mb-8 pb-8 border-b border-white/10 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-zinc-100">Offerwall.me Status</h3>
                <p class="text-sm text-zinc-500">Enable or disable the Offerwall.me iframe for users.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="offerwall_enabled" value="1" class="sr-only peer" <?php echo (isset($_site['offerwall_enabled']) && $_site['offerwall_enabled'] == '1') ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
        </div>

        <div class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">API Key (Public)</label>
                <input type="text" name="offerwall_api_key" value="<?php echo htmlspecialchars($_site['offerwall_api_key'] ?? ''); ?>" placeholder="Your Public API Key" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <p class="text-xs text-zinc-500 mt-1">Used in the signed offerwall URL. Safe to include in the iframe src.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">Secret Key (Private)</label>
                <input type="password" name="offerwall_secret_key" value="<?php echo htmlspecialchars($_site['offerwall_secret_key'] ?? ''); ?>" placeholder="Your Secret Key" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <p class="text-xs text-zinc-500 mt-1">Used to sign user IDs and verify postback signatures. Never expose this key publicly.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">Bearer Token (API Calls)</label>
                <input type="password" name="offerwall_token" value="<?php echo htmlspecialchars($_site['offerwall_token'] ?? ''); ?>" placeholder="Your Bearer Token" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <p class="text-xs text-zinc-500 mt-1">Shared with PTC, Shortlinks and Tasks feeds. Keep this private.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">Postback IP Whitelist</label>
                <input type="hidden" name="offerwall_ip_whitelist" id="offerwall_ip_whitelist" value="<?php echo htmlspecialchars($_site['offerwall_ip_whitelist'] ?? "95.216.65.163\n2a01:4f9:2b:1dc::2"); ?>">
                <div class="flex flex-wrap gap-2 mb-2 empty:hidden" id="offerwall_ip_tags"></div>
                <div class="flex gap-2">
                    <input type="text" id="offerwall_ip_input" placeholder="Add IP Address" class="flex-1 px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono text-sm">
                    <button type="button" data-ip-add="offerwall" class="bg-white/5 hover:bg-white/10 text-zinc-200 border border-white/10 font-bold py-2 px-4 rounded-lg transition-colors text-sm">Add</button>
                </div>
                <p class="text-xs text-zinc-500 mt-1">Allowlist Offerwall.me server IPs: 95.216.65.163 and 2a01:4f9:2b:1dc::2.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Referral Percent (%)</label>
                    <input type="number" step="0.1" min="0" max="100" name="ref_percent_offerwall" value="<?php echo htmlspecialchars($_site['ref_percent_offerwall'] ?? '5'); ?>" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Level Bonus Percent (%)</label>
                    <input type="number" step="0.1" min="0" max="100" name="level_bonus_offerwall" value="<?php echo htmlspecialchars($_site['level_bonus_offerwall'] ?? '5'); ?>" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                    <p class="text-xs text-zinc-500 mt-1">Percent of XP needed for the next level, awarded on each completed offer.</p>
                </div>
            </div>
        </div>

        <div class="mt-10 mb-8 pb-8 border-t border-b border-white/10 pt-8 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-zinc-100">OpinionUniverse Surveys</h3>
                <p class="text-sm text-zinc-500">Show Dynata, Bitlab and CPX Research click-tracking walls on the Offerwall page.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="ou_enabled" value="1" class="sr-only peer" <?php echo (!isset($_site['ou_enabled']) || $_site['ou_enabled'] == '1') ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
        </div>


        <div class="mb-8 pb-8 border-b border-white/10">
            <h3 class="text-lg font-bold text-zinc-100 mb-1">Survey Wall &amp; Feature Switches</h3>
            <p class="text-sm text-zinc-500 mb-4">Each one can be turned on or off independently. Manage individual offers under <a class="text-primary" href="<?php echo ADMIN_ROUTE; ?>/ou_offers">Survey Offers</a>.</p>
            <div class="space-y-3">
            <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/[0.02] px-4 py-3">
                <div>
                    <div class="text-sm font-bold text-zinc-200">Dynata wall</div>
                    <div class="text-xs text-zinc-500">Show the Dynata survey wall card.</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="ou_wall_dynata_enabled" value="1" class="sr-only peer" <?php echo ou_wall_enabled($_site, 'dynata') ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/[0.02] px-4 py-3">
                <div>
                    <div class="text-sm font-bold text-zinc-200">Bitlab wall</div>
                    <div class="text-xs text-zinc-500">Show the Bitlab survey wall card.</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="ou_wall_bitlab_enabled" value="1" class="sr-only peer" <?php echo ou_wall_enabled($_site, 'bitlab') ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/[0.02] px-4 py-3">
                <div>
                    <div class="text-sm font-bold text-zinc-200">CPX Research wall</div>
                    <div class="text-xs text-zinc-500">Show the CPX Research survey wall card.</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="ou_wall_cpx_enabled" value="1" class="sr-only peer" <?php echo ou_wall_enabled($_site, 'cpx') ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/[0.02] px-4 py-3">
                <div>
                    <div class="text-sm font-bold text-zinc-200">Survey offers list</div>
                    <div class="text-xs text-zinc-500">Show the enabled offers you added in Survey Offers.</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="ou_offers_enabled" value="1" class="sr-only peer" <?php echo ou_feature_enabled($_site, 'offers') ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/[0.02] px-4 py-3">
                <div>
                    <div class="text-sm font-bold text-zinc-200">Live surveys section</div>
                    <div class="text-xs text-zinc-500">Show offers marked LIVE in a pinned section at the top.</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="ou_live_enabled" value="1" class="sr-only peer" <?php echo ou_feature_enabled($_site, 'live') ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>
            </div>
        </div>
        <div class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">App ID</label>
                <input type="text" name="ou_app_id" value="<?php echo htmlspecialchars($_site['ou_app_id'] ?? 'ID_d1c94cda94189d2d3b41633b7289c70c'); ?>" placeholder="ID_..." class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <p class="text-xs text-zinc-500 mt-1">Placement App ID used in click-tracking URLs. Default is the provided OpinionUniverse app.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">Publisher UID</label>
                <input type="text" name="ou_uid" value="<?php echo htmlspecialchars($_site['ou_uid'] ?? '4157'); ?>" placeholder="4157" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <p class="text-xs text-zinc-500 mt-1">Fixed publisher id in click-tracking URLs (uid=4157). Site users are passed as sid, not uid.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">Secret Key (HMAC)</label>
                <input type="password" name="ou_secret_key" value="<?php echo htmlspecialchars($_site['ou_secret_key'] ?? ''); ?>" placeholder="Placement secret for {SIG} verification" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <p class="text-xs text-zinc-500 mt-1">Used to verify HMAC-SHA256 postback signatures (sid + offerid + payout + status). Leave empty only while testing.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">Source ID (sid2)</label>
                <input type="text" name="ou_source_id" value="<?php echo htmlspecialchars($_site['ou_source_id'] ?? 'offerwall'); ?>" placeholder="offerwall" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <p class="text-xs text-zinc-500 mt-1">Passed as sid2 on click-tracking links so you can attribute traffic in OpinionUniverse reports.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-zinc-300 mb-2">Postback IP Whitelist</label>
                <input type="hidden" name="ou_ip_whitelist" id="ou_ip_whitelist" value="<?php echo htmlspecialchars($_site['ou_ip_whitelist'] ?? '147.79.123.200'); ?>">
                <div class="flex flex-wrap gap-2 mb-2 empty:hidden" id="ou_ip_tags"></div>
                <div class="flex gap-2">
                    <input type="text" id="ou_ip_input" placeholder="Add IP Address" class="flex-1 px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono text-sm">
                    <button type="button" data-ip-add="ou" class="bg-white/5 hover:bg-white/10 text-zinc-200 border border-white/10 font-bold py-2 px-4 rounded-lg transition-colors text-sm">Add</button>
                </div>
                <p class="text-xs text-zinc-500 mt-1">Allowlist Opinion Universe postback IP: 147.79.123.200.</p>
            </div>
        </div>

        <div class="mt-8 pt-8 border-t border-white/10 flex items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap gap-2">
                <?php if ($offerwall_configured): ?>
                    <span class="inline-flex items-center gap-1 bg-primary/15 text-primary px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-check"></i> Offerwall.me keys configured</span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 bg-amber-500/10 text-amber-400 border border-amber-500/25 px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-triangle-exclamation"></i> Offerwall.me keys not configured</span>
                <?php endif; ?>
                <?php if ($ou_configured): ?>
                    <span class="inline-flex items-center gap-1 bg-primary/15 text-primary px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-check"></i> OpinionUniverse App ID set</span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 bg-amber-500/10 text-amber-400 border border-amber-500/25 px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-triangle-exclamation"></i> OpinionUniverse App ID missing</span>
                <?php endif; ?>
                </div>
            </div>
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
                <h3 class="text-lg font-bold text-zinc-100 mb-1">Offerwall.me Postback URL</h3>
                <p class="text-sm text-zinc-400 mb-4">Set this URL in your Offerwall.me Placement Settings for PTC, shortlink, task, and offer postbacks. Chargebacks use status=2 on the same endpoint.</p>
                <div class="bg-surface px-4 py-3 rounded-lg border border-white/10 font-mono text-sm text-primary break-all select-all">
                    <?php echo htmlspecialchars($postback_url); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 bg-surface-raised border border-white/10 rounded-lg p-6 md:p-8">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-link"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-zinc-100 mb-1">OpinionUniverse Postback URL</h3>
                <p class="text-sm text-zinc-400 mb-4">Paste this into Placement Settings → Postback. Responds with 1. Status 1 credits the user; status 2 reverses a previous credit. Locked to Dynata (3063128), Bitlab (6301974) and CPX Research (4314257).</p>
                <div class="bg-surface px-4 py-3 rounded-lg border border-white/10 font-mono text-sm text-primary break-all select-all">
                    <?php echo htmlspecialchars($ou_postback_url); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-settings.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
