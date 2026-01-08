<?php
/**
 * Product Bundles (PrimeFit)
 *
 * Implements a custom WooCommerce product type "bundle" that:
 * - Has its own product page (normal product single)
 * - Lets admins define bundle items consisting of multiple "color products"
 * - On add-to-cart, adds selected child products/variations individually
 * - Charges the bundle price by distributing it across child line items
 *
 * @package PrimeFit
 * @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Meta keys.
 */
const PRIMEFIT_BUNDLE_ITEMS_META = '_primefit_bundle_items';

/**
 * Custom WC product class for "bundle" type.
 */
if ( ! class_exists( 'WC_Product_PrimeFit_Bundle' ) ) {
	class WC_Product_PrimeFit_Bundle extends WC_Product {
		public function get_type() {
			return 'bundle';
		}

		/**
		 * Bundled items definition.
		 *
		 * Each item:
		 * - key: string
		 * - label: string
		 * - product_ids: int[] (each is a color-specific product)
		 * - qty: int
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public function get_bundle_items() : array {
			$raw = get_post_meta( $this->get_id(), PRIMEFIT_BUNDLE_ITEMS_META, true );
			$items = is_array( $raw ) ? $raw : array();

			$normalized = array();
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$key = isset( $item['key'] ) ? sanitize_key( (string) $item['key'] ) : '';
				$label = isset( $item['label'] ) ? sanitize_text_field( (string) $item['label'] ) : '';
				$qty = isset( $item['qty'] ) ? max( 1, (int) $item['qty'] ) : 1;
				$product_ids = array();

				if ( isset( $item['product_ids'] ) && is_array( $item['product_ids'] ) ) {
					$product_ids = array_values(
						array_unique(
							array_filter(
								array_map( 'absint', $item['product_ids'] )
							)
						)
					);
				}

				if ( $key === '' ) {
					$key = 'item_' . wp_generate_password( 8, false, false );
				}
				if ( empty( $product_ids ) ) {
					continue;
				}

				$normalized[] = array(
					'key' => $key,
					'label' => $label,
					'product_ids' => $product_ids,
					'qty' => $qty,
				);
			}

			return $normalized;
		}
	}
}

/**
 * Map product type => class.
 */
add_filter( 'woocommerce_product_class', function( $classname, $product_type, $product_id ) {
	if ( 'bundle' === $product_type ) {
		return WC_Product_PrimeFit_Bundle::class;
	}
	return $classname;
}, 10, 3 );

/**
 * Register product type in admin selector.
 */
add_filter( 'product_type_selector', function( $types ) {
	$types['bundle'] = __( 'Product bundle', 'primefit' );
	return $types;
} );

/**
 * Admin: ensure pricing fields show for bundle type.
 */
add_action( 'admin_footer', function() {
	if ( ! is_admin() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || $screen->id !== 'product' ) {
		return;
	}
	?>
	<script>
	(function($){
		'use strict';
		// Show pricing for bundle product type.
		$(document).on('woocommerce-product-type-change', function(e, selectVal){
			if (selectVal === 'bundle') {
				$('.options_group.pricing').addClass('show_if_bundle').show();
				$('.general_options.general_tab').addClass('show_if_bundle');
			}
		});
		$(function(){
			var type = $('#product-type').val();
			if (type === 'bundle') {
				$('.options_group.pricing').addClass('show_if_bundle').show();
				$('.general_options.general_tab').addClass('show_if_bundle');
			}
		});
	})(jQuery);
	</script>
	<?php
} );

/**
 * Admin UI: add "Bundle" product data tab.
 */
add_filter( 'woocommerce_product_data_tabs', function( $tabs ) {
	$tabs['primefit_bundle'] = array(
		'label'  => __( 'Bundle', 'primefit' ),
		'target' => 'primefit_bundle_product_data',
		'class'  => array( 'show_if_bundle' ),
		'priority' => 60,
	);
	return $tabs;
} );

add_action( 'woocommerce_product_data_panels', function() {
	global $post;
	if ( ! $post || $post->post_type !== 'product' ) {
		return;
	}

	$product_obj = wc_get_product( $post->ID );
	$current_price = $product_obj ? $product_obj->get_regular_price() : '';

	$items = get_post_meta( $post->ID, PRIMEFIT_BUNDLE_ITEMS_META, true );
	if ( ! is_array( $items ) ) {
		$items = array();
	}
	$search_nonce = wp_create_nonce( 'search-products' );
	?>
	<div id="primefit_bundle_product_data" class="panel woocommerce_options_panel hidden">
		<div class="options_group">
			<?php
			woocommerce_wp_text_input(
				array(
					'id'                => 'primefit_bundle_price',
					'label'             => __( 'Bundle price', 'primefit' ),
					'type'              => 'text',
					'data_type'         => 'price',
					'value'             => $current_price,
					'desc_tip'          => true,
					'description'       => __( 'This is the price charged for the whole bundle (cart/checkout will use this).', 'primefit' ),
					'wrapper_class'     => 'show_if_bundle',
					'class'             => 'wc_input_price short',
					'custom_attributes' => array( 'autocomplete' => 'off' ),
				)
			);
			?>

			<p class="form-field">
				<label><?php esc_html_e( 'Bundle items', 'primefit' ); ?></label>
				<span class="description">
					<?php esc_html_e( 'Each bundle item contains multiple "color products". Customers choose a color (product) and a size (variation) for each item.', 'primefit' ); ?>
				</span>
			</p>

			<div id="primefit-bundle-items" style="margin: 0 12px 12px;"></div>

			<input type="hidden" id="primefit_bundle_items_json" name="primefit_bundle_items_json" value="<?php echo esc_attr( wp_json_encode( $items ) ); ?>" />

			<p class="form-field">
				<button type="button" class="button" id="primefit-add-bundle-item"><?php esc_html_e( 'Add bundle item', 'primefit' ); ?></button>
			</p>

			<p class="form-field">
				<span class="description">
					<?php esc_html_e( 'Tip: Select multiple products per item (one per color). Each selected product should be a variable product with size variations.', 'primefit' ); ?>
				</span>
			</p>
		</div>
	</div>

	<script>
	(function($){
		'use strict';

		const searchNonce = <?php echo wp_json_encode( $search_nonce ); ?>;

		const $root = $('#primefit-bundle-items');
		const $json = $('#primefit_bundle_items_json');

		function uid() {
			return 'item_' + Math.random().toString(36).slice(2, 10);
		}

		function parseItems() {
			try {
				const v = $json.val();
				const arr = JSON.parse(v || '[]');
				return Array.isArray(arr) ? arr : [];
			} catch (e) {
				return [];
			}
		}

		function saveItems(items) {
			$json.val(JSON.stringify(items));
		}

		function render() {
			const items = parseItems();
			$root.empty();

			if (!items.length) {
				$root.append('<p style="margin:0 0 10px;color:#666;">No bundle items yet.</p>');
			}

			items.forEach((item, idx) => {
				const key = item.key || uid();
				item.key = key;
				const label = item.label || '';
				const qty = item.qty || 1;
				const productIds = Array.isArray(item.product_ids) ? item.product_ids : [];

				const row = $(`
					<div class="primefit-bundle-item" style="border:1px solid #ddd;padding:12px;margin:0 0 10px;background:#fff;">
						<div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap;">
							<p class="form-field" style="margin:0;min-width:260px;flex:1;">
								<label style="display:block;margin-bottom:4px;">Item label</label>
								<input type="text" class="short primefit-bundle-label" value="${$('<div>').text(label).html()}" placeholder="e.g. Tee" style="width:100%;" />
								<span class="description">Shown on the bundle page.</span>
							</p>
							<p class="form-field" style="margin:0;min-width:120px;">
								<label style="display:block;margin-bottom:4px;">Qty</label>
								<input type="number" class="small-text primefit-bundle-qty" min="1" value="${parseInt(qty,10) || 1}" />
							</p>
							<p class="form-field" style="margin:0;min-width:300px;flex:2;">
								<label style="display:block;margin-bottom:4px;">Color products</label>
								<select
									class="wc-product-search primefit-bundle-products"
									multiple="multiple"
									style="width:100%;"
									data-placeholder="Select products..."
									data-action="primefit_json_search_products"
									data-security="${searchNonce}"
									data-minimum_input_length="0"
								></select>
								<span class="description">Select one product per color (each product should have size variations).</span>
							</p>
							<p class="form-field" style="margin:0;">
								<label style="display:block;margin-bottom:4px;">&nbsp;</label>
								<button type="button" class="button link-button primefit-remove-bundle-item">Remove</button>
							</p>
						</div>
					</div>
				`);

				// Populate select2 with existing IDs.
				const $select = row.find('.primefit-bundle-products');
				if (productIds.length) {
					// Create options so select2 shows initial values; Woo will load labels async later.
					productIds.forEach((id) => {
						const opt = new Option('#' + id, id, true, true);
						$select.append(opt);
					});
				}

				// Wire events.
				row.on('change', '.primefit-bundle-label, .primefit-bundle-qty, .primefit-bundle-products', function(){
					const updated = parseItems();
					const current = updated[idx] || {};
					current.key = key;
					current.label = row.find('.primefit-bundle-label').val() || '';
					current.qty = parseInt(row.find('.primefit-bundle-qty').val(), 10) || 1;
					current.product_ids = (row.find('.primefit-bundle-products').val() || []).map(v => parseInt(v, 10)).filter(Boolean);
					updated[idx] = current;
					saveItems(updated);
				});

				row.on('click', '.primefit-remove-bundle-item', function(){
					const updated = parseItems();
					updated.splice(idx, 1);
					saveItems(updated);
					render();
				});

				$root.append(row);
			});

			// Ensure Woo select2 init runs AFTER elements are in the DOM.
			$(document.body).trigger('wc-enhanced-select-init');

			saveItems(items);
		}

		$(function(){
			render();
			$('#primefit-add-bundle-item').on('click', function(){
				const items = parseItems();
				items.push({ key: uid(), label: '', qty: 1, product_ids: [] });
				saveItems(items);
				render();
			});
		});
	})(jQuery);
	</script>
	<?php
} );

/**
 * Admin: save bundle items.
 * Priority 20 to run after WooCommerce's standard price processing (priority 10).
 */
add_action( 'woocommerce_process_product_meta', function( $post_id ) {
	// Only for bundle type.
	$type = isset( $_POST['product-type'] ) ? sanitize_key( wp_unslash( $_POST['product-type'] ) ) : '';
	if ( $type !== 'bundle' ) {
		// If product type is changed away from bundle, keep data (non-destructive).
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save bundle price (sync to regular price + current price).
	// Priority: Check custom bundle price field first, then fallback to standard WooCommerce price field
	$bundle_price = '';
	if ( isset( $_POST['primefit_bundle_price'] ) && $_POST['primefit_bundle_price'] !== '' ) {
		$bundle_price = sanitize_text_field( wp_unslash( $_POST['primefit_bundle_price'] ) );
	} elseif ( isset( $_POST['_regular_price'] ) && $_POST['_regular_price'] !== '' ) {
		// Fallback to standard WooCommerce price field if bundle price field is empty
		$bundle_price = sanitize_text_field( wp_unslash( $_POST['_regular_price'] ) );
	}

	// Format and save the price
	if ( $bundle_price !== '' ) {
		$price = wc_format_decimal( $bundle_price );
		if ( $price !== '' && $price >= 0 ) {
			update_post_meta( $post_id, '_regular_price', $price );
			update_post_meta( $post_id, '_price', $price );
			// Clear sale price to avoid confusion
			update_post_meta( $post_id, '_sale_price', '' );
		}
	}
	// Note: We don't clear the price if empty, to allow users to set it via standard WooCommerce fields if needed

	$json = isset( $_POST['primefit_bundle_items_json'] ) ? (string) wp_unslash( $_POST['primefit_bundle_items_json'] ) : '[]';
	$decoded = json_decode( $json, true );
	if ( ! is_array( $decoded ) ) {
		$decoded = array();
	}

	$sanitized = array();
	foreach ( $decoded as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$key = isset( $item['key'] ) ? sanitize_key( (string) $item['key'] ) : '';
		$label = isset( $item['label'] ) ? sanitize_text_field( (string) $item['label'] ) : '';
		$qty = isset( $item['qty'] ) ? max( 1, (int) $item['qty'] ) : 1;
		$product_ids = array();
		if ( isset( $item['product_ids'] ) && is_array( $item['product_ids'] ) ) {
			$product_ids = array_values(
				array_unique(
					array_filter( array_map( 'absint', $item['product_ids'] ) )
				)
			);
		}

		// Validate products exist and are of type product.
		$product_ids = array_values(
			array_filter( $product_ids, function( $pid ) {
				return $pid > 0 && get_post_type( $pid ) === 'product';
			} )
		);

		if ( empty( $product_ids ) ) {
			continue;
		}
		if ( $key === '' ) {
			$key = 'item_' . wp_generate_password( 8, false, false );
		}

		$sanitized[] = array(
			'key' => $key,
			'label' => $label,
			'qty' => $qty,
			'product_ids' => $product_ids,
		);
	}

	update_post_meta( $post_id, PRIMEFIT_BUNDLE_ITEMS_META, $sanitized );
}, 20 );

/**
 * Admin AJAX: product search for bundle builder.
 * Returns products even when term is empty (so admins can pick from a list).
 *
 * Expected by Woo's `wc-product-search` select2 integration:
 * - action: primefit_json_search_products
 * - params: term, security
 * - response: { "123": "Name", ... }
 */
add_action( 'wp_ajax_primefit_json_search_products', function() {
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_die( -1 );
	}
	check_ajax_referer( 'search-products', 'security' );

	// Woo's Select2/SelectWoo commonly sends the search term as `term`, but depending on
	// Woo/WP versions it may come via GET, POST, or under `q`/`search`.
	$term_raw = '';
	if ( isset( $_REQUEST['term'] ) ) {
		$term_raw = wp_unslash( $_REQUEST['term'] );
	} elseif ( isset( $_REQUEST['q'] ) ) {
		$term_raw = wp_unslash( $_REQUEST['q'] );
	} elseif ( isset( $_REQUEST['search'] ) ) {
		$term_raw = wp_unslash( $_REQUEST['search'] );
	}
	if ( is_array( $term_raw ) ) {
		$term_raw = reset( $term_raw );
	}
	$term = trim( sanitize_text_field( (string) $term_raw ) );
	$limit = 50;

	$found = array();

	// If searching, use WooCommerce's product datastore search (includes SKU matching).
	if ( $term !== '' ) {
		$ids = array();

		// 1) Title/content search.
		$q1 = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => $limit,
				's'              => $term,
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $q1->posts ) ) {
			$ids = array_merge( $ids, $q1->posts );
		}

		// 2) SKU search (Woo stores it on `_sku`).
		$q2 = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => $limit,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_sku',
						'value'   => $term,
						'compare' => 'LIKE',
					),
				),
			)
		);
		if ( ! empty( $q2->posts ) ) {
			$ids = array_merge( $ids, $q2->posts );
		}

		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		foreach ( array_slice( $ids, 0, $limit ) as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p ) {
				continue;
			}
			$found[ $p->get_id() ] = rawurldecode( $p->get_formatted_name() );
		}
	}

	// If no term, show recent products. If a term was provided and nothing matched,
	// return an empty result so Select2 can show "No matches found" instead of
	// misleadingly returning a full list.
	if ( $term === '' ) {
		if ( function_exists( 'wc_get_products' ) ) {
			$products = wc_get_products(
				array(
					'status'  => array( 'publish', 'private' ),
					'limit'   => $limit,
					'orderby' => 'date',
					'order'   => 'DESC',
					'return'  => 'objects',
				)
			);

			foreach ( $products as $product ) {
				if ( ! $product instanceof WC_Product ) {
					continue;
				}
				$found[ $product->get_id() ] = rawurldecode( $product->get_formatted_name() );
			}
		}
	}

	wp_send_json( $found );
} );

/**
 * Cart item meta keys for bundle grouping/pricing.
 */
const PRIMEFIT_BUNDLE_CART_GROUP_ID          = 'primefit_bundle_group_id';
const PRIMEFIT_BUNDLE_CART_BUNDLE_ID         = 'primefit_bundle_product_id';
const PRIMEFIT_BUNDLE_CART_BUNDLE_NAME       = 'primefit_bundle_product_name';
const PRIMEFIT_BUNDLE_CART_BUNDLE_PRICE      = 'primefit_bundle_price';
const PRIMEFIT_BUNDLE_CART_BUNDLE_QTY        = 'primefit_bundle_qty';
const PRIMEFIT_BUNDLE_CART_ITEM_KEY          = 'primefit_bundle_item_key';
const PRIMEFIT_BUNDLE_CART_ITEM_LABEL        = 'primefit_bundle_item_label';
const PRIMEFIT_BUNDLE_CART_ITEM_QTY          = 'primefit_bundle_item_qty';
const PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE  = 'primefit_bundle_child_base_price';

/**
 * Helper: calculate total bundle savings currently in the cart.
 *
 * Savings are computed as: (sum of original/base item totals) - (bundle price * bundle qty)
 * across each bundle group.
 */
function primefit_bundle_get_cart_savings_total() : float {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}

	$savings = 0.0;
	$groups  = array(); // gid => array( base_total, bundle_total )

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		if ( empty( $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) ) {
			continue;
		}

		$gid = (string) $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ];
		if ( ! isset( $groups[ $gid ] ) ) {
			$bundle_price = isset( $cart_item[ PRIMEFIT_BUNDLE_CART_BUNDLE_PRICE ] ) ? (float) $cart_item[ PRIMEFIT_BUNDLE_CART_BUNDLE_PRICE ] : 0.0;
			$bundle_qty   = isset( $cart_item[ PRIMEFIT_BUNDLE_CART_BUNDLE_QTY ] ) ? max( 1, (int) $cart_item[ PRIMEFIT_BUNDLE_CART_BUNDLE_QTY ] ) : 1;
			$groups[ $gid ] = array(
				'base_total' => 0.0,
				'bundle_total' => $bundle_price * $bundle_qty,
			);
		}

		$qty       = isset( $cart_item['quantity'] ) ? max( 1, (int) $cart_item['quantity'] ) : 1;
		$base_unit = isset( $cart_item[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] ) ? (float) $cart_item[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] : 0.0;
		if ( $base_unit <= 0 && isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product ) {
			// Fallback for cases where cart item meta wasn't persisted: use regular price.
			$base_unit = (float) $cart_item['data']->get_regular_price();
			if ( $base_unit <= 0 ) {
				$base_unit = (float) $cart_item['data']->get_price();
			}
		}
		if ( $base_unit > 0 ) {
			$groups[ $gid ]['base_total'] += ( $base_unit * $qty );
		}
	}

	foreach ( $groups as $g ) {
		$diff = (float) $g['base_total'] - (float) $g['bundle_total'];
		if ( $diff > 0 ) {
			$savings += $diff;
		}
	}

	return (float) $savings;
}

/**
 * Helper: calculate the bundle total (sum of bundle_price * bundle_qty) across all bundle groups in cart.
 *
 * This is the "bundle price" that should be charged/displayed, independent of how the price is distributed
 * across child line items.
 */
function primefit_bundle_get_cart_bundle_total() : float {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}

	$total  = 0.0;
	$groups = array(); // gid => bundle_total

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		if ( empty( $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) ) {
			continue;
		}

		$gid = (string) $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ];
		if ( isset( $groups[ $gid ] ) ) {
			continue;
		}

		$bundle_price = isset( $cart_item[ PRIMEFIT_BUNDLE_CART_BUNDLE_PRICE ] ) ? (float) $cart_item[ PRIMEFIT_BUNDLE_CART_BUNDLE_PRICE ] : 0.0;
		$bundle_qty   = isset( $cart_item[ PRIMEFIT_BUNDLE_CART_BUNDLE_QTY ] ) ? max( 1, (int) $cart_item[ PRIMEFIT_BUNDLE_CART_BUNDLE_QTY ] ) : 1;

		$groups[ $gid ] = $bundle_price * $bundle_qty;
	}

	foreach ( $groups as $bundle_total ) {
		$total += (float) $bundle_total;
	}

	return (float) $total;
}

/**
 * Helper: calculate the original (non-bundle-discounted) total price for all items in the cart.
 *
 * - Bundle child items use the stored base price.
 * - Non-bundle items use the cart line subtotal (pre-coupon) where available.
 */
function primefit_bundle_get_cart_original_items_total() : float {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}

	$total = 0.0;

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$qty = isset( $cart_item['quantity'] ) ? max( 1, (int) $cart_item['quantity'] ) : 1;

		// Bundle item: use base unit price.
		if ( ! empty( $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) ) {
			$base_unit = isset( $cart_item[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] ) ? (float) $cart_item[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] : 0.0;
			if ( $base_unit <= 0 && isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product ) {
				$base_unit = (float) $cart_item['data']->get_regular_price();
				if ( $base_unit <= 0 ) {
					$base_unit = (float) $cart_item['data']->get_price();
				}
			}
			if ( $base_unit > 0 ) {
				$total += ( $base_unit * $qty );
				continue;
			}
		}

		// Non-bundle: prefer cart line subtotal (pre-coupon) if present.
		if ( isset( $cart_item['line_subtotal'] ) ) {
			$total += (float) $cart_item['line_subtotal'];
			continue;
		}

		// Fallback: product price * qty.
		if ( isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product ) {
			$total += ( (float) $cart_item['data']->get_price() * $qty );
		}
	}

	return (float) $total;
}

/**
 * Checkout display: show original/base prices for bundle child items (not the distributed bundle prices).
 */
add_filter( 'woocommerce_cart_item_price', function( $price_html, $cart_item, $cart_item_key ) {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
		return $price_html;
	}
	if ( empty( $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) ) {
		return $price_html;
	}

	$base_unit = isset( $cart_item[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] ) ? (float) $cart_item[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] : 0.0;
	if ( $base_unit <= 0 ) {
		return $price_html;
	}

	return wc_price( $base_unit );
}, 20, 3 );

add_filter( 'woocommerce_cart_item_subtotal', function( $subtotal_html, $cart_item, $cart_item_key ) {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
		return $subtotal_html;
	}
	if ( empty( $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) ) {
		return $subtotal_html;
	}

	$base_unit = isset( $cart_item[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] ) ? (float) $cart_item[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] : 0.0;
	if ( $base_unit <= 0 ) {
		return $subtotal_html;
	}

	$qty = isset( $cart_item['quantity'] ) ? max( 1, (int) $cart_item['quantity'] ) : 1;
	return wc_price( $base_unit * $qty );
}, 20, 3 );

/**
 * Checkout display: show "Price of all items" and "You save" after Total, only when bundle savings exist.
 */
add_action( 'woocommerce_review_order_after_order_total', function() {
	$savings = primefit_bundle_get_cart_savings_total();
	if ( $savings <= 0 ) {
		return;
	}
	$original_items_total = primefit_bundle_get_cart_original_items_total();
	?>
	<tr class="primefit-bundle-original-items-total">
		<th><?php esc_html_e( 'Price of all items', 'primefit' ); ?></th>
		<td data-title="<?php echo esc_attr__( 'Price of all items', 'primefit' ); ?>"><?php echo wp_kses_post( wc_price( $original_items_total ) ); ?></td>
	</tr>
	<tr class="primefit-bundle-savings">
		<th><?php esc_html_e( 'You save', 'primefit' ); ?></th>
		<td data-title="<?php echo esc_attr__( 'You save', 'primefit' ); ?>"><?php echo wp_kses_post( wc_price( $savings ) ); ?></td>
	</tr>
	<?php
}, 20 );

/**
 * Frontend: enqueue bundle script on bundle product pages.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product_id = get_the_ID();
	if ( ! $product_id ) {
		return;
	}

	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_type( 'bundle' ) ) {
		return;
	}

	$ver = defined( 'PRIMEFIT_VERSION' ) ? PRIMEFIT_VERSION : null;
	if ( function_exists( 'primefit_get_file_version' ) ) {
		$ver = primefit_get_file_version( '/assets/js/bundle.js' );
	}

	wp_enqueue_script(
		'primefit-bundle',
		defined( 'PRIMEFIT_THEME_URI' ) ? PRIMEFIT_THEME_URI . '/assets/js/bundle.js' : get_template_directory_uri() . '/assets/js/bundle.js',
		array( 'jquery' ),
		$ver,
		true
	);
} );

/**
 * Helper: best-effort "Color" label for a product (since color is separate product).
 */
function primefit_bundle_get_color_label( WC_Product $product ) : string {
	// Try taxonomy/product attribute that looks like "color".
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute->is_taxonomy() ) {
			continue;
		}
		$name  = $attribute->get_name();
		$label = wc_attribute_label( $name );
		if ( stripos( $label, 'color' ) !== false || stripos( $name, 'color' ) !== false ) {
			$val = trim( (string) $product->get_attribute( $name ) );
			if ( $val !== '' ) {
				return $val;
			}
		}
	}

	// Fallback: parse title suffix after a dash (e.g. "Product X – Black").
	$title = $product->get_name();
	$parts = preg_split( '/\s+[-–—]\s+/', $title );
	if ( is_array( $parts ) && count( $parts ) > 1 ) {
		$suffix = trim( (string) end( $parts ) );
		if ( $suffix !== '' ) {
			return $suffix;
		}
	}

	return $product->get_name();
}

/**
 * Frontend: render bundle add-to-cart form.
 *
 * WooCommerce calls do_action( "woocommerce_{$type}_add_to_cart" ).
 */
add_action( 'woocommerce_bundle_add_to_cart', function() {
	global $product;
	if ( ! $product || ! $product->is_type( 'bundle' ) ) {
		return;
	}

	$bundle_items = method_exists( $product, 'get_bundle_items' ) ? $product->get_bundle_items() : array();
	if ( empty( $bundle_items ) ) {
		echo '<p class="primefit-bundle-empty">' . esc_html__( 'This bundle is not configured yet.', 'primefit' ) . '</p>';
		return;
	}

	wc_get_template(
		'single-product/add-to-cart/bundle.php',
		array(
			'bundle_product' => $product,
			'bundle_items'   => $bundle_items,
		)
	);
} );

/**
 * Add-to-cart handler for bundle type.
 */
add_action( 'woocommerce_add_to_cart_handler_bundle', function( $url = false ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$nonce = isset( $_POST['primefit_bundle_nonce'] ) ? (string) wp_unslash( $_POST['primefit_bundle_nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'primefit_bundle_add_to_cart' ) ) {
		wc_add_notice( __( 'Security check failed. Please try again.', 'primefit' ), 'error' );
		return;
	}

	$bundle_id  = isset( $_REQUEST['add-to-cart'] ) ? absint( wp_unslash( $_REQUEST['add-to-cart'] ) ) : 0;
	$bundle_qty = isset( $_REQUEST['quantity'] ) ? max( 1, (int) wp_unslash( $_REQUEST['quantity'] ) ) : 1;

	$bundle_product = $bundle_id ? wc_get_product( $bundle_id ) : null;
	if ( ! $bundle_product || ! $bundle_product->is_type( 'bundle' ) ) {
		return;
	}

	$bundle_items = method_exists( $bundle_product, 'get_bundle_items' ) ? $bundle_product->get_bundle_items() : array();
	if ( empty( $bundle_items ) ) {
		wc_add_notice( __( 'This bundle is not configured.', 'primefit' ), 'error' );
		return;
	}

	$selected_products  = isset( $_POST['primefit_bundle_item_product'] ) && is_array( $_POST['primefit_bundle_item_product'] ) ? wp_unslash( $_POST['primefit_bundle_item_product'] ) : array();
	$selected_variation = isset( $_POST['primefit_bundle_item_variation'] ) && is_array( $_POST['primefit_bundle_item_variation'] ) ? wp_unslash( $_POST['primefit_bundle_item_variation'] ) : array();

	$group_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : (string) ( time() . '_' . wp_generate_password( 6, false, false ) );

	$bundle_price = (float) $bundle_product->get_price();
	$bundle_name  = (string) $bundle_product->get_name();

	$added_any = false;

	foreach ( $bundle_items as $item ) {
		$item_key   = sanitize_key( (string) ( $item['key'] ?? '' ) );
		$item_label = sanitize_text_field( (string) ( $item['label'] ?? '' ) );
		$item_qty   = isset( $item['qty'] ) ? max( 1, (int) $item['qty'] ) : 1;
		$allowed    = isset( $item['product_ids'] ) && is_array( $item['product_ids'] ) ? array_map( 'absint', $item['product_ids'] ) : array();

		if ( $item_key === '' || empty( $allowed ) ) {
			continue;
		}

		$child_product_id = isset( $selected_products[ $item_key ] ) ? absint( $selected_products[ $item_key ] ) : 0;
		$variation_id     = isset( $selected_variation[ $item_key ] ) ? absint( $selected_variation[ $item_key ] ) : 0;

		if ( ! $child_product_id || ! in_array( $child_product_id, $allowed, true ) ) {
			wc_add_notice( __( 'Please select a color for all bundle items.', 'primefit' ), 'error' );
			return;
		}

		$child_product = wc_get_product( $child_product_id );
		if ( ! $child_product ) {
			wc_add_notice( __( 'Selected product is not available.', 'primefit' ), 'error' );
			return;
		}

		$child_qty = $bundle_qty * $item_qty;

		$variation_data = array();
		$base_price     = (float) $child_product->get_regular_price();
		if ( $base_price <= 0 ) {
			$base_price = (float) $child_product->get_price();
		}

		if ( $child_product->is_type( 'variable' ) ) {
			if ( ! $variation_id ) {
				wc_add_notice( __( 'Please select a size for all bundle items.', 'primefit' ), 'error' );
				return;
			}

			$variation = wc_get_product( $variation_id );
			if ( ! $variation || ! $variation->is_type( 'variation' ) || (int) $variation->get_parent_id() !== (int) $child_product_id ) {
				wc_add_notice( __( 'Selected size is not available.', 'primefit' ), 'error' );
				return;
			}

			if ( ! $variation->is_purchasable() || ! $variation->is_in_stock() ) {
				wc_add_notice( __( 'Selected size is out of stock.', 'primefit' ), 'error' );
				return;
			}

			$variation_data = $variation->get_variation_attributes();
			$base_price     = (float) $variation->get_regular_price();
			if ( $base_price <= 0 ) {
				$base_price = (float) $variation->get_price();
			}
		} else {
			if ( ! $child_product->is_purchasable() || ! $child_product->is_in_stock() ) {
				wc_add_notice( __( 'Selected product is out of stock.', 'primefit' ), 'error' );
				return;
			}
		}

		$cart_item_data = array(
			PRIMEFIT_BUNDLE_CART_GROUP_ID         => $group_id,
			PRIMEFIT_BUNDLE_CART_BUNDLE_ID        => $bundle_id,
			PRIMEFIT_BUNDLE_CART_BUNDLE_NAME      => $bundle_name,
			PRIMEFIT_BUNDLE_CART_BUNDLE_PRICE     => $bundle_price,
			PRIMEFIT_BUNDLE_CART_BUNDLE_QTY       => $bundle_qty,
			PRIMEFIT_BUNDLE_CART_ITEM_KEY         => $item_key,
			PRIMEFIT_BUNDLE_CART_ITEM_LABEL       => $item_label,
			PRIMEFIT_BUNDLE_CART_ITEM_QTY         => $item_qty,
			PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE => $base_price,
		);

		$cart_item_key = WC()->cart->add_to_cart( $child_product_id, $child_qty, $variation_id, $variation_data, $cart_item_data );
		if ( $cart_item_key ) {
			$added_any = true;
		}
	}

	if ( ! $added_any ) {
		wc_add_notice( __( 'Unable to add bundle to cart.', 'primefit' ), 'error' );
		return;
	}

	wc_add_notice( sprintf( __( '"%s" was added to your cart.', 'primefit' ), $bundle_name ), 'success' );

	// Redirect similar to core behavior.
	$redirect = apply_filters( 'woocommerce_add_to_cart_redirect', $url, $bundle_product );
	if ( $redirect ) {
		wp_safe_redirect( $redirect );
		exit;
	}
} );

/**
 * Pricing: distribute bundle price across child line items so cart total equals bundle price.
 */
add_action( 'woocommerce_before_calculate_totals', function( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	if ( ! $cart || ! method_exists( $cart, 'get_cart' ) ) {
		return;
	}

	// Prevent re-entrancy for the same cart object within a single request.
	if ( isset( $cart->primefit_bundle_pricing_ran ) && $cart->primefit_bundle_pricing_ran ) {
		return;
	}

	$contents = $cart->get_cart();
	$groups = array();
	foreach ( $contents as $cart_item_key => $cart_item ) {
		if ( empty( $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) ) {
			continue;
		}
		$gid = (string) $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ];
		$groups[ $gid ][] = $cart_item_key;
	}

	if ( empty( $groups ) ) {
		return;
	}

	$cart->primefit_bundle_pricing_ran = true;

	$decimals = wc_get_price_decimals();
	$factor   = pow( 10, $decimals );

	foreach ( $groups as $gid => $keys ) {
		// Read bundle unit price + bundle qty from first item.
		if ( empty( $contents[ $keys[0] ] ) ) {
			continue;
		}
		$first = $contents[ $keys[0] ];

		$bundle_price = isset( $first[ PRIMEFIT_BUNDLE_CART_BUNDLE_PRICE ] ) ? (float) $first[ PRIMEFIT_BUNDLE_CART_BUNDLE_PRICE ] : 0.0;
		$bundle_qty   = isset( $first[ PRIMEFIT_BUNDLE_CART_BUNDLE_QTY ] ) ? max( 1, (int) $first[ PRIMEFIT_BUNDLE_CART_BUNDLE_QTY ] ) : 1;
		$target_total = $bundle_price * $bundle_qty;

		// Sum base totals (regular price) to allocate proportionally.
		$base_total = 0.0;
		$line_base  = array();
		foreach ( $keys as $cart_item_key ) {
			if ( empty( $contents[ $cart_item_key ] ) ) {
				continue;
			}
			$item = $contents[ $cart_item_key ];
			if ( empty( $item['data'] ) ) {
				continue;
			}
			$qty = isset( $item['quantity'] ) ? max( 1, (int) $item['quantity'] ) : 1;
			$base = isset( $item[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] ) ? (float) $item[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] : (float) $item['data']->get_regular_price();
			if ( $base <= 0 ) {
				$base = (float) $item['data']->get_price();
			}
			$total = $base * $qty;
			$line_base[ $cart_item_key ] = array( 'qty' => $qty, 'total' => $total );
			$base_total += $total;
		}

		if ( $base_total <= 0 ) {
			// Fallback: equal split.
			$base_total = (float) count( $line_base );
			foreach ( $line_base as $k => $v ) {
				$line_base[ $k ]['total'] = 1.0;
			}
		}

		// Allocate in integer cents to avoid drift.
		$target_cents = (int) round( $target_total * $factor );
		$allocated_cents = 0;
		$last_key = '';
		$preferred_remainder_key = '';

		foreach ( $line_base as $cart_item_key => $info ) {
			$last_key = $cart_item_key;
			if ( empty( $preferred_remainder_key ) && isset( $info['qty'] ) && (int) $info['qty'] === 1 ) {
				$preferred_remainder_key = $cart_item_key;
			}
			$share = $info['total'] / $base_total;
			$cents = (int) floor( $target_cents * $share );
			$line_base[ $cart_item_key ]['alloc_cents'] = $cents;
			$allocated_cents += $cents;
		}

		// Add remainder to a qty=1 line when possible (reduces fractional-cent unit pricing).
		$remainder_key = $preferred_remainder_key !== '' ? $preferred_remainder_key : $last_key;
		if ( $remainder_key !== '' ) {
			$line_base[ $remainder_key ]['alloc_cents'] += ( $target_cents - $allocated_cents );
		}

		foreach ( $line_base as $cart_item_key => $info ) {
			if ( empty( $contents[ $cart_item_key ] ) ) {
				continue;
			}
			$item = $contents[ $cart_item_key ];
			if ( empty( $item['data'] ) ) {
				continue;
			}

			$qty = max( 1, (int) $info['qty'] );
			$unit = ( (float) $info['alloc_cents'] / (float) $factor ) / (float) $qty;
			if ( $unit < 0 ) {
				$unit = 0.0;
			}

			// IMPORTANT: clone the product object so bundle pricing doesn't leak into
			// non-bundle cart lines that happen to reference the same product instance.
			if ( isset( $cart->cart_contents[ $cart_item_key ]['data'] ) && $cart->cart_contents[ $cart_item_key ]['data'] instanceof WC_Product ) {
				$cart->cart_contents[ $cart_item_key ]['data'] = clone $cart->cart_contents[ $cart_item_key ]['data'];
				$cart->cart_contents[ $cart_item_key ]['data']->set_price( $unit );
			} else {
				$item['data']->set_price( $unit );
			}
		}
	}
}, 20 );

/**
 * Safety: prevent cart quantity updates for bundle child items (covers any cart update routes).
 */
add_filter( 'woocommerce_update_cart_validation', function( $passed, $cart_item_key, $values, $quantity ) {
	if ( ! $passed ) {
		return $passed;
	}
	if ( ! empty( $values[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) ) {
		wc_add_notice( __( 'Bundle item quantity cannot be changed.', 'primefit' ), 'error' );
		return false;
	}
	return $passed;
}, 20, 4 );

/**
 * Cart display: do not show any "Bundle" context on line items (customer-facing).
 */
add_filter( 'woocommerce_get_item_data', function( $item_data, $cart_item ) {
	if ( empty( $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) ) {
		return $item_data;
	}

	return $item_data;
}, 10, 2 );

/**
 * Prevent bundle "parent" product from being purchased/added directly from loops.
 * Bundles must be configured on the single product page so we can add the selected children.
 */
add_filter( 'woocommerce_loop_add_to_cart_link', function( $html, $product, $args ) {
	if ( ! $product instanceof WC_Product ) {
		return $html;
	}
	if ( ! $product->is_type( 'bundle' ) ) {
		return $html;
	}

	$permalink = $product->get_permalink();
	$label     = esc_html__( 'Select options', 'woocommerce' );

	$classes = isset( $args['class'] ) ? (string) $args['class'] : 'button';
	$classes = trim( $classes . ' product_type_bundle' );

	return sprintf(
		'<a href="%1$s" class="%2$s" aria-label="%3$s" rel="nofollow">%4$s</a>',
		esc_url( $permalink ),
		esc_attr( $classes ),
		esc_attr( sprintf( __( 'Select options for &ldquo;%s&rdquo;', 'woocommerce' ), $product->get_name() ) ),
		$label
	);
}, 10, 3 );

/**
 * Safety net: if a bundle "parent" product gets added to cart (e.g. via AJAX),
 * remove it so the cart contains ONLY the children.
 */
add_action( 'woocommerce_add_to_cart', function( $cart_item_key, $product_id ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$product = $product_id ? wc_get_product( $product_id ) : null;
	if ( ! $product || ! $product->is_type( 'bundle' ) ) {
		return;
	}

	// Remove the parent bundle line item unconditionally.
	if ( $cart_item_key && WC()->cart->get_cart_item( $cart_item_key ) ) {
		WC()->cart->remove_cart_item( $cart_item_key );
	}
}, 1, 2 );

/**
 * Cart removal: removing one bundle child removes all children in the same bundle group.
 */
add_action( 'woocommerce_cart_item_removed', function( $removed_cart_item_key, $cart ) {
	static $in_progress = false;
	if ( $in_progress ) {
		return;
	}
	if ( ! $cart ) {
		return;
	}

	$removed = array();
	if ( method_exists( $cart, 'get_removed_cart_contents' ) ) {
		$removed = $cart->get_removed_cart_contents();
	} elseif ( isset( $cart->removed_cart_contents ) && is_array( $cart->removed_cart_contents ) ) {
		$removed = $cart->removed_cart_contents;
	}
	if ( empty( $removed[ $removed_cart_item_key ] ) ) {
		return;
	}
	$removed_item = $removed[ $removed_cart_item_key ];
	if ( empty( $removed_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) ) {
		return;
	}

	$gid = (string) $removed_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ];

	$in_progress = true;
	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		if ( ! empty( $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) && (string) $cart_item[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] === $gid ) {
			$cart->remove_cart_item( $cart_item_key );
		}
	}
	$in_progress = false;
}, 10, 2 );

/**
 * Order meta: preserve bundle relationship on order line items.
 */
add_action( 'woocommerce_checkout_create_order_line_item', function( $item, $cart_item_key, $values, $order ) {
	if ( empty( $values[ PRIMEFIT_BUNDLE_CART_GROUP_ID ] ) ) {
		return;
	}

	$item->add_meta_data( '_primefit_bundle_group_id', (string) $values[ PRIMEFIT_BUNDLE_CART_GROUP_ID ], true );
	$item->add_meta_data( '_primefit_bundle_product_id', (int) ( $values[ PRIMEFIT_BUNDLE_CART_BUNDLE_ID ] ?? 0 ), true );
	$item->add_meta_data( '_primefit_bundle_product_name', (string) ( $values[ PRIMEFIT_BUNDLE_CART_BUNDLE_NAME ] ?? '' ), true );
	$item->add_meta_data( '_primefit_bundle_item_key', (string) ( $values[ PRIMEFIT_BUNDLE_CART_ITEM_KEY ] ?? '' ), true );
	$item->add_meta_data( '_primefit_bundle_item_label', (string) ( $values[ PRIMEFIT_BUNDLE_CART_ITEM_LABEL ] ?? '' ), true );
	$item->add_meta_data( '_primefit_bundle_price', (string) ( $values[ PRIMEFIT_BUNDLE_CART_BUNDLE_PRICE ] ?? '' ), true );
	$item->add_meta_data( '_primefit_bundle_qty', (int) ( $values[ PRIMEFIT_BUNDLE_CART_BUNDLE_QTY ] ?? 1 ), true );

	// Preserve the original (non-bundle-adjusted) price for display on the order/thank-you page.
	// Note: cart item line totals may have been modified in `woocommerce_before_calculate_totals`.
	$base_unit_price = isset( $values[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] ) ? (float) $values[ PRIMEFIT_BUNDLE_CART_CHILD_BASE_PRICE ] : 0.0;
	$qty             = isset( $values['quantity'] ) ? max( 1, (int) $values['quantity'] ) : 1;
	if ( $base_unit_price > 0 ) {
		$item->add_meta_data( '_primefit_bundle_child_base_price', wc_format_decimal( $base_unit_price, wc_get_price_decimals() ), true );
		$item->add_meta_data( '_primefit_bundle_child_base_line_total', wc_format_decimal( $base_unit_price * $qty, wc_get_price_decimals() ), true );
	}
}, 10, 4 );

