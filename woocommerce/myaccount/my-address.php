<?php
/**
 * My Account Addresses Template
 *
 * @package PrimeFit
 * @since 1.0.0
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        array(
            'billing'  => __( 'Billing address', 'primefit' ),
            'shipping' => __( 'Shipping address', 'primefit' ),
        ),
        $customer_id
    );
} else {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        array(
            'billing' => __( 'Billing address', 'primefit' ),
        ),
        $customer_id
    );
}

$oldcol = 1;
$col    = 1;
?>

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
                <h1 class="account-content-title"><?php esc_html_e( 'Addresses', 'primefit' ); ?></h1>
                
                <p><?php esc_html_e( 'The following addresses will be used on the checkout page by default.', 'primefit' ); ?></p>
                
                <div class="woocommerce-Addresses">
                    <?php foreach ( $get_addresses as $name => $title ) : ?>
                        <div class="woocommerce-Address">
                            <header class="woocommerce-Address-title title">
                                <h3><?php echo esc_html( $title ); ?></h3>
                                <a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $name ) ); ?>" class="woocommerce-Address-edit button"><?php echo $address ? esc_html__( 'Edit', 'primefit' ) : esc_html__( 'Add', 'primefit' ); ?></a>
                            </header>
                            <address>
                                <?php
                                $address = wc_get_account_formatted_address( $name );
                                echo $address ? wp_kses_post( $address ) : esc_html_e( 'You have not set up this type of address yet.', 'primefit' );
                                ?>
                            </address>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
