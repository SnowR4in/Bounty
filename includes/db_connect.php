<?php

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $proj_root = str_replace('\\', '/', dirname(__DIR__));
    $base_path = str_replace($doc_root, '', $proj_root);
    if (empty($base_path) || $base_path === '/') {
        $base_path = '';
    }
    define('BASE_PATH', $base_path);
    define('BASE_URL', $protocol . $host . $base_path);
}
require_once __DIR__ . '/../config/database.php';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    require_once __DIR__ . '/anti_fraud.php';
    require_once __DIR__ . '/site_settings.php';
    require_once __DIR__ . '/offerwallapi.php';
    require_once __DIR__ . '/offerwallme.php';
    require_once __DIR__ . '/opinionuniverse.php';

    try {
        $ow_table = $pdo->query("SHOW TABLES LIKE 'offerwall_claims'")->fetchColumn();
        if (!$ow_table) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `offerwall_claims` (
              `id` bigint(20) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `txid` varchar(200) NOT NULL,
              `reward_usdt` decimal(16,8) NOT NULL,
              `status` enum('completed','chargeback','rejected') NOT NULL DEFAULT 'completed',
              `campaign_name` varchar(255) DEFAULT NULL,
              `ip_address` varchar(45) DEFAULT NULL,
              `country` varchar(10) DEFAULT NULL,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `idx_txid` (`txid`),
              KEY `idx_user_status` (`user_id`, `status`),
              KEY `idx_user_date` (`user_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
        $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
            ('offerwall_enabled', '0'),
            ('ref_percent_offerwall', '5'),
            ('level_bonus_offerwall', '5'),
            ('ou_enabled', '1'),
            ('ou_app_id', 'ID_d1c94cda94189d2d3b41633b7289c70c'),
            ('ou_uid', '4157'),
            ('ou_secret_key', ''),
            ('ou_source_id', 'offerwall'),
            ('ou_ip_whitelist', '147.79.123.200')");
        $catCol = $pdo->query("SHOW COLUMNS FROM referral_earnings LIKE 'category'")->fetch(PDO::FETCH_ASSOC);
        if ($catCol && strpos((string)$catCol['Type'], 'offerwall') === false) {
            $pdo->exec("ALTER TABLE referral_earnings MODIFY category enum('faucet','ptc','shortlinks','tasks','video','deposits','offerwall') NOT NULL");
        }
    } catch (Exception $e) {
    }
    require_once __DIR__ . '/faucetpay.php';
    require_once __DIR__ . '/captcha.php';
    require_once __DIR__ . '/ipcheck.php';

    if (!defined('ADMIN_ROUTE')) {
        define('ADMIN_ROUTE', '/adminpanel');
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['admin_username']) && $_SESSION['admin_username'] === 'demo') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_POST = [];
            $_SESSION['admin_error'] = "Action disabled in demo mode.";
        }
    }
} catch (\PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
