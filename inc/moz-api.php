<?php
/** MOZ API client (Links API v2) */
if (!defined('ABSPATH')) { exit; }

/**
 * Fetch MOZ url metrics for a domain (Links API v2).
 * Robust to minor API field name variations and logs debug details when enabled.
 * @param string $site_url
 * @return array|WP_Error { authority, backlinks, ref_domains, raw }
 */
function hjseo_moz_url_metrics(string $site_url) {
    $site_url = trim($site_url);
    if ($site_url === '') {
        return new WP_Error('moz_empty', 'Empty site URL');
    }
    // Normalize: always https://, no trailing slash for MOZ target
    if (!preg_match('~^https?://~i', $site_url)) {
        $site_url = 'https://' . ltrim($site_url, '/');
    }
    $site_url = rtrim($site_url, '/');
    $access_id = get_option('hjseo_moz_access_id');
    $secret_key = get_option('hjseo_moz_secret_key');
    if (!$access_id || !$secret_key) {
        return new WP_Error('moz_auth_missing', 'MOZ API credentials missing');
    }
    // Bust old caches by including a version suffix
    $hash = md5($site_url);
    $cache_key = 'hj_moz_' . $hash . '_v2m4';
    $cached = get_transient($cache_key);
    if ($cached) { return $cached; }

    $endpoint = 'https://lsapi.seomoz.com/v2/url_metrics';

    // We'll try a couple of target variants if the first returns empty
    $variants = [];
    $variants[] = $site_url; // https root
    $host = parse_url($site_url, PHP_URL_HOST);
    if ($host) { $variants[] = $host; }
    if ($host && strpos($host, 'www.') !== 0) { $variants[] = 'www.' . $host; }

    $last_error = null; $parsed = null;
    foreach ($variants as $i => $target) {
        $bodyArr = [
            'targets' => [$target],
            'metrics' => [
                'domain_authority', 'domain_authority_score',
                'external_links', 'external_pages', 'inbound_links',
                'root_domains_linking', 'linking_root_domains'
            ],
            // root_domain scope improves chances of DA being populated
            'scope' => 'root_domain',
        ];
    $body = wp_json_encode($bodyArr);

    $args = [
        'method' => 'POST',
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($access_id . ':' . $secret_key),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'HealingJourney-SEO/1.0 (+wordpress)'
        ],
        'body' => $body,
        'timeout' => 20,
    ];

        if (function_exists('hjseo_debug_log')) {
            hjseo_debug_log('MOZ request', ['endpoint' => $endpoint, 'body' => $bodyArr]);
        }

        $args['body'] = wp_json_encode($bodyArr);
        $res = wp_remote_post($endpoint, $args);
        if (is_wp_error($res)) {
            $last_error = $res;
            if (function_exists('hjseo_debug_log')) {
                hjseo_debug_log('MOZ wp_error', ['message' => $res->get_error_message()]);
            }
            continue;
        }
        $code = wp_remote_retrieve_response_code($res);
        $body_raw = wp_remote_retrieve_body($res);
        if ($code >= 400) {
            $last_error = new WP_Error('moz_http_' . $code, 'MOZ API error (' . $code . '): ' . $body_raw);
            if (function_exists('hjseo_debug_log')) {
                hjseo_debug_log('MOZ http_error', ['code' => $code, 'body' => $body_raw]);
            }
            continue;
        }
        $json = json_decode($body_raw, true);
        if (function_exists('hjseo_debug_log')) {
            // log the raw body to understand structure
            hjseo_debug_log('MOZ body_raw', ['body' => $body_raw]);
        }
        if (!is_array($json)) {
            $last_error = new WP_Error('moz_invalid', 'Invalid JSON from MOZ');
            if (function_exists('hjseo_debug_log')) {
                hjseo_debug_log('MOZ invalid_json', ['body' => $body_raw]);
            }
            continue;
        }
        // Try to extract metrics from different possible shapes
        $metrics = [];
        $is_assoc = array_keys($json) !== range(0, count($json) - 1);
        if (!$is_assoc && isset($json[0]) && is_array($json[0])) {
            $metrics = $json[0];
        } elseif ($is_assoc && isset($json['results'][0]) && is_array($json['results'][0])) {
            $metrics = $json['results'][0];
        } elseif ($is_assoc) {
            $metrics = $json; // maybe the object itself is a metrics map
        }

        // Map with fallbacks to handle field variants. Prefer root-domain scope fields visible in `body_raw`.
        $parsed = [
            'authority'  => $metrics['domain_authority'] ?? $metrics['domain_authority_score'] ?? $metrics['authority'] ?? null,
            'backlinks'  => (
                $metrics['external_pages_to_root_domain']
                ?? $metrics['external_pages_to_subdomain']
                ?? $metrics['external_pages_to_page']
                ?? $metrics['external_links']
                ?? $metrics['links']
                ?? $metrics['inbound_links']
            ),
            'ref_domains'=> (
                $metrics['root_domains_to_root_domain']
                ?? $metrics['root_domains_to_subdomain']
                ?? $metrics['root_domains_to_page']
                ?? $metrics['root_domains_linking']
                ?? $metrics['linking_root_domains']
                ?? $metrics['root_domains_to_root']
                ?? $metrics['root_domains']
            ),
            'raw' => $metrics,
        ];

        if (function_exists('hjseo_debug_log')) {
            hjseo_debug_log('MOZ response', ['status' => $code, 'parsed' => $parsed]);
        }

        if (!empty($parsed['authority']) || !empty($parsed['backlinks']) || !empty($parsed['ref_domains'])) {
            set_transient($cache_key, $parsed, WEEK_IN_SECONDS);
            return $parsed;
        }
        // Try next variant if this one empty
    }
    // If all variants failed, return last error or empty metrics
    if ($last_error instanceof WP_Error) return $last_error;
    $fallback = ['authority'=>0,'backlinks'=>0,'ref_domains'=>0,'raw'=>[]];
    set_transient($cache_key, $fallback, HOUR_IN_SECONDS); // short cache to avoid thrashing
    return $fallback;
}

/** Test connection helper */
function hjseo_moz_test_connection() {
    $test = hjseo_moz_url_metrics('https://example.com/');
    if (is_wp_error($test)) return $test;
    return 'Domain Authority for example.com: ' . (int)$test['authority'];
}
