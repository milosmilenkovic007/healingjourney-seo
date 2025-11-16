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

  // Tasks
  register_post_type('seo_task', [
    'label' => 'SEO Tasks',
    'public' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-yes-alt',
    'supports' => ['title','editor'],
    'rewrite' => ['slug' => 'seo-task'],
  ]);

  // Task Lists taxonomy (assignable to tasks)
  register_taxonomy('seo_task_list', ['seo_task'], [
    'labels' => [
      'name' => 'Task Lists',
      'singular_name' => 'Task List',
    ],
    'public' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => ['slug' => 'task-list'],
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
// Remove forced redirect; let WordPress handle normal post save redirect.
add_action('admin_init', function(){
  remove_filter('redirect_post_location', '__return_false'); // placeholder cleanup if any
});
