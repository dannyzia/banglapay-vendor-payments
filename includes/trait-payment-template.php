<?php
/**
 * BanglaPay Payment Template Trait
 * Provides consistent payment page design for all payment gateways
 *
 * @package BanglaPay
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

trait BanglaPay_Payment_Template {
    
    /**
     * Display unified payment receipt page
     *
     * @param WC_Order $order Order object
     * @param array $payment_config Payment configuration array
     */
    protected function display_payment_page($order, $payment_config) {
        $vendor_account = $order->get_meta('_banglapay_vendor_account');
        $vendor_id = $order->get_meta('_banglapay_vendor_id');
        $vendor_info = $this->vendor_manager->get_vendor_info($vendor_id);
        
        if (empty($vendor_account)) {
            echo '<div class="woocommerce-error">' . esc_html__('Error loading payment details. Please contact support.', 'banglapay-vendor-payments') . '</div>';
            return;
        }
        
        // Extract and sanitize config
        $method_name = isset($payment_config['method_name']) ? sanitize_text_field($payment_config['method_name']) : '';
        $method_id = isset($payment_config['method_id']) ? sanitize_key($payment_config['method_id']) : '';
        $color = isset($payment_config['color']) ? sanitize_hex_color($payment_config['color']) : '#0066CC';
        $account_label = isset($payment_config['account_label']) ? sanitize_text_field($payment_config['account_label']) : __('Account Number', 'banglapay-vendor-payments');
        $instructions = isset($payment_config['instructions']) && is_array($payment_config['instructions']) ? $payment_config['instructions'] : array();
        $show_bank_details = !empty($payment_config['show_bank_details']);
        
        ?>
        <style>
        .banglapay-payment-box {
            background: #ffffff;
            border: 2px solid <?php echo esc_attr($color); ?>;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .banglapay-payment-box h3 {
            color: <?php echo esc_attr($color); ?>;
            margin: 0 0 20px 0;
            font-size: 24px;
            border-bottom: 2px solid <?php echo esc_attr($color); ?>;
            padding-bottom: 10px;
        }
        .payment-instructions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .payment-instructions h4 {
            margin-top: 0;
            color: #333;
        }
        .payment-instructions ol {
            margin: 10px 0;
            padding-left: 25px;
        }
        .payment-instructions li {
            margin: 10px 0;
            line-height: 1.6;
        }
        .vendor-payment-details {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .vendor-payment-details h4 {
            margin-top: 0;
            color: #333;
        }
        .vendor-payment-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .vendor-payment-details th {
            text-align: left;
            padding: 12px 8px;
            width: 40%;
            color: #666;
            font-weight: 600;
        }
        .vendor-payment-details td {
            padding: 12px 8px;
            color: #333;
        }
        .vendor-payment-details tr {
            border-bottom: 1px solid #e9ecef;
        }
        .vendor-payment-details tr:last-child {
            border-bottom: none;
        }
        .account-number {
            font-size: 1.3em;
            color: <?php echo esc_attr($color); ?>;
            font-weight: bold;
        }
        .copy-button {
            margin-left: 10px;
            padding: 6px 15px;
            background: <?php echo esc_attr($color); ?>;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .copy-button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .payment-form {
            background: #fff;
            padding: 25px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin: 20px 0;
        }
        .payment-form h4 {
            margin-top: 0;
            color: #333;
            margin-bottom: 20px;
        }
        .payment-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .payment-form input[type="text"],
        .payment-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .payment-form input[type="text"]:focus,
        .payment-form textarea:focus {
            outline: none;
            border-color: <?php echo esc_attr($color); ?>;
            box-shadow: 0 0 0 3px rgba(<?php echo $this->hex_to_rgb($color); ?>, 0.1);
        }
        .payment-form small {
            display: block;
            margin-top: -10px;
            margin-bottom: 15px;
            color: #6c757d;
            font-size: 13px;
        }
        .payment-form .required {
            color: #dc3545;
        }
        .payment-form button[type="submit"] {
            background: <?php echo esc_attr($color); ?>;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        .payment-form button[type="submit"]:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .payment-form button[type="submit"]:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        #payment-message {
            margin-top: 15px;
            padding: 15px;
            border-radius: 4px;
        }
        .woocommerce-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
        }
        .woocommerce-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
        }
        .bank-details-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        </style>
        
        <div class="banglapay-payment-box">
            <h3><?php 
                /* translators: %s: payment method name */
                printf(esc_html__('Complete Your %s Payment', 'banglapay-vendor-payments'), esc_html($method_name)); 
            ?></h3>
            
            <?php if (!empty($instructions)): ?>
            <div class="payment-instructions">
                <h4><?php esc_html_e('Payment Instructions:', 'banglapay-vendor-payments'); ?></h4>
                <ol>
                    <?php foreach ($instructions as $step): ?>
                        <li><?php echo wp_kses_post($step); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <?php endif; ?>
            
            <div class="vendor-payment-details">
                <h4><?php esc_html_e('Payment Details:', 'banglapay-vendor-payments'); ?></h4>
                <table>
                    <tr>
                        <th><?php esc_html_e('Vendor Name:', 'banglapay-vendor-payments'); ?></th>
                        <td><?php echo esc_html($vendor_info['name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo esc_html($account_label); ?>:</th>
                        <td class="account-number">
                            <span id="account-number"><?php echo esc_html($vendor_account); ?></span>
                            <button type="button" class="copy-button" onclick="banglapayCopyToClipboard('<?php echo esc_js($vendor_account); ?>')">
                                <?php esc_html_e('Copy', 'banglapay-vendor-payments'); ?>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Amount to Pay:', 'banglapay-vendor-payments'); ?></th>
                        <td><strong style="font-size:1.2em; color:<?php echo esc_attr($color); ?>;"><?php echo wc_price($order->get_total()); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Order Number:', 'banglapay-vendor-payments'); ?></th>
                        <td><?php echo esc_html($order->get_order_number()); ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if ($show_bank_details): ?>
            <div class="bank-details-box">
                <h4 style="margin-top:0;"><?php esc_html_e('Bank Account Details:', 'banglapay-vendor-payments'); ?></h4>
                <table style="width:100%;">
                    <?php
                    $bank_details = $this->vendor_manager->get_vendor_bank_details($vendor_id);
                    if (!empty($bank_details)):
                        foreach ($bank_details as $label => $value):
                            if (!empty($value)):
                    ?>
                    <tr>
                        <th style="text-align:left; width:40%; padding:8px 0;"><?php echo esc_html($label); ?>:</th>
                        <td style="padding:8px 0;"><strong><?php echo esc_html($value); ?></strong></td>
                    </tr>
                    <?php 
                            endif;
                        endforeach;
                    endif;
                    ?>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="payment-form">
                <h4><?php esc_html_e('Confirm Your Payment:', 'banglapay-vendor-payments'); ?></h4>
                <form id="<?php echo esc_attr($method_id); ?>-payment-form" method="post" enctype="multipart/form-data">
                    <?php
                    // Add file upload field
                    if (function_exists('banglapay_render_file_upload_field')) {
                        banglapay_render_file_upload_field($order->get_id(), $method_id);
                    }
                    ?>
                    <p>
                        <label for="transaction_id">
                            <?php 
                            /* translators: %s: payment method name */
                            printf(esc_html__('%s Transaction ID:', 'banglapay-vendor-payments'), esc_html($method_name)); 
                            ?>
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="transaction_id" 
                            name="transaction_id" 
                            required 
                            placeholder="<?php esc_attr_e('e.g., 8N7A5D3E2T', 'banglapay-vendor-payments'); ?>"
                            autocomplete="off"
                        />
                        <small><?php 
                            /* translators: %s: payment method name */
                            printf(esc_html__('Enter the transaction ID from your %s app/receipt', 'banglapay-vendor-payments'), esc_html($method_name)); 
                        ?></small>
                    </p>
                    
                    <p>
                        <label for="sender_number"><?php 
                            /* translators: %s: payment method name */
                            printf(esc_html__('Your %s Number:', 'banglapay-vendor-payments'), esc_html($method_name)); 
                        ?></label>
                        <input 
                            type="text" 
                            id="sender_number" 
                            name="sender_number" 
                            placeholder="<?php esc_attr_e('01XXXXXXXXX', 'banglapay-vendor-payments'); ?>"
                            autocomplete="off"
                        />
                        <small><?php 
                            /* translators: %s: payment method name */
                            printf(esc_html__('Optional: Your %s account number for verification', 'banglapay-vendor-payments'), esc_html($method_name)); 
                        ?></small>
                    </p>
                    
                    <p>
                        <label for="payment_notes"><?php esc_html_e('Additional Notes:', 'banglapay-vendor-payments'); ?></label>
                        <textarea 
                            id="payment_notes" 
                            name="notes" 
                            rows="3" 
                            placeholder="<?php esc_attr_e('Any additional information (optional)', 'banglapay-vendor-payments'); ?>"
                        ></textarea>
                    </p>
                    
                    <input type="hidden" name="order_id" value="<?php echo esc_attr($order->get_id()); ?>" />
                    <input type="hidden" name="action" value="banglapay_submit_<?php echo esc_attr($method_id); ?>_payment" />
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('banglapay_' . $method_id . '_nonce'); ?>" />
                    
                    <p>
                        <button type="submit" class="button alt" id="submit-payment-btn">
                            <?php esc_html_e('Submit Payment Information', 'banglapay-vendor-payments'); ?>
                        </button>
                    </p>
                    
                    <div id="payment-message" style="display:none;"></div>
                </form>
            </div>
        </div>
        
        <script>
        function banglapayCopyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('<?php echo esc_js(__('Account number copied to clipboard!', 'banglapay-vendor-payments')); ?>');
                }).catch(function() {
                    banglapayFallbackCopy(text);
                });
            } else {
                banglapayFallbackCopy(text);
            }
        }
        
        function banglapayFallbackCopy(text) {
            var textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.top = "-9999px";
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                alert('<?php echo esc_js(__('Account number copied!', 'banglapay-vendor-payments')); ?>');
            } catch (err) {
                alert('<?php echo esc_js(__('Unable to copy. Please copy manually:', 'banglapay-vendor-payments')); ?> ' + text);
            }
            document.body.removeChild(textArea);
        }

        if (typeof jQuery === 'undefined') {
            console.error('BanglaPay: jQuery is not loaded!');
        }
        
        jQuery(document).ready(function($) {
            $('#<?php echo esc_js($method_id); ?>-payment-form').on('submit', function(e) {
                e.preventDefault();
                
                var submitButton = $('#submit-payment-btn');
                var messageDiv = $('#payment-message');
                var originalText = submitButton.text();
                
                submitButton.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'banglapay-vendor-payments')); ?>');
                messageDiv.hide();
                
                var formData = new FormData(this);
                
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            messageDiv
                                .html('<div class="woocommerce-message">' + response.data.message + '</div>')
                                .show();
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        } else {
                            messageDiv
                                .html('<div class="woocommerce-error">' + (response.data ? response.data.message : '<?php echo esc_js(__('An error occurred', 'banglapay-vendor-payments')); ?>') + '</div>')
                                .show();
                            submitButton.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('BanglaPay: AJAX Error', xhr, status, error);
                        messageDiv
                            .html('<div class="woocommerce-error"><?php echo esc_js(__('An error occurred. Please try again.', 'banglapay-vendor-payments')); ?></div>')
                            .show();
                        submitButton.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Convert hex color to RGB
     *
     * @param string $hex Hex color code
     * @return string RGB color values as "r, g, b"
     */
    private function hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "$r, $g, $b";
    }
}