<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/site_settings.php';
$settings = get_site_settings($pdo);

if (!isset($settings['video_enabled']) || $settings['video_enabled'] == '0') {
    echo json_encode(['success' => false, 'message' => 'Video disabled']);
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

// Fetch OfferwallApi videos if enabled (Offerwall.me does not provide a video feed)
if (isset($settings['offerwallapi_video_enabled']) && $settings['offerwallapi_video_enabled'] == '1') {
    $feed = owapi_fetch_feed('video', $settings, $user_id, $userIP);
    if (!empty($feed['items'])) {
        foreach ($feed['items'] as $item) {
            $tasks[] = $item;
        }
    }
}

echo json_encode(['success' => true, 'tasks' => $tasks]);
