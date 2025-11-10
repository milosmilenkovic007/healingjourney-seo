<?php
/**
 * ACF Local JSON storage
 */
if (!defined('ABSPATH')) { exit; }

add_filter('acf/settings/save_json', function($path){
    $dir = HJSEO_DIR . '/acf-json';
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }
    return $dir;
});

add_filter('acf/settings/load_json', function($paths){
    $dir = HJSEO_DIR . '/acf-json';
    if (is_dir($dir)) {
        $paths[] = $dir; // keep existing first path (default)
    }
    return $paths;
});
