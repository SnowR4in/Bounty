(function () {
    'use strict';

    var adTimers = {};

    function filterAdsByDevice(ads) {
        var isMobile = window.innerWidth < 768;
        return ads.filter(function (ad) {
            if (ad.device === 'Desktop' && isMobile) return false;
            if (ad.device === 'Mobile' && !isMobile) return false;
            return true;
        });
    }

    function activateAdScripts(container) {
        Array.from(container.querySelectorAll('script')).forEach(function (oldScript) {
            var newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(function (attr) {
                newScript.setAttribute(attr.name, attr.value);
            });
            if (oldScript.innerHTML) {
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            }
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    function renderAd(zoneId, ads) {
        var container = document.getElementById('ad-container-' + zoneId);
        if (!container || !ads || ads.length === 0) return;

        if (adTimers[zoneId]) clearTimeout(adTimers[zoneId]);

        var validAds = filterAdsByDevice(ads);
        if (validAds.length === 0) return;

        validAds.sort(function (a, b) { return b.priority - a.priority; });
        var maxPriority = validAds[0].priority;
        var topAds = validAds.filter(function (ad) { return ad.priority === maxPriority; });
        var selectedAd = topAds[Math.floor(Math.random() * topAds.length)];

        container.innerHTML = selectedAd.code;
        activateAdScripts(container);

        if (selectedAd.refresh === '30 seconds') {
            adTimers[zoneId] = setTimeout(function () { renderAd(zoneId, ads); }, 30000);
        } else if (selectedAd.refresh === '60 seconds') {
            adTimers[zoneId] = setTimeout(function () { renderAd(zoneId, ads); }, 60000);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var data = window.OW_LANDING_ADS;
        if (!data) return;

        if (data.login_ads && data.login_ads.length > 0) {
            renderAd('login_ads', data.login_ads);
        }
        if (data.payments_ads && data.payments_ads.length > 0) {
            renderAd('payments_ads', data.payments_ads);
        }

        window.addEventListener('resize', function () {
            if (data.login_ads) renderAd('login_ads', data.login_ads);
            if (data.payments_ads) renderAd('payments_ads', data.payments_ads);
        });
    });
})();