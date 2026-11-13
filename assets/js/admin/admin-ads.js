(function () {
    'use strict';

    let adModal;
    let adModalContent;

    function setAdSizeInput(zone, defaultVal) {
        if (defaultVal === undefined) defaultVal = '';
        const container = document.getElementById('adSizeContainer');
        const inputClass = 'w-full bg-white/[0.03] border border-white/10 text-zinc-200 text-sm rounded-lg focus:ring-primary/20 focus:border-primary/60 block p-3';
        const selectClass = inputClass + ' cursor-pointer';

        if (zone === 'dashboard_level_ads') {
            const sizes = ['300x250', '336x280', '300x100', '320x100', '320x50', '300x50', '250x250'];
            let options = sizes.map(s => `<option value="${s}" ${s === defaultVal ? 'selected' : ''}>${s}</option>`).join('');
            container.innerHTML = `<select name="size" id="adSize" required class="${selectClass}">
                <option value="" disabled ${defaultVal ? '' : 'selected'}>Select recommended size...</option>
                ${options}
            </select>`;
        } else if (zone === 'wheel_left_ads' || zone === 'wheel_right_ads' || zone === 'faucet_left_ads' || zone === 'faucet_right_ads') {
            const sizes = ['300x250', '300x600', '120x600', '160x600'];
            let options = sizes.map(s => `<option value="${s}" ${s === defaultVal ? 'selected' : ''}>${s}</option>`).join('');
            container.innerHTML = `<select name="size" id="adSize" required class="${selectClass}">
                <option value="" disabled ${defaultVal ? '' : 'selected'}>Select allowed size...</option>
                ${options}
            </select>`;
        } else if (zone === 'faucet_captcha_ads') {
            const sizes = ['300x250', '250x250', '300x100', '320x100', '320x50'];
            let options = sizes.map(s => `<option value="${s}" ${s === defaultVal ? 'selected' : ''}>${s}</option>`).join('');
            container.innerHTML = `<select name="size" id="adSize" required class="${selectClass}">
                <option value="" disabled ${defaultVal ? '' : 'selected'}>Select allowed size...</option>
                ${options}
            </select>`;
        } else {
            container.innerHTML = `<input type="text" name="size" id="adSize" required placeholder="e.g. 728x90" value="${defaultVal}" class="${inputClass}">`;
        }
    }

    function openAdModal(zone) {
        document.getElementById('adAction').value = 'add_ad';
        document.getElementById('adZone').value = zone;
        document.getElementById('adId').value = '';
        document.getElementById('adModalTitle').textContent = 'Add New Ad';
        document.getElementById('adForm').reset();

        setAdSizeInput(zone);

        if (zone === 'dashboard_level_ads') {
            document.getElementById('adDeviceContainer').classList.add('hidden');
            document.getElementById('adDevice').value = 'All';
        } else if (zone === 'wheel_left_ads' || zone === 'wheel_right_ads' || zone === 'faucet_left_ads' || zone === 'faucet_right_ads') {
            document.getElementById('adDeviceContainer').classList.add('hidden');
            document.getElementById('adDevice').value = 'Desktop';
        } else {
            document.getElementById('adDeviceContainer').classList.remove('hidden');
        }

        adModal.classList.remove('opacity-0', 'invisible');
        setTimeout(() => adModalContent.classList.remove('scale-95'), 10);
    }

    function editAd(zone, ad) {
        document.getElementById('adAction').value = 'edit_ad';
        document.getElementById('adZone').value = zone;
        document.getElementById('adId').value = ad.id;
        document.getElementById('adModalTitle').textContent = 'Edit Ad';

        document.getElementById('adName').value = ad.name;
        document.getElementById('adCode').value = ad.code;
        setAdSizeInput(zone, ad.size);
        document.getElementById('adDevice').value = ad.device;
        document.getElementById('adPriority').value = ad.priority;
        document.getElementById('adRefresh').value = ad.refresh;

        if (zone === 'dashboard_level_ads') {
            document.getElementById('adDeviceContainer').classList.add('hidden');
            document.getElementById('adDevice').value = 'All';
        } else if (zone === 'wheel_left_ads' || zone === 'wheel_right_ads' || zone === 'faucet_left_ads' || zone === 'faucet_right_ads') {
            document.getElementById('adDeviceContainer').classList.add('hidden');
            document.getElementById('adDevice').value = 'Desktop';
        } else {
            document.getElementById('adDeviceContainer').classList.remove('hidden');
        }

        adModal.classList.remove('opacity-0', 'invisible');
        setTimeout(() => adModalContent.classList.remove('scale-95'), 10);
    }

    function closeAdModal() {
        adModalContent.classList.add('scale-95');
        setTimeout(() => adModal.classList.add('opacity-0', 'invisible'), 300);
    }

    async function deleteAd(zone, id) {
        if (!confirm('Are you sure you want to delete this ad?')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete_ad');
            formData.append('zone', zone);
            formData.append('id', id);

            const res = await fetch('', { method: 'POST', body: formData });
            const result = await res.json();

            if (result.success) {
                showToast('success', result.message);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('error', result.message);
            }
        } catch (err) {
            showToast('error', 'Network error occurred.');
        }
    }

    function bindSettingsForm(form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
            btn.disabled = true;

            try {
                const formData = new FormData(e.target);
                const res = await fetch('', { method: 'POST', body: formData });
                const result = await res.json();

                if (result.success) {
                    showToast('success', result.message);
                } else {
                    showToast('error', result.message);
                }
            } catch (err) {
                showToast('error', 'Network error occurred.');
            }

            btn.innerText = originalText;
            btn.disabled = false;
        });
    }

    function initAdForm() {
        const form = document.getElementById('adForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
            btn.disabled = true;

            try {
                const formData = new FormData(e.target);
                const res = await fetch('', { method: 'POST', body: formData });
                const result = await res.json();

                if (result.success) {
                    showToast('success', result.message);
                    closeAdModal();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('error', result.message);
                }
            } catch (err) {
                showToast('error', 'Network error occurred.');
            }

            btn.innerText = originalText;
            btn.disabled = false;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        adModal = document.getElementById('adModal');
        adModalContent = document.getElementById('adModalContent');
        if (!adModal) return;

        document.body.appendChild(adModal);

        ['settingsForm', 'floatingSettingsForm', 'dashboardSettingsForm', 'generalSettingsForm', 'wheelSettingsForm'].forEach(function (id) {
            const form = document.getElementById(id);
            if (form) bindSettingsForm(form);
        });

        const faucetSettingsForm = document.getElementById('faucetSettingsForm');
        if (faucetSettingsForm) bindSettingsForm(faucetSettingsForm);

        initAdForm();

        document.addEventListener('click', function (e) {
            const openBtn = e.target.closest('[data-open-ad-modal]');
            if (openBtn) {
                openAdModal(openBtn.getAttribute('data-open-ad-modal'));
                return;
            }

            const editBtn = e.target.closest('[data-edit-ad]');
            if (editBtn) {
                editAd(editBtn.getAttribute('data-edit-ad'), JSON.parse(editBtn.getAttribute('data-ad')));
                return;
            }

            const deleteBtn = e.target.closest('[data-delete-ad]');
            if (deleteBtn) {
                deleteAd(deleteBtn.getAttribute('data-delete-ad'), deleteBtn.getAttribute('data-ad-id'));
                return;
            }

            if (e.target.closest('[data-close-ad-modal]')) {
                closeAdModal();
            }
        });
    });
})();