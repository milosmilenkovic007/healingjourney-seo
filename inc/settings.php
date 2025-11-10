<?php
/** Admin Settings page for SEO Integrations */
if (!defined('ABSPATH')) { exit; }

add_action('admin_menu', function(){
    add_options_page('SEO Integrations', 'SEO Integrations', 'manage_options', 'hjseo-settings', 'hjseo_settings_page');
});

add_action('admin_init', function(){
    // Options
    register_setting('hjseo_settings', 'hjseo_moz_access_id', ['type'=>'string','sanitize_callback'=>'sanitize_text_field','show_in_rest'=>false]);
    register_setting('hjseo_settings', 'hjseo_moz_secret_key', ['type'=>'string','sanitize_callback'=>'sanitize_text_field','show_in_rest'=>false]);
    register_setting('hjseo_settings', 'hjseo_gsc_service_account_json', ['type'=>'string','sanitize_callback'=>'hjseo_sanitize_json','show_in_rest'=>false, 'autoload' => 'no']);
    register_setting('hjseo_settings', 'hjseo_sync_window', ['type'=>'integer','sanitize_callback'=>'absint','default'=>28]);
    register_setting('hjseo_settings', 'hjseo_enable_cron', ['type'=>'string','sanitize_callback'=>function($v){ return $v==='1'?'1':'0'; }, 'default'=>'0']);
});

function hjseo_sanitize_json($value){
    if (is_array($value)) $value = wp_json_encode($value);
    $trim = trim((string)$value);
    // minimal validation
    $d = json_decode($trim, true);
    return is_array($d) ? $trim : '';
}

function hjseo_settings_page(){
    if (!current_user_can('manage_options')) return;

    // Handle uploads of service account JSON
    if (!empty($_FILES['gsc_service_account_json_file']['tmp_name'])) {
        check_admin_referer('hjseo_settings');
        $contents = file_get_contents($_FILES['gsc_service_account_json_file']['tmp_name']);
        if ($contents) update_option('hjseo_gsc_service_account_json', $contents, false);
        echo '<div class="updated"><p>Service account JSON uploaded.</p></div>';
    }

    // Handle tests
    if (isset($_POST['hjseo_test_moz'])) {
        check_admin_referer('hjseo_settings');
        $test = hjseo_moz_test_connection();
        if (is_wp_error($test)) echo '<div class="error"><p>' . esc_html($test->get_error_message()) . '</p></div>';
        else echo '<div class="updated"><p>' . esc_html($test) . '</p></div>';
    }
    if (isset($_POST['hjseo_test_gsc'])) {
        check_admin_referer('hjseo_settings');
        $prop = sanitize_text_field($_POST['hjseo_test_property'] ?? 'https://example.com/');
        $test = hjseo_gsc_test_connection($prop);
        if (is_wp_error($test)) echo '<div class="error"><p>' . esc_html($test->get_error_message()) . '</p></div>';
        else echo '<div class="updated"><p>' . esc_html($test) . '</p></div>';
    }

    // Handle full sync
    if (isset($_POST['hjseo_run_full_sync'])) {
        check_admin_referer('hjseo_settings');
        $sites = get_posts(['post_type'=>'seo_site','posts_per_page'=>-1]);
        $ok=0;$fail=0;
        foreach ($sites as $s) {
            if (hjseo_field('active', $s->ID) !== '1') continue;
            $res = hjseo_update_site_metrics($s->ID);
            if (is_wp_error($res)) $fail++; else $ok++;
        }
        echo '<div class="updated"><p>Full sync done. OK: ' . (int)$ok . ', Failed: ' . (int)$fail . '</p></div>';
    }

    ?>
    <div class="wrap">
      <h1>SEO Integrations</h1>
      <form method="post" action="options.php" enctype="multipart/form-data">
        <?php settings_fields('hjseo_settings'); wp_nonce_field('hjseo_settings'); ?>

        <h2 class="title">MOZ API</h2>
        <table class="form-table" role="presentation">
          <tr><th scope="row"><label for="moz_access_id">Access ID</label></th>
            <td><input name="hjseo_moz_access_id" id="moz_access_id" type="text" value="<?php echo esc_attr(get_option('hjseo_moz_access_id','')); ?>" class="regular-text" /></td></tr>
          <tr><th scope="row"><label for="moz_secret_key">Secret Key</label></th>
            <td><input name="hjseo_moz_secret_key" id="moz_secret_key" type="password" value="<?php echo esc_attr(get_option('hjseo_moz_secret_key','')); ?>" class="regular-text" /></td></tr>
          <tr><th scope="row">Test Connection</th>
            <td><button class="button" name="hjseo_test_moz" value="1">Test MOZ Connection</button></td></tr>
        </table>

        <h2 class="title">Google Search Console</h2>
        <table class="form-table" role="presentation">
          <tr><th scope="row"><label for="gsc_json">Service Account JSON</label></th>
            <td>
              <input type="file" name="gsc_service_account_json_file" accept="application/json" />
              <p class="description">Alternatively, paste JSON:</p>
              <textarea name="hjseo_gsc_service_account_json" id="gsc_json" rows="6" cols="70"><?php echo esc_textarea(get_option('hjseo_gsc_service_account_json','')); ?></textarea>
            </td>
          </tr>
          <tr><th scope="row">Test Property</th>
            <td>
              <input type="text" name="hjseo_test_property" placeholder="https://example.com/" class="regular-text" />
              <button class="button" name="hjseo_test_gsc" value="1">Test GSC Connection</button>
            </td>
          </tr>
        </table>

        <h2 class="title">Sync</h2>
        <table class="form-table" role="presentation">
          <tr><th scope="row"><label for="sync_window">Default time window</label></th>
            <td>
              <select id="sync_window" name="hjseo_sync_window">
                <?php foreach ([7,28,90] as $d): ?>
                  <option value="<?php echo (int)$d; ?>" <?php selected((int)get_option('hjseo_sync_window',28), $d); ?>><?php echo (int)$d; ?> days</option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr><th scope="row">Enable Cron</th>
            <td>
              <label><input type="checkbox" name="hjseo_enable_cron" value="1" <?php checked(get_option('hjseo_enable_cron','0'),'1'); ?> /> Enable Cron (daily GSC, weekly MOZ)</label>
            </td>
          </tr>
          <tr><th scope="row">Run Full Sync</th>
            <td>
              <button class="button button-primary" name="hjseo_run_full_sync" value="1">Run Full Sync Now</button>
            </td>
          </tr>
        </table>

        <?php submit_button(); ?>
      </form>
    </div>
    <?php
}
