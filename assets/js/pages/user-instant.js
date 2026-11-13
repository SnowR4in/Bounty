(function () {
    'use strict';

    const pageData = window.OW_PAGE_DATA || {};
    const csrfToken = pageData.csrfToken || '';
    let timeRemaining = parseInt(pageData.timeRemaining, 10) || 0;
    let isClaiming = false;

    const adTimers = {};

    function formatTime(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
    }

    function startTimer() {
        const display = document.getElementById('countdown-display');
        const interval = setInterval(function () {
            timeRemaining--;
            if (timeRemaining <= 0) {
                clearInterval(interval);

                const soundToggle = document.getElementById('sound-toggle');
                if (soundToggle && soundToggle.checked) {
                    document.getElementById('notify-sound').play().catch(function (e) { console.log('Audio play failed', e); });
                    setTimeout(function () { window.location.reload(); }, 1500);
                } else {
                    window.location.reload();
                }
            } else {
                if (display) display.innerText = formatTime(timeRemaining);
            }
        }, 1000);
    }

    async function claimFaucet() {
        if (isClaiming) return;

        const btn = document.getElementById('claim-btn');
        const originalLabel = btn.textContent.trim();
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
        btn.disabled = true;
        isClaiming = true;

        try {
            const adBlockCheck = window.canRunAds === true && !window.isAdblockActive;
            const formData = new FormData();
            formData.append('type', 'instant');
            formData.append('csrf_token', csrfToken);
            formData.append('adBlockCheck', adBlockCheck);

            const recaptchaInput = document.querySelector('[name="g-recaptcha-response"]');
            if (recaptchaInput) formData.append('g-recaptcha-response', recaptchaInput.value);
            const turnstileInput = document.querySelector('[name="cf-turnstile-response"]');
            if (turnstileInput) formData.append('cf-turnstile-response', turnstileInput.value);

            const headers = new Headers();
            if (typeof window.getCFVerificationToken === 'function') {
                headers.append('x-sec-auth', window.getCFVerificationToken());
            }

            const response = await fetch('ajax_claim.php', {
                method: 'POST',
                body: formData,
                headers: headers
            });

            const result = await response.json();

            if (result.success) {
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Success!';
                setTimeout(function () {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('error', result.error || 'An error occurred. Please try again.');
                btn.innerHTML = originalLabel;
                btn.disabled = false;
                isClaiming = false;
            }
        } catch (error) {
            showToast('error', 'Network error. Please try again.');
            btn.innerHTML = originalLabel;
            btn.disabled = false;
            isClaiming = false;
        }
    }

    function toggleSound(isChecked) {
        const formData = new FormData();
        formData.append('play_sound', isChecked ? '1' : '0');
        fetch('ajax_sound_setting.php', {
            method: 'POST',
            body: formData
        });
    }

    async function handleBotHoneypot(e) {
        e.preventDefault();
        try {
            const response = await fetch('ajax_honeypot.php', {
                method: 'POST'
            });
            const result = await response.json();
            if (typeof showToast === 'function') {
                showToast('success', result.message || 'Bonus claimed successfully!');
            }
        } catch (error) {
            console.error(error);
        }
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

    function renderFaucetAd(zoneId, ads) {
        const container = document.getElementById('ad-container-' + zoneId);
        if (!container) return;

        const isMobile = window.innerWidth < 1280;
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
            adTimers[zoneId] = setTimeout(function () { renderFaucetAd(zoneId, ads); }, 30000);
        } else if (selectedAd.refresh === '60 seconds') {
            adTimers[zoneId] = setTimeout(function () { renderFaucetAd(zoneId, ads); }, 60000);
        }
    }

    function renderFaucetAds() {
        const ads = pageData.ads;
        if (!ads) return;
        ['faucet_top_ads', 'faucet_left_ads', 'faucet_right_ads', 'faucet_captcha_ads', 'faucet_bottom_ads'].forEach(function (zone) {
            if (ads[zone] && ads[zone].length > 0) {
                renderFaucetAd(zone, ads[zone]);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const claimBtn = document.getElementById('claim-btn');
        if (claimBtn) claimBtn.addEventListener('click', claimFaucet);

        const soundToggle = document.getElementById('sound-toggle');
        if (soundToggle) soundToggle.addEventListener('change', function () { toggleSound(this.checked); });

        const honeypotBtn = document.getElementById('bot-honeypot-btn');
        if (honeypotBtn) honeypotBtn.addEventListener('click', handleBotHoneypot);

        if (timeRemaining > 0) startTimer();

        renderFaucetAds();
    });
})();