<?php

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/site_settings.php';

$settings = get_site_settings($pdo);
$secret = $settings['offerwallapi_secret_key'] ?? '';
if ($secret === '') {
    http_response_code(400);
    die('Postbacks are not configured.');
}

$callerIP = $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0])
    ?? ($_SERVER['HTTP_X_REAL_IP'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

$whitelist_str = trim($settings['offerwallapi_ip_whitelist'] ?? '');
if ($whitelist_str !== '') {
    $whitelist = array_map('trim', preg_split('/[\n,]+/', $whitelist_str));
    if (!in_array($callerIP, $whitelist, true)) {
        http_response_code(403);
        die("ERROR: IP not whitelisted ($callerIP)");
    }
}

$subId      = $_REQUEST['subId'] ?? '';
$transId    = $_REQUEST['transId'] ?? '';
$reward     = $_REQUEST['reward'] ?? '';
$offerName  = $_REQUEST['offer_name'] ?? 'OfferwallApi';
$offerType  = strtolower($_REQUEST['offer_type'] ?? '');
$payoutUsd  = $_REQUEST['payout'] ?? '';
$userIp     = $_REQUEST['userIp'] ?? '';
$country    = $_REQUEST['country'] ?? '';
$status     = $_REQUEST['status'] ?? '';
$signature  = $_REQUEST['signature'] ?? '';
$signature2 = $_REQUEST['signature2'] ?? '';
$debug = ($_REQUEST['debug'] ?? '0') === '1';

if (empty($subId) || empty($transId) || $reward === '' || empty($status)) {
    http_response_code(400);
    die('Missing required parameters.');
}

$expected_hmac = hash_hmac('sha256', $subId . $transId . $reward, $secret);
$expected_md5  = md5($subId . $transId . $reward . $secret);

if (!hash_equals($expected_hmac, (string)$signature2) && !hash_equals($expected_md5, (string)$signature)) {
    http_response_code(403);
    die('Invalid signature.');
}

$user_id     = (int)$subId;
$reward_usdt = abs((float)$reward);
if ($user_id <= 0 || $reward_usdt <= 0) {
    http_response_code(400);
    die('Invalid user ID or reward amount.');
}

$status_lower = ((string)$status === '2') ? 'chargeback' : 'completed';

switch ($offerType) {
    case 'ptc':
    case 'push':
        $table = 'ptc_claims';
        $ref_key = 'ref_percent_ptc';
        $xp_key  = 'level_bonus_ptc';
        $ref_cat = 'ptc';
        break;
    case 'video':
        $table = 'video_claims';
        $ref_key = 'ref_percent_video';
        $xp_key  = 'level_bonus_video';
        $ref_cat = 'video';
        break;
    case 'shortlink':
        $table = 'shortlink_claims';
        $ref_key = 'ref_percent_shortlinks';
        $xp_key  = 'level_bonus_shortlink';
        $ref_cat = 'shortlinks';
        break;
    case 'microtask':
        $table = 'task_claims';
        $ref_key = 'ref_percent_tasks';
        $xp_key  = 'level_bonus_tasks';
        $ref_cat = 'tasks';
        break;
    default:
        $table = 'task_claims';
        $ref_key = 'ref_percent_tasks';
        $xp_key  = 'level_bonus_tasks';
        $ref_cat = 'tasks';
        break;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id, status FROM $table WHERE txid = ? FOR UPDATE");
    $stmt->execute([$transId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['status'] === 'completed' && $status_lower === 'chargeback') {
            $updUser = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $updUser->execute([$reward_usdt, $user_id]);

            $updClaim = $pdo->prepare("UPDATE $table SET status = 'chargeback' WHERE txid = ?");
            $updClaim->execute([$transId]);
        }
    } else {
        if ($status_lower === 'completed') {
            $updUser = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $updUser->execute([$reward_usdt, $user_id]);

            $insClaim = $pdo->prepare("INSERT INTO $table (user_id, txid, reward_usdt, status, campaign_name, ip_address, country) VALUES (?, ?, ?, 'completed', ?, ?, ?)");
            $insClaim->execute([$user_id, $transId, $reward_usdt, $offerName, $userIp ?: $callerIP, $country]);
            if (($settings['referrals_enabled'] ?? '0') == '1') {
                $stmtRef = $pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
                $stmtRef->execute([$user_id]);
                $referred_by = $stmtRef->fetchColumn();

                if ($referred_by) {
                    $ref_percent = floatval($settings[$ref_key] ?? 0);
                    if ($ref_percent > 0) {
                        $ref_reward = $reward_usdt * ($ref_percent / 100);
                        if ($ref_reward > 0) {
                            $updRef = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                            $updRef->execute([$ref_reward, $referred_by]);

                            $insRefLog = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_user_id, category, amount) VALUES (?, ?, ?, ?)");
                            $insRefLog->execute([$referred_by, $user_id, $ref_cat, $ref_reward]);
                        }
                    }
                }
            }
            if (($settings['level_system_enabled'] ?? '0') == '1') {
                $stmtXp = $pdo->prepare("SELECT xp FROM users WHERE id = ?");
                $stmtXp->execute([$user_id]);
                $current_xp = intval($stmtXp->fetchColumn());

                $current_level      = floor(sqrt($current_xp / 100));
                $current_level_base = 100 * pow($current_level, 2);
                $next_level_base    = 100 * pow($current_level + 1, 2);
                $xp_needed          = $next_level_base - $current_level_base;

                $xp_percent = 0;
                if (in_array($xp_key, ['level_bonus_ptc', 'level_bonus_shortlink'], true)) {
                    $xp_percent = floatval($settings[$xp_key] ?? 0);
                } else {
                    $tiers = json_decode($settings[$xp_key] ?? '[]', true);
                    $payout_val = floatval($payoutUsd ?: $reward_usdt);
                    if (is_array($tiers)) {
                        foreach ($tiers as $tier) {
                            if ($payout_val <= floatval($tier['amount'])) {
                                $xp_percent = floatval($tier['percent']);
                                break;
                            }
                        }
                    }
                }

                if ($xp_percent > 0) {
                    $gained_xp = intval(round($xp_needed * ($xp_percent / 100)));
                    if ($gained_xp > 0) {
                        $updXp = $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
                        $updXp->execute([$gained_xp, $user_id]);
                    }
                }
            }
        } else {
            $insClaim = $pdo->prepare("INSERT INTO $table (user_id, txid, reward_usdt, status, campaign_name, ip_address, country) VALUES (?, ?, ?, 'chargeback', ?, ?, ?)");
            $insClaim->execute([$user_id, $transId, $reward_usdt, $offerName, $userIp ?: $callerIP, $country]);
        }
    }
    $pdo->commit();
    echo "ok";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo "Internal Server Error";
}
