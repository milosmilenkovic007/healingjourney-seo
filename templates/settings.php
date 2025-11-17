<?php
/** Frontend Settings page */
if (!defined('ABSPATH')) { exit; }
if (!current_user_can('administrator')) { wp_redirect(home_url('/')); exit; }
get_header();
?>
<main class="container">
  <h1 class="m-0">Settings</h1>
  <div class="tabs" style="margin-top:16px;">
    <button class="tab active" data-tab="tab-general">General</button>
    <button class="tab" data-tab="tab-api">API Keys</button>
    <button class="tab" data-tab="tab-user">User</button>
    <button class="tab" data-tab="tab-tasks">Task & Lists</button>
  </div>

  <section id="tab-general" class="tabpanel active">
  <div class="card mt-16">
    <h3 class="m-0">General</h3>
    <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>" class="mt-16">
      <?php settings_fields('hjseo_settings'); ?>
      <label class="small">Default time window</label>
      <select name="hjseo_sync_window" class="input">
        <?php foreach ([14,28,56,90] as $d): ?>
          <option value="<?php echo (int)$d; ?>" <?php selected((int)get_option('hjseo_sync_window',28), $d); ?>><?php echo (int)$d; ?> days</option>
        <?php endforeach; ?>
      </select>
      <div class="mt-16">
        <label><input type="checkbox" name="hjseo_debug_log" value="1" <?php checked(get_option('hjseo_debug_log','0'),'1'); ?> /> Enable debug log</label>
      </div>
      <div class="flex" style="justify-content:flex-end; margin-top:16px;">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
  </section>

  <section id="tab-api" class="tabpanel">
  <div class="card mt-16">
    <h3 class="m-0">Google Search Console</h3>
    <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>" class="mt-16">
      <?php settings_fields('hjseo_settings'); ?>
      <label class="small">Service Account JSON</label>
      <textarea name="hjseo_gsc_service_account_json" rows="8" class="input"><?php echo esc_textarea(get_option('hjseo_gsc_service_account_json','')); ?></textarea>
      <div class="flex" style="justify-content:flex-end; margin-top:16px;">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
    <div class="mt-16">
      <form method="post" action="<?php echo esc_url( wp_nonce_url(admin_url('admin-post.php?action=hjseo_gsc_validate_now'), 'hjseo_gsc_validate_now') ); ?>">
        <button class="btn" type="submit">Re-check GSC Access</button>
      </form>
    </div>
  </div>
  <div class="card mt-16">
    <h3 class="m-0">OpenAI</h3>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mt-16">
      <?php wp_nonce_field('hjseo_update_api_keys'); ?>
      <input type="hidden" name="action" value="hjseo_update_api_keys" />
      <label class="small">OpenAI API Key</label>
      <input class="input" name="openai_api_key" value="<?php echo esc_attr(get_option('hjseo_openai_api_key','')); ?>" placeholder="sk-..." />
      <div class="flex" style="justify-content:flex-end; margin-top:16px;">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
  <div class="card mt-16">
    <h3 class="m-0">Sync Tools</h3>
    <form method="post" action="<?php echo esc_url( wp_nonce_url(admin_url('admin-post.php?action=hjseo_full_sync'), 'hjseo_full_sync') ); ?>">
      <button class="btn" type="submit">Run Full Sync</button>
    </form>
  </div>
  </section>

  <section id="tab-user" class="tabpanel">
  <div class="card mt-16">
    <h3 class="m-0">User Settings</h3>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mt-16">
      <?php wp_nonce_field('hjseo_update_user'); ?>
      <input type="hidden" name="action" value="hjseo_update_user" />
      <div class="grid grid-cols-4" style="gap:12px;">
        <div class="col-span-2"><label class="small">Display Name</label><input class="input" name="display_name" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" /></div>
        <div class="col-span-2"><label class="small">Email</label><input class="input" type="email" name="user_email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" /></div>
        <div class="col-span-2"><label class="small">New Password</label><input class="input" type="password" name="user_pass" /></div>
        <div class="col-span-2"><label class="small">Repeat Password</label><input class="input" type="password" name="user_pass2" /></div>
      </div>
      <div class="flex" style="justify-content:flex-end; margin-top:16px;"><button class="btn" type="submit">Save</button></div>
    </form>
  </div>
  </section>

  <section id="tab-tasks" class="tabpanel">
  <div class="card mt-16">
    <h3 class="m-0">Manage Task Lists</h3>
    <?php $sites = get_posts(['post_type'=>'seo_site','posts_per_page'=>-1]); ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mt-16" style="display:flex;gap:8px;align-items:flex-end;">
      <?php wp_nonce_field('hjseo_tasklist_create'); ?>
      <input type="hidden" name="action" value="hjseo_tasklist_create" />
      <div>
        <label class="small">Site</label>
        <select name="site_id" class="input"><?php foreach ($sites as $s): ?><option value="<?php echo (int)$s->ID; ?>"><?php echo esc_html($s->post_title); ?></option><?php endforeach; ?></select>
      </div>
      <div style="flex:1;">
        <label class="small">New List Name</label>
        <input class="input" name="list_name" />
      </div>
      <button class="btn" type="submit">Add List</button>
    </form>
    <div class="table-wrap mt-16"><table class="table"><thead><tr><th>Site</th><th>List</th><th>Actions</th></tr></thead><tbody>
    <?php $terms = get_terms(['taxonomy'=>'seo_task_list','hide_empty'=>false]);
      if ($terms): foreach ($terms as $t): $site_id=(int)get_term_meta($t->term_id,'related_site',true); ?>
      <tr>
        <td><?php echo esc_html(get_the_title($site_id)); ?></td>
        <td><?php echo esc_html($t->name); ?></td>
        <td>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
            <?php wp_nonce_field('hjseo_tasklist_delete'); ?>
            <input type="hidden" name="action" value="hjseo_tasklist_delete" />
            <input type="hidden" name="term_id" value="<?php echo (int)$t->term_id; ?>" />
            <button class="btn" onclick="return confirm('Delete this list?');">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; else: echo '<tr><td colspan="3">No lists.</td></tr>'; endif; ?>
    </tbody></table></div>
  </div>
  </section>

  <script>
  (function(){
    function $(s){return document.querySelector(s);} function $all(s){return Array.prototype.slice.call(document.querySelectorAll(s));}
    $all('.tabs .tab').forEach(function(t){ t.addEventListener('click', function(){ $all('.tabs .tab').forEach(x=>x.classList.remove('active')); t.classList.add('active'); $all('.tabpanel').forEach(x=>x.classList.remove('active')); var id=t.getAttribute('data-tab'); $("#"+id).classList.add('active'); }); });
  })();
  </script>
</main>
<?php get_footer(); ?>
