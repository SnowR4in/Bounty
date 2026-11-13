<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status  = $_GET['status'] ?? 'all';
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $search  = trim($_GET['search'] ?? '');
    $per_page = 20;
    $offset   = ($page - 1) * $per_page;

    $where  = [];
    $params = [];
    if ($status !== 'all') {
        $where[]  = 'w.status = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $where[]  = '(w.faucetpay_email LIKE ? OR w.txid LIKE ? OR u.username LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $pc_stmt = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'");
    $pending_count = (int)$pc_stmt->fetchColumn();

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM withdrawals w LEFT JOIN users u ON w.user_id = u.id $whereSQL");
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetchColumn();
    $pages = max(1, (int)ceil($total / $per_page));

    $row_params = array_merge($params, [$per_page, $offset]);
    $stmt = $pdo->prepare("
        SELECT w.*, u.username
        FROM withdrawals w
        LEFT JOIN users u ON w.user_id = u.id
        $whereSQL
        ORDER BY w.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($row_params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['created_at'] = date('d M Y H:i', strtotime($r['created_at']));
    }

    echo json_encode([
        'rows'          => $rows,
        'total'         => $total,
        'page'          => $page,
        'pages'         => $pages,
        'pending_count' => $pending_count,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
    $stmt->execute([$id]);
    $wd = $stmt->fetch();
    if (!$wd) {
        echo json_encode(['success' => false, 'message' => 'Withdrawal not found']);
        exit;
    }

    if ($action === 'approve') {
        if ($wd['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Only pending withdrawals can be approved.']);
            exit;
        }
        $txid = trim($_POST['txid'] ?? '');
        $note = trim($_POST['note'] ?? '');
        if ($txid === '' || mb_strlen($txid) > 191) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid TxID or payout ID.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE withdrawals SET status='approved', txid=?, note=?, processed_at=NOW() WHERE id=?");
        $stmt->execute([$txid, $note, $id]);
        echo json_encode(['success' => true, 'message' => 'Withdrawal approved.']);
    } elseif ($action === 'pay_faucetpay') {
        if ($wd['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Only pending withdrawals can be paid.']);
            exit;
        }

        require_once '../includes/faucetpay.php';

        $s_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'faucetpay_api_key'");
        $api_key = trim((string)$s_stmt->fetchColumn());

        if ($api_key === '') {
            echo json_encode(['success' => false, 'message' => 'FaucetPay API key is not configured.']);
            exit;
        }

        if (empty($wd['faucetpay_email'])) {
            echo json_encode(['success' => false, 'message' => 'This withdrawal has no FaucetPay email.']);
            exit;
        }

        $amount = (float)$wd['amount'];
        $res = faucetpay_send($api_key, $wd['faucetpay_email'], $amount);

        if (!empty($res['success'])) {
            $payout_id = (string)($res['payout_id'] ?? '');
            $stmt = $pdo->prepare("UPDATE withdrawals SET status='approved', txid=?, processed_at=NOW() WHERE id=?");
            $stmt->execute([$payout_id, $id]);
            echo json_encode(['success' => true, 'txid' => $payout_id, 'message' => $res['message']]);
        } else {
            echo json_encode(['success' => false, 'message' => $res['message'] ?? 'Payout failed.']);
        }
    } elseif ($action === 'reject') {
        if ($wd['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Only pending withdrawals can be rejected.']);
            exit;
        }
        $note = trim($_POST['note'] ?? '');
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$wd['amount'], $wd['user_id']]);
            $stmt = $pdo->prepare("UPDATE withdrawals SET status='rejected', note=?, processed_at=NOW() WHERE id=?");
            $stmt->execute([$note, $id]);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Withdrawal rejected and balance refunded.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM withdrawals WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Record deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Bad request']);
