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
  <div class="card mt-24">
    <h3 class="m-0">Add Task</h3>
    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" class="mt-16 hjseo-form tasks-form">
      <?php wp_nonce_field('hjseo_task_create'); ?>
      <input type="hidden" name="action" value="hjseo_task_create" />
      <div class="grid grid-cols-4" style="gap:16px;">
        <div class="col-span-2">
          <label class="small">Title</label>
          <input type="text" name="title" class="input" required />
        </div>
        <div class="col-span-2">
          <label class="small">Site</label>
          <select name="site_id" class="input">
            <?php foreach ($sites as $s): ?>
              <option value="<?php echo (int)$s->ID; ?>"><?php echo esc_html($s->post_title); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-span-2">
          <label class="small">List</label>
          <input type="text" name="task_list" class="input" placeholder="Technical / Content / Backlinks" />
        </div>
        <div>
          <label class="small">Priority</label>
          <select name="priority" class="input">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
        <div>
          <label class="small">Due Date</label>
          <input type="date" name="due_date" class="input" />
        </div>

        <div class="col-span-4">
          <label class="small">Description</label>
          <textarea name="content" rows="4" class="input"></textarea>
        </div>
      </div>
      <div class="flex" style="justify-content:flex-end; margin-top:16px;">
        <button class="btn" type="submit">Create Task</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="table-wrap mt-24"><table class="table">
    <thead>
      <tr><th>Site</th><th>List</th><th>Title</th><th>Priority</th><th>Status</th><th>Due</th><th>Assignee</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php if ($q->have_posts()): while ($q->have_posts()): $q->the_post();
      $site_id = (int) get_field('related_site');
      $list = get_field('task_list');
      $priority = get_field('priority');
      $status = get_field('status');
      $due = get_field('due_date');
      $assignee = get_field('assignee');
    ?>
      <tr>
        <td><?php echo esc_html(get_the_title($site_id)); ?></td>
        <td><?php echo esc_html($list ?: '—'); ?></td>
        <td><?php echo esc_html(get_the_title()); ?></td>
        <td><span class="task-priority priority-<?php echo esc_attr($priority ?: 'medium'); ?>"><?php echo esc_html(ucfirst($priority)); ?></span></td>
        <td><?php echo esc_html(ucfirst(str_replace('inprogress','in progress',$status))); ?></td>
        <td><?php echo esc_html($due ?: '—'); ?></td>
        <td><?php echo $assignee ? esc_html( get_the_author_meta('display_name', (int)$assignee) ) : '—'; ?></td>
        <td>
          <?php if ($can_complete && $status !== 'done'): ?>
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
              <?php wp_nonce_field('hjseo_task_complete'); ?>
              <input type="hidden" name="action" value="hjseo_task_complete" />
              <input type="hidden" name="task_id" value="<?php the_ID(); ?>" />
              <button class="btn">Mark Done</button>
            </form>
          <?php else: ?>
            <span class="small">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; wp_reset_postdata(); else: ?>
      <tr><td colspan="8">No tasks yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table></div>
</main>
<?php get_footer(); ?>
