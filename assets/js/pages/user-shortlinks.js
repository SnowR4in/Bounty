(function () {
    'use strict';

    const pageData = window.OW_PAGE_DATA || {};
    let offersData = pageData.offers || [];
    const userId = pageData.userId || '';

    const offersGrid = document.getElementById('offersGrid');
    const noOffersMatch = document.getElementById('noOffersMatch');
    const sortFilter = document.getElementById('sortFilter');

    let activeShortlink = null;

    if (sortFilter) {
        sortFilter.addEventListener('change', renderOffers);
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
        if (task.provider === 'OfferwallApi') {
            return parseFloat(task.reward_usdt || 0);
        }
        return parseFloat(task.reward || 0);
    }

    function renderOffers() {
        if (!offersGrid) return;

        let sortOrder = sortFilter ? sortFilter.value : 'desc';

        let filtered = [...offersData];

        filtered.sort(function (a, b) {
            let valA = getReward(a);
            let valB = getReward(b);
            return sortOrder === 'desc' ? (valB - valA) : (valA - valB);
        });

        offersGrid.innerHTML = '';

        if (filtered.length === 0) {
            noOffersMatch.classList.remove('hidden');
        } else {
            noOffersMatch.classList.add('hidden');

            let firstRowItems = 2;
            if (window.innerWidth >= 1280) firstRowItems = 6;
            else if (window.innerWidth >= 1024) firstRowItems = 5;
            else if (window.innerWidth >= 768) firstRowItems = 3;

            filtered.forEach(function (task, index) {
                let reward = getReward(task);
                let title = task.title || task.name || 'Shortlink';
                let remaining = parseInt(task.remainingVisits || task.remaining_views || 0) || 0;
                let total = parseInt(task.dailyLimit || task.daily_limit || 0) || 0;
                let badge = 'Shortlink';

                let imageUrl = task.icon || task.image || '';
                if (imageUrl && (imageUrl.includes('/defaults/default-') || imageUrl.includes('default-campaign'))) {
                    imageUrl = '';
                }

                let finalUrl = task.url || '#';
                if (finalUrl !== '#' && task.provider !== 'OfferwallApi') {
                    finalUrl = finalUrl.replace(/USER_ID/g, userId).replace(/\[USER_ID\]/gi, userId).replace(/%5BUSER_ID%5D/gi, userId);
                }

                let progressHtml = '';
                if (total > 0 && remaining > 0) {
                    progressHtml = `
                        <div class="mb-3 w-full bg-white/10 rounded-full h-1.5">
                            <div class="bg-primary h-1.5 rounded-full" style="width: ${(remaining / total) * 100}%"></div>
                        </div>
                        <p class="text-[10px] text-zinc-500 font-bold mb-3 font-mono">${remaining}/${total} visits left today</p>`;
                } else {
                    progressHtml = `
                        <p class="text-[11px] text-zinc-500 font-medium mb-3">Complete link to earn</p>`;
                }

                let html = `
                    <a href="${escapeHtml(finalUrl)}" target="_blank" rel="noopener noreferrer" data-shortlink-index="${index}" class="bg-surface-card rounded-lg border border-white/10 overflow-hidden hover:border-white/20 transition-colors group flex flex-col h-full micro-anim">

                        <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center overflow-hidden" style="height: 110px;">
                            ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(title)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">` : `<i class="fa-solid fa-link text-4xl text-primary/40"></i>`}

                            <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-zinc-300 text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide z-10 font-mono">
                                ${badge}
                            </div>
                        </div>

                        <div class="p-4 flex flex-col flex-grow relative z-10">
                            <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-1 truncate" title="${escapeHtml(title)}">${escapeHtml(title)}</h3>
                            ${progressHtml}

                            <div class="mt-auto flex items-center justify-between">
                                <span class="text-primary font-extrabold text-xs font-mono">+${formatAmount(reward)} USDT</span>
                                <div class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                `;
                offersGrid.insertAdjacentHTML('beforeend', html);

                if (index + 1 === firstRowItems) {
                    let adHtml = `<div id="ad-container-general_top_ads" class="col-span-full w-full flex justify-center empty:hidden min-h-[90px] mt-4 mb-2"></div>`;
                    offersGrid.insertAdjacentHTML('beforeend', adHtml);
                }
            });

            refreshTopAdZone();
        }
    }

    function refreshTopAdZone() {
        const zone = 'general_top_ads';
        const el = document.getElementById('ad-container-' + zone);
        if (!el || el.innerHTML) return;
        const ads = window.OW_ADS_DATA;
        if (ads && ads[zone] && ads[zone].length > 0 && typeof window.renderGeneralAd === 'function') {
            window.renderGeneralAd(zone, ads[zone]);
        }
    }

    if (offersGrid) {
        offersGrid.addEventListener('click', function (e) {
            const link = e.target.closest('a[data-shortlink-index]');
            if (!link) return;
            activeShortlink = parseInt(link.getAttribute('data-shortlink-index'), 10);
        });
    }

    window.addEventListener('focus', function () {
        if (activeShortlink !== null) {
            let overlay = document.createElement('div');
            overlay.id = 'shortlinkRefreshOverlay';
            overlay.className = 'fixed inset-0 bg-surface/90 backdrop-blur-sm z-[200] flex flex-col items-center justify-center';
            overlay.innerHTML = `
                <div class="w-16 h-16 border-4 border-primary border-t-transparent rounded-full animate-spin mb-4"></div>
                <h3 class="text-xl font-bold text-zinc-100">Checking shortlink status...</h3>
                <p class="text-zinc-500 text-sm mt-2">Please wait while we verify your completion.</p>
            `;
            document.body.appendChild(overlay);

            fetch('/user/ajax_shortlinks_fetch.php')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success && data.tasks) {
                        offersData.length = 0;
                        offersData.push(...data.tasks);
                        renderOffers();
                    }
                })
                .catch(function (err) { console.error('Error refreshing shortlinks:', err); })
                .finally(function () {
                    activeShortlink = null;
                    let ov = document.getElementById('shortlinkRefreshOverlay');
                    if (ov) ov.remove();
                });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        renderOffers();
        refreshTopAdZone();
    });
})();