<?php
/** Central synchronization orchestration for MOZ + GSC */
if (!defined('ABSPATH')) { exit; }

/** Update metrics for a single Site post */
function hjseo_update_site_metrics(int $site_post_id) {
    $domain = trim((string)hjseo_field('site_domain', $site_post_id));
    $property = trim((string)hjseo_field('gsc_property', $site_post_id));
    // Normalize domain (ensure scheme) for MOZ & GSC consistency
    if ($domain && !preg_match('~^https?://~i', $domain)) {
        $domain = 'https://' . ltrim($domain, '/');
    }
    // Normalize property (GSC requires exact match including trailing slash for domain properties)
    if ($property && !preg_match('~^https?://~i', $property)) {
        $property = 'https://' . ltrim($property, '/');
    }
    if ($property && substr($property, -1) !== '/') {
        $property .= '/';
    }
    if (!$domain) return new WP_Error('site_domain_missing', 'Site domain missing');
    if (!$property) return new WP_Error('gsc_property_missing', 'GSC property missing');

    $moz = hjseo_moz_url_metrics($domain);
    $window = get_option('hjseo_sync_window', 28);
    $end = date('Y-m-d');
    $start = date('Y-m-d', strtotime('-' . (int)$window . ' days'));
    $gsc = hjseo_gsc_summary($property, $start, $end);

    if (is_wp_error($moz)) return $moz;
    if (is_wp_error($gsc)) return $gsc;

    $authority = (int)($moz['authority'] ?? 0);
    $backlinks = (int)($moz['backlinks'] ?? 0);
    $ref_domains = (int)($moz['ref_domains'] ?? 0);
    $keywords = (int)($gsc['queries'] ?? 0);
    $clicks = (int)($gsc['clicks'] ?? 0);
    $impr = (int)($gsc['impressions'] ?? 0);
    $visibility = $impr > 0 ? round(($clicks / $impr) * 100, 2) : 0.00;

    hjseo_update_field_value('authority', $authority, $site_post_id);
    hjseo_update_field_value('backlinks', $backlinks, $site_post_id);
    hjseo_update_field_value('ref_domains', $ref_domains, $site_post_id);
    hjseo_update_field_value('keywords', $keywords, $site_post_id);
    hjseo_update_field_value('visibility', $visibility, $site_post_id);
    hjseo_update_field_value('last_synced', current_time('mysql'), $site_post_id);

    hjseo_log_sync([
        'site' => $domain,
        'authority' => $authority,
        'backlinks' => $backlinks,
        'ref_domains' => $ref_domains,
        'keywords' => $keywords,
        'visibility' => $visibility,
        'window_days' => $window,
    ]);

    return [
        'authority' => $authority,
        'backlinks' => $backlinks,
        'ref_domains' => $ref_domains,
        'keywords' => $keywords,
        'visibility' => $visibility,
    ];
}

// (Removed auto-sync on save to simplify UX and avoid unexpected redirects)

// Admin notice on edit screen if last auto-sync failed
add_action('admin_notices', function(){
    global $pagenow, $post;
    if ($pagenow !== 'post.php') return;
    if (!$post || $post->post_type !== 'seo_site') return;
    // Notice from on-demand refresh
    if (isset($_GET['hjseo_site_sync'])) {
        if ($_GET['hjseo_site_sync'] === 'ok') {
            echo '<div class="notice notice-success"><p>Metrics refreshed successfully.</p></div>';
        } elseif ($_GET['hjseo_site_sync'] === 'fail') {
            $msg = isset($_GET['msg']) ? sanitize_text_field(wp_unslash($_GET['msg'])) : 'Unknown error';
            echo '<div class="notice notice-error"><p>Refresh failed: ' . esc_html($msg) . '</p></div>';
        }
    }
    $msg = get_transient('hjseo_last_sync_error_' . $post->ID);
    if ($msg) {
        echo '<div class="notice notice-error"><p><strong>HealingJourney SEO Auto-Sync Error:</strong> ' . esc_html($msg) . '</p></div>';
    }
});

/** Log helper */
function hjseo_log_sync($payload) {
    $upload = wp_get_upload_dir();
    $file = trailingslashit($upload['basedir']) . 'hjseo-sync.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . wp_json_encode($payload) . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
}

/** Cron setup */
add_action('init', function() {
    if (get_option('hjseo_enable_cron') !== '1') return;
    if (!wp_next_scheduled('hjseo_sync_cron_daily')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'hjseo_sync_cron_daily');
    }
    if (!wp_next_scheduled('hjseo_sync_cron_weekly')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'weekly', 'hjseo_sync_cron_weekly');
    }
});

add_action('hjseo_sync_cron_daily', 'hjseo_sync_daily');
add_action('hjseo_sync_cron_weekly', 'hjseo_sync_weekly');

function hjseo_get_active_sites() {
    return get_posts([
        'post_type' => 'seo_site',
        'posts_per_page' => -1,
        'meta_key' => 'active',
        'meta_value' => '1',
    ]);
}

function hjseo_sync_daily() {
    $sites = hjseo_get_active_sites();
    foreach ($sites as $s) {
        // Only GSC portion daily
        $property = hjseo_field('gsc_property', $s->ID);
        if (!$property) continue;
        $window = get_option('hjseo_sync_window', 28);
        $end = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-' . (int)$window . ' days'));
        $gsc = hjseo_gsc_summary($property, $start, $end);
        if (is_wp_error($gsc)) continue; // skip on error
        $keywords = (int)($gsc['queries'] ?? 0);
        $clicks = (int)($gsc['clicks'] ?? 0);
        $impr = (int)($gsc['impressions'] ?? 0);
        $visibility = $impr > 0 ? round(($clicks / $impr) * 100, 2) : 0.00;
        hjseo_update_field_value('keywords', $keywords, $s->ID);
        hjseo_update_field_value('visibility', $visibility, $s->ID);
        hjseo_update_field_value('last_synced', current_time('mysql'), $s->ID);
        hjseo_log_sync(['cron' => 'daily', 'site' => hjseo_field('site_domain', $s->ID), 'keywords' => $keywords, 'visibility' => $visibility]);
    }
}

function hjseo_sync_weekly() {
    $sites = hjseo_get_active_sites();
    foreach ($sites as $s) {
        $domain = hjseo_field('site_domain', $s->ID);
        if (!$domain) continue;
        $moz = hjseo_moz_url_metrics($domain);
        if (is_wp_error($moz)) continue;
        hjseo_update_field_value('authority', (int)($moz['authority'] ?? 0), $s->ID);
        hjseo_update_field_value('backlinks', (int)($moz['backlinks'] ?? 0), $s->ID);
        hjseo_update_field_value('ref_domains', (int)($moz['ref_domains'] ?? 0), $s->ID);
        hjseo_update_field_value('last_synced', current_time('mysql'), $s->ID);
        hjseo_log_sync(['cron' => 'weekly', 'site' => $domain, 'authority' => $moz['authority'] ?? null]);
    }
}

/** Manual full sync action */
add_action('admin_post_hjseo_full_sync', function() {
    if (!current_user_can('manage_options')) wp_die('Forbidden');
    check_admin_referer('hjseo_full_sync');
    $sites = hjseo_get_active_sites();
    $ok = 0; $fail = 0;
    foreach ($sites as $s) {
        $res = hjseo_update_site_metrics($s->ID);
        if (is_wp_error($res)) { $fail++; } else { $ok++; }
    }
    wp_redirect(add_query_arg(['hjseo_sync' => 'done', 'ok' => $ok, 'fail' => $fail], wp_get_referer() ?: admin_url()));
    exit;
});

/** Metabox on single seo_site */
add_action('add_meta_boxes', function() {
    add_meta_box('hjseo_refresh_box', 'SEO Metrics', 'hjseo_refresh_box_cb', 'seo_site', 'side', 'high');
});

function hjseo_refresh_box_cb($post) {
    $metrics = hjseo_get_site_metrics($post->ID);
    echo '<p><strong>Authority:</strong> ' . esc_html($metrics['authority'] ?: '—') . '</p>';
    echo '<p><strong>Backlinks:</strong> ' . esc_html($metrics['backlinks'] ?: '—') . '</p>';
    echo '<p><strong>Ref Domains:</strong> ' . esc_html($metrics['ref_domains'] ?: '—') . '</p>';
    echo '<p><strong>Keywords:</strong> ' . esc_html($metrics['keywords'] ?: '—') . '</p>';
    echo '<p><strong>Visibility:</strong> ' . esc_html($metrics['visibility'] !== '' ? $metrics['visibility'] . '%' : '—') . '</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('hjseo_refresh_site_' . $post->ID);
    echo '<input type="hidden" name="action" value="hjseo_refresh_site" />';
    echo '<input type="hidden" name="site_id" value="' . esc_attr($post->ID) . '" />';
    echo '<p><button class="button button-primary">Refresh metrics</button></p>';
    echo '</form>';
}

add_action('admin_post_hjseo_refresh_site', function() {
    if (!current_user_can('edit_posts')) wp_die('Forbidden');
    $site_id = (int)($_POST['site_id'] ?? 0);
    check_admin_referer('hjseo_refresh_site_' . $site_id);
    $res = hjseo_update_site_metrics($site_id);
    $dest = add_query_arg(['post'=>$site_id,'action'=>'edit'], admin_url('post.php'));
    if (is_wp_error($res)) {
        $dest = add_query_arg(['hjseo_site_sync' => 'fail', 'msg' => rawurlencode($res->get_error_message())], $dest);
    } else {
        $dest = add_query_arg(['hjseo_site_sync' => 'ok'], $dest);
    }
    wp_redirect($dest);
    exit;
});
