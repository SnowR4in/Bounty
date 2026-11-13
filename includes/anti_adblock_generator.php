<?php

function renderPolymorphicAntiAdblock() {
    $blocked_classes = [
        'ad-banner', 'sponsor', 'advertisement', 'pub_300x250', 'ad-placement',
        'ad-wrapper', 'text-ad', 'banner-ad', 'google-ad', 'adsbygoogle'
    ];

    shuffle($blocked_classes);
    $honeypot_classes = implode(' ', array_slice($blocked_classes, 0, 3));

    $var_adblockDetected = 'ad_' . bin2hex(random_bytes(4));
    $var_honeypot = 'hp_' . bin2hex(random_bytes(4));
    $var_style = 'st_' . bin2hex(random_bytes(4));
    $var_overlay = 'ov_' . bin2hex(random_bytes(4));
    $var_isBlocked = 'blk_' . bin2hex(random_bytes(4));

    $timeout = rand(1200, 1800);
    $left_pos = '-9' . rand(100, 999) . 'px';

    $js = <<<JS
<script>
document.addEventListener("DOMContentLoaded", function() {
    let {$var_isBlocked} = false;

    async function checkAdblock() {
        let {$var_adblockDetected} = false;
        
        if (window.canRunAds !== true && window.hasAds !== true) { 
            {$var_adblockDetected} = true; 
        }

        const {$var_honeypot} = document.createElement('div');
        {$var_honeypot}.className = '{$honeypot_classes}';
        {$var_honeypot}.innerHTML = '&nbsp;';
        {$var_honeypot}.style.width = '10px';
        {$var_honeypot}.style.height = '10px';
        {$var_honeypot}.style.position = 'absolute';
        {$var_honeypot}.style.left = '{$left_pos}';
        document.body.appendChild({$var_honeypot});

        const {$var_style} = window.getComputedStyle({$var_honeypot});
        if ({$var_honeypot}.offsetHeight === 0 || {$var_style}.display === 'none' || {$var_style}.visibility === 'hidden') {
            {$var_adblockDetected} = true;
        }
        document.body.removeChild({$var_honeypot});

        if (!{$var_adblockDetected}) {
            try {
                await fetch('https://cdn.bmcdn6.com/js/69830aa314652254095848c1.js?r=' + Math.random(), { method: 'HEAD', mode: 'no-cors', cache: 'no-store' });
            } catch (e) {
                try {
                    await fetch('https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?r=' + Math.random(), { method: 'HEAD', mode: 'no-cors', cache: 'no-store' });
                } catch (err) {
                    {$var_adblockDetected} = true;
                }
            }
        }

        if ({$var_adblockDetected}) {
            window.isAdblockActive = true;
            if (!{$var_isBlocked}) {
                {$var_isBlocked} = true;
                
                if (!document.getElementById('adblock-overlay')) {
                    const {$var_overlay} = document.createElement('div');
                    {$var_overlay}.id = 'adblock-overlay';
                    {$var_overlay}.className = 'fixed inset-0 z-[99999] flex flex-col items-center justify-center';
                    {$var_overlay}.style.backgroundColor = '#0b0c0e';
                    {$var_overlay}.style.position = 'fixed';
                    {$var_overlay}.style.top = '0';
                    {$var_overlay}.style.left = '0';
                    {$var_overlay}.style.width = '100vw';
                    {$var_overlay}.style.height = '100vh';
                    {$var_overlay}.style.zIndex = '99999';

                    {$var_overlay}.innerHTML = `
                        <div class="bg-surface-card p-8 md:p-12 rounded-3xl shadow-2xl border border-white/10 max-w-lg text-center relative overflow-hidden mx-4 w-full" style="max-width: 32rem;">
                            <div class="absolute -right-10 -top-10 text-primary/10 text-9xl"><i class="fa-solid fa-shield-halved"></i></div>
                            <div class="w-24 h-24 bg-red-500/10 text-red-400 rounded-full flex items-center justify-center text-5xl mx-auto mb-6 relative z-10 shadow-inner">
                                <i class="fa-solid fa-hand-paper"></i>
                            </div>
                            <h1 class="text-3xl font-bold text-zinc-100 mb-4 relative z-10">Adblock Detected!</h1>
                            <p class="text-zinc-400 mb-8 text-lg relative z-10">
                                We rely on advertising revenue to keep providing you with free crypto rewards. Please disable your adblocker to continue using BountyEarn.
                            </p>
                            <button onclick="window.location.reload()" class="w-full bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-4 px-8 rounded-xl text-lg transition-all shadow-md hover:shadow-lg hover:-translate-y-1 relative z-10">
                                I have disabled it, reload page
                            </button>
                        </div>
                    `;
                    document.body.appendChild({$var_overlay});
                    document.body.style.overflow = 'hidden';
                }
            }
        } else {
            window.isAdblockActive = false;
        }
    }

    checkAdblock();
    setTimeout(checkAdblock, {$timeout});
});
</script>
JS;

    echo $js;
}
?>
