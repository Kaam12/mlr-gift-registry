<?php
/**
 * MLR User Class
 * Handles user profiles, authentication, and list ownership
 * 
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MLR_User {
    /**
     * User ID
     */
    private $user_id;

    /**
     * Constructor
     */
    public function __construct( $user_id = null ) {
        $this->user_id = $user_id ?? get_current_user_id();
    }

    /**
     * Get user profile data
     */
    public function get_profile() {
        $user = get_user_by( 'ID', $this->user_id );
        if ( ! $user ) {
            return false;
        }

        return array(
            'id'            => $user->ID,
            'name'          => $user->display_name,
            'email'         => $user->user_email,
            'phone'         => get_user_meta( $user->ID, '_mlr_phone', true ),
            'bank_account'  => get_user_meta( $user->ID, '_mlr_bank_account', true ),
            'bank_name'     => get_user_meta( $user->ID, '_mlr_bank_name', true ),
            'rut'           => get_user_meta( $user->ID, '_mlr_rut', true ),
            'created_at'    => $user->user_registered,
        );
    }

    /**
     * Update user profile
     */
    public function update_profile( $data ) {
        if ( ! $this->user_id ) {
            return new \WP_Error( 'no_user', 'User not found' );
        }

        $allowed_fields = array( 'phone', 'bank_account', 'bank_name', 'rut' );
        foreach ( $allowed_fields as $field ) {
            if ( isset( $data[ $field ] ) ) {
                update_user_meta( $this->user_id, '_mlr_' . $field, sanitize_text_field( $data[ $field ] ) );
            }
        }

        return true;
    }

    /**
     * Get user's lists
     */
    public function get_lists( $args = array() ) {
        $defaults = array(
            'posts_per_page' => -1,
            'paged'          => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        $args = wp_parse_args( $args, $defaults );

        $lists_query = new \WP_Query( array(
            'post_type'      => 'mlr_list',
            'posts_per_page' => $args['posts_per_page'],
            'paged'          => $args['paged'],
            'orderby'        => $args['orderby'],
            'order'          => $args['order'],
            'author'         => $this->user_id,
        ) );

        return $lists_query->posts;
    }

    /**
     * Check if user owns a list
     */
    public function owns_list( $list_id ) {
        $list = get_post( $list_id );
        return $list && $list->post_author == $this->user_id;
    }

    /**
     * Get user's total earnings
     */
    public function get_total_earnings() {
        global $wpdb;
        $table = $wpdb->prefix . 'mlr_ledger';

        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(amount) FROM {$table} WHERE user_id = %d AND type = 'credit'",
            $this->user_id
        ) );

        return floatval( $result ?? 0 );
    }

    /**
     * Get withdrawal history
     */
    public function get_withdrawals( $limit = 20 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'mlr_payouts';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $this->user_id,
            $limit
        ) );
    }

    /**
     * Request withdrawal
     */
    public function request_withdrawal( $amount ) {
        global $wpdb;
        $earnings = $this->get_total_earnings();

        if ( $amount > $earnings ) {
            return new \WP_Error( 'insufficient_funds', 'Insufficient funds for withdrawal' );
        }

        $payout_table = $wpdb->prefix . 'mlr_payouts';
        $ledger_table = $wpdb->prefix . 'mlr_ledger';

        // Create payout record
        $wpdb->insert( $payout_table, array(
            'user_id'    => $this->user_id,
            'amount'     => $amount,
            'status'     => 'pending',
            'created_at' => current_time( 'mysql' ),
        ) );

        $payout_id = $wpdb->insert_id;

        // Debit ledger
        $wpdb->insert( $ledger_table, array(
            'user_id'    => $this->user_id,
            'payout_id'  => $payout_id,
            'type'       => 'debit',
            'amount'     => $amount,
            'reason'     => 'withdrawal_request',
            'created_at' => current_time( 'mysql' ),
        ) );

        return $payout_id;
    }

    /**
     * Validate RUT
     */
    public static function validate_rut( $rut ) {
        // Simple RUT validation (remove dots and hyphens)
        $rut = str_replace( array( '.', '-' ), '', $rut );
        
        if ( strlen( $rut ) < 8 ) {
            return false;
        }

        $body = substr( $rut, 0, -1 );
        $dv = substr( $rut, -1 );
        $sum = 0;
        $multiplier = 2;

        for ( $i = strlen( $body ) - 1; $i >= 0; $i-- ) {
            $sum += intval( $body[ $i ] ) * $multiplier;
            $multiplier++;
            if ( $multiplier > 7 ) {
                $multiplier = 2;
            }
        }

        $calculated_dv = 11 - ( $sum % 11 );
        if ( $calculated_dv === 11 ) {
            $calculated_dv = 0;
        } elseif ( $calculated_dv === 10 ) {
            $calculated_dv = 'K';
        }

        return strtoupper( $dv ) === strval( $calculated_dv );
    }
}
