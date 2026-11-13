<?php
session_start();
header('Content-Type: application/json');

// Fake success for bots if they aren't even logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'message' => 'Bonus claimed successfully!']);
    exit;
}

require_once '../includes/db_connect.php';

try {
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];

    $pdo->beginTransaction();

    // Set shadow ban
    $stmt = $pdo->prepare("UPDATE users SET is_shadow_banned = 1 WHERE id = ?");
    $stmt->execute([$user_id]);

    // Insert security log
    $reason = 'Honeypot Triggered (IP: ' . $ip_address . ')';
    $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, severity, reason) VALUES (?, 'critical', ?)");
    $stmt->execute([$user_id, $reason]);

    $pdo->commit();

    // Fake success message
    echo json_encode(['success' => true, 'message' => 'Bonus claimed successfully!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Return fake success even on error so bots don't know it failed
    echo json_encode(['success' => true, 'message' => 'Bonus claimed successfully!']);
}
