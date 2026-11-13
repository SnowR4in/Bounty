<?php
include 'includes/header.php';
require_once '../includes/db_connect.php';
?>

        <main class="flex-1 p-4 lg:p-8 w-full">
            <div class="max-w-7xl mx-auto space-y-8">

<div class="flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-zinc-100 tracking-tight">Withdrawal Requests</h1>
        <p class="text-sm text-zinc-500 mt-1">Review, approve, or reject user withdrawal requests.</p>
    </div>
    <div class="flex gap-3">
        <span id="pending-badge" class="hidden items-center gap-2 bg-amber-500/10 text-amber-400 border border-amber-500/25 text-xs font-bold px-3 py-1.5 rounded-full">
            <i class="fa-solid fa-clock"></i> <span id="pending-count" class="font-mono">0</span> Pending
        </span>
    </div>
</div>

<div class="rounded-lg border border-white/10 bg-surface-card p-4 flex flex-wrap gap-3 items-center">
    <div class="flex gap-2">
        <button data-filter="all" class="filter-btn px-4 py-2 rounded-lg text-xs font-semibold border border-primary bg-primary text-zinc-950">All</button>
        <button data-filter="pending" class="filter-btn px-4 py-2 rounded-lg text-xs font-semibold border border-white/10 bg-white/5 text-zinc-300 hover:border-primary/40 hover:text-primary transition">Pending</button>
        <button data-filter="approved" class="filter-btn px-4 py-2 rounded-lg text-xs font-semibold border border-white/10 bg-white/5 text-zinc-300 hover:border-primary/40 hover:text-primary transition">Approved</button>
        <button data-filter="rejected" class="filter-btn px-4 py-2 rounded-lg text-xs font-semibold border border-white/10 bg-white/5 text-zinc-300 hover:border-primary/40 hover:text-primary transition">Rejected</button>
    </div>
    <div class="ml-auto">
        <input type="text" id="search-input" placeholder="Search email or TxID..."
            class="border border-white/10 bg-white/[0.03] rounded-lg px-4 py-2 text-sm text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none w-64">
    </div>
</div>

<div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden">
    <div class="overflow-x-auto table-scroll">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs font-semibold text-zinc-400 uppercase tracking-wide bg-white/[0.03]">
                    <th class="px-5 py-3 text-left">User</th>
                    <th class="px-5 py-3 text-left">FaucetPay Email</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-left">TxID</th>
                    <th class="px-5 py-3 text-left">Date</th>
                    <th class="px-5 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="withdrawals-tbody" class="divide-y divide-white/10">
                <tr><td colspan="7" class="px-5 py-10 text-center text-zinc-600"><i class="fa-solid fa-spinner fa-spin text-2xl"></i></td></tr>
            </tbody>
        </table>
    </div>
    <div id="pagination" class="px-5 py-4 border-t border-white/10 flex items-center justify-between hidden">
        <p class="text-xs text-zinc-500" id="page-info"></p>
        <div id="page-buttons" class="flex gap-2"></div>
    </div>
</div>

            </div><!-- /max-w-7xl -->
        </main>

<div id="approve-modal" class="fixed inset-0 bg-black/60 z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-surface-card rounded-lg border border-white/10 shadow-soft w-full max-w-md p-6 space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center"><i class="fa-solid fa-check"></i></div>
            <h3 class="text-lg font-bold text-zinc-100">Manual Approve</h3>
        </div>
        <p class="text-sm text-zinc-500">Enter the Transaction ID after manually sending USDT to the user via FaucetPay.</p>
        <div>
            <label class="block text-xs font-semibold text-zinc-400 mb-1.5">Transaction ID (TxID)</label>
            <input type="text" id="approve-txid" placeholder="FaucetPay payout ID or transaction hash..."
                class="w-full bg-white/[0.03] border border-white/10 rounded-lg px-4 py-2.5 text-sm text-zinc-200 placeholder-zinc-600 font-mono focus:border-primary/60 focus:ring-primary/20 outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-zinc-400 mb-1.5">Note (optional)</label>
            <input type="text" id="approve-note" placeholder="Internal note..."
                class="w-full bg-white/[0.03] border border-white/10 rounded-lg px-4 py-2.5 text-sm text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
        </div>
        <input type="hidden" id="approve-wid">
        <div class="flex gap-3 pt-2">
            <button data-close-modal class="flex-1 py-2.5 rounded-lg border border-white/10 bg-white/5 text-sm font-semibold text-zinc-300 hover:bg-white/10 transition">Cancel</button>
            <button id="submit-approve" class="flex-1 py-2.5 rounded-lg bg-primary hover:bg-primaryHover text-zinc-950 text-sm font-bold transition">Mark Approved</button>
        </div>
    </div>
</div>

<div id="auto-modal" class="fixed inset-0 bg-black/60 z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-surface-card rounded-lg border border-white/10 shadow-soft w-full max-w-md p-6 space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/25 text-primary flex items-center justify-center"><i class="fa-solid fa-bolt"></i></div>
            <h3 class="text-lg font-bold text-zinc-100">Confirm FaucetPay Payout</h3>
        </div>
        <p class="text-sm text-zinc-500">This will automatically send USDT to the user via the FaucetPay API using your configured API key.</p>
        <input type="hidden" id="auto-wid">
        <div class="flex gap-3 pt-2">
            <button data-close-modal class="flex-1 py-2.5 rounded-lg border border-white/10 bg-white/5 text-sm font-semibold text-zinc-300 hover:bg-white/10 transition">Cancel</button>
            <button id="submit-auto" class="flex-1 py-2.5 rounded-lg bg-primary hover:bg-primaryHover text-zinc-950 text-sm font-bold transition">Pay via FaucetPay</button>
        </div>
    </div>
</div>

<div id="reject-modal" class="fixed inset-0 bg-black/60 z-[100] hidden flex items-center justify-center p-4">
    <div class="bg-surface-card rounded-lg border border-white/10 shadow-soft w-full max-w-md p-6 space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 flex items-center justify-center"><i class="fa-solid fa-undo"></i></div>
            <h3 class="text-lg font-bold text-zinc-100">Return & Reject</h3>
        </div>
        <p class="text-sm text-zinc-500">This will return the USDT to the user's balance and mark the request as rejected.</p>
        <div>
            <label class="block text-xs font-semibold text-zinc-400 mb-1.5">Reason (optional)</label>
            <input type="text" id="reject-note" placeholder="Reason for rejection..."
                class="w-full bg-white/[0.03] border border-white/10 rounded-lg px-4 py-2.5 text-sm text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
        </div>
        <input type="hidden" id="reject-wid">
        <div class="flex gap-3 pt-2">
            <button data-close-modal class="flex-1 py-2.5 rounded-lg border border-white/10 bg-white/5 text-sm font-semibold text-zinc-300 hover:bg-white/10 transition">Cancel</button>
            <button id="submit-reject" class="flex-1 py-2.5 rounded-lg bg-red-500 hover:bg-red-400 text-white text-sm font-bold transition">Return Balance</button>
        </div>
    </div>
</div>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-withdrawals.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
