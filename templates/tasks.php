<?php
/** Template for /tasks */
if (!defined('ABSPATH')) { exit; }
get_header();

$current_user = wp_get_current_user();
$can_manage = current_user_can('manage_seo_tasks') || current_user_can('administrator');
$can_complete = $can_manage || current_user_can('complete_seo_tasks');

$site_filter = isset($_GET['site']) ? sanitize_text_field($_GET['site']) : '';
$list_filter = isset($_GET['list']) ? sanitize_text_field($_GET['list']) : '';

$task_args = [
  'post_type' => 'seo_task',
  'posts_per_page' => -1,
  'orderby' => 'date',
  'order' => 'DESC',
];
if ($site_filter) {
  $site_obj = get_page_by_path(sanitize_title($site_filter), OBJECT, 'seo_site');
  if ($site_obj) {
    $task_args['meta_query'][] = [ 'key' => 'related_site', 'value' => $site_obj->ID ];
  }
}
if ($list_filter) {
  $task_args['meta_query'][] = [ 'key' => 'task_list', 'value' => $list_filter ];
}
$q = new WP_Query($task_args);

$sites = get_posts(['post_type'=>'seo_site','posts_per_page'=>-1]);
?>
<main class="container">
  <h1 class="m-0">SEO Tasks</h1>
  <form method="get" class="mt-24 flex items-center" style="gap:12px;">
    <select name="site">
      <option value="">All Sites</option>
      <?php foreach ($sites as $s): ?>
        <option value="<?php echo esc_attr($s->post_name); ?>" <?php selected($site_filter===$s->post_name); ?>><?php echo esc_html($s->post_title); ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="list" placeholder="List name" value="<?php echo esc_attr($list_filter); ?>" class="input" />
    <button class="btn" type="submit">Filter</button>
  </form>

    <?php if ($can_manage): ?>
    <div class="card mt-24" style="display:flex;align-items:center;justify-content:space-between;">
      <h3 class="m-0">Control Panel</h3>
      <div class="flex" style="gap:8px;">
        <button class="btn" id="btn-open-new-task">+ Create New Task</button>
        <button class="btn" id="btn-open-new-list">+ Add New List</button>
      </div>
    </div>
    <?php endif; ?>

  <div class="table-wrap mt-24"><table class="table">
    <thead>
      <tr><th>Site</th><th>List</th><th>Title</th><th>Priority</th><th>Status</th><th>Due</th><th>Assignee</th><th>Notes</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if ($q->have_posts()): while ($q->have_posts()): $q->the_post();
      $site_id = (int) get_field('related_site');
      $term_list = wp_get_post_terms(get_the_ID(),'seo_task_list');
      $list = $term_list ? $term_list[0]->name : get_field('task_list');
      $priority = get_field('priority');
      $status = get_field('status');
      $due = get_field('due_date');
      $assignee = get_field('assignee');
      $notes = get_field('notes');
    ?>
      <tr>
        <td><?php echo esc_html(get_the_title($site_id)); ?></td>
        <td><?php echo esc_html($list ?: '—'); ?></td>
        <td><?php echo esc_html(get_the_title()); ?></td>
        <td><span class="task-priority priority-<?php echo esc_attr($priority ?: 'medium'); ?>"><?php echo esc_html(ucfirst($priority)); ?></span></td>
        <td><?php echo esc_html(ucfirst(str_replace('inprogress','in progress',$status))); ?></td>
        <td><?php echo esc_html($due ?: '—'); ?></td>
        <td><?php echo $assignee ? esc_html( get_the_author_meta('display_name', (int)$assignee) ) : '—'; ?></td>
        <td><?php echo $notes ? esc_html( wp_trim_words(wp_strip_all_tags($notes), 10) ) : '—'; ?></td>
        <td style="white-space:nowrap;">
          <button class="btn" data-edit-task
            data-id="<?php the_ID(); ?>"
            data-title="<?php echo esc_attr(get_the_title()); ?>"
            data-site="<?php echo (int)$site_id; ?>"
            data-list="<?php echo esc_attr($list); ?>"
            data-priority="<?php echo esc_attr($priority); ?>"
            data-status="<?php echo esc_attr($status); ?>"
            data-due="<?php echo esc_attr($due); ?>"
            data-notes="<?php echo esc_attr($notes); ?>"
          >Edit</button>
          <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="display:inline;">
            <?php wp_nonce_field('hjseo_task_delete'); ?>
            <input type="hidden" name="action" value="hjseo_task_delete" />
            <input type="hidden" name="task_id" value="<?php the_ID(); ?>" />
            <button class="btn" onclick="return confirm('Delete this task?');">Delete</button>
          </form>
          <?php if ($can_complete && $status !== 'done'): ?>
          <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="display:inline;">
            <?php wp_nonce_field('hjseo_task_complete'); ?>
            <input type="hidden" name="action" value="hjseo_task_complete" />
            <input type="hidden" name="task_id" value="<?php the_ID(); ?>" />
            <button class="btn">Mark Done</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; wp_reset_postdata(); else: ?>
      <tr><td colspan="8">No tasks yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table></div>
</main>
<?php if ($can_manage): ?>
<!-- New Task Modal -->
<div class="hjseo-modal" id="modal-new-task" hidden>
  <div class="hjseo-modal-card">
    <h3 class="m-0">Create Task</h3>
    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" class="mt-16 hjseo-form tasks-form">
      <?php wp_nonce_field('hjseo_task_create'); ?>
      <input type="hidden" name="action" value="hjseo_task_create" />
      <div class="grid grid-cols-4" style="gap:16px;">
        <div class="col-span-2"><label class="small">Title</label><input type="text" name="title" class="input" required></div>
        <div class="col-span-2"><label class="small">Site</label><select name="site_id" class="input"><?php foreach ($sites as $s): ?><option value="<?php echo (int)$s->ID; ?>"><?php echo esc_html($s->post_title); ?></option><?php endforeach; ?></select></div>
        <div class="col-span-2"><label class="small">List</label><select name="task_list_term" class="input" id="newtask-list-select"></select></div>
        <div><label class="small">Priority</label><select name="priority" class="input"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
        <div><label class="small">Due Date</label><input type="date" name="due_date" class="input"></div>
        <div class="col-span-4"><label class="small">Description</label><textarea name="content" rows="4" class="input"></textarea></div>
        <div class="col-span-4"><label class="small">Notes</label><textarea name="notes" rows="3" class="input"></textarea></div>
      </div>
      <div class="flex" style="justify-content:flex-end; gap:8px; margin-top:16px;">
        <button type="button" class="btn" data-close-modal="#modal-new-task">Cancel</button>
        <button class="btn" type="submit">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Task Modal -->
<div class="hjseo-modal" id="modal-edit-task" hidden>
  <div class="hjseo-modal-card">
    <h3 class="m-0">Edit Task</h3>
    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" class="mt-16 hjseo-form tasks-form" id="edit-task-form">
      <?php wp_nonce_field('hjseo_task_edit'); ?>
      <input type="hidden" name="action" value="hjseo_task_edit" />
      <input type="hidden" name="task_id" id="edit-task-id" />
      <div class="grid grid-cols-4" style="gap:16px;">
        <div class="col-span-2"><label class="small">Title</label><input type="text" name="title" id="edit-task-title" class="input" required></div>
        <div class="col-span-2"><label class="small">Site</label><select name="site_id" id="edit-task-site" class="input"><?php foreach ($sites as $s): ?><option value="<?php echo (int)$s->ID; ?>"><?php echo esc_html($s->post_title); ?></option><?php endforeach; ?></select></div>
        <div class="col-span-2"><label class="small">List</label><select name="task_list_term" id="edit-task-list" class="input"></select></div>
        <div><label class="small">Priority</label><select name="priority" id="edit-task-priority" class="input"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
        <div><label class="small">Status</label><select name="status" id="edit-task-status" class="input"><option value="todo">To do</option><option value="inprogress">In progress</option><option value="done">Done</option></select></div>
        <div><label class="small">Due Date</label><input type="date" name="due_date" id="edit-task-due" class="input"></div>
        <div class="col-span-4"><label class="small">Notes</label><textarea name="notes" id="edit-task-notes" rows="3" class="input"></textarea></div>
      </div>
      <div class="flex" style="justify-content:flex-end; gap:8px; margin-top:16px;">
        <button type="button" class="btn" data-close-modal="#modal-edit-task">Cancel</button>
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- New List Modal -->
<div class="hjseo-modal" id="modal-new-list" hidden>
  <div class="hjseo-modal-card">
    <h3 class="m-0">Add New List</h3>
    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" class="mt-16 hjseo-form">
      <?php wp_nonce_field('hjseo_tasklist_create'); ?>
      <input type="hidden" name="action" value="hjseo_tasklist_create" />
      <div class="grid grid-cols-4" style="gap:16px;">
        <div class="col-span-3"><label class="small">List name</label><input type="text" name="list_name" class="input" required></div>
        <div class="col-span-1"><label class="small">Site</label><select name="site_id" class="input"><?php foreach ($sites as $s): ?><option value="<?php echo (int)$s->ID; ?>"><?php echo esc_html($s->post_title); ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="flex" style="justify-content:flex-end; gap:8px; margin-top:16px;">
        <button type="button" class="btn" data-close-modal="#modal-new-list">Cancel</button>
        <button class="btn" type="submit">Create</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<script>
(function(){
  function $(s,root){return (root||document).querySelector(s);} 
  function $all(s,root){return Array.prototype.slice.call((root||document).querySelectorAll(s));}
  function openModal(id){var m=$(id); if(m){m.hidden=false;}}
  function closeModal(id){var m=$(id); if(m){m.hidden=true;}}
  var btnNew=$('#btn-open-new-task'); if(btnNew){btnNew.addEventListener('click',function(){openModal('#modal-new-task'); populateLists('#newtask-list-select');});}
  var btnList=$('#btn-open-new-list'); if(btnList){btnList.addEventListener('click',function(){openModal('#modal-new-list');});}
  $all('[data-close-modal]').forEach(function(b){b.addEventListener('click',function(){closeModal(b.getAttribute('data-close-modal'));});});
  // Edit modal populate
  $all('[data-edit-task]').forEach(function(b){b.addEventListener('click',function(){
    openModal('#modal-edit-task');
    $('#edit-task-id').value = b.getAttribute('data-id');
    $('#edit-task-title').value = b.getAttribute('data-title');
    $('#edit-task-site').value = b.getAttribute('data-site');
    $('#edit-task-priority').value = b.getAttribute('data-priority');
    $('#edit-task-status').value = b.getAttribute('data-status');
    $('#edit-task-due').value = b.getAttribute('data-due');
    $('#edit-task-notes').value = b.getAttribute('data-notes');
    populateLists('#edit-task-list', b.getAttribute('data-site'), b.getAttribute('data-list'));
  });});
  // Populate lists by site via data embedded in DOM using script localized list endpoint (simplified)
  function populateLists(selectSel, siteId, current){
    var sel=$(selectSel); if(!sel) return; sel.innerHTML='';
    // Attempt to pull options from a dataset rendered server-side (fallback to all terms)
    try {
      var all = JSON.parse(document.body.getAttribute('data-tasklists')||'[]');
      var opts = all.filter(function(t){return !siteId || String(t.site_id)===String(siteId);});
      if(opts.length===0) opts = all;
      opts.forEach(function(t){var o=document.createElement('option');o.value=t.term_id;o.textContent=t.name; if(current && (current===t.name||String(current)===String(t.term_id))) o.selected=true; sel.appendChild(o);});
    } catch(e) {}
  }
})();
</script>
<?php get_footer(); ?>
