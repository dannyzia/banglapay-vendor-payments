<?php
/**
 * Nagad Payment Gateway for BanglaPay
 * Handles Nagad mobile wallet payments with vendor-specific accounts
 *
 * @package BanglaPay
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Load the unified template trait
require_once BANGLAPAY_PLUGIN_DIR . 'includes/trait-payment-template.php';

if (!class_exists('WC_Gateway_Vendor_Nagad')) {
    class WC_Gateway_Vendor_Nagad extends WC_Payment_Gateway {
        use BanglaPay_Payment_Template;
        
        protected $vendor_id;
        protected $vendor_settings;
        protected $vendor_manager;
        
        public function __construct() {
            $this->id = 'vendor_nagad';
            $this->icon = '';
            $this->has_fields = true;
            $this->method_title = __('Vendor Nagad', 'banglapay-vendor-payments');
            $this->method_description = __('Accept Nagad payments with vendor-specific accounts', 'banglapay-vendor-payments');
            $this->supports = array('products');
            
            $this->init_form_fields();
            $this->init_settings();
            
            $this->title = $this->get_option('title', __('Nagad', 'banglapay-vendor-payments'));
            $this->description = $this->get_option('description', __('Pay with Nagad', 'banglapay-vendor-payments'));
            $this->enabled = $this->get_option('enabled', 'yes');
            
            // Hooks
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            
            // AJAX handlers
            add_action('wp_ajax_banglapay_submit_nagad_payment', array($this, 'ajax_submit_payment'));
            add_action('wp_ajax_nopriv_banglapay_submit_nagad_payment', array($this, 'ajax_submit_payment'));
        }
        
        /**
         * Initialize vendor manager when needed
         */
        private function init_vendor_manager() {
            if (!$this->vendor_manager && class_exists('BanglaPay_Vendor_Manager')) {
                $this->vendor_manager = BanglaPay_Vendor_Manager::instance();
            }
        }
        
        /**
         * Initialize form fields for admin settings
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'banglapay-vendor-payments'),
                    'type' => 'checkbox',
                    'label' => __('Enable Nagad Payment', 'banglapay-vendor-payments'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'banglapay-vendor-payments'),
                    'type' => 'text',
                    'description' => __('Payment method title that customers see during checkout', 'banglapay-vendor-payments'),
                    'default' => __('Nagad', 'banglapay-vendor-payments'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'banglapay-vendor-payments'),
                    'type' => 'textarea',
                    'description' => __('Payment method description that customers see during checkout', 'banglapay-vendor-payments'),
                    'default' => __('Pay securely using your Nagad mobile wallet', 'banglapay-vendor-payments'),
                    'desc_tip' => true,
                ),
            );
        }
        
        /**
         * Process the payment
         */
        public function process_payment($order_id) {
            $this->init_vendor_manager();
            
            if (!$this->vendor_manager) {
                wc_add_notice(__('Payment system error. Please contact support.', 'banglapay-vendor-payments'), 'error');
                return array('result' => 'failure');
            }
            
            $order = wc_get_order($order_id);
            if (!$order) {
                wc_add_notice(__('Invalid order.', 'banglapay-vendor-payments'), 'error');
                return array('result' => 'failure');
            }
            
            $this->vendor_id = $this->vendor_manager->get_order_vendor($order_id);
            
            if (!$this->vendor_id) {
                wc_add_notice(__('Unable to process payment.', 'banglapay-vendor-payments'), 'error');
                return array('result' => 'failure');
            }
            
            $this->vendor_settings = $this->vendor_manager->get_vendor_payment_settings($this->vendor_id);
            
            if (empty($this->vendor_settings['nagad_account']) || $this->vendor_settings['nagad_enabled'] !== 'yes') {
                wc_add_notice(__('Nagad payment is not available for this order.', 'banglapay-vendor-payments'), 'error');
                return array('result' => 'failure');
            }
            
            // Save payment method details to order
            $order->update_meta_data('_banglapay_vendor_id', $this->vendor_id);
            $order->update_meta_data('_banglapay_payment_method', 'nagad');
            $order->update_meta_data('_banglapay_vendor_account', $this->vendor_settings['nagad_account']);
            $order->save();
            
            // Update order status
            $order->update_status('pending', __('Awaiting Nagad payment confirmation', 'banglapay-vendor-payments'));
            
            // Reduce stock levels
            wc_reduce_stock_levels($order_id);
            
            // Empty cart
            WC()->cart->empty_cart();
            
            // Redirect to payment page
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }
        
        /**
         * Display payment receipt page
         */
        public function receipt_page($order_id) {
            $this->init_vendor_manager();
            
            $order = wc_get_order($order_id);
            if (!$order) {
                echo '<div class="woocommerce-error">' . esc_html__('Invalid order.', 'banglapay-vendor-payments') . '</div>';
                return;
            }
            
            $this->display_payment_page($order, array(
                'method_name' => 'Nagad',
                'method_id' => 'nagad',
                'color' => '#EB5628',
                'account_label' => __('Nagad Account Number', 'banglapay-vendor-payments'),
                'instructions' => array(
                    __('Open your <strong>Nagad app</strong> on your phone', 'banglapay-vendor-payments'),
                    __('Select <strong>"Send Money"</strong>', 'banglapay-vendor-payments'),
                    __('Enter the Nagad number shown below', 'banglapay-vendor-payments'),
                    __('Send the exact amount shown', 'banglapay-vendor-payments'),
                    __('Enter your <strong>transaction ID</strong> in the form below', 'banglapay-vendor-payments'),
                    __('Click <strong>Submit</strong> to complete your order', 'banglapay-vendor-payments')
                ),
                'show_bank_details' => false
            ));
        }
        
        /**
         * AJAX handler for payment submission
         */
        public function ajax_submit_payment() {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'banglapay_nagad_nonce')) {
                wp_send_json_error(array(
                    'message' => __('Security check failed. Please refresh the page and try again.', 'banglapay-vendor-payments')
                ));
                return;
            }
            
            $this->init_vendor_manager();
            
            // Get and sanitize input
            $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
            $transaction_id = isset($_POST['transaction_id']) ? sanitize_text_field($_POST['transaction_id']) : '';
            $sender_number = isset($_POST['sender_number']) ? sanitize_text_field($_POST['sender_number']) : '';
            $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
            
            // Validate required fields
            if (empty($transaction_id)) {
                wp_send_json_error(array(
                    'message' => __('Transaction ID is required.', 'banglapay-vendor-payments')
                ));
                return;
            }
            
            // Validate order
            $order = wc_get_order($order_id);
            if (!$order) {
                wp_send_json_error(array(
                    'message' => __('Invalid order.', 'banglapay-vendor-payments')
                ));
                return;
            }
            
            // Check if order belongs to current user
            if (get_current_user_id() !== $order->get_customer_id() && !current_user_can('manage_woocommerce')) {
                wp_send_json_error(array(
                    'message' => __('You do not have permission to update this order.', 'banglapay-vendor-payments')
                ));
                return;
            }
            
            // Save payment information to order meta
            $order->update_meta_data('_nagad_transaction_id', $transaction_id);
            $order->update_meta_data('_nagad_sender_number', $sender_number);
            $order->update_meta_data('_nagad_payment_notes', $notes);
            $order->update_meta_data('_nagad_payment_date', current_time('mysql'));
            
            // Update order status
            $order->update_status('on-hold', sprintf(
                /* translators: %s: transaction ID */
                __('Nagad payment submitted. Transaction ID: %s', 'banglapay-vendor-payments'),
                $transaction_id
            ));
            
            $order->save();
            
            // Save to transactions table
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'banglapay_transactions',
                array(
                    'order_id' => $order_id,
                    'vendor_id' => $order->get_meta('_banglapay_vendor_id'),
                    'payment_method' => 'nagad',
                    'transaction_id' => $transaction_id,
                    'amount' => $order->get_total(),
                    'status' => 'pending',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%f', '%s', '%s')
            );
            
            // Send success response
            wp_send_json_success(array(
                'message' => __('Payment information submitted successfully!', 'banglapay-vendor-payments'),
                'redirect' => $order->get_checkout_order_received_url()
            ));
        }
        
        /**
         * Display thank you page content
         */
        public function thankyou_page($order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }
            
            $transaction_id = $order->get_meta('_nagad_transaction_id');
            
            if ($transaction_id) {
                echo '<div class="woocommerce-message" style="margin-top: 20px;">';
                echo '<h3>' . esc_html__('Payment Information Received', 'banglapay-vendor-payments') . '</h3>';
                echo '<p>' . esc_html__('Thank you! We have received your Nagad payment information.', 'banglapay-vendor-payments') . '</p>';
                echo '<p><strong>' . esc_html__('Transaction ID:', 'banglapay-vendor-payments') . '</strong> ' . esc_html($transaction_id) . '</p>';
                echo '<p>' . esc_html__('Your order is being verified and will be processed shortly.', 'banglapay-vendor-payments') . '</p>';
                echo '</div>';
            }
        }
    }
}