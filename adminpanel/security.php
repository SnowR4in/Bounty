<?php
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update_settings'])) {
    header('Content-Type: application/json');
    $proxycheck_enabled = isset($_POST['proxycheck_enabled']) ? '1' : '0';
    $proxycheck_key = trim($_POST['proxycheck_key'] ?? '');

    $recaptcha_enabled = isset($_POST['recaptcha_enabled']) ? '1' : '0';
    $recaptcha_site_key = trim($_POST['recaptcha_site_key'] ?? '');
    $recaptcha_secret_key = trim($_POST['recaptcha_secret_key'] ?? '');

    $turnstile_enabled = isset($_POST['turnstile_enabled']) ? '1' : '0';
    $turnstile_site_key = trim($_POST['turnstile_site_key'] ?? '');
    $turnstile_secret_key = trim($_POST['turnstile_secret_key'] ?? '');

    try {
        $pdo->beginTransaction();

        $pdo->exec("DELETE FROM settings WHERE setting_key IN ('0', '1') AND setting_value IN ('proxycheck_enabled', 'proxycheck_key', 'recaptcha_enabled', 'recaptcha_site_key', 'recaptcha_secret_key', 'turnstile_enabled', 'turnstile_site_key', 'turnstile_secret_key')");

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['proxycheck_enabled', $proxycheck_enabled]);
        $stmt->execute(['proxycheck_key', $proxycheck_key]);
        $stmt->execute(['recaptcha_enabled', $recaptcha_enabled]);
        $stmt->execute(['recaptcha_site_key', $recaptcha_site_key]);
        $stmt->execute(['recaptcha_secret_key', $recaptcha_secret_key]);
        $stmt->execute(['turnstile_enabled', $turnstile_enabled]);
        $stmt->execute(['turnstile_site_key', $turnstile_site_key]);
        $stmt->execute(['turnstile_secret_key', $turnstile_secret_key]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Security & Fraud settings updated successfully!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error updating settings: ' . $e->getMessage()]);
    }
    exit;
}

include 'includes/header.php';

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_raw = $stmt->fetchAll();
if (!isset($settings)) {
    $settings = [];
}
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

        <main class="flex-1 p-4 lg:p-8 w-full">
            <div class="max-w-7xl mx-auto space-y-8">

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-zinc-100 tracking-tight">Security &amp; Fraud</h1>
    </div>

    <form id="securityForm" class="space-y-6">

        <div class="rounded-lg border border-white/10 bg-surface-card p-6">
            <div class="flex items-center justify-between mb-6 border-b border-white/10 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-zinc-100 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-primary"></i> VPN / Proxy Detection (ProxyCheck.io)
                    </h2>
                    <p class="text-sm text-zinc-500 mt-1">Block VPN, Proxy, and Hosting IPs during login and registration.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="proxycheck_enabled" class="sr-only peer" <?php echo ($settings['proxycheck_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">ProxyCheck.io API Key</label>
                    <input type="password" name="proxycheck_key" value="<?php echo htmlspecialchars($settings['proxycheck_key'] ?? ''); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                    <p class="text-xs text-zinc-500 mt-2">
                        VPN/Proxy detection powered by proxycheck.io — free tier available, get a free key at <a href="https://proxycheck.io" target="_blank" class="text-primary hover:underline">https://proxycheck.io</a>
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-white/10 bg-surface-card p-6">
            <div class="flex items-center justify-between mb-6 border-b border-white/10 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-zinc-100 flex items-center gap-2">
                        <i class="fa-solid fa-robot text-primary"></i> Google reCAPTCHA v2
                    </h2>
                    <p class="text-sm text-zinc-500 mt-1">Global settings for Google reCAPTCHA. Once enabled, it can be selected for the faucet, wheel, and admin login.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="recaptcha_enabled" class="sr-only peer" <?php echo ($settings['recaptcha_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">reCAPTCHA Site Key</label>
                    <input type="text" name="recaptcha_site_key" value="<?php echo htmlspecialchars($settings['recaptcha_site_key'] ?? ''); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">reCAPTCHA Secret Key</label>
                    <input type="password" name="recaptcha_secret_key" value="<?php echo htmlspecialchars($settings['recaptcha_secret_key'] ?? ''); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                </div>
            </div>
            <p class="text-xs text-zinc-500 mt-3">Get your keys from the <a href="https://www.google.com/recaptcha/admin" target="_blank" class="text-primary hover:underline">Google reCAPTCHA admin console</a> (use the "v2 Checkbox" type).</p>
        </div>

        <div class="rounded-lg border border-white/10 bg-surface-card p-6">
            <div class="flex items-center justify-between mb-6 border-b border-white/10 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-zinc-100 flex items-center gap-2">
                        <i class="fa-solid fa-shield-virus text-primary"></i> Cloudflare Turnstile
                    </h2>
                    <p class="text-sm text-zinc-500 mt-1">Configure Cloudflare Turnstile captcha for your faucets and wheel.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="turnstile_enabled" class="sr-only peer" <?php echo ($settings['turnstile_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Turnstile Site Key</label>
                    <input type="text" name="turnstile_site_key" value="<?php echo htmlspecialchars($settings['turnstile_site_key'] ?? ''); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Turnstile Secret Key</label>
                    <input type="text" name="turnstile_secret_key" value="<?php echo htmlspecialchars($settings['turnstile_secret_key'] ?? ''); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primaryHover text-zinc-950 font-medium py-2.5 px-6 rounded-lg transition-colors flex items-center gap-2">
                <i class="fa-solid fa-save"></i> Save Settings
            </button>
        </div>
    </form>
</div>

            </div><!-- /max-w-7xl -->
        </main>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-settings.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
