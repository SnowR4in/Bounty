(function () {
    'use strict';

    const tooltipDescriptions = {
        'Faucet': 'Earn a percentage every time your referral claims from the Faucet.',
        'PTC Ads': 'Earn a percentage every time your referral views a PTC ad.',
        'Shortlinks': 'Earn a percentage every time your referral completes a shortlink.',
        'Tasks': 'Earn a percentage every time your referral completes a task.',
        'Video': 'Earn a percentage every time your referral completes a video reward.',
        'Deposits': 'Earn a percentage every time your referral makes a deposit.'
    };

    let activeTooltipBadge = null;

    function toggleTooltip(badge) {
        const tooltip = document.getElementById('commission-tooltip');
        const tooltipText = document.getElementById('tooltip-text');
        const label = badge.dataset.label;
        const percent = badge.dataset.percent;

        if (activeTooltipBadge === badge) {
            tooltip.classList.add('hidden');
            activeTooltipBadge = null;
            return;
        }

        const desc = tooltipDescriptions[label] || 'Earn ' + percent + "% from your referral's " + label + ' activity.';
        tooltipText.textContent = label + ' — ' + percent + '%: ' + desc;
        tooltip.classList.remove('hidden');
        activeTooltipBadge = badge;
    }

    document.addEventListener('click', function (e) {
        const badge = e.target.closest('.commission-badge');
        if (badge) {
            toggleTooltip(badge);
            return;
        }
        if (!e.target.closest('#commission-tooltip')) {
            const tooltip = document.getElementById('commission-tooltip');
            if (tooltip) tooltip.classList.add('hidden');
            activeTooltipBadge = null;
        }
    });

    function copyRefLink() {
        const linkInput = document.getElementById('ref-link');
        linkInput.select();
        linkInput.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(linkInput.value).then(function () {
            showToast('success', 'Referral link copied to clipboard!');
        }).catch(function () {
            showToast('error', 'Failed to copy link.');
        });
    }

    let currentPage = 1;

    function fetchReferrals(page) {
        currentPage = page;
        const tbody = document.getElementById('referrals-table-body');
        const pageInfo = document.getElementById('pagination-info');
        const pageBtns = document.getElementById('pagination-buttons');

        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-zinc-500"><i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Loading...</td></tr>';

        fetch('ajax_referrals.php?page=' + page)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    if (data.referrals.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-zinc-500">You don\'t have any referrals yet. Share your link!</td></tr>';
                    } else {
                        let html = '';
                        data.referrals.forEach(function (ref) {
                            const email = ref.faucetpay_email || '';
                            const atIdx = email.indexOf('@');
                            const maskedEmail = atIdx > 0 ? email.substring(0, 2) + '***' + email.substring(atIdx) : '***';
                            const displayName = ref.username ? '<span class="font-semibold text-zinc-100">@' + ref.username + '</span>' : '<span class="font-mono text-xs text-zinc-400">' + maskedEmail + '</span>';
                            html += `
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-4 font-medium text-zinc-100 font-mono">#${ref.id}</td>
                                    <td class="px-6 py-4 text-zinc-300">${displayName}</td>
                                    <td class="px-6 py-4 font-medium text-primary font-mono">${parseFloat(ref.total_earned).toFixed(6)} USDT</td>
                                    <td class="px-6 py-4 text-right text-zinc-500">${ref.date_formatted}</td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    }

                    const start = (currentPage - 1) * 20 + (data.referrals.length > 0 ? 1 : 0);
                    const end = start + data.referrals.length - (data.referrals.length > 0 ? 1 : 0);
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
                    tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-red-400">' + data.error + '</td></tr>';
                }
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-red-400">Failed to load referrals.</td></tr>';
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const copyBtn = document.getElementById('copy-ref-link-btn');
        if (copyBtn) copyBtn.addEventListener('click', copyRefLink);

        const pageBtns = document.getElementById('pagination-buttons');
        if (pageBtns) {
            pageBtns.addEventListener('click', function (e) {
                const btn = e.target.closest('button[data-page]');
                if (!btn) return;
                fetchReferrals(parseInt(btn.getAttribute('data-page'), 10));
            });
        }

        fetchReferrals(1);
    });
})();