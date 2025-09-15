<?php
/**
 * Product Gallery Template
 *
 * @package PrimeFit
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product ) {
	return;
}

$attachment_ids = $product->get_gallery_image_ids();
$main_image_id = $product->get_image_id();

// Add main image to the beginning of gallery
if ( $main_image_id ) {
	array_unshift( $attachment_ids, $main_image_id );
}

// Remove duplicates
$attachment_ids = array_unique( $attachment_ids );

if ( empty( $attachment_ids ) ) {
	return;
}
?>

<div class="product-gallery-container">
	<!-- Main Image Display -->
	<div class="product-main-image">
		<div class="main-image-wrapper">
			<?php
			$main_image_url = wp_get_attachment_image_url( $attachment_ids[0], 'woocommerce_single' );
			$main_image_alt = get_post_meta( $attachment_ids[0], '_wp_attachment_image_alt', true );
			?>
			<img 
				src="<?php echo esc_url( $main_image_url ); ?>" 
				alt="<?php echo esc_attr( $main_image_alt ); ?>"
				class="main-product-image"
				data-image-index="0"
			/>
		</div>
		
		<!-- Image Navigation Dots -->
		<?php if ( count( $attachment_ids ) > 1 ) : ?>
			<div class="image-navigation-dots">
				<?php foreach ( $attachment_ids as $index => $attachment_id ) : ?>
					<button 
						class="image-dot <?php echo $index === 0 ? 'active' : ''; ?>"
						data-image-index="<?php echo esc_attr( $index ); ?>"
						aria-label="<?php printf( esc_attr__( 'View image %d', 'primefit' ), $index + 1 ); ?>"
					></button>
				<?php endforeach; ?>
			</div>
			
			<!-- Navigation Arrows -->
			<button class="image-nav image-nav-prev" aria-label="<?php esc_attr_e( 'Previous image', 'primefit' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
			<button class="image-nav image-nav-next" aria-label="<?php esc_attr_e( 'Next image', 'primefit' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		<?php endif; ?>
	</div>
	
	<!-- Thumbnail Gallery -->
	<?php if ( count( $attachment_ids ) > 1 ) : ?>
		<div class="product-thumbnails">
			<?php foreach ( $attachment_ids as $index => $attachment_id ) : ?>
				<?php
				$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'woocommerce_gallery_thumbnail' );
				$thumbnail_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
				?>
				<button 
					class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>"
					data-image-index="<?php echo esc_attr( $index ); ?>"
					aria-label="<?php printf( esc_attr__( 'View image %d', 'primefit' ), $index + 1 ); ?>"
				>
					<img 
						src="<?php echo esc_url( $thumbnail_url ); ?>" 
						alt="<?php echo esc_attr( $thumbnail_alt ); ?>"
						class="thumbnail-image"
					/>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	const galleryContainer = document.querySelector('.product-gallery-container');
	if (!galleryContainer) return;
	
	const mainImage = galleryContainer.querySelector('.main-product-image');
	const thumbnails = galleryContainer.querySelectorAll('.thumbnail-item');
	const dots = galleryContainer.querySelectorAll('.image-dot');
	const prevBtn = galleryContainer.querySelector('.image-nav-prev');
	const nextBtn = galleryContainer.querySelector('.image-nav-next');
	
	const images = <?php echo json_encode( $attachment_ids ); ?>;
	let currentIndex = 0;
	
	function updateMainImage(index) {
		if (!images[index]) return;
		
		const imageUrl = '<?php echo wp_get_attachment_image_url( 0, 'woocommerce_single' ); ?>'.replace('0', images[index]);
		const imageAlt = '<?php echo esc_js( get_post_meta( 0, '_wp_attachment_image_alt', true ) ); ?>'.replace('0', images[index]);
		
		mainImage.src = imageUrl;
		mainImage.alt = imageAlt;
		mainImage.dataset.imageIndex = index;
		
		// Update active states
		thumbnails.forEach((thumb, i) => {
			thumb.classList.toggle('active', i === index);
		});
		
		dots.forEach((dot, i) => {
			dot.classList.toggle('active', i === index);
		});
		
		currentIndex = index;
	}
	
	// Thumbnail click handlers
	thumbnails.forEach((thumb, index) => {
		thumb.addEventListener('click', () => updateMainImage(index));
	});
	
	// Dot click handlers
	dots.forEach((dot, index) => {
		dot.addEventListener('click', () => updateMainImage(index));
	});
	
	// Navigation handlers
	if (prevBtn) {
		prevBtn.addEventListener('click', () => {
			const newIndex = currentIndex > 0 ? currentIndex - 1 : images.length - 1;
			updateMainImage(newIndex);
		});
	}
	
	if (nextBtn) {
		nextBtn.addEventListener('click', () => {
			const newIndex = currentIndex < images.length - 1 ? currentIndex + 1 : 0;
			updateMainImage(newIndex);
		});
	}
	
	// Keyboard navigation
	document.addEventListener('keydown', (e) => {
		if (e.key === 'ArrowLeft' && prevBtn) {
			prevBtn.click();
		} else if (e.key === 'ArrowRight' && nextBtn) {
			nextBtn.click();
		}
	});
});
</script>
