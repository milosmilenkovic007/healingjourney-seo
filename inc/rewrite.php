<?php
if (!defined('ABSPATH')) { exit; }

function hjseo_register_rewrites() {
  add_rewrite_rule('^sites/?$', 'index.php?hjseo_sites=1', 'top');
  add_rewrite_rule('^tasks/?$', 'index.php?hjseo_tasks=1', 'top');
  add_rewrite_rule('^dashboard/?$', 'index.php?hjseo_dashboard=1', 'top');
  add_rewrite_rule('^settings/?$', 'index.php?hjseo_settings=1', 'top');
  add_rewrite_rule('^site/([^/]+)/?$', 'index.php?hjseo_site=$matches[1]', 'top');
}
add_action('init', 'hjseo_register_rewrites');

add_filter('query_vars', function($vars){
  $vars[] = 'hjseo_sites';
  $vars[] = 'hjseo_tasks';
  $vars[] = 'hjseo_dashboard';
  $vars[] = 'hjseo_settings';
  $vars[] = 'hjseo_site';
  return $vars;
});

add_filter('template_include', function($template){
  if (get_query_var('hjseo_sites')) {
    $tpl = HJSEO_DIR . '/templates/sites.php';
    if (file_exists($tpl)) return $tpl;
  }
  if (get_query_var('hjseo_tasks')) {
    $tpl = HJSEO_DIR . '/templates/tasks.php';
    if (file_exists($tpl)) return $tpl;
  }
  if (get_query_var('hjseo_dashboard')) {
    $tpl = HJSEO_DIR . '/templates/dashboard.php';
    if (file_exists($tpl)) return $tpl;
  }
  if (get_query_var('hjseo_settings')) {
    $tpl = HJSEO_DIR . '/templates/settings.php';
    if (file_exists($tpl)) return $tpl;
  }
  $site_slug = get_query_var('hjseo_site');
  if ($site_slug) {
    $tpl = HJSEO_DIR . '/templates/site-single.php';
    if (file_exists($tpl)) return $tpl;
  }
  return $template;
});
