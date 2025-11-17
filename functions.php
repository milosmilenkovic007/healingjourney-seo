<?php
// Hide WP admin bar on front-end
add_filter('show_admin_bar', '__return_false');
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
    // Ensure custom roles for task management
    if (!get_role('seo_manager')) {
        add_role('seo_manager', 'SEO Manager', [ 'read' => true ]);
    }
    if (!get_role('seo_developer')) {
        add_role('seo_developer', 'SEO Developer', [ 'read' => true ]);
    }
    // Add custom caps
    if ($r = get_role('seo_manager')) { $r->add_cap('manage_seo_tasks'); }
    if ($r = get_role('seo_developer')) { $r->add_cap('complete_seo_tasks'); }
    if ($r = get_role('administrator')) { $r->add_cap('manage_seo_tasks'); $r->add_cap('complete_seo_tasks'); }
    // Content role for keyword maps and content plans
    if (!get_role('seo_content')) {
        add_role('seo_content', 'Content Editor', [ 'read' => true ]);
    }
    if ($r = get_role('seo_content')) { $r->add_cap('manage_content_entries'); }
    if ($r = get_role('administrator')) { $r->add_cap('manage_content_entries'); }
});

// Enqueue assets
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('hjseo-style', HJSEO_URI . '/style.css', [], HJSEO_VERSION);
    wp_enqueue_style('hjseo-custom', HJSEO_URI . '/assets/css/custom.css', ['hjseo-style'], HJSEO_VERSION);
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true);
    wp_enqueue_script('hjseo-charts', HJSEO_URI . '/assets/js/charts.js', ['chartjs'], HJSEO_VERSION, true);
    wp_enqueue_script('hjseo-main', HJSEO_URI . '/assets/js/main.js', ['hjseo-charts'], HJSEO_VERSION, true);
    // Localize available task lists for modal selects
    if (function_exists('get_terms')) {
        $terms = get_terms(['taxonomy'=>'seo_task_list','hide_empty'=>false]);
        $payload = [];
        foreach ($terms as $t) {
            $site_id = (int) get_term_meta($t->term_id, 'related_site', true);
            $payload[] = [ 'term_id'=>$t->term_id, 'name'=>$t->name, 'site_id'=>$site_id ];
        }
        // Embed into body attribute via inline script
        $json = wp_json_encode($payload);
        wp_add_inline_script('hjseo-main', 'document.body.setAttribute("data-tasklists",'. $json .');', 'before');
    }
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

// Block wp-admin for non-admin users (frontend-only editing)
add_action('admin_init', function(){
    if (!current_user_can('administrator') && !wp_doing_ajax()) {
        $pagenow = isset($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : '';
        if ($pagenow !== 'admin-ajax.php') {
            wp_redirect(home_url('/'));
            exit;
        }
    }
});

// Frontend Task create handler
add_action('admin_post_hjseo_task_create', function(){
    if (!current_user_can('manage_seo_tasks') && !current_user_can('administrator')) wp_die('Forbidden');
    check_admin_referer('hjseo_task_create');
    $title = sanitize_text_field($_POST['title'] ?? '');
    $content = wp_kses_post($_POST['content'] ?? '');
    $site_id = (int)($_POST['site_id'] ?? 0);
    $term_id = (int)($_POST['task_list_term'] ?? 0);
    $priority = sanitize_text_field($_POST['priority'] ?? 'medium');
    $due = sanitize_text_field($_POST['due_date'] ?? '');
    $notes = wp_kses_post($_POST['notes'] ?? '');
    if (!$title || !$site_id) { wp_redirect(add_query_arg('hj_task','fail', wp_get_referer() ?: home_url('/tasks'))); exit; }
    $id = wp_insert_post([ 'post_type'=>'seo_task', 'post_title'=>$title, 'post_content'=>$content, 'post_status'=>'publish' ]);
    if (!is_wp_error($id)) {
        update_post_meta($id, 'related_site', $site_id);
        if ($term_id) wp_set_post_terms($id, [$term_id], 'seo_task_list', false);
        update_post_meta($id, 'priority', $priority);
        update_post_meta($id, 'status', 'todo');
        if ($due) update_post_meta($id, 'due_date', $due);
        if ($notes) update_post_meta($id, 'notes', $notes);
        wp_redirect(add_query_arg('hj_task','ok', wp_get_referer() ?: home_url('/tasks')));
    } else {
        wp_redirect(add_query_arg('hj_task','fail', wp_get_referer() ?: home_url('/tasks')));
    }
    exit;
});

// Frontend Task complete handler
add_action('admin_post_hjseo_task_complete', function(){
    if (!current_user_can('complete_seo_tasks') && !current_user_can('manage_seo_tasks') && !current_user_can('administrator')) wp_die('Forbidden');
    check_admin_referer('hjseo_task_complete');
    $task_id = (int)($_POST['task_id'] ?? 0);
    if ($task_id) update_post_meta($task_id, 'status', 'done');
    wp_redirect(wp_get_referer() ?: home_url('/tasks'));
    exit;
});

// Frontend Task edit handler
add_action('admin_post_hjseo_task_edit', function(){
    if (!current_user_can('manage_seo_tasks') && !current_user_can('administrator')) wp_die('Forbidden');
    check_admin_referer('hjseo_task_edit');
    $id = (int)($_POST['task_id'] ?? 0);
    if (!$id) wp_die('Task missing');
    $title = sanitize_text_field($_POST['title'] ?? '');
    $site_id = (int)($_POST['site_id'] ?? 0);
    $priority = sanitize_text_field($_POST['priority'] ?? 'medium');
    $status = sanitize_text_field($_POST['status'] ?? 'todo');
    $due = sanitize_text_field($_POST['due_date'] ?? '');
    $notes = wp_kses_post($_POST['notes'] ?? '');
    $term_id = (int)($_POST['task_list_term'] ?? 0);
    if ($title) wp_update_post(['ID'=>$id,'post_title'=>$title]);
    if ($site_id) update_post_meta($id,'related_site',$site_id);
    update_post_meta($id,'priority',$priority);
    update_post_meta($id,'status',$status);
    update_post_meta($id,'due_date',$due);
    update_post_meta($id,'notes',$notes);
    if ($term_id) wp_set_post_terms($id, [$term_id], 'seo_task_list', false);
    wp_redirect(wp_get_referer() ?: home_url('/tasks'));
    exit;
});

// Frontend Task delete handler
add_action('admin_post_hjseo_task_delete', function(){
    if (!current_user_can('manage_seo_tasks') && !current_user_can('administrator')) wp_die('Forbidden');
    check_admin_referer('hjseo_task_delete');
    $task_id = (int)($_POST['task_id'] ?? 0);
    if ($task_id) wp_delete_post($task_id, true);
    wp_redirect(wp_get_referer() ?: home_url('/tasks'));
    exit;
});

// Create new task list (taxonomy term) with site binding
add_action('admin_post_hjseo_tasklist_create', function(){
    if (!current_user_can('manage_seo_tasks') && !current_user_can('administrator')) wp_die('Forbidden');
    check_admin_referer('hjseo_tasklist_create');
    $name = sanitize_text_field($_POST['list_name'] ?? '');
    $site_id = (int)($_POST['site_id'] ?? 0);
    if (!$name || !$site_id) { wp_redirect(wp_get_referer() ?: home_url('/tasks')); exit; }
    $term = wp_insert_term($name, 'seo_task_list');
    if (!is_wp_error($term)) { update_term_meta($term['term_id'], 'related_site', $site_id); }
    wp_redirect(wp_get_referer() ?: home_url('/tasks'));
    exit;
});

// Settings: update API keys (OpenAI)
add_action('admin_post_hjseo_update_api_keys', function(){
    if (!current_user_can('administrator')) wp_die('Forbidden');
    check_admin_referer('hjseo_update_api_keys');
    $openai = sanitize_text_field($_POST['openai_api_key'] ?? '');
    update_option('hjseo_openai_api_key', $openai);
    wp_redirect(wp_get_referer() ?: home_url('/settings'));
    exit;
});

// Settings: update user profile
add_action('admin_post_hjseo_update_user', function(){
    if (!is_user_logged_in()) wp_die('Forbidden');
    check_admin_referer('hjseo_update_user');
    $uid = get_current_user_id();
    $args = ['ID'=>$uid];
    if (!empty($_POST['display_name'])) $args['display_name'] = sanitize_text_field($_POST['display_name']);
    if (!empty($_POST['user_email'])) $args['user_email'] = sanitize_email($_POST['user_email']);
    if (!empty($_POST['user_pass'])) {
        if (($_POST['user_pass'] ?? '') === ($_POST['user_pass2'] ?? '')) $args['user_pass'] = $_POST['user_pass'];
    }
    wp_update_user($args);
    wp_redirect(wp_get_referer() ?: home_url('/settings'));
    exit;
});

// Task list delete
add_action('admin_post_hjseo_tasklist_delete', function(){
    if (!current_user_can('manage_seo_tasks') && !current_user_can('administrator')) wp_die('Forbidden');
    check_admin_referer('hjseo_tasklist_delete');
    $term_id = (int)($_POST['term_id'] ?? 0);
    if ($term_id) wp_delete_term($term_id, 'seo_task_list');
    wp_redirect(wp_get_referer() ?: home_url('/settings'));
    exit;
});

// Add Site (frontend modal)
add_action('admin_post_hjseo_add_site', function(){
    if (!current_user_can('manage_seo_tasks') && !current_user_can('administrator')) wp_die('Forbidden');
    check_admin_referer('hjseo_add_site');
    $domain = sanitize_text_field($_POST['site_domain'] ?? '');
    $prop = sanitize_text_field($_POST['gsc_property'] ?? '');
    if (!$domain || !$prop) { wp_redirect(wp_get_referer() ?: home_url('/')); exit; }
    // Normalize title as domain only
    $title = preg_replace('~^https?://~i','',$domain); $title = rtrim($title,'/');
    $post_id = wp_insert_post(['post_type'=>'seo_site','post_status'=>'publish','post_title'=>$title,'post_name'=>sanitize_title($title)]);
    if (!is_wp_error($post_id)) {
        update_post_meta($post_id,'site_domain',$domain);
        update_post_meta($post_id,'gsc_property',$prop);
        update_post_meta($post_id,'active','1');
    }
    wp_redirect(home_url('/sites'));
    exit;
});

// Frontend: create keyword map from CSV
add_action('admin_post_hjseo_kwmap_create', function(){
    if (!current_user_can('manage_seo_tasks') && !current_user_can('manage_content_entries') && !current_user_can('administrator')) wp_die('Forbidden');
    check_admin_referer('hjseo_kwmap_create');
    $site_id = (int)($_POST['site_id'] ?? 0);
    $csv = trim((string)($_POST['csv'] ?? ''));
    if (!$site_id || !$csv) { wp_redirect(wp_get_referer() ?: home_url('/')); exit; }
    $post_id = wp_insert_post(['post_type'=>'keyword_map','post_title'=>'Keyword Map '.date('Y-m-d'),'post_status'=>'publish']);
    if (is_wp_error($post_id)) { wp_redirect(wp_get_referer()); exit; }
    update_post_meta($post_id,'related_site',$site_id);
    $rows = [];
    foreach (preg_split('/\r?\n/', $csv) as $line) {
        $cols = array_map('trim', str_getcsv($line));
        if (count($cols) < 2 || !$cols[0]) continue;
        $rows[] = [
          'keyword' => $cols[0],
          'url' => $cols[1] ?? '',
          'search_volume' => (int)($cols[2] ?? 0),
          'difficulty' => (int)($cols[3] ?? 0),
          'intent' => $cols[4] ?? '',
          'ctr_potential' => (float)($cols[5] ?? 0),
          'notes' => $cols[6] ?? '',
        ];
    }
    if ($rows) update_field('keywords', $rows, $post_id);
    wp_redirect(wp_get_referer() ?: home_url('/'));
    exit;
});

// Frontend: create content plan item
add_action('admin_post_hjseo_contentplan_create', function(){
    if (!current_user_can('manage_seo_tasks') && !current_user_can('manage_content_entries') && !current_user_can('administrator')) wp_die('Forbidden');
    check_admin_referer('hjseo_contentplan_create');
    $site_id = (int)($_POST['site_id'] ?? 0);
    $week = sanitize_text_field($_POST['week'] ?? '');
    $title = sanitize_text_field($_POST['blog_title'] ?? '');
    $keyword = sanitize_text_field($_POST['keyword_focus'] ?? '');
    $goal = sanitize_text_field($_POST['goal'] ?? '');
    $format = sanitize_text_field($_POST['format'] ?? '');
    $cta = sanitize_text_field($_POST['cta'] ?? '');
    if (!$site_id || !$title) { wp_redirect(wp_get_referer() ?: home_url('/')); exit; }
    $post_id = wp_insert_post(['post_type'=>'content_plan','post_title'=>$title,'post_status'=>'publish']);
    if (!is_wp_error($post_id)) {
        update_post_meta($post_id,'related_site',$site_id);
        update_post_meta($post_id,'week',$week);
        update_post_meta($post_id,'blog_title',$title);
        update_post_meta($post_id,'keyword_focus',$keyword);
        update_post_meta($post_id,'goal',$goal);
        update_post_meta($post_id,'format',$format);
        update_post_meta($post_id,'cta',$cta);
    }
    wp_redirect(wp_get_referer() ?: home_url('/'));
    exit;
});

// Activation/deactivation hooks for rewrite rules
add_action('after_switch_theme', function () {
    if (function_exists('hjseo_register_rewrites')) {
        hjseo_register_rewrites();
    }
    flush_rewrite_rules();
});

add_action('switch_theme', function () { flush_rewrite_rules(); });
