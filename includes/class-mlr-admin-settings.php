<?php
/**
 * MLR Admin Settings
 * Manages plugin configuration and admin pages
 * 
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MLR_Admin_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'MLR Gift Registry', 'mlr-gift-registry' ),
            __( 'Gift Registry', 'mlr-gift-registry' ),
            'manage_options',
            'mlr-gift-registry',
            array( $this, 'render_dashboard_page' ),
            'dashicons-gifts',
            25
        );

        add_submenu_page(
            'mlr-gift-registry',
            __( 'Settings', 'mlr-gift-registry' ),
            __( 'Settings', 'mlr-gift-registry' ),
            'manage_options',
            'mlr-settings',
            array( $this, 'render_settings_page' )
        );

        add_submenu_page(
            'mlr-gift-registry',
            __( 'Payouts', 'mlr-gift-registry' ),
            __( 'Payouts', 'mlr-gift-registry' ),
            'manage_options',
            'mlr-payouts',
            array( $this, 'render_payouts_page' )
        );

        add_submenu_page(
            'mlr-gift-registry',
            __( 'Transactions', 'mlr-gift-registry' ),
            __( 'Transactions', 'mlr-gift-registry' ),
            'manage_options',
            'mlr-transactions',
            array( $this, 'render_transactions_page' )
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( 'mlr_settings_group', 'mlr_webpay_commerce_code' );
        register_setting( 'mlr_settings_group', 'mlr_webpay_api_key' );
        register_setting( 'mlr_settings_group', 'mlr_webpay_environment' );
        register_setting( 'mlr_settings_group', 'mlr_platform_fee' );
        register_setting( 'mlr_settings_group', 'mlr_min_withdrawal' );
        register_setting( 'mlr_settings_group', 'mlr_email_notifications' );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'mlr-' ) === false ) {
            return;
        }

        wp_enqueue_style( 'mlr-admin-css', MLR_GIFT_REGISTRY_URL . 'assets/css/admin.css', array(), '1.0.0' );
        wp_enqueue_script( 'mlr-admin-js', MLR_GIFT_REGISTRY_URL . 'assets/js/admin.js', array( 'jquery' ), '1.0.0', true );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        global $wpdb;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div class="mlr-dashboard-grid">
                <div class="mlr-stat-card">
                    <h3><?php _e( 'Total Lists', 'mlr-gift-registry' ); ?></h3>
                    <p class="stat-value"><?php echo wp_count_posts( 'mlr_list' )->publish; ?></p>
                </div>

                <div class="mlr-stat-card">
                    <h3><?php _e( 'Total Users', 'mlr-gift-registry' ); ?></h3>
                    <p class="stat-value"><?php echo count_users()['total_users']; ?></p>
                </div>

                <div class="mlr-stat-card">
                    <h3><?php _e( 'Pending Payouts', 'mlr-gift-registry' ); ?></h3>
                    <?php
                    $pending = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mlr_payouts WHERE status = 'pending'" );
                    ?>
                    <p class="stat-value"><?php echo intval( $pending ); ?></p>
                </div>

                <div class="mlr-stat-card">
                    <h3><?php _e( 'Total Processed', 'mlr-gift-registry' ); ?></h3>
                    <?php
                    $total = $wpdb->get_var( "SELECT SUM(amount) FROM {$wpdb->prefix}mlr_payouts WHERE status = 'completed'" );
                    ?>
                    <p class="stat-value">CLP <?php echo number_format( $total, 0 ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to access this page.', 'mlr-gift-registry' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php settings_fields( 'mlr_settings_group' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Webpay Commerce Code</th>
                        <td>
                            <input type="text" name="mlr_webpay_commerce_code" value="<?php echo esc_attr( get_option( 'mlr_webpay_commerce_code' ) ); ?>" />
                            <p class="description">Your Webpay commerce code from Transbank</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Webpay API Key</th>
                        <td>
                            <input type="password" name="mlr_webpay_api_key" value="<?php echo esc_attr( get_option( 'mlr_webpay_api_key' ) ); ?>" />
                            <p class="description">Your Webpay API key (kept secure)</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Environment</th>
                        <td>
                            <select name="mlr_webpay_environment">
                                <option value="sandbox" <?php selected( get_option( 'mlr_webpay_environment' ), 'sandbox' ); ?>>Sandbox (Testing)</option>
                                <option value="production" <?php selected( get_option( 'mlr_webpay_environment' ), 'production' ); ?>>Production</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Platform Fee (%)</th>
                        <td>
                            <input type="number" step="0.01" name="mlr_platform_fee" value="<?php echo esc_attr( get_option( 'mlr_platform_fee', 10 ) ); ?>" />
                            <p class="description">Percentage fee charged on transactions</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Minimum Withdrawal (CLP)</th>
                        <td>
                            <input type="number" name="mlr_min_withdrawal" value="<?php echo esc_attr( get_option( 'mlr_min_withdrawal', 5000 ) ); ?>" />
                            <p class="description">Minimum amount users can withdraw</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Email Notifications</th>
                        <td>
                            <input type="checkbox" name="mlr_email_notifications" value="1" <?php checked( get_option( 'mlr_email_notifications' ), 1 ); ?> />
                            <p class="description">Send email notifications for transactions and payouts</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render payouts page
     */
    public function render_payouts_page() {
        global $wpdb;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Amount (CLP)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $payouts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}mlr_payouts ORDER BY created_at DESC LIMIT 50" );
                    foreach ( $payouts as $payout ) {
                        $user = get_user_by( 'ID', $payout->user_id );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $payout->id ); ?></td>
                            <td><?php echo esc_html( $user->display_name ); ?></td>
                            <td>CLP <?php echo number_format( $payout->amount, 0 ); ?></td>
                            <td><span class="status-badge <?php echo esc_attr( $payout->status ); ?>"><?php echo esc_html( ucfirst( $payout->status ) ); ?></span></td>
                            <td><?php echo esc_html( $payout->created_at ); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render transactions page
     */
    public function render_transactions_page() {
        global $wpdb;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order</th>
                        <th>Amount (CLP)</th>
                        <th>Fee (CLP)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $transactions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}mlr_transactions ORDER BY created_at DESC LIMIT 50" );
                    foreach ( $transactions as $txn ) {
                        ?>
                        <tr>
                            <td><?php echo esc_html( $txn->id ); ?></td>
                            <td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $txn->order_id . '&action=edit' ) ); ?>">#<?php echo esc_html( $txn->order_id ); ?></a></td>
                            <td>CLP <?php echo number_format( $txn->amount, 0 ); ?></td>
                            <td>CLP <?php echo number_format( $txn->platform_fee, 0 ); ?></td>
                            <td><span class="status-badge <?php echo esc_attr( $txn->status ); ?>"><?php echo esc_html( ucfirst( $txn->status ) ); ?></span></td>
                            <td><?php echo esc_html( $txn->created_at ); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
