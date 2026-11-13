<?php
include 'includes/header.php';
?>

        <main class="flex-1 p-4 lg:p-8 w-full">
            <div class="max-w-7xl mx-auto space-y-8">

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-100 tracking-tight">Manage Users</h1>
            <p class="text-sm text-zinc-500 mt-1">View, edit, or delete registered users.</p>
        </div>
    </div>

    <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
        <div class="p-6 border-b border-white/10 flex justify-between items-center">
            <h3 class="text-lg font-bold text-zinc-100 flex items-center gap-2">
                <i class="fa-solid fa-users text-primary"></i> All Users
            </h3>
            <div class="flex items-center gap-4">
                <select id="status-filter" class="px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-sm text-zinc-200 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="highrisk">High Risk</option>
                </select>
                <div class="relative">
                    <input type="text" id="search-input" placeholder="Search by ID, Email or Username..." class="pl-10 pr-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-sm text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all w-64">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500"></i>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto table-scroll">
            <table class="w-full text-left border-collapse whitespace-nowrap text-sm">
                <thead>
                    <tr class="text-zinc-400 text-xs uppercase tracking-wider border-b border-white/10">
                        <th class="px-6 py-4 font-medium">ID</th>
                        <th class="px-6 py-4 font-medium">Username / Address</th>
                        <th class="px-6 py-4 font-medium">Risk</th>
                        <th id="balance-sort-toggle" class="px-6 py-4 font-medium cursor-pointer hover:text-primary transition-colors">Balance <i id="balance-sort-icon" class="fa-solid fa-sort ml-1 text-zinc-500"></i></th>
                        <th class="px-6 py-4 font-medium">Status</th>
                        <th class="px-6 py-4 font-medium">Join Date</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table-body" class="divide-y divide-white/10 text-sm">
                    <tr><td colspan="7" class="px-6 py-8 text-center text-zinc-500"><i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Loading users...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-white/10 flex items-center justify-between bg-surface-card" id="pagination-container">
            <p class="text-sm text-zinc-500" id="pagination-info">Showing 0 of 0</p>
            <div class="flex gap-2" id="pagination-buttons"></div>
        </div>
    </div>
</div>

            </div><!-- /max-w-7xl -->
        </main>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-users.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
