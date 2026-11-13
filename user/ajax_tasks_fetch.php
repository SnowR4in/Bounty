<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/site_settings.php';
$settings = get_site_settings($pdo);

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

$tasks = [];
$approved = [];

if ($owapi_tasks_on) {
    $feed = owapi_fetch_feed('task', $settings, $user_id, $userIP);
    $tasks = $feed['items'];
    $approved = $feed['approved'];
}

if ($owme_tasks_on) {
    foreach (owme_fetch_list('taskapi.php', $settings, (string)$user_id, $userIP) as $t) {
        $tasks[] = $t;
    }
}

echo json_encode([
    'success' => true,
    'tasks' => $tasks,
    'approved' => $approved
]);
