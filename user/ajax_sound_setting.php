<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

require_once '../includes/db_connect.php';

$play_sound = isset($_POST['play_sound']) && $_POST['play_sound'] == '1' ? 1 : 0;

try {
    $stmt = $pdo->prepare("UPDATE users SET play_sound = ? WHERE id = ?");
    $stmt->execute([$play_sound, $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
