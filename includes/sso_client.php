<?php
/**
 * Server-side OurMarketplace SSO (authorization code + token exchange).
 */
function kg_sso_config(): array {
    static $cfg;
    if ($cfg === null) {
        $path = __DIR__ . '/../config/sso.php';
        $cfg = is_readable($path) ? require $path : [];
        if (!is_array($cfg)) {
            $cfg = [];
        }
    }
    return $cfg;
}

function kg_sso_authorize_url(): string {
    $c = kg_sso_config();
    $base = rtrim((string)($c['provider_base'] ?? ''), '/');
    $appId = (string)($c['app_id'] ?? '');
    $redirect = (string)($c['redirect_url'] ?? '');
    if ($base === '' || $appId === '' || $redirect === '') {
        return '#';
    }
    return $base . '/sso/authorize.php?' . http_build_query([
        'app_id' => $appId,
        'redirect_url' => $redirect,
    ]);
}

/** Web registration on OurMarketplace (customers create accounts there, then use SSO here). */
function kg_sso_marketplace_register_url(): string {
    $c = kg_sso_config();
    if (!empty($c['marketplace_register_url']) && is_string($c['marketplace_register_url'])) {
        return (string)$c['marketplace_register_url'];
    }
    $base = rtrim((string)($c['provider_base'] ?? ''), '/');
    if ($base === '') {
        return '#';
    }
    return $base . '/auth/register.php';
}

/**
 * Exchange an authorization code for token + user (id, username, full_name).
 * @return array Tuple: [bool success, ?array payload, string errorMessage]
 */
function kg_sso_exchange_code(string $code): array {
    if (!function_exists('curl_init')) {
        return [false, null, 'cURL is not available on this server.'];
    }
    $c = kg_sso_config();
    $base = rtrim((string)($c['provider_base'] ?? ''), '/');
    $url = $base . '/sso/token.php';
    $payload = json_encode([
        'code' => $code,
        'app_id' => (string)($c['app_id'] ?? ''),
        'app_secret' => (string)($c['app_secret'] ?? ''),
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $body = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err !== '') {
        return [false, null, 'SSO request failed: ' . $err];
    }
    $data = json_decode((string)$body, true);
    if ($http !== 200 || !is_array($data) || empty($data['token']) || empty($data['user'])) {
        $msg = is_array($data) && isset($data['error']) ? (string)$data['error'] : ('HTTP ' . $http);
        return [false, null, $msg];
    }
    return [true, $data, ''];
}
