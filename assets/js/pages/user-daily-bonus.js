(function () {
    'use strict';

    const pageData = window.OW_PAGE_DATA || {};
    const csrfToken = pageData.csrfToken || '';

    document.addEventListener('DOMContentLoaded', function () {
        const claimBtn = document.getElementById('claim-btn');
        if (!claimBtn) return;

        claimBtn.addEventListener('click', async function () {
            const originalHtml = claimBtn.innerHTML;
            claimBtn.disabled = true;
            claimBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Claiming...';

            try {
                const fd = new FormData();
                fd.append('claim_bonus', '1');
                fd.append('csrf_token', csrfToken);
                const res = await fetch('/user/daily_bonus', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    showToast('success', data.message);
                    setTimeout(function () { window.location.reload(); }, 1500);
                } else {
                    showToast('error', data.message);
                    claimBtn.innerHTML = originalHtml;
                    claimBtn.disabled = false;
                }
            } catch (e) {
                showToast('error', 'Network error');
                claimBtn.innerHTML = originalHtml;
                claimBtn.disabled = false;
            }
        });
    });
})();