<?php
session_start();
require_once 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = trim((string)$_POST['email']);
    $password = (string)$_POST['password'];
    $confirm_password = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : null;

    if ($email === '' || $password === '') {
        $_SESSION['login_error'] = "Please enter your email address and password.";
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_error'] = "Please enter a valid email address.";
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['login_error'] = "Password must be at least 6 characters long.";
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    require_once 'includes/anti_fraud.php';
    $telemetry_payload = $_POST['_cf_verification'] ?? '';
    if (empty($telemetry_payload)) {
        http_response_code(403);
        die("Suspicious activity detected! [Code: MISSING_CF]");
    }

    $telemetry_data = decode_telemetry_payload($telemetry_payload);
    if (!$telemetry_data || !isset($telemetry_data['signals'])) {
        http_response_code(403);
        die("Suspicious activity detected! [Code: INVALID_CF]");
    }

    if (empty($telemetry_data['signals']['canvasFingerprint']) || $telemetry_data['signals']['canvasFingerprint'] === 'unknown') {
        http_response_code(403);
        die("Suspicious activity detected! [Code: MISSING_HW_CF]");
    }

    $fp_hash = generate_fp_hash($telemetry_data['signals']);
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
    $secStmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('proxycheck_enabled', 'proxycheck_key')");
    $secSettings = [];
    foreach ($secStmt->fetchAll() as $row) {
        $secSettings[$row['setting_key']] = $row['setting_value'];
    }

    $ipcheck = ipcheck_is_vpn($ip, $secSettings);
    if (!empty($ipcheck['blocked'])) {
        $_SESSION['login_error'] = "Proxy/VPN usage detected on your IP Address!";
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    $ipcheck_country = $ipcheck['info']['country'] ?? null;
    $ipcheck_asn = $ipcheck['info']['asn'] ?? null;
    if (!empty($ipcheck_asn)) {
        $ipcheck_asn = explode(' ', trim($ipcheck_asn))[0];
    }

    try {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE faucetpay_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
                $_SESSION['login_error'] = "Invalid email or password.";
                header('Location: ' . BASE_URL . '/');
                exit;
            }

            $userId = $user['id'];
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$userId]);
        } else {
            if ($confirm_password !== null && $password !== $confirm_password) {
                $_SESSION['login_error'] = "Passwords do not match.";
                header('Location: ' . BASE_URL . '/');
                exit;
            }
            $keyStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'faucetpay_api_key'");
            $keyStmt->execute();
            $faucetpayKey = trim((string)$keyStmt->fetchColumn());
            if ($faucetpayKey !== '') {
                $addressCheck = faucetpay_check_address($faucetpayKey, $email);
                if (!$addressCheck['success']) {
                    $_SESSION['login_error'] = "This email could not be verified. Please use a valid email address.";
                    header('Location: ' . BASE_URL . '/');
                    exit;
                }
            }

            $chkFp = $pdo->prepare("SELECT user_id FROM user_fingerprints WHERE fp_hash = ? LIMIT 1");
            $chkFp->execute([$fp_hash]);
            if ($chkFp->fetch()) {
                http_response_code(400);
                $_SESSION['login_error'] = "You already have an account registered from this device. You can log in using that one.";
                header('Location: ' . BASE_URL . '/');
                exit;
            }

            $chkIp = $pdo->prepare("SELECT email FROM login_logs WHERE ip_address = ? AND email != ? LIMIT 1");
            $chkIp->execute([$ip, $email]);
            if ($chkIp->fetch()) {
                http_response_code(400);
                $_SESSION['login_error'] = "You already have an account registered from this IP. You can log in using that one.";
                header('Location: ' . BASE_URL . '/');
                exit;
            }

            $referred_by = null;
            if (isset($_SESSION['referred_by'])) {
                $referred_by = $_SESSION['referred_by'];
            } elseif (isset($_COOKIE['referred_by'])) {
                $referred_by = intval($_COOKIE['referred_by']);
            }

            if ($referred_by) {
                $refCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $refCheck->execute([$referred_by]);
                if (!$refCheck->fetch()) {
                    $referred_by = null;
                }
            }

            $insertStmt = $pdo->prepare("INSERT INTO users (faucetpay_email, password_hash, last_login, referred_by) VALUES (?, ?, CURRENT_TIMESTAMP, ?)");
            $insertStmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $referred_by]);
            $userId = $pdo->lastInsertId();
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['faucetpay_email'] = $email;
        $country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;
        $asn = $_SERVER['HTTP_CF_IPASN'] ?? null;
        if (empty($asn) && !empty($ipcheck_asn)) {
            $asn = $ipcheck_asn;
        }
        if (empty($country) && !empty($ipcheck_country)) {
            $country = $ipcheck_country;
        }

        if (empty($asn) || empty($country)) {
            $ip_api = @file_get_contents("http://ip-api.com/json/{$ip}?fields=as,countryCode");
            if ($ip_api) {
                $ip_data = json_decode($ip_api, true);
                if (empty($asn) && !empty($ip_data['as'])) {
                    $asn = explode(' ', $ip_data['as'])[0];
                }
                if (empty($country) && !empty($ip_data['countryCode'])) {
                    $country = $ip_data['countryCode'];
                }
            }
        }

        $logStmt = $pdo->prepare("INSERT INTO login_logs (email, ip_address, country_code, asn) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$email, $ip, $country, $asn]);
        analyze_telemetry_and_enforce($pdo, $userId, $telemetry_data, $ip);
        header('Location: ' . BASE_URL . '/user/');
        exit;

    } catch (PDOException $e) {
        $_SESSION['login_error'] = "Database error during login.";
        header('Location: ' . BASE_URL . '/');
        exit;
    }
}
header('Location: ' . BASE_URL . '/');
exit;