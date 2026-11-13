(function () {
    'use strict';

    function route(path) {
        const data = window.OW_ADMIN_DATA || {};
        return (data.adminRoute || '') + path;
    }

    function openModal(dayData) {
        const modal = document.getElementById('day-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (dayData) {
            document.getElementById('modal-title').innerText = 'Edit Day ' + dayData.day_number;
            document.getElementById('form-action').value = 'edit_day';
            document.getElementById('form-id').value = dayData.id;
            document.getElementById('form-day').value = dayData.day_number;
            document.getElementById('form-reward').value = parseFloat(dayData.reward_usdt);
            document.getElementById('form-min').value = parseFloat(dayData.min_condition_usdt);
        } else {
            document.getElementById('modal-title').innerText = 'Add Day';
            document.getElementById('form-action').value = 'add_day';
            document.getElementById('form-id').value = '';
            document.getElementById('form-day').value = '';
            document.getElementById('form-reward').value = '';
            document.getElementById('form-min').value = '0';
        }
    }

    function closeModal() {
        const modal = document.getElementById('day-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function initToggle() {
        const toggle = document.getElementById('toggle-enabled');
        if (!toggle) return;

        toggle.addEventListener('change', async function () {
            const fd = new FormData();
            fd.append('action', 'toggle_enabled');
            fd.append('daily_bonus_enabled', this.checked ? '1' : '0');
            try {
                const res = await fetch(route('/daily_bonus'), { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) showToast('success', data.message);
                else showToast('error', data.message);
            } catch (e) {
                showToast('error', 'Network error');
            }
        });
    }

    function initForm() {
        const form = document.getElementById('day-form');
        if (!form) return;

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('save-day-btn');
            btn.disabled = true;
            btn.innerText = 'Saving...';

            try {
                const fd = new FormData(form);
                const res = await fetch(route('/daily_bonus'), { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    showToast('error', data.message);
                    btn.disabled = false;
                    btn.innerText = 'Save Day';
                }
            } catch (err) {
                showToast('error', 'Network error');
                btn.disabled = false;
                btn.innerText = 'Save Day';
            }
        });
    }

    async function deleteDay(id) {
        if (!confirm('Are you sure you want to delete this day?')) return;
        const fd = new FormData();
        fd.append('action', 'delete_day');
        fd.append('id', id);
        try {
            const res = await fetch(route('/daily_bonus'), { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else showToast('error', data.message);
        } catch (err) {
            showToast('error', 'Network error');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('day-modal');
        if (modal) {
            document.body.appendChild(modal);
        }

        initToggle();
        initForm();

        document.addEventListener('click', function (e) {
            const addBtn = e.target.closest('[data-open-day-modal]');
            if (addBtn) {
                const dayData = addBtn.getAttribute('data-day');
                openModal(dayData ? JSON.parse(dayData) : null);
                return;
            }

            const closeBtn = e.target.closest('[data-close-modal]');
            if (closeBtn) {
                closeModal();
                return;
            }

            const deleteBtn = e.target.closest('[data-delete-day]');
            if (deleteBtn) {
                deleteDay(deleteBtn.getAttribute('data-delete-day'));
            }
        });
    });
})();