<?php
/**
 * BanglaPay Vendor Manager
 * Handles vendor detection and payment settings retrieval
 *
 * @package BanglaPay
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class BanglaPay_Vendor_Manager {
    /**
     * Single instance of the class
     *
     * @var BanglaPay_Vendor_Manager
     */
    private static $instance = null;
    
    /**
     * Get single instance
     *
     * @return BanglaPay_Vendor_Manager
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {}
    
    /**
     * Get vendor ID for an order
     * Returns the vendor who owns the products in the order
     *
     * @param int $order_id Order ID
     * @return int Vendor user ID
     */
    public function get_order_vendor($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return get_current_user_id() ?: 1;
        }
        
        // Check for Dokan vendor meta on the order
        $vendor_id = $order->get_meta('_dokan_vendor_id');
        if ($vendor_id) {
            return absint($vendor_id);
        }
        
        // Check for WCFM vendor meta
        $vendor_id = $order->get_meta('_wcfm_vendor');
        if ($vendor_id) {
            return absint($vendor_id);
        }
        
        // Check for WC Vendors meta
        $vendor_id = $order->get_meta('_wcv_vendor_id');
        if ($vendor_id) {
            return absint($vendor_id);
        }
        
        // Get vendor from the first product in the order
        $items = $order->get_items();
        if (!empty($items)) {
            foreach ($items as $item) {
                $product_id = $item->get_product_id();
                if ($product_id) {
                    // Get the product author (vendor)
                    $vendor_id = get_post_field('post_author', $product_id);
                    if ($vendor_id) {
                        return absint($vendor_id);
                    }
                }
            }
        }
        
        // Fallback to shop owner (user ID 1)
        return 1;
    }
    
    /**
     * Get vendor payment settings from database
     *
     * @param int $vendor_id Vendor user ID
     * @return array Payment settings array
     */
    public function get_vendor_payment_settings($vendor_id) {
        global $wpdb;
        
        $vendor_id = absint($vendor_id);
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT payment_method, account_number, enabled, settings 
             FROM {$wpdb->prefix}banglapay_vendor_settings 
             WHERE vendor_id = %d",
            $vendor_id
        ), ARRAY_A);
        
        $settings = array();
        
        if (!empty($results)) {
            foreach ($results as $row) {
                $method = sanitize_key($row['payment_method']);
                $settings[$method . '_enabled'] = !empty($row['enabled']) ? 'yes' : 'no';
                $settings[$method . '_account'] = sanitize_text_field($row['account_number']);
                
                // Parse additional settings from JSON
                if (!empty($row['settings'])) {
                    $extra = json_decode($row['settings'], true);
                    if (is_array($extra)) {
                        foreach ($extra as $key => $value) {
                            $settings[$method . '_' . sanitize_key($key)] = sanitize_text_field($value);
                        }
                    }
                }
            }
        }
        
        // If no settings found, return disabled methods
        if (empty($settings)) {
            $settings = array(
                'bkash_enabled' => 'no',
                'bkash_account' => '',
                'nagad_enabled' => 'no',
                'nagad_account' => '',
                'rocket_enabled' => 'no',
                'rocket_account' => '',
                'upay_enabled' => 'no',
                'upay_account' => '',
                'bank_enabled' => 'no',
                'bank_account' => '',
            );
        }
        
        return $settings;
    }
    
    /**
     * Get vendor information
     *
     * @param int $vendor_id Vendor user ID
     * @return array Vendor info array with id, name, email
     */
    public function get_vendor_info($vendor_id) {
        $vendor_id = absint($vendor_id);
        $user = get_userdata($vendor_id);
        
        if (!$user) {
            return array(
                'id' => $vendor_id,
                'name' => __('Unknown Vendor', 'banglapay-vendor-payments'),
                'email' => '',
            );
        }
        
        // Try to get shop name from multivendor plugins
        $shop_name = get_user_meta($vendor_id, 'dokan_store_name', true);
        
        if (empty($shop_name)) {
            $shop_name = get_user_meta($vendor_id, 'wcfmmp_store_name', true);
        }
        
        if (empty($shop_name)) {
            $shop_name = get_user_meta($vendor_id, 'pv_shop_name', true); // WC Vendors
        }
        
        if (empty($shop_name)) {
            $shop_name = $user->display_name;
        }
        
        return array(
            'id' => $vendor_id,
            'name' => sanitize_text_field($shop_name),
            'email' => sanitize_email($user->user_email),
        );
    }
    
    /**
     * Get vendor bank details for bank transfer
     *
     * @param int $vendor_id Vendor user ID
     * @return array Bank details array
     */
    public function get_vendor_bank_details($vendor_id) {
        global $wpdb;
        
        $vendor_id = absint($vendor_id);
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT account_number, settings 
             FROM {$wpdb->prefix}banglapay_vendor_settings 
             WHERE vendor_id = %d AND payment_method = 'bank'",
            $vendor_id
        ));
        
        if (!$result) {
            return array();
        }
        
        $settings = !empty($result->settings) ? json_decode($result->settings, true) : array();
        
        return array(
            'Bank Name' => isset($settings['bank_name']) ? sanitize_text_field($settings['bank_name']) : '',
            'Account Name' => isset($settings['account_name']) ? sanitize_text_field($settings['account_name']) : '',
            'Account Number' => isset($result->account_number) ? sanitize_text_field($result->account_number) : '',
            'Branch' => isset($settings['branch']) ? sanitize_text_field($settings['branch']) : '',
            'Routing Number' => isset($settings['routing']) ? sanitize_text_field($settings['routing']) : '',
        );
    }
    
    /**
     * Check if vendor has any payment method enabled
     *
     * @param int $vendor_id Vendor user ID
     * @return bool True if vendor has payment methods enabled
     */
    public function vendor_has_payment_methods($vendor_id) {
        global $wpdb;
        
        $vendor_id = absint($vendor_id);
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$wpdb->prefix}banglapay_vendor_settings 
             WHERE vendor_id = %d AND enabled = 1",
            $vendor_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get all active payment methods for vendor
     *
     * @param int $vendor_id Vendor user ID
     * @return array Array of active payment method names
     */
    public function get_active_payment_methods($vendor_id) {
        global $wpdb;
        
        $vendor_id = absint($vendor_id);
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT payment_method 
             FROM {$wpdb->prefix}banglapay_vendor_settings 
             WHERE vendor_id = %d AND enabled = 1",
            $vendor_id
        ), ARRAY_A);
        
        if (empty($results)) {
            return array();
        }
        
        return array_map('sanitize_key', array_column($results, 'payment_method'));
    }
}