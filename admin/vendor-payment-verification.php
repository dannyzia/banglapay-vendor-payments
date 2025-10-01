<?php
/**
 * Vendor Payment Verification
 * Adds payment verification to Dokan vendor dashboard
 *
 * @package BanglaPay
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Add payment info section to Dokan order details page
 *
 * @param WC_Order $order Order object
 */
add_action('dokan_order_detail_after_order_items', 'banglapay_dokan_payment_details', 10, 1);
function banglapay_dokan_payment_details($order) {
    if (!is_a($order, 'WC_Order')) {
        return;
    }
    
    $payment_method = $order->get_meta('_banglapay_payment_method');
    
    if (!$payment_method) {
        return; // Not a BanglaPay payment
    }
    
    // Get payment details
    $transaction_id = $order->get_meta('_' . $payment_method . '_transaction_id');
    $sender_number = $order->get_meta('_' . $payment_method . '_sender_number');
    $payment_notes = $order->get_meta('_' . $payment_method . '_payment_notes');
    $payment_date = $order->get_meta('_' . $payment_method . '_payment_date');
    $vendor_account = $order->get_meta('_banglapay_vendor_account');
    $receipt_file = $order->get_meta('_' . $payment_method . '_receipt_file');
    $verification_status = $order->get_meta('_banglapay_verification_status');
    $verification_notes = $order->get_meta('_banglapay_verification_notes');
    $verification_date = $order->get_meta('_banglapay_verification_date');
    
    // Default to pending if no status set
    if (empty($verification_status)) {
        $verification_status = 'pending';
    }
    
    ?>
    <style>
    .banglapay-payment-section {
        background: #fff;
        border: 2px solid #e5e5e5;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }
    .banglapay-payment-section h3 {
        margin-top: 0;
        color: #2196F3;
        border-bottom: 2px solid #2196F3;
        padding-bottom: 10px;
    }
    .banglapay-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }
    .banglapay-info-item {
        background: #f8f9fa;
        padding: 15px;
        border-left: 4px solid #2196F3;
        border-radius: 4px;
    }
    .banglapay-info-item strong {
        display: block;
        color: #666;
        font-size: 12px;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    .banglapay-info-item span {
        display: block;
        color: #333;
        font-size: 16px;
        font-weight: 600;
    }
    .banglapay-receipt {
        margin: 20px 0;
        padding: 15px;
        background: #e7f5fe;
        border-radius: 4px;
    }
    .banglapay-receipt img {
        max-width: 100%;
        height: auto;
        border: 2px solid #ddd;
        border-radius: 4px;
        margin-top: 10px;
    }
    .banglapay-status-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 14px;
        margin: 10px 0;
    }
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    .status-verified {
        background: #d4edda;
        color: #155724;
    }
    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }
    .banglapay-verify-buttons {
        margin: 20px 0;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 4px;
    }
    .banglapay-verify-buttons button {
        padding: 12px 24px;
        border: none;
        border-radius: 4px;
        font-weight: bold;
        cursor: pointer;
        margin-right: 10px;
        transition: all 0.3s;
    }
    .btn-verify {
        background: #28a745;
        color: white;
    }
    .btn-verify:hover {
        background: #218838;
    }
    .btn-reject {
        background: #dc3545;
        color: white;
    }
    .btn-reject:hover {
        background: #c82333;
    }
    .verification-history {
        margin-top: 15px;
        padding: 15px;
        background: #e9ecef;
        border-radius: 4px;
    }
    </style>
    
    <div class="banglapay-payment-section">
        <h3><?php esc_html_e('Payment Information', 'banglapay-vendor-payments'); ?></h3>
        
        <div class="banglapay-info-grid">
            <div class="banglapay-info-item">
                <strong><?php esc_html_e('Payment Method', 'banglapay-vendor-payments'); ?></strong>
                <span><?php echo esc_html(ucfirst($payment_method)); ?></span>
            </div>
            
            <div class="banglapay-info-item">
                <strong><?php esc_html_e('Your Account', 'banglapay-vendor-payments'); ?></strong>
                <span><?php echo esc_html($vendor_account); ?></span>
            </div>
            
            <?php if ($transaction_id): ?>
            <div class="banglapay-info-item">
                <strong><?php esc_html_e('Transaction ID', 'banglapay-vendor-payments'); ?></strong>
                <span style="font-family: monospace;"><?php echo esc_html($transaction_id); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($sender_number): ?>
            <div class="banglapay-info-item">
                <strong><?php esc_html_e("Customer's Number", 'banglapay-vendor-payments'); ?></strong>
                <span><?php echo esc_html($sender_number); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($payment_date): ?>
            <div class="banglapay-info-item">
                <strong><?php esc_html_e('Submitted On', 'banglapay-vendor-payments'); ?></strong>
                <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($payment_date))); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($payment_notes): ?>
        <div class="banglapay-info-item" style="margin: 15px 0;">
            <strong><?php esc_html_e('Customer Notes', 'banglapay-vendor-payments'); ?></strong>
            <span style="font-size: 14px; font-weight: normal;"><?php echo esc_html($payment_notes); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($receipt_file): ?>
        <div class="banglapay-receipt">
            <strong style="display: block; margin-bottom: 10px;"><?php esc_html_e('Payment Receipt:', 'banglapay-vendor-payments'); ?></strong>
            <?php
            $file_ext = strtolower(pathinfo($receipt_file, PATHINFO_EXTENSION));
            if (in_array($file_ext, array('jpg', 'jpeg', 'png', 'gif'))): ?>
                <a href="<?php echo esc_url($receipt_file); ?>" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo esc_url($receipt_file); ?>" 
                         alt="<?php esc_attr_e('Payment Receipt', 'banglapay-vendor-payments'); ?>" 
                         style="max-width: 400px;">
                </a>
            <?php else: ?>
                <a href="<?php echo esc_url($receipt_file); ?>" target="_blank" rel="noopener noreferrer" class="button">
                    <?php esc_html_e('View Receipt (PDF)', 'banglapay-vendor-payments'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <hr style="margin: 20px 0;">
        
        <div>
            <strong><?php esc_html_e('Verification Status:', 'banglapay-vendor-payments'); ?></strong>
            <span class="banglapay-status-badge status-<?php echo esc_attr($verification_status); ?>">
                <?php echo esc_html(ucfirst($verification_status)); ?>
            </span>
        </div>
        
        <?php if ($verification_status === 'pending'): ?>
        <div class="banglapay-verify-buttons">
            <p><strong><?php esc_html_e('Verify this payment:', 'banglapay-vendor-payments'); ?></strong></p>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                <?php 
                printf(
                    /* translators: 1: payment method name, 2: account number, 3: transaction ID */
                    esc_html__('Please check your %1$s account (%2$s) to confirm you received payment with transaction ID: %3$s', 'banglapay-vendor-payments'),
                    esc_html(ucfirst($payment_method)),
                    esc_html($vendor_account),
                    '<strong>' . esc_html($transaction_id) . '</strong>'
                );
                ?>
            </p>
            <button type="button" class="btn-verify" onclick="banglapayVerifyPayment(<?php echo absint($order->get_id()); ?>, 'verified')">
                ✓ <?php esc_html_e('Payment Received - Approve Order', 'banglapay-vendor-payments'); ?>
            </button>
            <button type="button" class="btn-reject" onclick="banglapayVerifyPayment(<?php echo absint($order->get_id()); ?>, 'rejected')">
                ✗ <?php esc_html_e('Payment Not Received - Reject', 'banglapay-vendor-payments'); ?>
            </button>
        </div>
        <?php else: ?>
        <div class="verification-history">
            <strong><?php esc_html_e('Verification Details:', 'banglapay-vendor-payments'); ?></strong>
            <p style="margin: 5px 0;">
                <?php esc_html_e('Status:', 'banglapay-vendor-payments'); ?> 
                <strong><?php echo esc_html(ucfirst($verification_status)); ?></strong>
                <?php if ($verification_date): ?>
                    <?php 
                    /* translators: %s: formatted date and time */
                    echo esc_html(sprintf(__('on %s', 'banglapay-vendor-payments'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($verification_date)))); 
                    ?>
                <?php endif; ?>
            </p>
            <?php if ($verification_notes): ?>
            <p style="margin: 5px 0;">
                <?php esc_html_e('Notes:', 'banglapay-vendor-payments'); ?> <?php echo esc_html($verification_notes); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    function banglapayVerifyPayment(orderId, status) {
        var statusText = status === 'verified' ? '<?php echo esc_js(__('approve', 'banglapay-vendor-payments')); ?>' : '<?php echo esc_js(__('reject', 'banglapay-vendor-payments')); ?>';
        var notes = prompt('<?php echo esc_js(__('Add notes about this verification (optional):', 'banglapay-vendor-payments')); ?>');
        
        if (notes === null) {
            return; // User cancelled
        }
        
        var confirmMsg = status === 'verified' 
            ? '<?php echo esc_js(__('Confirm that you RECEIVED the payment and want to APPROVE this order?', 'banglapay-vendor-payments')); ?>'
            : '<?php echo esc_js(__('Confirm that you DID NOT receive the payment and want to REJECT this order?', 'banglapay-vendor-payments')); ?>';
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        // Disable buttons
        jQuery('.btn-verify, .btn-reject').prop('disabled', true).css('opacity', '0.5');
        
        jQuery.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'banglapay_verify_payment',
                order_id: orderId,
                status: status,
                notes: notes,
                nonce: '<?php echo wp_create_nonce('banglapay_verify_' . $order->get_id()); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    alert('<?php echo esc_js(__('Payment verification successful! The page will now reload.', 'banglapay-vendor-payments')); ?>');
                    location.reload();
                } else {
                    var errorMsg = response && response.data && response.data.message 
                        ? response.data.message 
                        : '<?php echo esc_js(__('Unknown error occurred', 'banglapay-vendor-payments')); ?>';
                    alert('<?php echo esc_js(__('Error:', 'banglapay-vendor-payments')); ?> ' + errorMsg);
                    jQuery('.btn-verify, .btn-reject').prop('disabled', false).css('opacity', '1');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Connection error. Please try again.', 'banglapay-vendor-payments')); ?>');
                jQuery('.btn-verify, .btn-reject').prop('disabled', false).css('opacity', '1');
            }
        });
    }
    </script>
    <?php
}

/**
 * AJAX handler for payment verification
 */
add_action('wp_ajax_banglapay_verify_payment', 'banglapay_handle_payment_verification');
function banglapay_handle_payment_verification() {
    // Clean output buffer
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    ob_start();
    @ini_set('display_errors', 0);
    
    // Validate required POST data
    if (!isset($_POST['order_id']) || !isset($_POST['status']) || !isset($_POST['nonce'])) {
        ob_clean();
        wp_send_json_error(array('message' => __('Missing required data', 'banglapay-vendor-payments')));
        die();
    }
    
    $order_id = absint($_POST['order_id']);
    $status = sanitize_text_field($_POST['status']);
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'banglapay_verify_' . $order_id)) {
        ob_clean();
        wp_send_json_error(array('message' => __('Security check failed', 'banglapay-vendor-payments')));
        die();
    }
    
    // Validate status
    if (!in_array($status, array('verified', 'rejected'))) {
        ob_clean();
        wp_send_json_error(array('message' => __('Invalid status', 'banglapay-vendor-payments')));
        die();
    }
    
    // Get order
    $order = wc_get_order($order_id);
    if (!$order) {
        ob_clean();
        wp_send_json_error(array('message' => __('Invalid order', 'banglapay-vendor-payments')));
        die();
    }
    
    // Check user permission
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        ob_clean();
        wp_send_json_error(array('message' => __('You must be logged in', 'banglapay-vendor-payments')));
        die();
    }
    
    // Get order vendor ID
    $order_vendor_id = $order->get_meta('_banglapay_vendor_id');
    
    // If not set, try to get from Dokan
    if (!$order_vendor_id && function_exists('dokan_get_seller_id_by_order')) {
        $order_vendor_id = dokan_get_seller_id_by_order($order_id);
    }
    
    // If still not set, get from product author
    if (!$order_vendor_id) {
        $items = $order->get_items();
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            if ($product_id) {
                $order_vendor_id = get_post_field('post_author', $product_id);
                break;
            }
        }
    }
    
    // Verify user has permission
    if ($current_user_id != $order_vendor_id && !current_user_can('manage_woocommerce')) {
        ob_clean();
        wp_send_json_error(array('message' => __('You do not have permission to verify this order', 'banglapay-vendor-payments')));
        die();
    }
    
    // Update order meta
    $order->update_meta_data('_banglapay_verification_status', $status);
    $order->update_meta_data('_banglapay_verification_notes', $notes);
    $order->update_meta_data('_banglapay_verification_date', current_time('mysql'));
    $order->update_meta_data('_banglapay_verified_by', $current_user_id);
    
    // Update order status
    if ($status === 'verified') {
        $order->update_status(
            'processing',
            __('Payment verified by vendor.', 'banglapay-vendor-payments') . ' ' . $notes
        );
    } else {
        $order->update_status(
            'failed',
            __('Payment rejected by vendor.', 'banglapay-vendor-payments') . ' ' . $notes
        );
    }
    
    $order->save();
    
    // Update transaction record
    global $wpdb;
    $wpdb->update(
        $wpdb->prefix . 'banglapay_transactions',
        array('status' => $status),
        array('order_id' => $order_id),
        array('%s'),
        array('%d')
    );
    
    ob_clean();
    wp_send_json_success(array('message' => __('Payment status updated successfully', 'banglapay-vendor-payments')));
    die();
}

/**
 * Add payment status badge CSS to Dokan dashboard
 *
 * @param array $urls Dashboard URLs
 * @return array Dashboard URLs
 */
add_filter('dokan_get_dashboard_nav', 'banglapay_add_verification_css', 999);
function banglapay_add_verification_css($urls) {
    if (wp_doing_ajax()) {
        return $urls;
    }
    ?>
    <style>
    .banglapay-payment-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        margin-left: 5px;
    }
    .badge-pending { background: #f39c12; color: white; }
    .badge-verified { background: #27ae60; color: white; }
    .badge-rejected { background: #e74c3c; color: white; }
    </style>
    <?php
    return $urls;
}

/**
 * Add payment badge to Dokan order list
 *
 * @param mixed $order Order object or ID
 */
add_action('dokan_order_inside_content', 'banglapay_add_payment_badge_to_order', 20);
function banglapay_add_payment_badge_to_order($order) {
    if (!is_a($order, 'WC_Order')) {
        $order = wc_get_order($order);
    }
    
    if (!$order || !is_a($order, 'WC_Order')) {
        return;
    }
    
    $payment_method = $order->get_meta('_banglapay_payment_method');
    if (!$payment_method) {
        return;
    }
    
    $verification_status = $order->get_meta('_banglapay_verification_status');
    if (empty($verification_status)) {
        $verification_status = 'pending';
    }
    
    $labels = array(
        'pending' => __('Payment Pending', 'banglapay-vendor-payments'),
        'verified' => __('Payment Verified', 'banglapay-vendor-payments'),
        'rejected' => __('Payment Rejected', 'banglapay-vendor-payments')
    );
    
    echo '<span class="banglapay-payment-badge badge-' . esc_attr($verification_status) . '">';
    echo esc_html($labels[$verification_status]);
    echo '</span>';
}