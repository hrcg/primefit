<?php
/**
 * Lost Password Form Template
 *
 * @package PrimeFit
 * @since 1.0.0
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_lost_password_form' ); ?>

<div class="login-page-container">
    <div class="login-form-wrapper">
        <?php if ( isset( $_GET['reset-link-sent'] ) && 'true' === $_GET['reset-link-sent'] ) : ?>
            <!-- Success State -->
            <div class="account-form lost-password-success active">
                <h1 class="login-title"><?php esc_html_e( 'CHECK YOUR EMAIL', 'primefit' ); ?></h1>
                
                <div class="success-message">
                    <p><?php esc_html_e( 'Password reset email has been sent.', 'primefit' ); ?></p>
                    <p><?php esc_html_e( 'A password reset email has been sent to the email address on file for your account, but may take several minutes to show up in your inbox. Please wait at least 10 minutes before attempting another reset.', 'primefit' ); ?></p>
                </div>
                
                <div class="back-to-login-section">
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="back-to-login-button">
                        <?php esc_html_e( 'Back to Sign In', 'primefit' ); ?>
                    </a>
                </div>
            </div>
        <?php else : ?>
            <!-- Form State -->
            <div class="account-form lost-password-form active">
                <h1 class="login-title"><?php esc_html_e( 'RESET PASSWORD', 'primefit' ); ?></h1>
                
                <form method="post" class="woocommerce-ResetPassword lost_reset_password">
                    <?php do_action( 'woocommerce_lostpassword_form_start' ); ?>
                    
                    <div class="form-field">
                        <label for="user_login"><?php esc_html_e( 'Username or email', 'primefit' ); ?></label>
                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="text" name="user_login" id="user_login" autocomplete="username" placeholder="<?php esc_attr_e( 'Username or email', 'primefit' ); ?>" />
                    </div>
                    
                    <?php do_action( 'woocommerce_lostpassword_form' ); ?>
                    
                    <div class="form-submit">
                        <?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>
                        <input type="hidden" name="wc_reset_password" value="true" />
                        <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" value="<?php esc_attr_e( 'Reset password', 'primefit' ); ?>"><?php esc_html_e( 'Reset password', 'primefit' ); ?></button>
                    </div>
                    
                    <div class="back-to-login-section">
                        <p class="back-to-login-text"><?php esc_html_e( 'Remember your password?', 'primefit' ); ?></p>
                        <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="back-to-login-button">
                            <?php esc_html_e( 'Back to Sign In', 'primefit' ); ?>
                        </a>
                    </div>
                    
                    <?php do_action( 'woocommerce_lostpassword_form_end' ); ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php do_action( 'woocommerce_after_lost_password_form' ); ?>
