<?php
if (!defined('ABSPATH')) { exit; }

// [seo_site_list]
add_shortcode('seo_site_list', function($atts){
  $q = new WP_Query(['post_type' => 'seo_site', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
  if (!$q->have_posts()) return '<p>No sites found.</p>';
  $out = '<div class="grid grid-cols-3">';
  while ($q->have_posts()) { $q->the_post();
    $metrics = hjseo_get_site_metrics(get_the_ID());
    $out .= '<div class="card"><h3 class="m-0"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>' . hjseo_render_metrics_row($metrics) . '</div>';
  }
  wp_reset_postdata();
  return $out . '</div>';
});

// [seo_report_table site="slug"]
add_shortcode('seo_report_table', function($atts){
  $site_slug = $atts['site'] ?? '';
  if (!$site_slug) return '<p>Missing site attribute.</p>';
  $site = get_page_by_path($site_slug, OBJECT, 'seo_site');
  if (!$site) return '<p>Site not found.</p>';
  $q = new WP_Query(['post_type' => 'seo_report', 'posts_per_page' => -1, 'meta_query' => [ [ 'key' => 'related_site', 'value' => $site->ID ] ], 'orderby' => 'date', 'order' => 'DESC']);
  if (!$q->have_posts()) return '<p>No reports.</p>';
  $out = '<div class="table-wrap"><table class="table"><thead><tr><th>Month</th><th>Performance Summary</th></tr></thead><tbody>';
  while ($q->have_posts()) { $q->the_post();
  $month = hjseo_field('month');
  $perf = wp_strip_all_tags(hjseo_field('performance_summary'));
    $out .= '<tr><td>' . esc_html($month) . '</td><td>' . esc_html(wp_trim_words($perf, 25)) . '</td></tr>';
  }
  wp_reset_postdata();
  return $out . '</tbody></table></div>';
});

// [seo_keyword_map site="slug"]
add_shortcode('seo_keyword_map', function($atts){
  $site_slug = $atts['site'] ?? '';
  if (!$site_slug) return '<p>Missing site attribute.</p>';
  $site = get_page_by_path($site_slug, OBJECT, 'seo_site');
  if (!$site) return '<p>Site not found.</p>';
  $q = new WP_Query(['post_type' => 'keyword_map', 'posts_per_page' => 1, 'meta_query' => [ [ 'key' => 'related_site', 'value' => $site->ID ] ], 'orderby' => 'date', 'order' => 'DESC']);
  if (!$q->have_posts()) return '<p>No keyword map.</p>';
  $q->the_post();
  $rows = hjseo_field('keywords');
  wp_reset_postdata();
  if (!$rows) return '<p>No keywords.</p>';
  $out = '<div class="table-wrap"><table class="table"><thead><tr><th>Keyword</th><th>URL</th><th>Volume</th><th>Difficulty</th><th>Intent</th><th>CTR %</th><th>Notes</th></tr></thead><tbody>';
  foreach ($rows as $r) {
    $intent = $r['intent'] ?? '';
    $out .= '<tr>'
      . '<td>' . esc_html($r['keyword']) . '</td>'
      . '<td><a href="' . esc_url($r['url']) . '" target="_blank">' . esc_html(parse_url($r['url'], PHP_URL_PATH)) . '</a></td>'
      . '<td>' . esc_html(hjseo_number($r['search_volume'])) . '</td>'
      . '<td>' . esc_html($r['difficulty']) . '</td>'
      . '<td><span class="intent ' . esc_attr($intent) . '">' . esc_html(ucfirst($intent)) . '</span></td>'
      . '<td>' . esc_html($r['ctr_potential']) . '</td>'
      . '<td>' . esc_html($r['notes']) . '</td>'
      . '</tr>';
  }
  return $out . '</tbody></table></div>';
});

// [seo_content_plan site="slug"]
add_shortcode('seo_content_plan', function($atts){
  $site_slug = $atts['site'] ?? '';
  if (!$site_slug) return '<p>Missing site attribute.</p>';
  $site = get_page_by_path($site_slug, OBJECT, 'seo_site');
  if (!$site) return '<p>Site not found.</p>';
  $q = new WP_Query(['post_type' => 'content_plan', 'posts_per_page' => -1, 'meta_query' => [ [ 'key' => 'related_site', 'value' => $site->ID ] ], 'orderby' => 'date', 'order' => 'DESC']);
  if (!$q->have_posts()) return '<p>No content plan items.</p>';
  $out = '<div class="timeline">';
  while ($q->have_posts()) { $q->the_post();
    $out .= '<div class="timeline-item">'
  . '<div class="small">' . esc_html(hjseo_field('week')) . '</div>'
  . '<div class="title">' . esc_html(hjseo_field('blog_title')) . '</div>'
  . '<div class="small">Keyword: ' . esc_html(hjseo_field('keyword_focus')) . ' | Format: ' . esc_html(hjseo_field('format')) . '</div>'
  . '<div>' . esc_html(hjseo_field('goal')) . '</div>'
  . '<div class="small">CTA: ' . esc_html(hjseo_field('cta')) . '</div>'
      . '</div>';
  }
  wp_reset_postdata();
  return $out . '</div>';
});
