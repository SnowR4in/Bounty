<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        $stmt = $pdo->query("SELECT MAX(display_order) as max_order FROM wheel_segments");
        $max = $stmt->fetchColumn();
        $next = $max !== false ? $max + 1 : 1;

        $stmt = $pdo->prepare("INSERT INTO wheel_segments (display_order) VALUES (?)");
        $stmt->execute([$next]);
        echo json_encode(['status' => 'success']);
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $field = $_POST['field'];
        $value = $_POST['value'];

        $allowed_fields = ['color', 'reward_type', 'reward_value', 'win_chance'];
        if (in_array($field, $allowed_fields)) {
            $stmt = $pdo->prepare("UPDATE wheel_segments SET `$field` = ? WHERE id = ?");
            $stmt->execute([$value, $id]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
        }
    } elseif ($action === 'move') {
        $id = intval($_POST['id']);
        $direction = $_POST['direction'];

        $stmt = $pdo->prepare("SELECT id, display_order FROM wheel_segments WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch();

        if ($current) {
            if ($direction === 'up') {
                $stmt = $pdo->prepare("SELECT id, display_order FROM wheel_segments WHERE display_order < ? ORDER BY display_order DESC LIMIT 1");
            } else {
                $stmt = $pdo->prepare("SELECT id, display_order FROM wheel_segments WHERE display_order > ? ORDER BY display_order ASC LIMIT 1");
            }
            $stmt->execute([$current['display_order']]);
            $swap = $stmt->fetch();

            if ($swap) {
                $stmt = $pdo->prepare("UPDATE wheel_segments SET display_order = ? WHERE id = ?");
                $stmt->execute([$swap['display_order'], $current['id']]);
                $stmt->execute([$current['display_order'], $swap['id']]);
            }
            echo json_encode(['status' => 'success']);
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM wheel_segments WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
