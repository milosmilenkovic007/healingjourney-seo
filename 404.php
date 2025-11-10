<?php
/** 404 template */
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
  <h1>Not Found</h1>
  <p>The page you requested could not be found.</p>
  <p><a class="btn" href="/sites">View Sites</a></p>
</main>
<?php get_footer(); ?>
