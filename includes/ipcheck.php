<?php

if (!function_exists('proxycheck_query')) {
    function proxycheck_query(string $ip, array $settings): ?array {
        $key = trim($settings['proxycheck_key'] ?? '');
        $url = 'https://proxycheck.io/v2/' . urlencode($ip) . '?vpn=1&asn=1&risk=1';
        if ($key !== '') {
            $url .= '&key=' . urlencode($key);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) return null;
        $data = json_decode($res, true);
        if (!is_array($data) || !isset($data[$ip]) || !is_array($data[$ip])) return null;

        $node = $data[$ip];
        return [
            'proxy'   => (isset($node['proxy']) && $node['proxy'] === 'yes'),
            'type'    => (string)($node['type'] ?? ''),
            'country' => isset($data['country']) ? (string)$data['country'] : (string)($node['country'] ?? ''),
            'asn'     => (string)($node['asn'] ?? ''),
        ];
    }
}

if (!function_exists('ipcheck_is_vpn')) {
    function ipcheck_is_vpn(string $ip, array $settings): array {
        if (($settings['proxycheck_enabled'] ?? '0') !== '1') {
            return ['blocked' => false, 'info' => null];
        }
        $info = proxycheck_query($ip, $settings);
        return ['blocked' => (bool)($info['proxy'] ?? false), 'info' => $info];
    }
}
