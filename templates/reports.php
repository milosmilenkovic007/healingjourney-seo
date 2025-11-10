<?php
/** Template for /reports */
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<header class="site-header">
  <div class="container">
    <div class="brand"><span class="dot"></span> <span>HealingJourney SEO</span></div>
    <nav><a href="/sites">Sites</a> · <a href="/reports">Reports</a></nav>
  </div>
</header>
<main class="container">
  <h1 class="m-0">SEO Reports</h1>
  <form method="get" class="mt-24 flex items-center" style="gap:12px;">
    <?php $sites = get_posts(['post_type' => 'seo_site', 'posts_per_page' => -1]); ?>
    <select name="site">
      <option value="">All Sites</option>
      <?php foreach ($sites as $s): $slug = $s->post_name; ?>
        <option value="<?php echo esc_attr($slug); ?>" <?php selected(isset($_GET['site']) && $_GET['site']===$slug); ?>><?php echo esc_html($s->post_title); ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="month" placeholder="YYYY-MM" value="<?php echo isset($_GET['month'])? esc_attr($_GET['month']):''; ?>" class="input" />
    <button class="btn" type="submit">Filter</button>
  </form>
  <?php
    $meta = [];
    if (!empty($_GET['site'])) {
      $site_obj = get_page_by_path(sanitize_title($_GET['site']), OBJECT, 'seo_site');
      if ($site_obj) { $meta[] = [ 'key' => 'site', 'value' => $site_obj->ID ]; }
    }
    if (!empty($_GET['month'])) { $meta[] = [ 'key' => 'month', 'value' => sanitize_text_field($_GET['month']) ]; }
    $args = ['post_type' => 'seo_report', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC'];
    if ($meta) $args['meta_query'] = $meta;
    $q = new WP_Query($args);
    if ($q->have_posts()):
      echo '<div class="table-wrap mt-24"><table class="table"><thead><tr><th>Site</th><th>Month</th><th>Technical</th><th>On-page</th><th>Backlinks</th><th>Summary</th></tr></thead><tbody>';
      while ($q->have_posts()): $q->the_post();
  $site_id = hjseo_field('site');
        echo '<tr>'
          . '<td>' . esc_html(get_the_title($site_id)) . '</td>'
          . '<td>' . esc_html(hjseo_field('month')) . '</td>'
          . '<td>' . esc_html(wp_trim_words(wp_strip_all_tags(hjseo_field('technical_analysis')), 8)) . '</td>'
          . '<td>' . esc_html(wp_trim_words(wp_strip_all_tags(hjseo_field('onpage_analysis')), 8)) . '</td>'
          . '<td>' . esc_html(wp_trim_words(wp_strip_all_tags(hjseo_field('backlink_analysis')), 8)) . '</td>'
          . '<td>' . esc_html(wp_trim_words(wp_strip_all_tags(hjseo_field('performance_summary')), 10)) . '</td>'
          . '</tr>';
      endwhile; wp_reset_postdata();
      echo '</tbody></table></div>';
    else:
      echo '<p class="mt-24">No reports match filters.</p>';
    endif;
  ?>
</main>
<?php get_footer(); ?>
