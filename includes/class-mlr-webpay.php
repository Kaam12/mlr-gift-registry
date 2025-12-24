<?php
/**
 * MLR Webpay Integration
 * Handles Webpay payment gateway integration for Chilean transactions
 * 
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MLR_Webpay {
    /**
     * Webpay API endpoint
     */
    private $api_url = 'https://webpay3g.transbank.cl/webpayserver/initTransaction';
    private $commerce_code;
    private $api_key;
    private $environment; // 'sandbox' or 'production'

    /**
     * Constructor
     */
    public function __construct() {
        $this->commerce_code = get_option( 'mlr_webpay_commerce_code' );
        $this->api_key = get_option( 'mlr_webpay_api_key' );
        $this->environment = get_option( 'mlr_webpay_environment', 'sandbox' );
    }

    /**
     * Initialize Webpay transaction
     */
    public function initiate_transaction( $order_id, $amount, $return_url ) {
        if ( ! $this->commerce_code || ! $this->api_key ) {
            return new \WP_Error( 'missing_config', 'Webpay configuration missing' );
        }

        $session_id = 'sess_' . $order_id . '_' . time();
        $data = array(
            'Tid'           => $this->commerce_code,
            'Amount'        => intval( $amount * 100 ), // Convert to centavos
            'OrderId'       => $order_id,
            'SessionId'     => $session_id,
            'ReturnURL'     => $return_url,
            'CommerceCode'  => $this->commerce_code,
        );

        try {
            $response = $this->send_request( $this->api_url, $data );
            if ( isset( $response['token'] ) ) {
                // Store transaction token
                update_post_meta( $order_id, '_webpay_token', $response['token'] );
                return $response;
            } else {
                return new \WP_Error( 'transaction_failed', 'Failed to initiate Webpay transaction' );
            }
        } catch ( \Exception $e ) {
            return new \WP_Error( 'webpay_error', $e->getMessage() );
        }
    }

    /**
     * Validate payment callback
     */
    public function validate_payment( $token, $order_id ) {
        $validation_url = 'https://webpay3g.transbank.cl/webpayserver/validateTransaction';

        $data = array(
            'Tid'   => $this->commerce_code,
            'Token' => $token,
        );

        try {
            $response = $this->send_request( $validation_url, $data );

            if ( isset( $response['responseCode'] ) ) {
                if ( $response['responseCode'] == 0 ) {
                    // Payment successful
                    return array(
                        'status'        => 'approved',
                        'transaction_id' => $response['transactionId'] ?? null,
                        'amount'        => ( $response['amount'] ?? 0 ) / 100, // Convert from centavos
                        'auth_code'     => $response['authorizationCode'] ?? null,
                        'card_number'   => $response['cardNumber'] ?? null,
                    );
                } else {
                    return array(
                        'status'  => 'rejected',
                        'code'    => $response['responseCode'],
                        'message' => $this->get_response_message( $response['responseCode'] ),
                    );
                }
            }

            return new \WP_Error( 'validation_failed', 'Invalid payment validation response' );
        } catch ( \Exception $e ) {
            return new \WP_Error( 'validation_error', $e->getMessage() );
        }
    }

    /**
     * Refund transaction
     */
    public function refund_transaction( $token, $amount ) {
        $refund_url = 'https://webpay3g.transbank.cl/webpayserver/nullify';

        $data = array(
            'Tid'           => $this->commerce_code,
            'Token'         => $token,
            'NullifyAmount' => intval( $amount * 100 ),
        );

        try {
            $response = $this->send_request( $refund_url, $data );
            if ( isset( $response['responseCode'] ) && $response['responseCode'] == 0 ) {
                return true;
            }
            return false;
        } catch ( \Exception $e ) {
            return new \WP_Error( 'refund_error', $e->getMessage() );
        }
    }

    /**
     * Send SOAP request to Webpay
     */
    private function send_request( $url, $data ) {
        // This would use SOAP or REST calls to Webpay
        // For now, returning mock response structure
        // In production, use actual Webpay SDK or SOAP client
        
        // Example using WP_Remote_Post
        $response = wp_remote_post( $url, array(
            'method'      => 'POST',
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.1',
            'sslverify'   => false,
            'blocking'    => true,
            'headers'     => array( 'Content-Type' => 'application/json' ),
            'body'        => wp_json_encode( $data ),
        ) );

        if ( is_wp_error( $response ) ) {
            throw new \Exception( 'API request failed: ' . $response->get_error_message() );
        }

        $body = wp_remote_retrieve_body( $response );
        return json_decode( $body, true );
    }

    /**
     * Get response message for error codes
     */
    private function get_response_message( $code ) {
        $messages = array(
            '-1' => 'Transacción rechazada',
            '-2' => 'Transacción debe reintentarse',
            '-3' => 'Error en transacción',
            '-4' => 'Rechazo general',
            '-5' => 'Rechazo - Posible reintentar',
            '-6' => 'Rechazo definitivo',
            '-7' => 'Pago denegado',
            '-8' => 'Transacción cancelada',
        );

        return $messages[ $code ] ?? 'Error desconocido';
    }

    /**
     * Get saved payment methods for user
     */
    public function get_user_saved_cards( $user_id ) {
        $cards = get_user_meta( $user_id, '_webpay_saved_cards', false );
        return is_array( $cards ) ? $cards : array();
    }

    /**
     * Save payment method
     */
    public function save_payment_method( $user_id, $card_data ) {
        $card = array(
            'id'          => 'card_' . wp_generate_uuid4(),
            'last_four'   => substr( $card_data['number'], -4 ),
            'brand'       => $card_data['brand'] ?? 'visa',
            'saved_at'    => current_time( 'mysql' ),
        );

        add_user_meta( $user_id, '_webpay_saved_cards', $card );
        return $card;
    }
}
