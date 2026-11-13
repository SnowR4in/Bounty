(function () {
    'use strict';

    async function addSegment() {
        try {
            const response = await fetch('ajax_wheel_segments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=add'
            });
            if (response.ok) window.location.reload();
        } catch (e) {
            alert('Error adding segment');
        }
    }

    async function updateSegment(id, field, value) {
        try {
            const formData = new URLSearchParams();
            formData.append('action', 'update');
            formData.append('id', id);
            formData.append('field', field);
            formData.append('value', value);

            const response = await fetch('ajax_wheel_segments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            const result = await response.json();
            if (result.status === 'error') alert(result.message);
            if (field === 'color') window.location.reload();
        } catch (e) {
            alert('Error updating segment');
        }
    }

    async function moveSegment(id, direction) {
        try {
            const formData = new URLSearchParams();
            formData.append('action', 'move');
            formData.append('id', id);
            formData.append('direction', direction);

            const response = await fetch('ajax_wheel_segments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            if (response.ok) window.location.reload();
        } catch (e) {
            alert('Error moving segment');
        }
    }

    async function deleteSegment(id) {
        if (!confirm('Are you sure you want to delete this segment?')) return;
        try {
            const formData = new URLSearchParams();
            formData.append('action', 'delete');
            formData.append('id', id);

            const response = await fetch('ajax_wheel_segments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            if (response.ok) window.location.reload();
        } catch (e) {
            alert('Error deleting segment');
        }
    }

    function initSettingsForm() {
        const btn = document.getElementById('saveWheelSettingsBtn');
        if (!btn) return;

        btn.addEventListener('click', async function () {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
            btn.disabled = true;

            const formData = new URLSearchParams(new FormData(document.getElementById('wheelSettingsForm')));
            formData.append('ajax_update_settings', '1');

            try {
                const res = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });
                const data = await res.json();
                if (data.success) {
                    showToast('success', data.message);
                } else {
                    showToast('error', data.message);
                }
            } catch (e) {
                showToast('error', 'Network error or server error.');
            }

            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSettingsForm();

        document.addEventListener('click', function (e) {
            const addBtn = e.target.closest('[data-segment-add]');
            if (addBtn) {
                addSegment();
                return;
            }

            const deleteBtn = e.target.closest('[data-segment-delete]');
            if (deleteBtn) {
                deleteSegment(deleteBtn.getAttribute('data-segment-delete'));
                return;
            }

            const moveBtn = e.target.closest('[data-segment-move]');
            if (moveBtn) {
                moveSegment(moveBtn.getAttribute('data-segment-move'), moveBtn.getAttribute('data-segment-direction'));
            }
        });

        document.addEventListener('change', function (e) {
            const field = e.target.closest('[data-segment-field]');
            if (field) {
                updateSegment(field.getAttribute('data-segment-id'), field.getAttribute('data-segment-field'), field.value);
            }
        });
    });
})();