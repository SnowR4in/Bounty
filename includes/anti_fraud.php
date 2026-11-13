<?php

function decode_telemetry_payload($payload) {
    if (empty($payload)) return null;
    $decoded = base64_decode($payload);
    if (!$decoded) return null;
    $data = json_decode($decoded, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    return $data;
}

function generate_hardware_hash($signals) {
    $canvas = $signals['canvasFingerprint'] ?? '';
    $w = $signals['screenWidth'] ?? '';
    $h = $signals['screenHeight'] ?? '';
    $cd = $signals['colorDepth'] ?? '';
    $tz = $signals['timezoneOffset'] ?? '';
    $mem = $signals['deviceMemory'] ?? '';
    $hc = $signals['hardwareConcurrency'] ?? '';
    $webgl = $signals['webglRenderer'] ?? '';

    $rawString = "{$canvas}|{$w}x{$h}|{$cd}|{$tz}|{$mem}|{$hc}|{$webgl}";
    return 'hw_' . md5($rawString);
}

function generate_fp_hash($signals) {
    $canvas = $signals['canvasFingerprint'] ?? '';
    return md5($canvas);
}

function analyze_telemetry_and_enforce($pdo, $user_id, $telemetry_data, $ip_address) {
    if (!$telemetry_data || !isset($telemetry_data['signals'])) {
        http_response_code(403);
        die("Suspicious activity detected! [Code: MISSING_SIG]");
    }

    $signals = $telemetry_data['signals'];

    if (empty($signals['canvasFingerprint']) || $signals['canvasFingerprint'] === 'unknown') {
        http_response_code(403);
        die("Suspicious activity detected! [Code: MISSING_HW]");
    }

    $hw_hash = generate_hardware_hash($signals);
    $fp_hash = generate_fp_hash($signals);
    $trusted_token = $telemetry_data['trustedToken'] ?? '';

    if (isset($signals['webdriver']) && $signals['webdriver'] === true) {
        $stmt = $pdo->prepare("UPDATE users SET account_status = 'highrisk', is_shadow_banned = 1, risk_score = risk_score + 50 WHERE id = ? AND is_shadow_banned = 0");
        $stmt->execute([$user_id]);
        if ($stmt->rowCount() > 0) {
            $log = $pdo->prepare("INSERT INTO security_logs (user_id, severity, reason) VALUES (?, 'high', 'Automated Bot Detected (WebDriver)')");
            $log->execute([$user_id]);
        }
    }

    $stmt = $pdo->prepare("SELECT id FROM user_fingerprints WHERE user_id = ? AND hw_hash = ?");
    $stmt->execute([$user_id, $hw_hash]);
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare("INSERT INTO user_fingerprints (user_id, hw_hash, fp_hash, trusted_token) VALUES (?, ?, ?, ?)");
        $ins->execute([$user_id, $hw_hash, $fp_hash, $trusted_token]);

        $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_fingerprints WHERE hw_hash = ?");
        $countStmt->execute([$hw_hash]);
        $shared_users_count = $countStmt->fetchColumn();

        if ($shared_users_count >= 5) {
            $selStmt = $pdo->prepare("SELECT DISTINCT user_id FROM user_fingerprints WHERE hw_hash = ?");
            $selStmt->execute([$hw_hash]);
            $users = $selStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($users as $uid) {
                $upd = $pdo->prepare("UPDATE users SET account_status = 'highrisk', is_shadow_banned = 1 WHERE id = ? AND is_shadow_banned = 0");
                $upd->execute([$uid]);
                if ($upd->rowCount() > 0) {
                    $log = $pdo->prepare("INSERT INTO security_logs (user_id, severity, reason) VALUES (?, 'critical', 'Hardware Hash shared with multiple accounts. Auto-banned.')");
                    $log->execute([$uid]);
                }
            }
        } elseif ($shared_users_count >= 3) {
            $upd = $pdo->prepare("UPDATE users SET risk_score = risk_score + 10 WHERE id = ?");
            $upd->execute([$user_id]);

            $log = $pdo->prepare("INSERT INTO security_logs (user_id, severity, reason) VALUES (?, 'medium', 'Hardware Hash shared with 3 or 4 accounts.')");
            $log->execute([$user_id]);
        }
    }

    $page_path = htmlspecialchars($telemetry_data['page'] ?? 'unknown');
    $mouse = $signals['mouseMovements'] ?? 0;

    $isMobile = (isset($signals['maxTouchPoints']) && $signals['maxTouchPoints'] > 0) && ($mouse < 5);
    $clicks = $signals['clickCount'] ?? 0;
    $touch = $signals['touchEvents'] ?? 0;
    $mouse = $signals['mouseMovements'] ?? 0;

    $risk_level = 'low';
    $details = '';

    if ($isMobile) {
        if ($touch == 0 && $clicks > 0) {
            $risk_level = 'high';
            $details = "[$page_path] Mobile profile with 0 touch events but $clicks clicks.";
        } elseif ($touch < 2 && $clicks > 0) {
            $risk_level = 'medium';
            $details = "[$page_path] Mobile profile with low touch events ($touch) but $clicks clicks.";
        }
    } else {
        if ($mouse == 0 && $clicks > 0) {
            $risk_level = 'high';
            $details = "[$page_path] Desktop profile with 0 mouse movements but $clicks clicks.";
        } elseif ($mouse < 10 && $clicks > 0) {
            $risk_level = 'medium';
            $details = "[$page_path] Desktop profile with low mouse movements ($mouse) but $clicks clicks.";
        }
    }

    if ($risk_level === 'low') {
        $details = "[$page_path] Normal movement profile (Mouse: $mouse, Clicks: $clicks, Touch: $touch).";
    }

    if ($risk_level !== 'low') {
        $ins = $pdo->prepare("INSERT INTO movement_logs (user_id, risk_level, details) VALUES (?, ?, ?)");
        $ins->execute([$user_id, $risk_level, $details]);
    }

    return [
        'hw_hash' => $hw_hash,
        'fp_hash' => $fp_hash,
        'trusted_token' => $trusted_token
    ];
}
