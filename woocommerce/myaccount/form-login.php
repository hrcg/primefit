<?php
/**
 * Login Form Template
 *
 * @package PrimeFit
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' ); ?>

<div class="login-page-container">
    <div class="login-form-wrapper">
        <!-- Sign In Form -->
        <div class="account-form login-form active" id="login-form">
            <h1 class="login-title"><?php esc_html_e( 'SIGN IN', 'primefit' ); ?></h1>
            
            <form class="woocommerce-form woocommerce-form-login login" method="post">
                <?php do_action( 'woocommerce_login_form_start' ); ?>
                
                <div class="form-field">
                    <label for="username"><?php esc_html_e( 'Email', 'primefit' ); ?></label>
                    <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'Email', 'primefit' ); ?>" />
                </div>
                
                <div class="form-field password-field">
                    <div class="password-field-header">
                        <label for="password"><?php esc_html_e( 'Password', 'primefit' ); ?></label>
                        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="forgot-password-link"><?php esc_html_e( 'Forgot your password?', 'primefit' ); ?></a>
                    </div>
                    <div class="password-input-wrapper">
                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="<?php esc_attr_e( 'Password', 'primefit' ); ?>" />
                        <button type="button" class="password-toggle" aria-label="<?php esc_attr_e( 'Show password', 'primefit' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <?php do_action( 'woocommerce_login_form' ); ?>
                
                <div class="form-submit">
                    <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                    <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Sign In', 'primefit' ); ?>"><?php esc_html_e( 'Sign In', 'primefit' ); ?></button>
                </div>
                
                <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
                    <div class="no-account-section">
                        <p class="no-account-text"><?php esc_html_e( "Don't have an account?", 'primefit' ); ?></p>
                        <button type="button" class="create-account-button" id="show-register-form">
                            <?php esc_html_e( 'Create Account', 'primefit' ); ?>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php do_action( 'woocommerce_login_form_end' ); ?>
            </form>
        </div>

        <!-- Register Form -->
        <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
        <div class="account-form register-form" id="register-form">
            <h1 class="login-title"><?php esc_html_e( 'CREATE ACCOUNT', 'primefit' ); ?></h1>
            
            <form class="woocommerce-form woocommerce-form-register register" method="post">
                <?php do_action( 'woocommerce_register_form_start' ); ?>
                
                <div class="form-field">
                    <div class="password-input-wrapper">
                        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'Email', 'primefit' ); ?>" />
                    </div>
                </div>
                
                <div class="form-field password-field">
                    <div class="password-input-wrapper">
                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="reg_password" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Password', 'primefit' ); ?>" />
                        <button type="button" class="password-toggle" aria-label="<?php esc_attr_e( 'Show password', 'primefit' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <?php do_action( 'woocommerce_register_form' ); ?>
                
                <div class="form-submit">
                    <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
                    <button type="submit" class="woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Create Account', 'primefit' ); ?>"><?php esc_html_e( 'Create Account', 'primefit' ); ?></button>
                </div>
                
                <div class="has-account-section">
                    <p class="has-account-text"><?php esc_html_e( 'Already have an account?', 'primefit' ); ?></p>
                    <button type="button" class="sign-in-button" id="show-login-form">
                        <?php esc_html_e( 'Sign In', 'primefit' ); ?>
                    </button>
                </div>
                
                <?php do_action( 'woocommerce_register_form_end' ); ?>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
