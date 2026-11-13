(function () {
    'use strict';

    const pageData = window.OW_PAGE_DATA || {};
    const adTimers = {};

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

    function renderDashboardAd(zoneId, ads) {
        const container = document.getElementById('ad-container-' + zoneId);
        if (!container) return;

        const isMobile = window.innerWidth < 768;
        const validAds = ads.filter(function (ad) {
            if (ad.device === 'Desktop' && isMobile) return false;
            if (ad.device === 'Mobile' && !isMobile) return false;
            return true;
        });

        if (validAds.length === 0) return;

        validAds.sort(function (a, b) { return b.priority - a.priority; });
        const maxPriority = validAds[0].priority;
        const topAds = validAds.filter(function (ad) { return ad.priority === maxPriority; });
        const selectedAd = topAds[Math.floor(Math.random() * topAds.length)];

        container.innerHTML = selectedAd.code;
        activateAdScripts(container);

        clearTimeout(adTimers[zoneId]);
        if (selectedAd.refresh === '30 seconds') {
            adTimers[zoneId] = setTimeout(function () { renderDashboardAd(zoneId, ads); }, 30000);
        } else if (selectedAd.refresh === '60 seconds') {
            adTimers[zoneId] = setTimeout(function () { renderDashboardAd(zoneId, ads); }, 60000);
        }
    }

    function renderAllDashboardAds() {
        const ads = pageData.ads;
        if (!ads) return;
        ['dashboard_top_ads', 'dashboard_level_ads', 'dashboard_ways_ads'].forEach(function (zone) {
            if (ads[zone] && ads[zone].length > 0) {
                renderDashboardAd(zone, ads[zone]);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        renderAllDashboardAds();
        window.addEventListener('resize', renderAllDashboardAds);
    });
})();