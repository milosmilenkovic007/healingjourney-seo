<?php
/** Admin columns for seo_site CPT */
if (!defined('ABSPATH')) { exit; }

add_filter('manage_edit-seo_site_columns', function($cols){
    $new = [];
    foreach ($cols as $k=>$v) {
        if ($k === 'date') continue; // we'll add later
        $new[$k] = $v;
        if ($k === 'title') {
            $new['active'] = 'Status';
            $new['authority'] = 'Authority';
            $new['backlinks'] = 'Backlinks';
            $new['ref_domains'] = 'Ref Domains';
            $new['keywords'] = 'Keywords';
            $new['visibility'] = 'Visibility %';
            $new['last_synced'] = 'Last Synced';
        }
    }
    $new['date'] = $cols['date'];
    return $new;
});

add_action('manage_seo_site_posts_custom_column', function($col, $post_id){
    switch ($col) {
        case 'active':
            $active = hjseo_field('active', $post_id);
            $label = $active==='1' ? 'Active' : 'Inactive';
            $color = $active==='1' ? '#2ecc71' : '#e67e22';
            echo '<span style="background:' . esc_attr($color) . ';color:#fff;padding:2px 6px;border-radius:8px;font-size:11px">' . esc_html($label) . '</span>';
            // Quick toggle inline action
            $toggle_url = wp_nonce_url( add_query_arg(['action'=>'hjseo_toggle_active','post'=>$post_id], admin_url('admin-post.php')), 'hjseo_toggle_active_' . $post_id );
            echo ' <a class="button-link" href="' . esc_url($toggle_url) . '" style="margin-left:6px;">Toggle</a>';
            break;
        case 'authority': echo esc_html(hjseo_field('authority', $post_id)); break;
        case 'backlinks': echo esc_html(hjseo_number((int)hjseo_field('backlinks', $post_id))); break;
        case 'ref_domains': echo esc_html(hjseo_number((int)hjseo_field('ref_domains', $post_id))); break;
        case 'keywords': echo esc_html(hjseo_number((int)hjseo_field('keywords', $post_id))); break;
        case 'visibility': $v = hjseo_field('visibility', $post_id); echo $v!==''? esc_html($v.'%') : '—'; break;
        case 'last_synced':
            $dt = hjseo_field('last_synced', $post_id);
            echo $dt ? esc_html(date('Y-m-d H:i', strtotime($dt))) : '—';
            break;
    }
}, 10, 2);

// Handle quick toggle Active flag
add_action('admin_post_hjseo_toggle_active', function(){
    if (!current_user_can('edit_posts')) wp_die('Forbidden');
    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    check_admin_referer('hjseo_toggle_active_' . $post_id);
    $curr = hjseo_field('active', $post_id) === '1' ? '1' : '0';
    hjseo_update_field_value('active', $curr==='1' ? '0' : '1', $post_id);
    wp_safe_redirect( wp_get_referer() ?: admin_url('edit.php?post_type=seo_site') );
    exit;
});

add_filter('manage_edit-seo_site_sortable_columns', function($cols){
    $cols['authority'] = 'authority';
    $cols['backlinks'] = 'backlinks';
    $cols['keywords'] = 'keywords';
    return $cols;
});
