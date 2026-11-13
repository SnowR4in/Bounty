(function () {
    'use strict';

    window.showToast = function (type, message) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;

        const icon = type === 'success'
            ? '<i class="fa-solid fa-circle-check text-2xl"></i>'
            : '<i class="fa-solid fa-circle-exclamation text-2xl"></i>';

        toast.innerHTML = icon +
            '<div class="flex-1 text-sm font-medium text-zinc-200">' + message + '</div>' +
            '<button class="text-zinc-500 hover:text-zinc-300 outline-none" data-toast-close><i class="fa-solid fa-xmark"></i></button>' +
            '<div class="toast-progress"></div>';

        toast.querySelector('[data-toast-close]').addEventListener('click', function () {
            toast.remove();
        });

        container.appendChild(toast);

        setTimeout(function () {
            if (toast.parentElement) {
                toast.classList.add('hiding');
                setTimeout(function () { toast.remove(); }, 300);
            }
        }, 3000);
    };

    function initFlashToasts() {
        const flash = document.getElementById('flash-messages');
        if (!flash) return;
        try {
            const messages = JSON.parse(flash.getAttribute('data-messages') || '[]');
            messages.forEach(function (item) {
                window.showToast(item.type, item.message);
            });
        } catch (e) { /* invalid payload — ignore */ }
    }

    function initMobileMenu() {
        const menuOpen = document.getElementById('mobile-menu-open');
        const menuClose = document.getElementById('mobile-menu-close');
        const menu = document.getElementById('mobile-menu');
        const overlay = document.getElementById('mobile-overlay');

        if (!menuOpen || !menu || !overlay) return;

        function openMenu() {
            overlay.classList.remove('hidden');
            setTimeout(function () {
                overlay.classList.remove('opacity-0');
                menu.classList.remove('-translate-x-full');
            }, 10);
        }

        function closeMenu() {
            menu.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(function () {
                overlay.classList.add('hidden');
            }, 300);
        }

        menuOpen.addEventListener('click', openMenu);
        if (menuClose) menuClose.addEventListener('click', closeMenu);
        overlay.addEventListener('click', closeMenu);
    }

    function initHeaderScroll() {
        const header = document.getElementById('main-header');
        if (!header) return;

        function onScroll() {
            if (window.scrollY > 20) {
                header.classList.remove('bg-transparent', 'border-transparent');
                header.classList.add('bg-surface/90', 'backdrop-blur-md', 'border-surface-line', 'shadow-soft');
            } else {
                header.classList.add('bg-transparent', 'border-transparent');
                header.classList.remove('bg-surface/90', 'backdrop-blur-md', 'border-surface-line', 'shadow-soft');
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    function filterAdsByDevice(ads) {
        const isMobile = window.innerWidth < 768;
        return ads.filter(function (ad) {
            if (ad.device === 'Desktop' && isMobile) return false;
            if (ad.device === 'Mobile' && !isMobile) return false;
            return true;
        });
    }

    function pickAdByPriority(ads) {
        if (ads.length === 0) return null;
        const sorted = ads.slice().sort(function (a, b) { return b.priority - a.priority; });
        const maxPriority = sorted[0].priority;
        const top = sorted.filter(function (ad) { return ad.priority === maxPriority; });
        return top[Math.floor(Math.random() * top.length)];
    }

    function activateAdScripts(container) {
        Array.from(container.querySelectorAll('script')).forEach(function (oldScript) {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(function (attr) {
                newScript.setAttribute(attr.name, attr.value);
            });
            if (oldScript.innerHTML) {
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            }
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    window.renderGeneralAd = function (zoneId, ads) {
        const container = document.getElementById('ad-container-' + zoneId);
        if (!container) return;

        const selectedAd = pickAdByPriority(filterAdsByDevice(ads));
        if (!selectedAd) return;

        container.innerHTML = selectedAd.code;
        activateAdScripts(container);

        clearTimeout(window.OWAdTimers[zoneId]);
        if (selectedAd.refresh === '30 seconds') {
            window.OWAdTimers[zoneId] = setTimeout(function () { window.renderGeneralAd(zoneId, ads); }, 30000);
        } else if (selectedAd.refresh === '60 seconds') {
            window.OWAdTimers[zoneId] = setTimeout(function () { window.renderGeneralAd(zoneId, ads); }, 60000);
        }
    };

    function initGeneralAds() {
        window.OWAdTimers = window.OWAdTimers || {};
        const data = window.OW_ADS_DATA;
        if (!data) return;

        ['general_top_ads', 'general_bottom_ads'].forEach(function (zone) {
            if (data[zone] && data[zone].length > 0) {
                const el = document.getElementById('ad-container-' + zone);
                if (el && !el.innerHTML) {
                    window.renderGeneralAd(zone, data[zone]);
                }
            }
        });
    }

    function initFloatingAds() {
        const data = window.OW_FLOATING_ADS;
        if (!data || data.length === 0) return;

        const validAds = filterAdsByDevice(data);
        if (validAds.length === 0) return;

        const sorted = validAds.slice().sort(function (a, b) { return b.priority - a.priority; });
        const maxPriority = sorted[0].priority;
        const topAds = sorted.filter(function (ad) { return ad.priority === maxPriority; });

        const wrapper = document.createElement('div');
        wrapper.id = 'floating-ad-wrapper';
        wrapper.className = 'floating-ad-wrapper';
        wrapper.innerHTML =
            '<button data-floating-close class="absolute top-[6px] left-[6px] w-[12px] h-[12px] bg-red-500/90 text-white rounded-sm flex items-center justify-center shadow z-20 hover:bg-red-600 transition-colors">' +
            '<i class="fa-solid fa-xmark text-[6px]"></i></button>' +
            '<div id="ad-container-floating_ads" class="relative text-sm text-zinc-500 leading-none flex">Loading Ad...</div>';

        document.body.appendChild(wrapper);
        wrapper.querySelector('[data-floating-close]').addEventListener('click', function () {
            wrapper.style.display = 'none';
        });

        let adTimer = null;

        function renderAd() {
            const container = document.getElementById('ad-container-floating_ads');
            if (!container) return;

            const selectedAd = topAds[Math.floor(Math.random() * topAds.length)];
            container.innerHTML = selectedAd.code;
            activateAdScripts(container);

            clearTimeout(adTimer);
            if (selectedAd.refresh === '30 seconds') {
                adTimer = setTimeout(renderAd, 30000);
            } else if (selectedAd.refresh === '60 seconds') {
                adTimer = setTimeout(renderAd, 60000);
            }
        }

        renderAd();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initFlashToasts();
        initMobileMenu();
        initHeaderScroll();
        initGeneralAds();
        initFloatingAds();
    });
})();