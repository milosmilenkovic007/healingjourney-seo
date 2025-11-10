<?php
/** Optional seed script to create initial seo_site posts */
if (!defined('ABSPATH')) { exit; }

function hjseo_seed_sites() {
    $sites = [
        'https://healingjourney.travel/',
        'https://kneesurgeryturkey.com/',
        'https://HealthCheckupIstanbul.com/',
        'https://Themedicorner.com/',
        'https://medicaltravelfacilitator.com/',
        'https://healthcheckupantalya.com/',
    ];
    foreach ($sites as $url) {
        $existing = get_posts(['post_type'=>'seo_site','name'=>sanitize_title($url),'posts_per_page'=>1]);
        if ($existing) continue;
        $post_id = wp_insert_post([
            'post_type' => 'seo_site',
            'post_status' => 'publish',
            'post_title' => parse_url($url, PHP_URL_HOST),
            'post_name' => sanitize_title(parse_url($url, PHP_URL_HOST)),
        ]);
        if ($post_id && !is_wp_error($post_id)) {
            hjseo_update_field_value('site_domain', $url, $post_id);
            hjseo_update_field_value('gsc_property', $url, $post_id);
            hjseo_update_field_value('active', '1', $post_id);
        }
    }
}
// Uncomment to seed automatically on theme switch
// add_action('after_switch_theme','hjseo_seed_sites');
