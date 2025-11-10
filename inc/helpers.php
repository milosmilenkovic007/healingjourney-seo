<?php
if (!defined('ABSPATH')) { exit; }

// Graceful fallback if ACF is not active: provide a minimal get_field shim.
// Wrapper: use hjseo_field() instead of direct get_field() so theme survives without ACF.
function hjseo_field($key, $post_id = null) {
  if (function_exists('get_field')) {
    return get_field($key, $post_id);
  }
  if ($post_id === null) {
    $post_id = get_the_ID();
  }
  return get_post_meta($post_id, $key, true);
}

function hjseo_number($n) {
  if ($n === null || $n === '') return '–';
  if ($n >= 1000000) return round($n/1000000,1) . 'M';
  if ($n >= 1000) return round($n/1000,1) . 'K';
  return (string)$n;
}

function hjseo_avg($arr) {
  $arr = array_filter($arr, fn($v) => is_numeric($v));
  if (!$arr) return 0;
  return array_sum($arr)/count($arr);
}

// Update ACF/meta field wrapper to write values regardless of ACF presence
function hjseo_update_field_value($key, $value, $post_id) {
  if (function_exists('update_field')) {
    return update_field($key, $value, $post_id);
  }
  return update_post_meta($post_id, $key, $value);
}

function hjseo_get_site_metrics($site_id) {
  // Support new canonical keys first, then legacy fallback
  $authority = hjseo_field('authority', $site_id);
  if ($authority === '' || $authority === null) $authority = hjseo_field('authority_score', $site_id);

  $backlinks = hjseo_field('backlinks', $site_id);
  $ref_domains = hjseo_field('ref_domains', $site_id);
  if ($ref_domains === '' || $ref_domains === null) $ref_domains = hjseo_field('referring_domains', $site_id);
  $keywords = hjseo_field('keywords', $site_id);
  if ($keywords === '' || $keywords === null) $keywords = hjseo_field('keywords_count', $site_id);
  $visibility = hjseo_field('visibility', $site_id);
  return [
    'authority' => $authority,
    'backlinks' => $backlinks,
    'ref_domains' => $ref_domains,
    'keywords' => $keywords,
    'visibility' => $visibility,
  ];
}

function hjseo_render_metrics_row($metrics) {
  $out = '';
  foreach ($metrics as $label => $value) {
    $out .= '<div class="metric"><div class="label">' . esc_html(ucwords(str_replace('_',' ', $label))) . '</div><div class="value">' . esc_html(hjseo_number($value)) . '</div></div>';
  }
  return '<div class="metrics">' . $out . '</div>';
}
