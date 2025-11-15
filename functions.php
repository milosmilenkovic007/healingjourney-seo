<?php
// Hide WP admin bar on front-end
add_filter('show_admin_bar', '__return_false');
<?php
/**
 * healingjourney-seo Theme bootstrap
 */

if (!defined('ABSPATH')) { exit; }

// Constants
const HJSEO_VERSION = '1.0.0';
const HJSEO_TD = 'healingjourney-seo';

define('HJSEO_DIR', get_stylesheet_directory());
define('HJSEO_URI', get_stylesheet_directory_uri());

// Theme setup
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
});

// Enqueue assets
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('hjseo-style', HJSEO_URI . '/style.css', [], HJSEO_VERSION);
    wp_enqueue_style('hjseo-custom', HJSEO_URI . '/assets/css/custom.css', ['hjseo-style'], HJSEO_VERSION);
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true);
    wp_enqueue_script('hjseo-charts', HJSEO_URI . '/assets/js/charts.js', ['chartjs'], HJSEO_VERSION, true);
    wp_enqueue_script('hjseo-main', HJSEO_URI . '/assets/js/main.js', ['hjseo-charts'], HJSEO_VERSION, true);
});

// Admin assets (lightweight)
// Scope styles only to front-end (avoid coloring WP Admin). We no longer enqueue style.css in admin.

// Add a body class so CSS can target only theme front-end pages.
add_filter('body_class', function($classes){
    $classes[] = 'hjseo';
    return $classes;
});

// Include modules
$hjseo_includes = [
    '/inc/helpers.php',
    '/inc/cpt.php',
    '/inc/acf.php',
    '/inc/shortcodes.php',
    '/inc/rewrite.php',
    '/inc/dashboard.php',
    '/inc/moz-api.php',
    '/inc/gsc-api.php',
    '/inc/api-sync.php',
    '/inc/settings.php',
    '/inc/admin-columns.php',
    '/inc/media.php',
    '/inc/seed.php',
    '/inc/notices.php',
    '/inc/acf-json.php',
    '/inc/cli.php',
    '/inc/auth.php',
];
foreach ($hjseo_includes as $file) {
    $path = HJSEO_DIR . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

// Activation/deactivation hooks for rewrite rules
add_action('after_switch_theme', function () {
    if (function_exists('hjseo_register_rewrites')) {
        hjseo_register_rewrites();
    }
    flush_rewrite_rules();
});

add_action('switch_theme', function () { flush_rewrite_rules(); });
