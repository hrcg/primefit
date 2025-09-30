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

$product_id = $product->get_id();

// Get the current selected color from POST data or default
$selected_color = '';
if ( isset( $_POST['attribute_pa_color'] ) ) {
	$selected_color = sanitize_text_field( $_POST['attribute_pa_color'] );
} elseif ( isset( $_GET['color'] ) ) {
	$selected_color = sanitize_text_field( $_GET['color'] );
}

// Try to get cached gallery data first
$cached_gallery = primefit_get_cached_product_data( $product_id . '_gallery' );
if ( false !== $cached_gallery ) {
	extract( $cached_gallery );
} else {
	// Cache miss - get data and cache it
	// Always start with the default product gallery
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

	// Get variation galleries from ACF field
	$variation_galleries = primefit_get_variation_gallery_data( $product_id );

	// Generate URLs for all images with caching
	$image_urls = array();
	foreach ($attachment_ids as $attachment_id) {
		if ($attachment_id) {
			$image_urls[$attachment_id] = wp_get_attachment_image_url($attachment_id, 'full');
		}
	}

	// Cache the gallery data
	$gallery_data = compact( 'attachment_ids', 'variation_galleries', 'image_urls' );
	primefit_cache_product_data( $product_id . '_gallery', $gallery_data );
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


?>

<div class="product-gallery-container">
	<!-- Main Image Display -->
	<div class="product-main-image">
		<div class="main-image-wrapper">
			<?php
			$main_attachment_id = $attachment_ids[0];

			// Try to get cached image URL first
			$main_image_url = primefit_get_cached_attachment_image_url( $main_attachment_id, 'full' );
			if ( false === $main_image_url ) {
				$main_image_url = wp_get_attachment_image_url( $main_attachment_id, 'full' );
				primefit_cache_attachment_image_url( $main_attachment_id, 'full', $main_image_url );
			}

			// Try to get cached alt text first
			$main_image_alt = primefit_get_cached_attachment_meta( $main_attachment_id, '_wp_attachment_image_alt' );
			if ( false === $main_image_alt ) {
				$main_image_alt = get_post_meta( $main_attachment_id, '_wp_attachment_image_alt', true );
				primefit_cache_attachment_meta( $main_attachment_id, '_wp_attachment_image_alt', $main_image_alt );
			}
			?>
			<?php
			// Use original image without WebP conversion
			$main_image_url = wp_get_attachment_image_url( $main_attachment_id, 'full' );
			?>
			<img src="<?php echo esc_url( $main_image_url ); ?>" 
				 alt="<?php echo esc_attr( $main_image_alt ); ?>" 
				 class="main-product-image" 
				 loading="eager" 
				 fetchpriority="high" 
				 decoding="async" 
				 width="800" 
				 height="800" />
		</div>

		<!-- Image Navigation Dots -->
		<?php if ( count( $attachment_ids ) > 1 ) : ?>


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
				// Try to get cached image URL first
				$thumbnail_url = primefit_get_cached_attachment_image_url( $attachment_id, 'full' );
				if ( false === $thumbnail_url ) {
					$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'full' );
					primefit_cache_attachment_image_url( $attachment_id, 'full', $thumbnail_url );
				}

				// Try to get cached alt text first
				$thumbnail_alt = primefit_get_cached_attachment_meta( $attachment_id, '_wp_attachment_image_alt' );
				if ( false === $thumbnail_alt ) {
					$thumbnail_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
					primefit_cache_attachment_meta( $attachment_id, '_wp_attachment_image_alt', $thumbnail_alt );
				}
				?>
				<button
					class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>"
					data-image-index="<?php echo esc_attr( $index ); ?>"
					aria-label="<?php printf( esc_attr__( 'View image %d', 'primefit' ), $index + 1 ); ?>"
				>
					<?php
					// Use original image without WebP conversion
					$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
					?>
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" 
						 alt="<?php echo esc_attr( $thumbnail_alt ); ?>" 
						 class="thumbnail-image" 
						 loading="lazy" 
						 decoding="async" 
						 width="150" 
						 height="150" />
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

		// Update image source and attributes (no WebP conversion)
		mainImage.src = imageUrl;
		mainImage.alt = imageAlt;
		mainImage.dataset.imageIndex = index;

		// Update active states for thumbnails (after ensuring they exist)
		const thumbnails = galleryContainer.querySelectorAll('.thumbnail-item');
		thumbnails.forEach((thumb, i) => {
			thumb.classList.toggle('active', i === index);
		});

		currentIndex = index;
	}

	// Switch to a different color gallery
	function switchColorGallery(color) {
		console.log('switchColorGallery called with color:', color);
		console.log('Available variations:', Object.keys(galleryData.variations || {}));

		// Normalize color for consistent matching
		const normalizedColor = color ? color.toLowerCase().trim() : '';
		console.log('Normalized color:', normalizedColor);

		if (!normalizedColor || !galleryData.variations || !galleryData.variations[normalizedColor]) {
			console.log('Switching to default gallery');
			// Switch back to default gallery
			currentImages = galleryData.default;
			currentColor = '';
		} else {
			console.log('Switching to variation gallery for color:', normalizedColor);
			// Switch to specific color gallery
			currentImages = galleryData.variations[normalizedColor].images;
			currentColor = color; // Keep original color for reference
		}

		// Validate that we have images to display
		if (!currentImages || currentImages.length === 0) {
			console.log('No images found, falling back to default gallery');
			currentImages = galleryData.default;
			currentColor = '';
		}

		// Find the main variation image for this color and set it as the primary image
		if (currentColor && galleryData.variations && galleryData.variations[normalizedColor]) {
			// Get the main variation image from the color option data
			const colorOption = document.querySelector(`[data-color="${color}"]`);
			if (colorOption) {
				const variationImage = colorOption.getAttribute('data-variation-image');
				if (variationImage) {
					// Find this image in the current gallery array
					const imageIndex = currentImages.findIndex(imageId => {
						if (!imageId || imageId === 0) return false;
						const imageUrl = galleryData.image_urls[imageId];
						return imageUrl === variationImage;
					});

					if (imageIndex !== -1) {
						console.log('Found main variation image at index:', imageIndex);
						currentIndex = imageIndex;
					} else {
						console.log('Main variation image not found in ACF gallery, using index 0');
						currentIndex = 0;
					}
				} else {
					console.log('No variation image found for color, using index 0');
					currentIndex = 0;
				}
			} else {
				console.log('Color option not found, using index 0');
				currentIndex = 0;
			}
		} else {
			// For default gallery or fallback cases, use index 0
			currentIndex = 0;
		}

		// Update thumbnail gallery first
		updateThumbnailGallery();

		// Then update main image - ensure we have a valid image at currentIndex
		if (currentImages && currentImages.length > 0 && currentImages[currentIndex]) {
			updateMainImage(currentIndex);
		} else {
			console.log('No valid image at currentIndex, trying to find first valid image');
			// Find the first valid image in the gallery
			for (let i = 0; i < currentImages.length; i++) {
				if (currentImages[i] && currentImages[i] !== 0) {
					currentIndex = i;
					updateMainImage(i);
					break;
				}
			}
		}
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

		// Clear existing thumbnails
		thumbnailContainer.innerHTML = '';

		// Validate currentImages before processing
		if (!currentImages || currentImages.length === 0) {
			console.log('No images to display in thumbnail gallery');
			return;
		}

		// Add new thumbnails
		currentImages.forEach((imageId, index) => {
			if (!imageId || imageId === 0) return; // Skip empty or invalid image IDs

			const thumbnailUrl = galleryData.image_urls[imageId] || '';
			const thumbnailAlt = `Product image ${index + 1}`;

			if (!thumbnailUrl) {
				console.log('No thumbnail URL found for image ID:', imageId);
				return;
			}

			const thumbnailHtml = `
				<button class="thumbnail-item ${index === currentIndex ? 'active' : ''}"
						data-image-index="${index}"
						aria-label="View image ${index + 1}">
					<img src="${thumbnailUrl}"
						 alt="${thumbnailAlt}"
						 class="thumbnail-image"
						 loading="lazy"
						 decoding="async"
						 width="150"
						 height="150" />
				</button>
			`;

			thumbnailContainer.insertAdjacentHTML('beforeend', thumbnailHtml);
		});
	}

	// Event handlers for gallery navigation
	function bindGalleryEvents() {
		// Thumbnail click handlers (will be rebound after gallery updates)
		galleryContainer.removeEventListener('click', handleThumbnailClick);
		galleryContainer.addEventListener('click', handleThumbnailClick);


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

		// Swipe functionality is handled by ProductGallery class in single-product.js
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

	// Listen for color selection events from the ProductVariations class
	document.addEventListener('colorSelected', function(e) {
		console.log('Color selection event received:', e.detail.color);
		switchColorGallery(e.detail.color);
	});

	// Expose the switchColorGallery function globally for external use
	window.switchProductGallery = switchColorGallery;
});
</script>
