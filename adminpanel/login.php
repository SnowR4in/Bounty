<?php
session_start();
require_once '../includes/db_connect.php';

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_ROUTE . '/');
    exit;
}

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('admin_login_captcha', 'recaptcha_enabled', 'recaptcha_site_key', 'recaptcha_secret_key', 'turnstile_enabled', 'turnstile_site_key', 'turnstile_secret_key')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$admin_login_captcha = $settings['admin_login_captcha'] ?? 'none';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha_valid = true;

    if ($admin_login_captcha === 'google') {
        $captcha_response = $_POST['g-recaptcha-response'] ?? '';
        if (empty($captcha_response)) {
            $captcha_valid = false;
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'secret' => $settings['recaptcha_secret_key'] ?? '',
                'response' => $captcha_response
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($response, true);
            if (!$result || !isset($result['success']) || !$result['success']) {
                $captcha_valid = false;
            }
        }
    } elseif ($admin_login_captcha === 'turnstile') {
        $captcha_response = $_POST['cf-turnstile-response'] ?? '';
        if (empty($captcha_response)) {
            $captcha_valid = false;
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'secret' => $settings['turnstile_secret_key'] ?? '',
                'response' => $captcha_response
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($response, true);
            if (!$result || !isset($result['success']) || !$result['success']) {
                $captcha_valid = false;
            }
        }
    }

    if (!$captcha_valid) {
        $error = "Captcha verification failed. Please try again.";
    } elseif (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE admin_username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['admin_password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['admin_username'];
                header('Location: ' . ADMIN_ROUTE . '/');
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "System error during login.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - USDTFaucet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= BASE_URL ?>/assets/js/tailwind-config.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800;900&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="icon" type="image/webp" href="<?= BASE_URL ?>/assets/icon.webp">
</head>
<body class="bg-surface text-zinc-300 antialiased min-h-screen flex flex-col justify-center items-center relative overflow-x-hidden px-4">

    <div class="ambient-bg" aria-hidden="true">
        <span class="ambient-blob ambient-blob-a"></span>
        <span class="ambient-blob ambient-blob-b"></span>
        <span class="ambient-blob ambient-blob-c"></span>
        <div class="ambient-grid"></div>
    </div>

    <div class="w-full max-w-md rounded-lg border border-white/10 bg-surface-card p-8 shadow-soft relative z-10">
        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto rounded-lg bg-gradient-to-tr from-primaryDeep to-primary text-zinc-950 flex items-center justify-center text-2xl shadow-glow-sm mb-4">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <h1 class="text-2xl font-bold text-zinc-100 tracking-tight">Admin Area</h1>
            <p class="text-sm text-zinc-500 mt-1">Please sign in to continue</p>
        </div>

        <form method="POST" action="" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-1">Username</label>
                <input type="text" name="username" required class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2 bg-white/[0.03] border border-white/10 rounded-lg text-zinc-200 placeholder-zinc-600 focus:border-primary/60 focus:ring-primary/20 outline-none transition-all">
            </div>

            <?php if ($admin_login_captcha === 'google'): ?>
                <div class="flex justify-center">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($settings['recaptcha_site_key'] ?? ''); ?>"></div>
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                </div>
            <?php elseif ($admin_login_captcha === 'turnstile'): ?>
                <div class="flex justify-center">
                    <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($settings['turnstile_site_key'] ?? ''); ?>"></div>
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                </div>
            <?php endif; ?>

            <button type="submit" class="w-full bg-primary hover:bg-primaryHover text-zinc-950 font-bold py-3 rounded-lg transition-colors">
                Login
            </button>
        </form>
    </div>

    <div id="toast-container" class="toast-container"></div>
    <script>window.OW_ADMIN_DATA = <?php echo json_encode(['loginError' => $error]); ?>;</script>
    <script src="<?= BASE_URL ?>/assets/js/admin/admin-core.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_URL ?>/assets/js/admin/admin-login.js?v=<?= time() ?>"></script>
</body>
</html>
