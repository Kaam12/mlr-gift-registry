<?php
/**
 * MLR Utilities Class
 *
 * Handles common utility functions: RUT validation (Chilean),
 * QR code generation, URL slugs, currency formatting, etc.
 *
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MLR_Utilities {

	/**
	 * Validate Chilean RUT with module 11 algorithm
	 *
	 * @param string $rut RUT to validate (format: 12345678-K or 12345678K)
	 * @return bool True if valid, false otherwise
	 */
	public static function is_valid_rut( $rut ) {
		// Clean RUT: remove spaces and hyphens
		$rut = preg_replace( '/[\s.-]/', '', trim( $rut ) );

		// Must be minimum 8 characters
		if ( strlen( $rut ) < 8 ) {
			return false;
		}

		// Split RUT number and verification digit
		$number = substr( $rut, 0, -1 );
		$digit = strtoupper( substr( $rut, -1 ) );

		// Number must be numeric
		if ( ! is_numeric( $number ) ) {
			return false;
		}

		// Calculate verification digit using modulo 11
		$s = 0;
		$m = 2;
		$number = strrev( $number );

		for ( $i = 0; $i < strlen( $number ); $i++ ) {
			$s += intval( $number[ $i ] ) * $m;
			$m++;
			if ( $m > 7 ) {
				$m = 2;
			}
		}

		$remainder = 11 - ( $s % 11 );

		if ( $remainder === 11 ) {
			$verification_digit = '0';
		} elseif ( $remainder === 10 ) {
			$verification_digit = 'K';
		} else {
			$verification_digit = strval( $remainder );
		}

		return $digit === $verification_digit;
	}

	/**
	 * Format RUT for display (12345678-K format)
	 *
	 * @param string $rut Raw RUT
	 * @return string Formatted RUT
	 */
	public static function format_rut( $rut ) {
		$rut = preg_replace( '/[\s.-]/', '', trim( $rut ) );
		$number = substr( $rut, 0, -1 );
		$digit = substr( $rut, -1 );

		return number_format( intval( $number ), 0, '', '.' ) . '-' . strtoupper( $digit );
	}

	/**
	 * Generate QR code as SVG
	 *
	 * Uses qrserver.com API for QR generation (no dependencies)
	 *
	 * @param string $data URL or text to encode
	 * @param int    $size QR code size (200-500)
	 * @return string QR code SVG HTML
	 */
	public static function generate_qr_code( $data, $size = 300 ) {
		$size = intval( $size );
		$size = min( max( $size, 200 ), 500 ); // Limit to reasonable sizes

		$encoded_data = urlencode( $data );
		$url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded_data}";

		return sprintf(
			'<img src="%s" alt="QR Code" class="mlr-qr-code" width="%d" height="%d">',
			esc_url( $url ),
			$size,
			$size
		);
	}

	/**
	 * Generate public URL slug for a gift list
	 *
	 * Format: site.com/lista/juan-y-maria-2024
	 *
	 * @param int    $list_id Gift list ID
	 * @param string $list_name List name (celebrant name)
	 * @return string Public URL slug
	 */
	public static function get_list_public_url( $list_id, $list_name = '' ) {
		$slug = self::create_url_slug( $list_name );
		if ( empty( $slug ) ) {
			$slug = 'lista-' . $list_id;
		}

		return home_url( "/lista/{$slug}/" );
	}

	/**
	 * Generate secure share link with token
	 *
	 * @param int $list_id Gift list ID
	 * @return string Secure share URL with token
	 */
	public static function generate_share_token( $list_id ) {
		// Generate random token
		$token = wp_generate_password( 24, false );

		// Store token in list metadata
		update_post_meta( $list_id, '_mlr_share_token', $token );
		update_post_meta( $list_id, '_mlr_share_token_created', current_time( 'mysql' ) );

		$url = home_url( "/enviar/?id={$list_id}&token={$token}" );
		return $url;
	}

	/**
	 * Create URL-friendly slug from text
	 *
	 * @param string $text Text to convert
	 * @return string URL slug
	 */
	public static function create_url_slug( $text ) {
		$text = sanitize_title_with_hyphens( $text );
		return strtolower( preg_replace( '/[^a-z0-9-]/', '', $text ) );
	}

	/**
	 * Format amount in Chilean CLP
	 *
	 * @param float $amount Amount to format
	 * @param bool  $currency_symbol Include $ symbol
	 * @return string Formatted amount
	 */
	public static function format_currency( $amount, $currency_symbol = true ) {
		$formatted = number_format( floatval( $amount ), 0, '', '.' );

		if ( $currency_symbol ) {
			$formatted = '\$' . $formatted . ' CLP';
		}

		return $formatted;
	}

	/**
	 * Parse currency string to float
	 *
	 * @param string $amount Currency string (e.g., "$100.000 CLP")
	 * @return float Numeric amount
	 */
	public static function parse_currency( $amount ) {
		$amount = preg_replace( '/[^0-9.-]/', '', $amount );
		return floatval( $amount );
	}

	/**
	 * Calculate percentage of amount
	 *
	 * @param float $amount Base amount
	 * @param float $percentage Percentage (0-100)
	 * @return float Calculated percentage amount
	 */
	public static function calculate_percentage( $amount, $percentage ) {
		return floatval( $amount ) * floatval( $percentage ) / 100;
	}

	/**
	 * Get Chilean bank list for select dropdown
	 *
	 * @return array Bank list
	 */
	public static function get_chilean_banks() {
		return array(
			'01' => 'Banco de Chile',
			'02' => 'Banco Central de Chile',
			'03' => 'Banco del Estado de Chile',
			'04' => 'Banco de Crédito e Inversiones',
			'05' => 'Banco Falabella',
			'06' => 'Banco Internacional',
			'07' => 'Banco Itaú',
			'08' => 'Banco Santander',
			'09' => 'Banco Asiático',
			'10' => 'Banco Bbva',
			'11' => 'Banco Security',
			'12' => 'Banco Corpbanca',
			'13' => 'Scotiabank',
			'14' => 'Rabobank',
			'15' => 'Banco Monex',
			'16' => 'Banco Actinver',
			'17' => 'Banco Ve por Más',
			'18' => 'Banco Finterra',
		);
	}

	/**
	 * Get account type list
	 *
	 * @return array Account types
	 */
	public static function get_account_types() {
		return array(
			'Cuenta Corriente' => __( 'Cuenta Corriente', 'mlr-gift-registry' ),
			'Cuenta de Ahorro' => __( 'Cuenta de Ahorro', 'mlr-gift-registry' ),
			'Cuenta RUT' => __( 'Cuenta RUT', 'mlr-gift-registry' ),
		);
	}

	/**
	 * Check if account number is valid (simple format check)
	 *
	 * @param string $account_number Account number
	 * @return bool Valid format or not
	 */
	public static function is_valid_account_number( $account_number ) {
		$account_number = preg_replace( '/[^0-9-]/', '', $account_number );
		return strlen( $account_number ) >= 8 && strlen( $account_number ) <= 20;
	}

	/**
	 * Get event types available
	 *
	 * @return array Event types
	 */
	public static function get_event_types() {
		return array(
			'wedding'       => __( 'Boda', 'mlr-gift-registry' ),
			'anniversary'   => __( 'Aniversario', 'mlr-gift-registry' ),
			'birthday'      => __( 'Cumpleaños', 'mlr-gift-registry' ),
			'babyshower'    => __( 'Baby Shower', 'mlr-gift-registry' ),
			'baptism'       => __( 'Bautismo', 'mlr-gift-registry' ),
			'goldwedding'   => __( 'Bodas de Oro', 'mlr-gift-registry' ),
			'celebration'   => __( 'Celebración', 'mlr-gift-registry' ),
			'collection'    => __( 'Colecta', 'mlr-gift-registry' ),
			'shower'        => __( 'Departamento Shower', 'mlr-gift-registry' ),
		);
	}

	/**
	 * Sanitize and validate email
	 *
	 * @param string $email Email to validate
	 * @return string|false Sanitized email or false
	 */
	public static function validate_email( $email ) {
		$email = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			return false;
		}

		return $email;
	}

	/**
	 * Sanitize phone number (Chilean format)
	 *
	 * @param string $phone Phone number
	 * @return string Sanitized phone
	 */
	public static function sanitize_phone( $phone ) {
		$phone = preg_replace( '/[^0-9+]/', '', $phone );
		return trim( $phone );
	}

	/**
	 * Generate random gift code for tracking
	 *
	 * @return string Random code (12 chars)
	 */
	public static function generate_gift_code() {
		return strtoupper( wp_generate_password( 12, false ) );
	}
}
