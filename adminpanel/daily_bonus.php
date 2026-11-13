<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_ROUTE . '/login');
    exit;
}
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'toggle_enabled') {
            $enabled = isset($_POST['daily_bonus_enabled']) && $_POST['daily_bonus_enabled'] == '1' ? '1' : '0';
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('daily_bonus_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$enabled, $enabled]);
            echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
            exit;
        }

        if ($action === 'add_day' || $action === 'edit_day') {
            $id = $_POST['id'] ?? null;
            $day_number = intval($_POST['day_number'] ?? 0);
            $reward_usdt = floatval($_POST['reward_usdt'] ?? 0);
            $min_condition_usdt = floatval($_POST['min_condition_usdt'] ?? 0);

            if ($day_number <= 0) throw new Exception("Day number must be > 0.");
            if ($reward_usdt <= 0) throw new Exception("Reward must be > 0.");
            if ($min_condition_usdt < 0) throw new Exception("Min condition cannot be negative.");

            if ($action === 'add_day') {
                $stmt = $pdo->prepare("INSERT INTO daily_bonuses (day_number, reward_usdt, min_condition_usdt) VALUES (?, ?, ?)");
                $stmt->execute([$day_number, $reward_usdt, $min_condition_usdt]);
                echo json_encode(['success' => true, 'message' => 'Day added successfully.']);
            } else {
                $stmt = $pdo->prepare("UPDATE daily_bonuses SET day_number = ?, reward_usdt = ?, min_condition_usdt = ? WHERE id = ?");
                $stmt->execute([$day_number, $reward_usdt, $min_condition_usdt, $id]);
                echo json_encode(['success' => true, 'message' => 'Day updated successfully.']);
            }
            exit;
        }

        if ($action === 'delete_day') {
            $id = $_POST['id'] ?? null;
            if (!$id) throw new Exception("Invalid ID.");
            $stmt = $pdo->prepare("DELETE FROM daily_bonuses WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Day deleted successfully.']);
            exit;
        }

        throw new Exception("Unknown action.");
    } catch (Exception $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Duplicate entry') !== false) $msg = "A rule for this day number already exists.";
        echo json_encode(['success' => false, 'message' => $msg]);
    }
    exit;
}

include 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM daily_bonuses ORDER BY day_number ASC");
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);

$s_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'daily_bonus_enabled'");
$is_enabled = $s_stmt->fetchColumn() !== '0';
?>

<div class="p-6 lg:p-8">
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-zinc-100 tracking-tight">Daily Bonus Configuration</h2>
            <p class="text-zinc-500 mt-1">Configure rewards and daily earning requirements to encourage retention.</p>
        </div>
        <div>
            <label class="relative inline-flex items-center cursor-pointer bg-surface-card px-4 py-2 rounded-lg border border-white/10">
                <input type="checkbox" id="toggle-enabled" class="sr-only peer" <?php echo $is_enabled ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[10px] after:left-[18px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                <span class="ml-3 text-sm font-bold text-zinc-300">Module Enabled</span>
            </label>
        </div>
    </div>

    <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden mb-8">
        <div class="p-6 border-b border-white/10 bg-white/[0.03] flex justify-between items-center">
            <h3 class="text-lg font-bold text-zinc-100"><i class="fa-solid fa-calendar-check text-primary mr-2"></i> Bonus Days</h3>
            <button data-open-day-modal class="bg-primary hover:bg-primaryHover text-zinc-950 text-sm font-bold py-2 px-4 rounded-lg transition-colors">
                <i class="fa-solid fa-plus mr-1"></i> Add Day
            </button>
        </div>

        <div class="overflow-x-auto table-scroll">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-white/[0.03] border-b border-white/10 text-zinc-400 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium">Day Number</th>
                        <th class="px-6 py-4 font-medium">Reward (USDT)</th>
                        <th class="px-6 py-4 font-medium">Min Earned Condition (USDT)</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10 text-sm text-zinc-300">
                    <?php if (empty($days)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-zinc-500">
                            <i class="fa-solid fa-ghost text-4xl mb-3 block"></i>
                            No days configured yet.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($days as $day): ?>
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-4 font-bold text-zinc-100">Day <?php echo $day['day_number']; ?></td>
                            <td class="px-6 py-4 font-semibold text-primary font-mono">+<?php echo rtrim(rtrim($day['reward_usdt'], '0'), '.'); ?> USDT</td>
                            <td class="px-6 py-4 text-zinc-500">
                                <?php if($day['min_condition_usdt'] > 0): ?>
                                    Must earn <?php echo rtrim(rtrim($day['min_condition_usdt'], '0'), '.'); ?> USDT today
                                <?php else: ?>
                                    <span class="text-xs bg-white/5 text-zinc-400 border border-white/10 px-2 py-1 rounded">No Condition</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button data-open-day-modal data-day='<?php echo json_encode($day); ?>' class="text-zinc-300 hover:text-primary bg-white/5 hover:bg-white/10 border border-white/10 p-2 rounded-lg transition-colors mr-2">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button data-delete-day="<?php echo $day['id']; ?>" class="text-red-400 hover:text-red-300 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 p-2 rounded-lg transition-colors">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="day-modal" class="fixed inset-0 bg-black/60 z-[100] hidden items-center justify-center backdrop-blur-sm">
    <div class="bg-surface-card rounded-lg border border-white/10 shadow-soft w-full max-w-md overflow-hidden transform transition-all">
        <div class="p-6 border-b border-white/10 bg-white/[0.03] flex items-center justify-between">
            <h3 class="text-lg font-bold text-zinc-100" id="modal-title">Add Day</h3>
            <button type="button" data-close-modal class="text-zinc-500 hover:text-zinc-300 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form id="day-form" class="p-6">
            <input type="hidden" name="action" id="form-action" value="add_day">
            <input type="hidden" name="id" id="form-id" value="">

            <div class="mb-4">
                <label class="block text-sm font-bold text-zinc-300 mb-1">Day Number</label>
                <input type="number" name="day_number" id="form-day" min="1" step="1" required class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <p class="text-xs text-zinc-500 mt-1">Example: 1 for Day 1</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-zinc-300 mb-1">Reward (USDT)</label>
                <input type="number" name="reward_usdt" id="form-reward" min="0.00000001" step="0.00000001" required class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-zinc-300 mb-1">Min Condition (USDT)</label>
                <input type="number" name="min_condition_usdt" id="form-min" min="0" step="0.00000001" value="0" required class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                <p class="text-xs text-zinc-500 mt-1">How much USDT the user must earn today to claim this. 0 for no limit.</p>
            </div>

            <button type="submit" id="save-day-btn" class="w-full bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 rounded-lg transition-colors">
                Save Day
            </button>
        </form>
    </div>
</div>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-daily-bonus.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
