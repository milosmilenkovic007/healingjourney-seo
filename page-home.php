<?php
/**
 * Template Name: Dashboard Home
 * Home page for logged-in users with SEO overview
 */

if (!defined('ABSPATH')) { exit; }

// Redirect to login if not authenticated
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

get_header();

// Get all active sites for dashboard summary
$sites = get_posts([
    'post_type' => 'seo_site',
    'posts_per_page' => -1,
    'meta_key' => 'active',
    'meta_value' => '1',
    'orderby' => 'title',
    'order' => 'ASC'
]);

// Calculate totals
$total_authority = 0;
$total_backlinks = 0;
$total_ref_domains = 0;
$total_keywords = 0;
$total_visibility = 0;
$site_count = count($sites);

foreach ($sites as $site) {
    $total_authority += (int)hjseo_field('authority', $site->ID);
    $total_backlinks += (int)hjseo_field('backlinks', $site->ID);
    $total_ref_domains += (int)hjseo_field('ref_domains', $site->ID);
    $total_keywords += (int)hjseo_field('keywords', $site->ID);
    $total_visibility += (float)hjseo_field('visibility', $site->ID);
}

$avg_authority = $site_count > 0 ? round($total_authority / $site_count, 1) : 0;
$avg_visibility = $site_count > 0 ? round($total_visibility / $site_count, 2) : 0;
?>

<div class="hjseo-dashboard-home">
    <div class="hjseo-dashboard-header">
        <div class="hjseo-dashboard-welcome">
            <h1>Welcome back, <?php echo esc_html(wp_get_current_user()->display_name); ?></h1>
            <p>Here's your SEO performance overview</p>
        </div>
        <div class="hjseo-dashboard-actions">
            <button id="btn-open-add-site" class="hjseo-btn hjseo-btn-primary" type="button">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add Site
            </button>
            <a href="<?php echo esc_url(home_url('/settings')); ?>" class="hjseo-btn hjseo-btn-secondary">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                </svg>
                Settings
            </a>
        </div>
    </div>

    <div class="hjseo-stats-grid">
        <div class="hjseo-stat-card">
            <div class="hjseo-stat-icon" style="background: #edf2f7; color: #2d3748;">
                <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="hjseo-stat-content">
                <div class="hjseo-stat-label">Total Sites</div>
                <div class="hjseo-stat-value"><?php echo (int)$site_count; ?></div>
            </div>
        </div>

        <div class="hjseo-stat-card">
            <div class="hjseo-stat-icon" style="background: #e6fffa; color: #047857;">
                <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="hjseo-stat-content">
                <div class="hjseo-stat-label">Avg Authority</div>
                <div class="hjseo-stat-value"><?php echo esc_html($avg_authority); ?></div>
            </div>
        </div>

        <div class="hjseo-stat-card">
            <div class="hjseo-stat-icon" style="background: #fef3c7; color: #d97706;">
                <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="hjseo-stat-content">
                <div class="hjseo-stat-label">Total Keywords</div>
                <div class="hjseo-stat-value"><?php echo hjseo_number($total_keywords); ?></div>
            </div>
        </div>

        <div class="hjseo-stat-card">
            <div class="hjseo-stat-icon" style="background: #dbeafe; color: #1e40af;">
                <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="hjseo-stat-content">
                <div class="hjseo-stat-label">Avg Visibility</div>
                <div class="hjseo-stat-value"><?php echo esc_html($avg_visibility); ?>%</div>
            </div>
        </div>
    </div>

    <div class="hjseo-sites-section">
        <div class="hjseo-section-header">
            <h2>Your Sites</h2>
            <a href="<?php echo esc_url(home_url('/sites/')); ?>" class="hjseo-link">View all →</a>
        </div>

        <?php if ($sites): ?>
            <div class="hjseo-sites-table">
                <table>
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Authority</th>
                            <th>Backlinks</th>
                            <th>Ref Domains</th>
                            <th>Keywords</th>
                            <th>Visibility</th>
                            <th>Last Synced</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sites as $site): 
                            $metrics = hjseo_get_site_metrics($site->ID);
                            $last_synced = hjseo_field('last_synced', $site->ID);
                        ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(home_url('/site/' . $site->post_name . '/')); ?>" class="hjseo-site-link">
                                        <?php echo esc_html($site->post_title); ?>
                                    </a>
                                </td>
                                <td><strong><?php echo esc_html($metrics['authority'] ?: '—'); ?></strong></td>
                                <td><?php echo esc_html(hjseo_number($metrics['backlinks'])); ?></td>
                                <td><?php echo esc_html(hjseo_number($metrics['ref_domains'])); ?></td>
                                <td><?php echo esc_html(hjseo_number($metrics['keywords'])); ?></td>
                                <td><?php echo esc_html($metrics['visibility'] ? $metrics['visibility'] . '%' : '—'); ?></td>
                                <td><small><?php echo $last_synced ? esc_html(human_time_diff(strtotime($last_synced), current_time('timestamp'))) . ' ago' : '—'; ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="hjseo-empty-state">
                <svg width="64" height="64" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                </svg>
                <h3>No sites yet</h3>
                <p>Add your first site to start tracking SEO metrics</p>
                                <button id="btn-open-add-site-empty" class="hjseo-btn hjseo-btn-primary" type="button">Add Site</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Site Modal -->
<div class="hjseo-modal" id="modal-add-site" hidden>
    <div class="hjseo-modal-card">
        <h3 class="m-0">Add New Site</h3>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mt-16 hjseo-form tasks-form">
            <?php wp_nonce_field('hjseo_add_site'); ?>
            <input type="hidden" name="action" value="hjseo_add_site" />
            <div class="grid grid-cols-4" style="gap:16px;">
                <div class="col-span-2"><label class="small">Site Domain</label><input type="text" name="site_domain" class="input" placeholder="example.com" required /></div>
                <div class="col-span-2"><label class="small">GSC Property</label><input type="text" name="gsc_property" class="input" placeholder="https://example.com/ or sc-domain:example.com" required /></div>
            </div>
            <div class="flex" style="justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn" data-close-modal="#modal-add-site">Cancel</button>
                <button class="btn" type="submit">Create</button>
            </div>
        </form>
    </div>
</div>
<script>
(function(){
    function openModal(){ var m=document.getElementById('modal-add-site'); if(m) m.hidden=false; }
    var a=document.getElementById('btn-open-add-site'); if(a) a.addEventListener('click', openModal);
    var b=document.getElementById('btn-open-add-site-empty'); if(b) b.addEventListener('click', openModal);
    document.querySelectorAll('[data-close-modal]').forEach(function(x){ x.addEventListener('click', function(){ var id=x.getAttribute('data-close-modal'); var el=document.querySelector(id); if(el) el.hidden=true; }); });
})();
</script>

<?php get_footer(); ?>
