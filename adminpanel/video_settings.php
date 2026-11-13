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
        $keys = ['video_enabled', 'offerwallapi_video_enabled'];

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");

        foreach ($keys as $key) {
            $val = isset($_POST[$key]) && $_POST[$key] == '1' ? '1' : '0';
            $stmt->execute([$key, $val, $val]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Video configuration saved successfully.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

include 'includes/header.php';

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('video_enabled', 'offerwallapi_video_enabled', 'offerwallapi_api_key')");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$offerwallapi_configured = !empty($settings['offerwallapi_api_key']);
?>

<div class="p-6 lg:p-8">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-zinc-100 tracking-tight">Video Integration</h2>
        <p class="text-zinc-500 mt-1">Paid videos are provided by the OfferwallApi Video feed.</p>
    </div>

    <form id="video-form" class="rounded-lg border border-white/10 bg-surface-card p-6 md:p-8">
        <div class="mb-8 pb-8 border-b border-white/10 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-zinc-100">Module Status</h3>
                <p class="text-sm text-zinc-500">Enable or disable the Video page for users.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="video_enabled" value="1" class="sr-only peer" <?php echo ($settings['video_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
        </div>

        <div class="mb-8 pb-8 border-b border-white/10 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-zinc-100">OfferwallApi Video Feed</h3>
                <p class="text-sm text-zinc-500 mt-1">Serve videos from the OfferwallApi Video feed.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="offerwallapi_video_enabled" value="1" class="sr-only peer" <?php echo ($settings['offerwallapi_video_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
        </div>

        <div class="p-4 rounded-lg border border-primary/25 bg-primary/10 text-sm text-zinc-300">
            <i class="fa-solid fa-circle-info mr-1"></i>
            Video rewards come from the OfferwallApi Video feed. Configure keys in <a href="<?php echo ADMIN_ROUTE; ?>/ptc_settings" class="font-bold underline hover:text-primary">PTC settings</a>.
            The referral percent for videos is set in <a href="<?php echo ADMIN_ROUTE; ?>/general_settings" class="font-bold underline hover:text-primary">General settings</a>.
            <?php if ($offerwallapi_configured): ?>
                <span class="ml-1 inline-flex items-center gap-1 bg-primary/15 text-primary px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-check"></i> API key configured</span>
            <?php else: ?>
                <span class="ml-1 inline-flex items-center gap-1 bg-amber-500/10 text-amber-400 border border-amber-500/25 px-2 py-0.5 rounded-full text-xs font-bold"><i class="fa-solid fa-triangle-exclamation"></i> API key not configured</span>
            <?php endif; ?>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" id="save-btn" class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-8 rounded-lg transition-colors flex items-center gap-2">
                <i class="fa-solid fa-save"></i> Save Configuration
            </button>
        </div>
    </form>
</div>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-settings.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
