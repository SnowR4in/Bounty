<?php
include 'includes/header.php';
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_faucets'])) {
    $keys = [
        'timed_faucet_enabled', 'timed_faucet_cooldown', 'timed_faucet_reward', 'timed_faucet_energy', 'timed_faucet_daily_limit', 'timed_faucet_captcha', 'timed_faucet_min_level',
        'instant_faucet_enabled', 'instant_faucet_cooldown', 'instant_faucet_reward', 'instant_faucet_energy', 'instant_faucet_daily_limit', 'instant_faucet_captcha', 'instant_faucet_min_level'
    ];

    $_POST['timed_faucet_enabled'] = isset($_POST['timed_faucet_enabled']) ? '1' : '0';
    $_POST['instant_faucet_enabled'] = isset($_POST['instant_faucet_enabled']) ? '1' : '0';

    $updateStmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    try {
        $pdo->beginTransaction();
        foreach ($keys as $key) {
            $value = $_POST[$key] ?? '0';
            $updateStmt->execute([$key, $value]);
        }
        $pdo->commit();
        $success_msg = "Faucet settings updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Failed to update settings.";
    }
}

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE '%faucet%' OR setting_key IN ('level_system_enabled', 'turnstile_enabled', 'recaptcha_enabled')");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

function get_set($key, $default, $settings) {
    return isset($settings[$key]) ? htmlspecialchars($settings[$key]) : $default;
}
?>

        <main class="flex-1 p-4 lg:p-8 w-full">
            <div class="max-w-7xl mx-auto space-y-8">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-100 tracking-tight">Faucet Settings</h1>
            <p class="text-sm text-zinc-500 mt-1">Configure rewards, cooldowns, and limits for the platform's faucets.</p>
        </div>
    </div>

    <?php
    if (isset($success_msg)) {
        $_SESSION['success_msg'] = $success_msg;
    } elseif (isset($error_msg)) {
        $_SESSION['error_msg'] = $error_msg;
    }
    ?>

    <form method="POST" action="" class="space-y-8">

        <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
            <div class="p-6 border-b border-white/10 bg-white/[0.03] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-zinc-100">Timed Faucet</h2>
                        <p class="text-xs text-zinc-500">Standard recurring claims</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" name="timed_faucet_enabled" value="1" class="sr-only peer" <?php echo get_set('timed_faucet_enabled', '1', $settings) == '1' ? 'checked' : ''; ?>>
                  <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                  <span class="ml-3 text-sm font-semibold text-zinc-300">Enable Module</span>
                </label>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Cooldown Timer (Seconds)</label>
                    <input type="number" name="timed_faucet_cooldown" value="<?php echo get_set('timed_faucet_cooldown', '300', $settings); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Reward (USDT)</label>
                    <input type="text" name="timed_faucet_reward" value="<?php echo get_set('timed_faucet_reward', '0.00200000', $settings); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Energy Reward</label>
                    <input type="number" name="timed_faucet_energy" value="<?php echo get_set('timed_faucet_energy', '5', $settings); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Max Daily Claims Per User</label>
                    <input type="number" name="timed_faucet_daily_limit" value="<?php echo get_set('timed_faucet_daily_limit', '100', $settings); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Timed Faucet Captcha</label>
                    <select name="timed_faucet_captcha" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                        <option value="none" <?php echo ($settings['timed_faucet_captcha'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                        <?php if (($settings['recaptcha_enabled'] ?? '0') == '1'): ?>
                            <option value="google" <?php echo ($settings['timed_faucet_captcha'] ?? '') == 'google' ? 'selected' : ''; ?>>Google reCAPTCHA</option>
                        <?php endif; ?>
                        <?php if (($settings['turnstile_enabled'] ?? '0') == '1'): ?>
                            <option value="turnstile" <?php echo ($settings['timed_faucet_captcha'] ?? '') == 'turnstile' ? 'selected' : ''; ?>>Cloudflare Turnstile</option>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if (isset($settings['level_system_enabled']) && $settings['level_system_enabled'] == '1'): ?>
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Minimum Required Level</label>
                    <input type="number" name="timed_faucet_min_level" value="<?php echo get_set('timed_faucet_min_level', '0', $settings); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono" required>
                    <p class="text-[10px] text-zinc-500 mt-1">Set to 0 for no level restriction.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
            <div class="p-6 border-b border-white/10 bg-white/[0.03] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-lg">
                        <i class="fa-solid fa-bolt-lightning"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-zinc-100">Instant Faucet</h2>
                        <p class="text-xs text-zinc-500">Fast 10s recurring claims</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" name="instant_faucet_enabled" value="1" class="sr-only peer" <?php echo get_set('instant_faucet_enabled', '1', $settings) == '1' ? 'checked' : ''; ?>>
                  <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                  <span class="ml-3 text-sm font-semibold text-zinc-300">Enable Module</span>
                </label>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Cooldown Timer (Seconds)</label>
                    <input type="number" name="instant_faucet_cooldown" value="<?php echo get_set('instant_faucet_cooldown', '10', $settings); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Reward (USDT)</label>
                    <input type="text" name="instant_faucet_reward" value="<?php echo get_set('instant_faucet_reward', '0.00050000', $settings); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Energy Reward</label>
                    <input type="number" name="instant_faucet_energy" value="<?php echo get_set('instant_faucet_energy', '1', $settings); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Instant Faucet Captcha</label>
                    <select name="instant_faucet_captcha" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                        <option value="none" <?php echo ($settings['instant_faucet_captcha'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                        <?php if (($settings['recaptcha_enabled'] ?? '0') == '1'): ?>
                            <option value="google" <?php echo ($settings['instant_faucet_captcha'] ?? '') == 'google' ? 'selected' : ''; ?>>Google reCAPTCHA</option>
                        <?php endif; ?>
                        <?php if (($settings['turnstile_enabled'] ?? '0') == '1'): ?>
                            <option value="turnstile" <?php echo ($settings['instant_faucet_captcha'] ?? '') == 'turnstile' ? 'selected' : ''; ?>>Cloudflare Turnstile</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Max Daily Claims Per User</label>
                    <input type="number" name="instant_faucet_daily_limit" value="<?php echo get_set('instant_faucet_daily_limit', '200', $settings); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono" required>
                </div>
                <?php if (isset($settings['level_system_enabled']) && $settings['level_system_enabled'] == '1'): ?>
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Minimum Required Level</label>
                    <input type="number" name="instant_faucet_min_level" value="<?php echo get_set('instant_faucet_min_level', '0', $settings); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono" required>
                    <p class="text-[10px] text-zinc-500 mt-1">Set to 0 for no level restriction.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" name="update_faucets" class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-8 rounded-lg transition-colors">
                <i class="fa-solid fa-save mr-2"></i> Save Faucet Settings
            </button>
        </div>
    </form>

            </div><!-- /max-w-7xl -->
        </main>

<?php include 'includes/footer.php'; ?>
