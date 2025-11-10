<?php
if (!defined('ABSPATH')) { exit; }

// Allow SVG uploads for administrators only (safer by default)
add_filter('upload_mimes', function($mimes){
  if (current_user_can('manage_options')) {
    $mimes['svg'] = 'image/svg+xml';
  }
  return $mimes;
});

// Fix filetype detection to allow SVG
add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes){
  $ext = pathinfo($filename, PATHINFO_EXTENSION);
  if (strtolower($ext) === 'svg') {
    $data['ext'] = 'svg';
    $data['type'] = 'image/svg+xml';
    $data['proper_filename'] = $filename;
  }
  return $data;
}, 10, 4);

// Make SVGs render nicely in media grid (admin only)
add_action('admin_head', function(){
  echo '<style>.attachment .thumbnail img[src$=".svg"], .media-icon img[src$=".svg"], .thumbnail img[src*="mime_image"]{ width:100% !important; height:auto !important; }</style>';
});
