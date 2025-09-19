<?php
/**
 * My Account Downloads Template
 *
 * @package PrimeFit
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$downloads     = WC()->customer->get_downloadable_products();
$has_downloads = (bool) $downloads;

do_action( 'woocommerce_before_account_downloads', $has_downloads ); ?>

<div class="account-container">
    <div class="account-layout">
        <div class="account-navigation-section">
            <nav class="account-navigation">
                <h2 class="account-navigation-title"><?php esc_html_e( 'My Account', 'primefit' ); ?></h2>
                <?php wc_get_template( 'myaccount/navigation.php' ); ?>
            </nav>
        </div>
        
        <div class="account-content-section">
            <div class="account-content">
                <h1 class="account-content-title"><?php esc_html_e( 'Downloads', 'primefit' ); ?></h1>
                
                <?php if ( $has_downloads ) : ?>
                    <div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
                        <?php esc_html_e( 'No downloads available yet.', 'primefit' ); ?>
                        <a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
                            <?php esc_html_e( 'Browse products', 'primefit' ); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
                        <?php esc_html_e( 'No downloads available yet.', 'primefit' ); ?>
                        <a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
                            <?php esc_html_e( 'Browse products', 'primefit' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php do_action( 'woocommerce_account_downloads', $has_downloads ); ?>
            </div>
        </div>
    </div>
</div>

<?php do_action( 'woocommerce_after_account_downloads', $has_downloads ); ?>
