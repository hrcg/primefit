<?php
/**
 * Single Product Image
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

// Get the current selected color from POST data or default
$selected_color = '';
if ( isset( $_POST['attribute_pa_color'] ) ) {
	$selected_color = sanitize_text_field( $_POST['attribute_pa_color'] );
} elseif ( isset( $_GET['color'] ) ) {
	$selected_color = sanitize_text_field( $_GET['color'] );
}

// Always start with the default product gallery
$attachment_ids = $product->get_gallery_image_ids();
$main_image_id = $product->get_image_id();

// Add main image to the beginning of gallery
if ( $main_image_id ) {
	array_unshift( $attachment_ids, $main_image_id );
}

// Remove duplicates
$attachment_ids = array_unique( $attachment_ids );

// Get variation gallery for the selected color (if any)
$variation_gallery_ids = array();
if ( ! empty( $selected_color ) ) {
	$variation_gallery_ids = primefit_get_variation_gallery( $product->get_id(), $selected_color );

	// If we have a variation gallery for the selected color, use it
	if ( ! empty( $variation_gallery_ids ) ) {
		$attachment_ids = $variation_gallery_ids;
		$main_image_id = ! empty( $attachment_ids[0] ) ? $attachment_ids[0] : $product->get_image_id();

		// If main image from variation gallery doesn't exist, use product main image
		if ( ! $main_image_id ) {
			$main_image_id = $product->get_image_id();
			if ( $main_image_id ) {
				array_unshift( $attachment_ids, $main_image_id );
			}
		}
	}
}

if ( empty( $attachment_ids ) ) {
	return;
}

// Get gallery data for JavaScript
$variation_galleries = primefit_get_variation_gallery_data( $product->get_id() );

// Generate URLs for all images
$image_urls = array();
foreach ($attachment_ids as $attachment_id) {
	if ($attachment_id) {
		$image_urls[$attachment_id] = wp_get_attachment_image_url($attachment_id, 'full');
	}
}

// Generate URLs for variation gallery images
foreach ($variation_galleries as $color => $gallery_data) {
	if (isset($gallery_data['images']) && is_array($gallery_data['images'])) {
		foreach ($gallery_data['images'] as $attachment_id) {
			if ($attachment_id && !isset($image_urls[$attachment_id])) {
				$image_urls[$attachment_id] = wp_get_attachment_image_url($attachment_id, 'full');
			}
		}
	}
}

$gallery_data = array(
	'default' => $attachment_ids,
	'variations' => $variation_galleries,
	'current_color' => '', // Always empty on initial load to ensure default gallery is used
	'product_id' => $product->get_id(),
	'image_urls' => $image_urls
);

// Debug: Log the gallery data being sent to JavaScript
if (!empty($variation_galleries)) {
}

?>

<div class="product-gallery-container">
	<!-- Main Image Display -->
	<div class="product-main-image">
		<div class="main-image-wrapper">
			<?php
			$main_image_url = wp_get_attachment_image_url( $attachment_ids[0], 'full' );
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
				$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'full' );
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

	// Gallery data from PHP
	const galleryData = <?php echo json_encode( $gallery_data ); ?>;


	let currentImages = galleryData.default;
	let currentIndex = 0;
	let currentColor = galleryData.current_color;

	// Initialize gallery with current images
	function initializeGallery() {

		// Always start with default gallery
		currentImages = galleryData.default;
		currentIndex = 0;
		currentColor = '';

		// Only switch to variation gallery if we have a selected color AND variation galleries exist
		if (currentColor) {
			const normalizedColor = currentColor.toLowerCase().trim();
			if (galleryData.variations && galleryData.variations[normalizedColor] && galleryData.variations[normalizedColor].images.length > 0) {
				currentImages = galleryData.variations[normalizedColor].images;
			}
		}

		// Final safety check
		if (!currentImages || currentImages.length === 0) {
			currentImages = galleryData.default;
			currentColor = '';
		}


		// Create thumbnails first
		updateThumbnailGallery();

		// Then update main image
		updateMainImage(0);
	}

	function updateMainImage(index) {
		if (!currentImages || !currentImages[index] || currentImages[index] === 0) {
			return;
		}

		const imageId = currentImages[index];
		const imageUrl = galleryData.image_urls[imageId] || '';

		if (!imageUrl) {
			return;
		}

		const imageAlt = `Product image ${index + 1}`;


		mainImage.src = imageUrl;
		mainImage.alt = imageAlt;
		mainImage.dataset.imageIndex = index;

		// Update active states for thumbnails (after ensuring they exist)
		const thumbnails = galleryContainer.querySelectorAll('.thumbnail-item');
		thumbnails.forEach((thumb, i) => {
			thumb.classList.toggle('active', i === index);
		});

		// Update active states for dots (after ensuring they exist)
		const dots = galleryContainer.querySelectorAll('.image-dot');
		dots.forEach((dot, i) => {
			dot.classList.toggle('active', i === index);
		});

		currentIndex = index;
	}

	// Switch to a different color gallery
	function switchColorGallery(color) {

		// Normalize color for consistent matching
		const normalizedColor = color ? color.toLowerCase().trim() : '';

		if (!normalizedColor || !galleryData.variations || !galleryData.variations[normalizedColor]) {
			// Switch back to default gallery
			currentImages = galleryData.default;
			currentColor = '';
		} else {
			// Switch to specific color gallery
			currentImages = galleryData.variations[normalizedColor].images;
			currentColor = color; // Keep original color for reference
		}


		// Reset to first image
		currentIndex = 0;

		// Update thumbnail gallery first
		updateThumbnailGallery();

		// Then update main image
		updateMainImage(0);
	}

	// Update thumbnail gallery to show current images
	function updateThumbnailGallery() {
		// Ensure thumbnail container exists
		let thumbnailContainer = galleryContainer.querySelector('.product-thumbnails');
		if (!thumbnailContainer) {
			thumbnailContainer = document.createElement('div');
			thumbnailContainer.className = 'product-thumbnails';
			galleryContainer.appendChild(thumbnailContainer);
		}

		// Ensure dots container exists
		let dotsContainer = galleryContainer.querySelector('.image-navigation-dots');
		if (!dotsContainer) {
			dotsContainer = document.createElement('div');
			dotsContainer.className = 'image-navigation-dots';
			galleryContainer.appendChild(dotsContainer);
		}

		// Clear existing thumbnails
		thumbnailContainer.innerHTML = '';

		// Add new thumbnails
		currentImages.forEach((imageId, index) => {
			if (!imageId) return; // Skip empty image IDs

			const thumbnailUrl = galleryData.image_urls[imageId] || '';
			const thumbnailAlt = `Product image ${index + 1}`;

			if (!thumbnailUrl) {
				return;
			}

			const thumbnailHtml = `
				<button class="thumbnail-item ${index === 0 ? 'active' : ''}"
						data-image-index="${index}"
						aria-label="View image ${index + 1}">
					<img src="${thumbnailUrl}"
						 alt="${thumbnailAlt}"
						 class="thumbnail-image" />
				</button>
			`;

			thumbnailContainer.insertAdjacentHTML('beforeend', thumbnailHtml);
		});

		// Update dots if they exist
		dotsContainer.innerHTML = '';

		currentImages.forEach((imageId, index) => {
			if (!imageId) return; // Skip empty image IDs

			const dotHtml = `
				<button class="image-dot ${index === 0 ? 'active' : ''}"
						data-image-index="${index}"
						aria-label="View image ${index + 1}">
				</button>
			`;

			dotsContainer.insertAdjacentHTML('beforeend', dotHtml);
		});
	}

	// Event handlers for gallery navigation
	function bindGalleryEvents() {
		// Thumbnail click handlers (will be rebound after gallery updates)
		galleryContainer.removeEventListener('click', handleThumbnailClick);
		galleryContainer.addEventListener('click', handleThumbnailClick);

		// Dot click handlers
		galleryContainer.removeEventListener('click', handleDotClick);
		galleryContainer.addEventListener('click', handleDotClick);

		// Navigation handlers
		if (prevBtn) {
			prevBtn.addEventListener('click', () => {
				const newIndex = currentIndex > 0 ? currentIndex - 1 : currentImages.length - 1;
				updateMainImage(newIndex);
			});
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', () => {
				const newIndex = currentIndex < currentImages.length - 1 ? currentIndex + 1 : 0;
				updateMainImage(newIndex);
			});
		}
	}

	// Handle thumbnail clicks
	function handleThumbnailClick(e) {
		if (e.target.closest('.thumbnail-item')) {
			e.preventDefault();
			const index = parseInt(e.target.closest('.thumbnail-item').dataset.imageIndex);
			if (!isNaN(index)) {
				updateMainImage(index);
			}
		}
	}

	// Handle dot clicks
	function handleDotClick(e) {
		if (e.target.closest('.image-dot')) {
			e.preventDefault();
			const index = parseInt(e.target.closest('.image-dot').dataset.imageIndex);
			if (!isNaN(index)) {
				updateMainImage(index);
			}
		}
	}

	// Listen for color changes from the product variations
	function bindColorChangeEvents() {
		// Color option clicks
		document.removeEventListener('click', handleColorOptionClick);
		document.addEventListener('click', handleColorOptionClick);

		// WooCommerce variation changes
		document.removeEventListener('woocommerce_variation_select_change', handleVariationChange);
		document.addEventListener('woocommerce_variation_select_change', handleVariationChange);
	}

	// Handle color option clicks
	function handleColorOptionClick(e) {
		if (e.target.closest('.color-option')) {
			const color = e.target.closest('.color-option').dataset.color;
			switchColorGallery(color);
		}
	}

	// Handle WooCommerce variation changes
	function handleVariationChange(e) {
		// Get selected color from form inputs
		const colorInputs = [
			'input[name="attribute_pa_color"]',
			'select[name="attribute_pa_color"]',
			'input[name="attribute_color"]',
			'select[name="attribute_color"]'
		];

		let selectedColor = '';
		for (const selector of colorInputs) {
			const element = document.querySelector(selector);
			if (element && element.value) {
				selectedColor = element.value;
				break;
			}
		}

		if (selectedColor && selectedColor !== currentColor) {
			switchColorGallery(selectedColor);
		}
	}

	// Initialize everything
	initializeGallery();
	bindGalleryEvents();
	bindColorChangeEvents();

	// Keyboard navigation
	document.addEventListener('keydown', (e) => {
		if (e.key === 'ArrowLeft' && prevBtn) {
			prevBtn.click();
		} else if (e.key === 'ArrowRight' && nextBtn) {
			nextBtn.click();
		}
	});

	// Expose the switchColorGallery function globally for external use
	window.switchProductGallery = switchColorGallery;
});
</script>
