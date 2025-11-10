<?php
if (!defined('ABSPATH')) { exit; }

// Register ACF local field groups if ACF is active
add_action('init', function() {
  if (!function_exists('acf_add_local_field_group')) return;

  // Sites fields
  acf_add_local_field_group([
    'key' => 'group_hjseo_sites',
    'title' => 'Site Details',
    'fields' => [
      [ 'key' => 'field_hjseo_site_domain', 'label' => 'Site Domain', 'name' => 'site_domain', 'type' => 'text', 'required' => 1 ],
      [ 'key' => 'field_hjseo_logo', 'label' => 'Logo', 'name' => 'logo', 'type' => 'image', 'return_format' => 'array' ],
      [ 'key' => 'field_hjseo_authority', 'label' => 'Authority (DA)', 'name' => 'authority', 'type' => 'number', 'min' => 0, 'max' => 100 ],
      [ 'key' => 'field_hjseo_backlinks', 'label' => 'Backlinks', 'name' => 'backlinks', 'type' => 'number', 'min' => 0 ],
      [ 'key' => 'field_hjseo_refdomains', 'label' => 'Referring Domains', 'name' => 'ref_domains', 'type' => 'number', 'min' => 0 ],
      [ 'key' => 'field_hjseo_keywords', 'label' => 'Keywords (Queries)', 'name' => 'keywords', 'type' => 'number', 'min' => 0 ],
      [ 'key' => 'field_hjseo_visibility', 'label' => 'Visibility %', 'name' => 'visibility', 'type' => 'number', 'min' => 0, 'max' => 100, 'step' => 0.01 ],
      [ 'key' => 'field_hjseo_last_synced', 'label' => 'Last Synced', 'name' => 'last_synced', 'type' => 'date_time_picker', 'display_format' => 'Y-m-d H:i', 'return_format' => 'Y-m-d H:i:s' ],
      [ 'key' => 'field_hjseo_gsc_property', 'label' => 'GSC Property', 'name' => 'gsc_property', 'type' => 'text' ],
      [ 'key' => 'field_hjseo_active', 'label' => 'Active', 'name' => 'active', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1 ],
    ],
    'location' => [[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'seo_site' ]]],
  ]);

  // SEO Reports fields
  acf_add_local_field_group([
    'key' => 'group_hjseo_reports',
    'title' => 'SEO Report Details',
    'fields' => [
      [ 'key' => 'field_hjseo_report_related_site', 'label' => 'Related Site', 'name' => 'related_site', 'type' => 'post_object', 'post_type' => ['seo_site'], 'return_format' => 'id', 'required' => 1 ],
      [ 'key' => 'field_hjseo_report_month', 'label' => 'Month', 'name' => 'month', 'type' => 'text', 'instructions' => 'Format: YYYY-MM', 'required' => 1 ],
      [ 'key' => 'field_hjseo_report_tech', 'label' => 'Technical Analysis', 'name' => 'technical_analysis', 'type' => 'wysiwyg' ],
      [ 'key' => 'field_hjseo_report_onpage', 'label' => 'On-page Analysis', 'name' => 'onpage_analysis', 'type' => 'wysiwyg' ],
      [ 'key' => 'field_hjseo_report_backlink', 'label' => 'Backlink Analysis', 'name' => 'backlink_analysis', 'type' => 'wysiwyg' ],
      [ 'key' => 'field_hjseo_report_perf', 'label' => 'Performance Summary', 'name' => 'performance_summary', 'type' => 'wysiwyg' ],
    ],
    'location' => [[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'seo_report' ]]],
  ]);

  // Keyword Maps fields (repeater rows)
  acf_add_local_field_group([
    'key' => 'group_hjseo_kwmap',
    'title' => 'Keyword Map',
    'fields' => [
      [ 'key' => 'field_hjseo_kwmap_related_site', 'label' => 'Related Site', 'name' => 'related_site', 'type' => 'post_object', 'post_type' => ['seo_site'], 'return_format' => 'id', 'required' => 1 ],
      [ 'key' => 'field_hjseo_kw_rows', 'label' => 'Keywords', 'name' => 'keywords', 'type' => 'repeater', 'collapsed' => 'field_hjseo_kw_keyword', 'layout' => 'table', 'button_label' => '+ Add Row', 'sub_fields' => [
        [ 'key' => 'field_hjseo_kw_keyword', 'label' => 'Keyword', 'name' => 'keyword', 'type' => 'text' ],
        [ 'key' => 'field_hjseo_kw_url', 'label' => 'URL', 'name' => 'url', 'type' => 'url' ],
        [ 'key' => 'field_hjseo_kw_searchvol', 'label' => 'Search Volume', 'name' => 'search_volume', 'type' => 'number' ],
        [ 'key' => 'field_hjseo_kw_diff', 'label' => 'Difficulty', 'name' => 'difficulty', 'type' => 'number' ],
        [ 'key' => 'field_hjseo_kw_intent', 'label' => 'Intent', 'name' => 'intent', 'type' => 'select', 'choices' => [ 'info' => 'Informational', 'buy' => 'Transactional', 'nav' => 'Navigational', 'learn' => 'Educational' ] ],
        [ 'key' => 'field_hjseo_kw_ctr', 'label' => 'CTR Potential %', 'name' => 'ctr_potential', 'type' => 'number', 'step' => 0.1 ],
        [ 'key' => 'field_hjseo_kw_notes', 'label' => 'Notes', 'name' => 'notes', 'type' => 'text' ],
      ]],
    ],
    'location' => [[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'keyword_map' ]]],
  ]);

  // Content Plans fields
  acf_add_local_field_group([
    'key' => 'group_hjseo_contentplan',
    'title' => 'Content Plan',
    'fields' => [
      [ 'key' => 'field_hjseo_cp_related_site', 'label' => 'Related Site', 'name' => 'related_site', 'type' => 'post_object', 'post_type' => ['seo_site'], 'return_format' => 'id', 'required' => 1 ],
      [ 'key' => 'field_hjseo_cp_week', 'label' => 'Week', 'name' => 'week', 'type' => 'text', 'instructions' => 'Format: YYYY-Www (e.g., 2025-W45)' ],
      [ 'key' => 'field_hjseo_cp_title', 'label' => 'Blog Title', 'name' => 'blog_title', 'type' => 'text' ],
      [ 'key' => 'field_hjseo_cp_keyword', 'label' => 'Keyword Focus', 'name' => 'keyword_focus', 'type' => 'text' ],
      [ 'key' => 'field_hjseo_cp_goal', 'label' => 'Goal', 'name' => 'goal', 'type' => 'textarea' ],
      [ 'key' => 'field_hjseo_cp_format', 'label' => 'Format', 'name' => 'format', 'type' => 'text' ],
      [ 'key' => 'field_hjseo_cp_cta', 'label' => 'CTA', 'name' => 'cta', 'type' => 'text' ],
    ],
    'location' => [[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'content_plan' ]]],
  ]);
});
