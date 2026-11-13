(function () {
    'use strict';

    let currentPage = 1;
    let currentSearch = '';
    let currentFilter = 'all';
    let currentSort = 'id_desc';

    function route(path) {
        const data = window.OW_ADMIN_DATA || {};
        return (data.adminRoute || '') + path;
    }

    function fetchUsers(page) {
        if (page === undefined) page = 1;
        currentPage = page;
        const tbody = document.getElementById('users-table-body');
        const pageInfo = document.getElementById('pagination-info');
        const pageBtns = document.getElementById('pagination-buttons');

        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-zinc-500"><i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Loading...</td></tr>';

        fetch(route('/ajax_users_fetch?page=' + page + '&search=' + encodeURIComponent(currentSearch) + '&status_filter=' + encodeURIComponent(currentFilter) + '&sort=' + encodeURIComponent(currentSort)))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    if (data.users.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-zinc-500">No users found.</td></tr>';
                        pageInfo.innerText = 'Showing 0 of 0';
                        pageBtns.innerHTML = '';
                        return;
                    }

                    let html = '';
                    data.users.forEach(function (u) {
                        let statusBadge = '';
                        if (u.status == 1) {
                            statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/25">Active</span>';
                        } else if (u.status == 2) {
                            statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/25">Shadow Banned</span>';
                        } else {
                            statusBadge = '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/25">Banned</span>';
                        }

                        let riskColor = u.risk_score > 50 ? 'text-red-400 font-bold' : (u.risk_score > 20 ? 'text-amber-400' : 'text-primary');
                        let usernameDisplay = u.username ? '<div class="font-bold text-zinc-100">' + escapeHtml(u.username) + '</div>' : '';

                        html += `
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-4 font-medium text-zinc-100 font-mono">#${u.id}</td>
                            <td class="px-6 py-4">
                                ${usernameDisplay}
                                <div class="text-zinc-500 font-mono text-xs">${u.faucetpay_email}</div>
                            </td>
                            <td class="px-6 py-4 ${riskColor}">${u.risk_score}</td>
                            <td class="px-6 py-4 font-bold font-mono text-primary">${parseFloat(u.balance).toFixed(6)} <span class="text-zinc-500 font-normal text-xs ml-1">/ ${u.energy} ⚡</span></td>
                            <td class="px-6 py-4">${statusBadge}</td>
                            <td class="px-6 py-4 text-zinc-500">${u.date_formatted}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="${route('/edit_user/' + u.id)}" class="w-8 h-8 rounded-lg bg-white/5 text-zinc-300 hover:bg-white/10 border border-white/10 flex items-center justify-center transition-colors" title="Edit User">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <button data-delete-user="${u.id}" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 flex items-center justify-center transition-colors" title="Delete User">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                    });
                    tbody.innerHTML = html;

                    const start = (currentPage - 1) * 20 + 1;
                    const end = start + data.users.length - 1;
                    pageInfo.innerText = 'Showing ' + start + ' to ' + end + ' of ' + data.total;

                    let btnsHtml = '';
                    if (currentPage > 1) {
                        btnsHtml += `<button data-page="${currentPage - 1}" class="px-3 py-1.5 text-sm bg-white/5 border border-white/10 text-zinc-300 rounded-lg hover:bg-white/10 transition-colors">Previous</button>`;
                    }
                    if (end < data.total) {
                        btnsHtml += `<button data-page="${currentPage + 1}" class="px-3 py-1.5 text-sm bg-white/5 border border-white/10 text-zinc-300 rounded-lg hover:bg-white/10 transition-colors">Next</button>`;
                    }
                    pageBtns.innerHTML = btnsHtml;
                } else {
                    tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-8 text-center text-red-400">${data.error}</td></tr>`;
                }
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-red-400">Failed to load users.</td></tr>';
            });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function deleteUser(id) {
        if (confirm('Are you absolutely sure you want to delete this user? ALL their logs and claims will be permanently deleted.')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', id);

            fetch(route('/ajax_users_action'), {
                method: 'POST',
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showToast('success', 'User deleted successfully.');
                        fetchUsers(currentPage);
                    } else {
                        alert(data.error);
                    }
                });
        }
    }

    function toggleSort() {
        const icon = document.getElementById('balance-sort-icon');
        if (currentSort === 'balance_desc') {
            currentSort = 'balance_asc';
            icon.className = 'fa-solid fa-sort-up ml-1 text-primary';
        } else {
            currentSort = 'balance_desc';
            icon.className = 'fa-solid fa-sort-down ml-1 text-primary';
        }
        fetchUsers(1);
    }

    function init() {
        const searchInput = document.getElementById('search-input');
        const statusFilter = document.getElementById('status-filter');
        const sortToggle = document.getElementById('balance-sort-toggle');
        const tableBody = document.getElementById('users-table-body');
        const paginationButtons = document.getElementById('pagination-buttons');

        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function (e) {
                clearTimeout(searchTimeout);
                currentSearch = e.target.value.trim();
                searchTimeout = setTimeout(function () {
                    fetchUsers(1);
                }, 500);
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', function (e) {
                currentFilter = e.target.value;
                fetchUsers(1);
            });
        }

        if (sortToggle) {
            sortToggle.addEventListener('click', toggleSort);
        }

        if (tableBody) {
            tableBody.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-delete-user]');
                if (btn) deleteUser(btn.getAttribute('data-delete-user'));
            });
        }

        if (paginationButtons) {
            paginationButtons.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-page]');
                if (btn) fetchUsers(parseInt(btn.getAttribute('data-page'), 10));
            });
        }

        fetchUsers(1);
    }

    document.addEventListener('DOMContentLoaded', init);
})();