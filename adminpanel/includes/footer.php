<?php ?>
        <footer class="bg-surface-card/60 relative z-20 py-6 mt-auto border-t border-white/10 text-zinc-400 w-full">
            <div class="px-6 lg:px-12 max-w-[1400px] mx-auto flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-zinc-500">
                <p>&copy; <?php echo date('Y'); ?> <strong class="text-zinc-300"><?php echo htmlspecialchars($_site['site_name'] ?? 'USDTFaucet'); ?></strong>. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="<?= BASE_URL ?>/terms" class="hover:text-primary transition-colors">Terms of Service</a>
                    <a href="<?= BASE_URL ?>/privacy" class="hover:text-primary transition-colors">Privacy Policy</a>
                </div>
                <p class="text-xs text-zinc-600">
                    Powered by <a href="https://offerwallapi.com" target="_blank" class="hover:text-primary font-semibold">OfferwallApi</a> | USDT Faucet Script
                </p>
            </div>
        </footer>
    </div>

    <div id="toast-container" class="toast-container"></div>
    <?php
    $flash_messages = [];
    if (isset($_SESSION['success_msg']) && !empty($_SESSION['success_msg'])) {
        $flash_messages[] = ['type' => 'success', 'message' => $_SESSION['success_msg']];
        unset($_SESSION['success_msg']);
    }
    if (isset($_SESSION['error_msg']) && !empty($_SESSION['error_msg'])) {
        $flash_messages[] = ['type' => 'error', 'message' => $_SESSION['error_msg']];
        unset($_SESSION['error_msg']);
    }
    ?>
    <div id="flash-messages" class="hidden" data-messages='<?php echo json_encode($flash_messages); ?>'></div>
    <script src="<?= BASE_URL ?>/assets/js/admin/admin-core.js?v=<?= time() ?>"></script>
</body>
</html>
