<?php
/** Fallback index template (required). */
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<main class="container">
  <h1>Latest Content</h1>
  <?php if (have_posts()): echo '<div class="grid grid-cols-3">';
    while (have_posts()): the_post();
      echo '<div class="card"><h3 class="m-0"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
      echo '<p class="small">' . esc_html(get_post_type()) . '</p>';
      echo '<p>' . esc_html(wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 20)) . '</p>';
      echo '</div>';
    endwhile; echo '</div>';
    the_posts_pagination();
  else:
    echo '<p>No posts found.</p>';
  endif; ?>
</main>
<?php get_footer(); ?>
