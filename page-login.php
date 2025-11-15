<?php
/**
 * Template Name: Login Page
 * Public-facing login page for the SEO Dashboard
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

// Handle login form submission
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hjseo_login_nonce'])) {
    if (wp_verify_nonce($_POST['hjseo_login_nonce'], 'hjseo_login_action')) {
        $username = sanitize_user($_POST['log'] ?? '');
        $password = $_POST['pwd'] ?? '';
        $remember = !empty($_POST['rememberme']);
        
        $creds = [
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        ];
        
        $user = wp_signon($creds, is_ssl());
        
        if (is_wp_error($user)) {
            $login_error = $user->get_error_message();
        } else {
            $redirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : home_url('/');
            wp_redirect($redirect);
            exit;
        }
    }
}

get_header();
?>

<div class="hjseo-login-wrapper">
    <div class="hjseo-login-container">
        <div class="hjseo-login-logo">
            <svg width="120" height="120" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="45" stroke="currentColor" stroke-width="3" fill="none"/>
                <path d="M30 50 L45 65 L70 35" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
        </div>
        
        <h1 class="hjseo-login-title">HealingJourney SEO Dashboard</h1>
        <p class="hjseo-login-subtitle">Sign in to access your SEO analytics</p>
        
        <?php if ($login_error): ?>
            <div class="hjseo-login-error">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <?php echo esc_html($login_error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="" class="hjseo-login-form">
            <?php wp_nonce_field('hjseo_login_action', 'hjseo_login_nonce'); ?>
            <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/')); ?>">
            
            <div class="hjseo-form-group">
                <label for="user_login">Username</label>
                <input type="text" name="log" id="user_login" class="hjseo-input" required autofocus value="<?php echo esc_attr($username ?? ''); ?>">
            </div>
            
            <div class="hjseo-form-group">
                <label for="user_pass">Password</label>
                <input type="password" name="pwd" id="user_pass" class="hjseo-input" required>
            </div>
            
            <div class="hjseo-form-group hjseo-checkbox-group">
                <label>
                    <input type="checkbox" name="rememberme" value="forever">
                    <span>Remember me</span>
                </label>
            </div>
            
            <button type="submit" class="hjseo-login-button">
                Sign In
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </form>
        
        <div class="hjseo-login-footer">
            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Forgot your password?</a>
        </div>
    </div>
</div>

<style>
.hjseo-login-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
}

.hjseo-login-container {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    padding: 3rem;
    max-width: 420px;
    width: 100%;
    text-align: center;
}

.hjseo-login-logo {
    color: #667eea;
    margin-bottom: 1.5rem;
}

.hjseo-login-logo svg {
    width: 80px;
    height: 80px;
}

.hjseo-login-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    color: #1a202c;
}

.hjseo-login-subtitle {
    color: #718096;
    margin: 0 0 2rem;
}

.hjseo-login-error {
    background: #fed7d7;
    color: #c53030;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-align: left;
}

.hjseo-login-form {
    text-align: left;
}

.hjseo-form-group {
    margin-bottom: 1.5rem;
}

.hjseo-form-group label {
    display: block;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.hjseo-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: all 0.2s;
    box-sizing: border-box;
}

.hjseo-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.hjseo-checkbox-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 400;
    cursor: pointer;
}

.hjseo-checkbox-group input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.hjseo-login-button {
    width: 100%;
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.hjseo-login-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.hjseo-login-button:active {
    transform: translateY(0);
}

.hjseo-login-footer {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.hjseo-login-footer a {
    color: #667eea;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s;
}

.hjseo-login-footer a:hover {
    color: #764ba2;
    text-decoration: underline;
}

@media (max-width: 480px) {
    .hjseo-login-wrapper {
        padding: 1rem;
    }
    
    .hjseo-login-container {
        padding: 2rem 1.5rem;
    }
}
</style>

<?php get_footer(); ?>
