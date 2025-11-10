<?php
/** Google Search Console client using Service Account (no external libs). */
if (!defined('ABSPATH')) { exit; }

const HJSEO_GSC_SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';
const HJSEO_GSC_TOKEN_URL = 'https://oauth2.googleapis.com/token';

/**
 * Get service account JSON (string) from wp_options.
 */
function hjseo_gsc_get_sa_json() {
    $json = get_option('hjseo_gsc_service_account_json');
    return is_string($json) ? trim($json) : '';
}

/**
 * Build a signed JWT and exchange for an access token. Token is cached in a transient.
 * @return string|WP_Error access token
 */
function hjseo_gsc_get_access_token() {
    $json = hjseo_gsc_get_sa_json();
    if (!$json) return new WP_Error('gsc_no_json', 'GSC service account JSON not configured');
    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['client_email']) || empty($data['private_key'])) {
        return new WP_Error('gsc_bad_json', 'Invalid service account JSON');
    }
    $cache_key = 'hj_gsc_token_' . md5($data['client_email']);
    $cached = get_transient($cache_key);
    if ($cached) return $cached;

    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claims = [
        'iss' => $data['client_email'],
        'scope' => HJSEO_GSC_SCOPE,
        'aud' => HJSEO_GSC_TOKEN_URL,
        'exp' => $now + 3600,
        'iat' => $now,
    ];
    $segments = [
        rtrim(strtr(base64_encode(wp_json_encode($header)), '+/', '-_'), '='),
        rtrim(strtr(base64_encode(wp_json_encode($claims)), '+/', '-_'), '='),
    ];
    $input = implode('.', $segments);

    $private_key = openssl_pkey_get_private($data['private_key']);
    if (!$private_key) return new WP_Error('gsc_key', 'Invalid private key');
    $signature = '';
    $ok = openssl_sign($input, $signature, $private_key, 'sha256WithRSAEncryption');
    openssl_free_key($private_key);
    if (!$ok) return new WP_Error('gsc_sign', 'JWT signing failed');

    $segments[] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    $jwt = implode('.', $segments);

    $response = wp_remote_post(HJSEO_GSC_TOKEN_URL, [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
        'timeout' => 20,
    ]);
    if (is_wp_error($response)) return $response;
    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 400) return new WP_Error('gsc_oauth_' . $code, wp_remote_retrieve_body($response));
    $json = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($json['access_token'])) return new WP_Error('gsc_token', 'Token missing');
    $token = $json['access_token'];
    set_transient($cache_key, $token, 55 * MINUTE_IN_SECONDS);
    return $token;
}

/**
 * Get Search Console summary for a property.
 * @param string $property e.g. https://example.com/
 * @param string $start Y-m-d
 * @param string $end Y-m-d
 * @return array|WP_Error {clicks, impressions, queries, rows}
 */
function hjseo_gsc_summary(string $property, string $start, string $end) {
    $token = hjseo_gsc_get_access_token();
    if (is_wp_error($token)) return $token;
    $property = trim($property);
    if ($property === '') return new WP_Error('gsc_property', 'Empty GSC property');

    $url = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($property) . '/searchAnalytics/query';
    $body = [
        'startDate' => $start,
        'endDate' => $end,
        'dimensions' => ['query'],
        'rowLimit' => 25000,
    ];
    $res = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($body),
        'timeout' => 30,
    ]);
    if (is_wp_error($res)) return $res;
    $code = wp_remote_retrieve_response_code($res);
    if ($code === 429) return new WP_Error('gsc_429', 'Rate limited by Google');
    if ($code >= 400) return new WP_Error('gsc_http_' . $code, wp_remote_retrieve_body($res));
    $data = json_decode(wp_remote_retrieve_body($res), true);
    $rows = $data['rows'] ?? [];
    $clicks = 0; $impr = 0; $q = 0;
    foreach ($rows as $r) {
        $clicks += (int)($r['clicks'] ?? 0);
        $impr += (int)($r['impressions'] ?? 0);
        $q++;
    }
    return [
        'clicks' => $clicks,
        'impressions' => $impr,
        'queries' => $q,
        'rows' => $rows,
    ];
}

/** Test connection helper */
function hjseo_gsc_test_connection($property) {
    $end = date('Y-m-d');
    $start = date('Y-m-d', strtotime('-28 days'));
    $sum = hjseo_gsc_summary($property, $start, $end);
    if (is_wp_error($sum)) return $sum;
    return sprintf('GSC OK — clicks: %d, impressions: %d, queries: %d', $sum['clicks'], $sum['impressions'], $sum['queries']);
}
