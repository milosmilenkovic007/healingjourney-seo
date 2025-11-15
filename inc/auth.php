<?php
/** Authentication middleware for protecting front-end pages */
if (!defined('ABSPATH')) { exit; }

/**
 * Redirect unauthenticated users to login page (except login page itself)
 */
add_action('template_redirect', function() {
    // Skip if user is logged in
    if (is_user_logged_in()) {
        return;
    }
    
    // Skip admin area
    if (is_admin()) {
        return;
    }
    
    // Skip REST API requests
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    
    // Get current page
    global $post;
    
    // Allow login page itself
    if ($post && $post->post_name === 'login') {
        return;
    }
    
    // Allow wp-login.php
    if ($GLOBALS['pagenow'] === 'wp-login.php') {
        return;
    }
    
    // Redirect to login
    $login_url = home_url('/login/');
    if (!$login_url) {
        $login_url = wp_login_url();
    }
    
    wp_redirect($login_url);
    exit;
});

/**
 * Redirect wp-login.php to custom login page
 */
add_action('login_init', function() {
    // Skip if this is a logout action
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        return;
    }
    
    // Skip if this is lost password
    if (isset($_GET['action']) && in_array($_GET['action'], ['lostpassword', 'rp', 'resetpass'])) {
        return;
    }
    
    // Redirect to custom login
    $custom_login = home_url('/login/');
    if ($custom_login && $custom_login !== wp_login_url()) {
        wp_redirect($custom_login);
        exit;
    }
});

/**
 * Redirect after logout to login page
 */
add_filter('logout_url', function($logout_url, $redirect) {
    $custom_login = home_url('/login/');
    if ($custom_login) {
        return add_query_arg('redirect_to', urlencode($custom_login), $logout_url);
    }
    return $logout_url;
}, 10, 2);
