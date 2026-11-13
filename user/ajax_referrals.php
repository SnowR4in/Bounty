<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

require_once '../includes/db_connect.php';

// Check if referrals module is enabled
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'referrals_enabled'");
if ($stmt->fetchColumn() != '1') {
    echo json_encode(['success' => false, 'error' => 'Referrals module is disabled.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Check if user is shadow banned
$stmt = $pdo->prepare("SELECT is_shadow_banned FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$is_shadow_banned = (int)$stmt->fetchColumn();

if ($is_shadow_banned === 1) {
    echo json_encode([
        'success' => true,
        'referrals' => [],
        'total' => 0,
        'page' => $page,
        'total_pages' => 0
    ]);
    exit;
}

try {
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referred_by = ?");
    $stmt->execute([$user_id]);
    $total = $stmt->fetchColumn();

    // Get paginated results
    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.faucetpay_email, 
            u.username,
            u.created_at,
            COALESCE(SUM(re.amount), 0) as total_earned
        FROM users u 
        LEFT JOIN referral_earnings re ON u.id = re.referred_user_id AND re.referrer_id = u.referred_by
        WHERE u.referred_by = ? 
        GROUP BY u.id
        ORDER BY u.id DESC 
        LIMIT ? OFFSET ?
    ");
    // Bind values explicitly for LIMIT and OFFSET to avoid PDO string conversion issues
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates
    foreach ($referrals as &$ref) {
        $ref['date_formatted'] = date('M d, Y', strtotime($ref['created_at']));
    }
        // Mask FaucetPay emails for privacy
        if (!empty($ref['faucetpay_email']) && filter_var($ref['faucetpay_email'], FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $ref['faucetpay_email']);
            $ref['faucetpay_email'] = substr($parts[0], 0, 2) . '***@' . $parts[1];
        }

    echo json_encode([
        'success' => true,
        'referrals' => $referrals,
        'total' => $total,
        'page' => $page,
        'total_pages' => ceil($total / $limit)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
