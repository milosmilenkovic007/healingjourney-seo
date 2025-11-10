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
