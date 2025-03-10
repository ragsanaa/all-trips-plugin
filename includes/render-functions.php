<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Format a trip's price for display
 */
function all_trips_format_price($price) {
    if (empty($price) || !isset($price['amount']) || !isset($price['currencySymbol'])) {
        return '';
    }

    return $price['currencySymbol'] . number_format($price['amount'], 2);
}

/**
 * Format a trip's date for display
 */
function all_trips_format_date($date_string) {
    if (empty($date_string)) {
        return '';
    }

    $date = strtotime($date_string);
    if (!$date) {
        return '';
    }

    return date_i18n(get_option('date_format'), $date);
}

/**
 * Get trip thumbnail image
 */
function all_trips_get_thumbnail($trip) {
    if (empty($trip['banner_img']) || !is_array($trip['banner_img'])) {
        return '';
    }

    return $trip['banner_img'];
}

/**
 * Clear WeTravel trips cache
 */
function all_trips_clear_cache() {
    global $wpdb;

    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_wetravel_trips_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_wetravel_trips_%'");
}

/**
 * Escape HTML attributes correctly
 */
function all_trips_esc_attr($text) {
    return esc_attr($text);
}
