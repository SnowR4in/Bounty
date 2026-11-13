<?php

if (!defined('OFFERWALLAPI_BASE')) {
    define('OFFERWALLAPI_BASE', 'https://offerwallapi.com');
}

if (!function_exists('owapi_http_get')) {
    function owapi_http_get(string $path, string $bearer): ?array {
        $ch = curl_init(OFFERWALLAPI_BASE . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $bearer,
                'User-UA: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) return null;
        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }
}

if (!function_exists('owapi_fetch_feed')) {
    function owapi_fetch_feed(string $category, array $settings, int $user_id, string $user_ip): array {
        $api_key = trim($settings['offerwallapi_api_key'] ?? '');
        $bearer  = trim($settings['offerwallapi_bearer_token'] ?? '');
        $result  = ['items' => [], 'approved' => []];

        if ($api_key === '' || $bearer === '') return $result;

        $pathMap = ['ptc' => 'ptc-api', 'video' => 'video-api', 'sl' => 'sl-api', 'task' => 'task-api'];
        if (!isset($pathMap[$category])) return $result;

        $data = owapi_http_get('/' . $pathMap[$category] . '/' . urlencode($api_key) . '/' . urlencode($user_id) . '/' . urlencode($user_ip), $bearer);
        if (!$data || (string)($data['status'] ?? '') !== '200' || empty($data['data'])) return $result;

        foreach ($data['data'] as $item) {
            $item['provider']    = 'OfferwallApi';
            $item['reward_usdt'] = (float)($item['reward'] ?? 0);
            if ($category === 'task') {
                $item['reward_usdt'] = (float)($item['reward_usd'] ?? $item['reward'] ?? 0);
            }
            $result['items'][] = $item;
        }

        if ($category === 'task' && !empty($data['approved']) && is_array($data['approved'])) {
            $result['approved'] = $data['approved'];
        }
        return $result;
    }
}

if (!function_exists('owapi_submit_task')) {
    function owapi_submit_task(array $settings, int $user_id, string $user_ip, string $taskId, string $proof): array {
        $api_key = trim($settings['offerwallapi_api_key'] ?? '');
        $bearer  = trim($settings['offerwallapi_bearer_token'] ?? '');
        if ($api_key === '' || $bearer === '') {
            return ['success' => false, 'message' => 'OfferwallApi is not configured.'];
        }

        $payload = json_encode(['taskId' => $taskId, 'proof' => $proof]);
        $ch = curl_init(OFFERWALLAPI_BASE . '/task-api/' . urlencode($api_key) . '/' . urlencode($user_id) . '/' . urlencode($user_ip));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $bearer,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => $payload,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return ['success' => false, 'message' => 'Connection to OfferwallApi failed. Try again.'];
        $data = json_decode($response, true);
        if (is_array($data) && (string)($data['status'] ?? '') === '200') {
            return ['success' => true, 'message' => 'Submitted for review.'];
        }
        return ['success' => false, 'message' => (string)($data['message'] ?? 'Submission failed.')];
    }
}
