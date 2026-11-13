<?php

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/site_settings.php';

$settings = get_site_settings($pdo);
$secret = trim((string)($settings['offerwall_secret_key'] ?? ''));
if ($secret === '') {
    http_response_code(400);
    echo 'ERROR: Postbacks are not configured.';
    exit;
}

function owme_postback_ip(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

$callerIP = owme_postback_ip();
$whitelist_str = trim((string)($settings['offerwall_ip_whitelist'] ?? "95.216.65.163\n2a01:4f9:2b:1dc::2"));
if ($whitelist_str !== '') {
    $whitelist = array_map('trim', preg_split('/[\n,]+/', $whitelist_str));
    if (!in_array($callerIP, $whitelist, true)) {
        http_response_code(403);
        echo "ERROR: IP not whitelisted ($callerIP)";
        exit;
    }
}

$userId     = $_REQUEST['subId'] ?? null;
$transId    = $_REQUEST['transId'] ?? null;
$reward     = $_REQUEST['reward'] ?? null;
$status     = $_REQUEST['status'] ?? null;
$signature  = $_REQUEST['signature'] ?? null;
$offer_name = $_REQUEST['offer_name'] ?? 'Offerwall.me';
$offer_type = strtolower(trim((string)($_REQUEST['offer_type'] ?? 'offer')));
$userIp     = $_REQUEST['userIp'] ?? '';
$country    = $_REQUEST['country'] ?? '';
$payoutUsd  = $_REQUEST['payout'] ?? '';

if (!hash_equals(md5((string)$userId . (string)$transId . (string)$reward . $secret), (string)$signature)) {
    http_response_code(403);
    echo "ERROR: Signature doesn't match";
    exit;
}

if (!$userId || !$transId || $reward === null || $reward === '') {
    http_response_code(400);
    echo "ERROR: Missing parameters";
    exit;
}

$user_id     = (int)$userId;
$reward_usdt = abs((float)$reward);
if ($user_id <= 0 || $reward_usdt <= 0) {
    http_response_code(400);
    echo 'ERROR: Invalid user ID or reward amount.';
    exit;
}

$stmtUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
if (!$stmtUser->fetchColumn()) {
    http_response_code(400);
    echo 'ERROR: User not found.';
    exit;
}

$is_chargeback = ((string)$status === '2');
$status_lower  = $is_chargeback ? 'chargeback' : 'completed';

switch ($offer_type) {
    case 'ptc':
    case 'push':
        $table   = 'ptc_claims';
        $ref_key = 'ref_percent_ptc';
        $xp_key  = 'level_bonus_ptc';
        $ref_cat = 'ptc';
        break;
    case 'shortlink':
        $table   = 'shortlink_claims';
        $ref_key = 'ref_percent_shortlinks';
        $xp_key  = 'level_bonus_shortlink';
        $ref_cat = 'shortlinks';
        break;
    case 'task':
        $table   = 'task_claims';
        $ref_key = 'ref_percent_tasks';
        $xp_key  = 'level_bonus_tasks';
        $ref_cat = 'tasks';
        break;
    default:
        $table   = 'offerwall_claims';
        $ref_key = 'ref_percent_offerwall';
        $xp_key  = 'level_bonus_offerwall';
        $ref_cat = 'offerwall';
        break;
}

$claim_ip = $userIp !== '' ? $userIp : $callerIP;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, status FROM $table WHERE txid = ? FOR UPDATE");
    $stmt->execute([$transId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['status'] === 'completed' && $is_chargeback) {
            $updUser = $pdo->prepare("UPDATE users SET balance = GREATEST(0, balance - ?) WHERE id = ?");
            $updUser->execute([$reward_usdt, $user_id]);

            $updClaim = $pdo->prepare("UPDATE $table SET status = 'chargeback' WHERE txid = ?");
            $updClaim->execute([$transId]);
        }
    } else {
        if (!$is_chargeback) {
            $updUser = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $updUser->execute([$reward_usdt, $user_id]);

            $insClaim = $pdo->prepare("INSERT INTO $table (user_id, txid, reward_usdt, status, campaign_name, ip_address, country) VALUES (?, ?, ?, 'completed', ?, ?, ?)");
            $insClaim->execute([$user_id, $transId, $reward_usdt, $offer_name, $claim_ip, $country]);

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
                if (in_array($xp_key, ['level_bonus_ptc', 'level_bonus_shortlink', 'level_bonus_offerwall'], true)) {
                    $xp_percent = floatval($settings[$xp_key] ?? 0);
                } else {
                    $tiers = json_decode($settings[$xp_key] ?? '[]', true);
                    $payout_val = floatval($payoutUsd !== '' ? $payoutUsd : $reward_usdt);
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
            $insClaim->execute([$user_id, $transId, $reward_usdt, $offer_name, $claim_ip, $country]);
        }
    }

    $pdo->commit();
    echo "ok";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo "ERROR: Database error";
}
