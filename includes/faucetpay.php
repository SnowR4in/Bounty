<?php

if (!defined('FAUCETPAY_API_BASE')) {
    define('FAUCETPAY_API_BASE', 'https://faucetpay.io/api/v1');
}

if (!defined('FAUCETPAY_CURRENCY')) {
    define('FAUCETPAY_CURRENCY', 'USDT');
}

if (!defined('FAUCETPAY_DECIMALS')) {
    define('FAUCETPAY_DECIMALS', 100000000);
}

if (!function_exists('faucetpay_post')) {
    function faucetpay_post(string $method, array $fields, string $api_key): array {
        $fields['api_key'] = $api_key;
        $ch = curl_init(FAUCETPAY_API_BASE . $method);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
        ]);
        $response = curl_exec($ch);
        $http     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        return [
            'http' => $err ? 0 : $http,
            'body' => $response ? (json_decode($response, true) ?: null) : null,
        ];
    }
}

if (!function_exists('faucetpay_send')) {
    function faucetpay_send(string $api_key, string $to_email, float $amount_usdt, string $ip_address = ''): array {
        if ($api_key === '' || $to_email === '' || $amount_usdt <= 0) {
            return ['success' => false, 'message' => 'Invalid payout request.'];
        }

        $amount_units = (string)intval(round($amount_usdt * FAUCETPAY_DECIMALS));
        if ($amount_units === '0') {
            return ['success' => false, 'message' => 'Amount is too small.'];
        }

        $fields = [
            'amount'   => $amount_units,
            'to'       => $to_email,
            'currency' => FAUCETPAY_CURRENCY,
        ];
        if ($ip_address !== '') {
            $fields['ip_address'] = $ip_address;
        }

        $res  = faucetpay_post('/send', $fields, $api_key);
        $body = $res['body'] ?? [];
        $code = (int)($body['status'] ?? 0);

        if ($code === 200) {
            return [
                'success'   => true,
                'message'   => (string)($body['message'] ?? 'Payout completed successfully!'),
                'payout_id' => (string)($body['payout_id'] ?? ''),
                'balance'   => isset($body['balance_bitcoin']) ? (float)$body['balance_bitcoin'] : null,
            ];
        }

        $messages = [
            401 => 'Payout failed: server IP is not whitelisted in FaucetPay (or account suspended).',
            402 => 'FaucetPay wallet has insufficient balance.',
            403 => 'Payout failed: invalid FaucetPay API key.',
            405 => 'Payout failed: invalid amount.',
            410 => 'Payout failed: invalid currency.',
            450 => 'Payout failed: faucet send limit reached. Try again later.',
            456 => 'Recipient is not a valid FaucetPay user.',
            457 => 'Recipient refused by FaucetPay anti-fraud rules.',
            459 => 'Rate limited by FaucetPay. Try again later.',
        ];
        return [
            'success'   => false,
            'message'   => $messages[$code] ?? ('Payout failed: ' . (string)($body['message'] ?? 'unknown error')),
            'payout_id' => null,
            'balance'   => null,
        ];
    }
}

if (!function_exists('faucetpay_check_address')) {
    function faucetpay_check_address(string $api_key, string $email): array {
        if ($api_key === '' || $email === '') {
            return ['success' => false, 'message' => 'Missing API key or email.'];
        }
        $res  = faucetpay_post('/checkaddress', ['address' => $email, 'currency' => FAUCETPAY_CURRENCY], $api_key);
        $code = (int)($res['body']['status'] ?? 0);
        return [
            'success' => $code === 200,
            'message' => (string)($res['body']['message'] ?? 'Address check failed.'),
        ];
    }
}

if (!function_exists('faucetpay_get_balance')) {
    function faucetpay_get_balance(string $api_key): array {
        $res  = faucetpay_post('/getbalance', ['currency' => FAUCETPAY_CURRENCY], $api_key);
        $code = (int)($res['body']['status'] ?? 0);
        return [
            'success' => $code === 200,
            'balance' => isset($res['body']['balance_bitcoin']) ? (float)$res['body']['balance_bitcoin'] : 0.0,
            'message' => (string)($res['body']['message'] ?? 'Balance check failed.'),
        ];
    }
}
