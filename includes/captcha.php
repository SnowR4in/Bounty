<?php

if (!function_exists('captcha_render')) {
    function captcha_render(string $type, array $settings): string {
        if ($type === 'google'
            && ($settings['recaptcha_enabled'] ?? '0') === '1'
            && !empty($settings['recaptcha_site_key'])) {
            $site = htmlspecialchars($settings['recaptcha_site_key']);
            return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>'
                 . '<div class="flex justify-center my-3"><div class="g-recaptcha" data-sitekey="' . $site . '"></div></div>';
        }

        if ($type === 'turnstile'
            && ($settings['turnstile_enabled'] ?? '0') === '1'
            && !empty($settings['turnstile_site_key'])) {
            $site = htmlspecialchars($settings['turnstile_site_key']);
            return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>'
                 . '<div class="flex justify-center my-3"><div class="cf-turnstile" data-sitekey="' . $site . '"></div></div>';
        }

        return '';
    }
}

if (!function_exists('captcha_field_name')) {
    function captcha_field_name(string $type): string {
        return $type === 'google' ? 'g-recaptcha-response' : 'cf-turnstile-response';
    }
}

if (!function_exists('captcha_verify')) {
    function captcha_verify(string $type, array $settings): bool {
        $response = $_POST[captcha_field_name($type)] ?? '';

        if ($type === 'google') {
            if (($settings['recaptcha_enabled'] ?? '0') !== '1' || empty($settings['recaptcha_secret_key'])) return true;
            if (empty($response)) return false;
            $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'secret'   => $settings['recaptcha_secret_key'],
                    'response' => $response,
                    'remoteip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
                ]),
            ]);
            $res = curl_exec($ch);
            curl_close($ch);
            $data = $res ? json_decode($res, true) : null;
            return !empty($data['success']);
        }

        if ($type === 'turnstile') {
            if (($settings['turnstile_enabled'] ?? '0') !== '1' || empty($settings['turnstile_secret_key'])) return true;
            if (empty($response)) return false;
            $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'secret'   => $settings['turnstile_secret_key'],
                    'response' => $response,
                    'remoteip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
                ]),
            ]);
            $res = curl_exec($ch);
            curl_close($ch);
            $data = $res ? json_decode($res, true) : null;
            return !empty($data['success']);
        }

        return true;
    }
}

if (!function_exists('captcha_error_message')) {
    function captcha_error_message(string $type): string {
        return $type === 'google'
            ? 'Captcha verification failed. Please solve the reCAPTCHA and try again.'
            : 'Captcha verification failed. Please try again.';
    }
}
