# PrimeFit WordPress Theme - Development Guidelines

## 🎯 **Project Overview**

PrimeFit is a modern, performance-optimized WordPress theme built for WooCommerce e-commerce sites, specifically designed for fitness and athletic apparel brands. The theme follows WordPress best practices with a clean, modular structure.

## 📁 **Current Theme Structure**

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
│       ├── hero-image.webp     # Default hero background
│       ├── logo-black.webp     # Dark logo variant
│       ├── logo-white.webp     # Light logo variant
│       ├── rec.webp            # Category tile images
│       ├── run.webp            # Category tile images
│       ├── train.webp          # Category tile images
│       └── training-dept.jpg   # Training section image
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
│   │   ├── header-actions.php  # Header action buttons (LOCATIONS, ACCOUNT, SEARCH, CART)
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
│   │   ├── button.php          # Button component
│   │   ├── image.php           # Image component
│   │   ├── section-header.php  # Section headers
│   │   ├── category-tiles.php  # Category grid
│   │   ├── featured-products.php # Featured products
│   │   ├── hero.php            # Hero section
│   │   ├── product-showcase.php # Product showcase
│   │   ├── training-division.php # Training section
│   │   └── woocommerce/        # WooCommerce components
│   │       ├── product-price.php # Product pricing
│   │       ├── product-status-badge.php # Status badges
│   │       └── shop-filter-bar.php # Shop filters
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
├── archive.php                 # Archive template (shop, category, tag, blog)
├── search.php                  # Search results
├── 404.php                     # Error page
├── README.md                   # Theme documentation
└── DEVELOPMENT_GUIDELINES.md  # This file
```

## 🔧 **Core Architecture Principles**

### **1. Modular PHP Structure**

- **`functions.php`**: Minimal bootstrap file that only loads includes
- **`inc/` directory**: Contains all PHP functionality split by purpose
- **No monolithic files**: Each file has a single responsibility

### **2. Template Organization**

- **`templates/` directory**: All template parts organized by function
- **Logical grouping**: Header, footer, parts, content separated
- **Consistent naming**: Clear, descriptive file names

### **3. Asset Management**

- **Organized structure**: CSS, JS, images in separate directories
- **Optimized loading**: Proper enqueuing with dependencies
- **Performance focus**: WebP support, lazy loading

## 🚀 **Abstracted Functions**

### **Hero Section Functions**

```php
// Render hero section programmatically
primefit_render_hero(array(
    'heading' => 'My Hero Title',
    'subheading' => 'Hero subtitle',
    'cta_text' => 'Shop Now',
    'cta_link' => '/shop',
    'overlay_position' => 'center', // 'left', 'center', 'right'
    'text_color' => 'light', // 'light', 'dark'
    'height' => 'medium', // 'auto', 'small', 'medium', 'large', 'full'
    'parallax' => false,
    'overlay_opacity' => 0.4
));

// Get hero config for different page types
$hero_config = primefit_get_hero_config_for_page('shop', $custom_args);
```

### **Product Loop Functions**

```php
// Render product loop programmatically
primefit_render_product_loop(array(
    'title' => 'Featured Products',
    'limit' => 8,
    'columns' => 4,
    'orderby' => 'date', // 'date', 'price', 'popularity', 'rating'
    'order' => 'DESC',
    'category' => 'clothing',
    'tag' => 'sale',
    'featured' => true,
    'on_sale' => false,
    'best_selling' => false,
    'show_view_all' => true,
    'view_all_text' => 'VIEW ALL',
    'view_all_link' => '/shop',
    'section_class' => 'product-section',
    'header_alignment' => 'center',
    'layout' => 'grid' // 'grid', 'carousel', 'list'
));

// Get product loop config for different contexts
$product_config = primefit_get_product_loop_config('sale', $custom_args);
```

### **Shortcodes**

```php
// Hero section shortcode
[primefit_hero heading="My Title" subheading="My subtitle" cta_text="Shop Now" cta_link="/shop"]

// Product loop shortcode
[primefit_products title="Featured Products" limit="8" columns="4" featured="true"]
```

## 📋 **Development Guidelines**

### **File Organization Rules**

1. **Never modify `functions.php` directly** - Use modular files in `inc/`
2. **Template parts go in `templates/`** - Organized by function
3. **Assets go in `assets/`** - CSS, JS, images in subdirectories
4. **WooCommerce overrides go in `woocommerce/`** - Keep separate

### **Code Standards**

1. **WordPress Coding Standards**: Follow WPCS guidelines
2. **Security First**: Always sanitize and escape data
3. **Performance Focus**: Optimize queries, minimize HTTP requests
4. **Documentation**: Comment all functions and complex logic
5. **Error Handling**: Use proper fallbacks and checks

### **Function Naming Conventions**

- **Prefix**: All functions use `primefit_` prefix
- **Descriptive**: Clear, self-documenting names
- **Consistent**: Follow WordPress naming patterns

### **Template Part Usage**

```php
// Correct usage
get_template_part('templates/parts/hero', null, $args);
get_template_part('templates/header/header-actions');

// Abstracted function usage
primefit_render_hero($hero_args);
primefit_render_product_loop($product_args);
```

## 🛠 **Key Components**

### **Header Structure**

- **Mobile menu toggle**: Hamburger button
- **Primary navigation**: Main menu
- **Brand logo**: Site logo
- **Header actions**: LOCATIONS, ACCOUNT, SEARCH, CART (with `header-actions--mobile` class)

### **Homepage Sections**

1. **Hero Section**: Customizable via WordPress Customizer
2. **Featured Products**: Sale items with abstracted product loop
3. **Training Division**: Custom section with image and CTAs
4. **Product Showcase**: New arrivals with abstracted product loop
5. **Category Tiles**: Grid of product categories

### **WooCommerce Integration**

- **Custom product loops**: Enhanced with hover effects and size selection
- **Product custom fields**: Highlights, details, additional content
- **Custom product tabs**: Dynamic tabs based on content
- **Cart fragments**: AJAX cart updates
- **Shop customization**: Custom filter bars and sorting

## 🔍 **Debugging & Testing**

### **Debug Functions**

```php
// Size overlay debugging
// Add ?debug_sizes=1 to any page URL
```

### **Common Issues & Solutions**

1. **Missing template parts**: Check file paths in `templates/` directory
2. **Asset loading issues**: Verify paths in `inc/enqueue.php`
3. **WooCommerce not working**: Check `inc/woocommerce.php` includes
4. **Hero not displaying**: Check `primefit_get_hero_config()` function

## 📝 **Adding New Features**

### **Step-by-Step Process**

1. **Create functionality** in appropriate `inc/` file
2. **Add template parts** to `templates/` directory
3. **Update asset paths** in `inc/enqueue.php` if needed
4. **Test functionality** across different page types
5. **Document changes** in this file

### **Example: Adding a New Section**

```php
// 1. Add function to inc/helpers.php
function primefit_render_new_section($args = array()) {
    // Implementation
}

// 2. Create template part in templates/parts/new-section.php
// 3. Use in templates:
primefit_render_new_section($section_args);
```

## 🎨 **Styling Guidelines**

### **CSS Organization**

- **`app.css`**: Main theme styles
- **`woocommerce.css`**: WooCommerce-specific styles
- **`variables.css`**: CSS custom properties

### **Class Naming**

- **BEM Methodology**: Block\_\_Element--Modifier
- **Consistent prefixes**: Use theme-specific prefixes
- **Mobile-first**: Responsive design approach

## 🔄 **Maintenance Tasks**

### **Regular Updates**

1. **WordPress compatibility**: Test with latest WP version
2. **WooCommerce compatibility**: Test with latest WC version
3. **Performance optimization**: Monitor loading times
4. **Security updates**: Keep dependencies updated

### **Before Major Changes**

1. **Backup current state**: Create git commit
2. **Test in staging**: Verify functionality
3. **Update documentation**: Modify this file
4. **Deploy carefully**: Monitor for issues

## 📞 **Support Information**

### **Key Files to Check**

- **`functions.php`**: Main bootstrap (should be minimal)
- **`inc/helpers.php`**: Abstracted functions
- **`inc/enqueue.php`**: Asset loading
- **`templates/parts/hero.php`**: Hero section template
- **`templates/header/header-actions.php`**: Header actions with cart

### **Common Commands**

```bash
# Check file structure
find . -name "*.php" | head -20

# Check for linting errors
# Use IDE linting or WordPress coding standards
```

---

**Last Updated**: 2024  
**Theme Version**: 1.0.0  
**WordPress Compatibility**: 5.0+  
**WooCommerce Compatibility**: 3.0+

**Remember**: This theme follows WordPress best practices with a modular, maintainable structure. Always preserve the existing architecture when making changes.
