<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    require_once __DIR__ . '/../includes/db_connect.php';
    session_start();
    if (!isset($_SESSION['admin_logged_in'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    require_once __DIR__ . '/../includes/db_connect.php';

    $user_id = intval($_GET['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare("SELECT faucetpay_email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u_ajax = $stmt->fetch();

    if ($u_ajax && in_array($action, ['delete_sec_logs', 'delete_fp_logs', 'delete_mov_logs', 'delete_login_logs'])) {
        $msg = '';
        if ($action === 'delete_sec_logs') {
            $pdo->prepare("DELETE FROM security_logs WHERE user_id = ?")->execute([$user_id]);
            $msg = 'Security alerts deleted!';
        } elseif ($action === 'delete_fp_logs') {
            $pdo->prepare("DELETE FROM user_fingerprints WHERE user_id = ?")->execute([$user_id]);
            $msg = 'Fingerprints deleted!';
        } elseif ($action === 'delete_mov_logs') {
            $pdo->prepare("DELETE FROM movement_logs WHERE user_id = ?")->execute([$user_id]);
            $msg = 'Movement logs deleted!';
        } elseif ($action === 'delete_login_logs') {
            $pdo->prepare("DELETE FROM login_logs WHERE email = ?")->execute([$u_ajax['faucetpay_email']]);
            $msg = 'Login history deleted!';
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }
}

include 'includes/header.php';

$user_id = intval($_GET['id'] ?? 0);
if ($user_id <= 0) {
    echo "<script>window.location.href='" . ADMIN_ROUTE . "/users';</script>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

if (!$u) {
    echo "<main class='flex-1 p-8'><h1 class='text-2xl font-bold text-red-400'>User not found.</h1></main>";
    include 'includes/footer.php';
    exit;
}

$toast = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'edit_user') {
        $username = trim($_POST['username'] ?? '');
        $balance = floatval($_POST['balance'] ?? 0);
        $energy = intval($_POST['energy'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        $is_shadow_banned = intval($_POST['is_shadow_banned'] ?? 0);
        $account_status = $_POST['account_status'] ?? 'active';

        try {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, balance = ?, energy = ?, status = ?, is_shadow_banned = ?, account_status = ? WHERE id = ?");
            $stmt->execute([$username, $balance, $energy, $status, $is_shadow_banned, $account_status, $user_id]);
            $u['username'] = $username; $u['balance'] = $balance; $u['energy'] = $energy; $u['status'] = $status; $u['is_shadow_banned'] = $is_shadow_banned; $u['account_status'] = $account_status;
            $toast = ['type' => 'success', 'message' => 'User updated successfully!'];
        } catch (Exception $e) {
            $toast = ['type' => 'error', 'message' => 'Error updating user.'];
        }
    }
}

$limit = 10;
$p_login = max(1, intval($_GET['p_login'] ?? 1));
$p_sec   = max(1, intval($_GET['p_sec'] ?? 1));
$p_mov   = max(1, intval($_GET['p_mov'] ?? 1));
$p_fp    = max(1, intval($_GET['p_fp'] ?? 1));

function render_pagination($current_page, $total_records, $limit, $param_name) {
    $total_pages = ceil($total_records / $limit);
    if ($total_pages <= 1) return '';

    $html = '<div class="px-4 py-3 border-t border-white/10 flex items-center justify-between bg-white/[0.03]">';
    $html .= '<span class="text-xs text-zinc-500">Page '.$current_page.' of '.$total_pages.'</span>';
    $html .= '<div class="flex gap-2">';

    $params = $_GET;
    if ($current_page > 1) {
        $params[$param_name] = $current_page - 1;
        $url = '?' . http_build_query($params);
        $html .= '<a href="'.$url.'" class="px-3 py-1 bg-white/5 border border-white/10 text-zinc-300 rounded text-xs hover:bg-white/10 transition-colors">Prev</a>';
    }
    if ($current_page < $total_pages) {
        $params[$param_name] = $current_page + 1;
        $url = '?' . http_build_query($params);
        $html .= '<a href="'.$url.'" class="px-3 py-1 bg-white/5 border border-white/10 text-zinc-300 rounded text-xs hover:bg-white/10 transition-colors">Next</a>';
    }
    $html .= '</div></div>';
    return $html;
}

$t_login = $pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE email = ?"); $t_login->execute([$u['faucetpay_email']]); $t_login = $t_login->fetchColumn();
$t_sec   = $pdo->prepare("SELECT COUNT(*) FROM security_logs WHERE user_id = ?"); $t_sec->execute([$user_id]); $t_sec = $t_sec->fetchColumn();
$t_mov   = $pdo->prepare("SELECT COUNT(*) FROM movement_logs WHERE user_id = ?"); $t_mov->execute([$user_id]); $t_mov = $t_mov->fetchColumn();
$t_fp    = $pdo->prepare("SELECT COUNT(*) FROM user_fingerprints WHERE user_id = ?"); $t_fp->execute([$user_id]); $t_fp = $t_fp->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM login_logs WHERE email = ? ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $u['faucetpay_email']);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, ($p_login - 1) * $limit, PDO::PARAM_INT);
$stmt->execute();
$login_logs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM security_logs WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $user_id);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, ($p_sec - 1) * $limit, PDO::PARAM_INT);
$stmt->execute();
$security_logs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM movement_logs WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $user_id);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, ($p_mov - 1) * $limit, PDO::PARAM_INT);
$stmt->execute();
$movement_logs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM user_fingerprints WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $user_id);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, ($p_fp - 1) * $limit, PDO::PARAM_INT);
$stmt->execute();
$fingerprints = $stmt->fetchAll();

?>

<main class="flex-1 p-4 lg:p-8 w-full">
    <div class="max-w-7xl mx-auto space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-100 tracking-tight flex items-center gap-2">
                    <a href="<?= ADMIN_ROUTE ?>/users" class="text-zinc-500 hover:text-primary transition-colors"><i class="fa-solid fa-arrow-left"></i></a>
                    Edit User #<?php echo $user_id; ?>
                </h1>
                <p class="text-sm text-zinc-500 mt-1">Manage user account details, balance, and view security logs.</p>
            </div>
            <div>
                <a href="<?= ADMIN_ROUTE ?>/users" class="bg-white/5 hover:bg-white/10 border border-white/10 text-zinc-200 font-semibold py-2 px-4 rounded-lg transition-colors text-sm">
                    Back to Users
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-1 space-y-6">
                <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
                    <div class="p-4 border-b border-white/10 bg-white/[0.03]">
                        <h3 class="font-bold text-zinc-100"><i class="fa-solid fa-user-edit text-primary mr-2"></i> Account Details</h3>
                    </div>
                    <form method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="action" value="edit_user">

                        <div>
                            <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1">FaucetPay Email</label>
                            <input type="text" value="<?php echo htmlspecialchars($u['faucetpay_email']); ?>" readonly class="w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-sm text-zinc-500 outline-none font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1">Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($u['username'] ?? ''); ?>" class="w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-sm text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1">Balance (USDT)</label>
                            <input type="text" name="balance" value="<?php echo number_format($u['balance'], 6, '.', ''); ?>" class="w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-sm text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1">Energy</label>
                            <input type="number" name="energy" value="<?php echo $u['energy']; ?>" class="w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-sm text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-1">Login Status</label>
                            <select name="status" class="w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-sm text-zinc-200 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                                <option value="1" <?php if($u['status'] == 1) echo 'selected'; ?>>Active</option>
                                <option value="0" <?php if($u['status'] == 0) echo 'selected'; ?>>Banned (Cannot login)</option>
                            </select>
                        </div>

                        <div class="p-3 bg-red-500/10 rounded-lg border border-red-500/25 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-red-400 uppercase tracking-wider mb-1">Shadow Ban</label>
                                <select name="is_shadow_banned" class="w-full px-3 py-2 bg-white/[0.03] border border-red-500/25 rounded-lg text-sm text-zinc-200 focus:ring-red-500/20 focus:border-red-500/60 outline-none transition-all">
                                    <option value="0" <?php if($u['is_shadow_banned'] == 0) echo 'selected'; ?>>No</option>
                                    <option value="1" <?php if($u['is_shadow_banned'] == 1) echo 'selected'; ?>>Yes (Ghost claims, no withdrawals)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-red-400 uppercase tracking-wider mb-1">Risk Status</label>
                                <select name="account_status" class="w-full px-3 py-2 bg-white/[0.03] border border-red-500/25 rounded-lg text-sm text-zinc-200 focus:ring-red-500/20 focus:border-red-500/60 outline-none transition-all">
                                    <option value="active" <?php if($u['account_status'] === 'active') echo 'selected'; ?>>Active</option>
                                    <option value="highrisk" <?php if($u['account_status'] === 'highrisk') echo 'selected'; ?>>High Risk</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-red-400 uppercase tracking-wider mb-1">Risk Score</label>
                                <div class="font-bold text-red-400 text-lg font-mono"><?php echo $u['risk_score']; ?> / 100</div>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-4 rounded-lg transition-colors">
                            <i class="fa-solid fa-save mr-2"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">

                <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
                    <div class="p-4 border-b border-white/10 bg-red-500/10 flex items-center justify-between">
                        <h3 class="font-bold text-red-400"><i class="fa-solid fa-shield-halved mr-2"></i> Security Alerts (Last 10)</h3>
                        <button type="button" data-delete-logs="delete_sec_logs" data-confirm="Delete all security alerts for this user?" class="text-xs bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 px-2 py-1 rounded font-bold transition-colors">Delete Alerts</button>
                    </div>
                    <div class="p-0">
                        <?php if (empty($security_logs)): ?>
                            <p class="p-4 text-sm text-zinc-500 text-center">No security alerts triggered.</p>
                        <?php else: ?>
                            <ul class="divide-y divide-white/10">
                                <?php foreach($security_logs as $log):
                                    $color = match($log['severity']) {
                                        'low' => 'text-blue-400 bg-blue-500/10',
                                        'medium' => 'text-amber-400 bg-amber-500/10',
                                        'high' => 'text-amber-300 bg-amber-500/15',
                                        'critical' => 'text-red-400 bg-red-500/10',
                                        default => 'text-zinc-400 bg-white/5'
                                    };
                                ?>
                                <li class="p-4 flex flex-col gap-1">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded uppercase <?php echo $color; ?>"><?php echo $log['severity']; ?></span>
                                        <span class="text-xs text-zinc-500"><?php echo $log['created_at']; ?></span>
                                    </div>
                                    <p class="text-sm font-medium text-zinc-200"><?php echo htmlspecialchars($log['reason']); ?></p>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <?php echo render_pagination($p_sec, $t_sec, $limit, 'p_sec'); ?>
                </div>

                <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
                    <div class="p-4 border-b border-white/10 bg-white/[0.03] flex items-center justify-between">
                        <h3 class="font-bold text-zinc-100"><i class="fa-solid fa-fingerprint text-primary mr-2"></i> Device Fingerprints</h3>
                        <button type="button" data-delete-logs="delete_fp_logs" data-confirm="Delete all fingerprints for this user?" class="text-xs bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 px-2 py-1 rounded font-bold transition-colors">Delete Fingerprints</button>
                    </div>
                    <div class="overflow-x-auto table-scroll">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white/[0.03] text-zinc-400 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-2">Date</th>
                                    <th class="px-4 py-2">HW Hash (Device)</th>
                                    <th class="px-4 py-2">Canvas Hash</th>
                                    <th class="px-4 py-2">Trusted Token</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <?php if (empty($fingerprints)): ?>
                                    <tr><td colspan="4" class="px-4 py-4 text-center text-zinc-500">No fingerprint data.</td></tr>
                                <?php else: ?>
                                    <?php foreach($fingerprints as $fp): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-zinc-500 whitespace-nowrap text-xs"><?php echo $fp['created_at']; ?></td>
                                        <td class="px-4 py-3 font-mono text-xs font-bold text-primary"><?php echo htmlspecialchars($fp['hw_hash']); ?></td>
                                        <td class="px-4 py-3 font-mono text-xs text-zinc-400"><?php echo htmlspecialchars($fp['fp_hash']); ?></td>
                                        <td class="px-4 py-3 font-mono text-xs text-zinc-500"><?php echo htmlspecialchars($fp['trusted_token'] ?? '-'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php echo render_pagination($p_fp, $t_fp, $limit, 'p_fp'); ?>
                </div>

                <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
                    <div class="p-4 border-b border-white/10 bg-white/[0.03] flex items-center justify-between">
                        <h3 class="font-bold text-zinc-100"><i class="fa-solid fa-computer-mouse text-primary mr-2"></i> Suspicious Movements</h3>
                        <button type="button" data-delete-logs="delete_mov_logs" data-confirm="Delete all suspicious movement logs for this user?" class="text-xs bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 px-2 py-1 rounded font-bold transition-colors">Delete Logs</button>
                    </div>
                    <div class="overflow-x-auto table-scroll">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white/[0.03] text-zinc-400 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-2">Date</th>
                                    <th class="px-4 py-2">Risk</th>
                                    <th class="px-4 py-2">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <?php if (empty($movement_logs)): ?>
                                    <tr><td colspan="3" class="px-4 py-4 text-center text-zinc-500">No suspicious movements recorded.</td></tr>
                                <?php else: ?>
                                    <?php foreach($movement_logs as $ml):
                                        $rc = match($ml['risk_level']) {
                                            'high' => 'text-red-400 font-bold',
                                            'medium' => 'text-amber-400 font-bold',
                                            default => 'text-zinc-400'
                                        };
                                    ?>
                                    <tr>
                                        <td class="px-4 py-3 text-zinc-500 whitespace-nowrap text-xs"><?php echo $ml['created_at']; ?></td>
                                        <td class="px-4 py-3 uppercase text-xs <?php echo $rc; ?>"><?php echo $ml['risk_level']; ?></td>
                                        <td class="px-4 py-3 text-zinc-300"><?php echo htmlspecialchars($ml['details']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php echo render_pagination($p_mov, $t_mov, $limit, 'p_mov'); ?>
                </div>

                <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
                    <div class="p-4 border-b border-white/10 bg-white/[0.03] flex items-center justify-between">
                        <h3 class="font-bold text-zinc-100"><i class="fa-solid fa-right-to-bracket text-primary mr-2"></i> Login History</h3>
                        <button type="button" data-delete-logs="delete_login_logs" data-confirm="Delete all login history for this user?" class="text-xs bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 px-2 py-1 rounded font-bold transition-colors">Delete Logs</button>
                    </div>
                    <div class="overflow-x-auto table-scroll">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white/[0.03] text-zinc-400 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-2">Date</th>
                                    <th class="px-4 py-2">IP Address</th>
                                    <th class="px-4 py-2">Country</th>
                                    <th class="px-4 py-2">ASN</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <?php if (empty($login_logs)): ?>
                                    <tr><td colspan="4" class="px-4 py-4 text-center text-zinc-500">No login logs.</td></tr>
                                <?php else: ?>
                                    <?php foreach($login_logs as $ll): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-zinc-500 whitespace-nowrap text-xs"><?php echo $ll['created_at']; ?></td>
                                        <td class="px-4 py-3 font-mono text-zinc-100"><?php echo htmlspecialchars($ll['ip_address']); ?></td>
                                        <td class="px-4 py-3 text-zinc-400 font-bold"><?php echo htmlspecialchars($ll['country_code'] ?? '-'); ?></td>
                                        <td class="px-4 py-3 text-zinc-500 font-mono text-xs"><?php echo htmlspecialchars($ll['asn'] ?? '-'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php echo render_pagination($p_login, $t_login, $limit, 'p_login'); ?>
                </div>

                <div class="bg-surface-raised border border-white/10 p-5 rounded-lg mt-6">
                    <h4 class="text-sm font-bold text-zinc-100 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-shield-virus text-primary"></i> Anti-Cheat System Overview
                    </h4>
                    <div class="text-xs text-zinc-400 space-y-2 leading-relaxed">
                        <p><strong class="text-zinc-200 font-bold">Banned (Login Status):</strong> The user cannot log in. They will see an access denied message on the login page.</p>
                        <p><strong class="text-zinc-200 font-bold">Shadow Ban:</strong> The user can log in and use the site normally, but their earnings are ignored (ghost claims) and they cannot withdraw funds. They don't know they are banned.</p>
                        <p><strong class="text-zinc-200 font-bold">High Risk:</strong> Automatically flagged by the anti-fraud telemetry system when suspicious hardware, bots, or proxies are detected.</p>
                        <p><strong class="text-zinc-200 font-bold">Risk Score:</strong> A numeric value that increases with each suspicious action. Usually triggers a Shadow Ban when it gets too high.</p>
                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['toast' => $toast]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-edit-user.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
