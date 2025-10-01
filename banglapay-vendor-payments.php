<?php
/**
 * Plugin Name: BanglaPay Vendor Payments
 * Plugin URI: https://github.com/yourusername/banglapay-vendor-payments
 * Description: Vendor-specific payment gateways for WooCommerce supporting bKash, Nagad, Rocket, Upay, and Bank Transfer
 * Version: 1.0.0
 * Author: Ziaur Rahman
 * Author URI: https://digital-papyrus.xyz
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: banglapay-vendor-payments
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 */

if (!defined('ABSPATH')) exit;

// Suppress all output during initialization
ob_start();

// Define constants
define('BANGLAPAY_VERSION', '1.0.0');
define('BANGLAPAY_PLUGIN_FILE', __FILE__);
define('BANGLAPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BANGLAPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BANGLAPAY_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('BANGLAPAY_DB_VERSION', '1.0.0');

final class BanglaPay_Vendor_Payments {
    private static $instance = null;
    private $initialized = false;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->define_tables();
        $this->init_hooks();
    }
    
    private function define_tables() {
        global $wpdb;
        $wpdb->banglapay_transactions = $wpdb->prefix . 'banglapay_transactions';
        $wpdb->banglapay_vendor_settings = $wpdb->prefix . 'banglapay_vendor_settings';
    }
    
    private function init_hooks() {
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        add_action('plugins_loaded', array($this, 'check_dependencies'), 5);
        add_action('plugins_loaded', array($this, 'init_plugin'), 10);
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(BANGLAPAY_PLUGIN_FILE, array($this, 'activate'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('banglapay-vendor-payments', false, dirname(BANGLAPAY_PLUGIN_BASENAME) . '/languages');
    }
    
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                BANGLAPAY_PLUGIN_FILE,
                true
            );
        }
    }
    
    public function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            update_option('banglapay_error', __('WooCommerce is required', 'banglapay-vendor-payments'));
            return false;
        }
        delete_option('banglapay_error');
        return true;
    }
    
    public function init_plugin() {
        if (!$this->check_dependencies() || $this->initialized) return;
        
        try {
            $this->load_includes();
            add_filter('woocommerce_payment_gateways', array($this, 'register_payment_gateways'));
            
            if (class_exists('BanglaPay_Core')) {
                BanglaPay_Core::instance();
            }
            if (class_exists('BanglaPay_Vendor_Manager')) {
                BanglaPay_Vendor_Manager::instance();
            }
            
            $this->initialized = true;
            
        } catch (Exception $e) {
            update_option('banglapay_error', $e->getMessage());
        }
    }
    
    private function load_includes() {
        // Core files - always load
        $this->include_file('includes/class-banglapay-error-handler.php');
        $this->include_file('includes/class-banglapay-core.php');
        $this->include_file('includes/class-banglapay-vendor-manager.php');
        $this->include_file('includes/class-banglapay-qr-generator.php');
        $this->include_file('includes/class-banglapay-file-handler.php');
        
        // AJAX handlers - load on admin and frontend
        $this->include_file('includes/class-banglapay-ajax-handlers.php');
        
        // Admin-only files
        if (is_admin()) {
            $this->include_file('admin/banglapay-vendor-payment-settings.php');
        }
        
        // Dokan integration - load if Dokan is active
        if (function_exists('dokan') || class_exists('WeDevs_Dokan')) {
            $this->include_file('admin/dokan-banglapay-integration.php');
            
            // Only load verification on Dokan dashboard pages
            if (is_admin() || (function_exists('dokan_is_user_seller') && dokan_is_user_seller(get_current_user_id()))) {
                $this->include_file('admin/vendor-payment-verification.php');
            }
        }
        
        // Payment template trait - load when needed for gateways
        add_filter('woocommerce_payment_gateways', array($this, 'load_gateway_files'), 5);
    }
    
    private function include_file($file) {
        $path = BANGLAPAY_PLUGIN_DIR . $file;
        if (file_exists($path)) {
            require_once $path;
        } else {
            throw new Exception(sprintf(__('Missing file: %s', 'banglapay-vendor-payments'), $file));
        }
    }
    
    public function load_gateway_files($gateways) {
        // Load the shared payment template trait first
        $this->include_file('includes/trait-payment-template.php');
        
        // Load gateway files
        $gateway_files = array(
            'gateways/class-wc-gateway-vendor-bkash.php',
            'gateways/class-wc-gateway-vendor-nagad.php',
            'gateways/class-wc-gateway-vendor-rocket.php',
            'gateways/class-wc-gateway-vendor-upay.php',
            'gateways/class-wc-gateway-vendor-bank-transfer.php',
        );
        
        foreach ($gateway_files as $file) {
            $this->include_file($file);
        }
        
        return $gateways;
    }
    
    public function register_payment_gateways($gateways) {
        if (class_exists('WC_Gateway_Vendor_Bkash')) $gateways[] = 'WC_Gateway_Vendor_Bkash';
        if (class_exists('WC_Gateway_Vendor_Nagad')) $gateways[] = 'WC_Gateway_Vendor_Nagad';
        if (class_exists('WC_Gateway_Vendor_Rocket')) $gateways[] = 'WC_Gateway_Vendor_Rocket';
        if (class_exists('WC_Gateway_Vendor_Upay')) $gateways[] = 'WC_Gateway_Vendor_Upay';
        if (class_exists('WC_Gateway_Vendor_Bank_Transfer')) $gateways[] = 'WC_Gateway_Vendor_Bank_Transfer';
        return $gateways;
    }
    
    public function admin_notices() {
        $error = get_option('banglapay_error');
        if ($error) {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__('BanglaPay:', 'banglapay-vendor-payments') . '</strong> ' . esc_html($error) . '</p></div>';
        }
        
        if (get_transient('banglapay_success')) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__('BanglaPay:', 'banglapay-vendor-payments') . '</strong> ' . esc_html(get_transient('banglapay_success')) . '</p></div>';
            delete_transient('banglapay_success');
        }
    }
    
    public function activate() {
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(BANGLAPAY_PLUGIN_BASENAME);
            wp_die(esc_html__('WooCommerce is required for BanglaPay Vendor Payments', 'banglapay-vendor-payments'));
        }
        
        $this->create_tables();
        set_transient('banglapay_success', __('Plugin activated! Database tables created.', 'banglapay-vendor-payments'), 30);
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$wpdb->prefix}banglapay_transactions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            vendor_id bigint(20) unsigned NOT NULL,
            payment_method varchar(50) NOT NULL,
            transaction_id varchar(100) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id)
        ) $charset;";
        
        dbDelta($sql);
        
        $sql2 = "CREATE TABLE {$wpdb->prefix}banglapay_vendor_settings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            vendor_id bigint(20) unsigned NOT NULL,
            payment_method varchar(50) NOT NULL,
            account_number varchar(100) DEFAULT NULL,
            enabled tinyint(1) NOT NULL DEFAULT 0,
            settings longtext DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY vendor_method (vendor_id, payment_method)
        ) $charset;";
        
        dbDelta($sql2);
        update_option('banglapay_db_version', BANGLAPAY_DB_VERSION);
    }
}

function BanglaPay() {
    return BanglaPay_Vendor_Payments::instance();
}

BanglaPay();

// Clean up output buffer at the end
if (ob_get_length()) ob_end_clean();