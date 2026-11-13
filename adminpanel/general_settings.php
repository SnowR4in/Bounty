<?php
require_once '../includes/db_connect.php';
require_once '../includes/site_settings.php';

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$site = get_site_settings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $update_stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    $allowed_settings = [
        'referrals_enabled',
        'ref_percent_faucet',
        'ref_percent_ptc',
        'ref_percent_shortlinks',
        'ref_percent_video',
        'ref_percent_tasks',
        'ref_percent_offerwall',
        'ref_percent_deposits'
    ];

    try {
        $pdo->beginTransaction();

        if (isset($_POST['site_name'])) {
            $update_stmt->execute(['site_name', trim(htmlspecialchars($_POST['site_name']))]);
        }
        if (isset($_POST['logo_type']) && in_array($_POST['logo_type'], ['text','image'])) {
            $update_stmt->execute(['logo_type', $_POST['logo_type']]);
        }
        if (isset($_POST['logo_text1'])) {
            $update_stmt->execute(['logo_text1', trim(htmlspecialchars($_POST['logo_text1']))]);
        }
        if (isset($_POST['logo_text2'])) {
            $update_stmt->execute(['logo_text2', trim(htmlspecialchars($_POST['logo_text2']))]);
        }

        if (isset($_POST['faucetpay_api_key'])) {
            $update_stmt->execute(['faucetpay_api_key', trim($_POST['faucetpay_api_key'])]);
        }
        if (isset($_POST['min_withdrawal'])) {
            $val = max(0, floatval($_POST['min_withdrawal']));
            $update_stmt->execute(['min_withdrawal', number_format($val, 8, '.', '')]);
        }
        $instant_w = isset($_POST['instant_withdrawals_enabled']) ? '1' : '0';
        $update_stmt->execute(['instant_withdrawals_enabled', $instant_w]);

        if (isset($_POST['admin_login_captcha'])) {
            $allowed_admin_captchas = ['none', 'google', 'turnstile'];
            $admin_login_captcha = in_array($_POST['admin_login_captcha'], $allowed_admin_captchas) ? $_POST['admin_login_captcha'] : 'none';
            $update_stmt->execute(['admin_login_captcha', $admin_login_captcha]);
        }

        $social_e = isset($_POST['social_enabled']) ? '1' : '0';
        $update_stmt->execute(['social_enabled', $social_e]);
        if (isset($_POST['social_facebook'])) {
            $update_stmt->execute(['social_facebook', trim($_POST['social_facebook'])]);
        }
        if (isset($_POST['social_instagram'])) {
            $update_stmt->execute(['social_instagram', trim($_POST['social_instagram'])]);
        }
        if (isset($_POST['social_twitter'])) {
            $update_stmt->execute(['social_twitter', trim($_POST['social_twitter'])]);
        }
        if (isset($_POST['social_telegram'])) {
            $update_stmt->execute(['social_telegram', trim($_POST['social_telegram'])]);
        }

        $referrals_enabled = isset($_POST['referrals_enabled']) ? '1' : '0';
        $update_stmt->execute(['referrals_enabled', $referrals_enabled]);

        foreach ($allowed_settings as $key) {
            if ($key !== 'referrals_enabled' && isset($_POST[$key])) {
                $val = max(0, min(100, floatval($_POST[$key])));
                $update_stmt->execute([$key, $val]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

include 'includes/header.php';
?>

        <main class="flex-1 p-4 lg:p-8 w-full">
            <div class="max-w-7xl mx-auto space-y-8">

<div class="mb-8">
    <h2 class="text-2xl font-bold text-zinc-100 tracking-tight">General Settings</h2>
    <p class="text-zinc-500 mt-1">Manage global site configurations and modules.</p>
</div>

<form id="settings-form" class="space-y-8 w-full">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden flex flex-col">
            <div class="border-b border-white/10 px-6 py-4 bg-white/[0.03] flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center">
                    <i class="fa-solid fa-globe"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-zinc-100">General Site Settings</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Site name, logo style, and branding.</p>
                </div>
            </div>
            <div class="p-5 space-y-5 flex-1">
                <div>
                    <label class="block text-sm font-semibold text-zinc-300 mb-1.5">Site Name</label>
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($site['site_name']); ?>" placeholder="e.g. USDTFaucet"
                        class="w-full bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition">
                    <p class="text-xs text-zinc-500 mt-1">Used in titles, footer, and throughout the site.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-300 mb-2">Logo Type</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="logo_type" value="text" <?php echo $site['logo_type'] === 'text' ? 'checked' : ''; ?> class="accent-emerald-500" data-logo-type-radio>
                            <span class="text-sm text-zinc-300"><i class="fa-solid fa-font mr-1 text-primary"></i> Text</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="logo_type" value="image" <?php echo $site['logo_type'] === 'image' ? 'checked' : ''; ?> class="accent-emerald-500" data-logo-type-radio id="logo_type_image">
                            <span class="text-sm text-zinc-300"><i class="fa-solid fa-image mr-1 text-primary"></i> Image</span>
                        </label>
                    </div>
                </div>
                <div id="logo-text-options" class="<?php echo $site['logo_type'] === 'image' ? 'hidden' : ''; ?> space-y-3 pl-4 border-l-2 border-primary/25">
                    <p class="text-xs text-zinc-500">Two-tone: <strong class="text-zinc-100"><?php echo htmlspecialchars($site['logo_text1']); ?></strong><span class="text-primary font-bold" id="preview-text2"><?php echo htmlspecialchars($site['logo_text2']); ?></span></p>
                    <div class="flex gap-3">
                        <input type="text" name="logo_text1" value="<?php echo htmlspecialchars($site['logo_text1']); ?>" maxlength="20" placeholder="USDT"
                            class="bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 outline-none focus:border-primary/60 focus:ring-primary/20 w-32">
                        <input type="text" name="logo_text2" value="<?php echo htmlspecialchars($site['logo_text2']); ?>" maxlength="20" placeholder="Faucet"
                            class="bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 outline-none focus:border-primary/60 focus:ring-primary/20 w-32">
                    </div>
                    <div class="flex items-center gap-1 text-xs text-zinc-500">Preview: <span class="font-bold text-zinc-100 ml-1" id="preview-text1"><?php echo htmlspecialchars($site['logo_text1']); ?></span><span class="font-bold text-primary" id="preview-text2b"><?php echo htmlspecialchars($site['logo_text2']); ?></span></div>
                </div>
                <div id="logo-image-options" class="<?php echo $site['logo_type'] === 'text' ? 'hidden' : ''; ?> space-y-3 pl-4 border-l-2 border-primary/25">
                    <?php if (!empty($site['logo_image'])): ?>
                    <img src="<?php echo htmlspecialchars($site['logo_image']); ?>" alt="Logo" class="h-10 object-contain border border-white/10 rounded-lg p-1 bg-white/[0.03]" id="current-logo-preview">
                    <?php else: ?>
                    <p class="text-xs text-zinc-500 italic">No image yet.</p>
                    <img src="" alt="" class="h-10 object-contain hidden" id="current-logo-preview">
                    <?php endif; ?>
                    <label class="cursor-pointer bg-primary/10 hover:bg-primary/20 border border-primary/25 text-primary font-semibold py-1.5 px-3 rounded-lg text-xs flex items-center gap-2 w-fit">
                        <i class="fa-solid fa-upload"></i> Upload Logo
                        <input type="file" id="logo-file-input" accept="image/*" class="hidden" data-logo-file>
                    </label>
                    <span id="logo-upload-status" class="text-xs text-zinc-500 block"></span>
                    <input type="hidden" name="logo_image" id="logo_image_value" value="<?php echo htmlspecialchars($site['logo_image']); ?>">
                </div>

                <hr class="border-white/10 my-2">

                <div>
                    <label class="block text-sm font-semibold text-zinc-300 mb-1.5 flex items-center justify-between">
                        Admin Login Captcha
                    </label>
                    <select name="admin_login_captcha" class="w-full bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2 text-sm text-zinc-200 focus:border-primary/60 focus:ring-primary/20 outline-none">
                        <option value="none" <?php echo ($settings['admin_login_captcha'] ?? 'none') == 'none' ? 'selected' : ''; ?>>None</option>
                        <?php if (($settings['recaptcha_enabled'] ?? '0') == '1'): ?>
                            <option value="google" <?php echo ($settings['admin_login_captcha'] ?? '') == 'google' ? 'selected' : ''; ?>>Google reCAPTCHA</option>
                        <?php endif; ?>
                        <?php if (($settings['turnstile_enabled'] ?? '0') == '1'): ?>
                            <option value="turnstile" <?php echo ($settings['admin_login_captcha'] ?? '') == 'turnstile' ? 'selected' : ''; ?>>Cloudflare Turnstile</option>
                        <?php endif; ?>
                    </select>
                    <p class="text-xs text-zinc-500 mt-1">Protect your admin login with a captcha.</p>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden flex flex-col">
            <div class="border-b border-white/10 px-6 py-4 bg-white/[0.03] flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center">
                    <i class="fa-solid fa-link"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-zinc-100">FaucetPay Settings</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Payout API and withdrawal configuration.</p>
                </div>
            </div>
            <div class="p-5 space-y-5 flex-1">

                <div>
                    <label class="block text-sm font-semibold text-zinc-300 mb-1.5">FaucetPay API Key</label>
                    <div class="relative">
                        <input type="password" name="faucetpay_api_key" id="fp-api-key"
                            value="<?php echo htmlspecialchars($settings['faucetpay_api_key'] ?? ''); ?>"
                            class="w-full bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 pr-10 focus:border-primary/60 focus:ring-primary/20 outline-none transition font-mono">
                        <button type="button" data-toggle-pass="fp-api-key" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-300">
                            <i class="fa-solid fa-eye text-xs"></i>
                        </button>
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Used to send USDT payouts to your users. Get your API key from FaucetPay → Manage → API &amp; Webhooks (<a href="https://faucetpay.io" target="_blank" class="text-primary hover:underline">https://faucetpay.io</a>).</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-zinc-300 mb-1.5">Minimum Withdrawal (USDT)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="min_withdrawal" step="0.00000001" min="0"
                            value="<?php echo number_format(floatval($settings['min_withdrawal'] ?? 0.01), 8, '.', ''); ?>"
                            class="w-44 bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition font-mono">
                        <span class="text-sm text-zinc-500 font-medium">USDT</span>
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Minimum balance required before a user can withdraw.</p>
                </div>

                <div class="flex items-center justify-between p-4 bg-white/[0.03] rounded-lg border border-white/10">
                    <div>
                        <p class="text-sm font-semibold text-zinc-300">Instant Withdrawals</p>
                        <p class="text-xs text-zinc-500 mt-0.5">If OFF, requests go to admin for manual approval.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="instant_withdrawals_enabled" class="sr-only peer"
                            <?php echo ($settings['instant_withdrawals_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-white/10 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>

            </div>
        </div>

    </div>

    <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
        <div class="border-b border-white/10 px-6 py-5 bg-white/[0.03] flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-zinc-100"><i class="fa-solid fa-users-viewfinder text-primary mr-2"></i> Referrals Module</h3>
                <p class="text-sm text-zinc-500 mt-1">Configure global referral commissions and toggle the module.</p>
            </div>

            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="referrals_enabled" class="sr-only peer" <?php echo ($settings['referrals_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                <span class="ml-3 text-sm font-medium text-zinc-300">Enable Module</span>
            </label>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Faucet Claims Percent (%)</label>
                    <div class="relative">
                        <input type="number" step="0.1" name="ref_percent_faucet" value="<?php echo htmlspecialchars($settings['ref_percent_faucet'] ?? '10'); ?>" class="w-full pl-4 pr-10 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-zinc-500"><i class="fa-solid fa-percent"></i></span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">PTC Claims Percent (%)</label>
                    <div class="relative">
                        <input type="number" step="0.1" name="ref_percent_ptc" value="<?php echo htmlspecialchars($settings['ref_percent_ptc'] ?? '5'); ?>" class="w-full pl-4 pr-10 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-zinc-500"><i class="fa-solid fa-percent"></i></span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Shortlinks Claims Percent (%)</label>
                    <div class="relative">
                        <input type="number" step="0.1" name="ref_percent_shortlinks" value="<?php echo htmlspecialchars($settings['ref_percent_shortlinks'] ?? '5'); ?>" class="w-full pl-4 pr-10 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-zinc-500"><i class="fa-solid fa-percent"></i></span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Video Percent (%)</label>
                    <div class="relative">
                        <input type="number" step="0.1" name="ref_percent_video" value="<?php echo htmlspecialchars($settings['ref_percent_video'] ?? '10'); ?>" class="w-full pl-4 pr-10 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-zinc-500"><i class="fa-solid fa-percent"></i></span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Tasks Completions Percent (%)</label>
                    <div class="relative">
                        <input type="number" step="0.1" name="ref_percent_tasks" value="<?php echo htmlspecialchars($settings['ref_percent_tasks'] ?? '10'); ?>" class="w-full pl-4 pr-10 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-zinc-500"><i class="fa-solid fa-percent"></i></span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Offerwall Completions Percent (%)</label>
                    <div class="relative">
                        <input type="number" step="0.1" name="ref_percent_offerwall" value="<?php echo htmlspecialchars($settings['ref_percent_offerwall'] ?? '5'); ?>" class="w-full pl-4 pr-10 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-zinc-500"><i class="fa-solid fa-percent"></i></span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Deposits Percent (%)</label>
                    <div class="relative">
                        <input type="number" step="0.1" name="ref_percent_deposits" value="<?php echo htmlspecialchars($settings['ref_percent_deposits'] ?? '0'); ?>" class="w-full pl-4 pr-10 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-zinc-500"><i class="fa-solid fa-percent"></i></span>
                        </div>
                    </div>
                </div>

            </div>
            <p class="text-xs text-zinc-500 mt-4"><i class="fa-solid fa-info-circle"></i> Set a percentage to 0 to hide it from the user dashboard.</p>
        </div>
    </div>

    <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden mb-8">
        <div class="p-6 border-b border-white/10 bg-white/[0.03] flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary/10 border border-primary/25 text-primary rounded-lg flex items-center justify-center text-lg">
                    <i class="fa-solid fa-share-nodes"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-zinc-100">Social Media Links</h2>
                    <p class="text-sm text-zinc-500">Configure footer social icons</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="social_enabled" value="1" class="sr-only peer" <?php echo (isset($settings['social_enabled']) && $settings['social_enabled'] == '1') ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                <span class="ml-3 text-sm font-medium text-zinc-300">Enable</span>
            </label>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Facebook URL</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-brands fa-facebook text-blue-400"></i>
                        </div>
                        <input type="url" name="social_facebook" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? 'https://facebook.com'); ?>" class="w-full pl-10 pr-4 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Instagram URL</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-brands fa-instagram text-pink-400"></i>
                        </div>
                        <input type="url" name="social_instagram" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? 'https://instagram.com'); ?>" class="w-full pl-10 pr-4 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Twitter (X) URL</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-brands fa-twitter text-zinc-400"></i>
                        </div>
                        <input type="url" name="social_twitter" value="<?php echo htmlspecialchars($settings['social_twitter'] ?? 'https://twitter.com'); ?>" class="w-full pl-10 pr-4 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Telegram URL</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-brands fa-telegram text-blue-400"></i>
                        </div>
                        <input type="url" name="social_telegram" value="<?php echo htmlspecialchars($settings['social_telegram'] ?? 'https://telegram.org'); ?>" class="w-full pl-10 pr-4 py-2.5 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end pt-4">
        <button type="submit" id="save-btn" class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-8 rounded-lg transition-colors flex items-center gap-2">
            <i class="fa-solid fa-save"></i> Save Settings
        </button>
    </div>
</form>

            </div><!-- /max-w-7xl -->
        </main>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-settings.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
