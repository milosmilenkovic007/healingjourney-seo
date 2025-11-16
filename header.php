<?php
if (!defined('ABSPATH')) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">

<header class="site-header">
	<div class="container" style="display:flex;align-items:center;justify-content:space-between;">
		<a href="/" class="brand" style="text-decoration:none;"><span class="dot"></span> <span>HealingJourney SEO</span></a>
		<nav style="display:flex;align-items:center;gap:2rem;">
			<a href="/sites" class="hjseo-nav-link">Sites</a>
			<a href="/reports" class="hjseo-nav-link">Reports</a>
			<?php if ( is_user_logged_in() ) : ?>
				<form method="post" action="<?php echo esc_url( wp_logout_url('/login/') ); ?>" style="display:inline; margin-left:2rem;">
					<button type="submit" class="hjseo-btn hjseo-btn-secondary" style="padding:0.5rem 1.2rem;">Logout</button>
				</form>
			<?php else: ?>
				<a class="hjseo-btn hjseo-btn-secondary" href="<?php echo esc_url( home_url('/login/') ); ?>" style="padding:0.5rem 1.2rem;">Login</a>
			<?php endif; ?>
		</nav>
	</div>
</header>
