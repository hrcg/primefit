<?php
/**
 * Shop Filter Bar
 * 
 * Filter bar with grid toggle and sort dropdown for shop and category pages
 *
 * @package PrimeFit
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only show on shop/category pages
if ( ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
	return;
}

global $wp_query;

// Get current sorting order
$current_orderby = isset( $_GET['orderby'] ) ? wc_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

// Get sorting options
$catalog_orderby_options = apply_filters( 'woocommerce_catalog_orderby', array(
	'menu_order' => __( 'Default sorting', 'woocommerce' ),
	'popularity' => __( 'Sort by popularity', 'woocommerce' ),
	'rating'     => __( 'Sort by average rating', 'woocommerce' ),
	'date'       => __( 'Sort by latest', 'woocommerce' ),
	'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
	'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
) );

// Get current grid view from session/cookie or default to 3 columns
$current_grid = isset( $_COOKIE['primefit_grid_view'] ) ? $_COOKIE['primefit_grid_view'] : '3';

?>
<div class="shop-filter-bar">
	<div class="container">
		<div class="filter-bar-inner">
			<!-- Grid Toggle (Left Side) -->
			<div class="grid-controls">
				<span class="grid-label"><?php esc_html_e( 'Grid:', 'primefit' ); ?></span>
				<div class="grid-options">
					<button class="grid-option<?php echo $current_grid === '2' ? ' active' : ''; ?>" data-grid="2" title="<?php esc_attr_e( '2 columns', 'primefit' ); ?>">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
							<rect x="2" y="2" width="7" height="7"></rect>
							<rect x="11" y="2" width="7" height="7"></rect>
							<rect x="2" y="11" width="7" height="7"></rect>
							<rect x="11" y="11" width="7" height="7"></rect>
						</svg>
					</button>
					<button class="grid-option<?php echo $current_grid === '3' ? ' active' : ''; ?>" data-grid="3" title="<?php esc_attr_e( '3 columns', 'primefit' ); ?>">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
							<rect x="1" y="2" width="4" height="4"></rect>
							<rect x="8" y="2" width="4" height="4"></rect>
							<rect x="15" y="2" width="4" height="4"></rect>
							<rect x="1" y="8" width="4" height="4"></rect>
							<rect x="8" y="8" width="4" height="4"></rect>
							<rect x="15" y="8" width="4" height="4"></rect>
							<rect x="1" y="14" width="4" height="4"></rect>
							<rect x="8" y="14" width="4" height="4"></rect>
							<rect x="15" y="14" width="4" height="4"></rect>
						</svg>
					</button>
					<button class="grid-option<?php echo $current_grid === '4' ? ' active' : ''; ?>" data-grid="4" title="<?php esc_attr_e( '4 columns', 'primefit' ); ?>">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
							<rect x="1" y="1" width="3" height="3"></rect>
							<rect x="6" y="1" width="3" height="3"></rect>
							<rect x="11" y="1" width="3" height="3"></rect>
							<rect x="16" y="1" width="3" height="3"></rect>
							<rect x="1" y="6" width="3" height="3"></rect>
							<rect x="6" y="6" width="3" height="3"></rect>
							<rect x="11" y="6" width="3" height="3"></rect>
							<rect x="16" y="6" width="3" height="3"></rect>
							<rect x="1" y="11" width="3" height="3"></rect>
							<rect x="6" y="11" width="3" height="3"></rect>
							<rect x="11" y="11" width="3" height="3"></rect>
							<rect x="16" y="11" width="3" height="3"></rect>
							<rect x="1" y="16" width="3" height="3"></rect>
							<rect x="6" y="16" width="3" height="3"></rect>
							<rect x="11" y="16" width="3" height="3"></rect>
							<rect x="16" y="16" width="3" height="3"></rect>
						</svg>
					</button>
				</div>
			</div>

			<!-- Product Count -->
			<div class="product-count">
				<?php
				$total = $wp_query->found_posts;
				if ( 1 === intval( $total ) ) {
					_e( 'Showing the single result', 'woocommerce' );
				} elseif ( $total <= wc_get_loop_prop( 'per_page' ) || -1 === wc_get_loop_prop( 'per_page' ) ) {
					/* translators: %d: total results */
					printf( _n( 'Showing all %d result', 'Showing all %d results', $total, 'woocommerce' ), $total );
				} else {
					$first = ( absint( get_query_var( 'paged' ) ) - 1 ) * wc_get_loop_prop( 'per_page' ) + 1;
					$last  = min( $total, wc_get_loop_prop( 'per_page' ) * absint( get_query_var( 'paged' ) ) );
					/* translators: 1: first result 2: last result 3: total results */
					printf( _nx( 'Showing %1$d&ndash;%2$d of %3$d result', 'Showing %1$d&ndash;%2$d of %3$d results', $total, 'with first and last result', 'woocommerce' ), $first, $last, $total );
				}
				?>
			</div>

			<!-- Sort Dropdown (Right Side) -->
			<form class="woocommerce-ordering" method="get">
				<select name="orderby" class="orderby" aria-label="<?php esc_attr_e( 'Shop order', 'woocommerce' ); ?>">
					<?php foreach ( $catalog_orderby_options as $id => $name ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $current_orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="hidden" name="paged" value="1" />
				<?php wc_query_string_form_fields( null, array( 'orderby', 'submit', 'paged', 'product-page' ) ); ?>
			</form>
		</div>
	</div>
</div>
