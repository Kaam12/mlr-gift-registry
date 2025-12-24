<?php
/**
 * MLR Gift Registry Plugin
 *
 * @package MLR_Gift_Registry
 * @version 1.0.0
 * 
 * Plugin Name: MLR Gift Registry
 * Plugin URI: https://github.com/Kaam12/mlr-gift-registry
 * Description: Complete gift registry system with WooCommerce integration, Elementor Pro support, and Chilean payment gateways.
 * Version: 1.0.0
 * Author: Kaam12
 * Author URI: https://github.com/Kaam12
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: mlr-gift-registry
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce, elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants FIRST (before any requires)
define( 'MLR_GIFT_REGISTRY_VERSION', '1.0.0' );
define( 'MLR_GIFT_REGISTRY_FILE', __FILE__ );
define( 'MLR_GIFT_REGISTRY_PATH', plugin_dir_path( __FILE__ ) );
define( 'MLR_GIFT_REGISTRY_URL', plugin_dir_url( __FILE__ ) );
define( 'MLR_GIFT_REGISTRY_BASENAME', plugin_basename( __FILE__ ) );

// Include configuration and helper functions
require_once MLR_GIFT_REGISTRY_PATH . 'includes/constants.php';
require_once MLR_GIFT_REGISTRY_PATH . 'includes/helpers.php';

// Main plugin class
class MLR_Gift_Registry {
    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Load text domain
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        // Register activation and deactivation hooks
        register_activation_hook( MLR_GIFT_REGISTRY_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( MLR_GIFT_REGISTRY_FILE, array( $this, 'deactivate' ) );
        // Initialize plugin
        add_action( 'init', array( $this, 'init' ) );
    }

    /**
     * Load textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mlr-gift-registry',
            false,
            trailingslashit( dirname( MLR_GIFT_REGISTRY_BASENAME ) ) . 'languages'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Register CPTs and flush rewrite rules
        $this->register_custom_post_types();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin initialization
     */
    public function init() {
        // Check dependencies
        if ( ! $this->check_dependencies() ) {
            return;
        }
        // Register custom post types
        $this->register_custom_post_types();
        // Register custom taxonomies
        $this->register_custom_taxonomies();
        // Load includes
        $this->load_includes();
    }

    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        // Check if WooCommerce is active
        if ( ! function_exists( 'WC' ) ) {
            add_action( 'admin_notices', function() {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e( 'MLR Gift Registry requires WooCommerce to be installed and activated.', 'mlr-gift-registry' ); ?></p>
                </div>
                <?php
            });
            return false;
        }
        // Check if Elementor is active
        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            add_action( 'admin_notices', function() {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e( 'MLR Gift Registry requires Elementor to be installed and activated.', 'mlr-gift-registry' ); ?></p>
                </div>
                <?php
            });
            return false;
        }
        return true;
    }

    /**
     * Register custom post types
     */
    private function register_custom_post_types() {
        // Gift List CPT
        register_post_type( 'gift_list', array(
            'labels' => array(
                'name' => __( 'Gift Lists', 'mlr-gift-registry' ),
                'singular_name' => __( 'Gift List', 'mlr-gift-registry' ),
            ),
            'public' => true,
            'show_in_rest' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
            'has_archive' => false,
            'rewrite' => array( 'slug' => 'lista' ),
            'menu_icon' => 'dashicons-heart',
            'capability_type' => 'post',
        ));
    }

    /**
     * Register custom taxonomies
     */
    private function register_custom_taxonomies() {
        // Event type taxonomy
        register_taxonomy( 'event_type', 'gift_list', array(
            'labels' => array(
                'name' => __( 'Event Types', 'mlr-gift-registry' ),
                'singular_name' => __( 'Event Type', 'mlr-gift-registry' ),
            ),
            'public' => true,
            'show_in_rest' => true,
            'hierarchical' => true,
        ));
    }

    /**
     * Load plugin includes
     */
    private function load_includes() {
        // Load all plugin classes
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-ledger.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-list-service.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-utilities.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-woocommerce.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-user.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-webpay.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-payouts.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-shortcodes.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-admin-settings.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-emails.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-api.php';
        require_once MLR_GIFT_REGISTRY_PATH . 'includes/class-mlr-activator.php';

        // Register activation/deactivation hooks
        register_activation_hook( MLR_GIFT_REGISTRY_FILE, array( 'MLR_Activator', 'activate' ) );
        register_deactivation_hook( MLR_GIFT_REGISTRY_FILE, array( 'MLR_Activator', 'deactivate' ) );

        // Initialize classes
        new MLR_Ledger();
        new MLR_WooCommerce();
        new MLR_Webpay();
        new MLR_Payouts();
        new MLR_Admin_Settings();
        new MLR_Emails();
        new MLR_API();
    }
}

// Initialize the plugin
MLR_Gift_Registry::get_instance();
