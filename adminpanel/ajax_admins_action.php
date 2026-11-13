<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Please fill all fields.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE admin_username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Username already exists.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO admin_users (admin_username, admin_password) VALUES (?, ?)");
    if ($stmt->execute([$username, $hash])) {
        echo json_encode(['success' => true, 'message' => 'Admin added successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add admin.']);
    }
} elseif ($action === 'edit') {
    $id = intval($_POST['admin_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($id <= 0 || empty($username)) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT admin_username FROM admin_users WHERE id = ?");
    $stmt->execute([$id]);
    $current_admin = $stmt->fetch();

    if (!$current_admin) {
        echo json_encode(['success' => false, 'error' => 'Admin not found.']);
        exit;
    }

    if ($current_admin['admin_username'] === 'admin') {
        $username = 'admin';
    } else {
        $check = $pdo->prepare("SELECT id FROM admin_users WHERE admin_username = ? AND id != ?");
        $check->execute([$username, $id]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Username already exists.']);
            exit;
        }
    }

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE admin_users SET admin_username = ?, admin_password = ? WHERE id = ?");
        $upd->execute([$username, $hash, $id]);
    } else {
        $upd = $pdo->prepare("UPDATE admin_users SET admin_username = ? WHERE id = ?");
        $upd->execute([$username, $id]);
    }

    echo json_encode(['success' => true, 'message' => 'Admin updated successfully.']);
} elseif ($action === 'delete') {
    $id = intval($_POST['admin_id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid admin ID.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT admin_username FROM admin_users WHERE id = ?");
    $stmt->execute([$id]);
    $current_admin = $stmt->fetch();

    if (!$current_admin) {
        echo json_encode(['success' => false, 'error' => 'Admin not found.']);
        exit;
    }

    if ($current_admin['admin_username'] === 'admin') {
        echo json_encode(['success' => false, 'error' => 'Cannot delete the root admin.']);
        exit;
    }

    if ($current_admin['admin_username'] === $_SESSION['admin_username']) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete yourself.']);
        exit;
    }

    $del = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
    if ($del->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Admin deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete admin.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}
