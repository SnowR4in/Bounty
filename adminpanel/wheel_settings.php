<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . ADMIN_ROUTE . '/login');
    exit;
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update_settings'])) {
    header('Content-Type: application/json');
    $enabled = isset($_POST['wheel_enabled']) && $_POST['wheel_enabled'] == '1' ? '1' : '0';
    $energy = intval($_POST['wheel_energy_cost'] ?? 10);
    if ($energy < 1) $energy = 1;
    $allowed_captchas = ['none', 'google', 'turnstile'];
    $captcha = (isset($_POST['wheel_captcha']) && in_array($_POST['wheel_captcha'], $allowed_captchas)) ? $_POST['wheel_captcha'] : 'none';

    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['wheel_enabled', $enabled]);
        $stmt->execute(['wheel_energy_cost', $energy]);
        $stmt->execute(['wheel_captcha', $captcha]);

        echo json_encode(['success' => true, 'message' => 'Wheel settings saved successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Error: " . $e->getMessage()]);
    }
    exit;
}

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('wheel_enabled', 'wheel_energy_cost', 'wheel_captcha', 'turnstile_enabled', 'recaptcha_enabled')");
$wheel_settings = [];
while ($row = $stmt->fetch()) {
    $wheel_settings[$row['setting_key']] = $row['setting_value'];
}
$wheel_enabled = $wheel_settings['wheel_enabled'] ?? '0';
$wheel_energy_cost = $wheel_settings['wheel_energy_cost'] ?? '10';
$wheel_captcha = $wheel_settings['wheel_captcha'] ?? 'none';

$stmt = $pdo->query("SELECT * FROM wheel_segments ORDER BY display_order ASC");
$segments = $stmt->fetchAll();

$_page_title = 'Wheel of Fortune Settings';
require_once 'includes/header.php';
?>

<div class="p-4 md:p-6 lg:p-8 w-full mx-auto space-y-6">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-100 tracking-tight">Wheel of Fortune</h1>
        <p class="text-zinc-500 mt-2">Configure the wheel requirements and segments.</p>
    </div>

    <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden mb-6">
        <div class="border-b border-white/10 p-6 bg-white/[0.03]">
            <h2 class="text-lg font-bold text-zinc-100 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-primary"></i> General Settings
            </h2>
        </div>
        <div class="p-6">
            <form id="wheelSettingsForm" class="space-y-6">
                <div class="flex items-center justify-between p-4 bg-white/[0.03] rounded-lg border border-white/10">
                    <div>
                        <label for="wheel_enabled" class="font-bold text-zinc-200">Enable Wheel of Fortune</label>
                        <p class="text-sm text-zinc-500">Allow users to access the wheel on their dashboard.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="wheel_enabled" id="wheel_enabled" value="1" class="sr-only peer" <?php echo $wheel_enabled == '1' ? 'checked' : ''; ?>>
                        <div class="w-14 h-7 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>

                <div>
                    <label for="wheel_energy_cost" class="block text-sm font-bold text-zinc-300 mb-2">Energy Cost Per Spin</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-bolt text-amber-400"></i>
                        </div>
                        <input type="number" name="wheel_energy_cost" id="wheel_energy_cost" value="<?php echo htmlspecialchars($wheel_energy_cost); ?>" min="1" step="1" class="block w-full pl-10 pr-3 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 transition-colors text-sm font-medium font-mono" required>
                    </div>
                </div>

                <div>
                    <label for="wheel_captcha" class="block text-sm font-bold text-zinc-300 mb-2">Wheel Captcha</label>
                    <select name="wheel_captcha" id="wheel_captcha" class="w-full px-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                        <option value="none" <?php echo $wheel_captcha == 'none' ? 'selected' : ''; ?>>None</option>
                        <?php if (($wheel_settings['recaptcha_enabled'] ?? '0') == '1'): ?>
                            <option value="google" <?php echo $wheel_captcha == 'google' ? 'selected' : ''; ?>>Google reCAPTCHA</option>
                        <?php endif; ?>
                        <?php if (($wheel_settings['turnstile_enabled'] ?? '0') == '1'): ?>
                            <option value="turnstile" <?php echo $wheel_captcha == 'turnstile' ? 'selected' : ''; ?>>Cloudflare Turnstile</option>
                        <?php endif; ?>
                    </select>
                    <p class="text-[10px] text-zinc-500 mt-1">If enabled, users must solve this captcha when clicking SPIN.</p>
                </div>

                <div class="pt-2">
                    <button type="button" id="saveWheelSettingsBtn" class="w-full bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-6 rounded-lg transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
        <div class="border-b border-white/10 p-6 bg-white/[0.03] flex justify-between items-center">
            <h2 class="text-lg font-bold text-zinc-100 flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-primary"></i> Wheel Segments
            </h2>
            <button data-segment-add class="bg-primary hover:bg-primaryHover text-zinc-950 font-semibold py-2 px-4 rounded-lg transition-colors text-sm flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Add Segment
            </button>
        </div>

        <div class="p-6">
            <p class="text-sm text-zinc-500 mb-4"><i class="fa-solid fa-info-circle text-primary mr-1"></i> The percentages define the weight (chance) of winning. Higher number = higher probability.</p>

            <div class="overflow-x-auto table-scroll">
                <table class="w-full text-sm text-left text-zinc-400">
                    <thead class="text-xs text-zinc-400 uppercase bg-white/[0.03] border-b border-white/10">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium rounded-tl-lg">Order</th>
                            <th scope="col" class="px-6 py-4 font-medium">Color</th>
                            <th scope="col" class="px-6 py-4 font-medium">Reward Type</th>
                            <th scope="col" class="px-6 py-4 font-medium">Reward Value</th>
                            <th scope="col" class="px-6 py-4 font-medium">Win Chance (%)</th>
                            <th scope="col" class="px-6 py-4 font-medium text-right rounded-tr-lg">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="segmentsTableBody">
                        <?php foreach ($segments as $index => $seg): ?>
                        <tr class="bg-surface-card border-b border-white/10 hover:bg-white/[0.02] transition-colors" data-id="<?php echo $seg['id']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <button data-segment-move="<?php echo $seg['id']; ?>" data-segment-direction="up" class="text-zinc-500 hover:text-primary"><i class="fa-solid fa-caret-up"></i></button>
                                    <button data-segment-move="<?php echo $seg['id']; ?>" data-segment-direction="down" class="text-zinc-500 hover:text-primary"><i class="fa-solid fa-caret-down"></i></button>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <input type="color" value="<?php echo htmlspecialchars($seg['color']); ?>" data-segment-id="<?php echo $seg['id']; ?>" data-segment-field="color" class="w-8 h-8 rounded cursor-pointer border-0 p-0 bg-transparent">
                                    <input type="text" value="<?php echo htmlspecialchars($seg['color']); ?>" data-segment-id="<?php echo $seg['id']; ?>" data-segment-field="color" class="w-24 text-xs border border-white/10 rounded px-2 py-1 uppercase font-mono text-zinc-400 bg-white/[0.03] focus:border-primary/60 focus:ring-primary/20 outline-none">
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <select data-segment-id="<?php echo $seg['id']; ?>" data-segment-field="reward_type" class="border border-white/10 rounded px-3 py-1.5 text-sm font-medium text-zinc-300 bg-white/[0.03] focus:border-primary/60 focus:ring-primary/20 outline-none">
                                    <option value="USDT" <?php echo $seg['reward_type'] == 'USDT' ? 'selected' : ''; ?>>USDT</option>
                                    <option value="Energy" <?php echo $seg['reward_type'] == 'Energy' ? 'selected' : ''; ?>>Energy</option>
                                </select>
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" value="<?php echo rtrim(rtrim(sprintf('%.8f', $seg['reward_value']), '0'), '.'); ?>" step="0.00000001" min="0" data-segment-id="<?php echo $seg['id']; ?>" data-segment-field="reward_value" class="w-24 border border-white/10 rounded px-3 py-1.5 text-sm font-medium text-zinc-300 bg-white/[0.03] focus:border-primary/60 focus:ring-primary/20 outline-none font-mono">
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" value="<?php echo rtrim(rtrim(sprintf('%.2f', $seg['win_chance']), '0'), '.'); ?>" step="0.01" min="0" data-segment-id="<?php echo $seg['id']; ?>" data-segment-field="win_chance" class="w-24 border border-white/10 rounded px-3 py-1.5 text-sm font-medium text-zinc-300 bg-white/[0.03] focus:border-primary/60 focus:ring-primary/20 outline-none font-mono">
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button data-segment-delete="<?php echo $seg['id']; ?>" class="text-red-400 hover:text-red-300 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 p-2 rounded-lg transition-colors" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($segments) === 0): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-zinc-500">
                                No segments configured. Add some segments to the wheel!
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/admin/admin-wheel-settings.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>
