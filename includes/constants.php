<?php
/**
 * MLR Gift Registry - Constants Configuration
 *
 * Defines all constants used throughout the plugin for security,
 * configuration, and feature management.
 *
 * @package MLR_Gift_Registry
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Plugin identification constants
if ( ! defined( 'MLR_PLUGIN_NAME' ) ) {
    define( 'MLR_PLUGIN_NAME', 'MLR Gift Registry' );
}

if ( ! defined( 'MLR_PLUGIN_SLUG' ) ) {
    define( 'MLR_PLUGIN_SLUG', 'mlr-gift-registry' );
}

if ( ! defined( 'MLR_PLUGIN_VERSION' ) ) {
    define( 'MLR_PLUGIN_VERSION', '1.0.0' );
}

if ( ! defined( 'MLR_PLUGIN_BASENAME' ) ) {
    define( 'MLR_PLUGIN_BASENAME', plugin_basename( MLR_PLUGIN_FILE ) );
}

// Path constants
if ( ! defined( 'MLR_PLUGIN_DIR' ) ) {
    define( 'MLR_PLUGIN_DIR', plugin_dir_path( MLR_PLUGIN_FILE ) );
}

if ( ! defined( 'MLR_PLUGIN_URL' ) ) {
    define( 'MLR_PLUGIN_URL', plugin_dir_url( MLR_PLUGIN_FILE ) );
}

if ( ! defined( 'MLR_INCLUDES_DIR' ) ) {
    define( 'MLR_INCLUDES_DIR', MLR_PLUGIN_DIR . 'includes/' );
}

if ( ! defined( 'MLR_ASSETS_DIR' ) ) {
    define( 'MLR_ASSETS_DIR', MLR_PLUGIN_DIR . 'assets/' );
}

if ( ! defined( 'MLR_ASSETS_URL' ) ) {
    define( 'MLR_ASSETS_URL', MLR_PLUGIN_URL . 'assets/' );
}

// Database prefix for custom tables
if ( ! defined( 'MLR_DB_PREFIX' ) ) {
    global $wpdb;
    define( 'MLR_DB_PREFIX', $wpdb->prefix . 'mlr_' );
}

// Feature flags
if ( ! defined( 'MLR_ENABLE_LOGGING' ) ) {
    define( 'MLR_ENABLE_LOGGING', defined( 'WP_DEBUG' ) && WP_DEBUG );
}

if ( ! defined( 'MLR_ENABLE_CACHING' ) ) {
    define( 'MLR_ENABLE_CACHING', true );
}

if ( ! defined( 'MLR_CACHE_EXPIRY' ) ) {
    define( 'MLR_CACHE_EXPIRY', 12 * HOUR_IN_SECONDS );
}

if ( ! defined( 'MLR_ENABLE_DEBUG_MODE' ) ) {
    define( 'MLR_ENABLE_DEBUG_MODE', false );
}

// Security constants
if ( ! defined( 'MLR_NONCE_ACTION' ) ) {
    define( 'MLR_NONCE_ACTION', 'mlr_nonce_action' );
}

if ( ! defined( 'MLR_NONCE_FIELD' ) ) {
    define( 'MLR_NONCE_FIELD', 'mlr_nonce_field' );
}

// API constants
if ( ! defined( 'MLR_API_VERSION' ) ) {
    define( 'MLR_API_VERSION', 'v1' );
}

if ( ! defined( 'MLR_API_NAMESPACE' ) ) {
    define( 'MLR_API_NAMESPACE', 'mlr-registry/' . MLR_API_VERSION );
}

// Email configuration
if ( ! defined( 'MLR_EMAIL_FROM_NAME' ) ) {
    define( 'MLR_EMAIL_FROM_NAME', get_bloginfo( 'name' ) );
}

if ( ! defined( 'MLR_EMAIL_FROM_ADDRESS' ) ) {
    define( 'MLR_EMAIL_FROM_ADDRESS', get_option( 'admin_email' ) );
}

// User roles
if ( ! defined( 'MLR_USER_ROLE_ADMIN' ) ) {
    define( 'MLR_USER_ROLE_ADMIN', 'mlr_admin' );
}

if ( ! defined( 'MLR_USER_ROLE_MODERATOR' ) ) {
    define( 'MLR_USER_ROLE_MODERATOR', 'mlr_moderator' );
}

// Request validation constants
if ( ! defined( 'MLR_MIN_LIST_NAME_LENGTH' ) ) {
    define( 'MLR_MIN_LIST_NAME_LENGTH', 3 );
}

if ( ! defined( 'MLR_MAX_LIST_NAME_LENGTH' ) ) {
    define( 'MLR_MAX_LIST_NAME_LENGTH', 255 );
}

if ( ! defined( 'MLR_MAX_ITEMS_PER_LIST' ) ) {
    define( 'MLR_MAX_ITEMS_PER_LIST', 500 );
}

// Payment gateway constants
if ( ! defined( 'MLR_PAYMENT_GATEWAY_WEBPAY' ) ) {
    define( 'MLR_PAYMENT_GATEWAY_WEBPAY', 'webpay' );
}

if ( ! defined( 'MLR_PAYMENT_GATEWAY_KHIPU' ) ) {
    define( 'MLR_PAYMENT_GATEWAY_KHIPU', 'khipu' );
}

// Session and transient expiry times
if ( ! defined( 'MLR_SESSION_EXPIRY' ) ) {
    define( 'MLR_SESSION_EXPIRY', 24 * HOUR_IN_SECONDS );
}

if ( ! defined( 'MLR_TRANSIENT_EXPIRY' ) ) {
    define( 'MLR_TRANSIENT_EXPIRY', 2 * HOUR_IN_SECONDS );
}

// Default values
if ( ! defined( 'MLR_DEFAULT_ITEMS_PER_PAGE' ) ) {
    define( 'MLR_DEFAULT_ITEMS_PER_PAGE', 20 );
}

if ( ! defined( 'MLR_DEFAULT_SORT_BY' ) ) {
    define( 'MLR_DEFAULT_SORT_BY', 'date' );
}

if ( ! defined( 'MLR_DEFAULT_SORT_ORDER' ) ) {
    define( 'MLR_DEFAULT_SORT_ORDER', 'DESC' );
}
