(function () {
    'use strict';

    let currentPage = 1;
    let currentFilter = 'all';
    let searchTimer = null;

    function route(path) {
        const data = window.OW_ADMIN_DATA || {};
        return (data.adminRoute || '') + path;
    }

    function debounceLoad() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            currentPage = 1;
            loadWithdrawals();
        }, 350);
    }

    function filterStatus(status) {
        currentFilter = status;
        currentPage = 1;
        document.querySelectorAll('.filter-btn').forEach(function (b) {
            b.className = b.dataset.filter === status
                ? 'filter-btn px-4 py-2 rounded-lg text-xs font-semibold border border-primary bg-primary text-zinc-950'
                : 'filter-btn px-4 py-2 rounded-lg text-xs font-semibold border border-white/10 bg-white/5 text-zinc-300 hover:border-primary/40 hover:text-primary transition';
        });
        loadWithdrawals();
    }

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    async function loadWithdrawals(page) {
        if (page === undefined) page = currentPage;
        currentPage = page;
        const search = document.getElementById('search-input').value;
        const tbody = document.getElementById('withdrawals-tbody');
        tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-10 text-center text-zinc-600"><i class="fa-solid fa-spinner fa-spin text-2xl"></i></td></tr>';

        const params = new URLSearchParams({ status: currentFilter, page: page, search: search });
        const res = await fetch(route('/ajax_withdrawals?' + params));
        const data = await res.json();

        const badge = document.getElementById('pending-badge');
        const pc = document.getElementById('pending-count');
        if (data.pending_count > 0) {
            badge.classList.remove('hidden');
            badge.classList.add('flex');
            pc.textContent = data.pending_count;
        } else {
            badge.classList.add('hidden');
        }

        if (!data.rows || data.rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-12 text-center text-zinc-500 text-sm">No withdrawal records found.</td></tr>';
            document.getElementById('pagination').classList.add('hidden');
            return;
        }

        const statusBadge = {
            approved: 'bg-primary/10 text-primary border border-primary/25',
            rejected: 'bg-red-500/10 text-red-400 border border-red-500/25',
            pending: 'bg-amber-500/10 text-amber-400 border border-amber-500/25'
        };
        const statusIcon = { approved: 'fa-check-circle', rejected: 'fa-times-circle', pending: 'fa-clock' };

        tbody.innerHTML = data.rows.map(function (w) {
            const txidHtml = w.txid
                ? `<span class="font-mono text-xs text-zinc-400 flex items-center gap-1.5" title="${escHtml(w.txid)}">
                <i class="fa-solid fa-receipt opacity-60"></i>
                ${escHtml(w.txid.length > 24 ? w.txid.substring(0, 16) + '...' + w.txid.slice(-6) : w.txid)}
               </span>`
                : '<span class="text-zinc-600">—</span>';

            const actionBtns = w.status === 'pending' ? `
            <button data-open-auto="${w.id}" class="text-[10px] px-2 py-1 rounded-md bg-primary/10 text-primary border border-primary/25 hover:bg-primary/20 font-semibold transition" title="Pay via FaucetPay">
                <i class="fa-solid fa-bolt"></i> Pay via FaucetPay
            </button>
            <button data-open-approve="${w.id}" class="text-[10px] px-2 py-1 rounded-md bg-white/5 text-zinc-300 border border-white/10 hover:bg-white/10 font-semibold transition" title="Manual TxID">
                <i class="fa-solid fa-check"></i> Manual
            </button>
            <button data-open-reject="${w.id}" class="text-[10px] px-2 py-1 rounded-md bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 font-semibold transition" title="Reject & Return Balance">
                <i class="fa-solid fa-undo"></i> Return
            </button>
            <button data-delete-withdrawal="${w.id}" class="text-[10px] px-2 py-1 rounded-md bg-white/5 text-zinc-500 border border-white/10 hover:bg-white/10 font-semibold transition">
                <i class="fa-solid fa-trash"></i>
            </button>` : `
            <button data-delete-withdrawal="${w.id}" class="text-[10px] px-2 py-1 rounded-md bg-white/5 text-zinc-500 border border-white/10 hover:bg-white/10 font-semibold transition">
                <i class="fa-solid fa-trash"></i>
            </button>`;

            return `<tr class="hover:bg-white/[0.02] transition-colors">
            <td class="px-5 py-3.5">
                <div class="font-semibold text-zinc-100 text-xs">${escHtml(w.username || 'Unknown')}</div>
                <div class="text-zinc-500 text-xs">ID #${w.user_id}</div>
            </td>
            <td class="px-5 py-3.5 font-mono text-xs text-zinc-400 max-w-[160px]">
                <span class="truncate block" title="${escHtml(w.faucetpay_email)}">${escHtml(w.faucetpay_email)}</span>
            </td>
            <td class="px-5 py-3.5 text-right font-mono font-semibold text-primary text-xs whitespace-nowrap">
                ${parseFloat(w.amount).toFixed(6)} <span class="text-primary/70">USDT</span>
            </td>
            <td class="px-5 py-3.5 text-center">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${statusBadge[w.status] || ''}">
                    <i class="fa-solid ${statusIcon[w.status] || ''} text-xs"></i>
                    ${w.status.charAt(0).toUpperCase() + w.status.slice(1)}
                </span>
            </td>
            <td class="px-5 py-3.5">${txidHtml}</td>
            <td class="px-5 py-3.5 text-xs text-zinc-500 whitespace-nowrap">${w.created_at}</td>
            <td class="px-5 py-3.5">
                <div class="flex gap-1.5 justify-center">${actionBtns}</div>
            </td>
        </tr>`;
        }).join('');

        const pg = document.getElementById('pagination');
        const pi = document.getElementById('page-info');
        const pb = document.getElementById('page-buttons');
        if (data.pages > 1) {
            pg.classList.remove('hidden');
            pi.textContent = `Page ${data.page} of ${data.pages}`;
            const start = Math.max(1, data.page - 2);
            const end = Math.min(data.pages, data.page + 2);
            let html = data.page > 1 ? `<button data-page="${data.page - 1}" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-white/10 bg-white/5 text-zinc-300 hover:border-primary/40 hover:text-primary transition">← Prev</button>` : '';
            for (let p = start; p <= end; p++) {
                html += `<button data-page="${p}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition ${p === data.page ? 'bg-primary text-zinc-950' : 'border border-white/10 bg-white/5 text-zinc-300 hover:border-primary/40 hover:text-primary'}">${p}</button>`;
            }
            if (data.page < data.pages) html += `<button data-page="${data.page + 1}" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-white/10 bg-white/5 text-zinc-300 hover:border-primary/40 hover:text-primary transition">Next →</button>`;
            pb.innerHTML = html;
        } else {
            pg.classList.add('hidden');
        }
    }

    function openApprove(id) {
        document.getElementById('approve-wid').value = id;
        document.getElementById('approve-txid').value = '';
        document.getElementById('approve-note').value = '';
        document.getElementById('approve-modal').classList.remove('hidden');
    }

    async function submitApprove() {
        const id = document.getElementById('approve-wid').value;
        const txid = document.getElementById('approve-txid').value.trim();
        const note = document.getElementById('approve-note').value.trim();
        if (!txid) { showToast('error', 'Please enter a TxID.'); return; }

        const fd = new FormData();
        fd.append('action', 'approve');
        fd.append('id', id);
        fd.append('txid', txid);
        fd.append('note', note);
        const res = await fetch(route('/ajax_withdrawals'), { method: 'POST', body: fd });
        const data = await res.json();
        closeModal();
        if (data.success) {
            showToast('success', data.message);
            loadWithdrawals();
        } else {
            showToast('error', data.message);
        }
    }

    function openAuto(id) {
        document.getElementById('auto-wid').value = id;
        document.getElementById('auto-modal').classList.remove('hidden');
    }

    async function submitAutoApprove(btn) {
        const id = document.getElementById('auto-wid').value;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const fd = new FormData();
        fd.append('action', 'pay_faucetpay');
        fd.append('id', id);
        try {
            const res = await fetch(route('/ajax_withdrawals'), { method: 'POST', body: fd });
            const data = await res.json();
            closeModal();
            if (data.success) {
                showToast('success', 'Sent! TxID: ' + data.txid);
                loadWithdrawals();
            } else {
                showToast('error', 'Failed: ' + data.message);
            }
        } catch (e) {
            closeModal();
            showToast('error', 'Network error.');
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }

    function openReject(id) {
        document.getElementById('reject-wid').value = id;
        document.getElementById('reject-note').value = '';
        document.getElementById('reject-modal').classList.remove('hidden');
    }

    async function submitReject() {
        const id = document.getElementById('reject-wid').value;
        const note = document.getElementById('reject-note').value.trim();
        const fd = new FormData();
        fd.append('action', 'reject');
        fd.append('id', id);
        fd.append('note', note);
        const res = await fetch(route('/ajax_withdrawals'), { method: 'POST', body: fd });
        const data = await res.json();
        closeModal();
        if (data.success) {
            showToast('success', data.message);
            loadWithdrawals();
        } else {
            showToast('error', data.message);
        }
    }

    async function deleteWithdrawal(id) {
        if (!confirm('Delete this withdrawal record?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        const res = await fetch(route('/ajax_withdrawals'), { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message);
            loadWithdrawals();
        } else {
            showToast('error', data.message);
        }
    }

    function closeModal() {
        document.getElementById('approve-modal').classList.add('hidden');
        document.getElementById('reject-modal').classList.add('hidden');
        document.getElementById('auto-modal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.body.appendChild(document.getElementById('approve-modal'));
        document.body.appendChild(document.getElementById('reject-modal'));
        document.body.appendChild(document.getElementById('auto-modal'));

        document.addEventListener('click', function (e) {
            const filterBtn = e.target.closest('.filter-btn[data-filter]');
            if (filterBtn) {
                filterStatus(filterBtn.getAttribute('data-filter'));
                return;
            }

            const pageBtn = e.target.closest('#page-buttons [data-page], [data-page]');
            if (pageBtn && pageBtn.closest('#page-buttons')) {
                loadWithdrawals(parseInt(pageBtn.getAttribute('data-page'), 10));
                return;
            }

            const del = e.target.closest('[data-delete-withdrawal]');
            if (del) {
                deleteWithdrawal(del.getAttribute('data-delete-withdrawal'));
                return;
            }

            const openApproveBtn = e.target.closest('[data-open-approve]');
            if (openApproveBtn) {
                openApprove(openApproveBtn.getAttribute('data-open-approve'));
                return;
            }

            const openAutoBtn = e.target.closest('[data-open-auto]');
            if (openAutoBtn) {
                openAuto(openAutoBtn.getAttribute('data-open-auto'));
                return;
            }

            const openRejectBtn = e.target.closest('[data-open-reject]');
            if (openRejectBtn) {
                openReject(openRejectBtn.getAttribute('data-open-reject'));
                return;
            }

            const closeBtn = e.target.closest('[data-close-modal]');
            if (closeBtn) {
                closeModal();
                return;
            }

            const approveBtn = e.target.closest('#submit-approve');
            if (approveBtn) {
                submitApprove();
                return;
            }

            const autoBtn = e.target.closest('#submit-auto');
            if (autoBtn) {
                submitAutoApprove(autoBtn);
                return;
            }

            const rejectBtn = e.target.closest('#submit-reject');
            if (rejectBtn) {
                submitReject();
            }
        });

        document.getElementById('search-input').addEventListener('input', debounceLoad);

        loadWithdrawals();
    });
})();