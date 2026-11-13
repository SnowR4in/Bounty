<?php

if (!function_exists('be_valid_usdt_address')) {
    function be_valid_usdt_address(string $address): bool {
        return (bool)preg_match('/^0x[a-fA-F0-9]{40}$/', trim($address));
    }
}

if (!function_exists('be_valid_network')) {
    function be_valid_network(string $network): bool {
        return in_array(strtolower(trim($network)), ['bnb', 'polygon'], true);
    }
}

if (!function_exists('be_network_label')) {
    function be_network_label(string $network): string {
        $network = strtolower(trim($network));
        if ($network === 'bnb') return 'BNB Chain (BEP-20)';
        if ($network === 'polygon') return 'Polygon (USDT)';
        return $network;
    }
}

if (!function_exists('be_mask_address')) {
    function be_mask_address(string $address): string {
        $address = trim($address);
        if (strlen($address) < 12) return $address;
        return substr($address, 0, 6) . '...' . substr($address, -4);
    }
}
