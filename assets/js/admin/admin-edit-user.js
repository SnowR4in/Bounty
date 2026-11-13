(function () {
    'use strict';

    function initResultToast() {
        const data = window.OW_ADMIN_DATA || {};
        if (data.toast) {
            showToast(data.toast.type, data.toast.message);
        }
    }

    async function deleteLogs(action, message) {
        if (!confirm(message)) return;

        try {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('ajax', '1');

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: fd
            });

            const result = await response.json();
            if (result.success) {
                showToast('success', result.message);
                setTimeout(function () { window.location.reload(); }, 1000);
            } else {
                showToast('error', result.message || 'An error occurred');
            }
        } catch (e) {
            showToast('error', 'Network error');
        }
    }

    function init() {
        initResultToast();

        document.querySelectorAll('[data-delete-logs]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                deleteLogs(btn.getAttribute('data-delete-logs'), btn.getAttribute('data-confirm'));
            });
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();