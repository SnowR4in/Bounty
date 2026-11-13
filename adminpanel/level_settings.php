<?php
require_once '../includes/db_connect.php';

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'level_%'");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    try {
        $pdo->beginTransaction();

        $level_system_enabled = isset($_POST['level_system_enabled']) ? '1' : '0';
        $update_stmt->execute(['level_system_enabled', $level_system_enabled]);

        $simple_settings = ['level_bonus_timed', 'level_bonus_instant', 'level_bonus_ptc', 'level_bonus_shortlink', 'level_bonus_offerwall'];
        foreach ($simple_settings as $key) {
            if (isset($_POST[$key])) {
                $val = max(0, floatval($_POST[$key]));
                $update_stmt->execute([$key, $val]);
            }
        }

        $video_tiers = [];
        if (isset($_POST['video_amounts']) && is_array($_POST['video_amounts'])) {
            for ($i = 0; $i < count($_POST['video_amounts']); $i++) {
                $video_tiers[] = [
                    'amount' => max(0, floatval($_POST['video_amounts'][$i])),
                    'percent' => max(0, floatval($_POST['video_percents'][$i]))
                ];
            }
        }
        $update_stmt->execute(['level_bonus_video', json_encode($video_tiers)]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to update settings.']);
    }
    exit;
}

$video_json = $settings['level_bonus_video'] ?? '[{"amount":0.5,"percent":20},{"amount":2.0,"percent":40},{"amount":100.0,"percent":75}]';

include 'includes/header.php';
?>

<main class="flex-1 p-4 lg:p-8 w-full">
    <div class="max-w-7xl mx-auto space-y-8">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-zinc-100 tracking-tight">Level System Settings</h2>
            <p class="text-zinc-500 mt-1">Configure XP gain and tier progression.</p>
        </div>

        <form id="level-settings-form" class="space-y-6">
            <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
                <div class="border-b border-white/10 px-6 py-5 bg-white/[0.03] flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-100"><i class="fa-solid fa-ranking-star text-primary mr-2"></i> Level System</h3>
                        <p class="text-sm text-zinc-500 mt-1">Enable or disable the leveling system. If disabled, level progression is hidden.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="level_system_enabled" class="sr-only peer" <?php echo ($settings['level_system_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>
            </div>

            <div class="bg-primary/10 text-zinc-300 p-4 rounded-lg border border-primary/25 text-sm">
                <i class="fa-solid fa-circle-info mr-2"></i>
                <strong>How it works:</strong> The "Percent" you define below means "Percentage of the XP needed for the NEXT level". For example, if a user needs 500 XP to level up, and you set a bonus to 10%, the user will gain 50 XP upon completing that task.
            </div>

            <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10 bg-white/[0.03]">
                    <h3 class="text-base font-bold text-zinc-100">Standard Modules XP</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">Timed Faucet Level Bonus Percent (%)</label>
                        <input type="number" step="0.1" min="0" name="level_bonus_timed" value="<?php echo htmlspecialchars($settings['level_bonus_timed'] ?? '2'); ?>" class="w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">Instant Faucet Level Bonus Percent (%)</label>
                        <input type="number" step="0.1" min="0" name="level_bonus_instant" value="<?php echo htmlspecialchars($settings['level_bonus_instant'] ?? '1'); ?>" class="w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">PTC Completion Level Bonus Percent (%)</label>
                        <input type="number" step="0.1" min="0" name="level_bonus_ptc" value="<?php echo htmlspecialchars($settings['level_bonus_ptc'] ?? '5'); ?>" class="w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">Shortlink Completion Level Bonus Percent (%)</label>
                        <input type="number" step="0.1" min="0" name="level_bonus_shortlink" value="<?php echo htmlspecialchars($settings['level_bonus_shortlink'] ?? '5'); ?>" class="w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">Offerwall Completion Level Bonus Percent (%)</label>
                        <input type="number" step="0.1" min="0" name="level_bonus_offerwall" value="<?php echo htmlspecialchars($settings['level_bonus_offerwall'] ?? '5'); ?>" class="w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none font-mono">
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10 bg-white/[0.03] flex justify-between items-center">
                    <h3 class="text-base font-bold text-zinc-100">Video Completion XP Tiers</h3>
                    <button type="button" data-add-tier="video" class="bg-primary hover:bg-primaryHover text-zinc-950 px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors">
                        <i class="fa-solid fa-plus mr-1"></i> Add Tier
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-sm text-zinc-500 mb-4">Define XP gained based on the video reward amount. If the reward is UP TO the "Amount", the user gets the specified XP percent. Tiers are evaluated from top to bottom, so order them ascending.</p>
                    <div id="video_tiers_container" class="space-y-3"></div>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" id="save-btn" class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-8 rounded-lg transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</main>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE, 'tiers' => ['video' => json_decode($video_json, true) ?: []]]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-settings.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
