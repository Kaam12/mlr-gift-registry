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


### Phase 1: Core Architecture (Completed ✅)
- [x] Plugin bootstrap and initialization
- [x] Custom Post Type for gift lists
- [x] Database schema design and activation hooks
- [x] User authentication and profile management
- [x] WooCommerce integration
- [x] Webpay payment gateway integration

### Phase 2: Frontend Implementation (In Progress)
- [ ] Shortcodes for list creation, display, and dashboard
- [ ] Template system for list views
- [ ] User dashboard with earnings tracking
- [ ] Withdrawal request system
- [ ] Admin settings page for gateway configuration

### Phase 3: Advanced Features
- [ ] REST API endpoints
- [ ] Advanced analytics and reporting
- [ ] Multi-currency support
- [ ] Payment history and export
- [ ] Automated payout scheduling

## Plugin Architecture

### Core Classes (Implemented)

#### MLR_Ledger
- Double-entry accounting system
- Transaction tracking and balance calculation
- Database: `wp_mlr_ledger` table

#### MLR_List_Service
- Gift list CRUD operations
- Item management
- List sharing and access control

#### MLR_WooCommerce
- Cart integration
- Platform fee calculation (10%)
- Order processing

#### MLR_User
- User profile management
- RUT (Chilean ID) validation
- Earnings calculation
- List ownership tracking

#### MLR_Webpay
- Transbank Webpay integration
- Transaction initialization and validation
- Refund processing
- Payment method storage

#### MLR_Payouts
- Withdrawal request creation
- Payout status management
- Batch processing
- Statistics and reporting

#### MLR_Utilities
- RUT validator with checksum algorithm
- QR code generation
- Helper functions

#### MLR_Shortcodes
- `[mlr_create_list]` - Create new gift list
- `[mlr_my_lists]` - Display user's lists
- `[mlr_list_view id="123"]` - Display specific list
- `[mlr_dashboard]` - User earnings dashboard
- `[mlr_checkout]` - WooCommerce checkout integration

#### MLR_Activator/Deactivator
- Database table creation on activation
- Custom post type registration
- Rewrite rules management

## Database Schema

### wp_mlr_ledger
Double-entry accounting table for financial tracking:
- `id` - Primary key
- `user_id` - User reference
- `type` - 'credit' or 'debit'
- `amount` - Transaction amount in CLP
- `reason` - Transaction reason
- `created_at` - Timestamp

### wp_mlr_payouts
Payout/withdrawal management:
- `id` - Primary key
- `user_id` - User reference
- `amount` - Withdrawal amount
- `fee` - Platform fee (2%)
- `net_amount` - Amount after fees
- `status` - pending/processing/completed/cancelled
- `bank_account` - User's bank account

### wp_mlr_transactions
WooCommerce transaction tracking:
- `id` - Primary key
- `order_id` - WooCommerce order reference
- `list_id` - Gift list reference
- `amount` - Transaction amount
- `platform_fee` - Fee amount
- `status` - Transaction status

## Hooks & Filters

### Plugin Hooks
```php
// Activation
register_activation_hook( __FILE__, array( 'MLR_Activator', 'activate' ) );

// Deactivation  
register_deactivation_hook( __FILE__, array( 'MLR_Deactivator', 'deactivate' ) );

// Custom action hooks
do_action( 'mlr_payout_requested', $payout_id, $user_id, $amount );
do_action( 'mlr_payout_completed', $payout_id, $user_id );
do_action( 'mlr_list_created', $list_id, $user_id );
```

## Configuration

### Webpay Settings
Configure in WordPress admin or via filters:
```php
add_filter( 'mlr_webpay_commerce_code', function() {
    return '595945000612'; // Your Webpay code
} );

add_filter( 'mlr_webpay_api_key', function() {
    return 'your-api-key-here';
} );

add_filter( 'mlr_webpay_environment', function() {
    return 'sandbox'; // or 'production'
} );
```

## Usage Examples

### Display Gift List
```
[mlr_list_view id="123"]
```

### Show User Dashboard
```
[mlr_dashboard]
```

### Create New List
```
[mlr_create_list]
```

## Next Steps

1. **Theme Integration**: Use GeneratePress theme for visual styling
2. **Elementor Widgets**: Create custom Elementor widgets for advanced layouts
3. **Admin Dashboard**: Build comprehensive admin panel for site managers
4. **Email Notifications**: Add automated email notifications for transactions
5. **Analytics**: Implement analytics dashboard for tracking system usage
