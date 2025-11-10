<?php
/** MOZ API client (Links API v2) */
if (!defined('ABSPATH')) { exit; }

/**
 * Fetch MOZ url metrics for a domain.
 * @param string $site_url
 * @return array|WP_Error { authority, backlinks, ref_domains, raw }
 */
function hjseo_moz_url_metrics(string $site_url) {
    $site_url = trim($site_url);
    if ($site_url === '') {
        return new WP_Error('moz_empty', 'Empty site URL');
    }
    $access_id = get_option('hjseo_moz_access_id');
    $secret_key = get_option('hjseo_moz_secret_key');
    if (!$access_id || !$secret_key) {
        return new WP_Error('moz_auth_missing', 'MOZ API credentials missing');
    }
    $hash = md5($site_url);
    $cache_key = 'hj_moz_' . $hash;
    $cached = get_transient($cache_key);
    if ($cached) { return $cached; }

    $endpoint = 'https://lsapi.seomoz.com/v2/url_metrics';
    $body = wp_json_encode(['targets' => [$site_url]]);

    $args = [
        'method' => 'POST',
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($access_id . ':' . $secret_key),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'body' => $body,
        'timeout' => 20,
    ];

    $res = wp_remote_post($endpoint, $args);
    if (is_wp_error($res)) {
        return $res;
    }
    $code = wp_remote_retrieve_response_code($res);
    if ($code >= 400) {
        return new WP_Error('moz_http_' . $code, 'MOZ API error (' . $code . '): ' . wp_remote_retrieve_body($res));
    }
    $json = json_decode(wp_remote_retrieve_body($res), true);
    if (!is_array($json)) {
        return new WP_Error('moz_invalid', 'Invalid JSON from MOZ');
    }
    // Response expected array of metrics objects
    $metrics = $json[0] ?? [];
    $parsed = [
        'authority' => $metrics['domain_authority'] ?? null,
        'backlinks' => $metrics['external_links'] ?? null,
        'ref_domains' => $metrics['root_domains_linking'] ?? null,
        'raw' => $metrics,
    ];
    set_transient($cache_key, $parsed, WEEK_IN_SECONDS);
    return $parsed;
}

/** Test connection helper */
function hjseo_moz_test_connection() {
    $test = hjseo_moz_url_metrics('https://example.com/');
    if (is_wp_error($test)) return $test;
    return 'Domain Authority for example.com: ' . (int)$test['authority'];
}
