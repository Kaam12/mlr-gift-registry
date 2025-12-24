<?php
/**
 * MLR Gift Registry - Helper Functions
 *
 * Collection of utility functions for common operations throughout
 * the plugin including formatting, validation, and data manipulation.
 *
 * @package MLR_Gift_Registry
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Format currency amount for display
 *
 * @param float  $amount The amount to format.
 * @param string $currency Optional currency code (default: CLP).
 * @return string Formatted currency string
 */
function mlr_format_currency( $amount, $currency = 'CLP' ) {
    if ( ! is_numeric( $amount ) ) {
        $amount = 0;
    }
    return apply_filters( 'mlr_format_currency', number_format( $amount, 0, ',', '.' ) . ' ' . $currency, $amount, $currency );
}

/**
 * Get user profile data
 *
 * @param int $user_id User ID.
 * @return array User profile array
 */
function mlr_get_user_profile( $user_id ) {
    $user = get_user_by( 'ID', absint( $user_id ) );
    if ( ! $user ) {
        return array();
    }
    return array(
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'avatar' => get_avatar_url( $user->ID ),
    );
}

/**
 * Check if user is gift list owner
 *
 * @param int $list_id List ID.
 * @param int $user_id User ID.
 * @return bool True if user owns the list
 */
function mlr_user_owns_list( $list_id, $user_id ) {
    global $wpdb;
    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT owner_id FROM {table_name} WHERE id = %d AND owner_id = %d",
            absint( $list_id ),
            absint( $user_id )
        )
    );
    return ! empty( $result );
}

/**
 * Sanitize and validate list name
 *
 * @param string $name The list name to validate.
 * @return string|false Sanitized name or false if invalid
 */
function mlr_validate_list_name( $name ) {
    $name = sanitize_text_field( $name );
    $length = strlen( $name );
    if ( $length < MLR_MIN_LIST_NAME_LENGTH || $length > MLR_MAX_LIST_NAME_LENGTH ) {
        return false;
    }
    return $name;
}

/**
 * Get list statistics
 *
 * @param int $list_id List ID.
 * @return array Statistics array
 */
function mlr_get_list_statistics( $list_id ) {
    global $wpdb;
    $list_id = absint( $list_id );
    
    $stats = array(
        'total_items' => 0,
        'contributed_items' => 0,
        'total_raised' => 0,
    );
    
    return apply_filters( 'mlr_get_list_statistics', $stats, $list_id );
}

/**
 * Log plugin activity
 *
 * @param string $message The message to log.
 * @param string $level Log level (info, warning, error).
 * @param array  $data Additional data to log.
 */
function mlr_log( $message, $level = 'info', $data = array() ) {
    if ( ! MLR_ENABLE_LOGGING ) {
        return;
    }
    
    $log_message = sprintf(
        '[%s] [%s] %s',
        gmdate( 'Y-m-d H:i:s' ),
        strtoupper( $level ),
        $message
    );
    
    if ( ! empty( $data ) ) {
        $log_message .= ' ' . wp_json_encode( $data );
    }
    
    error_log( $log_message );
    do_action( 'mlr_log', $message, $level, $data );
}

/**
 * Get cached data
 *
 * @param string $key Cache key.
 * @param string $group Optional cache group.
 * @return mixed Cached value or false
 */
function mlr_get_cache( $key, $group = 'mlr' ) {
    if ( ! MLR_ENABLE_CACHING ) {
        return false;
    }
    return wp_cache_get( $key, $group );
}

/**
 * Set cached data
 *
 * @param string $key Cache key.
 * @param mixed  $value Value to cache.
 * @param string $group Optional cache group.
 * @param int    $expire Expiration time in seconds.
 */
function mlr_set_cache( $key, $value, $group = 'mlr', $expire = 0 ) {
    if ( ! MLR_ENABLE_CACHING ) {
        return;
    }
    if ( 0 === $expire ) {
        $expire = MLR_CACHE_EXPIRY;
    }
    wp_cache_set( $key, $value, $group, $expire );
}

/**
 * Delete cached data
 *
 * @param string $key Cache key.
 * @param string $group Optional cache group.
 */
function mlr_delete_cache( $key, $group = 'mlr' ) {
    wp_cache_delete( $key, $group );
}

/**
 * Flush all MLR cache
 */
function mlr_flush_cache() {
    wp_cache_flush();
}

/**
 * Check if current user can manage lists
 *
 * @param int $user_id Optional user ID (defaults to current user).
 * @return bool True if user can manage lists
 */
function mlr_user_can_manage( $user_id = 0 ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    return current_user_can( 'manage_options' ) || user_can( $user_id, 'manage_options' );
}

/**
 * Verify nonce with error handling
 *
 * @param string $nonce Nonce value to verify.
 * @param string $action Nonce action.
 * @return bool True if nonce is valid
 */
function mlr_verify_nonce( $nonce, $action = MLR_NONCE_ACTION ) {
    if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $action ) ) {
        mlr_log( 'Invalid nonce detected', 'warning' );
        return false;
    }
    return true;
}

/**
 * Get nonce field for forms
 *
 * @param string $action Optional nonce action.
 * @return string HTML nonce field
 */
function mlr_get_nonce_field( $action = MLR_NONCE_ACTION ) {
    return wp_nonce_field( $action, MLR_NONCE_FIELD, true, false );
}

/**
 * Get plugin version
 *
 * @return string Plugin version
 */
function mlr_get_version() {
    return MLR_PLUGIN_VERSION;
}

/**
 * Get plugin URL
 *
 * @param string $path Optional path to append.
 * @return string Plugin URL
 */
function mlr_get_url( $path = '' ) {
    return MLR_PLUGIN_URL . ltrim( $path, '/' );
}

/**
 * Get plugin directory path
 *
 * @param string $path Optional path to append.
 * @return string Plugin directory path
 */
function mlr_get_path( $path = '' ) {
    return MLR_PLUGIN_DIR . ltrim( $path, '/' );
}
