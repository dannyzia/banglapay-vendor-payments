<?php
/**
 * Uninstall BanglaPay
 * Fires when the plugin is uninstalled
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options
delete_option('banglapay_db_version');
delete_option('banglapay_error');

// Delete transients
delete_transient('banglapay_success');

// Drop custom tables (optional - ask users first via settings)
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}banglapay_transactions");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}banglapay_vendor_settings");

// Delete all order meta
$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_banglapay_%'");
$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_bkash_%'");
$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_nagad_%'");
$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_rocket_%'");
$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_upay_%'");
$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_bank_%'");