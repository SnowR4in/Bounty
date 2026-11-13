(function () {
    'use strict';

    function route(path) {
        const data = window.OW_ADMIN_DATA || {};
        return (data.adminRoute || '') + path;
    }

    function bindSettingForm(formId, endpoint, options) {
        options = options || {};
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
            btn.disabled = true;

            try {
                const formData = new FormData(form);
                if (options.append) {
                    Object.keys(options.append).forEach(function (key) {
                        formData.append(key, options.append[key]);
                    });
                }

                const res = await fetch(endpoint, {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    showToast('success', result.message);
                } else {
                    showToast('error', result.message || 'Error saving settings.');
                }
            } catch (err) {
                showToast('error', 'A network error occurred.');
            } finally {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        });
    }

    function parseIps(raw) {
        const val = String(raw || '').trim();
        return val ? val.split(/[\n,]+/).map(function (ip) { return ip.trim(); }).filter(function (ip) { return ip !== ''; }) : [];
    }

    function initIpTags(type) {
        const hiddenInput = document.getElementById(type + '_ip_whitelist');
        if (!hiddenInput) return;
        const tagsContainer = document.getElementById(type + '_ip_tags');
        const inputField = document.getElementById(type + '_ip_input');

        const ips = parseIps(hiddenInput.value);

        tagsContainer.innerHTML = '';
        ips.forEach(function (ip) {
            const badge = document.createElement('div');
            badge.className = 'inline-flex items-center gap-1.5 bg-primary/10 text-primary border border-primary/25 px-3 py-1 rounded-full text-xs font-mono font-bold';
            badge.innerHTML = `
                ${ip}
                <button type="button" data-ip-remove="${type}" data-ip="${ip}" class="text-primary/60 hover:text-primary transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </button>
            `;
            tagsContainer.appendChild(badge);
        });

        hiddenInput.value = ips.join(',');

        if (inputField) {
            inputField.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addIpTag(type);
                }
            });
        }
    }

    function addIpTag(type) {
        const inputField = document.getElementById(type + '_ip_input');
        const hiddenInput = document.getElementById(type + '_ip_whitelist');
        const newIp = inputField.value.trim();

        if (newIp) {
            let ips = parseIps(hiddenInput.value);
            if (!ips.includes(newIp)) {
                ips.push(newIp);
                hiddenInput.value = ips.join(',');
                initIpTags(type);
            }
            inputField.value = '';
        }
    }

    function removeIpTag(type, ipToRemove) {
        const hiddenInput = document.getElementById(type + '_ip_whitelist');
        let ips = parseIps(hiddenInput.value);

        ips = ips.filter(function (ip) { return ip !== ipToRemove; });
        hiddenInput.value = ips.join(',');
        initIpTags(type);
    }

    function createTierHTML(type, amount, percent) {
        return `
        <div class="flex items-center gap-4 bg-white/[0.03] p-3 rounded-lg border border-white/10 tier-row">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-zinc-400 mb-1 uppercase tracking-wide">Up to Amount (USDT)</label>
                <div class="relative">
                    <input type="number" step="0.0001" min="0" name="${type}_amounts[]" value="${amount}" class="w-full px-3 py-2 rounded-lg text-sm bg-white/[0.03] border border-white/10 text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all" required>
                </div>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-semibold text-zinc-400 mb-1 uppercase tracking-wide">XP Percent (%)</label>
                <div class="relative">
                    <input type="number" step="0.1" min="0" name="${type}_percents[]" value="${percent}" class="w-full px-3 py-2 rounded-lg text-sm bg-white/[0.03] border border-white/10 text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all" required>
                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-500">%</span>
                </div>
            </div>
            <div class="pt-5">
                <button type="button" data-remove-tier class="text-red-400 hover:text-red-300 p-2 rounded-lg hover:bg-red-500/10 transition-colors">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    }

    function addTier(type, amount, percent) {
        if (amount === undefined) amount = '';
        if (percent === undefined) percent = '';
        const container = document.getElementById(type + '_tiers_container');
        if (!container) return;
        container.insertAdjacentHTML('beforeend', createTierHTML(type, amount, percent));
    }

    function toggleLogoType() {
        const isImage = document.getElementById('logo_type_image').checked;
        document.getElementById('logo-text-options').classList.toggle('hidden', isImage);
        document.getElementById('logo-image-options').classList.toggle('hidden', !isImage);
    }

    function initLogoPreview() {
        document.querySelectorAll('[name="logo_text1"], [name="logo_text2"]').forEach(function (input) {
            input.addEventListener('input', function () {
                const v1 = document.querySelector('[name="logo_text1"]').value;
                const v2 = document.querySelector('[name="logo_text2"]').value;
                document.getElementById('preview-text1').textContent = v1;
                document.getElementById('preview-text2').textContent = v2;
                const p2b = document.getElementById('preview-text2b');
                if (p2b) p2b.textContent = v2;
            });
        });
    }

    async function uploadLogo(input) {
        if (!input.files || !input.files[0]) return;
        const statusEl = document.getElementById('logo-upload-status');
        statusEl.textContent = 'Uploading...';
        statusEl.className = 'text-xs text-zinc-500';

        const fd = new FormData();
        fd.append('logo_file', input.files[0]);

        try {
            const res = await fetch(route('/ajax_upload_logo'), { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                document.getElementById('logo_image_value').value = data.url;
                const preview = document.getElementById('current-logo-preview');
                preview.src = data.url + '?t=' + Date.now();
                preview.classList.remove('hidden');
                statusEl.textContent = '✓ Uploaded successfully';
                statusEl.className = 'text-xs text-primary font-medium';
            } else {
                statusEl.textContent = '✗ ' + data.message;
                statusEl.className = 'text-xs text-red-400';
            }
        } catch (e) {
            statusEl.textContent = '✗ Upload failed';
            statusEl.className = 'text-xs text-red-400';
        }
    }

    function togglePass(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fa-solid fa-eye-slash text-xs';
        } else {
            input.type = 'password';
            icon.className = 'fa-solid fa-eye text-xs';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const data = window.OW_ADMIN_DATA || {};

        bindSettingForm('video-form', route('/video_settings'));
        bindSettingForm('ptc-form', route('/ptc_settings'));
        bindSettingForm('shortlinks-form', route('/shortlink_settings'));
        bindSettingForm('tasks-form', route('/tasks_settings'));
        bindSettingForm('offerwall-form', route('/offerwall_settings'));
        bindSettingForm('level-settings-form', route('/level_settings'));
        bindSettingForm('settings-form', route('/general_settings'));
        bindSettingForm('securityForm', window.location.href, { append: { ajax_update_settings: '1' } });

        if (data.tiers) {
            if (Array.isArray(data.tiers.tasks)) {
                data.tiers.tasks.forEach(function (t) { addTier('tasks', t.amount, t.percent); });
                if (data.tiers.tasks.length === 0) addTier('tasks', 0.1, 10);
            }
            if (Array.isArray(data.tiers.video)) {
                data.tiers.video.forEach(function (t) { addTier('video', t.amount, t.percent); });
                if (data.tiers.video.length === 0) addTier('video', 0.5, 20);
            }
        }

        initIpTags('offerwallapi');
        initIpTags('offerwall');
        initIpTags('ou');
        initLogoPreview();

        document.addEventListener('click', function (e) {
            const ipAdd = e.target.closest('[data-ip-add]');
            if (ipAdd) {
                addIpTag(ipAdd.getAttribute('data-ip-add'));
                return;
            }

            const ipRemove = e.target.closest('[data-ip-remove]');
            if (ipRemove) {
                removeIpTag(ipRemove.getAttribute('data-ip-remove'), ipRemove.getAttribute('data-ip'));
                return;
            }

            const tierAdd = e.target.closest('[data-add-tier]');
            if (tierAdd) {
                addTier(tierAdd.getAttribute('data-add-tier'));
                return;
            }

            const tierRemove = e.target.closest('[data-remove-tier]');
            if (tierRemove) {
                tierRemove.closest('.tier-row').remove();
                return;
            }

            const passToggle = e.target.closest('[data-toggle-pass]');
            if (passToggle) {
                togglePass(passToggle.getAttribute('data-toggle-pass'), passToggle);
                return;
            }

            const logoFile = e.target.closest('[data-logo-file]');
            if (logoFile) {
                uploadLogo(logoFile);
            }
        });

        document.addEventListener('change', function (e) {
            if (e.target.matches('[data-logo-type-radio]')) {
                toggleLogoType();
            }
        });
    });
})();