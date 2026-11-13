(function () {
    'use strict';

    const pageData = window.OW_PAGE_DATA || {};
    let tasksData = pageData.tasks || [];
    const approvedIds = pageData.approvedIds || [];

    const tasksGrid = document.getElementById('tasksGrid');
    const noTasksMatch = document.getElementById('noTasksMatch');
    const sortFilter = document.getElementById('sortFilter');

    const taskModal = document.getElementById('taskModal');
    const taskModalTitle = document.getElementById('taskModalTitle');
    const taskModalReward = document.getElementById('taskModalReward');
    const taskModalInstructions = document.getElementById('taskModalInstructions');
    const taskModalRequirementsWrap = document.getElementById('taskModalRequirementsWrap');
    const taskModalRequirements = document.getElementById('taskModalRequirements');
    const taskModalOpenBtn = document.getElementById('taskModalOpenBtn');
    const taskModalProofLabel = document.getElementById('taskModalProofLabel');
    const taskModalProofContainer = document.getElementById('taskModalProofContainer');
    const taskModalMessage = document.getElementById('taskModalMessage');
    const taskModalSubmitBtn = document.getElementById('taskModalSubmitBtn');

    let currentTask = null;
    // Sorted copy currently rendered on the grid — Start Task buttons carry
    // indexes into THIS array (same pattern as user-ptc.js / user-video.js).
    // Reading tasksData[index] opened the WRONG task when both providers were
    // enabled: the grid renders the reward-sorted list while tasksData keeps
    // the unsorted merge order (OfferwallApi items first, Offerwall.me appended).
    let currentFiltered = [];

    if (sortFilter) {
        sortFilter.addEventListener('change', renderTasks);
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatAmount(amount) {
        let n = parseFloat(amount);
        if (!isFinite(n)) n = 0;
        let s = n.toFixed(6);
        if (s.indexOf('.') !== -1) {
            s = s.replace(/0+$/, '').replace(/\.$/, '');
        }
        return s === '' ? '0' : s;
    }

    function getReward(task) {
        return parseFloat(task.reward_usdt || task.reward_usd || task.reward || 0);
    }

    function isApproved(task) {
        // Approved ids come from the OfferwallApi feed only — never mark an
        // Offerwall.me task as approved on a coincidentally equal id.
        return task.provider === 'OfferwallApi' && approvedIds.indexOf(String(task.id)) !== -1;
    }

    function renderTasks() {
        if (!tasksGrid) return;

        let sortOrder = sortFilter ? sortFilter.value : 'desc';

        let filtered = [...tasksData];

        filtered.sort(function (a, b) {
            let valA = getReward(a);
            let valB = getReward(b);
            return sortOrder === 'desc' ? (valB - valA) : (valA - valB);
        });

        currentFiltered = filtered;

        tasksGrid.innerHTML = '';

        if (filtered.length === 0) {
            noTasksMatch.classList.remove('hidden');
        } else {
            noTasksMatch.classList.add('hidden');

            filtered.forEach(function (task, index) {
                let reward = getReward(task);
                let title = task.title || 'Task';
                let description = task.description || '';
                let imageUrl = task.image || task.icon || '';

                if (imageUrl && (imageUrl.includes('/defaults/default-') || imageUrl.includes('default-campaign'))) {
                    imageUrl = '';
                }

                let statusHtml = '';
                if (isApproved(task)) {
                    statusHtml = `<span class="inline-flex items-center gap-1.5 bg-primary/10 text-primary border border-primary/25 text-xs font-bold px-3 py-2 rounded-lg"><i class="fa-solid fa-circle-check"></i> Approved</span>`;
                } else if ((task.status || '') === 'pending') {
                    statusHtml = `<span class="inline-flex items-center gap-1.5 bg-white/5 text-zinc-300 border border-white/10 text-xs font-bold px-3 py-2 rounded-lg"><i class="fa-solid fa-hourglass-half"></i> Pending review</span>`;
                } else {
                    statusHtml = `<button data-task-index="${index}" class="bg-primary hover:bg-primaryHover text-zinc-950 px-4 py-2 rounded-lg text-sm font-bold transition-colors inline-block text-center">Start Task</button>`;
                }

                let badge = 'Task';

                let html = `
                    <div class="bg-surface-card rounded-lg border border-white/10 overflow-hidden hover:border-white/20 transition-colors group flex flex-col h-full">

                        <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center overflow-hidden" style="height: 110px;">
                            ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(title)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">` : `<i class="fa-solid fa-list-check text-4xl text-primary/40"></i>`}

                            <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-zinc-300 text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide z-10 font-mono">
                                ${badge}
                            </div>
                        </div>

                        <div class="p-4 flex flex-col flex-grow relative z-10">
                            <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-2 truncate" title="${escapeHtml(title)}">${escapeHtml(title)}</h3>
                            <p class="text-xs text-zinc-400 mb-4 line-clamp-3">${escapeHtml(description)}</p>

                            <div class="mt-auto pt-4 border-t border-white/10 flex items-center justify-between gap-2">
                                <span class="text-primary font-extrabold text-sm font-mono">+${formatAmount(reward)} USDT</span>
                                ${statusHtml}
                            </div>
                        </div>
                    </div>
                `;
                tasksGrid.insertAdjacentHTML('beforeend', html);
            });
        }
    }

    function openTaskModal(index) {
        // Resolve by the RENDERED (sorted) list — the button index refers to
        // the card position on screen, not to the unsorted tasksData merge.
        let task = currentFiltered[index];
        if (!task) return;
        currentTask = task;

        taskModalTitle.textContent = task.title || 'Task';
        taskModalReward.textContent = formatAmount(getReward(task));
        taskModalInstructions.textContent = task.instructions || task.description || 'Complete the task as described.';

        if (task.requirements) {
            taskModalRequirements.textContent = task.requirements;
            taskModalRequirementsWrap.classList.remove('hidden');
        } else {
            taskModalRequirements.textContent = '';
            taskModalRequirementsWrap.classList.add('hidden');
        }

        let targetUrl = task.targetUrl || task.url || '#';
        taskModalOpenBtn.href = targetUrl;
        if (targetUrl === '#') {
            taskModalOpenBtn.classList.add('hidden');
        } else {
            taskModalOpenBtn.classList.remove('hidden');
        }

        let proofType = (task.proofType || 'TEXT').toUpperCase();
        let hint = task.proofHint || '';
        let field = '';

        if (proofType === 'URL') {
            taskModalProofLabel.innerHTML = '<i class="fa-solid fa-link mr-1.5 text-primary"></i>Proof URL';
            field = `<input type="url" id="proofInput" maxlength="2048" placeholder="${escapeHtml(hint || 'https://example.com/your-result')}" class="w-full bg-white/[0.03] border border-white/10 rounded-lg px-4 py-3 text-sm text-zinc-200 placeholder-zinc-600 outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 transition-all">`;
        } else if (proofType === 'IMAGE_URL') {
            taskModalProofLabel.innerHTML = '<i class="fa-solid fa-image mr-1.5 text-primary"></i>Proof Screenshot URL';
            field = `<input type="url" id="proofInput" maxlength="2048" placeholder="${escapeHtml(hint || 'https://example.com/screenshot.jpg')}" class="w-full bg-white/[0.03] border border-white/10 rounded-lg px-4 py-3 text-sm text-zinc-200 placeholder-zinc-600 outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 transition-all">
                     <p class="text-xs text-zinc-500 mt-2">Upload a screenshot to any image host and paste the direct link here.</p>`;
        } else {
            taskModalProofLabel.innerHTML = '<i class="fa-solid fa-pen-to-square mr-1.5 text-primary"></i>Proof';
            field = `<textarea id="proofInput" rows="4" maxlength="2000" placeholder="${escapeHtml(hint || 'Describe the result / paste the required text...')}" class="w-full bg-white/[0.03] border border-white/10 rounded-lg px-4 py-3 text-sm text-zinc-200 placeholder-zinc-600 outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/20 transition-all resize-y"></textarea>`;
        }
        taskModalProofContainer.innerHTML = field;

        taskModalMessage.classList.add('hidden');
        taskModalMessage.textContent = '';
        taskModalSubmitBtn.disabled = false;
        taskModalSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');

        taskModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeTaskModal() {
        taskModal.classList.add('hidden');
        document.body.style.overflow = '';
        currentTask = null;
    }

    function showTaskMessage(text, isError) {
        taskModalMessage.textContent = text;
        taskModalMessage.className = 'text-sm rounded-lg px-4 py-3 mb-4 ' + (isError ? 'bg-red-500/10 text-red-400 border border-red-500/25' : 'bg-primary/10 text-primary border border-primary/25');
    }

    function submitProof() {
        if (!currentTask) return;

        let input = document.getElementById('proofInput');
        let proof = input ? input.value.trim() : '';

        if (!proof) {
            showTaskMessage('Please enter your proof before submitting.', true);
            return;
        }
        if (proof.length > 2048) {
            showTaskMessage('Proof is too long (max 2048 characters).', true);
            return;
        }

        let proofType = (currentTask.proofType || 'TEXT').toUpperCase();
        if ((proofType === 'URL' || proofType === 'IMAGE_URL') && !/^https?:\/\//i.test(proof)) {
            showTaskMessage('Please enter a valid http(s) URL.', true);
            return;
        }
        if (proofType === 'TEXT' && proof.length > 2000) {
            showTaskMessage('Proof is too long (max 2000 characters).', true);
            return;
        }

        taskModalSubmitBtn.disabled = true;
        taskModalSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        showTaskMessage('Submitting...', false);

        fetch('/user/ajax_tasks_submit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                taskId: String(currentTask.id),
                proof: proof,
                provider: currentTask.provider === 'Offerwall.me' ? 'offerwall.me' : 'offerwallapi'
            })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                for (let i = 0; i < tasksData.length; i++) {
                    if (String(tasksData[i].id) === String(currentTask.id) && tasksData[i].provider === currentTask.provider) {
                        tasksData[i].status = 'pending';
                        break;
                    }
                }
                showTaskMessage(data.message || 'Submitted for review.', false);
                setTimeout(function () {
                    closeTaskModal();
                    renderTasks();
                }, 900);
            } else {
                showTaskMessage(data.message || 'Submission failed. Please try again.', true);
                taskModalSubmitBtn.disabled = false;
                taskModalSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        })
        .catch(function () {
            showTaskMessage('Connection error. Please try again.', true);
            taskModalSubmitBtn.disabled = false;
            taskModalSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !taskModal.classList.contains('hidden')) {
            closeTaskModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (tasksGrid) {
            tasksGrid.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-task-index]');
                if (!btn) return;
                openTaskModal(parseInt(btn.getAttribute('data-task-index'), 10));
            });
        }

        if (taskModal) {
            taskModal.addEventListener('click', function (e) {
                if (e.target.closest('[data-task-modal-close]')) {
                    closeTaskModal();
                }
            });
        }

        if (taskModalSubmitBtn) {
            taskModalSubmitBtn.addEventListener('click', submitProof);
        }

        renderTasks();
    });
})();