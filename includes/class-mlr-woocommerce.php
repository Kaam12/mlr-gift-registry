<?php
/**
 * MLR WooCommerce Integration Class
 *
 * Handles all WooCommerce-specific functionality including
 * fee calculations, order processing, and payment method integration
 *
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MLR_WooCommerce {

	/**
	 * Fee percentage for service (invitado pays this)
	 */
	const SERVICE_FEE_PERCENTAGE = 10;

	/**
	 * Initialize WooCommerce integration
	 */
	public static function init() {
		// Add service fee to cart
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_service_fee' ) );

		// Process order after payment
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'process_gift_registry_order' ), 10, 1 );

		// Update order metadata on creation
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_gift_metadata_to_order_item' ), 10, 4 );

		// Save additional order meta
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_gift_registry_order_meta' ), 10, 2 );

		// Make gift registry products virtual by default
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'register_product_settings_tabs' ) );
	}

	/**
	 * Add service fee (10%) to cart for guests
	 *
	 * This is the key functionality that separates payment into two components:
	 * - Amount to host (full gift price)
	 * - Amount to platform (10% service fee)
	 */
	public static function add_service_fee() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( WC()->cart->is_empty() ) {
			return;
		}

		$cart_total = WC()->cart->get_subtotal();
		$fee_amount = ( $cart_total * self::SERVICE_FEE_PERCENTAGE ) / 100;

		if ( $fee_amount > 0 ) {
			WC()->cart->add_fee(
				__( 'Service Fee (10%)', 'mlr-gift-registry' ),
				$fee_amount
			);
		}
	}

	/**
	 * Get breakdown of order amounts
	 *
	 * @param WC_Order $order WooCommerce order object
	 * @return array Breakdown with host and platform amounts
	 */
	public static function get_order_breakdown( $order ) {
		$subtotal = 0;
		$fee = 0;

		// Calculate subtotal from line items
		foreach ( $order->get_items() as $item ) {
			$subtotal += $item->get_total();
		}

		// Calculate fee from fees
		foreach ( $order->get_fees() as $fee_item ) {
			$fee += abs( $fee_item->get_total() );
		}

		return array(
			'host_amount'     => floatval( $subtotal ),
			'platform_fee'    => floatval( $fee ),
			'total_paid'      => floatval( $subtotal + $fee ),
			'fee_percentage'  => self::SERVICE_FEE_PERCENTAGE,
		);
	}

	/**
	 * Add gift metadata to order items
	 *
	 * @param WC_Order_Item $item Order item
	 * @param string         $cart_item_key Cart item key
	 * @param array          $values Cart item values
	 * @param WC_Order       $order WooCommerce order
	 */
	public static function add_gift_metadata_to_order_item( $item, $cart_item_key, $values, $order ) {
		// Store cart metadata for tracking
		if ( isset( $values['mlr_gift_id'] ) ) {
			$item->add_meta_data( '_mlr_gift_id', sanitize_text_field( $values['mlr_gift_id'] ) );
		}

		if ( isset( $values['mlr_list_id'] ) ) {
			$item->add_meta_data( '_mlr_list_id', intval( $values['mlr_list_id'] ) );
		}
	}

	/**
	 * Save gift registry specific order metadata
	 *
	 * @param WC_Order $order WooCommerce order
	 * @param array    $data  Checkout data
	 */
	public static function save_gift_registry_order_meta( $order, $data ) {
		// Store breakdown for accounting
		$breakdown = self::get_order_breakdown( $order );
		$order->update_meta_data( '_mlr_host_amount', $breakdown['host_amount'] );
		$order->update_meta_data( '_mlr_platform_fee', $breakdown['platform_fee'] );
		$order->update_meta_data( '_mlr_processed', 'no' );

		// Store guest dedication message if provided
		if ( isset( $_POST['mlr_gift_message'] ) ) {
			$message = sanitize_textarea_field( $_POST['mlr_gift_message'] );
			$order->update_meta_data( '_mlr_guest_message', $message );
		}
	}

	/**
	 * Process order on successful payment
	 *
	 * This updates the gift registry list with the donation amount
	 *
	 * @param int $order_id WooCommerce order ID
	 */
	public static function process_gift_registry_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Check if already processed
		if ( 'yes' === $order->get_meta( '_mlr_processed' ) ) {
			return;
		}

		// Only process completed/paid orders
		if ( ! in_array( $order->get_status(), array( 'completed', 'processing' ), true ) ) {
			return;
		}

		// Get order items and process each gift
		foreach ( $order->get_items() as $item ) {
			$list_id = $item->get_meta( '_mlr_list_id' );

			if ( $list_id ) {
				$breakdown = self::get_order_breakdown( $order );

				// Record donation to the list
				MLR_List_Service::record_donation( $list_id, $breakdown['host_amount'] );
			}
		}

		// Mark as processed
		$order->update_meta_data( '_mlr_processed', 'yes' );
		$order->save();
	}

	/**
	 * Register custom product settings tabs
	 *
	 * @param array $tabs Product tabs
	 * @return array Modified tabs
	 */
	public static function register_product_settings_tabs( $tabs ) {
		// Products in gift registry should always be virtual
		return $tabs;
	}

	/**
	 * Create gift registry product
	 *
	 * Creates a virtual WooCommerce product from a gift list item
	 *
	 * @param int    $list_id Gift list ID
	 * @param array  $gift Gift data
	 * @return int|WP_Error Product ID or error
	 */
	public static function create_gift_product( $list_id, $gift ) {
		$product = new WC_Product_Simple();

		// Set product data
		$product->set_name( sanitize_text_field( $gift['name'] ) );
		$product->set_description( isset( $gift['description'] ) ? sanitize_textarea_field( $gift['description'] ) : '' );
		$product->set_price( floatval( $gift['price'] ) );
		$product->set_regular_price( floatval( $gift['price'] ) );

		// Make virtual - no shipping needed
		$product->set_virtual( true );
		$product->set_downloadable( false );

		// Set as internal product (not for direct sale)
		$product->set_status( 'draft' ); // Keep draft until published

		// Add metadata for tracking
		$product->add_meta_data( '_mlr_list_id', $list_id );
		$product->add_meta_data( '_mlr_gift_id', $gift['id'] );
		$product->add_meta_data( '_mlr_gift', 'yes' );

		// Save product
		$product_id = $product->save();

		return $product_id;
	}

	/**
	 * Get fee percentage
	 *
	 * @return int Service fee percentage
	 */
	public static function get_fee_percentage() {
		return self::SERVICE_FEE_PERCENTAGE;
	}

	/**
	 * Calculate total with fee
	 *
	 * @param float $amount Base amount
	 * @return float Amount with fee added
	 */
	public static function calculate_with_fee( $amount ) {
		return floatval( $amount ) + ( floatval( $amount ) * self::SERVICE_FEE_PERCENTAGE / 100 );
	}
}
