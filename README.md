# PrimeFit WordPress Theme

A modern, performance-optimized WordPress theme built for WooCommerce e-commerce sites, specifically designed for fitness and athletic apparel brands.

## Theme Structure

This theme follows WordPress best practices with a clean, modular structure that's easy to understand and maintain.

```
primefit/
│
├── assets/                     # Frontend assets
│   ├── css/                    # Stylesheets
│   │   ├── app.css             # Main theme styles
│   │   ├── woocommerce.css     # WooCommerce-specific styles
│   │   └── variables.css       # CSS custom properties
│   ├── js/                     # JavaScript files
│   │   ├── app.js              # Main theme JavaScript
│   │   └── product.js          # Product-specific functionality
│   └── images/                 # Theme images and icons
│       ├── hero-image.webp      # Default hero background
│       ├── logo-black.webp      # Dark logo variant
│       ├── logo-white.webp      # Light logo variant
│       └── ...                  # Other theme images
│
├── inc/                        # Theme includes (modular PHP)
│   ├── setup.php               # Theme setup, supports, and configurations
│   ├── enqueue.php             # Scripts & styles enqueuing
│   ├── hooks.php               # Actions & filters for WooCommerce
│   ├── helpers.php             # Utility/helper functions
│   ├── customizer.php          # Theme customizer settings
│   └── woocommerce.php         # WooCommerce integration
│
├── templates/                  # Template parts
│   ├── header/                 # Header components
│   │   ├── brand-logo.php      # Logo display
│   │   ├── header-actions.php  # Header action buttons
│   │   ├── mega-menu.php       # Navigation menu
│   │   ├── mini-cart.php       # Shopping cart widget
│   │   ├── navigation.php      # Navigation wrapper
│   │   ├── primary-navigation.php # Main navigation
│   │   └── promo-bar.php       # Promotional banner
│   ├── footer/                 # Footer components
│   │   ├── copyright.php       # Copyright information
│   │   ├── footer-info.php     # Footer information
│   │   ├── footer-menu.php     # Footer navigation
│   │   └── footer-navigation.php # Footer nav wrapper
│   ├── parts/                  # Reusable template parts
│   │   ├── components/         # UI components
│   │   │   ├── button.php      # Button component
│   │   │   ├── image.php       # Image component
│   │   │   └── section-header.php # Section headers
│   │   ├── sections/           # Page sections
│   │   │   ├── category-tiles.php # Category grid
│   │   │   ├── featured-products.php # Featured products
│   │   │   ├── hero.php        # Hero section
│   │   │   ├── product-showcase.php # Product showcase
│   │   │   └── training-division.php # Training section
│   │   ├── woocommerce/        # WooCommerce components
│   │   │   ├── product-price.php # Product pricing
│   │   │   ├── product-status-badge.php # Status badges
│   │   │   └── shop-filter-bar.php # Shop filters
│   │   └── hero.php            # Hero section template
│   └── content/                # Post/page content templates
│
├── woocommerce/                # WooCommerce template overrides
│   └── woocommerce.php         # Main WooCommerce integration
│
├── languages/                  # Translation files (.pot, .po, .mo)
│
├── style.css                   # Theme stylesheet (required by WP)
├── functions.php               # Minimal bootstrap → loads /inc/ files
├── index.php                   # Fallback template
├── front-page.php              # Homepage template
├── page.php                    # Page template
├── single.php                  # Single post template
├── archive.php                 # Archive template
├── search.php                  # Search results
├── 404.php                     # Error page
└── README.md                   # This file
```

## Key Features

### Performance Optimizations

- **Modular Architecture**: Clean separation of concerns with dedicated files for different functionalities
- **Asset Optimization**: Organized CSS/JS with proper enqueuing and minification support
- **Image Optimization**: WebP/AVIF support with lazy loading
- **Critical CSS**: Inline critical styles for above-the-fold content

### WooCommerce Integration

- **Custom Product Loops**: Enhanced product display with hover effects and size selection
- **Product Custom Fields**: Highlights, details, and additional content sections
- **Custom Product Tabs**: Dynamic tabs based on product content
- **Cart Fragments**: AJAX cart updates with proper fragment handling
- **Shop Customization**: Custom filter bars and sorting options

### Theme Customizer

- **Hero Section**: Customizable hero with image, text, and CTA options
- **Promo Bar**: Configurable promotional banner
- **Footer Settings**: Copyright text and footer customization
- **Color Schemes**: Light/dark theme support

### Developer-Friendly

- **WordPress Standards**: Follows WordPress coding standards and best practices
- **Modular Structure**: Easy to extend and maintain
- **Documentation**: Comprehensive inline documentation
- **Hooks & Filters**: Extensive customization points
- **Abstracted Components**: Reusable hero and product loop functions
- **Shortcodes**: Easy-to-use shortcodes for content creators

## File Organization Principles

### `/inc/` Directory

Contains modular PHP files that handle specific functionality:

- **setup.php**: Theme initialization, supports, and basic configuration
- **enqueue.php**: Asset loading and optimization
- **hooks.php**: WordPress actions and filters
- **helpers.php**: Utility functions and helpers
- **customizer.php**: Theme customizer settings
- **woocommerce.php**: WooCommerce-specific functionality

### `/templates/` Directory

Organized template parts for better maintainability:

- **header/**: All header-related components
- **footer/**: Footer components and widgets
- **parts/**: Reusable components and sections
- **content/**: Post and page content templates

### `/assets/` Directory

Structured asset organization:

- **css/**: Stylesheets organized by functionality
- **js/**: JavaScript files with proper dependencies
- **images/**: Optimized images with multiple format support

## Development Guidelines

### Adding New Features

1. Create functionality in appropriate `/inc/` file
2. Add template parts to `/templates/` directory
3. Update asset paths in `/inc/enqueue.php`
4. Document changes in this README

### Customization

- Use WordPress hooks and filters for customization
- Override templates by copying to child theme
- Modify styles in `/assets/css/` files
- Add custom JavaScript in `/assets/js/` files

### Abstracted Functions

The theme includes powerful abstracted functions for easy reuse:

#### Hero Section

```php
// Render hero section programmatically
primefit_render_hero(array(
    'heading' => 'My Hero Title',
    'subheading' => 'Hero subtitle',
    'cta_text' => 'Shop Now',
    'cta_link' => '/shop',
    'overlay_position' => 'center',
    'text_color' => 'light'
));

// Get hero config for different page types
$hero_config = primefit_get_hero_config_for_page('shop', $custom_args);
```

#### Product Loops

```php
// Render product loop programmatically
primefit_render_product_loop(array(
    'title' => 'Featured Products',
    'limit' => 8,
    'columns' => 4,
    'featured' => true,
    'show_view_all' => true
));

// Get product loop config for different contexts
$product_config = primefit_get_product_loop_config('sale', $custom_args);
```

#### Shortcodes

```php
// Hero section shortcode
[primefit_hero heading="My Title" subheading="My subtitle" cta_text="Shop Now" cta_link="/shop"]

// Product loop shortcode
[primefit_products title="Featured Products" limit="8" columns="4" featured="true"]
```

### Performance Considerations

- Keep functions.php minimal (only includes)
- Use proper asset enqueuing
- Optimize images (WebP/AVIF preferred)
- Minimize HTTP requests
- Use WordPress caching plugins

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile-responsive design
- Progressive enhancement approach

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.4+
- Modern web server with WebP support

## Installation

1. Upload theme files to `/wp-content/themes/primefit/`
2. Activate theme in WordPress admin
3. Install and activate WooCommerce
4. Configure theme settings in Customizer
5. Import demo content (if available)

## Support

For theme support and customization requests, please refer to the theme documentation or contact the development team.

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**WordPress Compatibility**: 5.0+  
**WooCommerce Compatibility**: 3.0+
