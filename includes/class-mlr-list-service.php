<?php
/**
 * MLR List Service Class
 *
 * Handles CRUD operations for gift lists
 *
 * @package MLR_Gift_Registry
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MLR_List_Service {

	/**
	 * Create or update a gift list
	 *
	 * @param array $args Gift list arguments
	 * @return int|WP_Error Post ID or error
	 */
	public static function create_or_update_list( $args ) {
		$defaults = array(
			'post_type'    => 'gift_list',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
		);

		$args = wp_parse_args( $args, $defaults );
		return wp_insert_post( $args );
	}

	/**
	 * Get a gift list with metadata
	 *
	 * @param int $list_id Gift list ID
	 * @return array|null Gift list data or null
	 */
	public static function get_list( $list_id ) {
		$post = get_post( $list_id );

		if ( ! $post || 'gift_list' !== $post->post_type ) {
			return null;
		}

		return array(
			'id'             => $post->ID,
			'title'          => $post->post_title,
			'slug'           => $post->post_name,
			'event_type'     => wp_get_post_terms( $post->ID, 'event_type', array( 'fields' => 'names' ) ),
			'description'    => $post->post_content,
			'author_id'      => $post->post_author,
			'created_at'     => $post->post_date,
			'updated_at'     => $post->post_modified,
			'total_donated'  => self::get_total_donated( $post->ID ),
			'total_gifts'    => self::get_total_gifts( $post->ID ),
		);
	}

	/**
	 * Get all lists for a user
	 *
	 * @param int $user_id User ID
	 * @return array Gift lists
	 */
	public static function get_user_lists( $user_id ) {
		$args = array(
			'post_type'      => 'gift_list',
			'post_status'    => 'publish',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$posts = get_posts( $args );
		$lists = array();

		foreach ( $posts as $post ) {
			$lists[] = self::get_list( $post->ID );
		}

		return $lists;
	}

	/**
	 * Get total money donated to a list
	 *
	 * @param int $list_id Gift list ID
	 * @return float Total donated
	 */
	public static function get_total_donated( $list_id ) {
		$total = (float) get_post_meta( $list_id, '_mlr_total_donated', true );
		return $total ? $total : 0;
	}

	/**
	 * Get total number of gifts in a list
	 *
	 * @param int $list_id Gift list ID
	 * @return int Total gifts
	 */
	public static function get_total_gifts( $list_id ) {
		$gifts = get_post_meta( $list_id, '_mlr_gifts', true );
		return is_array( $gifts ) ? count( $gifts ) : 0;
	}

	/**
	 * Add a gift to a list
	 *
	 * @param int   $list_id Gift list ID
	 * @param array $gift Gift data
	 * @return array|WP_Error Updated gift with ID
	 */
	public static function add_gift( $list_id, $gift ) {
		$gifts = get_post_meta( $list_id, '_mlr_gifts', true );
		if ( ! is_array( $gifts ) ) {
			$gifts = array();
		}

		$gift_id = uniqid( 'gift_', true );
		$gift['id'] = $gift_id;
		$gift['created_at'] = current_time( 'mysql' );
		$gift['donated_amount'] = isset( $gift['donated_amount'] ) ? (float) $gift['donated_amount'] : 0;

		$gifts[ $gift_id ] = $gift;
		update_post_meta( $list_id, '_mlr_gifts', $gifts );

		return $gift;
	}

	/**
	 * Update list donation tracking
	 *
	 * @param int   $list_id Gift list ID
	 * @param float $amount Donation amount
	 * @return bool Success
	 */
	public static function record_donation( $list_id, $amount ) {
		$total = self::get_total_donated( $list_id );
		$new_total = $total + (float) $amount;
		return update_post_meta( $list_id, '_mlr_total_donated', $new_total );
	}

	/**
	 * Delete a gift list
	 *
	 * @param int $list_id Gift list ID
	 * @return bool Success
	 */
	public static function delete_list( $list_id ) {
		return wp_delete_post( $list_id, true );
	}
}
