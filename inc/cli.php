<?php
/**
 * WP-CLI commands for HealingJourney SEO
 */
if (!defined('ABSPATH')) { exit; }

if (defined('WP_CLI') && constant('WP_CLI')) {
    /**
     * Sync a single site or all sites.
     */
    class HJSEO_CLI_Sync {
        /**
         * Sync a single seo_site by post ID.
         *
         * ## OPTIONS
         *
         * <post_id>
         * : The ID of the seo_site post.
         *
         * ## EXAMPLES
         *
         * wp hjseo sync_one 123
         */
        public function sync_one($args) {
            list($post_id) = $args;
            $post = get_post($post_id);
            if (!$post || $post->post_type !== 'seo_site') {
                call_user_func(['WP_CLI','error'], 'Invalid seo_site ID');
            }
            if (!function_exists('hjseo_update_site_metrics')) {
                call_user_func(['WP_CLI','error'], 'Sync function missing');
            }
            $res = hjseo_update_site_metrics($post_id);
            if (is_wp_error($res)) {
                call_user_func(['WP_CLI','error'], 'Sync failed: ' . $res->get_error_message());
            }
            call_user_func(['WP_CLI','success'], 'Synced site #' . $post_id . ' (' . $post->post_title . ')');
        }

        /**
         * Sync all active seo_site posts.
         *
         * ## OPTIONS
         *
         * [--limit=<number>]
         * : Optional max number of sites to sync in this run.
         *
         * ## EXAMPLES
         *
         * wp hjseo sync_all
         * wp hjseo sync_all --limit=10
         */
        public function sync_all($args, $assoc_args) {
            $limit = isset($assoc_args['limit']) ? (int)$assoc_args['limit'] : -1;
            $q = new WP_Query([
                'post_type' => 'seo_site',
                'posts_per_page' => $limit > 0 ? $limit : -1,
                'meta_key' => 'active',
                'meta_value' => '1',
                'orderby' => 'ID',
                'order' => 'ASC',
                'fields' => 'ids'
            ]);
            $count = 0;
            if ($q->have_posts()) {
                foreach ($q->posts as $pid) {
                    $res = hjseo_update_site_metrics($pid);
                    if (is_wp_error($res)) {
                        call_user_func(['WP_CLI','warning'], 'Site #' . $pid . ' failed: ' . $res->get_error_message());
                    } else {
                        call_user_func(['WP_CLI','log'], 'Synced #' . $pid);
                    }
                    $count++;
                }
            }
            call_user_func(['WP_CLI','success'], 'Completed sync for ' . $count . ' site(s).');
        }
    }

    call_user_func(['WP_CLI','add_command'], 'hjseo sync_one', [new HJSEO_CLI_Sync(), 'sync_one']);
    call_user_func(['WP_CLI','add_command'], 'hjseo sync_all', [new HJSEO_CLI_Sync(), 'sync_all']);
}
