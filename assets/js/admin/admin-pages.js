(function () {
    'use strict';

    function route(path) {
        const data = window.OW_ADMIN_DATA || {};
        return (data.adminRoute || '') + path;
    }

    function initPagesForm() {
        const form = document.getElementById('pages-form');
        if (!form) return;

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('save-btn');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
            btn.disabled = true;

            try {
                const formData = new FormData(form);
                const response = await fetch(route('/pages'), {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showToast('success', result.message);
                } else {
                    showToast('error', result.message || 'Error saving settings.');
                }
            } catch (err) {
                showToast('error', 'Network error.');
            } finally {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        });
    }

    function initFaqManager() {
        const addFaqBtn = document.getElementById('addFaqBtn');
        const container = document.getElementById('faq-container');
        if (!addFaqBtn || !container) return;

        addFaqBtn.addEventListener('click', function () {
            document.getElementById('faq-empty').classList.add('hidden');
            const item = document.createElement('div');
            item.className = 'faq-item flex gap-4 p-4 border border-white/10 rounded-lg bg-white/[0.03] relative group';
            item.innerHTML = `
                <div class="flex-1 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Question</label>
                        <input type="text" name="faq_q[]" class="w-full px-4 py-2 rounded-lg bg-white/[0.03] border border-white/10 text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Answer</label>
                        <textarea name="faq_a[]" rows="2" class="w-full px-4 py-2 rounded-lg bg-white/[0.03] border border-white/10 text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all resize-y" required></textarea>
                    </div>
                </div>
                <button type="button" class="remove-faq text-zinc-500 hover:text-red-400 transition-colors self-center p-2 rounded-lg hover:bg-red-500/10">
                    <i class="fa-solid fa-trash text-lg"></i>
                </button>
            `;
            container.appendChild(item);
        });

        container.addEventListener('click', function (e) {
            if (e.target.closest('.remove-faq')) {
                e.target.closest('.faq-item').remove();
                if (document.querySelectorAll('.faq-item').length === 0) {
                    document.getElementById('faq-empty').classList.remove('hidden');
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPagesForm();
        initFaqManager();
    });
})();