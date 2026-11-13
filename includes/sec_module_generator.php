<?php

function renderPolymorphicSecModule() {
    $var_cfState = 'cfs_' . bin2hex(random_bytes(4));
    $var_trackMouse = 'tm_' . bin2hex(random_bytes(4));
    $var_trackClick = 'tc_' . bin2hex(random_bytes(4));
    $var_trackTouch = 'tt_' . bin2hex(random_bytes(4));
    $var_getCanvasFP = 'gfp_' . bin2hex(random_bytes(4));
    $var_getWebGL = 'gl_' . bin2hex(random_bytes(4));
    $var_getTrusted = 'gt_' . bin2hex(random_bytes(4));
    $var_genPayload = 'gp_' . bin2hex(random_bytes(4));
    $var_injectForms = 'inj_' . bin2hex(random_bytes(4));

    $js = <<<JS
<script>
(function() {
    const {$var_cfState} = {
        mouseMovements: 0,
        clickCount: 0,
        touchEvents: 0,
        maxTouchPoints: navigator.maxTouchPoints || 0,
        webdriver: navigator.webdriver || false,
        screenWidth: window.screen.width,
        screenHeight: window.screen.height,
        colorDepth: window.screen.colorDepth,
        timezoneOffset: new Date().getTimezoneOffset(),
        deviceMemory: navigator.deviceMemory || 0,
        hardwareConcurrency: navigator.hardwareConcurrency || 0,
        webglRenderer: 'unknown',
        canvasFingerprint: 'unknown'
    };

    const {$var_trackMouse} = () => { {$var_cfState}.mouseMovements++; };
    const {$var_trackClick} = () => { {$var_cfState}.clickCount++; };
    const {$var_trackTouch} = () => { {$var_cfState}.touchEvents++; };

    window.addEventListener('mousemove', {$var_trackMouse}, true);
    window.addEventListener('pointerdown', {$var_trackClick}, true);
    window.addEventListener('keydown', {$var_trackClick}, true);
    window.addEventListener('touchstart', {$var_trackTouch}, true);
    window.addEventListener('touchmove', {$var_trackTouch}, true);

    function {$var_getCanvasFP}() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 200;
            canvas.height = 50;
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('usdtfaucet_fp_test', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('usdtfaucet_fp_test', 4, 17);
            
            const dataURL = canvas.toDataURL();
            let hash = 0;
            for (let i = 0; i < dataURL.length; i++) {
                const char = dataURL.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return Math.abs(hash).toString(16);
        } catch (e) {
            return 'error';
        }
    }

    function {$var_getWebGL}() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || (canvas.getContext && canvas.getContext('experimental-webgl'));
            if (gl) {
                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                if (debugInfo) {
                    return gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
                }
            }
            return 'unknown';
        } catch (e) {
            return 'unknown';
        }
    }

    function {$var_getTrusted}() {
        let token = localStorage.getItem('_cf_trusted_token');
        if (!token) {
            token = 'cf-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now().toString(36);
            localStorage.setItem('_cf_trusted_token', token);
        }
        return token;
    }

    function {$var_genPayload}() {
        if ({$var_cfState}.canvasFingerprint === 'unknown') {
            {$var_cfState}.canvasFingerprint = {$var_getCanvasFP}();
            {$var_cfState}.webglRenderer = {$var_getWebGL}();
        }

        const payload = {
            signals: {$var_cfState},
            trustedToken: {$var_getTrusted}(),
            timestamp: Date.now(),
            page: window.location.pathname || 'unknown'
        };

        return btoa(JSON.stringify(payload));
    }

    function {$var_injectForms}() {
        const forms = document.querySelectorAll('form');
        const payload = {$var_genPayload}();
        
        forms.forEach(form => {
            if (!form.querySelector('input[name="_cf_verification"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_cf_verification';
                input.value = payload;
                form.appendChild(input);
            } else {
                form.querySelector('input[name="_cf_verification"]').value = payload;
            }
        });
    }

    window.getCFVerificationToken = function() {
        return {$var_genPayload}();
    };

    document.addEventListener('DOMContentLoaded', {$var_injectForms});
    document.addEventListener('submit', {$var_injectForms});

})();
</script>
JS;

    echo $js;
}
?>
