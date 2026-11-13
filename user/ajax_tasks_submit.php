<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/site_settings.php';
$settings = get_site_settings($pdo);

header('Content-Type: application/json');

$owapi_tasks_on = isset($settings['offerwallapi_tasks_enabled']) && $settings['offerwallapi_tasks_enabled'] == '1';
$owme_tasks_on = isset($settings['offerwall_tasks_enabled']) && $settings['offerwall_tasks_enabled'] == '1';
if (!$owapi_tasks_on && !$owme_tasks_on) {
    echo json_encode(['success' => false, 'message' => 'Tasks disabled']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

$userIP = getUserIP();

// Accept JSON body or regular POST
$taskId = '';
$proof = '';
$provider = '';

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
if (is_array($json)) {
    $taskId = isset($json['taskId']) ? (string)$json['taskId'] : '';
    $proof  = isset($json['proof']) ? (string)$json['proof'] : '';
    $provider = isset($json['provider']) ? (string)$json['provider'] : '';
}
if ($taskId === '' && isset($_POST['taskId'])) $taskId = (string)$_POST['taskId'];
if ($proof === '' && isset($_POST['proof'])) $proof = (string)$_POST['proof'];
if ($provider === '' && isset($_POST['provider'])) $provider = (string)$_POST['provider'];

$taskId = trim($taskId);
$proof = trim($proof);
$provider = strtolower(trim($provider));

if ($taskId === '' || $proof === '') {
    echo json_encode(['success' => false, 'message' => 'Missing task or proof.']);
    exit;
}

if ($provider === 'offerwall.me') {
    if (!$owme_tasks_on) {
        echo json_encode(['success' => false, 'message' => 'Tasks are currently disabled.']);
        exit;
    }
    $len = function_exists('mb_strlen') ? mb_strlen($proof) : strlen($proof);
    if ($len < 1 || $len > 2000) {
        echo json_encode(['success' => false, 'message' => 'Proof must be between 1 and 2000 characters.']);
        exit;
    }
    $result = owme_submit_task($settings, (string)$user_id, $userIP, $taskId, $proof);
    echo json_encode($result);
    exit;
}

// Resolve proofType from the live feed so validation matches the task definition
$proofType = 'TEXT';
$taskFound = false;
$feed = owapi_fetch_feed('task', $settings, $user_id, $userIP);

if (!empty($feed['approved']) && in_array($taskId, array_map('strval', $feed['approved']), true)) {
    echo json_encode(['success' => true, 'message' => 'This task is already approved.']);
    exit;
}

foreach ($feed['items'] as $item) {
    if ((string)($item['id'] ?? '') === $taskId) {
        $taskFound = true;
        $proofType = strtoupper((string)($item['proofType'] ?? 'TEXT'));
        break;
    }
}

// Validate the proof according to proofType
if ($proofType === 'URL' || $proofType === 'IMAGE_URL') {
    $valid = strlen($proof) <= 2048
        && filter_var($proof, FILTER_VALIDATE_URL) !== false
        && preg_match('#^https?://#i', $proof) === 1;
    if (!$valid) {
        echo json_encode([
            'success' => false,
            'message' => $proofType === 'IMAGE_URL'
                ? 'Please provide a valid image URL (http/https, max 2048 characters).'
                : 'Please provide a valid URL (http/https, max 2048 characters).'
        ]);
        exit;
    }
} else {
    $len = function_exists('mb_strlen') ? mb_strlen($proof) : strlen($proof);
    if ($len < 1 || $len > 2000) {
        echo json_encode(['success' => false, 'message' => 'Proof must be between 1 and 2000 characters.']);
        exit;
    }
}

// Unknown task (not in the feed anymore) — apply generic limit and let OfferwallApi decide
if (!$taskFound && strlen($proof) > 2048) {
    echo json_encode(['success' => false, 'message' => 'Proof is too long (max 2048 characters).']);
    exit;
}

$result = owapi_submit_task($settings, $user_id, $userIP, $taskId, $proof);
echo json_encode($result);
