<?php
/** Optional seed script to create initial seo_site posts */
if (!defined('ABSPATH')) { exit; }

function hjseo_seed_sites() {
    $sites = [
        'https://healingjourney.travel/',
        'https://kneesurgeryturkey.com/',
        'https://HealthCheckupIstanbul.com/',
        'https://Themedicorner.com/',
        'https://medicaltravelfacilitator.com/',
        'https://healthcheckupantalya.com/',
    ];
    foreach ($sites as $url) {
        $existing = get_posts(['post_type'=>'seo_site','name'=>sanitize_title($url),'posts_per_page'=>1]);
        if ($existing) continue;
        $post_id = wp_insert_post([
            'post_type' => 'seo_site',
            'post_status' => 'publish',
            'post_title' => parse_url($url, PHP_URL_HOST),
            'post_name' => sanitize_title(parse_url($url, PHP_URL_HOST)),
        ]);
        if ($post_id && !is_wp_error($post_id)) {
            hjseo_update_field_value('site_domain', $url, $post_id);
            hjseo_update_field_value('gsc_property', $url, $post_id);
            hjseo_update_field_value('active', '1', $post_id);
        }
    }
}
// Uncomment to seed automatically on theme switch
// add_action('after_switch_theme','hjseo_seed_sites');

function hjseo_seed_tasks() {
    // Get example users for assignment
    $devs = get_users(['role'=>'seo_developer','number'=>1]);
    $mgrs = get_users(['role'=>'seo_manager','number'=>1]);
    $assignee = $devs ? $devs[0]->ID : 0;

    $sites = get_posts(['post_type'=>'seo_site','posts_per_page'=>-1]);
    foreach ($sites as $s) {
        // Skip if tasks already exist
        $existing = get_posts(['post_type'=>'seo_task','meta_key'=>'related_site','meta_value'=>$s->ID,'posts_per_page'=>1]);
        if ($existing) continue;

        $lists = [
          ['Technical: Fix SSL mismatch', 'technical', 'urgent'],
          ['Content: Write meta titles & descriptions', 'content', 'high'],
          ['Backlinks: Outreach to 10 partners', 'backlinks', 'medium'],
        ];
        foreach ($lists as $i => $row) {
            [$title, $list, $prio] = $row;
            $id = wp_insert_post(['post_type'=>'seo_task','post_status'=>'publish','post_title'=>$title]);
            if (is_wp_error($id) || !$id) continue;
            update_post_meta($id,'related_site',$s->ID);
            update_post_meta($id,'task_list',ucfirst($list));
            update_post_meta($id,'priority',$prio);
            update_post_meta($id,'status','todo');
            update_post_meta($id,'due_date', date('Y-m-d', strtotime('+'.(7+($i*7)).' days')));
            if ($assignee) update_post_meta($id,'assignee',$assignee);
        }
    }
}

/** Ensure default Task Lists per site and assign tasks to lists */
function hjseo_seed_task_lists_and_assign() {
    $sites = get_posts(['post_type'=>'seo_site','posts_per_page'=>-1]);
    foreach ($sites as $s) {
        // Ensure lists
        $list_names = ['Technical','Content','Backlinks'];
        $terms_for_site = [];
        foreach ($list_names as $name) {
            $term = get_term_by('name', $name, 'seo_task_list');
            if (!$term || (int)get_term_meta($term->term_id,'related_site',true)!==$s->ID) {
                $created = wp_insert_term($name, 'seo_task_list');
                if (!is_wp_error($created)) { update_term_meta($created['term_id'],'related_site',$s->ID); $terms_for_site[$name] = (int)$created['term_id']; }
            } else {
                $terms_for_site[$name] = (int)$term->term_id;
            }
        }
        // Assign unassigned tasks by title heuristic
        $tasks = get_posts(['post_type'=>'seo_task','posts_per_page'=>-1,'meta_key'=>'related_site','meta_value'=>$s->ID]);
        foreach ($tasks as $t) {
            $has = wp_get_post_terms($t->ID,'seo_task_list');
            if ($has) continue;
            $title = $t->post_title;
            $term_id = 0;
            if (stripos($title,'Technical')===0 && isset($terms_for_site['Technical'])) $term_id = $terms_for_site['Technical'];
            elseif (stripos($title,'Content')===0 && isset($terms_for_site['Content'])) $term_id = $terms_for_site['Content'];
            elseif (stripos($title,'Backlinks')===0 && isset($terms_for_site['Backlinks'])) $term_id = $terms_for_site['Backlinks'];
            if ($term_id) wp_set_post_terms($t->ID, [$term_id], 'seo_task_list', false);
        }
    }
}
