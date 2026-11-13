<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = intval($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID.']);
    exit;
}

if ($action === 'edit') {
    $balance = floatval($_POST['balance'] ?? 0);
    $status = intval($_POST['status'] ?? 1);

    if ($balance < 0) $balance = 0;
    if (!in_array($status, [0, 1, 2])) $status = 1;

    try {
        $stmt = $pdo->prepare("UPDATE users SET balance = ?, status = ? WHERE id = ?");
        $stmt->execute([number_format($balance, 8, '.', ''), $status, $user_id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to update user.']);
    }
} elseif ($action === 'delete') {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM faucet_claims WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $stmt = $pdo->prepare("DELETE FROM referral_earnings WHERE referrer_id = ? OR referred_user_id = ?");
        $stmt->execute([$user_id, $user_id]);

        $stmt = $pdo->prepare("UPDATE users SET referred_by = NULL WHERE referred_by = ?");
        $stmt->execute([$user_id]);

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Failed to delete user.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}
