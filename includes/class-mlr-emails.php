<?php
/**
 * MLR Email Notifications
 * Handles email notifications for transactions and payouts
 * 
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MLR_Emails {
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'mlr_payout_requested', array( $this, 'send_payout_request_email' ), 10, 3 );
        add_action( 'mlr_payout_completed', array( $this, 'send_payout_completed_email' ), 10, 2 );
        add_action( 'mlr_list_created', array( $this, 'send_list_created_email' ), 10, 2 );
        add_action( 'mlr_contribution_received', array( $this, 'send_contribution_email' ), 10, 3 );
    }

    /**
     * Send payout request email
     */
    public function send_payout_request_email( $payout_id, $user_id, $amount ) {
        if ( ! get_option( 'mlr_email_notifications' ) ) {
            return;
        }

        $user = get_user_by( 'ID', $user_id );
        if ( ! $user ) {
            return;
        }

        $subject = sprintf( __( 'Payout Request Received - CLP %d', 'mlr-gift-registry' ), intval( $amount ) );
        $message = $this->get_payout_request_template( $user->display_name, $amount, $payout_id );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $user->user_email, $subject, $message, $headers );
    }

    /**
     * Send payout completed email
     */
    public function send_payout_completed_email( $payout_id, $user_id ) {
        if ( ! get_option( 'mlr_email_notifications' ) ) {
            return;
        }

        global $wpdb;
        $payout = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mlr_payouts WHERE id = %d",
            $payout_id
        ) );

        if ( ! $payout ) {
            return;
        }

        $user = get_user_by( 'ID', $user_id );
        if ( ! $user ) {
            return;
        }

        $subject = sprintf( __( 'Payout Completed - CLP %d', 'mlr-gift-registry' ), intval( $payout->net_amount ) );
        $message = $this->get_payout_completed_template( $user->display_name, $payout->net_amount, $payout->transaction_id );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $user->user_email, $subject, $message, $headers );
    }

    /**
     * Send list created email
     */
    public function send_list_created_email( $list_id, $user_id ) {
        if ( ! get_option( 'mlr_email_notifications' ) ) {
            return;
        }

        $list = get_post( $list_id );
        $user = get_user_by( 'ID', $user_id );

        if ( ! $list || ! $user ) {
            return;
        }

        $subject = sprintf( __( 'Gift List Created: %s', 'mlr-gift-registry' ), $list->post_title );
        $message = $this->get_list_created_template( $user->display_name, $list->post_title, get_permalink( $list_id ) );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $user->user_email, $subject, $message, $headers );
    }

    /**
     * Send contribution received email
     */
    public function send_contribution_email( $contribution_id, $list_id, $amount ) {
        if ( ! get_option( 'mlr_email_notifications' ) ) {
            return;
        }

        $list = get_post( $list_id );
        if ( ! $list ) {
            return;
        }

        $user = get_user_by( 'ID', $list->post_author );
        if ( ! $user ) {
            return;
        }

        $subject = sprintf( __( 'New Contribution to Your List: %s', 'mlr-gift-registry' ), $list->post_title );
        $message = $this->get_contribution_template( $user->display_name, $list->post_title, $amount );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $user->user_email, $subject, $message, $headers );
    }

    /**
     * Get payout request email template
     */
    private function get_payout_request_template( $user_name, $amount, $payout_id ) {
        $template = "<html><body style='font-family: Arial, sans-serif; color: #333;'>
            <h2>Hola {$user_name},</h2>
            <p>Hemos recibido tu solicitud de retiro de fondos.</p>
            <div style='background-color: #f5f5f5; padding: 15px; border-radius: 5px;'>
                <p><strong>Monto solicitado:</strong> CLP " . number_format( $amount, 0 ) . "</p>
                <p><strong>ID de Pago:</strong> {$payout_id}</p>
                <p><strong>Estado:</strong> Pendiente de procesamiento</p>
            </div>
            <p>Tu solicitud está en nuestro sistema y será procesada en las próximas 24 horas.</p>
            <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
            <p>Saludos,<br/>El equipo de Mi Lista de Regalos</p>
        </body></html>";
        return $template;
    }

    /**
     * Get payout completed email template
     */
    private function get_payout_completed_template( $user_name, $amount, $transaction_id ) {
        $template = "<html><body style='font-family: Arial, sans-serif; color: #333;'>
            <h2>¡Hola {$user_name}!</h2>
            <p>Tu retiro de fondos ha sido procesado exitosamente.</p>
            <div style='background-color: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>
                <p><strong>Monto transferido:</strong> CLP " . number_format( $amount, 0 ) . "</p>
                <p><strong>ID de Transacción:</strong> {$transaction_id}</p>
                <p><strong>Fecha:</strong> " . date( 'd/m/Y H:i', current_time( 'timestamp' ) ) . "</p>
            </div>
            <p>El dinero ha sido transferido a tu cuenta bancaria. Puede tomar 1-3 días hábiles en aparecer según tu banco.</p>
            <p>Saludos,<br/>El equipo de Mi Lista de Regalos</p>
        </body></html>";
        return $template;
    }

    /**
     * Get list created email template
     */
    private function get_list_created_template( $user_name, $list_title, $list_url ) {
        $template = "<html><body style='font-family: Arial, sans-serif; color: #333;'>
            <h2>¡Hola {$user_name}!</h2>
            <p>Tu lista de regalos '{$list_title}' ha sido creada exitosamente.</p>
            <p><a href='{$list_url}' style='display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ver mi lista</a></p>
            <p>Ahora puedes compartir el enlace de tu lista con tus amigos y familiares para que contribuyan a los regalos que deseas.</p>
            <p>Saludos,<br/>El equipo de Mi Lista de Regalos</p>
        </body></html>";
        return $template;
    }

    /**
     * Get contribution received email template
     */
    private function get_contribution_template( $user_name, $list_title, $amount ) {
        $template = "<html><body style='font-family: Arial, sans-serif; color: #333;'>
            <h2>¡Excelente {$user_name}!</h2>
            <p>Alguien acaba de contribuir a tu lista '{$list_title}'.</p>
            <div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px;'>
                <p><strong>Monto contribuido:</strong> CLP " . number_format( $amount, 0 ) . "</p>
            </div>
            <p>¡Gracias a los generosos aportes de tus amigos! Tu lista está creciendo.</p>
            <p>Saludos,<br/>El equipo de Mi Lista de Regalos</p>
        </body></html>";
        return $template;
    }
}
