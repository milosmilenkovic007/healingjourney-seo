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
  <h1 class="m-0">All Sites</h1>
  <?php
    $q = new WP_Query(['post_type' => 'seo_site', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    if ($q->have_posts()):
      echo '<div class="grid grid-cols-3 mt-16">';
      while ($q->have_posts()): $q->the_post();
        $metrics = hjseo_get_site_metrics(get_the_ID());
  $domain = hjseo_field('domain');
        echo '<div class="card">';
        echo '<div class="card-header"><h3 class="m-0"><a href="' . esc_url('/site/' . sanitize_title($post->post_name)) . '">' . esc_html(get_the_title()) . '</a></h3><span class="small">' . esc_html($domain) . '</span></div>';
        echo hjseo_render_metrics_row($metrics);
        echo '</div>';
      endwhile; wp_reset_postdata();
      echo '</div>';
    else:
      echo '<p>No sites found.</p>';
    endif;
  ?>
</main>
<?php get_footer(); ?>
