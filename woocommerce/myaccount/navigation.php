<?php
/**
 * My Account Navigation Template
 *
 * @package PrimeFit
 * @since 1.0.0
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$endpoints = wc_get_account_menu_items();
?>

<ul class="account-navigation-menu">
    <?php foreach ( $endpoints as $endpoint => $label ) : ?>
        <li>
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
