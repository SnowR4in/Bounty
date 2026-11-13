<?php

function be_schema_migrate(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('faucetpay_email', $cols, true) && !in_array('email', $cols, true)) {
            $pdo->exec("ALTER TABLE users CHANGE faucetpay_email email varchar(255) NOT NULL");
            $pdo->exec("ALTER TABLE users DROP INDEX idx_faucetpay_email");
            $pdo->exec("ALTER TABLE users ADD UNIQUE KEY idx_email (email)");
            $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        }
        if (!in_array('usdt_address', $cols, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN usdt_address varchar(128) DEFAULT NULL AFTER email");
        }
        if (!in_array('usdt_network', $cols, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN usdt_network varchar(20) DEFAULT NULL AFTER usdt_address");
        }

        $wcols = $pdo->query("SHOW COLUMNS FROM withdrawals")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('faucetpay_email', $wcols, true) && !in_array('usdt_address', $wcols, true)) {
            $pdo->exec("ALTER TABLE withdrawals CHANGE faucetpay_email usdt_address varchar(255) NOT NULL");
            $wcols = $pdo->query("SHOW COLUMNS FROM withdrawals")->fetchAll(PDO::FETCH_COLUMN);
        }
        if (!in_array('network', $wcols, true)) {
            $pdo->exec("ALTER TABLE withdrawals ADD COLUMN network varchar(20) NOT NULL DEFAULT 'bnb' AFTER usdt_address");
        }

        $keys = [
            'offerwall_enabled' => '1',
            'site_name' => 'BountyEarn',
            'logo_text1' => 'Bounty',
            'logo_text2' => 'Earn',
        ];
        $ins = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($keys as $k => $v) {
            $ins->execute([$k, $v]);
        }
    } catch (Exception $e) {
    }
}
