<?php
if (!defined('ABSPATH')) { exit; }

function hjseo_register_rewrites() {
  add_rewrite_rule('^sites/?$', 'index.php?hjseo_sites=1', 'top');
  add_rewrite_rule('^reports/?$', 'index.php?hjseo_reports=1', 'top');
  add_rewrite_rule('^site/([^/]+)/?$', 'index.php?hjseo_site=$matches[1]', 'top');
}
add_action('init', 'hjseo_register_rewrites');

add_filter('query_vars', function($vars){
  $vars[] = 'hjseo_sites';
  $vars[] = 'hjseo_reports';
  $vars[] = 'hjseo_site';
  return $vars;
});

add_filter('template_include', function($template){
  if (get_query_var('hjseo_sites')) {
    $tpl = HJSEO_DIR . '/templates/sites.php';
    if (file_exists($tpl)) return $tpl;
  }
  if (get_query_var('hjseo_reports')) {
    $tpl = HJSEO_DIR . '/templates/reports.php';
    if (file_exists($tpl)) return $tpl;
  }
  $site_slug = get_query_var('hjseo_site');
  if ($site_slug) {
    $tpl = HJSEO_DIR . '/templates/site-single.php';
    if (file_exists($tpl)) return $tpl;
  }
  return $template;
});
