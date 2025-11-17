<?php
/** Template for /dashboard */
if (!defined('ABSPATH')) { exit; }
if (!is_user_logged_in()) { wp_redirect(home_url('/login/')); exit; }
get_header();
$user = wp_get_current_user();
$tasks = new WP_Query([
  'post_type'=>'seo_task',
  'posts_per_page'=>-1,
  'meta_query'=>[[ 'key'=>'assignee', 'value'=>$user->ID ]],
  'orderby'=>'date','order'=>'DESC'
]);
?>
<main class="container">
  <h1 class="m-0">My Dashboard</h1>
  <div class="grid grid-cols-3 mt-24">
    <div class="card"><h3 class="m-0">Open Tasks</h3><div class="metrics mt-16"><div class="metric"><div class="label">To do</div><div class="value"><?php echo (int)wp_count_posts('seo_task')->publish; ?></div></div></div></div>
    <div class="card"><h3 class="m-0">Assigned</h3><p class="small">Tasks assigned to you</p></div>
  </div>
  <div class="table-wrap mt-24"><table class="table"><thead><tr><th>Site</th><th>Title</th><th>Priority</th><th>Status</th><th>Due</th></tr></thead><tbody>
  <?php if ($tasks->have_posts()): while($tasks->have_posts()): $tasks->the_post();
    $site_id = (int) get_field('related_site');
    $priority = get_field('priority');
    $status = get_field('status');
    $due = get_field('due_date');
  ?>
    <tr>
      <td><?php echo esc_html(get_the_title($site_id)); ?></td>
      <td><?php echo esc_html(get_the_title()); ?></td>
      <td><span class="task-priority priority-<?php echo esc_attr($priority ?: 'medium'); ?>"><?php echo esc_html(ucfirst($priority)); ?></span></td>
      <td><?php echo esc_html(ucfirst(str_replace('inprogress','in progress',$status))); ?></td>
      <td><?php echo esc_html($due ?: '—'); ?></td>
    </tr>
  <?php endwhile; else: ?>
    <tr><td colspan="5">No assigned tasks.</td></tr>
  <?php endif; wp_reset_postdata(); ?>
  </tbody></table></div>
</main>
<?php get_footer(); ?>
