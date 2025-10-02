<?php
/**
 * My Account Edit Address Template
 *
 * @package PrimeFit
 * @since 1.0.0
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$page_title = ( 'billing' === $load_address ) ? __( 'Billing address', 'primefit' ) : __( 'Shipping address', 'primefit' );

do_action( 'woocommerce_before_edit_address_form' ); ?>

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
                <h1 class="account-content-title"><?php echo esc_html( $page_title ); ?></h1>
                
                <form method="post">
                    <?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>
                    
                    <div class="woocommerce-address-fields">
                        <?php
                        foreach ( $address as $key => $field ) {
                            woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
                        }
                        ?>
                    </div>
                    
                    <?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>
                    
                    <p>
                        <button type="submit" class="woocommerce-Button button" name="save_address" value="<?php esc_attr_e( 'Save address', 'primefit' ); ?>"><?php esc_html_e( 'Save address', 'primefit' ); ?></button>
                        <?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
                        <input type="hidden" name="action" value="edit_address" />
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php do_action( 'woocommerce_after_edit_address_form' ); ?>
