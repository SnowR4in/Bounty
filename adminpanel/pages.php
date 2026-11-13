<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: ' . ADMIN_ROUTE . '/login'); exit; }

$pages_dir = __DIR__ . '/../pages';
if (!is_dir($pages_dir)) {
    mkdir($pages_dir, 0755, true);
}

$terms_file = $pages_dir . '/terms_content.php';
$privacy_file = $pages_dir . '/privacy_content.php';
$about_file = $pages_dir . '/about_content.php';
$contact_file = $pages_dir . '/contact_content.php';
$faq_file = $pages_dir . '/faq_content.json';
$landing_file = $pages_dir . '/landing_content.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        if (isset($_POST['terms_content'])) {
            file_put_contents($terms_file, $_POST['terms_content']);
        }
        if (isset($_POST['privacy_content'])) {
            file_put_contents($privacy_file, $_POST['privacy_content']);
        }
        if (isset($_POST['about_content'])) {
            file_put_contents($about_file, $_POST['about_content']);
        }
        if (isset($_POST['contact_content'])) {
            file_put_contents($contact_file, $_POST['contact_content']);
        }

        if (isset($_POST['landing_content'])) {
            $landing_data = [
                'hero' => [
                    'title1' => $_POST['hero_title1'] ?? '',
                    'title2' => $_POST['hero_title2'] ?? '',
                    'subtitle' => $_POST['hero_subtitle'] ?? ''
                ],
                'tasks' => [
                    'title' => $_POST['tasks_title'] ?? '',
                    'subtitle' => $_POST['tasks_subtitle'] ?? ''
                ],
                'video' => [
                    'title' => $_POST['video_title'] ?? '',
                    'subtitle' => $_POST['video_subtitle'] ?? ''
                ],
                'ptc' => [
                    'title' => $_POST['ptc_title'] ?? '',
                    'subtitle' => $_POST['ptc_subtitle'] ?? ''
                ],
                'shortlinks' => [
                    'title' => $_POST['shortlinks_title'] ?? '',
                    'subtitle' => $_POST['shortlinks_subtitle'] ?? ''
                ],
                'wheel' => [
                    'title' => $_POST['wheel_title'] ?? '',
                    'subtitle' => $_POST['wheel_subtitle'] ?? ''
                ],
                'why' => [
                    'title' => $_POST['why_title'] ?? '',
                    'subtitle' => $_POST['why_subtitle'] ?? '',
                    'btn_text' => $_POST['why_btn_text'] ?? '',
                    'btn_link' => $_POST['why_btn_link'] ?? ''
                ],
                'footer' => [
                    'text' => $_POST['footer_text'] ?? ''
                ]
            ];
            file_put_contents($landing_file, json_encode($landing_data, JSON_PRETTY_PRINT));
        }

        if (isset($_POST['faq_q']) && isset($_POST['faq_a'])) {
            $faqs = [];
            foreach ($_POST['faq_q'] as $index => $q) {
                $a = $_POST['faq_a'][$index] ?? '';
                if (trim($q) !== '' && trim($a) !== '') {
                    $faqs[] = [
                        'question' => trim($q),
                        'answer' => trim($a)
                    ];
                }
            }
            file_put_contents($faq_file, json_encode($faqs, JSON_PRETTY_PRINT));
        } elseif (isset($_POST['update_faqs'])) {
            file_put_contents($faq_file, json_encode([]));
        }

        echo json_encode(['success' => true, 'message' => 'Pages updated successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to save pages. Check folder permissions.']);
    }
    exit;
}

include 'includes/header.php';

if (!file_exists($terms_file)) file_put_contents($terms_file, '<div class="prose max-w-none text-zinc-400"><h2>Terms of Service</h2><p>Your terms here.</p></div>');
if (!file_exists($privacy_file)) file_put_contents($privacy_file, '<div class="prose max-w-none text-zinc-400"><h2>Privacy Policy</h2><p>Your privacy policy here.</p></div>');
if (!file_exists($about_file)) file_put_contents($about_file, '<div class="prose max-w-none text-zinc-400"><h2>About Us</h2><p>Learn more about our company.</p></div>');
if (!file_exists($contact_file)) file_put_contents($contact_file, '<div class="prose max-w-none text-zinc-400"><h2>Contact Support</h2><p>Need help? Contact us here.</p></div>');
if (!file_exists($faq_file)) file_put_contents($faq_file, json_encode([['question' => 'What is this?', 'answer' => 'This is a sample FAQ.']]));

$terms_content = file_get_contents($terms_file);
$privacy_content = file_get_contents($privacy_file);
$about_content = file_get_contents($about_file);
$contact_content = file_get_contents($contact_file);
$faq_content = json_decode(file_get_contents($faq_file), true) ?? [];

$default_landing = [
    'hero' => [
        'title1' => 'Earn USDT in',
        'title2' => 'Minutes.',
        'subtitle' => 'Turn your free time into Tether USDT by completing microtasks, viewing PTC ads, and claiming from our instant faucet.'
    ],
    'tasks' => [
        'title' => 'Microtasks & Offers',
        'subtitle' => 'Complete microtasks and offers from our trusted partners. Download apps, play games, or test new products to earn USDT directly to your balance. New tasks are added constantly.'
    ],
    'video' => [
        'title' => 'Watch & Earn Videos',
        'subtitle' => 'Get paid to watch short videos. Choose a video from the feed, watch it to the end, and receive your USDT reward instantly. A fun and effortless way to grow your balance every day.'
    ],
    'ptc' => [
        'title' => 'PTC Ads (Paid-To-Click)',
        'subtitle' => 'The easiest way to start earning. Simply visit sponsored websites, wait for the timer to finish, and get paid instantly. Discover new crypto projects, faucets, and useful websites while growing your own wallet.'
    ],
    'shortlinks' => [
        'title' => 'Shortlinks',
        'subtitle' => 'Boost your earnings by solving shortlinks. Navigate through partner links, complete captchas, and reach the destination URL to claim higher rewards. A great way to increase your daily earning potential.'
    ],
    'wheel' => [
        'title' => 'Wheel of Fortune',
        'subtitle' => 'Spin the wheel and test your luck! Use your collected energy to spin the wheel for a chance to win massive USDT rewards. The higher the energy, the more spins you get.'
    ],
    'why' => [
        'title' => 'Why Tether USDT?',
        'subtitle' => 'Tether (USDT) is the world\'s largest stablecoin, 1:1 pegged to the US dollar. Instant transactions, minimal fees, and wide support across exchanges and wallets — perfect for faucet payouts.',
        'btn_text' => 'Official Site',
        'btn_link' => 'https://tether.to/'
    ],
    'footer' => [
        'text' => 'USDTFaucet helps members earn Tether USDT rewards by completing microtasks, watching videos, viewing ads, and claiming from the faucet.'
    ]
];
if (!file_exists($landing_file)) file_put_contents($landing_file, json_encode($default_landing, JSON_PRETTY_PRINT));
$landing_content = array_merge($default_landing, json_decode(file_get_contents($landing_file), true) ?: []);
?>

<div class="p-6 lg:p-8">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-zinc-100 tracking-tight">Manage Pages</h2>
        <p class="text-zinc-500 mt-1">Edit the public content for Terms of Service and Privacy Policy. Content is saved directly to files for zero-latency loading.</p>
    </div>

    <form id="pages-form">
        <input type="hidden" name="landing_content" value="1">
        <div class="mb-8 rounded-lg border border-white/10 bg-surface-card overflow-hidden flex flex-col">
            <div class="p-6 border-b border-white/10 bg-white/[0.03]">
                <h3 class="text-lg font-bold text-zinc-100"><i class="fa-solid fa-home text-primary mr-2"></i> Landing Page Settings</h3>
                <p class="text-xs text-zinc-500 mt-1">Configure texts for the front page components.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 bg-white/[0.02]">
                <div class="space-y-3 md:col-span-2 border-b border-white/10 pb-4">
                    <h4 class="font-bold text-zinc-200">Hero Section</h4>
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Title Part 1</label>
                            <input type="text" name="hero_title1" value="<?php echo htmlspecialchars($landing_content['hero']['title1']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Title Part 2 (Primary)</label>
                            <input type="text" name="hero_title2" value="<?php echo htmlspecialchars($landing_content['hero']['title2']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none text-primary font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Subtitle</label>
                        <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars($landing_content['hero']['subtitle']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                    </div>
                </div>

                <div class="space-y-3 border-b border-white/10 pb-4 md:border-b-0 md:pb-0">
                    <h4 class="font-bold text-zinc-200">Tasks</h4>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Title</label>
                        <input type="text" name="tasks_title" value="<?php echo htmlspecialchars($landing_content['tasks']['title']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Subtitle</label>
                        <textarea name="tasks_subtitle" rows="3" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none"><?php echo htmlspecialchars($landing_content['tasks']['subtitle']); ?></textarea>
                    </div>
                </div>

                <div class="space-y-3 border-b border-white/10 pb-4 md:border-b-0 md:pb-0">
                    <h4 class="font-bold text-zinc-200">Video</h4>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Title</label>
                        <input type="text" name="video_title" value="<?php echo htmlspecialchars($landing_content['video']['title']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Subtitle</label>
                        <textarea name="video_subtitle" rows="3" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none"><?php echo htmlspecialchars($landing_content['video']['subtitle']); ?></textarea>
                    </div>
                </div>

                <div class="space-y-3 border-b border-white/10 pb-4 md:border-b-0 md:pb-0">
                    <h4 class="font-bold text-zinc-200">PTC Ads</h4>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Title</label>
                        <input type="text" name="ptc_title" value="<?php echo htmlspecialchars($landing_content['ptc']['title']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Subtitle</label>
                        <textarea name="ptc_subtitle" rows="3" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none"><?php echo htmlspecialchars($landing_content['ptc']['subtitle']); ?></textarea>
                    </div>
                </div>

                <div class="space-y-3 border-b border-white/10 pb-4 md:border-b-0 md:pb-0">
                    <h4 class="font-bold text-zinc-200">Shortlinks</h4>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Title</label>
                        <input type="text" name="shortlinks_title" value="<?php echo htmlspecialchars($landing_content['shortlinks']['title']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Subtitle</label>
                        <textarea name="shortlinks_subtitle" rows="3" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none"><?php echo htmlspecialchars($landing_content['shortlinks']['subtitle']); ?></textarea>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="font-bold text-zinc-200">Wheel</h4>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Title</label>
                        <input type="text" name="wheel_title" value="<?php echo htmlspecialchars($landing_content['wheel']['title']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Subtitle</label>
                        <textarea name="wheel_subtitle" rows="3" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none"><?php echo htmlspecialchars($landing_content['wheel']['subtitle']); ?></textarea>
                    </div>
                </div>

                <div class="space-y-3 md:col-span-2 border-t border-white/10 pt-4 mt-2">
                    <h4 class="font-bold text-zinc-200">Why Tether USDT?</h4>
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Title</label>
                            <input type="text" name="why_title" value="<?php echo htmlspecialchars($landing_content['why']['title']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Subtitle</label>
                            <input type="text" name="why_subtitle" value="<?php echo htmlspecialchars($landing_content['why']['subtitle']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-4 mt-3">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Button Text</label>
                            <input type="text" name="why_btn_text" value="<?php echo htmlspecialchars($landing_content['why']['btn_text']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Button Link</label>
                            <input type="text" name="why_btn_link" value="<?php echo htmlspecialchars($landing_content['why']['btn_link']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none">
                        </div>
                    </div>
                </div>

                <div class="space-y-3 md:col-span-2 border-t border-white/10 pt-4 mt-2">
                    <h4 class="font-bold text-zinc-200">Footer Text</h4>
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Global Footer Text</label>
                        <textarea name="footer_text" rows="2" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none"><?php echo htmlspecialchars($landing_content['footer']['text']); ?></textarea>
                    </div>
                </div>

            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

            <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden flex flex-col">
                <div class="p-6 border-b border-white/10 bg-white/[0.03]">
                    <h3 class="text-lg font-bold text-zinc-100"><i class="fa-solid fa-file-contract text-primary mr-2"></i> Terms of Service</h3>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <p class="text-xs text-zinc-500 mb-2">You can use standard HTML (headings, paragraphs, bold, lists). The header and footer are automatically included.</p>
                    <textarea name="terms_content" rows="15" class="w-full flex-1 p-4 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono text-sm resize-y min-h-[300px]"><?php echo htmlspecialchars($terms_content); ?></textarea>
                </div>
            </div>

            <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden flex flex-col">
                <div class="p-6 border-b border-white/10 bg-white/[0.03]">
                    <h3 class="text-lg font-bold text-zinc-100"><i class="fa-solid fa-user-shield text-primary mr-2"></i> Privacy Policy</h3>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <p class="text-xs text-zinc-500 mb-2">You can use standard HTML (headings, paragraphs, bold, lists). The header and footer are automatically included.</p>
                    <textarea name="privacy_content" rows="15" class="w-full flex-1 p-4 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono text-sm resize-y min-h-[300px]"><?php echo htmlspecialchars($privacy_content); ?></textarea>
                </div>
            </div>

            <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden flex flex-col">
                <div class="p-6 border-b border-white/10 bg-white/[0.03]">
                    <h3 class="text-lg font-bold text-zinc-100"><i class="fa-solid fa-circle-info text-primary mr-2"></i> About Us</h3>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <p class="text-xs text-zinc-500 mb-2">You can use standard HTML (headings, paragraphs, bold, lists). The header and footer are automatically included.</p>
                    <textarea name="about_content" rows="15" class="w-full flex-1 p-4 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono text-sm resize-y min-h-[300px]"><?php echo htmlspecialchars($about_content); ?></textarea>
                </div>
            </div>

            <div class="rounded-lg border border-white/10 bg-surface-card overflow-hidden flex flex-col">
                <div class="p-6 border-b border-white/10 bg-white/[0.03]">
                    <h3 class="text-lg font-bold text-zinc-100"><i class="fa-solid fa-headset text-primary mr-2"></i> Contact Support</h3>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <p class="text-xs text-zinc-500 mb-2">You can use standard HTML (headings, paragraphs, bold, lists). The header and footer are automatically included.</p>
                    <textarea name="contact_content" rows="15" class="w-full flex-1 p-4 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all font-mono text-sm resize-y min-h-[300px]"><?php echo htmlspecialchars($contact_content); ?></textarea>
                </div>
            </div>

        </div>

        <input type="hidden" name="update_faqs" value="1">
        <div class="mt-8 rounded-lg border border-white/10 bg-surface-card overflow-hidden flex flex-col">
            <div class="p-6 border-b border-white/10 bg-white/[0.03] flex justify-between items-center">
                <h3 class="text-lg font-bold text-zinc-100"><i class="fa-solid fa-circle-question text-primary mr-2"></i> Frequently Asked Questions</h3>
                <button type="button" id="addFaqBtn" class="bg-white/5 hover:bg-white/10 text-zinc-200 border border-white/10 font-bold py-2 px-4 rounded-lg transition-colors text-sm">
                    <i class="fa-solid fa-plus mr-1"></i> Add FAQ
                </button>
            </div>
            <div class="p-6">
                <p class="text-xs text-zinc-500 mb-6">Manage questions and answers dynamically. They will be displayed on the FAQ page as an interactive list.</p>
                <div id="faq-container" class="space-y-4">
                    <?php foreach ($faq_content as $index => $faq): ?>
                    <div class="faq-item flex gap-4 p-4 border border-white/10 rounded-lg bg-white/[0.03] relative group">
                        <div class="flex-1 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Question</label>
                                <input type="text" name="faq_q[]" value="<?php echo htmlspecialchars($faq['question']); ?>" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-zinc-400 uppercase mb-1">Answer</label>
                                <textarea name="faq_a[]" rows="2" class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none resize-y" required><?php echo htmlspecialchars($faq['answer']); ?></textarea>
                            </div>
                        </div>
                        <button type="button" class="remove-faq text-zinc-500 hover:text-red-400 transition-colors self-center p-2 rounded-lg hover:bg-red-500/10">
                            <i class="fa-solid fa-trash text-lg"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="faq-empty" class="text-center py-8 text-zinc-500 <?php echo empty($faq_content) ? '' : 'hidden'; ?>">
                    <i class="fa-regular fa-folder-open text-3xl mb-2"></i>
                    <p>No FAQs added yet.</p>
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" id="save-btn" class="bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-8 rounded-lg transition-colors flex items-center gap-2">
                <i class="fa-solid fa-save"></i> Save Pages
            </button>
        </div>
    </form>
</div>

<script>window.OW_ADMIN_DATA = <?php echo json_encode(['adminRoute' => ADMIN_ROUTE]); ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/admin/admin-pages.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
