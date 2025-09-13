<?php
/**
 * Mega Menu Component
 * 
 * Usage: get_template_part('parts/header/mega-menu', null, $args);
 * 
 * Expected $args:
 * - 'image' => featured image for the mega menu
 * - 'image_alt' => alt text for the image
 * - 'products' => array of product categories
 * - 'collections' => array of collections
 */

$defaults = array(
	'image' => primefit_get_asset_uri( [ '/assets/images/hero-image.webp', '/assets/images/hero-image.jpg' ] ),
	'image_alt' => 'Featured',
	'products' => array(),
	'collections' => array()
);

$mega = wp_parse_args($args ?? array(), $defaults);
?>

<div class="mega-panel">
	<div class="mega-grid">
		<div class="mega-media">
			<img src="<?php echo esc_url( $mega['image'] ); ?>" alt="<?php echo esc_attr( $mega['image_alt'] ); ?>" />
		</div>
		
		<?php if ( !empty( $mega['products'] ) ) : ?>
			<div class="mega-col">
				<h3>PRODUCTS</h3>
				<ul>
					<?php foreach ( $mega['products'] as $product ) : ?>
						<li>
							<?php if ( isset( $product['url'] ) && !empty( $product['url'] ) ) : ?>
								<a href="<?php echo esc_url( $product['url'] ); ?>">
									<?php echo esc_html( $product['name'] ); ?>
								</a>
							<?php else : ?>
								<span class="muted"><?php echo esc_html( $product['name'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		
		<?php if ( !empty( $mega['collections'] ) ) : ?>
			<div class="mega-col">
				<h3>COLLECTIONS</h3>
				<ul>
					<?php foreach ( $mega['collections'] as $collection ) : ?>
						<li>
							<?php if ( isset( $collection['url'] ) && !empty( $collection['url'] ) ) : ?>
								<a href="<?php echo esc_url( $collection['url'] ); ?>">
									<?php echo esc_html( $collection['name'] ); ?>
								</a>
							<?php else : ?>
								<span class="muted"><?php echo esc_html( $collection['name'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</div>
