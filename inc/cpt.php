<?php
if (!defined('ABSPATH')) { exit; }

add_action('init', function() {
  // Sites
  register_post_type('seo_site', [
    'label' => 'Sites',
    'public' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-admin-site',
    'supports' => ['title','thumbnail'],
    'rewrite' => ['slug' => 'site'],
  ]);

  // Reports
  register_post_type('seo_report', [
    'label' => 'SEO Reports',
    'public' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-chart-bar',
    'supports' => ['title','editor'],
    'rewrite' => ['slug' => 'seo-report'],
  ]);

  // Keyword Maps
  register_post_type('keyword_map', [
    'label' => 'Keyword Maps',
    'public' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-list-view',
    'supports' => ['title'],
    'rewrite' => ['slug' => 'keyword-map'],
  ]);

  // Content Plans
  register_post_type('content_plan', [
    'label' => 'Content Plans',
    'public' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-calendar-alt',
    'supports' => ['title'],
    'rewrite' => ['slug' => 'content-plan'],
  ]);
});

// Force redirect back to the seo_site edit screen after save (avoid falling back to Posts list)
add_filter('redirect_post_location', function($location, $post_id){
  $post = get_post($post_id);
  if ($post && $post->post_type === 'seo_site') {
    // Ensure we're returning to the edit screen for this post type
    $location = add_query_arg(['post' => $post_id, 'action' => 'edit'], admin_url('post.php'));
  }
  return $location;
}, 10, 2);
