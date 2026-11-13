<?php

if (!defined('OU_API_BASE')) {
    define('OU_API_BASE', 'https://api.opinionuniverse.com');
}

if (!defined('OU_DEFAULT_APP_ID')) {
    define('OU_DEFAULT_APP_ID', 'ID_d1c94cda94189d2d3b41633b7289c70c');
}

if (!defined('OU_DEFAULT_UID')) {
    define('OU_DEFAULT_UID', '4157');
}

if (!defined('OU_POSTBACK_IP')) {
    define('OU_POSTBACK_IP', '147.79.123.200');
}

if (!defined('OU_POSTBACK_IPS')) {
    define('OU_POSTBACK_IPS', "147.79.123.200\n147.182.172.173");
}

if (!function_exists('ou_survey_walls')) {
    function ou_survey_walls(): array {
        return [
            'dynata' => [
                'slug'        => 'dynata',
                'name'        => 'Dynata',
                'offer_id'    => '3063128',
                'description' => 'Complete professional surveys from Dynata and earn USDT.',
                'icon'        => 'fa-clipboard-list',
            ],
            'bitlab' => [
                'slug'        => 'bitlab',
                'name'        => 'Bitlab',
                'offer_id'    => '6301974',
                'description' => 'Take Bitlab surveys matched to your profile and get credited automatically.',
                'icon'        => 'fa-flask',
            ],
            'cpx' => [
                'slug'        => 'cpx',
                'name'        => 'CPX Research',
                'offer_id'    => '4314257',
                'description' => 'Answer CPX Research surveys. Rewards are sent via secure postback.',
                'icon'        => 'fa-comments',
            ],
        ];
    }
}

if (!function_exists('ou_app_id')) {
    function ou_app_id(array $settings): string {
        $id = trim((string)($settings['ou_app_id'] ?? ''));
        return $id !== '' ? $id : OU_DEFAULT_APP_ID;
    }
}

if (!function_exists('ou_publisher_uid')) {
    function ou_publisher_uid(array $settings): string {
        $uid = trim((string)($settings['ou_uid'] ?? ''));
        return $uid !== '' ? $uid : OU_DEFAULT_UID;
    }
}

if (!function_exists('ou_is_enabled')) {
    function ou_is_enabled(array $settings): bool {
        return isset($settings['ou_enabled']) && $settings['ou_enabled'] == '1';
    }
}

if (!function_exists('ou_click_id')) {
    function ou_click_id(int $user_id): string {
        return $user_id . '_' . bin2hex(random_bytes(8));
    }
}

if (!function_exists('ou_user_id_from_sid')) {
    function ou_user_id_from_sid(string $sid): int {
        $sid = trim($sid);
        if ($sid !== '' && ctype_digit($sid)) {
            return (int)$sid;
        }
        if (preg_match('/^(\d+)_/', $sid, $m)) {
            return (int)$m[1];
        }
        return 0;
    }
}

if (!function_exists('ou_click_url')) {
    function ou_click_url(array $settings, string $offer_id, int $user_id, string $sid, string $sid2 = ''): string {
        $params = [
            'app_id'   => ou_app_id($settings),
            'offer_id' => $offer_id,
            'uid'      => ou_publisher_uid($settings),
            'sid'      => $sid !== '' ? $sid : (string)$user_id,
        ];
        $source = $sid2 !== '' ? $sid2 : trim((string)($settings['ou_source_id'] ?? ''));
        if ($source !== '') {
            $params['sid2'] = $source;
        }
        return OU_API_BASE . '/offerwall/clickTrackingLink?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}

if (!function_exists('ou_walls_for_user')) {
    function ou_walls_for_user(array $settings, int $user_id): array {
        $source = trim((string)($settings['ou_source_id'] ?? 'offerwall'));
        $walls = [];
        foreach (ou_survey_walls() as $wall) {
            if (!ou_wall_enabled($settings, $wall['slug'])) {
                continue;
            }
            $sid = ou_click_id($user_id);
            $sid2 = $source !== '' ? $source : $wall['slug'];
            $wall['sid'] = $sid;
            $wall['url'] = ou_click_url($settings, $wall['offer_id'], $user_id, $sid, $sid2);
            $walls[] = $wall;
        }
        return $walls;
    }
}

if (!function_exists('ou_verify_sig')) {
    function ou_verify_sig(string $secret, string $sid, string $offer_id, string $payout, string $status, string $sig): bool {
        if ($secret === '' || $sig === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $sid . $offer_id . $payout . $status, $secret);
        return hash_equals($expected, strtolower($sig)) || hash_equals($expected, $sig);
    }
}


/* ------------------------------------------------------------------ */
/* Per-feature enable/disable toggles                                  */
/* ------------------------------------------------------------------ */

if (!function_exists('ou_wall_enabled')) {
    // Default ON so existing installs keep working until admin turns one off.
    function ou_wall_enabled(array $settings, string $slug): bool {
        $k = 'ou_wall_' . $slug . '_enabled';
        return !isset($settings[$k]) || $settings[$k] === '' || $settings[$k] == '1';
    }
}

if (!function_exists('ou_feature_enabled')) {
    // $feature: 'offers' (admin-managed offer list) or 'live' (live survey section)
    function ou_feature_enabled(array $settings, string $feature): bool {
        $k = 'ou_' . $feature . '_enabled';
        return !isset($settings[$k]) || $settings[$k] === '' || $settings[$k] == '1';
    }
}

/* ------------------------------------------------------------------ */
/* Admin-managed offers table                                          */
/* ------------------------------------------------------------------ */

if (!function_exists('ou_ensure_offers_table')) {
    function ou_ensure_offers_table(PDO $pdo): void {
        static $done = false;
        if ($done) return;
        $pdo->exec("CREATE TABLE IF NOT EXISTS `ou_offers` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `provider` varchar(32) NOT NULL DEFAULT 'other',
          `offer_id` varchar(64) NOT NULL,
          `title` varchar(255) NOT NULL,
          `description` varchar(500) DEFAULT NULL,
          `reward` decimal(16,6) DEFAULT NULL,
          `reward_type` enum('fixed','up_to','varies') NOT NULL DEFAULT 'fixed',
          `loi` int(11) DEFAULT NULL,
          `image_url` varchar(500) DEFAULT NULL,
          `custom_url` varchar(1000) DEFAULT NULL,
          `is_live` tinyint(1) NOT NULL DEFAULT 0,
          `enabled` tinyint(1) NOT NULL DEFAULT 1,
          `sort_order` int(11) NOT NULL DEFAULT 0,
          `raw_json` mediumtext DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_provider_offer` (`provider`, `offer_id`),
          KEY `idx_enabled` (`enabled`, `sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
            ('ou_wall_dynata_enabled','1'),('ou_wall_bitlab_enabled','1'),('ou_wall_cpx_enabled','1'),
            ('ou_offers_enabled','1'),('ou_live_enabled','1')");
        $done = true;
    }
}

if (!function_exists('ou_pick')) {
    function ou_pick(array $row, array $keys, $default = null) {
        $lower = [];
        foreach ($row as $k => $v) $lower[strtolower((string)$k)] = $v;
        foreach ($keys as $k) {
            $k = strtolower($k);
            if (isset($lower[$k]) && $lower[$k] !== '' && !is_array($lower[$k])) return $lower[$k];
        }
        return $default;
    }
}

if (!function_exists('ou_guess_provider')) {
    function ou_guess_provider(string $hay): string {
        $h = strtolower($hay);
        if (strpos($h, 'cpx') !== false) return 'cpx';
        if (strpos($h, 'bitlab') !== false) return 'bitlab';
        if (strpos($h, 'dynata') !== false) return 'dynata';
        return 'other';
    }
}

if (!function_exists('ou_parse_offers_payload')) {
    /**
     * Accepts whatever the admin pastes: a JSON array, a single JSON object,
     * or an API response like {"offers":[...]} / {"data":[...]} / {"surveys":[...]}.
     * Returns [offers[], error|null]. Field names are matched loosely so the
     * OpinionUniverse API shape does not have to be exact.
     */
    function ou_parse_offers_payload(string $text, string $default_provider = ''): array {
        $text = trim($text);
        if ($text === '') return [[], 'Nothing pasted.'];
        $data = json_decode($text, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return [[], 'Invalid JSON: ' . json_last_error_msg()];
        }
        if (!is_array($data)) return [[], 'JSON must be an object or array.'];

        // unwrap common envelopes
        foreach (['offers', 'data', 'surveys', 'results', 'items', 'offerwall'] as $wrap) {
            if (isset($data[$wrap]) && is_array($data[$wrap])) { $data = $data[$wrap]; break; }
        }
        // single object -> list of one
        if (array_keys($data) !== range(0, count($data) - 1)) $data = [$data];

        $out = [];
        foreach ($data as $row) {
            if (!is_array($row)) continue;
            $oid = ou_pick($row, ['offer_id', 'offerid', 'id', 'survey_id', 'surveyid', 'uid']);
            $title = ou_pick($row, ['title', 'name', 'offer_name', 'offername', 'survey_name']);
            if ($oid === null || $title === null) continue;
            $payout = ou_pick($row, ['reward', 'pubpayout', 'pub_payout', 'payout', 'amount', 'payout_usd', 'cpi', 'value']);
            $type = 'fixed';
            $reward = null;
            if ($payout === null) {
                $type = 'varies';
            } else {
                $reward = (float)preg_replace('/[^0-9.\-]/', '', (string)$payout);
                if ($reward <= 0) { $reward = null; $type = 'varies'; }
            }
            $rt = strtolower((string)ou_pick($row, ['reward_type', 'payout_type'], ''));
            if (in_array($rt, ['up_to', 'upto', 'max'], true)) $type = 'up_to';
            $provider = strtolower((string)ou_pick($row, ['provider', 'network', 'type', 'source'], ''));
            $provider = $provider !== '' ? ou_guess_provider($provider) : '';
            if ($provider === '' || $provider === 'other') {
                $provider = $default_provider !== '' ? $default_provider : ou_guess_provider((string)$title);
            }
            $live = ou_pick($row, ['is_live', 'live'], 0);
            $out[] = [
                'provider'    => substr($provider, 0, 32),
                'offer_id'    => substr((string)$oid, 0, 64),
                'title'       => substr((string)$title, 0, 255),
                'description' => substr((string)ou_pick($row, ['description', 'desc', 'details'], ''), 0, 500),
                'reward'      => $reward,
                'reward_type' => $type,
                'loi'         => (int)ou_pick($row, ['loi', 'length', 'duration', 'minutes', 'time'], 0),
                'image_url'   => (string)ou_pick($row, ['image', 'image_url', 'icon', 'logo', 'thumbnail'], ''),
                'custom_url'  => (string)ou_pick($row, ['url', 'link', 'click_url', 'clickurl', 'tracking_url'], ''),
                'is_live'     => ($live === true || $live === 1 || $live === '1' || $live === 'true') ? 1 : 0,
                'raw_json'    => json_encode($row, JSON_UNESCAPED_SLASHES),
            ];
        }
        if (!$out) return [[], 'No offers found. Each item needs at least an id and a title/name.'];
        return [$out, null];
    }
}

if (!function_exists('ou_safe_http_url')) {
    function ou_safe_http_url(string $u): string {
        $u = trim($u);
        return preg_match('#^https://#i', $u) ? $u : '';
    }
}

if (!function_exists('ou_import_offers')) {
    // New offers land DISABLED so nothing goes live until the admin flips the switch.
    function ou_import_offers(PDO $pdo, array $offers, bool $enable_new = false): array {
        ou_ensure_offers_table($pdo);
        $ins = $pdo->prepare("INSERT INTO ou_offers
            (provider, offer_id, title, description, reward, reward_type, loi, image_url, custom_url, is_live, enabled, raw_json)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), reward=VALUES(reward),
              reward_type=VALUES(reward_type), loi=VALUES(loi), image_url=VALUES(image_url),
              custom_url=VALUES(custom_url), is_live=VALUES(is_live), raw_json=VALUES(raw_json)");
        $added = 0; $updated = 0;
        foreach ($offers as $o) {
            $ins->execute([$o['provider'], $o['offer_id'], $o['title'], $o['description'], $o['reward'],
                $o['reward_type'], $o['loi'] ?: null, ou_safe_http_url($o['image_url']) ?: null,
                ou_safe_http_url($o['custom_url']) ?: null, $o['is_live'], $enable_new ? 1 : 0, $o['raw_json']]);
            $rc = $ins->rowCount();
            if ($rc === 1) $added++; elseif ($rc >= 2) $updated++;
        }
        return ['added' => $added, 'updated' => $updated];
    }
}

if (!function_exists('ou_enabled_offers')) {
    function ou_enabled_offers(PDO $pdo, array $settings, bool $live_only = false): array {
        ou_ensure_offers_table($pdo);
        $sql = "SELECT * FROM ou_offers WHERE enabled = 1 AND is_live = " . ($live_only ? '1' : '0') . " ORDER BY sort_order ASC, reward DESC, id DESC";
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            if (!ou_wall_enabled($settings, $r['provider']) && isset(ou_survey_walls()[$r['provider']])) continue;
            $out[] = $r;
        }
        return $out;
    }
}

if (!function_exists('ou_offer_url')) {
    function ou_offer_url(array $settings, array $offer, int $user_id): string {
        $sid = ou_click_id($user_id);
        $sid2 = trim((string)($settings['ou_source_id'] ?? 'offerwall')) ?: $offer['provider'];
        // Always route through the click-tracking link so the sid reaches the postback.
        return ou_click_url($settings, (string)$offer['offer_id'], $user_id, $sid, $sid2);
    }
}

if (!function_exists('ou_reward_label')) {
    // Never prints "$0" — an unknown payout is shown as "Varies" because dynamic
    // survey walls only report the real amount in the completion postback.
    function ou_reward_label($reward, string $type = 'fixed'): string {
        $r = (float)$reward;
        if ($type === 'varies' || $r <= 0) return 'Varies';
        $s = rtrim(rtrim(number_format($r, 6, '.', ''), '0'), '.');
        return ($type === 'up_to' ? 'Up to ' : '+') . $s . ' USDT';
    }
}
