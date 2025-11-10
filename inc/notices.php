<?php
/**
 * Admin notices and configuration validation
 */

if (!defined('ABSPATH')) { exit; }

/**
 * Run a periodic validation for GSC service account/property access.
 * Cache the result in a transient to avoid slowing down admin.
 */
add_action('admin_init', function(){
    if (!current_user_can('manage_options')) return;
    $key = 'hjseo_gsc_validation';
    $cached = get_transient($key);
    if ($cached === false) {
        if (function_exists('hjseo_gsc_validate_properties')) {
            $res = hjseo_gsc_validate_properties();
            if (is_wp_error($res)) {
                set_transient($key, ['status'=>'error','message'=>$res->get_error_message()], HOUR_IN_SECONDS * 12);
            } else {
                set_transient($key, ['status'=>'ok'], HOUR_IN_SECONDS * 12);
            }
        }
    }
});

/**
 * Force re-check via admin-post action
 */
add_action('admin_post_hjseo_gsc_validate_now', function(){
    if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions', HJSEO_TD));
    check_admin_referer('hjseo_gsc_validate_now');
    delete_transient('hjseo_gsc_validation');
    // Immediately perform a fresh check
    $msg = '';
    if (function_exists('hjseo_gsc_validate_properties')) {
        $res = hjseo_gsc_validate_properties();
        if (is_wp_error($res)) {
            set_transient('hjseo_gsc_validation', ['status'=>'error','message'=>$res->get_error_message()], HOUR_IN_SECONDS * 12);
            $msg = 'error';
        } else {
            set_transient('hjseo_gsc_validation', ['status'=>'ok'], HOUR_IN_SECONDS * 12);
            $msg = 'ok';
        }
    }
    wp_safe_redirect( add_query_arg(['hjseo_gsc_checked'=>$msg], wp_get_referer() ?: admin_url()) );
    exit;
});

/**
 * Show admin notice if validation failed
 */
add_action('admin_notices', function(){
    if (!current_user_can('manage_options')) return;
    $state = get_transient('hjseo_gsc_validation');
    if (!$state || !is_array($state)) return;
    if (!empty($state['status']) && $state['status'] === 'error') {
        $settings_url = admin_url('options-general.php?page=hjseo-settings');
        $check_url = wp_nonce_url(admin_url('admin-post.php?action=hjseo_gsc_validate_now'), 'hjseo_gsc_validate_now');
        echo '<div class="notice notice-error"><p><strong>HealingJourney SEO:</strong> Google Search Console access problem detected.<br/>';
        echo esc_html($state['message']);
        echo ' — Please verify the service account JSON and property ownership on the <a href="'.esc_url($settings_url).'">SEO Integrations</a> page.';
        echo ' <a class="button button-secondary" href="'.esc_url($check_url).'">Re-check now</a>';
        echo '</p></div>';
    }
});
