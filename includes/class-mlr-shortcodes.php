<?php
/**
 * MLR Shortcodes
 * Register and handle all plugin shortcodes
 * 
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MLR_Shortcodes {
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_loaded', array( $this, 'register_shortcodes' ) );
    }

    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode( 'mlr_create_list', array( $this, 'create_list_shortcode' ) );
        add_shortcode( 'mlr_my_lists', array( $this, 'my_lists_shortcode' ) );
        add_shortcode( 'mlr_list_view', array( $this, 'list_view_shortcode' ) );
        add_shortcode( 'mlr_dashboard', array( $this, 'dashboard_shortcode' ) );
        add_shortcode( 'mlr_checkout', array( $this, 'checkout_shortcode' ) );
    }

    /**
     * Create List Shortcode [mlr_create_list]
     */
    public function create_list_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Please log in to create a gift list.', 'mlr-gift-registry' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="mlr-create-list-container">
            <form id="mlr-create-list-form" method="post">
                <?php wp_nonce_field( 'mlr_create_list_nonce', 'mlr_nonce' ); ?>
                <div class="form-group">
                    <label for="list-name"><?php _e( 'List Name', 'mlr-gift-registry' ); ?></label>
                    <input type="text" id="list-name" name="list_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="list-description"><?php _e( 'Description', 'mlr-gift-registry' ); ?></label>
                    <textarea id="list-description" name="list_description" class="form-control" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="event-type"><?php _e( 'Event Type', 'mlr-gift-registry' ); ?></label>
                    <select id="event-type" name="event_type" class="form-control" required>
                        <option value="wedding"><?php _e( 'Wedding', 'mlr-gift-registry' ); ?></option>
                        <option value="birthday"><?php _e( 'Birthday', 'mlr-gift-registry' ); ?></option>
                        <option value="baby_shower"><?php _e( 'Baby Shower', 'mlr-gift-registry' ); ?></option>
                        <option value="other"><?php _e( 'Other', 'mlr-gift-registry' ); ?></option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?php _e( 'Create List', 'mlr-gift-registry' ); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * My Lists Shortcode [mlr_my_lists]
     */
    public function my_lists_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Please log in to view your lists.', 'mlr-gift-registry' ) . '</p>';
        }

        $user_id = get_current_user_id();
        $user = new MLR_User( $user_id );
        $lists = $user->get_lists();

        ob_start();
        ?>
        <div class="mlr-my-lists-container">
            <h2><?php _e( 'My Gift Lists', 'mlr-gift-registry' ); ?></h2>
            <?php if ( ! empty( $lists ) ) : ?>
                <div class="mlr-lists-grid">
                    <?php foreach ( $lists as $list ) : ?>
                        <div class="mlr-list-card">
                            <h3><?php echo esc_html( $list->post_title ); ?></h3>
                            <p class="list-description"><?php echo wp_kses_post( $list->post_content ); ?></p>
                            <div class="list-actions">
                                <a href="<?php echo esc_url( add_query_arg( 'list_id', $list->ID ) ); ?>" class="btn btn-primary"><?php _e( 'View', 'mlr-gift-registry' ); ?></a>
                                <a href="<?php echo esc_url( add_query_arg( 'list_id', $list->ID ) ); ?>" class="btn btn-secondary"><?php _e( 'Edit', 'mlr-gift-registry' ); ?></a>
                                <a href="<?php echo esc_url( add_query_arg( 'list_id', $list->ID ) ); ?>" class="btn btn-danger"><?php _e( 'Delete', 'mlr-gift-registry' ); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p><?php _e( 'You have no gift lists yet.', 'mlr-gift-registry' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * List View Shortcode [mlr_list_view id="123"]
     */
    public function list_view_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id' => 0,
        ), $atts, 'mlr_list_view' );

        $list_id = intval( $atts['id'] );
        if ( ! $list_id ) {
            return '<p>' . __( 'Invalid list ID.', 'mlr-gift-registry' ) . '</p>';
        }

        $list = get_post( $list_id );
        if ( ! $list || 'mlr_list' !== $list->post_type ) {
            return '<p>' . __( 'List not found.', 'mlr-gift-registry' ) . '</p>';
        }

        $list_service = new MLR_List_Service();
        $items = $list_service->get_list_items( $list_id );

        ob_start();
        ?>
        <div class="mlr-list-view-container">
            <h2><?php echo esc_html( $list->post_title ); ?></h2>
            <div class="list-header">
                <p class="list-creator"><?php echo sprintf( __( 'Created by: %s', 'mlr-gift-registry' ), esc_html( get_the_author_meta( 'display_name', $list->post_author ) ) ); ?></p>
                <p class="list-total"><?php echo sprintf( __( 'Total items: %d', 'mlr-gift-registry' ), count( $items ) ); ?></p>
            </div>
            <?php if ( ! empty( $items ) ) : ?>
                <div class="mlr-items-grid">
                    <?php foreach ( $items as $item ) : ?>
                        <div class="mlr-item-card">
                            <h4><?php echo esc_html( $item['name'] ); ?></h4>
                            <p class="item-price"><?php echo sprintf( __( 'Price: CLP %d', 'mlr-gift-registry' ), intval( $item['price'] ) ); ?></p>
                            <p class="item-description"><?php echo wp_kses_post( $item['description'] ); ?></p>
                            <a href="<?php echo esc_url( add_query_arg( 'item_id', $item['id'] ) ); ?>" class="btn btn-success"><?php _e( 'Contribute', 'mlr-gift-registry' ); ?></a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p><?php _e( 'This list has no items yet.', 'mlr-gift-registry' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Dashboard Shortcode [mlr_dashboard]
     */
    public function dashboard_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Please log in to view your dashboard.', 'mlr-gift-registry' ) . '</p>';
        }

        $user_id = get_current_user_id();
        $user = new MLR_User( $user_id );
        $profile = $user->get_profile();
        $earnings = $user->get_total_earnings();
        $withdrawals = $user->get_withdrawals();

        ob_start();
        ?>
        <div class="mlr-dashboard-container">
            <h2><?php _e( 'Your Dashboard', 'mlr-gift-registry' ); ?></h2>
            <div class="dashboard-grid">
                <div class="dashboard-card earnings">
                    <h3><?php _e( 'Total Earnings', 'mlr-gift-registry' ); ?></h3>
                    <p class="amount">CLP <?php echo number_format( $earnings, 0 ); ?></p>
                </div>
                <div class="dashboard-card profile">
                    <h3><?php _e( 'Profile', 'mlr-gift-registry' ); ?></h3>
                    <p><?php echo sprintf( __( 'Email: %s', 'mlr-gift-registry' ), esc_html( $profile['email'] ) ); ?></p>
                    <p><?php echo sprintf( __( 'Phone: %s', 'mlr-gift-registry' ), esc_html( $profile['phone'] ) ); ?></p>
                </div>
            </div>
            <div class="withdrawals-section">
                <h3><?php _e( 'Recent Withdrawals', 'mlr-gift-registry' ); ?></h3>
                <?php if ( ! empty( $withdrawals ) ) : ?>
                    <table class="withdrawals-table">
                        <thead>
                            <tr>
                                <th><?php _e( 'Date', 'mlr-gift-registry' ); ?></th>
                                <th><?php _e( 'Amount', 'mlr-gift-registry' ); ?></th>
                                <th><?php _e( 'Status', 'mlr-gift-registry' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $withdrawals as $withdrawal ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $withdrawal['created_at'] ); ?></td>
                                    <td><?php echo sprintf( __( 'CLP %d', 'mlr-gift-registry' ), intval( $withdrawal['amount'] ) ); ?></td>
                                    <td><span class="status <?php echo esc_attr( $withdrawal['status'] ); ?>"><?php echo esc_html( ucfirst( $withdrawal['status'] ) ); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php _e( 'No withdrawals yet.', 'mlr-gift-registry' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Checkout Shortcode [mlr_checkout]
     */
    public function checkout_shortcode( $atts ) {
        ob_start();
        ?>
        <div class="mlr-checkout-container">
            <h2><?php _e( 'Checkout', 'mlr-gift-registry' ); ?></h2>
            <?php woocommerce_cart(); ?>
            <?php woocommerce_checkout(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
