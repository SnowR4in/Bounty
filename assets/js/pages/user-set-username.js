(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('username-form');
        if (!form) return;

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');
            const input = document.getElementById('username-input');
            const username = input.value.trim();

            if (!/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
                showToast('error', 'Username must be 3-20 characters, letters, numbers, and underscores only.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';

            try {
                const formData = new FormData();
                formData.append('username', username);

                const res = await fetch('/user/ajax_set_username', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    showToast('success', 'Username set successfully!');
                    setTimeout(function () {
                        window.location.href = '/user/';
                    }, 1000);
                } else {
                    showToast('error', data.error || 'Failed to set username.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> Set Username';
                }
            } catch (err) {
                showToast('error', 'Network error. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Set Username';
            }
        });
    });
})();