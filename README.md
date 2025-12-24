# MLR Gift Registry

WordPress Gift Registry Plugin - A complete solution for gift lists with WooCommerce integration, Elementor Pro support, and Chilean payment gateways (Webpay).

## Overview

MLR Gift Registry is a WordPress plugin that enables users to create and manage gift registries (gift lists) for events like weddings, birthdays, baby showers, and more. Users can share their lists with guests who can then contribute money towards specific gift items.

### Key Features

- ✅ **Create Gift Lists** - Users can create multiple gift lists for different events
- 💝 **Gift Management** - Add, edit, and manage desired gifts with prices
- 💰 **Monetary Contributions** - Guests contribute money towards gifts instead of purchasing physical items
- 🎁 **Share & Track** - Generate shareable links and QR codes for each list
- 📊 **Dashboard** - Track donations and gifts received in real-time
- 🛒 **WooCommerce Integration** - Seamless payment processing through WooCommerce
- 🎨 **Elementor Pro Support** - Build custom pages with Elementor widgets
- 🇨🇱 **Chilean Payments** - Compatible with Webpay and other Chilean payment gateways
- 📅 **Payment Calendar** - Bi-weekly payout schedule for accumulated donations

## Project Status

**Current Phase:** Core Architecture & Data Model (In Progress)

### Completed ✅
- [x] Plugin bootstrap and main class
- [x] Custom Post Type (gift_list) registration
- [x] Event Type taxonomy
- [x] MLR_List_Service class with CRUD operations
- [x] Dependency checks (WooCommerce, Elementor)
- [x] .gitignore configuration

### In Progress 🔄
- [ ] WooCommerce integration class
- [ ] Elementor widgets (List header, totals, gifts grid, form)
- [ ] Frontend templates and pages
- [ ] Payment gateway integration

### Planned 📋
- [ ] Gift item management service
- [ ] Donation tracking and analytics
- [ ] Email notifications
- [ ] User profile customization
- [ ] Admin dashboard
- [ ] Multi-language support

## Architecture

### Plugin Structure

```
mlr-gift-registry/
├── mlr-gift-registry.php          # Plugin main file
├── includes/
│   ├── class-mlr-list-service.php # Gift list CRUD operations
│   ├── class-mlr-woocommerce.php  # WooCommerce integration
│   └── ...
├── elementor/
│   └── widgets/                   # Custom Elementor widgets
├── templates/                     # Frontend templates
├── assets/                        # CSS, JS, images
└── languages/                     # Translation files
```

### Data Model

**Gift List (Custom Post Type: gift_list)**
- Post Title: Celebrant name(s)
- Post Content: Event description
- Meta Data:
  - `_mlr_event_type`: Event category (wedding, birthday, etc.)
  - `_mlr_event_date`: Event date
  - `_mlr_total_donated`: Total money received
  - `_mlr_gifts`: Array of gift items
  - `_mlr_guest_contributions`: Contributor tracking

**Gift Items**
- ID: Unique identifier
- Name: Gift name
- Price: Reference price
- Quantity: Desired quantity
- Donated Amount: Money collected
- Status: Available/Reserved/Completed

## Installation

### Requirements
- WordPress 5.6+
- PHP 7.4+
- WooCommerce 4.0+
- Elementor 3.0+ (Elementor Pro recommended)

### Setup

1. Clone this repository into your `/wp-content/plugins/` directory
2. Activate the plugin from WordPress admin
3. Ensure WooCommerce and Elementor are active
4. Configure payment gateway settings

## Development

### Contributing

Contributions are welcome! Please ensure:
- Code follows WordPress coding standards
- All functions are properly documented
- Changes include appropriate tests
- Commit messages follow conventional commits format

### Coding Standards

This project follows WordPress coding standards:
- PHP code style: `phpcs`
- Naming: snake_case for functions, classes use PascalCase
- Documentation: JSDoc for JavaScript, DocBlocks for PHP

## License

MIT License - See LICENSE file for details

## Author

**Kaam12** - [@Kaam12](https://github.com/Kaam12)

## Roadmap

See [Issues](https://github.com/Kaam12/mlr-gift-registry/issues) for planned features and bug tracking.
