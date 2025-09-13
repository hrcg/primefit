# Design Document

## Overview

This design outlines the architecture and implementation approach for recreating the ASRV.com Shopify store as a custom WordPress theme with WooCommerce integration. The solution leverages the existing PrimeFit theme structure as a foundation while implementing ASRV-specific design patterns, functionality, and performance optimizations.

The design follows a modular, component-based architecture that separates concerns between presentation, business logic, and data management. This approach ensures maintainability, scalability, and adherence to WordPress best practices while achieving pixel-perfect design replication.

## Architecture

### Theme Structure

```
wp-content/themes/primefit/
├── style.css                    # Theme header and base styles
├── functions.php                # Core theme functionality
├── index.php                    # Fallback template
├── front-page.php              # Homepage template
├── header.php                   # Site header
├── footer.php                   # Site footer
├── woocommerce.php             # WooCommerce wrapper
├── searchform.php              # Search form template
├── assets/
│   ├── css/
│   │   ├── app.css             # Main stylesheet
│   │   ├── woocommerce.css     # WooCommerce-specific styles
│   │   └── components/         # Component-specific CSS
│   ├── js/
│   │   ├── app.js              # Main JavaScript
│   │   ├── product.js          # Product-specific functionality
│   │   └── modules/            # JavaScript modules
│   └── media/                  # Images and media assets
├── parts/
│   ├── header/                 # Header components
│   ├── footer/                 # Footer components
│   ├── components/             # Reusable UI components
│   ├── sections/               # Page sections
│   └── woocommerce/           # WooCommerce template parts
├── woocommerce/               # WooCommerce template overrides
└── inc/                       # PHP includes and utilities
```

### Design System Architecture

The design system follows ASRV's visual hierarchy and component patterns:

**Color Palette:**

- Primary: Black (#000000) and White (#FFFFFF)
- Accent: Red for CTAs and highlights
- Grays: Multiple shades for text hierarchy and backgrounds
- Status colors: Green (success), Red (error), Orange (warning)

**Typography Scale:**

- Headings: Bold, uppercase, high contrast
- Body text: Clean, readable sans-serif
- UI text: Consistent sizing and spacing

**Component Hierarchy:**

1. Layout containers (header, main, footer)
2. Section components (hero, product grids, content blocks)
3. UI components (buttons, forms, navigation)
4. Utility classes (spacing, typography, responsive)

## Components and Interfaces

### Core Theme Components

#### 1. Header System

**File:** `parts/header/`

- **Promo Bar:** Customizable promotional messaging
- **Navigation:** Multi-level menu with mobile optimization
- **Brand Logo:** Responsive logo with proper alt text
- **Mini Cart:** Real-time cart updates with AJAX
- **Mobile Menu:** Slide-out navigation for mobile devices

**Interface:**

```php
// Header configuration
function primefit_get_header_config() {
    return [
        'logo_url' => get_custom_logo_url(),
        'menu_locations' => get_nav_menu_locations(),
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'promo_text' => get_theme_mod('primefit_promo_text'),
        'mobile_breakpoint' => 768
    ];
}
```

#### 2. Product Display System

**File:** `parts/woocommerce/`

- **Product Cards:** Hover effects, quick view, size selection
- **Product Gallery:** Image zoom, thumbnails, 360° view support
- **Product Information:** Structured data, variations, stock status
- **Add to Cart:** AJAX functionality with loading states

**Interface:**

```php
// Product card configuration
function primefit_render_product_card($product_id, $options = []) {
    $defaults = [
        'show_quick_view' => true,
        'show_size_overlay' => true,
        'image_size' => 'woocommerce_thumbnail',
        'show_badges' => true
    ];
    $options = wp_parse_args($options, $defaults);
    // Render product card
}
```

#### 3. Hero Section System

**File:** `parts/sections/hero.php`

- **Background Management:** Image optimization, lazy loading
- **Content Overlay:** Flexible positioning and styling
- **CTA Integration:** Conversion-optimized button placement
- **Responsive Behavior:** Mobile-first responsive design

#### 4. WooCommerce Integration Layer

**File:** `inc/woocommerce-integration.php`

- **Template Overrides:** Custom product, cart, checkout templates
- **Hook Management:** Strategic use of WooCommerce hooks
- **Performance Optimization:** Query optimization, caching
- **Custom Fields:** Product highlights, details, additional content

### Data Models

#### Product Enhancement Model

```php
class PRIMEFIT_Product_Enhancement {
    private $product_id;

    public function get_highlights() {
        return get_post_meta($this->product_id, 'primefit_highlights', true);
    }

    public function get_details() {
        return get_post_meta($this->product_id, 'primefit_details', true);
    }

    public function get_size_guide() {
        return get_post_meta($this->product_id, 'primefit_size_guide', true);
    }

    public function get_care_instructions() {
        return get_post_meta($this->product_id, 'primefit_care_instructions', true);
    }
}
```

#### Theme Configuration Model

```php
class PRIMEFIT_Theme_Config {
    public static function get_hero_config() {
        return [
            'image' => get_theme_mod('primefit_hero_image'),
            'heading' => get_theme_mod('primefit_hero_heading'),
            'subheading' => get_theme_mod('primefit_hero_subheading'),
            'cta_text' => get_theme_mod('primefit_hero_cta_text'),
            'cta_link' => get_theme_mod('primefit_hero_cta_link')
        ];
    }

    public static function get_layout_config() {
        return [
            'container_width' => get_theme_mod('primefit_container_width', '1200px'),
            'grid_columns' => get_theme_mod('primefit_grid_columns', 4),
            'breakpoints' => [
                'mobile' => 768,
                'tablet' => 1024,
                'desktop' => 1200
            ]
        ];
    }
}
```

## Error Handling

### Frontend Error Management

1. **Graceful Degradation:** Ensure functionality works without JavaScript
2. **Fallback Content:** Default images and text for missing content
3. **User Feedback:** Clear error messages for failed actions
4. **Loading States:** Visual indicators for AJAX operations

### Backend Error Handling

```php
// Error logging and handling
function primefit_log_error($message, $context = []) {
    if (WP_DEBUG_LOG) {
        error_log(sprintf('[PrimeFit Theme] %s - Context: %s',
            $message,
            json_encode($context)
        ));
    }
}

// Safe data retrieval
function primefit_get_safe_meta($post_id, $key, $default = '') {
    $value = get_post_meta($post_id, $key, true);
    return !empty($value) ? $value : $default;
}
```

### WooCommerce Error Handling

1. **Product Availability:** Handle out-of-stock scenarios
2. **Cart Operations:** Manage cart errors and conflicts
3. **Checkout Process:** Validate and handle payment errors
4. **API Integration:** Handle external service failures

## Testing Strategy

### Performance Testing

1. **Page Load Speed:** Target <3 seconds on 3G connections
2. **Core Web Vitals:** Optimize LCP, FID, and CLS metrics
3. **Database Queries:** Monitor and optimize query performance
4. **Asset Loading:** Implement lazy loading and critical CSS

### Functionality Testing

1. **Cross-Browser Compatibility:** Test on major browsers
2. **Responsive Design:** Validate across device sizes
3. **WooCommerce Integration:** Test all e-commerce workflows
4. **Accessibility:** WCAG 2.1 AA compliance testing

### User Experience Testing

1. **Navigation Flow:** Ensure intuitive user journeys
2. **Mobile Experience:** Optimize touch interactions
3. **Conversion Funnel:** Test product discovery to purchase
4. **Search Functionality:** Validate product search and filtering

### Code Quality Testing

```php
// Unit testing example for product enhancement
class Test_PRIMEFIT_Product_Enhancement extends WP_UnitTestCase {
    public function test_get_highlights() {
        $product_id = $this->factory->post->create(['post_type' => 'product']);
        $highlights = "Moisture-wicking\nBreathable fabric\nAnti-odor technology";
        update_post_meta($product_id, 'primefit_highlights', $highlights);

        $enhancement = new PRIMEFIT_Product_Enhancement($product_id);
        $this->assertEquals($highlights, $enhancement->get_highlights());
    }
}
```

### Security Testing

1. **Input Validation:** Sanitize all user inputs
2. **Nonce Verification:** Protect AJAX requests
3. **Capability Checks:** Verify user permissions
4. **SQL Injection Prevention:** Use prepared statements

### Integration Testing

1. **WooCommerce Compatibility:** Test with WooCommerce updates
2. **Plugin Conflicts:** Validate with common plugins
3. **Theme Switching:** Ensure clean activation/deactivation
4. **Data Migration:** Test import/export functionality

## Performance Optimization Strategy

### Asset Optimization

1. **CSS:** Minification, critical CSS inlining, unused CSS removal
2. **JavaScript:** Code splitting, lazy loading, tree shaking
3. **Images:** WebP/AVIF formats, responsive images, lazy loading
4. **Fonts:** Font display optimization, preloading critical fonts

### Caching Strategy

1. **Browser Caching:** Long-term caching for static assets
2. **Object Caching:** WordPress object cache for database queries
3. **Page Caching:** Full-page caching for non-dynamic content
4. **CDN Integration:** Content delivery network for global performance

### Database Optimization

1. **Query Optimization:** Efficient WooCommerce queries
2. **Index Management:** Proper database indexing
3. **Transient Usage:** Cache expensive operations
4. **Cleanup Routines:** Remove unnecessary data

### Mobile Optimization

1. **Touch Interactions:** Optimize for mobile gestures
2. **Viewport Management:** Proper mobile viewport handling
3. **Network Awareness:** Adapt to connection quality
4. **Progressive Enhancement:** Mobile-first development approach
