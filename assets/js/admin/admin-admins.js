(function () {
    'use strict';

    let modal;
    let modalContent;
    let modalTitle;
    let modalAction;
    let modalAdminId;
    let modalUsername;
    let modalPassword;
    let passwordHelper;
    let usernameHelper;

    function route(path) {
        const data = window.OW_ADMIN_DATA || {};
        return (data.adminRoute || '') + path;
    }

    function openAddModal() {
        modalTitle.innerText = 'Add Admin';
        modalAction.value = 'add';
        modalAdminId.value = '';
        modalUsername.value = '';
        modalUsername.readOnly = false;
        modalUsername.classList.remove('bg-white/10', 'text-zinc-500', 'cursor-not-allowed');
        modalPassword.value = '';
        modalPassword.required = true;
        passwordHelper.classList.add('hidden');
        usernameHelper.classList.add('hidden');

        modal.classList.remove('hidden');
        setTimeout(function () {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    function openEditModal(id, username) {
        modalTitle.innerText = 'Edit Admin';
        modalAction.value = 'edit';
        modalAdminId.value = id;
        modalUsername.value = username;
        modalPassword.value = '';
        modalPassword.required = false;
        passwordHelper.classList.remove('hidden');

        if (username === 'admin') {
            modalUsername.readOnly = true;
            modalUsername.classList.add('bg-white/10', 'text-zinc-500', 'cursor-not-allowed');
            usernameHelper.classList.remove('hidden');
        } else {
            modalUsername.readOnly = false;
            modalUsername.classList.remove('bg-white/10', 'text-zinc-500', 'cursor-not-allowed');
            usernameHelper.classList.add('hidden');
        }

        modal.classList.remove('hidden');
        setTimeout(function () {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    function closeModal() {
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');
        setTimeout(function () {
            modal.classList.add('hidden');
        }, 300);
    }

    function submitAdminForm(e) {
        e.preventDefault();
        const formData = new URLSearchParams();
        formData.append('action', modalAction.value);
        formData.append('admin_id', modalAdminId.value);
        formData.append('username', modalUsername.value);
        formData.append('password', modalPassword.value);

        fetch(route('/ajax_admins_action.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    closeModal();
                    showToast('success', data.message);
                    setTimeout(function () { window.location.reload(); }, 1500);
                } else {
                    showToast('error', data.error || 'An error occurred.');
                }
            })
            .catch(function () {
                showToast('error', 'Network error.');
            });
    }

    function deleteAdmin(id) {
        if (!confirm('Are you sure you want to delete this admin?')) return;

        const formData = new URLSearchParams();
        formData.append('action', 'delete');
        formData.append('admin_id', id);

        fetch(route('/ajax_admins_action.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    showToast('success', data.message);
                    setTimeout(function () { window.location.reload(); }, 1500);
                } else {
                    showToast('error', data.error || 'An error occurred.');
                }
            })
            .catch(function () {
                showToast('error', 'Network error.');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        modal = document.getElementById('adminModal');
        if (!modal) return;
        modalContent = document.getElementById('adminModalContent');
        document.body.appendChild(modal);
        modalTitle = document.getElementById('modalTitle');
        modalAction = document.getElementById('modalAction');
        modalAdminId = document.getElementById('modalAdminId');
        modalUsername = document.getElementById('modalUsername');
        modalPassword = document.getElementById('modalPassword');
        passwordHelper = document.getElementById('passwordHelper');
        usernameHelper = document.getElementById('usernameHelper');

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-open-add-admin]')) {
                openAddModal();
                return;
            }

            const editBtn = e.target.closest('[data-open-edit-admin]');
            if (editBtn) {
                openEditModal(editBtn.getAttribute('data-open-edit-admin'), editBtn.getAttribute('data-admin-username'));
                return;
            }

            const deleteBtn = e.target.closest('[data-delete-admin]');
            if (deleteBtn) {
                deleteAdmin(deleteBtn.getAttribute('data-delete-admin'));
                return;
            }

            const closeBtn = e.target.closest('[data-close-modal]');
            if (closeBtn) {
                closeModal();
            }
        });

        const adminForm = document.getElementById('admin-form');
        if (adminForm) {
            adminForm.addEventListener('submit', submitAdminForm);
        }
    });
})();