<?php
/**
 * OurMarketplace SSO client defaults.
 * Override secrets and URLs in config/sso_credentials.local.php (see sso_credentials.example.php).
 */
$defaults = [
    'provider_base' => 'https://mansiguptacs.com/ourmarketplace',
    'app_id' => 'kg-makeup-studio',
    'app_secret' => '',
    // Must match the redirect_url registered in OurMarketplace sso_apps for this app_id.
    'redirect_url' => 'https://mansiguptacs.com/kgmakeupstudio/sso/callback.php',
];

$local = [];
$localPath = __DIR__ . '/sso_credentials.local.php';
if (is_readable($localPath)) {
    $loaded = require $localPath;
    if (is_array($loaded)) {
        $local = $loaded;
    }
}

return array_merge($defaults, $local);
