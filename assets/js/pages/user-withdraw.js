(function () {
    'use strict';

    async function doWithdraw() {
        const btn = document.getElementById('withdraw-btn');
        const resultEl = document.getElementById('withdraw-result');
        const originalHTML = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...';

        try {
            const res = await fetch('/user/ajax_withdraw', { method: 'POST' });
            const data = await res.json();

            if (data.success) {
                showToast('success', data.message || 'Withdrawal successful!');
                setTimeout(function () { window.location.reload(); }, 2000);
            } else {
                showToast('error', data.message);
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        } catch (e) {
            showToast('error', 'Network error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('withdraw-btn');
        if (btn) btn.addEventListener('click', doWithdraw);
    });
})();