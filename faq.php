<?php
include 'includes/landingheader.php';
$faq_file = __DIR__ . '/pages/faq_content.json';
$faqs = [];
if (file_exists($faq_file)) {
    $faqs = json_decode(file_get_contents($faq_file), true) ?? [];
}
?>

<div class="relative z-10 pt-32 pb-16 min-h-[60vh]">
    <div class="container-custom">
        <div id="ad-container-general_top_ads" class="mb-6 flex justify-center empty:hidden w-full min-h-[90px]"></div>
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-extrabold text-zinc-100 tracking-tight mb-4">Frequently Asked Questions</h1>
            <p class="text-lg text-zinc-400">Find answers to common questions about our platform.</p>
        </div>

        <div class="bg-white/[0.02] p-8 md:p-10 rounded-lg border border-white/10">
            <?php if (empty($faqs)): ?>
                <div class="text-center text-zinc-500 py-8">
                    <i class="fa-regular fa-folder-open text-4xl mb-3"></i>
                    <p>No FAQs available at the moment.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4" id="faq-accordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="border border-white/10 rounded-lg overflow-hidden bg-white/[0.02] transition-all duration-200 hover:border-white/20">
                            <button class="faq-btn w-full text-left px-6 py-5 flex justify-between items-center focus:outline-none" aria-expanded="false">
                                <span class="font-semibold text-zinc-100 text-lg pr-4"><?php echo htmlspecialchars($faq['question']); ?></span>
                                <i class="fa-solid fa-chevron-down text-primary transition-transform duration-300"></i>
                            </button>
                            <div class="faq-content hidden px-6 pb-5">
                                <div class="text-zinc-300 leading-relaxed border-t border-white/10 pt-4 mt-2">
                                    <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div id="ad-container-general_bottom_ads" class="mt-6 flex justify-center empty:hidden w-full min-h-[90px]"></div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/pages/faq.js?v=<?= time() ?>"></script>
<?php include 'includes/landingfooter.php'; ?>
