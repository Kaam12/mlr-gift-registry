<?php
/**
 * MLR REST API
 * Provides REST API endpoints for frontend AJAX operations
 * 
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MLR_API {
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Lists endpoints
        register_rest_route( 'mlr/v1', '/lists', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_user_lists' ),
            'permission_callback' => array( $this, 'check_user_auth' ),
        ) );

        register_rest_route( 'mlr/v1', '/lists', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_list' ),
            'permission_callback' => array( $this, 'check_user_auth' ),
        ) );

        // Payout endpoints
        register_rest_route( 'mlr/v1', '/payouts', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_user_payouts' ),
            'permission_callback' => array( $this, 'check_user_auth' ),
        ) );

        register_rest_route( 'mlr/v1', '/payouts', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'request_payout' ),
            'permission_callback' => array( $this, 'check_user_auth' ),
        ) );

        // User endpoints
        register_rest_route( 'mlr/v1', '/user', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_user_profile' ),
            'permission_callback' => array( $this, 'check_user_auth' ),
        ) );

        register_rest_route( 'mlr/v1', '/user', array(
            'methods'             => \WP_REST_Server::EDITABLE,
            'callback'            => array( $this, 'update_user_profile' ),
            'permission_callback' => array( $this, 'check_user_auth' ),
        ) );

        // Statistics endpoint
        register_rest_route( 'mlr/v1', '/stats', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_user_stats' ),
            'permission_callback' => array( $this, 'check_user_auth' ),
        ) );
    }

    /**
     * Check user authentication
     */
    public function check_user_auth() {
        return is_user_logged_in();
    }

    /**
     * Get user lists
     */
    public function get_user_lists( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $user = new MLR_User( $user_id );
        $lists = $user->get_lists();

        return rest_ensure_response( array(
            'success' => true,
            'data'    => array_map( function( $list ) {
                return array(
                    'id'    => $list->ID,
                    'title' => $list->post_title,
                    'link'  => get_permalink( $list->ID ),
                );
            }, $lists ),
        ) );
    }

    /**
     * Create list
     */
    public function create_list( \WP_REST_Request $request ) {
        $data = $request->get_json_params();
        $user_id = get_current_user_id();

        $list_service = new MLR_List_Service();
        $list_id = $list_service->create_list( $user_id, $data['title'], $data['description'] ?? '' );

        if ( is_wp_error( $list_id ) ) {
            return rest_ensure_response( array(
                'success' => false,
                'error'   => $list_id->get_error_message(),
            ) );
        }

        return rest_ensure_response( array(
            'success' => true,
            'list_id' => $list_id,
            'link'    => get_permalink( $list_id ),
        ) );
    }

    /**
     * Get user payouts
     */
    public function get_user_payouts( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $user = new MLR_User( $user_id );
        $payouts = $user->get_withdrawals();

        return rest_ensure_response( array(
            'success' => true,
            'data'    => $payouts,
        ) );
    }

    /**
     * Request payout
     */
    public function request_payout( \WP_REST_Request $request ) {
        $data = $request->get_json_params();
        $user_id = get_current_user_id();

        $payouts = new MLR_Payouts();
        $payout_id = $payouts->create_payout_request( $user_id, intval( $data['amount'] ) );

        if ( is_wp_error( $payout_id ) ) {
            return rest_ensure_response( array(
                'success' => false,
                'error'   => $payout_id->get_error_message(),
            ) );
        }

        return rest_ensure_response( array(
            'success'   => true,
            'payout_id' => $payout_id,
            'message'   => __( 'Payout request created successfully', 'mlr-gift-registry' ),
        ) );
    }

    /**
     * Get user profile
     */
    public function get_user_profile( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $user = new MLR_User( $user_id );
        $profile = $user->get_profile();
        $earnings = $user->get_total_earnings();

        return rest_ensure_response( array(
            'success' => true,
            'profile' => $profile,
            'earnings' => $earnings,
        ) );
    }

    /**
     * Update user profile
     */
    public function update_user_profile( \WP_REST_Request $request ) {
        $data = $request->get_json_params();
        $user_id = get_current_user_id();
        $user = new MLR_User( $user_id );

        $result = $user->update_profile( $data );

        return rest_ensure_response( array(
            'success' => is_bool( $result ) ? $result : false,
            'message' => is_bool( $result ) ? __( 'Profile updated', 'mlr-gift-registry' ) : $result->get_error_message(),
        ) );
    }

    /**
     * Get user statistics
     */
    public function get_user_stats( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $user = new MLR_User( $user_id );
        $payouts = new MLR_Payouts();

        $stats = $payouts->get_statistics();

        return rest_ensure_response( array(
            'success' => true,
            'earnings' => $user->get_total_earnings(),
            'withdrawals' => count( $user->get_withdrawals() ),
            'lists' => count( $user->get_lists() ),
            'platform_stats' => $stats,
        ) );
    }
}
