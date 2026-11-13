(function () {
    'use strict';

    const pageData = window.OW_PAGE_DATA || {};
    let offersData = pageData.offers || [];
    const userId = pageData.userId || '';

    const offersGrid = document.getElementById('offersGrid');
    const noOffersMatch = document.getElementById('noOffersMatch');
    const sortFilter = document.getElementById('sortFilter');

    const prematureOverlay = document.getElementById('prematureOverlay');
    const prematureSeconds = document.getElementById('prematureSeconds');
    const reopenAdBtn = document.getElementById('reopenAdBtn');
    const cancelAdBtn = document.getElementById('cancelAdBtn');

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
        return parseFloat(task.reward_usdt || task.reward || 0);
    }

    function getTypeLabel(task) {
        return (task.ad_type || '').toLowerCase() === 'window' ? 'Window' : 'YouTube';
    }

    let currentFiltered = [];

    function renderOffers() {
        if (!offersGrid) return;

        let sortOrder = sortFilter ? sortFilter.value : 'desc';

        let filtered = [...offersData];

        filtered.sort(function (a, b) {
            let valA = getReward(a);
            let valB = getReward(b);
            return sortOrder === 'desc' ? (valB - valA) : (valA - valB);
        });

        currentFiltered = filtered;
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
                let title = task.title || task.name || 'Video';

                let imageUrl = task.icon || task.image || '';

                if (imageUrl && (imageUrl.includes('/defaults/default-') || imageUrl.includes('default-campaign'))) {
                    imageUrl = '';
                }

                let finalUrl = task.url || '#';

                let duration = parseInt(task.duration) || 0;
                let typeLabel = getTypeLabel(task);

                let html = `
                    <a href="${escapeHtml(finalUrl)}" target="_blank" rel="noopener noreferrer" data-ad-index="${index}" data-duration="${duration}" class="bg-surface-card rounded-lg border border-white/10 overflow-hidden hover:border-white/20 transition-colors group flex flex-col h-full micro-anim">

                        <div class="bg-surface-raised relative w-full flex-shrink-0 flex items-center justify-center overflow-hidden" style="height: 110px;">
                            ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(title)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">` : `<i class="fa-brands fa-youtube text-4xl text-primary/40"></i>`}

                            <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-zinc-300 text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide z-10 font-mono">
                                Video
                            </div>
                        </div>

                        <div class="p-4 flex flex-col flex-grow relative z-10">
                            <h3 class="text-sm font-bold text-zinc-100 leading-tight mb-1 truncate" title="${escapeHtml(title)}">${escapeHtml(title)}</h3>
                            <p class="text-[11px] text-primary font-medium mb-3 font-mono"><i class="fa-regular fa-clock"></i> ${duration ? duration + ' seconds' : 'Watch Video'} <span class="ml-1 text-zinc-500 font-bold uppercase">${escapeHtml(typeLabel)}</span></p>

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

    window.activeAd = null;

    function handleAdClick(index, durationSec) {
        let task = currentFiltered[index] || {};
        window.activeAd = {
            clickTime: Date.now(),
            duration: durationSec * 1000,
            url: task.url || '#',
            notified: false
        };
    }

    if (offersGrid) {
        offersGrid.addEventListener('click', function (e) {
            const link = e.target.closest('a[data-ad-index]');
            if (!link) return;
            const index = parseInt(link.getAttribute('data-ad-index'), 10);
            const duration = parseInt(link.getAttribute('data-duration'), 10) || 0;
            handleAdClick(index, duration);
        });
    }

    if (reopenAdBtn) {
        reopenAdBtn.addEventListener('click', function () {
            prematureOverlay.classList.add('opacity-0');
            setTimeout(function () { prematureOverlay.classList.add('hidden'); }, 300);

            if (window.activeAd) {
                window.activeAd.clickTime = Date.now();
                window.activeAd.notified = false;
                reopenAdBtn.href = window.activeAd.url;
            }
        });
    }

    if (cancelAdBtn) {
        cancelAdBtn.addEventListener('click', function () {
            window.activeAd = null;
            prematureOverlay.classList.add('opacity-0');
            setTimeout(function () { prematureOverlay.classList.add('hidden'); }, 300);
            checkAdStatus();
        });
    }

    function checkAdStatus() {
        if (offersGrid) {
            offersGrid.innerHTML = '<div class="col-span-full text-center py-12"><i class="fa-solid fa-circle-notch fa-spin text-4xl text-primary mb-4 block"></i><h2 class="text-xl font-bold text-zinc-100 mb-2">Checking video status...</h2><p class="text-zinc-500">Updating available videos.</p></div>';
        }

        fetch('/user/ajax_video_fetch.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    offersData = data.tasks;
                }
                renderOffers();
            })
            .catch(function () {
                renderOffers();
            });
    }

    window.addEventListener('focus', function () {
        if (window.activeAd) {
            let elapsed = Date.now() - window.activeAd.clickTime;
            let required = window.activeAd.duration;

            if (elapsed < required) {
                if (!window.activeAd.notified) {
                    window.activeAd.notified = true;

                    window.activeAd.duration = required - elapsed;

                    let remaining = Math.ceil(window.activeAd.duration / 1000);
                    prematureSeconds.textContent = remaining;

                    prematureOverlay.classList.remove('hidden');
                    void prematureOverlay.offsetWidth;
                    prematureOverlay.classList.remove('opacity-0');
                }
            } else {
                window.activeAd = null;
                checkAdStatus();
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        renderOffers();
        refreshTopAdZone();
    });
})();