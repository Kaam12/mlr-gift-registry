<?php
/**
 * MLR Payouts Class
 * Manages payout processing and withdrawal requests
 * 
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MLR_Payouts {
    /**
     * Minimum withdrawal amount in CLP
     */
    const MIN_WITHDRAWAL = 5000;

    /**
     * Processing fee percentage
     */
    const PROCESSING_FEE = 0.02; // 2%

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'mlr_process_pending_payouts', array( $this, 'process_pending_payouts' ) );
    }

    /**
     * Create payout request
     */
    public function create_payout_request( $user_id, $amount, $bank_account_id = null ) {
        if ( $amount < self::MIN_WITHDRAWAL ) {
            return new \WP_Error( 'minimum_amount', sprintf(
                'Minimum withdrawal amount is CLP %d',
                self::MIN_WITHDRAWAL
            ) );
        }

        // Validate user has sufficient balance
        $user = new MLR_User( $user_id );
        $balance = $user->get_total_earnings();

        if ( $amount > $balance ) {
            return new \WP_Error( 'insufficient_balance', 'Insufficient balance for withdrawal' );
        }

        // Get bank account
        $bank_account = get_user_meta( $user_id, '_mlr_bank_account', true );
        if ( ! $bank_account ) {
            return new \WP_Error( 'no_bank_account', 'Please set up a bank account first' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mlr_payouts';

        // Calculate fee
        $fee = $amount * self::PROCESSING_FEE;
        $net_amount = $amount - $fee;

        // Create payout record
        $result = $wpdb->insert( $table, array(
            'user_id'      => $user_id,
            'amount'       => $amount,
            'fee'          => $fee,
            'net_amount'   => $net_amount,
            'status'       => 'pending',
            'bank_account' => $bank_account,
            'created_at'   => current_time( 'mysql' ),
        ) );

        if ( ! $result ) {
            return new \WP_Error( 'payout_creation_failed', 'Failed to create payout request' );
        }

        $payout_id = $wpdb->insert_id;

        // Log in ledger
        $ledger = new MLR_Ledger();
        $ledger->record_transaction( $user_id, 'debit', $amount, 'payout_request', array(
            'payout_id' => $payout_id,
        ) );

        // Send notification
        do_action( 'mlr_payout_requested', $payout_id, $user_id, $amount );

        return $payout_id;
    }

    /**
     * Get payout details
     */
    public function get_payout( $payout_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'mlr_payouts';

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $payout_id
        ), ARRAY_A );
    }

    /**
     * Update payout status
     */
    public function update_payout_status( $payout_id, $status, $transaction_id = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'mlr_payouts';

        $update = array(
            'status'          => $status,
            'updated_at'      => current_time( 'mysql' ),
        );

        if ( $transaction_id ) {
            $update['transaction_id'] = $transaction_id;
        }

        if ( $status === 'completed' ) {
            $update['completed_at'] = current_time( 'mysql' );
        }

        $wpdb->update(
            $table,
            $update,
            array( 'id' => $payout_id )
        );

        // Get payout to log it
        $payout = $this->get_payout( $payout_id );
        if ( $payout ) {
            do_action( 'mlr_payout_status_changed', $payout_id, $payout['user_id'], $status );
        }

        return true;
    }

    /**
     * Get pending payouts
     */
    public function get_pending_payouts() {
        global $wpdb;
        $table = $wpdb->prefix . 'mlr_payouts';

        return $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY created_at ASC",
            ARRAY_A
        );
    }

    /**
     * Process pending payouts batch
     */
    public function process_pending_payouts() {
        $pending = $this->get_pending_payouts();

        foreach ( $pending as $payout ) {
            $this->process_single_payout( $payout );
        }
    }

    /**
     * Process single payout
     */
    private function process_single_payout( $payout ) {
        // This would integrate with bank transfer APIs
        // For now, we'll mark as processing

        $this->update_payout_status( $payout['id'], 'processing' );

        // In production, call bank transfer service
        // Example: ACH transfer, SPEI, etc.
        
        // Simulate processing
        do_action( 'mlr_payout_processing', $payout['id'], $payout['user_id'] );
    }

    /**
     * Get payout history for user
     */
    public function get_user_payout_history( $user_id, $limit = 50 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'mlr_payouts';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A );
    }

    /**
     * Cancel payout request
     */
    public function cancel_payout( $payout_id ) {
        $payout = $this->get_payout( $payout_id );

        if ( ! $payout ) {
            return new \WP_Error( 'payout_not_found', 'Payout not found' );
        }

        if ( $payout['status'] !== 'pending' ) {
            return new \WP_Error( 'cannot_cancel', 'Only pending payouts can be cancelled' );
        }

        // Update status
        $this->update_payout_status( $payout_id, 'cancelled' );

        // Reverse ledger entries
        $ledger = new MLR_Ledger();
        $ledger->record_transaction( $payout['user_id'], 'credit', $payout['amount'], 'payout_cancelled', array(
            'payout_id' => $payout_id,
        ) );

        do_action( 'mlr_payout_cancelled', $payout_id, $payout['user_id'] );

        return true;
    }

    /**
     * Get payout statistics
     */
    public function get_statistics( $start_date = null, $end_date = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'mlr_payouts';

        $where = "1=1";
        $params = array();

        if ( $start_date ) {
            $where .= " AND created_at >= %s";
            $params[] = $start_date;
        }
        if ( $end_date ) {
            $where .= " AND created_at <= %s";
            $params[] = $end_date;
        }

        $query = $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_payouts,
                SUM(amount) as total_amount,
                SUM(fee) as total_fees,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount
            FROM {$table}
            WHERE {$where}",
            $params
        );

        return $wpdb->get_row( $query, ARRAY_A );
    }
}
