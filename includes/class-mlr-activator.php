<?php
/**
 * MLR Plugin Activator
 * Handles plugin activation and database table creation
 * 
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MLR_Activator {
    /**
     * Activate plugin
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Create necessary post types
        self::register_post_types();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create plugin database tables
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Ledger table
        $ledger_table = $wpdb->prefix . 'mlr_ledger';
        $ledger_sql = "CREATE TABLE IF NOT EXISTS {$ledger_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'credit',
            amount decimal(10,2) NOT NULL,
            reason varchar(100),
            payout_id mediumint(9),
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type)
        ) $charset_collate;";

        // Payouts table
        $payouts_table = $wpdb->prefix . 'mlr_payouts';
        $payouts_sql = "CREATE TABLE IF NOT EXISTS {$payouts_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            fee decimal(10,2) NOT NULL DEFAULT 0,
            net_amount decimal(10,2) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            transaction_id varchar(100),
            bank_account varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime,
            completed_at datetime,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Transactions table for WooCommerce integration
        $transactions_table = $wpdb->prefix . 'mlr_transactions';
        $transactions_sql = "CREATE TABLE IF NOT EXISTS {$transactions_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            list_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            platform_fee decimal(10,2) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            payment_method varchar(50),
            transaction_id varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY list_id (list_id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $ledger_sql );
        dbDelta( $payouts_sql );
        dbDelta( $transactions_sql );
    }

    /**
     * Register custom post types
     */
    private static function register_post_types() {
        register_post_type( 'mlr_list', array(
            'label' => 'Gift Lists',
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'supports' => array( 'title', 'editor', 'author', 'thumbnail' ),
        ) );
    }
}

/**
 * MLR Plugin Deactivator
 */
class MLR_Deactivator {
    /**
     * Deactivate plugin
     */
    public static function deactivate() {
        // Optionally delete tables or do cleanup
        // For safety, we don't delete tables on deactivation
        flush_rewrite_rules();
    }
}
