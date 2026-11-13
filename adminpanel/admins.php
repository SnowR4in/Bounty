<?php
include 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM admin_users ORDER BY id ASC");
$admins = $stmt->fetchAll();
?>

        <main class="flex-1 p-4 lg:p-8 w-full">
            <div class="w-full space-y-8">

                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-100 tracking-tight">Manage Admins</h1>
                        <p class="text-sm text-zinc-500 mt-1">Add, edit, or delete admin accounts.</p>
                    </div>
                    <button data-open-add-admin class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-2 px-4 rounded-lg transition-colors text-sm">
                        <i class="fa-solid fa-plus mr-1"></i> Add Admin
                    </button>
                </div>

                <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
                    <div class="overflow-x-auto table-scroll">
                        <table class="w-full text-left border-collapse whitespace-nowrap text-sm">
                            <thead>
                                <tr class="bg-white/[0.03] text-zinc-400 text-xs uppercase tracking-wider border-b border-white/10">
                                    <th class="px-6 py-4 font-medium">ID</th>
                                    <th class="px-6 py-4 font-medium">Username</th>
                                    <th class="px-6 py-4 font-medium">Created At</th>
                                    <th class="px-6 py-4 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10 text-sm">
                                <?php foreach ($admins as $ad): ?>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-4 font-semibold text-zinc-100 font-mono">#<?= $ad['id'] ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-primaryDeep to-primary text-zinc-950 flex items-center justify-center font-bold text-xs">
                                                <?= strtoupper(substr($ad['admin_username'], 0, 1)) ?>
                                            </div>
                                            <span class="font-medium text-zinc-100"><?= htmlspecialchars($ad['admin_username']) ?></span>
                                            <?php if ($ad['admin_username'] === 'admin'): ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-primary/10 text-primary border border-primary/25 ml-2">ROOT</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-zinc-500"><?= date('M j, Y H:i', strtotime($ad['created_at'])) ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <button data-open-edit-admin="<?= $ad['id'] ?>" data-admin-username="<?= htmlspecialchars($ad['admin_username'], ENT_QUOTES) ?>" class="text-zinc-300 hover:text-primary bg-white/5 hover:bg-white/10 border border-white/10 p-2 rounded-lg transition-colors mr-2">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <?php if ($ad['admin_username'] !== 'admin'): ?>
                                        <button data-delete-admin="<?= $ad['id'] ?>" class="text-red-400 hover:text-red-300 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 p-2 rounded-lg transition-colors">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button disabled class="text-zinc-600 bg-white/5 border border-white/10 p-2 rounded-lg cursor-not-allowed" title="Root admin cannot be deleted">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>

<div id="adminModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-[99] flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-surface-card rounded-lg border border-white/10 shadow-soft w-full max-w-md transform scale-95 transition-transform duration-300" id="adminModalContent">
        <div class="p-6 border-b border-white/10 flex justify-between items-center">
            <h3 class="text-xl font-bold text-zinc-100" id="modalTitle">Add Admin</h3>
            <button data-close-modal class="text-zinc-500 hover:text-zinc-300 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form id="admin-form" class="p-6">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="admin_id" id="modalAdminId" value="">

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-1">Username</label>
                    <input type="text" name="username" id="modalUsername" required class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                    <p id="usernameHelper" class="text-xs text-amber-400 mt-1 hidden"><i class="fa-solid fa-circle-info"></i> Username cannot be changed for the root admin.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-1">Password</label>
                    <input type="text" name="password" id="modalPassword" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono">
                    <p class="text-xs text-zinc-500 mt-1" id="passwordHelper">Leave empty when editing to keep current password.</p>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <button type="button" data-close-modal class="px-5 py-2 text-sm font-bold text-zinc-300 hover:bg-white/10 border border-white/10 rounded-lg transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm font-bold text-zinc-950 bg-primary hover:bg-primaryHover rounded-lg transition-colors">Save Admin</button>
            </div>
        </form>
    </div>
</div>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-admins.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
