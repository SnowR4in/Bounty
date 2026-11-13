<?php

if (!defined('OFFERWALLME_BASE')) {
    define('OFFERWALLME_BASE', 'https://offerwall.me');
}

if (!function_exists('owme_user_country')) {
    function owme_user_country(): string {
        $code = strtoupper(trim($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));
        return (strlen($code) === 2 && ctype_alpha($code)) ? $code : 'US';
    }
}

if (!function_exists('owme_identity_params')) {
    function owme_identity_params(string $public_key, string $user_id, string $secret): array {
        $expires = (string)(time() + 3600);
        $message = "offerwall-user-v1\n" . $public_key . "\n" . $user_id . "\n" . $expires;
        return [
            'identityExpires'   => $expires,
            'identitySignature' => hash_hmac('sha256', $message, $secret),
        ];
    }
}

if (!function_exists('owme_signed_url')) {
    function owme_signed_url(string $public_key, string $user_id, string $secret): string {
        $identity = owme_identity_params($public_key, $user_id, $secret);
        return OFFERWALLME_BASE . '/offerwall/' . rawurlencode($public_key)
            . '/' . rawurlencode($user_id) . '?' . http_build_query([
                'identityExpires'   => $identity['identityExpires'],
                'identitySignature' => $identity['identitySignature'],
            ], '', '&', PHP_QUERY_RFC3986);
    }
}

if (!function_exists('owme_fetch_list')) {
    function owme_fetch_list(string $endpoint, array $settings, string $user_id, string $user_ip, string $country = ''): array {
        $api    = trim((string)($settings['offerwall_api_key'] ?? ''));
        $token  = trim((string)($settings['offerwall_token'] ?? ''));
        $secret = trim((string)($settings['offerwall_secret_key'] ?? ''));
        if ($api === '' || $token === '') return [];

        if ($country === '') $country = owme_user_country();
        $identity = owme_identity_params($api, $user_id, $secret);

        $url = OFFERWALLME_BASE . '/' . ltrim($endpoint, '/') . '?' . http_build_query([
            'api'               => $api,
            'id'                => $user_id,
            'ip'                => $user_ip,
            'token'             => $token,
            'country'           => $country,
            'identityExpires'   => $identity['identityExpires'],
            'identitySignature' => $identity['identitySignature'],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['User-Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'USDTFaucet')],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return [];
        $data = json_decode($response, true);
        if (!is_array($data) || (string)($data['status'] ?? '') !== '200' || empty($data['data']) || !is_array($data['data'])) return [];

        $items = [];
        foreach ($data['data'] as $t) {
            if (!is_array($t)) continue;
            $t['provider'] = 'Offerwall.me';
            if (isset($t['reward'])) $t['reward_usdt'] = (float)$t['reward'];
            if (isset($t['proof_type'])) $t['proofType'] = strtoupper((string)$t['proof_type']);
            if (isset($t['proof_text'])) $t['proofHint'] = (string)$t['proof_text'];
            if (isset($t['instructions'])) $t['instructions'] = trim(preg_replace('/\s+/', ' ', strip_tags((string)$t['instructions'])));
            if (isset($t['url'])) $t['targetUrl'] = (string)$t['url'];
            $items[] = $t;
        }
        return $items;
    }
}

if (!function_exists('owme_submit_task')) {
    function owme_submit_task(array $settings, string $user_id, string $user_ip, string $task_id, string $proof): array {
        $api    = trim((string)($settings['offerwall_api_key'] ?? ''));
        $token  = trim((string)($settings['offerwall_token'] ?? ''));
        $secret = trim((string)($settings['offerwall_secret_key'] ?? ''));
        if ($api === '' || $token === '') {
            return ['success' => false, 'message' => 'Offerwall.me is not configured.'];
        }

        $identity = owme_identity_params($api, $user_id, $secret);
        $payload = json_encode([
            'api'               => $api,
            'token'             => $token,
            'id'                => $user_id,
            'identityExpires'   => $identity['identityExpires'],
            'identitySignature' => $identity['identitySignature'],
            'country'           => owme_user_country(),
            'task_id'           => $task_id,
            'proof'             => $proof,
        ]);

        $ch = curl_init(OFFERWALLME_BASE . '/tasksubmit.php');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $payload,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return ['success' => false, 'message' => 'Connection to Offerwall.me failed. Try again.'];
        $data = json_decode($response, true);
        if (is_array($data)) {
            $ok  = ((string)($data['status'] ?? '') === '200') || !empty($data['success']);
            $msg = (string)($data['message'] ?? '');
            return ['success' => $ok, 'message' => $msg !== '' ? $msg : ($ok ? 'Submitted.' : 'Submission failed.')];
        }
        return ['success' => false, 'message' => 'Unexpected response from Offerwall.me.'];
    }
}
