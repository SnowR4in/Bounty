<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$username = trim($_POST['username'] ?? '');

// Validate
if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    echo json_encode(['success' => false, 'error' => 'Username must be 3-20 characters, letters, numbers, and underscores only.']);
    exit;
}

// Check if user already has a username
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$existing = $stmt->fetchColumn();
if ($existing) {
    echo json_encode(['success' => false, 'error' => 'You already have a username set.']);
    exit;
}

// Check uniqueness (case-insensitive)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE LOWER(username) = LOWER(?)");
$stmt->execute([$username]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'error' => 'This username is already taken. Please choose another.']);
    exit;
}

// Set username
try {
    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->execute([$username, $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to set username. Please try again.']);
}
