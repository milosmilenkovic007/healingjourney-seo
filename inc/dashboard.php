<?php
if (!defined('ABSPATH')) { exit; }

add_action('wp_dashboard_setup', function(){
  wp_add_dashboard_widget('hjseo_global_metrics', 'SEO Global Metrics', 'hjseo_dashboard_widget');
});

function hjseo_dashboard_widget() {
  $sites = get_posts(['post_type' => 'seo_site', 'posts_per_page' => -1]);
  if (!$sites) { echo '<p>No sites.</p>'; return; }
  $auth = $back = $kw = [];
  foreach ($sites as $s) {
  $auth[] = (int)hjseo_field('authority_score', $s->ID);
  $back[] = (int)hjseo_field('backlinks', $s->ID);
  $kw[] = (int)hjseo_field('keywords_count', $s->ID);
  }
  echo '<div class="metrics">';
  echo '<div class="metric"><div class="label">Avg Authority</div><div class="value">' . esc_html(round(hjseo_avg($auth),1)) . '</div></div>';
  echo '<div class="metric"><div class="label">Total Backlinks</div><div class="value">' . esc_html(hjseo_number(array_sum($back))) . '</div></div>';
  echo '<div class="metric"><div class="label">Total Keywords</div><div class="value">' . esc_html(hjseo_number(array_sum($kw))) . '</div></div>';
  echo '</div>';
}
