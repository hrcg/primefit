<?php
/**
 * My Account Dashboard Template
 *
 * @package PrimeFit
 * @since 1.0.0
 * @version 4.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_account_dashboard' ); ?>

<div class="account-container">
    <div class="account-layout">
        <div class="account-content-section">
            <div class="account-content">
                <div class="dashboard-header">
                    <h1 class="dashboard-title"><?php esc_html_e( 'Account Home', 'primefit' ); ?></h1>
                    <h2 class="dashboard-welcome"><?php printf( esc_html__( 'Welcome, %s', 'primefit' ), esc_html( $current_user->display_name ) ); ?></h2>
                </div>
                
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 11V7a4 4 0 0 0-8 0v4"></path>
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            </svg>
                        </div>
                        <h3 class="card-title"><?php esc_html_e( 'My Orders', 'primefit' ); ?></h3>
                        <p class="card-description"><?php esc_html_e( 'View and manage your orders, see the latest delivery information and track packages in your account.', 'primefit' ); ?></p>
                        <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="card-link">
                            <?php esc_html_e( 'View Orders', 'primefit' ); ?>
                        </a>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <h3 class="card-title"><?php esc_html_e( 'Account Details', 'primefit' ); ?></h3>
                        <p class="card-description"><?php esc_html_e( 'Easily update your personal information, change your password, modify your contact preferences and manage your social accounts.', 'primefit' ); ?></p>
                        <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>" class="card-link">
                            <?php esc_html_e( 'Edit Details', 'primefit' ); ?>
                        </a>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16,17 21,12 16,7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </div>
                        <h3 class="card-title"><?php esc_html_e( 'Logout', 'primefit' ); ?></h3>
                        <p class="card-description"><?php esc_html_e( 'Sign out of your account securely and return to the homepage.', 'primefit' ); ?></p>
                        <a href="<?php echo esc_url( wc_logout_url( wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="card-link">
                            <?php esc_html_e( 'Sign Out', 'primefit' ); ?>
                        </a>
                    </div>
                </div>
                
                <div class="woocommerce-MyAccount-content">
                    <?php
                    /**
                     * My Account dashboard.
                     *
                     * @since 2.6.0
                     */
                    do_action( 'woocommerce_account_dashboard' );
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
