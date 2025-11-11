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
  register_setting('hjseo_settings', 'hjseo_debug_log', ['type'=>'string','sanitize_callback'=>function($v){ return $v==='1'?'1':'0'; }, 'default'=>'0']);
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

  // Handle uploads of service account JSON (when posted to this page directly)
  if (!empty($_FILES['gsc_service_account_json_file']['tmp_name'])) {
    check_admin_referer('hjseo_tests');
        $contents = file_get_contents($_FILES['gsc_service_account_json_file']['tmp_name']);
        if ($contents) update_option('hjseo_gsc_service_account_json', $contents, false);
        echo '<div class="updated"><p>Service account JSON uploaded.</p></div>';
    }
    // Clear debug log
    if (isset($_POST['hjseo_clear_log'])) {
    check_admin_referer('hjseo_tests');
      $upload = wp_get_upload_dir();
      $file = trailingslashit($upload['basedir']) . 'hjseo-sync.log';
      if (file_exists($file)) {
        @unlink($file);
      }
      echo '<div class="updated"><p>Debug log cleared.</p></div>';
    }

    // Handle tests
    if (isset($_POST['hjseo_test_moz'])) {
    check_admin_referer('hjseo_tests');
        $test = hjseo_moz_test_connection();
        if (is_wp_error($test)) echo '<div class="error"><p>' . esc_html($test->get_error_message()) . '</p></div>';
        else echo '<div class="updated"><p>' . esc_html($test) . '</p></div>';
    }
    if (isset($_POST['hjseo_test_gsc'])) {
    check_admin_referer('hjseo_tests');
        $prop = sanitize_text_field($_POST['hjseo_test_property'] ?? 'https://example.com/');
        $test = hjseo_gsc_test_connection($prop);
        if (is_wp_error($test)) echo '<div class="error"><p>' . esc_html($test->get_error_message()) . '</p></div>';
        else echo '<div class="updated"><p>' . esc_html($test) . '</p></div>';
    }

  // Show status from admin-post full sync redirect
  if (isset($_GET['hjseo_sync']) && $_GET['hjseo_sync'] === 'done') {
    $ok = isset($_GET['ok']) ? (int) $_GET['ok'] : 0;
    $fail = isset($_GET['fail']) ? (int) $_GET['fail'] : 0;
    echo '<div class="updated"><p>Full sync done. OK: ' . $ok . ', Failed: ' . $fail . '</p></div>';
  }

    ?>
    <div class="wrap">
      <h1>SEO Integrations</h1>
      <form method="post" action="options.php" enctype="multipart/form-data">
        <?php settings_fields('hjseo_settings'); ?>

        <h2 class="title">MOZ API</h2>
        <table class="form-table" role="presentation">
          <tr><th scope="row"><label for="moz_access_id">Access ID</label></th>
            <td><input name="hjseo_moz_access_id" id="moz_access_id" type="text" value="<?php echo esc_attr(get_option('hjseo_moz_access_id','')); ?>" class="regular-text" /></td></tr>
          <tr><th scope="row"><label for="moz_secret_key">Secret Key</label></th>
            <td><input name="hjseo_moz_secret_key" id="moz_secret_key" type="password" value="<?php echo esc_attr(get_option('hjseo_moz_secret_key','')); ?>" class="regular-text" /></td></tr>
          <tr><th scope="row">Test Connection</th>
            <td><em>Use the test buttons in the Tools section below.</em></td></tr>
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
            <td><em>Use the test form in the Tools section below.</em></td></tr>
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
          <tr><th scope="row">Enable Debug Log</th>
            <td>
              <label><input type="checkbox" name="hjseo_debug_log" value="1" <?php checked(get_option('hjseo_debug_log','0'),'1'); ?> /> Write extended debug details (requests & responses) to hjseo-sync.log</label>
              <p class="description">Disable in production to reduce log size.</p>
            </td>
          </tr>
          <tr><th scope="row">Run Full Sync</th>
            <td><em>Use the Run Full Sync button in the Tools section below.</em></td>
          </tr>
        </table>

        <?php submit_button(); ?>
      </form>
  <h2 class="title">Tools</h2>
      <table class="form-table" role="presentation">
        <tr><th scope="row">Test MOZ API</th>
          <td>
            <form method="post" action="<?php echo esc_url(admin_url('options-general.php?page=hjseo-settings')); ?>">
              <?php wp_nonce_field('hjseo_tests'); ?>
              <input type="hidden" name="hjseo_test_moz" value="1" />
              <button class="button">Test MOZ Connection</button>
            </form>
          </td></tr>
        <tr><th scope="row">Test GSC API</th>
          <td>
            <form method="post" action="<?php echo esc_url(admin_url('options-general.php?page=hjseo-settings')); ?>">
              <?php wp_nonce_field('hjseo_tests'); ?>
              <input type="text" name="hjseo_test_property" placeholder="https://example.com/" class="regular-text" />
              <button class="button" name="hjseo_test_gsc" value="1">Test GSC Connection</button>
            </form>
          </td></tr>
        <tr><th scope="row">Upload Service Account JSON</th>
          <td>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('options-general.php?page=hjseo-settings')); ?>">
              <?php wp_nonce_field('hjseo_tests'); ?>
              <input type="file" name="gsc_service_account_json_file" accept="application/json" />
              <button class="button">Upload</button>
            </form>
          </td></tr>
        <tr><th scope="row">Run Full Sync</th>
          <td>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <?php wp_nonce_field('hjseo_full_sync'); ?>
              <input type="hidden" name="action" value="hjseo_full_sync" />
              <button class="button button-primary">Run Full Sync Now</button>
            </form>
          </td>
        </tr>
        <tr><th scope="row">Debug Log</th>
          <td>
            <?php $upload = wp_get_upload_dir(); $log_url = trailingslashit($upload['baseurl']) . 'hjseo-sync.log'; ?>
            <p>
              <a class="button" href="<?php echo esc_url($log_url); ?>" target="_blank" rel="noopener">Download/View Log</a>
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('options-general.php?page=hjseo-settings')); ?>">
              <?php wp_nonce_field('hjseo_tests'); ?>
              <input type="hidden" name="hjseo_clear_log" value="1" />
              <button class="button">Clear Log</button>
            </form>
          </td>
        </tr>
      </table>
    </div>
    <?php
}
