<?php ?>
    <footer class="bg-surface-card/60 relative z-20 pt-16 pb-8 mt-auto border-t border-white/10 text-zinc-400">
        <div class="container-custom">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-8 mb-12">
                <div class="flex flex-col">
                    <a href="<?= BASE_URL ?>/" class="flex items-center gap-2 mb-4">
                        <?php if ($_site['logo_type'] === 'image' && !empty($_site['logo_image'])): ?>
                        <img src="<?php echo htmlspecialchars($_site['logo_image']); ?>" alt="Logo" class="w-10 h-10 object-contain">
                        <?php else: ?>
                        <img src="<?= BASE_URL ?>/assets/iconhd.png" alt="USDT" class="w-8 h-8 md:w-10 md:h-10 object-contain logo-flip">
                        <span class="text-2xl font-bold tracking-tight text-zinc-100"><?php echo htmlspecialchars($_site['logo_text1']); ?><span class="text-primary"><?php echo htmlspecialchars($_site['logo_text2']); ?></span></span>
                        <?php endif; ?>
                    </a>
                    <p class="text-sm leading-relaxed mb-6">
                        <?php
                        if (!isset($landing_content)) {
                            $landing_content = json_decode(@file_get_contents(__DIR__ . '/../pages/landing_content.json'), true) ?? [];
                        }
                        echo htmlspecialchars($landing_content['footer']['text'] ?? 'BountyEarn helps members earn Tether USDT rewards by completing GPT offers, tasks, ads, and campaigns from trusted advertisers.');
                        ?>
                    </p>
                    <?php if (isset($_site['social_enabled']) && $_site['social_enabled'] == '1'): ?>
                    <div class="flex gap-3">
                        <a href="<?php echo htmlspecialchars($_site['social_facebook'] ?? '#'); ?>" target="_blank" class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-zinc-400 hover:text-primary hover:border-white/20 transition-all"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="<?php echo htmlspecialchars($_site['social_instagram'] ?? '#'); ?>" target="_blank" class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-zinc-400 hover:text-primary hover:border-white/20 transition-all"><i class="fa-brands fa-instagram"></i></a>
                        <a href="<?php echo htmlspecialchars($_site['social_twitter'] ?? '#'); ?>" target="_blank" class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-zinc-400 hover:text-primary hover:border-white/20 transition-all"><i class="fa-brands fa-twitter"></i></a>
                        <a href="<?php echo htmlspecialchars($_site['social_telegram'] ?? '#'); ?>" target="_blank" class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-zinc-400 hover:text-primary hover:border-white/20 transition-all"><i class="fa-brands fa-telegram"></i></a>
                    </div>
                    <?php endif; ?>
                </div>

                <div>
                    <h4 class="text-zinc-100 font-semibold mb-6">Earn Crypto</h4>
                    <ul class="flex flex-col gap-3 text-sm">
                        <?php if (isset($_site['video_enabled']) && $_site['video_enabled'] == '1'): ?>
                        <li><a href="<?= BASE_URL ?>/#video" class="hover:text-primary transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary/50"></i> Video</a></li>
                        <?php endif; ?>
                        <?php if (isset($_site['ptc_enabled']) && $_site['ptc_enabled'] == '1'): ?>
                        <li><a href="<?= BASE_URL ?>/#ptc" class="hover:text-primary transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary/50"></i> PTC Ads</a></li>
                        <?php endif; ?>
                        <?php if (offerwall_page_enabled($_site)): ?>
                        <li><a href="<?= BASE_URL ?>/#offerwall" class="hover:text-primary transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary/50"></i> Offerwall</a></li>
                        <?php endif; ?>
                        <?php if (isset($_site['shortlinks_enabled']) && $_site['shortlinks_enabled'] == '1'): ?>
                        <li><a href="<?= BASE_URL ?>/#shortlinks" class="hover:text-primary transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary/50"></i> Shortlinks</a></li>
                        <?php endif; ?>
                        <?php if (isset($_site['wheel_enabled']) && $_site['wheel_enabled'] == '1'): ?>
                        <li><a href="<?= BASE_URL ?>/#wheel" class="hover:text-primary transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary/50"></i> Wheel of Fortune</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div>
                    <h4 class="text-zinc-100 font-semibold mb-6"><?php echo htmlspecialchars($_site['site_name']); ?></h4>
                    <ul class="flex flex-col gap-3 text-sm">
                        <li><a href="<?= BASE_URL ?>/about" class="hover:text-primary transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary/50"></i> About Us</a></li>
                        <li><a href="<?= BASE_URL ?>/faq" class="hover:text-primary transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary/50"></i> FAQ</a></li>
                        <li><a href="<?= BASE_URL ?>/contact" class="hover:text-primary transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary/50"></i> Contact</a></li>
                        <li><a href="https://tether.to/" target="_blank" class="hover:text-primary transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[10px] text-primary/50"></i> Tether</a></li>
                    </ul>
                </div>

                <div>
                    <div class="bg-white/[0.02] p-6 rounded-lg border border-white/10 text-center md:text-left h-full flex flex-col justify-center hover:border-white/20 transition-colors">
                        <h4 class="text-zinc-100 font-semibold mb-3 text-lg">Earn more with BountyEarn</h4>
                        <p class="text-sm mb-6 text-zinc-500">Complete GPT offers, tasks, ads and videos to earn USDT rewards on BountyEarn.</p>
                        <a href="<?= BASE_URL ?>/" class="block w-full bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-4 rounded-lg transition-all mt-auto text-center">
                            Start Now
                        </a>
                    </div>
                </div>
            </div>

            <div class="pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-zinc-500">
                <p>&copy; <?php echo date('Y'); ?> <strong class="text-zinc-300"><?php echo htmlspecialchars($_site['site_name']); ?></strong>. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="<?= BASE_URL ?>/terms" class="hover:text-primary transition-colors">Terms of Service</a>
                    <a href="<?= BASE_URL ?>/privacy" class="hover:text-primary transition-colors">Privacy Policy</a>
                </div>
                <p class="text-xs text-zinc-600">
                    Powered by <strong class="text-zinc-400">BountyEarn</strong>
                </p>
            </div>
        </div>
    </footer>

    <div id="toast-container" class="toast-container"></div>
    <?php
    $general_ads = null;
    if (isset($_site['general_ads_enabled']) && $_site['general_ads_enabled'] == '1') {
        $active_pages = isset($_site['general_ads_pages']) ? json_decode($_site['general_ads_pages'], true) : [];
        if (!is_array($active_pages)) $active_pages = [];
        $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $current_path = rtrim($current_path, '/');
        if (empty($current_path)) $current_path = '/';

        if (in_array($current_path, $active_pages)) {
            $ads_file = __DIR__ . '/ads.json';
            $general_ads = ['general_top_ads' => [], 'general_bottom_ads' => []];
            if (file_exists($ads_file)) {
                $raw_ads = json_decode(file_get_contents($ads_file), true);
                $general_ads['general_top_ads'] = $raw_ads['general_top_ads'] ?? [];
                $general_ads['general_bottom_ads'] = $raw_ads['general_bottom_ads'] ?? [];
            }
        }
    }
    if ($general_ads !== null):
    ?>
    <script>
    window.OW_ADS_DATA = <?php echo json_encode($general_ads); ?>;
    </script>
    <?php endif; ?>
    <script src="<?= BASE_URL ?>/assets/js/core.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/assets/js/ads.js?v=<?= time() ?>"></script>
    <?php
    if (file_exists(__DIR__ . '/anti_adblock_generator.php')) {
        include_once __DIR__ . '/anti_adblock_generator.php';
        renderPolymorphicAntiAdblock();
    }
    ?>
</body>
</html>
