<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../includes/db_connect.php';

$user_id = (int)$_SESSION['user_id'];
$ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

// Fetch user
$stmt = $pdo->prepare("SELECT balance, faucetpay_email, status, is_shadow_banned FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['status'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Account unavailable.']);
    exit;
}

if ($user['is_shadow_banned'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Your withdrawal requires manual review. Please contact support or wait 24-48 hours.']);
    exit;
}

// Payout email linked to FaucetPay
$email = trim((string)($user['faucetpay_email'] ?? ''));
if ($email === '') {
    echo json_encode(['success' => false, 'message' => 'No payout email is set on your account. Please contact support.']);
    exit;
}

// Fetch settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('min_withdrawal','instant_withdrawals_enabled','faucetpay_api_key')");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$min_wd     = floatval($settings['min_withdrawal'] ?? 0.01);
$instant    = ($settings['instant_withdrawals_enabled'] ?? '1') === '1';
$fp_api_key = trim($settings['faucetpay_api_key'] ?? '');
$balance    = floatval($user['balance']);

if ($balance < $min_wd) {
    echo json_encode(['success' => false, 'message' => 'Balance below minimum withdrawal amount.']);
    exit;
}

$amount = $balance; // withdraw full balance
$fee    = 0.0;

try {
    $pdo->beginTransaction();

    // Lock user row & re-read balance
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $locked = $stmt->fetch();
    $balance = floatval($locked['balance']);

    if ($balance < $min_wd) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Balance below minimum withdrawal.']);
        exit;
    }

    $amount       = $balance;
    $txid         = null;
    $status       = 'pending';
    $instant_sent = false;

    if ($instant && $fp_api_key !== '') {
        // Instant payout via FaucetPay (email must be linked to a FaucetPay account)
        $result = faucetpay_send($fp_api_key, $email, $amount, $ip_address);

        if (!$result['success']) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $result['message']]);
            exit;
        }

        $txid         = ($result['payout_id'] ?? '') !== '' ? $result['payout_id'] : null;
        $status       = 'approved';
        $instant_sent = true;
    }

    // Deduct balance
    $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
    $stmt->execute([$amount, $user_id, $amount]);

    // Insert withdrawal record
    $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, faucetpay_email, amount, fee, status, txid) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $email, $amount, $fee, $status, $txid]);

    $pdo->commit();

    if ($instant_sent) {
        echo json_encode([
            'success' => true,
            'instant' => true,
            'message' => 'Withdrawal sent to your payout email!',
            'txid'    => $txid,
            'amount'  => $amount,
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'instant' => false,
            'message' => 'Withdrawal request submitted! Admin will process it shortly.',
        ]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
