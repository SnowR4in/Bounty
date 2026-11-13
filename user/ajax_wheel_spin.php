<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db_connect.php';
require_once '../includes/anti_fraud.php';

// Authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}
$user_id = intval($_SESSION['user_id']);

// Wheel Status check
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'wheel_enabled'");
if ($stmt->fetchColumn() != '1') {
    echo json_encode(['status' => 'error', 'message' => 'Wheel of Fortune is disabled.']);
    exit;
}

// CSRF check
$token = $_POST['token'] ?? '';
if (empty($token) || $token !== $_SESSION['wheel_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid session token. Please refresh the page.']);
    exit;
}

// Rate Limiting (Anti Double-Click)
if (isset($_SESSION['last_wheel_spin']) && (time() - $_SESSION['last_wheel_spin']) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Spinning too fast!']);
    exit;
}
$_SESSION['last_wheel_spin'] = time();

// Telemetry Anti-Cheat Check
$headers = getallheaders();
$telemetry_payload = $headers['x-sec-auth'] ?? ($headers['X-Sec-Auth'] ?? '');

if (empty($telemetry_payload)) {
    echo json_encode(['status' => 'error', 'message' => 'Browser security check failed. Reload page.']);
    exit;
}

$telemetry_data = decode_telemetry_payload($telemetry_payload);
if (!$telemetry_data || !isset($telemetry_data['signals'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid security payload.']);
    exit;
}

// Get user IP for logs
$ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];

// Run telemetry enforcement rules (will exit if fraudulent)
analyze_telemetry_and_enforce($pdo, $user_id, $telemetry_data, $ip_address);

// Captcha Validation (none | google | turnstile)
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$wheel_captcha = $settings['wheel_captcha'] ?? 'none';

if (!captcha_verify($wheel_captcha, $settings)) {
    echo json_encode(['status' => 'error', 'message' => captcha_error_message($wheel_captcha)]);
    exit;
}

// Fetch segments
$stmt = $pdo->query("SELECT * FROM wheel_segments ORDER BY display_order ASC");
$segments = $stmt->fetchAll();

if (count($segments) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Wheel not configured.']);
    exit;
}

// Fetch energy cost
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'wheel_energy_cost'");
$energy_cost = intval($stmt->fetchColumn() ?: 10);

try {
    // Start Transaction
    $pdo->beginTransaction();
    
    // Lock user row for update
    $stmt = $pdo->prepare("SELECT balance, energy FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || $user['energy'] < $energy_cost) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Not enough energy!']);
        exit;
    }
    
    // Weighted Random Selection
    $totalWeight = 0;
    foreach ($segments as $seg) {
        $totalWeight += floatval($seg['win_chance']);
    }
    
    $random = (mt_rand() / mt_getrandmax()) * $totalWeight;
    $winningSegment = $segments[0];
    
    foreach ($segments as $seg) {
        if ($random < floatval($seg['win_chance'])) {
            $winningSegment = $seg;
            break;
        }
        $random -= floatval($seg['win_chance']);
    }
    
    $reward_type = $winningSegment['reward_type'];
    $reward_value = floatval($winningSegment['reward_value']);
    
    // Deduct cost
    $new_energy = $user['energy'] - $energy_cost;
    $new_balance = floatval($user['balance']);
    
    // Add reward
    if ($reward_value > 0) {
        if ($reward_type === 'Energy') {
            $new_energy += $reward_value;
        } else if ($reward_type === 'USDT') {
            $new_balance += $reward_value;
        }
    }
    
    // Update user
    $stmt = $pdo->prepare("UPDATE users SET energy = ?, balance = ? WHERE id = ?");
    $stmt->execute([$new_energy, $new_balance, $user_id]);
    
    // Log spin
    $stmt = $pdo->prepare("INSERT INTO wheel_spins (user_id, reward_type, reward_value, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $reward_type, $reward_value, $ip_address]);
    
    // Commit
    $pdo->commit();
    
    // Regenerate CSRF to prevent replay attacks
    $_SESSION['wheel_token'] = bin2hex(random_bytes(32));
    
    echo json_encode([
        'status' => 'success',
        'winning_segment_id' => $winningSegment['id'],
        'reward_type' => $reward_type,
        'reward_value' => rtrim(rtrim(sprintf('%.6f', $reward_value), '0'), '.'),
        'new_energy' => $new_energy
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
