<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/anti_fraud.php';

// Validate CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid session. Please refresh the page.']);
    exit;
}

// Validate Adblock Check
$adBlockCheck = $_POST['adBlockCheck'] ?? 'false';
if ($adBlockCheck !== 'true') {
    $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
    $reason = 'Adblock Detected (IP: ' . $ip_address . ')';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE users SET risk_score = risk_score + 2 WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, severity, reason) VALUES (?, 'high', ?)");
        $stmt->execute([$_SESSION['user_id'], $reason]);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    echo json_encode(['success' => false, 'error' => 'Adblocker detected. Please disable it to claim.']);
    exit;
}

// Telemetry Verification
$headers = getallheaders();
$telemetry_payload = $headers['x-sec-auth'] ?? ($headers['X-Sec-Auth'] ?? '');
if (empty($telemetry_payload)) {
    echo json_encode(['success' => false, 'error' => 'Security verification failed (Missing token).']);
    exit;
}

$telemetry_data = decode_telemetry_payload($telemetry_payload);
if (!$telemetry_data || !isset($telemetry_data['signals'])) {
    echo json_encode(['success' => false, 'error' => 'Security verification failed (Invalid token).']);
    exit;
}
$ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
// analyze_telemetry_and_enforce($pdo, $_SESSION['user_id'], $telemetry_data, $ip_address);
// We will call this right before processing the claim to log movements and check hardware.


$type = $_POST['type'] ?? '';
if (!in_array($type, ['timed', 'instant'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid faucet type.']);
    exit;
}

// Fetch settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Check if enabled
if (($settings[$type.'_faucet_enabled'] ?? '0') !== '1') {
    echo json_encode(['success' => false, 'error' => 'This faucet is currently disabled.']);
    exit;
}

$cooldown = intval($settings[$type.'_faucet_cooldown'] ?? ($type == 'timed' ? 300 : 10));
$reward = floatval($settings[$type.'_faucet_reward'] ?? 0);
$energy = intval($settings[$type.'_faucet_energy'] ?? 0);
$daily_limit = intval($settings[$type.'_faucet_daily_limit'] ?? 100);

// Get real IP (Cloudflare support)
$ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';


// Telemetry enforcement (generates logs and potentially auto-bans)
analyze_telemetry_and_enforce($pdo, $_SESSION['user_id'], $telemetry_data, $ip_address);

// --- CAPTCHA CHECK ---
$captcha_type = $settings[$type.'_faucet_captcha'] ?? 'none';

if (!captcha_verify($captcha_type, $settings)) {
    echo json_encode(['success' => false, 'error' => captcha_error_message($captcha_type)]);
    exit;
}
// --- END CAPTCHA CHECK ---

try {
    $pdo->beginTransaction();

    // 1. Check Daily Limit
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM faucet_claims WHERE user_id = ? AND faucet_type = ? AND DATE(claimed_at) = CURDATE()");
    $stmt->execute([$_SESSION['user_id'], $type]);
    $claims_today = $stmt->fetchColumn();

    if ($claims_today >= $daily_limit) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'You have reached your daily limit for this faucet.']);
        exit;
    }

    // 2. Cooldown Check (Anti-cheat)
    // SELECT FOR UPDATE locks the row to prevent concurrent rapid requests (race condition bypass)
    $stmt = $pdo->prepare("SELECT UNIX_TIMESTAMP(claimed_at) FROM faucet_claims WHERE user_id = ? AND faucet_type = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $stmt->execute([$_SESSION['user_id'], $type]);
    $last_claim = $stmt->fetchColumn();

    if ($last_claim) {
        $elapsed = time() - $last_claim;
        if ($elapsed < $cooldown) {
            $pdo->rollBack();
            $wait = $cooldown - $elapsed;
            echo json_encode(['success' => false, 'error' => "Please wait $wait more seconds."]);
            exit;
        }
    }

    // 3. Get user status
    $stmt = $pdo->prepare("SELECT status, referred_by, is_shadow_banned FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$_SESSION['user_id']]);
    $user_row = $stmt->fetch();
    $user_status = intval($user_row['status'] ?? 1);
    $is_shadow_banned = intval($user_row['is_shadow_banned'] ?? 0);
    $referred_by = $user_row['referred_by'] ? intval($user_row['referred_by']) : null;

    // 4. Shadow Ban Check - if shadow banned, log claim but don't add balance
    if ($is_shadow_banned === 1) {
        // Log claim with real reward values (so it looks legit in their history)
        $stmt = $pdo->prepare("INSERT INTO faucet_claims (user_id, faucet_type, reward_usdt, reward_energy, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $type, $reward, $energy, $ip_address, $user_agent]);
        $pdo->commit();
        // Return success as if everything is normal
        echo json_encode(['success' => true]);
        exit;
    }

    // 5. Banned check
    if ($user_status == 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Your account has been suspended.']);
        exit;
    }

    // 6. Update User Balance (Active users only)
    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ?, energy = energy + ? WHERE id = ?");
    $stmt->execute([$reward, $energy, $_SESSION['user_id']]);

    // 7. Log Claim
    $stmt = $pdo->prepare("INSERT INTO faucet_claims (user_id, faucet_type, reward_usdt, reward_energy, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $type, $reward, $energy, $ip_address, $user_agent]);

    // 8. Referral Commission
    if ($referred_by && ($settings['referrals_enabled'] ?? '0') === '1') {
        $ref_percent = floatval($settings['ref_percent_faucet'] ?? 0);
        if ($ref_percent > 0) {
            $commission = ($reward * $ref_percent) / 100;
            if ($commission > 0) {
                // Credit referrer balance
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ? AND status = 1");
                $stmt->execute([$commission, $referred_by]);

                // Log referral earning
                $stmt = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_user_id, category, amount) VALUES (?, ?, 'faucet', ?)");
                $stmt->execute([$referred_by, $_SESSION['user_id'], $commission]);
            }
        }
    }

    // 9. Level System XP
    if (isset($settings['level_system_enabled']) && $settings['level_system_enabled'] == '1') {
        $stmtXp = $pdo->prepare("SELECT xp FROM users WHERE id = ? FOR UPDATE");
        $stmtXp->execute([$_SESSION['user_id']]);
        $current_xp = intval($stmtXp->fetchColumn());

        $current_level = floor(sqrt($current_xp / 100));
        $current_level_base = 100 * pow($current_level, 2);
        $next_level_base = 100 * pow($current_level + 1, 2);
        $xp_needed = $next_level_base - $current_level_base;

        $xp_percent = floatval($settings['level_bonus_' . $type] ?? 0);

        if ($xp_percent > 0) {
            $gained_xp = intval(round($xp_needed * ($xp_percent / 100)));
            if ($gained_xp > 0) {
                $updXp = $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
                $updXp->execute([$gained_xp, $_SESSION['user_id']]);
            }
        }
    }

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
}
