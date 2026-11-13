<?php

function get_site_settings(PDO $pdo): array {
    static $cached = null;
    if ($cached !== null) return $cached;

    $defaults = [
        'site_name'                       => 'USDTFaucet',
        'logo_type'                       => 'text',
        'logo_text1'                      => 'USDT',
        'logo_text2'                      => 'Faucet',
        'logo_image'                      => '',
        'social_enabled'                  => '1',
        'social_facebook'                 => 'https://facebook.com',
        'social_instagram'                => 'https://instagram.com',
        'social_twitter'                  => 'https://twitter.com',
        'social_telegram'                 => 'https://telegram.org',
        'daily_bonus_enabled'             => '1',
        'video_enabled'                   => '0',
        'ptc_enabled'                     => '0',
        'shortlinks_enabled'              => '0',
        'landing_ads_enabled'             => '0',
        'floating_ads_landing_enabled'    => '0',
        'floating_ads_dashboard_enabled'  => '0',
        'dashboard_ads_enabled'           => '0',
        'offerwallapi_api_key'            => '',
        'offerwallapi_bearer_token'       => '',
        'offerwallapi_secret_key'         => '',
        'offerwallapi_ptc_enabled'        => '1',
        'offerwallapi_video_enabled'      => '0',
        'offerwallapi_shortlinks_enabled' => '1',
        'offerwallapi_tasks_enabled'      => '0',
        'offerwall_api_key'               => '',
        'offerwall_secret_key'            => '',
        'offerwall_token'                 => '',
        'offerwall_enabled'               => '0',
        'offerwall_ptc_enabled'           => '0',
        'offerwall_shortlinks_enabled'    => '0',
        'offerwall_tasks_enabled'         => '0',
        'ref_percent_offerwall'           => '5',
        'level_bonus_offerwall'           => '5',
        'ou_enabled'                      => '1',
        'ou_app_id'                       => 'ID_d1c94cda94189d2d3b41633b7289c70c',
        'ou_uid'                          => '4157',
        'ou_secret_key'                   => '',
        'ou_source_id'                    => 'offerwall',
        'ou_ip_whitelist'                 => '147.79.123.200',
        'faucetpay_api_key'               => ''
    ];

    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $defaults[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
    }

    $cached = $defaults;
    return $cached;
}

function offerwall_page_enabled(array $s): bool {
    return (isset($s['offerwall_enabled']) && $s['offerwall_enabled'] == '1')
        || (isset($s['ou_enabled']) && $s['ou_enabled'] == '1');
}

function render_logo(array $s, string $href = '/', string $imgClass = 'w-8 h-8 object-contain', string $textClass = 'text-xl font-bold tracking-tight'): string {
    $name1 = htmlspecialchars($s['logo_text1']);
    $name2 = htmlspecialchars($s['logo_text2']);
    $siteName = htmlspecialchars($s['site_name']);

    if ($s['logo_type'] === 'image' && !empty($s['logo_image'])) {
        $src = htmlspecialchars($s['logo_image']);
        return '<a href="' . $href . '" class="flex items-center gap-2 group">
            <img src="' . $src . '" alt="' . $siteName . ' Logo" class="' . $imgClass . '">
        </a>';
    }

    return '<a href="' . $href . '" class="flex items-center gap-2 group">
        <span class="' . $textClass . ' text-zinc-100">' . $name1 . '<span class="text-primary">' . $name2 . '</span></span>
    </a>';
}
