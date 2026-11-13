<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

require_once '../includes/db_connect.php';

$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status_filter'] ?? 'all');
$sort = trim($_GET['sort'] ?? 'id_desc');
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $whereConditions = [];
    $paramsData = [];

    if ($search !== '') {
        $whereConditions[] = "(id LIKE ? OR faucetpay_email LIKE ? OR username LIKE ?)";
        $searchTerm = "%$search%";
        array_push($paramsData, $searchTerm, $searchTerm, $searchTerm);
    }

    if ($status_filter === 'highrisk') {
        $whereConditions[] = "account_status = 'highrisk'";
    } elseif ($status_filter === 'active') {
        $whereConditions[] = "account_status = 'active'";
    }

    $whereClause = "";
    if (count($whereConditions) > 0) {
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
    $stmt->execute($paramsData);
    $total = $stmt->fetchColumn();

    $orderBy = "ORDER BY id DESC";
    if ($sort === 'balance_desc') {
        $orderBy = "ORDER BY balance DESC, id DESC";
    } elseif ($sort === 'balance_asc') {
        $orderBy = "ORDER BY balance ASC, id DESC";
    }

    $query = "SELECT id, username, faucetpay_email, balance, energy, status, account_status, risk_score, created_at FROM users $whereClause $orderBy LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($query);

    $paramIndex = 1;
    foreach ($paramsData as $p) {
        $stmt->bindValue($paramIndex++, $p, PDO::PARAM_STR);
    }
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as &$u) {
        $u['date_formatted'] = date('M d, Y', strtotime($u['created_at']));
    }

    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => $total,
        'page' => $page,
        'total_pages' => ceil($total / $limit)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
