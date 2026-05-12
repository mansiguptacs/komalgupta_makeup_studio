<?php
/**
 * Copy to sso_credentials.local.php and adjust for your environment.
 * The app_secret must match OurMarketplace `sso_apps.app_secret` for app_id kg-makeup-studio.
 *
 * For local development, register a matching redirect_url in OurMarketplace (e.g.
 * http://localhost/komalgupta_makeup_studio/sso/callback.php) and set redirect_url here.
 */
return [
    'app_secret' => 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2',
    // 'redirect_url' => 'http://localhost/.../sso/callback.php',
];
