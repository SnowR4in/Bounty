<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/site_settings.php';
$settings = get_site_settings($pdo);

$owapi_tasks_on = isset($settings['offerwallapi_tasks_enabled']) && $settings['offerwallapi_tasks_enabled'] == '1';
$owme_tasks_on = isset($settings['offerwall_tasks_enabled']) && $settings['offerwall_tasks_enabled'] == '1';
if (!$owapi_tasks_on && !$owme_tasks_on) {
    header('Location: /user/');
    exit;
}

$user_id = intval($_SESSION['user_id']);

function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

$userIP = getUserIP();

$tasks = [];
$approved = [];

if ($owapi_tasks_on) {
    $feed = owapi_fetch_feed('task', $settings, $user_id, $userIP);
    $tasks = $feed['items'];
    $approved = $feed['approved'];
}

if ($owme_tasks_on) {
    foreach (owme_fetch_list('taskapi.php', $settings, (string)$user_id, $userIP) as $t) {
        $tasks[] = $t;
    }
}

$error_msg = empty($tasks) ? "No Tasks available at the moment. Please check back later." : null;

include 'includes/header.php';

$page_data = [
    'tasks' => $tasks,
    'approvedIds' => array_map('strval', $approved)
];
?>

<div class="relative z-10 pt-24 pb-16 min-h-screen">
    <div class="container-custom">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <div class="section-label">Earn</div>
                <h1 class="text-3xl font-extrabold text-zinc-100 tracking-tight mt-2">Tasks</h1>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:w-auto">
                    <select id="sortFilter" class="appearance-none w-full sm:w-auto bg-white/[0.03] border border-white/10 text-zinc-200 px-4 py-2 pr-8 rounded-lg text-sm font-medium hover:bg-white/5 transition-colors outline-none cursor-pointer focus:border-primary/60">
                        <option value="desc">Reward High to Low</option>
                        <option value="asc">Reward Low to High</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-zinc-500">
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </div>
                </div>
            </div>
        </div>

        <div id="ad-container-general_top_ads" class="flex justify-center empty:hidden w-full min-h-[90px] mb-8"></div>

        <?php if ($error_msg): ?>
            <div class="bg-surface-card rounded-lg border border-white/10 p-12 text-center">
                <i class="fa-solid fa-list-check text-5xl text-zinc-600 mb-4 block"></i>
                <h2 class="text-xl font-bold text-zinc-100 mb-2">No Tasks Available</h2>
                <p class="text-zinc-500">There are currently no tasks available for your region. Please check back later!</p>
            </div>
        <?php else: ?>
            <div id="tasksGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6"></div>

            <div id="noTasksMatch" class="hidden bg-surface-card rounded-lg border border-white/10 p-12 text-center mt-6">
                <i class="fa-solid fa-filter-circle-xmark text-5xl text-zinc-600 mb-4 block"></i>
                <h2 class="text-xl font-bold text-zinc-100 mb-2">No Tasks Match</h2>
                <p class="text-zinc-500">Try changing your filter settings to see more tasks.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="taskModal" class="fixed inset-0 z-[200] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-task-modal-close></div>
    <div class="relative z-10 flex items-center justify-center min-h-screen p-4">
        <div class="bg-surface-card rounded-lg shadow-2xl border border-white/10 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1 min-w-0">
                        <h3 id="taskModalTitle" class="text-lg font-bold text-zinc-100 leading-tight"></h3>
                        <p class="text-primary font-extrabold text-sm mt-1 font-mono">+<span id="taskModalReward"></span> USDT</p>
                    </div>
                    <button data-task-modal-close class="flex-shrink-0 w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 text-zinc-400 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="mb-4">
                    <div class="text-xs uppercase tracking-wider font-bold text-zinc-500 mb-2">Instructions</div>
                    <div id="taskModalInstructions" class="text-sm text-zinc-300 whitespace-pre-line bg-white/[0.03] rounded-lg p-4 border border-white/10"></div>
                </div>

                <div id="taskModalRequirementsWrap" class="mb-4 hidden">
                    <div class="text-xs uppercase tracking-wider font-bold text-zinc-500 mb-2">Requirements</div>
                    <div id="taskModalRequirements" class="text-sm text-zinc-300 whitespace-pre-line"></div>
                </div>

                <a id="taskModalOpenBtn" href="#" target="_blank" rel="noopener noreferrer" class="block w-full bg-primary hover:bg-primaryHover text-zinc-950 text-center font-bold py-3 px-6 rounded-lg transition-all mb-4">
                    <i class="fa-solid fa-arrow-up-right-from-square mr-2"></i>Open Task Page
                </a>

                <div class="mb-4">
                    <label for="proofInput" id="taskModalProofLabel" class="block text-sm font-bold text-zinc-300 mb-2">Submit Proof</label>
                    <div id="taskModalProofContainer"></div>
                </div>

                <div id="taskModalMessage" class="hidden text-sm rounded-lg px-4 py-3 mb-4"></div>

                <button id="taskModalSubmitBtn" class="w-full bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-6 rounded-lg transition-all">
                    <i class="fa-solid fa-paper-plane mr-2"></i>Submit Proof
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container-custom pb-8"><div id="ad-container-general_bottom_ads" class="flex justify-center empty:hidden w-full min-h-[90px]"></div></div>

<script>window.OW_PAGE_DATA = <?php echo json_encode($page_data, JSON_HEX_TAG); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/pages/user-tasks.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
