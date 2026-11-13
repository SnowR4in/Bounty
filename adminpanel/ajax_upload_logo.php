<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['logo_file'];
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
$max_size = 2 * 1024 * 1024;

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, SVG']);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 2MB allowed']);
    exit;
}

$upload_dir = __DIR__ . '/../../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'logo_' . time() . '.' . strtolower($ext);
$dest = $upload_dir . $filename;

if ($mime !== 'image/svg+xml' && extension_loaded('gd')) {
    list($orig_w, $orig_h) = getimagesize($file['tmp_name']);
    $max_dim = 200;
    $ratio = min($max_dim / $orig_w, $max_dim / $orig_h);
    $new_w = (int)round($orig_w * $ratio);
    $new_h = (int)round($orig_h * $ratio);

    $dst = imagecreatetruecolor($new_w, $new_h);

    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefilledrectangle($dst, 0, 0, $new_w, $new_h, $transparent);

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $src_img = imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png':
            $src_img = imagecreatefrompng($file['tmp_name']); break;
        case 'image/gif':
            $src_img = imagecreatefromgif($file['tmp_name']); break;
        case 'image/webp':
            $src_img = imagecreatefromwebp($file['tmp_name']); break;
        default:
            $src_img = null;
    }

    if ($src_img) {
        imagecopyresampled($dst, $src_img, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
        imagedestroy($src_img);
        imagepng($dst, $dest);
        imagedestroy($dst);
        $filename = 'logo_' . time() . '.png';
        $dest = $upload_dir . $filename;
        imagepng($dst, $dest);
    } else {
        move_uploaded_file($file['tmp_name'], $dest);
    }
} else {
    move_uploaded_file($file['tmp_name'], $dest);
}

$logo_url = '/uploads/' . $filename;
try {
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('logo_image', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$logo_url]);
    echo json_encode(['success' => true, 'url' => $logo_url, 'message' => 'Logo uploaded successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
