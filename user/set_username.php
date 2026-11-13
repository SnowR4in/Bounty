<?php
include 'includes/header.php';

$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$existing_username = $stmt->fetchColumn();
if ($existing_username) {
    header('Location: /user/');
    exit;
}
?>

<main class="flex-1 relative z-10">
    <section class="pt-20 md:pt-28 pb-8 md:pb-12 min-h-[80vh] flex items-center justify-center">
        <div class="container-custom relative z-10">
            <div class="max-w-md mx-auto">
                <div class="bg-surface-card rounded-lg border border-white/10 p-8 md:p-10 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-primary/5 rounded-bl-full -z-10"></div>
                    <div class="absolute bottom-0 left-0 w-32 h-32 bg-primary/5 rounded-tr-full -z-10"></div>

                    <div class="text-center mb-8">
                        <div class="w-16 h-16 rounded-full bg-primary/10 border border-primary/25 text-primary flex items-center justify-center text-3xl mx-auto mb-4">
                            <i class="fa-solid fa-user-pen"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-zinc-100 mb-2">Choose Your Username</h1>
                        <p class="text-sm text-zinc-500">Pick a unique username to identify yourself on the platform. This will be visible to other users.</p>
                    </div>

                    <form id="username-form" class="space-y-5">
                        <div>
                            <label class="block text-sm font-bold text-zinc-300 mb-1.5" for="username-input">Username</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-500"><i class="fa-solid fa-at"></i></span>
                                <input type="text" name="username" id="username-input" minlength="3" maxlength="20" required
                                    class="w-full pl-10 pr-4 py-3 bg-white/[0.03] border border-white/10 rounded-lg text-sm text-zinc-200 placeholder-zinc-600 focus:ring-2 focus:ring-primary/20 focus:border-primary/60 outline-none transition-all"
                                    placeholder="e.g. cryptoking42" autocomplete="off">
                            </div>
                            <p class="text-xs text-zinc-500 mt-1.5">3-20 characters. Letters, numbers, and underscores only.</p>
                        </div>

                        <button type="submit" id="submit-btn" class="w-full bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 px-4 rounded-lg transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-check"></i> Set Username
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="<?= BASE_URL ?>/assets/js/pages/user-set-username.js?v=<?= time() ?>"></script>

<?php include 'includes/footer.php'; ?>
