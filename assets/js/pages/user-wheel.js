(function () {
    'use strict';

    const pageData = window.OW_PAGE_DATA || {};

    const SEGMENT_COLOR_MAP = {
        '#f97316': '#34d399',
        '#ea580c': '#10b981',
        '#c2410c': '#059669'
    };

    function normalizeSegment(segment) {
        const mapped = SEGMENT_COLOR_MAP[String(segment.color || '').toLowerCase()];
        if (!mapped) return segment;
        const hex = mapped.replace('#', '');
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
        return {
            id: segment.id,
            text: segment.text,
            color: mapped,
            textCol: yiq >= 128 ? '#000000' : '#ffffff',
            weight: segment.weight
        };
    }

    const segments = (pageData.segments || []).map(normalizeSegment);
    const energyCost = parseInt(pageData.energyCost, 10) || 0;
    let userEnergy = parseInt(pageData.userEnergy, 10) || 0;
    const wheelToken = pageData.wheelToken || '';
    const wheelCaptchaType = pageData.wheelCaptchaType || '';

    const canvas = document.getElementById('wheelCanvas');
    const ctx = canvas.getContext('2d');
    const offscreenCanvas = document.createElement('canvas');
    const offscreenCtx = offscreenCanvas.getContext('2d');
    let wheelIsCached = false;

    const gameContainer = document.getElementById('gameContainer');
    const spinBtn = document.getElementById('spinBtn');

    let currentAngle = 0;
    let isSpinning = false;
    let targetAngle = 0;

    function resizeCanvas() {
        const dpr = window.devicePixelRatio || 1;
        const containerWidth = gameContainer.offsetWidth;
        canvas.width = containerWidth * dpr;
        canvas.height = containerWidth * dpr;
        ctx.scale(dpr, dpr);

        offscreenCanvas.width = canvas.width;
        offscreenCanvas.height = canvas.height;
        offscreenCtx.scale(dpr, dpr);
        wheelIsCached = false;
    }

    function cacheWheel() {
        const containerWidth = gameContainer.offsetWidth;
        const centerX = containerWidth / 2;
        const centerY = containerWidth / 2;
        const radius = containerWidth / 2 - 15;

        offscreenCtx.clearRect(0, 0, containerWidth, containerWidth);

        const rimGrad = offscreenCtx.createLinearGradient(0, 0, containerWidth, containerWidth);
        rimGrad.addColorStop(0, '#3f3f46');
        rimGrad.addColorStop(0.5, '#52525b');
        rimGrad.addColorStop(1, '#3f3f46');
        offscreenCtx.beginPath();
        offscreenCtx.arc(centerX, centerY, radius + 10, 0, 2 * Math.PI);
        offscreenCtx.fillStyle = rimGrad;
        offscreenCtx.fill();

        offscreenCtx.beginPath();
        offscreenCtx.arc(centerX, centerY, radius + 2, 0, 2 * Math.PI);
        offscreenCtx.fillStyle = '#0b0c0e';
        offscreenCtx.fill();

        const arcSize = (2 * Math.PI) / segments.length;
        for (let i = 0; i < segments.length; i++) {
            const angle = i * arcSize;
            offscreenCtx.beginPath();
            offscreenCtx.moveTo(centerX, centerY);
            offscreenCtx.arc(centerX, centerY, radius, angle, angle + arcSize);

            offscreenCtx.fillStyle = segments[i].color;
            offscreenCtx.fill();
            offscreenCtx.strokeStyle = 'rgba(0,0,0,0.1)';
            offscreenCtx.lineWidth = 1;
            offscreenCtx.stroke();

            offscreenCtx.save();
            offscreenCtx.translate(centerX, centerY);
            offscreenCtx.rotate(angle + arcSize / 2);
            offscreenCtx.textAlign = 'right';
            offscreenCtx.fillStyle = segments[i].textCol;
            offscreenCtx.font = 'bold ' + Math.max(10, radius * 0.1) + 'px Geist, sans-serif';
            offscreenCtx.fillText(segments[i].text, radius - 20, 6);
            offscreenCtx.restore();
        }

        offscreenCtx.beginPath();
        offscreenCtx.arc(centerX, centerY, 20, 0, 2 * Math.PI);
        offscreenCtx.fillStyle = '#18181b';
        offscreenCtx.fill();
        offscreenCtx.strokeStyle = '#27272a';
        offscreenCtx.lineWidth = 3;
        offscreenCtx.stroke();

        wheelIsCached = true;
    }

    function drawWheel(angleDeg) {
        const containerWidth = gameContainer.offsetWidth;
        const centerX = containerWidth / 2;
        const centerY = containerWidth / 2;
        const radius = containerWidth / 2 - 15;

        if (!wheelIsCached) cacheWheel();

        ctx.clearRect(0, 0, containerWidth, containerWidth);

        ctx.save();
        ctx.translate(centerX, centerY);
        ctx.rotate((angleDeg * Math.PI) / 180);
        ctx.translate(-centerX, -centerY);

        ctx.drawImage(offscreenCanvas, 0, 0, containerWidth, containerWidth);
        ctx.restore();

        const time = Date.now();
        const blink = Math.floor(time / 500) % 2;
        for (let i = 0; i < 24; i++) {
            const a = (i / 24) * (2 * Math.PI);
            const lx = centerX + Math.cos(a) * (radius + 6);
            const ly = centerY + Math.sin(a) * (radius + 6);
            ctx.beginPath();
            ctx.arc(lx, ly, 4, 0, 2 * Math.PI);
            ctx.fillStyle = i % 2 === blink ? '#34d399' : '#3f3f46';
            ctx.fill();
        }
    }

    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    let lastBlink = -1;
    function idleLoop() {
        if (!isSpinning) {
            const time = Date.now();
            const blink = Math.floor(time / 500) % 2;
            if (blink !== lastBlink) {
                drawWheel(currentAngle);
                lastBlink = blink;
            }
        }
        requestAnimationFrame(idleLoop);
    }

    function updateEnergyUI() {
        document.getElementById('userEnergyDisplay').innerText = userEnergy;
        if (userEnergy < energyCost) {
            spinBtn.disabled = true;
        } else {
            spinBtn.disabled = false;
        }
    }

    async function requestSpin() {
        if (isSpinning) return;
        if (userEnergy < energyCost) {
            showToast('error', 'Not enough energy!');
            return;
        }

        if (wheelCaptchaType === 'google' || wheelCaptchaType === 'turnstile') {
            document.getElementById('captchaModal').classList.add('active');
            return;
        }

        executeSpin('');
    }

    async function submitCaptchaSpin() {
        const fieldName = wheelCaptchaType === 'google' ? 'g-recaptcha-response' : 'cf-turnstile-response';
        const captchaInput = document.querySelector('[name="' + fieldName + '"]');
        const captchaVal = captchaInput ? captchaInput.value : '';
        if (!captchaVal) {
            showToast('error', 'Please complete the captcha.');
            return;
        }

        document.getElementById('captchaModal').classList.remove('active');
        executeSpin(captchaVal);
    }

    async function executeSpin(captchaResponse) {
        isSpinning = true;
        spinBtn.disabled = true;

        userEnergy -= energyCost;
        updateEnergyUI();

        gameContainer.classList.add('shake-active');
        setTimeout(function () { gameContainer.classList.remove('shake-active'); }, 500);

        try {
            let authHeader = '';
            if (typeof window.getCFVerificationToken === 'function') {
                authHeader = window.getCFVerificationToken();
            }

            const formData = new URLSearchParams();
            formData.append('token', wheelToken);
            if (captchaResponse) {
                const fieldName = wheelCaptchaType === 'google' ? 'g-recaptcha-response' : 'cf-turnstile-response';
                formData.append(fieldName, captchaResponse);
            }

            const res = await fetch('ajax_wheel_spin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Sec-Auth': authHeader
                },
                body: formData.toString()
            });

            const data = await res.json();

            if (data.status === 'success') {
                let winningIndex = -1;
                for (let i = 0; i < segments.length; i++) {
                    if (segments[i].id == data.winning_segment_id) {
                        winningIndex = i;
                        break;
                    }
                }

                if (winningIndex === -1) {
                    throw new Error('Winning segment not found locally');
                }

                animateWheel(winningIndex, data);
            } else {
                userEnergy += energyCost;
                updateEnergyUI();
                isSpinning = false;
                spinBtn.disabled = false;
                showToast('error', data.message || 'Error spinning wheel');
            }
        } catch (err) {
            console.error(err);
            userEnergy += energyCost;
            updateEnergyUI();
            isSpinning = false;
            spinBtn.disabled = false;
            showToast('error', 'Network error occurred.');
        }
    }

    function animateWheel(winningIndex, data) {
        const segmentDegrees = 360 / segments.length;
        const prizeAngle = winningIndex * segmentDegrees + segmentDegrees / 2;
        const target = (270 - prizeAngle + 360) % 360;

        let distance = target - (currentAngle % 360);
        if (distance < 0) distance += 360;
        distance += 8 * 360;

        const startAngle = currentAngle;
        const duration = 6000;
        let startTime = null;

        function animate(now) {
            if (!startTime) startTime = now;
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const ease = easeOutCubic(progress);
            currentAngle = startAngle + distance * ease;

            drawWheel(currentAngle);

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                finishSpin(data);
            }
        }
        requestAnimationFrame(animate);
    }

    function finishSpin(data) {
        isSpinning = false;

        if (data.new_energy !== undefined) {
            userEnergy = parseInt(data.new_energy);
            updateEnergyUI();
        }

        const titleEl = document.getElementById('modalTitle');
        const msgEl = document.getElementById('winnerMsg');
        const iconEl = document.getElementById('modalIcon');
        const overlay = document.getElementById('modal');

        if (data.reward_value > 0) {
            if (typeof confetti === 'function') {
                confetti({
                    particleCount: 40,
                    spread: 60,
                    origin: { y: 0.6 },
                    ticks: 100,
                    gravity: 1.5,
                    disableForReducedMotion: true
                });
            }
            iconEl.innerText = data.reward_type === 'USDT' ? '💎' : '⚡';
            titleEl.innerText = 'WINNER!';
            titleEl.style.color = '#34d399';
            msgEl.innerText = 'You won ' + data.reward_value + ' ' + data.reward_type + '!';
        } else {
            iconEl.innerText = '😢';
            titleEl.innerText = 'UNLUCKY';
            titleEl.style.color = '#ff6568';
            msgEl.innerText = 'Better luck next time.';
        }

        overlay.classList.add('active');
    }

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

    function renderWheelAd(zoneId, ads) {
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
            adTimers[zoneId] = setTimeout(function () { renderWheelAd(zoneId, ads); }, 30000);
        } else if (selectedAd.refresh === '60 seconds') {
            adTimers[zoneId] = setTimeout(function () { renderWheelAd(zoneId, ads); }, 60000);
        }
    }

    function renderWheelAds() {
        const ads = pageData.ads;
        if (!ads) return;
        ['wheel_top_ads', 'wheel_left_ads', 'wheel_right_ads', 'wheel_bottom_ads'].forEach(function (zone) {
            if (ads[zone] && ads[zone].length > 0) {
                renderWheelAd(zone, ads[zone]);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (spinBtn) spinBtn.addEventListener('click', requestSpin);

        const modalClaimBtn = document.getElementById('modalClaimBtn');
        if (modalClaimBtn) modalClaimBtn.addEventListener('click', function () { window.location.reload(); });

        const captchaSubmitBtn = document.getElementById('captchaSubmitBtn');
        if (captchaSubmitBtn) captchaSubmitBtn.addEventListener('click', submitCaptchaSpin);

        document.querySelectorAll('[data-captcha-modal-close]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('captchaModal').classList.remove('active');
            });
        });

        resizeCanvas();
        drawWheel(currentAngle);
        updateEnergyUI();
        idleLoop();
        renderWheelAds();
    });

    window.addEventListener('resize', function () {
        resizeCanvas();
        drawWheel(currentAngle);
    });
})();