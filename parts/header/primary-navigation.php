<?php
/**
 * Primary Navigation Component
 * 
 * Usage: get_template_part('parts/header/primary-navigation');
 */

// Navigation menu data - could be moved to customizer or menu system
$nav_items = array(
	array(
		'name' => 'SALE',
		'url' => '/sale',
		'has_mega' => false
	),
	array(
		'name' => 'MEN',
		'url' => '/men',
		'has_mega' => true,
		'mega_data' => array(
			'image_alt' => 'Men',
			'products' => array(
				array('name' => 'All Products', 'url' => '/shop'),
				array('name' => 'T-Shirts & Tops', 'url' => '/product-category/t-shirts-tops'),
				array('name' => 'Joggers', 'url' => '/product-category/joggers'),
				array('name' => 'Shorts', 'url' => '/product-category/shorts'),
				array('name' => 'Hoodies', 'url' => '/product-category/hoodies'),
				array('name' => 'Workout Sets', 'url' => '/product-category/workout-sets'),
				array('name' => 'Basics (Stay Tuned)', 'url' => '')
			),
			'collections' => array(
				array('name' => 'Genesis', 'url' => '/collection/genesis'),
				array('name' => 'Gym Essentials (Stay Tuned)', 'url' => ''),
				array('name' => 'Training Dept (Stay Tuned)', 'url' => ''),
				array('name' => 'Athleisure (Stay Tuned)', 'url' => '')
			)
		)
	),
	array(
		'name' => 'WOMEN',
		'url' => '/women',
		'has_mega' => true,
		'mega_data' => array(
			'image_alt' => 'Women',
			'products' => array(
				array('name' => 'All Products', 'url' => '/shop'),
				array('name' => 'Tops', 'url' => '/product-category/women-tops'),
				array('name' => 'Leggings & Joggers', 'url' => '/product-category/women-leggings'),
				array('name' => 'Shorts', 'url' => '/product-category/women-shorts'),
				array('name' => 'Basics (Stay Tuned)', 'url' => '')
			),
			'collections' => array(
				array('name' => 'Genesis', 'url' => '/collection/genesis'),
				array('name' => 'Gym Essentials (Stay Tuned)', 'url' => '')
			)
		)
	),
	array(
		'name' => 'COLLECTIONS',
		'url' => '/collections',
		'has_mega' => false
	),
	array(
		'name' => 'FABRIC TECH',
		'url' => '/fabric-tech',
		'has_mega' => false
	),
	array(
		'name' => 'EXPLORE',
		'url' => '/explore',
		'has_mega' => false
	)
);
?>

<nav class="left-nav" id="primary-menu" aria-label="Primary">
	<div class="mobile-nav-top" aria-hidden="true">
		<button class="menu-close" type="button" aria-label="Close menu">
			<span class="close-icon" aria-hidden="true"></span>
		</button>
	</div>
	<ul class="menu">
		<?php foreach ( $nav_items as $item ) : ?>
			<li class="<?php echo $item['has_mega'] ? 'menu-item has-mega' : ''; ?>">
				<a href="<?php echo esc_url( $item['url'] ); ?>">
					<?php echo esc_html( $item['name'] ); ?>
				</a>
				<?php if ( $item['has_mega'] && isset( $item['mega_data'] ) ) : ?>
					<?php get_template_part( 'parts/header/mega-menu', null, $item['mega_data'] ); ?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="header-actions header-actions--mobile">
		<?php get_template_part( 'parts/header/header-actions' ); ?>
	</div>
</nav>
