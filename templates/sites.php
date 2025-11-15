<?php
/** Template for /sites */
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
  <div class="flex items-center justify-between" style="margin-bottom: 2rem;">
    <div>
      <h1 class="m-0">All Sites</h1>
      <p class="small" style="margin-top: 0.5rem;">Monitor and manage your SEO portfolio</p>
    </div>
    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=seo_site')); ?>" class="hjseo-btn hjseo-btn-primary">
      <span>➕</span> Add New Site
    </a>
  </div>
  
  <?php
    $q = new WP_Query(['post_type' => 'seo_site', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    if ($q->have_posts()):
      echo '<div class="hjseo-sites-grid">';
      while ($q->have_posts()): $q->the_post();
  $metrics = hjseo_get_site_metrics(get_the_ID());
  $domain = hjseo_field('site_domain');
  if (!$domain) { $domain = hjseo_field('domain'); }
        $thumb_id = get_post_thumbnail_id(get_the_ID());
        echo '<div class="hjseo-site-card">';
        echo '<div class="hjseo-site-card-header">';
        if ($thumb_id) {
          $thumb_url = wp_get_attachment_image_url($thumb_id, 'medium');
          echo '<img src="' . esc_url($thumb_url) . '" alt="Featured" class="hjseo-site-logo">';
        } else {
          echo '<div class="hjseo-site-logo-placeholder">' . strtoupper(substr($domain, 0, 1)) . '</div>';
        }
        echo '<div class="hjseo-site-info">';
        echo '<h3 class="m-0"><a href="' . esc_url('/site/' . sanitize_title($post->post_name)) . '" class="hjseo-site-link">' . esc_html($domain) . '</a></h3>';
        echo '<span class="small">' . esc_html(get_the_title()) . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="hjseo-site-metrics">';
        echo hjseo_render_metrics_row($metrics);
        echo '</div>';
        echo '</div>';
      endwhile; wp_reset_postdata();
      echo '</div>';
    else:
      echo '<div class="hjseo-empty-state">';
      echo '<h3>No sites found</h3>';
      echo '<p>Get started by adding your first site to monitor.</p>';
      echo '<a href="' . esc_url(admin_url('post-new.php?post_type=seo_site')) . '" class="hjseo-btn hjseo-btn-primary">Add Site</a>';
      echo '</div>';
    endif;
  ?>
</main>
<?php get_footer(); ?>
