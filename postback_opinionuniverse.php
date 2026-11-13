<?php

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/site_settings.php';

$settings = get_site_settings($pdo);

function ou_postback_ip(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function ou_req(array $keys, string $default = ''): string {
    foreach ($keys as $key) {
        if (isset($_REQUEST[$key]) && $_REQUEST[$key] !== '') {
            return (string)$_REQUEST[$key];
        }
    }
    return $default;
}

$callerIP = ou_postback_ip();
$whitelist_str = trim((string)($settings['ou_ip_whitelist'] ?? OU_POSTBACK_IPS));
if ($whitelist_str !== '') {
    $whitelist = array_map('trim', preg_split('/[\n,]+/', $whitelist_str));
    if (!in_array($callerIP, $whitelist, true)) {
        http_response_code(403);
        echo "ERROR: IP not whitelisted ($callerIP)";
        exit;
    }
}

$sid       = ou_req(['sid', 'SID', 'sub_id', 'subId']);
$offer_id  = ou_req(['offerid', 'offer_id', 'OFFERID']);
$status    = ou_req(['status', 'STATUS']);
$payout    = ou_req(['payout', 'PAYOUT']);
$pubpayout = ou_req(['pubpayout', 'PUBPAYOUT']);
$transId   = ou_req(['txid', 'TransactionID', 'transactionid', 'transId']);
$userIp    = ou_req(['ip', 'IP', 'userIp']);
$sig       = ou_req(['sig', 'SIG', 'hash', 'HASH']);
$offername = ou_req(['offername', 'eventname', 'event_name']);
$app_id    = ou_req(['app_id', 'appId']);
$is_ou_tester = ($callerIP === '147.182.172.173');

$allowed_app = ou_app_id($settings);
if (!$is_ou_tester && $app_id !== '' && $allowed_app !== '' && !hash_equals($allowed_app, $app_id)) {
    http_response_code(400);
    echo 'ERROR: Invalid app_id.';
    exit;
}

$allowed_offers = [];
foreach (ou_survey_walls() as $wall) {
    $allowed_offers[$wall['offer_id']] = $wall['name'];
}
try {
    ou_ensure_offers_table($pdo);
    foreach ($pdo->query("SELECT offer_id, title FROM ou_offers")->fetchAll(PDO::FETCH_ASSOC) as $o) {
        $allowed_offers[(string)$o['offer_id']] = $o['title'];
    }
} catch (Exception $e) {
}
if (!$is_ou_tester && $offer_id !== '' && !isset($allowed_offers[$offer_id])) {
    http_response_code(400);
    echo 'ERROR: Unsupported offer.';
    exit;
}

$secret = trim((string)($settings['ou_secret_key'] ?? ''));
if (!$is_ou_tester && $secret !== '') {
    if ($sig === '' || !ou_verify_sig($secret, $sid, $offer_id, $payout, $status, $sig)) {
        http_response_code(403);
        echo "ERROR: Signature doesn't match";
        exit;
    }
}

$user_id = ou_user_id_from_sid($sid);
if ($user_id <= 0 && $sid !== '') {
    $stmtSid = $pdo->prepare("SELECT id FROM users WHERE username = ? OR faucetpay_email = ? LIMIT 1");
    $stmtSid->execute([$sid, $sid]);
    $user_id = (int)$stmtSid->fetchColumn();
}
if ($user_id <= 0) {
    if ($is_ou_tester) {
        echo '1';
        exit;
    }
    http_response_code(400);
    echo 'ERROR: Missing user ID.';
    exit;
}

$reward_raw = $payout !== '' ? $payout : ($pubpayout !== '' ? $pubpayout : ou_req(['reward', 'REWARD', 'amount', 'AMOUNT', 'payout_usd', 'PAYOUT_USD', 'usd', 'USD']));
$reward_usdt = abs((float)$reward_raw);
if ($reward_usdt <= 0) {
    http_response_code(400);
    echo 'ERROR: Invalid reward amount.';
    exit;
}

if ($transId === '') {
    $transId = hash('sha256', $sid . '|' . $offer_id . '|' . $payout . '|' . $status);
}
$transId = 'ou_' . $transId;

$stmtUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
if (!$stmtUser->fetchColumn()) {
    if ($is_ou_tester) {
        echo '1';
        exit;
    }
    http_response_code(400);
    echo 'ERROR: User not found.';
    exit;
}

$status_norm = (string)$status;
$is_chargeback = ($status_norm === '2');
if (!$is_chargeback && $status_norm !== '' && $status_norm !== '1') {
    echo '1';
    exit;
}
$offer_name = $offername !== '' ? $offername : ($allowed_offers[$offer_id] ?? 'OpinionUniverse');
$claim_ip = $userIp !== '' ? $userIp : $callerIP;
$country = '';
$ref_key = 'ref_percent_offerwall';
$xp_key  = 'level_bonus_offerwall';
$ref_cat = 'offerwall';
$table   = 'offerwall_claims';

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

                $xp_percent = floatval($settings[$xp_key] ?? 0);
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
    echo '1';
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo 'ERROR: Database error';
}
