-- Table structure for admin_users
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_username` varchar(50) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_admin_username` (`admin_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (admin / admin)
INSERT IGNORE INTO `admin_users` (`admin_username`, `admin_password`) VALUES
('admin', '$2a$12$Uoo.9wphhQ.k1tnBGc8O..oj3ahW7BMqv154Okchcb5gUSiKM7Y2C');

-- Table structure for settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert all default settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('timed_faucet_enabled', '1'),
('timed_faucet_cooldown', '300'),
('timed_faucet_reward', '0.00200000'),
('timed_faucet_energy', '5'),
('timed_faucet_daily_limit', '100'),
('instant_faucet_enabled', '1'),
('instant_faucet_cooldown', '10'),
('instant_faucet_reward', '0.00050000'),
('instant_faucet_energy', '1'),
('instant_faucet_daily_limit', '200'),
('proxycheck_enabled', '0'),
('proxycheck_key', ''),
('recaptcha_enabled', '0'),
('recaptcha_site_key', ''),
('recaptcha_secret_key', ''),
('timed_faucet_captcha', 'none'),
('instant_faucet_captcha', 'none'),
('referrals_enabled', '1'),
('ref_percent_faucet', '10'),
('ref_percent_ptc', '5'),
('ref_percent_shortlinks', '5'),
('ref_percent_tasks', '0'),
('ref_percent_video', '10'),
('ref_percent_deposits', '0'),
('min_withdrawal', '0.10000000'),
('instant_withdrawals_enabled', '1'),
('faucetpay_api_key', ''),
('site_name', 'OfferwallApi'),
('logo_type', 'text'),
('logo_text1', 'Offerwall'),
('logo_text2', 'Api'),
('logo_image', ''),
('social_enabled', '1'),
('social_facebook', 'https://facebook.com'),
('social_instagram', 'https://instagram.com'),
('social_twitter', 'https://twitter.com'),
('social_telegram', 'https://t.me/offerwallapi'),
('daily_bonus_enabled', '1'),
('video_enabled', '0'),
('ptc_enabled', '0'),
('shortlinks_enabled', '0'),
('landing_ads_enabled', '0'),
('floating_ads_landing_enabled', '0'),
('floating_ads_dashboard_enabled', '0'),
('dashboard_ads_enabled', '0'),
('level_system_enabled', '1'),
('level_bonus_timed', '2'),
('level_bonus_instant', '1'),
('level_bonus_ptc', '5'),
('level_bonus_shortlink', '5'),
('level_bonus_video', '[{"amount":0.5,"percent":20},{"amount":2.0,"percent":40},{"amount":100.0,"percent":75}]'),
('level_bonus_tasks', '[{"amount":0.1,"percent":10},{"amount":1.0,"percent":25},{"amount":100.0,"percent":50}]'),
('timed_faucet_min_level', '0'),
('instant_faucet_min_level', '0'),
('wheel_enabled', '0'),
('wheel_energy_cost', '10'),
('wheel_captcha', 'none'),
('wheel_ads_enabled', '0'),
('faucet_ads_enabled', '0'),
('offerwallapi_ptc_enabled', '1'),
('offerwallapi_api_key', ''),
('offerwallapi_bearer_token', ''),
('offerwallapi_secret_key', ''),
('offerwallapi_ip_whitelist', '95.211.62.33\n2001:1af8:5301:10a:1c00:28ff:fe00:895'),
('offerwallapi_video_enabled', '0'),
('offerwallapi_shortlinks_enabled', '1'),
('offerwallapi_tasks_enabled', '0'),
 ('offerwall_ptc_enabled', '0'),
 ('offerwall_api_key', ''),
 ('offerwall_secret_key', ''),
 ('offerwall_token', ''),
 ('offerwall_ip_whitelist', '95.216.65.163\n2a01:4f9:2b:1dc::2'),
 ('offerwall_shortlinks_enabled', '0'),
 ('offerwall_tasks_enabled', '0'),
 ('offerwall_enabled', '0'),
 ('ref_percent_offerwall', '5'),
 ('level_bonus_offerwall', '5'),
 ('ou_enabled', '1'),
 ('ou_app_id', 'ID_d1c94cda94189d2d3b41633b7289c70c'),
 ('ou_uid', '4157'),
 ('ou_secret_key', ''),
 ('ou_source_id', 'offerwall'),
 ('ou_ip_whitelist', '147.79.123.200'),
('turnstile_enabled', '0'),
('turnstile_site_key', ''),
('turnstile_secret_key', ''),
('admin_login_captcha', 'none');

-- Table structure for users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `faucetpay_email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `balance` decimal(16,8) DEFAULT 0.00000000,
  `energy` int(11) DEFAULT 0,
  `play_sound` tinyint(1) DEFAULT 0,
  `referred_by` int(11) DEFAULT NULL,
  `daily_bonus_streak` int(11) DEFAULT 0,
  `xp` INT(11) NOT NULL DEFAULT 0,
  `account_status` ENUM('active', 'highrisk') NOT NULL DEFAULT 'active',
  `is_shadow_banned` TINYINT(1) NOT NULL DEFAULT 0,
  `risk_score` INT(11) NOT NULL DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_faucetpay_email` (`faucetpay_email`),
  UNIQUE KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for faucet_claims
CREATE TABLE IF NOT EXISTS `faucet_claims` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `faucet_type` enum('timed','instant') NOT NULL,
  `reward_usdt` decimal(16,8) NOT NULL,
  `reward_energy` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `claimed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_type_time` (`user_id`, `faucet_type`, `claimed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for referral_earnings
CREATE TABLE IF NOT EXISTS `referral_earnings` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `referrer_id` int(11) NOT NULL,
  `referred_user_id` int(11) NOT NULL,
  `category` enum('faucet','ptc','shortlinks','tasks','video','deposits','offerwall') NOT NULL,
  `amount` decimal(16,8) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_referrer_category` (`referrer_id`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for withdrawals
CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `faucetpay_email` varchar(255) NOT NULL,
  `amount` decimal(16,8) NOT NULL,
  `fee` decimal(16,8) NOT NULL DEFAULT 0.00000000,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `txid` varchar(200) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for daily_bonuses
CREATE TABLE IF NOT EXISTS `daily_bonuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `day_number` int(11) NOT NULL,
  `reward_usdt` decimal(16,8) NOT NULL,
  `min_condition_usdt` decimal(16,8) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_day_number` (`day_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default daily bonus ladder (USDT)
INSERT IGNORE INTO `daily_bonuses` (`day_number`, `reward_usdt`, `min_condition_usdt`) VALUES
(1, 0.01000000, 0.00000000),
(2, 0.02000000, 0.00000000),
(3, 0.03000000, 0.00000000),
(4, 0.04000000, 0.00000000),
(5, 0.05000000, 0.00000000),
(6, 0.07500000, 0.00000000),
(7, 0.10000000, 0.00000000);

-- Table structure for daily_bonus_claims
CREATE TABLE IF NOT EXISTS `daily_bonus_claims` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `day_number` int(11) NOT NULL,
  `reward_usdt` decimal(16,8) NOT NULL,
  `claim_date` date NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_date` (`user_id`, `claim_date`),
  KEY `idx_user_date_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for video_claims (Video rewards from OfferwallApi)
CREATE TABLE IF NOT EXISTS `video_claims` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `txid` varchar(200) NOT NULL,
  `reward_usdt` decimal(16,8) NOT NULL,
  `status` enum('completed','chargeback','rejected') NOT NULL DEFAULT 'completed',
  `campaign_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(10) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_txid` (`txid`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_user_date` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for ptc_claims
CREATE TABLE IF NOT EXISTS `ptc_claims` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `txid` varchar(200) NOT NULL,
  `reward_usdt` decimal(16,8) NOT NULL,
  `status` enum('completed','chargeback','rejected') NOT NULL DEFAULT 'completed',
  `campaign_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(10) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_txid` (`txid`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_user_date` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for shortlink_claims
CREATE TABLE IF NOT EXISTS `shortlink_claims` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `txid` varchar(200) NOT NULL,
  `reward_usdt` decimal(16,8) NOT NULL,
  `status` enum('completed','chargeback','rejected') NOT NULL DEFAULT 'completed',
  `campaign_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(10) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_txid` (`txid`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_user_date` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Anti-Fraud & Security Schema

-- Table for Fingerprints
CREATE TABLE IF NOT EXISTS `user_fingerprints` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `hw_hash` varchar(64) NOT NULL,
  `fp_hash` varchar(64) NOT NULL,
  `trusted_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hw_hash` (`hw_hash`),
  KEY `idx_fp_hash` (`fp_hash`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for Login Logs
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `asn` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for Security Logs
CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `severity` enum('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
  `reason` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_severity` (`user_id`, `severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for Movement Logs
CREATE TABLE IF NOT EXISTS `movement_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `risk_level` enum('low', 'medium', 'high') NOT NULL DEFAULT 'low',
  `details` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for Wheel Segments
CREATE TABLE IF NOT EXISTS `wheel_segments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `color` varchar(20) NOT NULL DEFAULT '#f1c40f',
  `reward_type` enum('USDT', 'Energy') NOT NULL DEFAULT 'USDT',
  `reward_value` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `win_chance` decimal(5,2) NOT NULL DEFAULT '0.00',
  `display_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default segments
INSERT IGNORE INTO `wheel_segments` (`color`, `reward_type`, `reward_value`, `win_chance`, `display_order`) VALUES
('#f1c40f', 'USDT', 0.05, 5.00, 1),
('#7f8c8d', 'USDT', 0, 45.00, 2),
('#e67e22', 'USDT', 0.01, 15.00, 3),
('#2c3e50', 'USDT', 0, 10.00, 4),
('#e74c3c', 'USDT', 0.02, 10.00, 5),
('#3498db', 'Energy', 10, 10.00, 6),
('#9b59b6', 'USDT', 0.1, 4.00, 7),
('#16a085', 'USDT', 1.0, 1.00, 8);

-- Table for Wheel Spins (History)
CREATE TABLE IF NOT EXISTS `wheel_spins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reward_type` varchar(20) NOT NULL,
  `reward_value` decimal(16,8) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_date` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for task_claims (microtask rewards from OfferwallApi)
CREATE TABLE IF NOT EXISTS `task_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `txid` varchar(200) NOT NULL,
  `reward_usdt` decimal(20,8) NOT NULL DEFAULT '0.00000000',
  `status` varchar(50) NOT NULL DEFAULT 'completed',
  `campaign_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `country` varchar(10) DEFAULT NULL,
  `claimed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `txid` (`txid`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for offerwall_claims (Offerwall.me iframe offers and surveys)
CREATE TABLE IF NOT EXISTS `offerwall_claims` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `txid` varchar(200) NOT NULL,
  `reward_usdt` decimal(16,8) NOT NULL,
  `status` enum('completed','chargeback','rejected') NOT NULL DEFAULT 'completed',
  `campaign_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(10) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_txid` (`txid`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_user_date` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
