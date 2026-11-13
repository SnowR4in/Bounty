<?php

function smtp_settings_from(array $s): array {
    return [
        'enabled'    => ($s['smtp_enabled'] ?? '0') === '1',
        'host'       => trim((string)($s['smtp_host'] ?? '')),
        'port'       => intval($s['smtp_port'] ?? 587),
        'encryption' => strtolower(trim((string)($s['smtp_encryption'] ?? 'tls'))),
        'username'   => trim((string)($s['smtp_username'] ?? '')),
        'password'   => (string)($s['smtp_password'] ?? ''),
        'from_email' => trim((string)($s['smtp_from_email'] ?? '')),
        'from_name'  => trim((string)($s['smtp_from_name'] ?? 'BountyEarn')),
    ];
}

function smtp_is_configured(array $s): bool {
    $c = smtp_settings_from($s);
    return $c['enabled'] && $c['host'] !== '' && $c['from_email'] !== '';
}

function smtp_read_line($fp): string {
    $data = '';
    while (!feof($fp)) {
        $line = fgets($fp, 515);
        if ($line === false) break;
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return $data;
}

function smtp_cmd($fp, string $cmd, string $expect): array {
    if ($cmd !== '') {
        fwrite($fp, $cmd . "\r\n");
    }
    $resp = smtp_read_line($fp);
    $code = substr($resp, 0, 3);
    if (strpos($expect, $code) === false) {
        return ['ok' => false, 'resp' => trim($resp)];
    }
    return ['ok' => true, 'resp' => trim($resp)];
}

function smtp_send_mail(array $s, string $to, string $subject, string $html, string $text = ''): array {
    $c = smtp_settings_from($s);
    if (!$c['enabled']) {
        return ['success' => false, 'message' => 'SMTP is disabled.'];
    }
    if ($c['host'] === '' || $c['from_email'] === '') {
        return ['success' => false, 'message' => 'SMTP host and from email are required.'];
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid recipient email.'];
    }

    $port = $c['port'] > 0 ? $c['port'] : 587;
    $enc  = $c['encryption'];
    $remote = ($enc === 'ssl' ? 'ssl://' : '') . $c['host'] . ':' . $port;
    $timeout = 20;
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$fp) {
        return ['success' => false, 'message' => 'Could not connect to SMTP server.'];
    }
    stream_set_timeout($fp, $timeout);

    $hello = preg_replace('/[^a-zA-Z0-9.-]/', '', parse_url(defined('BASE_URL') ? BASE_URL : 'http://localhost', PHP_URL_HOST) ?: 'localhost');
    if ($hello === '') $hello = 'localhost';

    $r = smtp_cmd($fp, '', '220');
    if (!$r['ok']) { fclose($fp); return ['success' => false, 'message' => 'SMTP handshake failed.']; }

    $r = smtp_cmd($fp, 'EHLO ' . $hello, '250');
    if (!$r['ok']) {
        $r = smtp_cmd($fp, 'HELO ' . $hello, '250');
        if (!$r['ok']) { fclose($fp); return ['success' => false, 'message' => 'SMTP EHLO failed.']; }
    }

    if ($enc === 'tls') {
        $r = smtp_cmd($fp, 'STARTTLS', '220');
        if (!$r['ok']) { fclose($fp); return ['success' => false, 'message' => 'STARTTLS failed.']; }
        $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        if (!@stream_socket_enable_crypto($fp, true, $crypto)) {
            fclose($fp);
            return ['success' => false, 'message' => 'TLS negotiation failed.'];
        }
        $r = smtp_cmd($fp, 'EHLO ' . $hello, '250');
        if (!$r['ok']) { fclose($fp); return ['success' => false, 'message' => 'SMTP EHLO after TLS failed.']; }
    }

    if ($c['username'] !== '') {
        $r = smtp_cmd($fp, 'AUTH LOGIN', '334');
        if (!$r['ok']) { fclose($fp); return ['success' => false, 'message' => 'SMTP AUTH not accepted.']; }
        $r = smtp_cmd($fp, base64_encode($c['username']), '334');
        if (!$r['ok']) { fclose($fp); return ['success' => false, 'message' => 'SMTP username rejected.']; }
        $r = smtp_cmd($fp, base64_encode($c['password']), '235');
        if (!$r['ok']) { fclose($fp); return ['success' => false, 'message' => 'SMTP authentication failed.']; }
    }

    $from = $c['from_email'];
    $r = smtp_cmd($fp, 'MAIL FROM:<' . $from . '>', '250');
    if (!$r['ok']) { fclose($fp); return ['success' => false, 'message' => 'MAIL FROM rejected.']; }
    $r = smtp_cmd($fp, 'RCPT TO:<' . $to . '>', '250');
    if (!$r['ok']) { fclose($fp); return ['success' => false, 'message' => 'Recipient rejected.']; }
    $r = smtp_cmd($fp, 'DATA', '354');
    if (!$r['ok']) { fclose($fp); return ['success' => false, 'message' => 'DATA not accepted.']; }

    if ($text === '') {
        $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
    }
    $boundary = 'be_' . bin2hex(random_bytes(8));
    $fromName = str_replace(['"', "\r", "\n"], '', $c['from_name']);
    $headers = [];
    $headers[] = 'From: "' . $fromName . '" <' . $from . '>';
    $headers[] = 'To: <' . $to . '>';
    $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Date: ' . date('r');
    $headers[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $hello . '>';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    $body  = implode("\r\n", $headers) . "\r\n\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($text)) . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($html)) . "\r\n";
    $body .= '--' . $boundary . "--\r\n.";

    $r = smtp_cmd($fp, $body, '250');
    smtp_cmd($fp, 'QUIT', '221');
    fclose($fp);
    if (!$r['ok']) {
        return ['success' => false, 'message' => 'Message was rejected by the SMTP server.'];
    }
    return ['success' => true, 'message' => 'Email sent.'];
}

function be_email_wrap(string $site, string $title, string $inner): string {
    $safeSite = htmlspecialchars($site, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#0b0c0e;font-family:Arial,sans-serif;">'
        . '<div style="max-width:560px;margin:24px auto;background:#101114;border:1px solid #27272a;border-radius:12px;overflow:hidden;">'
        . '<div style="padding:20px 24px;border-bottom:1px solid #27272a;color:#34d399;font-size:20px;font-weight:700;">' . $safeSite . '</div>'
        . '<div style="padding:24px;color:#e4e4e7;font-size:15px;line-height:1.6;">'
        . '<h1 style="margin:0 0 12px;font-size:20px;color:#fafafa;">' . $safeTitle . '</h1>'
        . $inner
        . '</div>'
        . '<div style="padding:14px 24px;border-top:1px solid #27272a;color:#71717a;font-size:12px;">This email was sent by ' . $safeSite . '. If you did not request it, you can ignore it.</div>'
        . '</div></body></html>';
}

function otp_create_and_send(PDO $pdo, array $s, string $email, string $purpose): array {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }
    $allowed = ['register', 'reset', 'password_change'];
    if (!in_array($purpose, $allowed, true)) {
        return ['success' => false, 'message' => 'Invalid verification purpose.'];
    }
    if (!smtp_is_configured($s)) {
        return ['success' => false, 'message' => 'Email delivery is not configured yet.'];
    }

    $stmt = $pdo->prepare("SELECT created_at FROM email_otps WHERE email = ? AND purpose = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $purpose]);
    $last = $stmt->fetchColumn();
    if ($last && (time() - strtotime($last)) < 45) {
        return ['success' => false, 'message' => 'Please wait a moment before requesting another code.'];
    }

    $pdo->prepare("UPDATE email_otps SET consumed_at = CURRENT_TIMESTAMP WHERE email = ? AND purpose = ? AND consumed_at IS NULL")->execute([$email, $purpose]);

    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($code, PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO email_otps (email, purpose, code_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
    $ins->execute([$email, $purpose, $hash]);

    $site = trim((string)($s['site_name'] ?? 'BountyEarn'));
    $labels = [
        'register' => 'Verify your BountyEarn account',
        'reset' => 'Reset your BountyEarn password',
        'password_change' => 'Confirm your password change',
    ];
    $title = $labels[$purpose];
    $inner = '<p>Your verification code is:</p>'
        . '<div style="font-size:28px;letter-spacing:8px;font-weight:700;color:#34d399;background:#0b0c0e;border:1px solid #27272a;border-radius:10px;padding:14px 16px;text-align:center;font-family:monospace;">' . htmlspecialchars($code) . '</div>'
        . '<p style="margin-top:16px;color:#a1a1aa;">This code expires in 10 minutes. Do not share it with anyone.</p>';
    $html = be_email_wrap($site, $title, $inner);
    $sent = smtp_send_mail($s, $email, $title, $html, 'Your verification code is ' . $code . '. It expires in 10 minutes.');
    if (!$sent['success']) {
        return $sent;
    }
    return ['success' => true, 'message' => 'Verification code sent to your email.'];
}

function otp_verify_code(PDO $pdo, string $email, string $purpose, string $code): array {
    $email = strtolower(trim($email));
    $code = trim($code);
    if (!preg_match('/^\d{6}$/', $code)) {
        return ['success' => false, 'message' => 'Enter the 6-digit code.'];
    }
    $stmt = $pdo->prepare("SELECT id, code_hash, attempts, expires_at FROM email_otps WHERE email = ? AND purpose = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $purpose]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['success' => false, 'message' => 'No active verification code. Request a new one.'];
    }
    if (strtotime($row['expires_at']) < time()) {
        return ['success' => false, 'message' => 'That code has expired. Request a new one.'];
    }
    if (intval($row['attempts']) >= 5) {
        return ['success' => false, 'message' => 'Too many attempts. Request a new code.'];
    }
    if (!password_verify($code, $row['code_hash'])) {
        $pdo->prepare("UPDATE email_otps SET attempts = attempts + 1 WHERE id = ?")->execute([$row['id']]);
        return ['success' => false, 'message' => 'Invalid verification code.'];
    }
    $pdo->prepare("UPDATE email_otps SET consumed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$row['id']]);
    return ['success' => true, 'message' => 'Verified.'];
}
