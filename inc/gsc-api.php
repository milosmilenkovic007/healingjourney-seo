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
 * Auto-tries both with and without trailing slash for URL properties.
 * @param string $property e.g. https://example.com/ or https://example.com or sc-domain:example.com
 * @param string $start Y-m-d
 * @param string $end Y-m-d
 * @return array|WP_Error {clicks, impressions, queries, rows}
 */
function hjseo_gsc_summary(string $property, string $start, string $end) {
    $token = hjseo_gsc_get_access_token();
    if (is_wp_error($token)) return $token;
    $property = trim($property);
    if ($property === '') return new WP_Error('gsc_property', 'Empty GSC property');

    // Build property variants to try
    $variants = [];

    $is_domain_prop = stripos($property, 'sc-domain:') === 0;
    $is_url_prop = preg_match('~^https?://~i', $property) === 1;

    // Helper to push unique variants keeping order
    $push = function($v) use (&$variants) {
        $v = rtrim($v);
        if ($v === '') return;
        if (!in_array($v, $variants, true)) $variants[] = $v;
    };

    if ($is_domain_prop) {
        $domain = trim(substr($property, strlen('sc-domain:')));
        $push('sc-domain:' . $domain);
        // Try common URL-prefix representations for the same domain (with and without www)
        foreach (['https://', 'http://'] as $scheme) {
            foreach ([$domain, (stripos($domain, 'www.') === 0 ? substr($domain, 4) : 'www.' . $domain)] as $host) {
                $push($scheme . $host . '/');
            }
        }
    } elseif ($is_url_prop) {
        // Start with original URL and its slash variant
        $push($property);
        $push(substr($property, -1) === '/' ? rtrim($property, '/') : $property . '/');
        // http/https swap
        if (stripos($property, 'https://') === 0) {
            $http = 'http://' . substr($property, 8);
            $push($http);
            $push(rtrim($http, '/'));
        } else {
            $https = 'https://' . substr($property, 7);
            $push($https);
            $push(rtrim($https, '/'));
        }
        // Also try sc-domain derived from host
        $host = parse_url($property, PHP_URL_HOST);
        if ($host) $push('sc-domain:' . $host);
    } else {
        // Bare domain or malformed value — construct robust variants
        $domain = preg_replace('~^https?://~i', '', $property);
        $domain = rtrim($domain, '/');
        $domain = trim($domain);
        if ($domain) {
            $push('sc-domain:' . $domain);
            foreach (['https://', 'http://'] as $scheme) {
                foreach ([$domain, (stripos($domain, 'www.') === 0 ? substr($domain, 4) : 'www.' . $domain)] as $host) {
                    $push($scheme . $host . '/');
                }
            }
        }
    }

    if (function_exists('hjseo_debug_log')) {
        hjseo_debug_log('GSC property_variants', ['original' => $property, 'variants' => $variants]);
    }

    $body = [
        'startDate' => $start,
        'endDate' => $end,
        'dimensions' => ['query'],
        'rowLimit' => 25000,
    ];

    $last_error = null;
    foreach ($variants as $prop) {
        $url = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($prop) . '/searchAnalytics/query';
        $res = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
            'timeout' => 30,
        ]);
        if (is_wp_error($res)) {
            $last_error = $res;
            continue;
        }
        $code = wp_remote_retrieve_response_code($res);
        if ($code === 429) return new WP_Error('gsc_429', 'Rate limited by Google');
        if ($code >= 400) {
            $body_raw = wp_remote_retrieve_body($res);
            if (function_exists('hjseo_debug_log')) {
                hjseo_debug_log('GSC error', ['variant' => $prop, 'code' => $code, 'body' => $body_raw]);
            }
            $last_error = new WP_Error('gsc_http_' . $code, $body_raw);
            continue;
        }
        // Success
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
    // All variants failed
    return $last_error ?: new WP_Error('gsc_fail', 'GSC API failed for all property variants');
}

/** Test connection helper */
function hjseo_gsc_test_connection($property) {
    $end = date('Y-m-d');
    $start = date('Y-m-d', strtotime('-28 days'));
    $sum = hjseo_gsc_summary($property, $start, $end);
    if (is_wp_error($sum)) return $sum;
    return sprintf('GSC OK — clicks: %d, impressions: %d, queries: %d', $sum['clicks'], $sum['impressions'], $sum['queries']);
}

/** Validate service account ownership for active properties (lightweight) */
function hjseo_gsc_validate_properties() {
    $json = hjseo_gsc_get_sa_json();
    if (!$json) return new WP_Error('gsc_missing', 'Service account JSON missing');
    $sites = get_posts(['post_type'=>'seo_site','posts_per_page'=>-1,'meta_key'=>'active','meta_value'=>'1']);
    $errors = [];
    foreach ($sites as $s) {
        $prop = hjseo_field('gsc_property', $s->ID);
        if (!$prop) continue;
        $test = hjseo_gsc_summary($prop, date('Y-m-d', strtotime('-1 days')), date('Y-m-d'));
        if (is_wp_error($test)) {
            $errors[] = $prop . ': ' . $test->get_error_message();
        }
    }
    if ($errors) {
        return new WP_Error('gsc_props', implode('; ', $errors));
    }
    return true;
}
