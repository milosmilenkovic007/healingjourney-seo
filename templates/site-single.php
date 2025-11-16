<?php
/** Template for /site/{slug} */
if (!defined('ABSPATH')) { exit; }
$slug = get_query_var('hjseo_site');
$site = $slug ? get_page_by_path($slug, OBJECT, 'seo_site') : null;
if (!$site) { status_header(404); nocache_headers(); echo '<main class="container"><p>Site not found.</p></main>'; exit; }
setup_postdata($site);
get_header();
?>
<main class="container">
  <h1 class="m-0"><?php echo esc_html($site->post_title); ?></h1>
  <div class="small"><?php echo esc_html(hjseo_field('site_domain', $site->ID) ?: hjseo_field('domain', $site->ID)); ?></div>
  <div class="mt-16 card">
    <?php echo hjseo_render_metrics_row(hjseo_get_site_metrics($site->ID)); ?>
  </div>

  <?php if (!empty($site->post_content)): ?>
  <div class="mt-24">
    <?php echo wp_kses_post($site->post_content); ?>
  </div>
  <?php endif; ?>

  <div class="tabs">
    <button class="tab active" data-tab="tab-tasks">SEO Tasks</button>
    <button class="tab" data-tab="tab-keywords">Keyword Map</button>
    <button class="tab" data-tab="tab-content">Content Plan</button>
  </div>

  <section id="tab-tasks" class="tabpanel active">
    <?php
      $tasks = new WP_Query(['post_type'=>'seo_task','posts_per_page'=>-1,'meta_query'=>[[ 'key'=>'related_site','value'=>$site->ID ]],'orderby'=>'date','order'=>'DESC']);
      echo '<div class="table-wrap"><table class="table"><thead><tr><th>List</th><th>Title</th><th>Priority</th><th>Status</th><th>Due</th></tr></thead><tbody>';
      if ($tasks->have_posts()): while ($tasks->have_posts()): $tasks->the_post();
        $list = hjseo_field('task_list');
        $priority = hjseo_field('priority');
        $status = hjseo_field('status');
        $due = hjseo_field('due_date');
        echo '<tr>'
          . '<td>' . esc_html($list ?: '—') . '</td>'
          . '<td>' . esc_html(get_the_title()) . '</td>'
          . '<td>' . esc_html(ucfirst($priority)) . '</td>'
          . '<td>' . esc_html(ucfirst(str_replace('inprogress','in progress',$status))) . '</td>'
          . '<td>' . esc_html($due ?: '—') . '</td>'
        . '</tr>';
      endwhile; wp_reset_postdata(); else: echo '<tr><td colspan="5">No tasks yet.</td></tr>'; endif;
      echo '</tbody></table></div>';
    ?>
  </section>

  <section id="tab-keywords" class="tabpanel">
    <?php
  $k = new WP_Query(['post_type' => 'keyword_map', 'posts_per_page' => 1, 'meta_query' => [ [ 'key' => 'related_site', 'value' => $site->ID ] ], 'orderby' => 'date', 'order' => 'DESC']);
      if ($k->have_posts()):
        $k->the_post();
  $rows = hjseo_field('keywords');
        if ($rows) {
          echo '<div class="table-wrap"><table class="table"><thead><tr><th>Keyword</th><th>URL</th><th>Vol</th><th>Diff</th><th>Intent</th><th>CTR %</th><th>Notes</th></tr></thead><tbody>';
          foreach ($rows as $r) {
            $intent = $r['intent'] ?? '';
            echo '<tr>'
              . '<td>' . esc_html($r['keyword']) . '</td>'
              . '<td><a href="' . esc_url($r['url']) . '" target="_blank">' . esc_html(parse_url($r['url'], PHP_URL_PATH)) . '</a></td>'
              . '<td>' . esc_html(hjseo_number($r['search_volume'])) . '</td>'
              . '<td>' . esc_html($r['difficulty']) . '</td>'
              . '<td><span class="intent ' . esc_attr($intent) . '">' . esc_html(ucfirst($intent)) . '</span></td>'
              . '<td>' . esc_html($r['ctr_potential']) . '</td>'
              . '<td>' . esc_html($r['notes']) . '</td>'
              . '</tr>';
          }
          echo '</tbody></table></div>';
        } else {
          echo '<p>No keyword rows.</p>';
        }
        wp_reset_postdata();
      else:
        echo '<p>No keyword map.</p>';
      endif;
    ?>
  </section>

  <section id="tab-content" class="tabpanel">
    <?php
  $c = new WP_Query(['post_type' => 'content_plan', 'posts_per_page' => -1, 'meta_query' => [ [ 'key' => 'related_site', 'value' => $site->ID ] ], 'orderby' => 'date', 'order' => 'DESC']);
      if ($c->have_posts()): echo '<div class="timeline">';
        while ($c->have_posts()): $c->the_post();
          echo '<div class="timeline-item">'
            . '<div class="small">' . esc_html(hjseo_field('week')) . '</div>'
            . '<div class="title">' . esc_html(hjseo_field('blog_title')) . '</div>'
            . '<div class="small">Keyword: ' . esc_html(hjseo_field('keyword_focus')) . ' | Format: ' . esc_html(hjseo_field('format')) . '</div>'
            . '<div>' . esc_html(hjseo_field('goal')) . '</div>'
            . '<div class="small">CTA: ' . esc_html(hjseo_field('cta')) . '</div>'
            . '</div>';
        endwhile; echo '</div>'; wp_reset_postdata();
      else: echo '<p>No content plan items.</p>'; endif;
    ?>
  </section>
</main>
<?php get_footer(); ?>
