<?php
/**
 * My Account Edit Account Template
 *
 * @package PrimeFit
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_edit_account_form' ); ?>

<div class="account-container">
    <div class="account-layout">
        <div class="account-content-section">
            <div class="account-content">
                <div class="dashboard-header">
                    <div class="dashboard-navigation">
                        <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>" class="back-to-dashboard">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 12H5"></path>
                                <polyline points="12,19 5,12 12,5"></polyline>
                            </svg>
                            <?php esc_html_e( 'Back to Dashboard', 'primefit' ); ?>
                        </a>
                    </div>
                </div>
                
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <h3 class="card-title"><?php esc_html_e( 'Account Details', 'primefit' ); ?></h3>
                        <p class="card-description"><?php esc_html_e( 'Easily update your personal information, change your password, modify your contact preferences and manage your social accounts.', 'primefit' ); ?></p>
                        
                        <form class="woocommerce-EditAccountForm edit-account" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?> >
                            <?php do_action( 'woocommerce_edit_account_form_start' ); ?>
                            
                            <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
                                <label for="account_first_name"><?php esc_html_e( 'First name', 'primefit' ); ?>&nbsp;<span class="required">*</span></label>
                                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" placeholder="<?php esc_attr_e( 'First name', 'primefit' ); ?>" />
                            </p>
                            
                            <p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
                                <label for="account_last_name"><?php esc_html_e( 'Last name', 'primefit' ); ?>&nbsp;<span class="required">*</span></label>
                                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" placeholder="<?php esc_attr_e( 'Last name', 'primefit' ); ?>" />
                            </p>
                            
                            <div class="clear"></div>
                            
                            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                                <label for="account_display_name"><?php esc_html_e( 'Display name', 'primefit' ); ?>&nbsp;<span class="required">*</span></label>
                                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" placeholder="<?php esc_attr_e( 'Display name', 'primefit' ); ?>" />
                                <span><em><?php esc_html_e( 'This will be how your name will be displayed in the account section and in reviews', 'primefit' ); ?></em></span>
                            </p>
                            
                            <div class="clear"></div>
                            
                            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                                <label for="account_email"><?php esc_html_e( 'Email address', 'primefit' ); ?>&nbsp;<span class="required">*</span></label>
                                <input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" placeholder="<?php esc_attr_e( 'Email address', 'primefit' ); ?>" />
                            </p>
                            
                            <fieldset>
                                <legend><?php esc_html_e( 'Password change', 'primefit' ); ?></legend>
                                
                                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                                    <label for="password_current"><?php esc_html_e( 'Current password (leave blank to leave unchanged)', 'primefit' ); ?></label>
                                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="off" placeholder="<?php esc_attr_e( 'Current password', 'primefit' ); ?>" />
                                </p>
                                
                                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                                    <label for="password_1"><?php esc_html_e( 'New password (leave blank to leave unchanged)', 'primefit' ); ?></label>
                                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="off" placeholder="<?php esc_attr_e( 'New password', 'primefit' ); ?>" />
                                </p>
                                
                                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                                    <label for="password_2"><?php esc_html_e( 'Confirm new password', 'primefit' ); ?></label>
                                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="off" placeholder="<?php esc_attr_e( 'Confirm new password', 'primefit' ); ?>" />
                                </p>
                            </fieldset>
                            
                            <div class="clear"></div>
                            
                            <?php do_action( 'woocommerce_edit_account_form' ); ?>
                            
                            <p>
                                <?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
                                <button type="submit" class="woocommerce-Button button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'primefit' ); ?>"><?php esc_html_e( 'Save changes', 'primefit' ); ?></button>
                                <input type="hidden" name="action" value="save_account_details" />
                            </p>
                            
                            <?php do_action( 'woocommerce_edit_account_form_end' ); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
