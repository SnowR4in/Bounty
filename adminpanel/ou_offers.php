<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_ROUTE . '/login');
    exit;
}
if (empty($_SESSION['ou_csrf'])) {
    $_SESSION['ou_csrf'] = bin2hex(random_bytes(16));
}
ou_ensure_offers_table($pdo);

/* ----------------------------- AJAX actions ----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['ou_csrf'], (string)$token)) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Reload the page.']);
        exit;
    }
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'import':
                [$offers, $err] = ou_parse_offers_payload((string)($_POST['payload'] ?? ''), (string)($_POST['provider'] ?? ''));
                if ($err) { echo json_encode(['success' => false, 'message' => $err]); break; }
                $r = ou_import_offers($pdo, $offers, ($_POST['enable_new'] ?? '') === '1');
                echo json_encode(['success' => true, 'message' => "Imported: {$r['added']} new, {$r['updated']} updated."]);
                break;

            case 'toggle':
                $id = (int)($_POST['id'] ?? 0);
                $val = ($_POST['value'] ?? '') === '1' ? 1 : 0;
                $pdo->prepare("UPDATE ou_offers SET enabled = ? WHERE id = ?")->execute([$val, $id]);
                echo json_encode(['success' => true, 'message' => $val ? 'Offer enabled.' : 'Offer disabled.']);
                break;

            case 'toggle_live':
                $id = (int)($_POST['id'] ?? 0);
                $val = ($_POST['value'] ?? '') === '1' ? 1 : 0;
                $pdo->prepare("UPDATE ou_offers SET is_live = ? WHERE id = ?")->execute([$val, $id]);
                echo json_encode(['success' => true, 'message' => $val ? 'Marked LIVE.' : 'Removed from live.']);
                break;

            case 'bulk':
                $ids = array_filter(array_map('intval', (array)($_POST['ids'] ?? [])));
                $op = $_POST['op'] ?? '';
                if (!$ids) { echo json_encode(['success' => false, 'message' => 'Select at least one offer.']); break; }
                $in = implode(',', array_fill(0, count($ids), '?'));
                if ($op === 'enable' || $op === 'disable') {
                    $pdo->prepare("UPDATE ou_offers SET enabled = " . ($op === 'enable' ? 1 : 0) . " WHERE id IN ($in)")->execute($ids);
                } elseif ($op === 'delete') {
                    $pdo->prepare("DELETE FROM ou_offers WHERE id IN ($in)")->execute($ids);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Unknown bulk action.']); break;
                }
                echo json_encode(['success' => true, 'message' => 'Done (' . count($ids) . ' offers).']);
                break;

            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $type = in_array($_POST['reward_type'] ?? '', ['fixed', 'up_to', 'varies'], true) ? $_POST['reward_type'] : 'fixed';
                $reward = trim((string)($_POST['reward'] ?? ''));
                $rewardVal = ($reward === '' || (float)$reward <= 0) ? null : (float)$reward;
                if ($rewardVal === null) $type = 'varies';
                $title = trim((string)($_POST['title'] ?? ''));
                if ($title === '') { echo json_encode(['success' => false, 'message' => 'Title is required.']); break; }
                $pdo->prepare("UPDATE ou_offers SET title=?, description=?, reward=?, reward_type=?, loi=?, sort_order=? WHERE id=?")
                    ->execute([
                        substr($title, 0, 255),
                        substr((string)($_POST['description'] ?? ''), 0, 500),
                        $rewardVal, $type,
                        ((int)($_POST['loi'] ?? 0)) ?: null,
                        (int)($_POST['sort_order'] ?? 0),
                        $id,
                    ]);
                echo json_encode(['success' => true, 'message' => 'Offer updated.']);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

include 'includes/header.php';

$offers = $pdo->query("SELECT * FROM ou_offers ORDER BY enabled DESC, is_live DESC, sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$en = 0;
foreach ($offers as $o) { if ($o['enabled']) $en++; }
$walls = ou_survey_walls();
$inputCls = 'w-full px-3 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 outline-none font-mono text-sm';
$toggleInner = "w-11 h-6 bg-white/10 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white/20 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary";
?>

<div class="p-6 lg:p-8" id="ou-offers-root" data-csrf="<?php echo htmlspecialchars($_SESSION['ou_csrf']); ?>">
    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-zinc-100 tracking-tight">Survey Offers</h2>
            <p class="text-zinc-500 mt-1">Paste offer data from OpinionUniverse (Dynata, Bitlab, CPX). Imported offers start <b>disabled</b> — switch on the ones you want users to see.</p>
        </div>
        <a href="<?php echo ADMIN_ROUTE; ?>/offerwall_settings" class="text-sm text-primary font-semibold"><i class="fa-solid fa-gear mr-1"></i>Wall &amp; feature switches</a>
    </div>

    <!-- Paste box -->
    <div class="rounded-lg border border-white/10 bg-surface-card p-6 mb-8">
        <h3 class="text-lg font-bold text-zinc-100 mb-1">Paste offers</h3>
        <p class="text-xs text-zinc-500 mb-3">Paste a JSON array, a single JSON object, or the whole API response (<code>{"offers":[...]}</code> / <code>{"data":[...]}</code>). Recognised fields: id / offer_id, title / name, reward / payout / pubpayout, loi, description, image, url, provider, is_live. Existing offers (same provider + id) are updated, not duplicated.</p>
        <textarea id="ou-payload" rows="9" class="<?php echo $inputCls; ?>" placeholder='[{"offer_id":"3063128","title":"Dynata Surveys","reward":0.5,"loi":10,"provider":"dynata"}]'></textarea>
        <div class="mt-3 flex flex-col md:flex-row md:items-center gap-3">
            <select id="ou-provider" class="<?php echo $inputCls; ?> md:w-56">
                <option value="">Provider: auto-detect</option>
                <?php foreach ($walls as $w): ?>
                    <option value="<?php echo htmlspecialchars($w['slug']); ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                <?php endforeach; ?>
                <option value="other">Other</option>
            </select>
            <label class="flex items-center gap-2 text-sm text-zinc-400"><input type="checkbox" id="ou-enable-new" class="accent-emerald-500"> Enable new offers immediately</label>
            <button id="ou-import-btn" class="md:ml-auto bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-2.5 px-6 rounded-lg transition-colors"><i class="fa-solid fa-file-import mr-2"></i>Import</button>
        </div>
    </div>

    <!-- List -->
    <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
        <div class="p-4 border-b border-white/10 flex flex-wrap items-center gap-3">
            <span class="text-sm text-zinc-400"><b class="text-zinc-100"><?php echo count($offers); ?></b> offers · <b class="text-primary"><?php echo $en; ?></b> enabled</span>
            <div class="ml-auto flex flex-wrap gap-2">
                <button data-bulk="enable" class="bg-white/5 hover:bg-white/10 border border-white/10 text-zinc-200 text-xs font-bold py-2 px-3 rounded-lg">Enable selected</button>
                <button data-bulk="disable" class="bg-white/5 hover:bg-white/10 border border-white/10 text-zinc-200 text-xs font-bold py-2 px-3 rounded-lg">Disable selected</button>
                <button data-bulk="delete" class="bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-red-400 text-xs font-bold py-2 px-3 rounded-lg">Delete selected</button>
            </div>
        </div>

        <?php if (!$offers): ?>
            <div class="p-12 text-center text-zinc-500">No offers yet. Paste some above.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-zinc-500 border-b border-white/10">
                    <tr>
                        <th class="p-3 w-8"><input type="checkbox" id="ou-select-all" class="accent-emerald-500"></th>
                        <th class="p-3 text-left">Offer</th>
                        <th class="p-3 text-left">Provider</th>
                        <th class="p-3 text-left">Reward</th>
                        <th class="p-3 text-center">Live</th>
                        <th class="p-3 text-center">Enabled</th>
                        <th class="p-3 text-right">Edit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                <?php foreach ($offers as $o): ?>
                    <tr data-row="<?php echo (int)$o['id']; ?>"
                        data-json="<?php echo htmlspecialchars(json_encode([
                            'id' => (int)$o['id'], 'title' => $o['title'], 'description' => $o['description'],
                            'reward' => $o['reward'], 'reward_type' => $o['reward_type'],
                            'loi' => $o['loi'], 'sort_order' => (int)$o['sort_order'],
                        ]), ENT_QUOTES, 'UTF-8'); ?>">
                        <td class="p-3"><input type="checkbox" class="ou-sel accent-emerald-500" value="<?php echo (int)$o['id']; ?>"></td>
                        <td class="p-3">
                            <div class="font-semibold text-zinc-100"><?php echo htmlspecialchars($o['title']); ?></div>
                            <div class="text-xs text-zinc-500 font-mono">ID <?php echo htmlspecialchars($o['offer_id']); ?><?php echo $o['loi'] ? ' · ' . (int)$o['loi'] . ' min' : ''; ?></div>
                        </td>
                        <td class="p-3 text-zinc-400"><?php echo htmlspecialchars($o['provider']); ?></td>
                        <td class="p-3 font-mono text-primary"><?php echo htmlspecialchars(ou_reward_label($o['reward'], $o['reward_type'])); ?></td>
                        <td class="p-3 text-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer ou-live" data-id="<?php echo (int)$o['id']; ?>" <?php echo $o['is_live'] ? 'checked' : ''; ?>>
                                <div class="<?php echo $toggleInner; ?>"></div>
                            </label>
                        </td>
                        <td class="p-3 text-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer ou-toggle" data-id="<?php echo (int)$o['id']; ?>" <?php echo $o['enabled'] ? 'checked' : ''; ?>>
                                <div class="<?php echo $toggleInner; ?>"></div>
                            </label>
                        </td>
                        <td class="p-3 text-right"><button class="ou-edit text-zinc-400 hover:text-primary"><i class="fa-solid fa-pen-to-square"></i></button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit modal -->
<div id="ou-edit-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/70 p-4">
    <div class="w-full max-w-lg rounded-lg border border-white/10 bg-surface-card p-6">
        <h3 class="text-lg font-bold text-zinc-100 mb-4">Edit offer</h3>
        <input type="hidden" id="ed-id">
        <div class="space-y-3">
            <input id="ed-title" class="<?php echo $inputCls; ?>" placeholder="Title">
            <textarea id="ed-desc" rows="2" class="<?php echo $inputCls; ?>" placeholder="Description"></textarea>
            <div class="grid grid-cols-2 gap-3">
                <input id="ed-reward" type="number" step="0.000001" min="0" class="<?php echo $inputCls; ?>" placeholder="Reward (USDT)">
                <select id="ed-type" class="<?php echo $inputCls; ?>">
                    <option value="fixed">Fixed reward</option>
                    <option value="up_to">Up to</option>
                    <option value="varies">Varies</option>
                </select>
                <input id="ed-loi" type="number" min="0" class="<?php echo $inputCls; ?>" placeholder="Minutes (LOI)">
                <input id="ed-sort" type="number" class="<?php echo $inputCls; ?>" placeholder="Sort order (0 = first)">
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button id="ed-cancel" class="bg-white/5 border border-white/10 text-zinc-300 font-bold py-2 px-4 rounded-lg">Cancel</button>
            <button id="ed-save" class="bg-primary text-zinc-950 font-bold py-2 px-5 rounded-lg">Save</button>
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById('ou-offers-root');
    var csrf = root.dataset.csrf;

    function post(data) {
        var fd = new FormData();
        fd.append('csrf', csrf);
        Object.keys(data).forEach(function (k) {
            if (Array.isArray(data[k])) data[k].forEach(function (v) { fd.append(k + '[]', v); });
            else fd.append(k, data[k]);
        });
        return fetch(location.pathname, { method: 'POST', body: fd }).then(function (r) { return r.json(); });
    }
    function done(res, reload) {
        showToast(res.success ? 'success' : 'error', res.message);
        if (res.success && reload) setTimeout(function () { location.reload(); }, 600);
    }

    document.getElementById('ou-import-btn').addEventListener('click', function () {
        post({
            action: 'import',
            payload: document.getElementById('ou-payload').value,
            provider: document.getElementById('ou-provider').value,
            enable_new: document.getElementById('ou-enable-new').checked ? '1' : '0'
        }).then(function (r) { done(r, true); }).catch(function () { showToast('error', 'Request failed.'); });
    });

    document.querySelectorAll('.ou-toggle').forEach(function (el) {
        el.addEventListener('change', function () {
            post({ action: 'toggle', id: el.dataset.id, value: el.checked ? '1' : '0' })
                .then(function (r) { if (!r.success) el.checked = !el.checked; done(r, false); });
        });
    });
    document.querySelectorAll('.ou-live').forEach(function (el) {
        el.addEventListener('change', function () {
            post({ action: 'toggle_live', id: el.dataset.id, value: el.checked ? '1' : '0' })
                .then(function (r) { if (!r.success) el.checked = !el.checked; done(r, false); });
        });
    });

    var all = document.getElementById('ou-select-all');
    if (all) all.addEventListener('change', function () {
        document.querySelectorAll('.ou-sel').forEach(function (c) { c.checked = all.checked; });
    });
    document.querySelectorAll('[data-bulk]').forEach(function (b) {
        b.addEventListener('click', function () {
            var ids = Array.prototype.map.call(document.querySelectorAll('.ou-sel:checked'), function (c) { return c.value; });
            if (b.dataset.bulk === 'delete' && !confirm('Delete ' + ids.length + ' offer(s)?')) return;
            post({ action: 'bulk', op: b.dataset.bulk, ids: ids }).then(function (r) { done(r, true); });
        });
    });

    var modal = document.getElementById('ou-edit-modal');
    function openModal(o) {
        document.getElementById('ed-id').value = o.id;
        document.getElementById('ed-title').value = o.title || '';
        document.getElementById('ed-desc').value = o.description || '';
        document.getElementById('ed-reward').value = o.reward || '';
        document.getElementById('ed-type').value = o.reward_type || 'fixed';
        document.getElementById('ed-loi').value = o.loi || '';
        document.getElementById('ed-sort').value = o.sort_order || 0;
        modal.classList.remove('hidden'); modal.classList.add('flex');
    }
    function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }
    document.querySelectorAll('.ou-edit').forEach(function (b) {
        b.addEventListener('click', function () {
            openModal(JSON.parse(b.closest('tr').dataset.json));
        });
    });
    document.getElementById('ed-cancel').addEventListener('click', closeModal);
    document.getElementById('ed-save').addEventListener('click', function () {
        post({
            action: 'update',
            id: document.getElementById('ed-id').value,
            title: document.getElementById('ed-title').value,
            description: document.getElementById('ed-desc').value,
            reward: document.getElementById('ed-reward').value,
            reward_type: document.getElementById('ed-type').value,
            loi: document.getElementById('ed-loi').value,
            sort_order: document.getElementById('ed-sort').value
        }).then(function (r) { closeModal(); done(r, true); });
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
