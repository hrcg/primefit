<?php
/**
 * Category Tiles Section
 * 
 * Usage: get_template_part('parts/sections/category-tiles', null, $args);
 * 
 * Expected $args:
 * - 'tiles' => array of tile data
 */

$defaults = array(
	'tiles' => array(
		array(
			'image' => array('/assets/media/run.webp', '/assets/media/run.jpg'),
			'alt' => 'RUN',
			'label' => 'RUN',
			'url' => '/designed-for/run',
			'description' => 'Performance gear designed for runners who demand excellence in every stride.',
			'button_text' => 'Shop Run'
		),
		array(
			'image' => array('/assets/media/train.webp', '/assets/media/train.jpg'),
			'alt' => 'TRAIN',
			'label' => 'TRAIN',
			'url' => '/designed-for/train',
			'description' => 'Training equipment built to push your limits and maximize your potential.',
			'button_text' => 'Shop Train'
		),
		array(
			'image' => array('/assets/media/rec.webp', '/assets/media/rec.jpg'),
			'alt' => 'REC',
			'label' => 'REC',
			'url' => '/designed-for/rec',
			'description' => 'Technical, versatile gear for everyday use and recreational activities.',
			'button_text' => 'Shop Rec'
		)
	)
);

$section = wp_parse_args($args ?? array(), $defaults);
?>

<section class="container tiles-3">
	<?php foreach ($section['tiles'] as $tile) : ?>
		<div class="tile">
			<img 
				src="<?php echo esc_url( primefit_get_asset_uri( $tile['image'] ) ); ?>" 
				alt="<?php echo esc_attr( $tile['alt'] ); ?>" 
			/>
			<a href="<?php echo esc_url( $tile['url'] ); ?>" class="tile-label">
				<?php echo esc_html( $tile['label'] ); ?>
			</a>
			<div class="tile-overlay">
				<div class="tile-content">
					<p class="tile-description"><?php echo esc_html( $tile['description'] ); ?></p>
					<a href="<?php echo esc_url( $tile['url'] ); ?>" class="tile-button button button--primary">
						<?php echo esc_html( $tile['button_text'] ); ?>
					</a>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</section>
