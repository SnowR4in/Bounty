<?php
include 'includes/landingheader.php';
?>

<div class="relative z-10 pt-32 pb-16 min-h-[60vh]">
    <div class="container-custom">
        <div id="ad-container-general_top_ads" class="mb-6 flex justify-center empty:hidden w-full min-h-[90px]"></div>
        <div class="bg-white/[0.02] p-8 md:p-12 rounded-lg border border-white/10">
            <?php
            $content_file = __DIR__ . '/pages/contact_content.php';
            if (file_exists($content_file)) {
                include $content_file;
            } else {
                echo '<div class="prose max-w-none text-zinc-300"><h2 class="text-zinc-100">Contact Support</h2><p>Need help? Contact us here.</p></div>';
            }
            ?>
        </div>
        <div id="ad-container-general_bottom_ads" class="mt-6 flex justify-center empty:hidden w-full min-h-[90px]"></div>
    </div>
</div>

<?php include 'includes/landingfooter.php'; ?>
