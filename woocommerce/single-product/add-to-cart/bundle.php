<?php
/**
 * Bundle add-to-cart template.
 *
 * Variables:
 * - $bundle_product (WC_Product)
 * - $bundle_items (array)
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $bundle_product ) || ! $bundle_product instanceof WC_Product ) {
	return;
}

if ( empty( $bundle_items ) || ! is_array( $bundle_items ) ) {
	return;
}

/**
 * Build UI data for JS (products, colors, sizes, regular prices).
 */
$items_ui = array();
foreach ( $bundle_items as $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}

	$key        = sanitize_key( (string) ( $item['key'] ?? '' ) );
	$label      = sanitize_text_field( (string) ( $item['label'] ?? '' ) );
	$item_qty   = isset( $item['qty'] ) ? max( 1, (int) $item['qty'] ) : 1;
	$product_ids = isset( $item['product_ids'] ) && is_array( $item['product_ids'] ) ? array_values( array_filter( array_map( 'absint', $item['product_ids'] ) ) ) : array();

	if ( $key === '' || empty( $product_ids ) ) {
		continue;
	}

	$products_ui = array();
	foreach ( $product_ids as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			continue;
		}

		$color = function_exists( 'primefit_bundle_get_color_label' ) ? primefit_bundle_get_color_label( $p ) : $p->get_name();

		$image_id = $p->get_image_id();
		$thumb_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : '';

		$sizes = array();
		$is_variable = $p->is_type( 'variable' );
		$product_regular = (float) $p->get_regular_price();
		if ( $product_regular <= 0 ) {
			$product_regular = (float) $p->get_price();
		}
		if ( $product_regular <= 0 && $is_variable ) {
			// Best-effort fallback for variable products.
			$product_regular = (float) $p->get_variation_regular_price( 'min', true );
			if ( $product_regular <= 0 ) {
				$product_regular = (float) $p->get_variation_price( 'min', true );
			}
		}

		if ( $is_variable ) {
			$variations = false;
			if ( function_exists( 'primefit_get_cached_product_variations' ) ) {
				$variations = primefit_get_cached_product_variations( $pid );
			}
			if ( false === $variations ) {
				$variations = $p->get_available_variations();
				if ( function_exists( 'primefit_cache_product_variations' ) ) {
					primefit_cache_product_variations( $pid, $variations );
				}
			}

			// Detect the "size" attribute key used by variations (robust across locales/custom naming).
			$size_attr_key = '';
			$attr_keys_all = array(); // key => distinct values
			if ( is_array( $variations ) ) {
				foreach ( $variations as $v ) {
					$attrs = isset( $v['attributes'] ) && is_array( $v['attributes'] ) ? $v['attributes'] : array();
					foreach ( $attrs as $attr_key => $attr_val ) {
						$attr_key = (string) $attr_key;
						$attr_val = (string) $attr_val;
						if ( $attr_key === '' ) {
							continue;
						}
						if ( ! isset( $attr_keys_all[ $attr_key ] ) ) {
							$attr_keys_all[ $attr_key ] = array();
						}
						if ( $attr_val !== '' ) {
							$attr_keys_all[ $attr_key ][ $attr_val ] = true;
						}
					}
				}
			}

			$attr_keys = array_keys( $attr_keys_all );

			// 1) Prefer keys/labels that look like "size" (including common non-English variants).
			$size_like_patterns = array( 'size', 'madh', 'masa', 'talla', 'taglia', 'taille', 'grösse', 'grosse', 'rozmiar' );
			foreach ( $attr_keys as $attr_key ) {
				$attr_label = wc_attribute_label( str_replace( 'attribute_', '', (string) $attr_key ) );
				$haystack = strtolower( (string) $attr_key . ' ' . (string) $attr_label );
				foreach ( $size_like_patterns as $pat ) {
					if ( $pat !== '' && strpos( $haystack, $pat ) !== false ) {
						$size_attr_key = (string) $attr_key;
						break 2;
					}
				}
			}

			// 2) If the product only varies by one attribute, that attribute is effectively "size".
			if ( $size_attr_key === '' && count( $attr_keys ) === 1 ) {
				$size_attr_key = (string) $attr_keys[0];
			}

			// 3) Otherwise choose the attribute with the most distinct values (usually size).
			if ( $size_attr_key === '' && ! empty( $attr_keys_all ) ) {
				$best_key = '';
				$best_count = -1;
				foreach ( $attr_keys_all as $k => $vals ) {
					$c = is_array( $vals ) ? count( $vals ) : 0;
					if ( $c > $best_count ) {
						$best_count = $c;
						$best_key = (string) $k;
					}
				}
				if ( $best_key !== '' ) {
					$size_attr_key = $best_key;
				}
			}

			if ( $size_attr_key !== '' && is_array( $variations ) ) {
				foreach ( $variations as $v ) {
					$vid = isset( $v['variation_id'] ) ? absint( $v['variation_id'] ) : 0;
					if ( ! $vid ) {
						continue;
					}

					$attrs = isset( $v['attributes'] ) && is_array( $v['attributes'] ) ? $v['attributes'] : array();
					$size_value = isset( $attrs[ $size_attr_key ] ) ? (string) $attrs[ $size_attr_key ] : '';
					if ( $size_value === '' ) {
						continue;
					}

					$in_stock = isset( $v['is_in_stock'] ) ? (bool) $v['is_in_stock'] : true;

					$display_regular = isset( $v['display_regular_price'] ) ? (float) $v['display_regular_price'] : 0.0;
					$display_price   = isset( $v['display_price'] ) ? (float) $v['display_price'] : 0.0;
					if ( $display_regular <= 0 ) {
						$display_regular = $display_price;
					}

					// De-dupe by size (keep the first in-stock variation if duplicates exist).
					if ( isset( $sizes[ $size_value ] ) ) {
						if ( ! $sizes[ $size_value ]['in_stock'] && $in_stock ) {
							$sizes[ $size_value ] = array(
								'variation_id' => $vid,
								'regular_price' => $display_regular,
								'price' => $display_price,
								'in_stock' => $in_stock,
							);
						}
						continue;
					}

					$sizes[ $size_value ] = array(
						'variation_id' => $vid,
						'regular_price' => $display_regular,
						'price' => $display_price,
						'in_stock' => $in_stock,
					);
				}
			}
		} else {
			$regular = (float) $p->get_regular_price();
			if ( $regular <= 0 ) {
				$regular = (float) $p->get_price();
			}
			$sizes = array(
				'one-size' => array(
					'variation_id' => 0,
					'regular_price' => $regular,
					'price' => (float) $p->get_price(),
					'in_stock' => $p->is_in_stock(),
				),
			);
		}

		$products_ui[] = array(
			'id' => $pid,
			'name' => $p->get_name(),
			'color' => $color,
			'thumb_url' => $thumb_url,
			'is_variable' => (bool) $is_variable,
			'regular_price' => $product_regular,
			'sizes' => $sizes,
		);
	}

	if ( empty( $products_ui ) ) {
		continue;
	}

	$items_ui[] = array(
		'key' => $key,
		'label' => $label,
		'qty' => $item_qty,
		'products' => $products_ui,
	);
}

$bundle_price = (float) $bundle_product->get_price();
?>

<form class="cart primefit-bundle-form" method="post" enctype="multipart/form-data">
	<?php wp_nonce_field( 'primefit_bundle_add_to_cart', 'primefit_bundle_nonce' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $bundle_product->get_id() ); ?>" />

	<div class="primefit-bundle-items" data-primefit-bundle-items>
		<?php foreach ( $items_ui as $item ) : ?>
			<div class="primefit-bundle-item" data-item-key="<?php echo esc_attr( $item['key'] ); ?>" data-item-qty="<?php echo esc_attr( (int) $item['qty'] ); ?>">
				<div class="primefit-bundle-item__head">
					<div class="primefit-bundle-item__title-wrapper">
						<div class="primefit-bundle-item__label">
							<?php echo esc_html( $item['label'] !== '' ? $item['label'] : __( 'Item', 'primefit' ) ); ?>
							<?php if ( (int) $item['qty'] > 1 ) : ?>
								<span class="primefit-bundle-item__qty">×<?php echo esc_html( (int) $item['qty'] ); ?></span>
							<?php endif; ?>
						</div>
					</div>
					<div class="primefit-bundle-item__price" data-item-price>—</div>
				</div>

				<?php if ( count( $item['products'] ) > 1 ) : ?>
					<div class="product-color-selection">
						<div class="color-options" data-item-colors>
							<?php foreach ( $item['products'] as $p ) : ?>
								<?php
								$color_img = isset( $p['thumb_url'] ) ? (string) $p['thumb_url'] : '';
								?>
								<button
									type="button"
									class="color-option primefit-bundle-color"
									data-product-id="<?php echo esc_attr( (int) $p['id'] ); ?>"
									data-color="<?php echo esc_attr( (string) $p['color'] ); ?>"
									aria-label="<?php echo esc_attr( (string) $p['color'] ); ?>"
								>
									<?php if ( $color_img ) : ?>
										<img src="<?php echo esc_url( $color_img ); ?>" alt="<?php echo esc_attr( (string) $p['color'] ); ?>" class="color-swatch" loading="lazy" decoding="async">
									<?php endif; ?>
									<span class="color-name"><?php echo esc_html( (string) $p['color'] ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php else : ?>
					<!-- If only one product, set it as selected automatically -->
					<input type="hidden" name="primefit_bundle_item_product[<?php echo esc_attr( $item['key'] ); ?>]" value="<?php echo esc_attr( (int) $item['products'][0]['id'] ); ?>" data-item-product-input />
				<?php endif; ?>

				<div class="product-size-selection">
					<div class="size-options" data-item-sizes></div>
				</div>

				<?php if ( count( $item['products'] ) > 1 ) : ?>
					<input type="hidden" name="primefit_bundle_item_product[<?php echo esc_attr( $item['key'] ); ?>]" value="" data-item-product-input />
				<?php endif; ?>
				<input type="hidden" name="primefit_bundle_item_variation[<?php echo esc_attr( $item['key'] ); ?>]" value="0" data-item-variation-input />
			</div>
		<?php endforeach; ?>
	</div>

	<div class="primefit-bundle-pricing" data-primefit-bundle-pricing>
		<div class="primefit-bundle-pricing__line">
			<span class="label"><?php esc_html_e( 'Price of all items', 'primefit' ); ?></span>
			<span class="value" data-items-total>—</span>
		</div>
		<div class="primefit-bundle-pricing__line primefit-bundle-pricing__bundle">
			<span class="label"><?php esc_html_e( 'Bundle price', 'primefit' ); ?></span>
			<span class="value" data-bundle-price><?php echo wp_kses_post( wc_price( $bundle_price ) ); ?></span>
		</div>
		<div class="primefit-bundle-pricing__line primefit-bundle-pricing__savings">
			<span class="label"><?php esc_html_e( 'You save', 'primefit' ); ?></span>
			<span class="value" data-savings>—</span>
		</div>
	</div>

	<input type="hidden" name="quantity" value="1" />

	<button type="submit" class="single_add_to_cart_button button alt" disabled data-primefit-bundle-submit>
		<?php esc_html_e( 'Add bundle to cart', 'primefit' ); ?>
	</button>
</form>

<script>
window.primefitBundleData = <?php
	// Get currency symbol and decode HTML entities
	$currency_symbol = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
	
	echo wp_json_encode(
		array(
			'bundleId' => (int) $bundle_product->get_id(),
			'bundlePrice' => $bundle_price,
			'currencySymbol' => $currency_symbol,
			'currencyPosition' => get_option( 'woocommerce_currency_pos' ),
			'priceDecimals' => wc_get_price_decimals(),
			'priceDecimalSep' => wc_get_price_decimal_separator(),
			'priceThousandSep' => wc_get_price_thousand_separator(),
			'items' => $items_ui,
		),
		JSON_UNESCAPED_UNICODE
	);
?>;
</script>

