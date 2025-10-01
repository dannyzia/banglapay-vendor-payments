<?php
/**
 * BanglaPay File Handler
 * Handles payment receipt/screenshot uploads
 *
 * @package BanglaPay
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class BanglaPay_File_Handler {
    /**
     * Single instance of the class
     *
     * @var BanglaPay_File_Handler
     */
    private static $instance = null;
    
    /**
     * Get single instance
     *
     * @return BanglaPay_File_Handler
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
    private function __construct() {
        // Handle file upload via AJAX
        add_action('wp_ajax_banglapay_upload_receipt', array($this, 'handle_file_upload'));
        add_action('wp_ajax_nopriv_banglapay_upload_receipt', array($this, 'handle_file_upload'));
    }
    
    /**
     * Log debug messages only when WP_DEBUG is enabled
     *
     * @param string $message Debug message
     */
    private function log_debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('BanglaPay: ' . $message);
        }
    }
    
    /**
     * Handle file upload via AJAX
     */
    public function handle_file_upload() {
        // Clean all output buffers to prevent JSON corruption
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        // Disable error display
        @ini_set('display_errors', 0);
        
        $this->log_debug('File upload request received');
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'banglapay_upload_receipt')) {
            $this->log_debug('File upload: Nonce verification failed');
            ob_clean();
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'banglapay-vendor-payments')
            ));
            die();
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : '';
        
        $this->log_debug('File upload: Order ID ' . $order_id . ', Method: ' . $method);
        
        // Validate order ID and method
        if (empty($order_id) || empty($method)) {
            ob_clean();
            wp_send_json_error(array(
                'message' => __('Invalid request parameters.', 'banglapay-vendor-payments')
            ));
            die();
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] !== UPLOAD_ERR_OK) {
            $error_code = isset($_FILES['receipt_file']['error']) ? $_FILES['receipt_file']['error'] : 'No file';
            $this->log_debug('File upload error: ' . $error_code);
            ob_clean();
            wp_send_json_error(array(
                'message' => __('File upload failed. Please try again.', 'banglapay-vendor-payments')
            ));
            die();
        }
        
        $file = $_FILES['receipt_file'];
        
        // Validate file type (images and PDFs only)
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf');
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $this->log_debug('Invalid file type: ' . $file_type);
            ob_clean();
            wp_send_json_error(array(
                'message' => __('Only images (JPG, PNG, GIF) and PDF files are allowed.', 'banglapay-vendor-payments')
            ));
            die();
        }
        
        // Validate file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_size) {
            $this->log_debug('File too large: ' . $file['size'] . ' bytes');
            ob_clean();
            wp_send_json_error(array(
                'message' => __('File size must be less than 5MB.', 'banglapay-vendor-payments')
            ));
            die();
        }
        
        $this->log_debug('File validation passed');
        
        // Verify order exists and user has permission
        $order = wc_get_order($order_id);
        if (!$order) {
            ob_clean();
            wp_send_json_error(array(
                'message' => __('Invalid order.', 'banglapay-vendor-payments')
            ));
            die();
        }
        
        // Check if current user owns this order
        if (get_current_user_id() !== $order->get_customer_id() && !current_user_can('manage_woocommerce')) {
            ob_clean();
            wp_send_json_error(array(
                'message' => __('You do not have permission to upload files for this order.', 'banglapay-vendor-payments')
            ));
            die();
        }
        
        // Upload file
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload_overrides = array(
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) use ($order_id, $method) {
                return 'banglapay-receipt-order-' . $order_id . '-' . $method . '-' . time() . $ext;
            }
        );
        
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            $this->log_debug('File uploaded successfully: ' . $movefile['url']);
            
            // Save file URL to order meta
            $order->update_meta_data('_' . $method . '_receipt_file', $movefile['url']);
            $order->update_meta_data('_' . $method . '_receipt_filename', basename($movefile['file']));
            $order->save();
            
            $this->log_debug('File URL saved to order meta');
            
            // Clean buffer before JSON response
            ob_clean();
            
            wp_send_json_success(array(
                'message' => __('Receipt uploaded successfully!', 'banglapay-vendor-payments'),
                'file_url' => $movefile['url']
            ));
        } else {
            $error_message = isset($movefile['error']) ? $movefile['error'] : __('Unknown upload error', 'banglapay-vendor-payments');
            $this->log_debug('Upload error: ' . $error_message);
            ob_clean();
            wp_send_json_error(array(
                'message' => $error_message
            ));
        }
        
        die();
    }
    
    /**
     * Get uploaded receipt URL for an order
     *
     * @param int $order_id Order ID
     * @param string $method Payment method
     * @return string Receipt URL or empty string
     */
    public static function get_receipt_url($order_id, $method) {
        $order = wc_get_order($order_id);
        return $order ? $order->get_meta('_' . $method . '_receipt_file') : '';
    }
}

// Initialize
BanglaPay_File_Handler::instance();

/**
 * Render file upload field in payment form
 *
 * @param int $order_id Order ID
 * @param string $method_id Payment method ID
 */
function banglapay_render_file_upload_field($order_id, $method_id) {
    ?>
    <p>
        <label for="receipt_file">
            <?php esc_html_e('Payment Receipt/Screenshot (Optional):', 'banglapay-vendor-payments'); ?>
        </label>
        <input 
            type="file" 
            id="receipt_file" 
            name="receipt_file" 
            accept="image/*,.pdf"
            style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;"
        />
        <small><?php esc_html_e('Upload a screenshot or PDF of your payment receipt (Max 5MB, JPG/PNG/PDF)', 'banglapay-vendor-payments'); ?></small>
    </p>
    <div id="upload-progress" style="display:none; margin-top: 10px;">
        <div style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
            <div id="upload-progress-bar" style="background: #4CAF50; height: 100%; width: 0%; transition: width 0.3s;"></div>
        </div>
        <small id="upload-status" style="color: #666;"><?php esc_html_e('Uploading...', 'banglapay-vendor-payments'); ?></small>
    </div>
    
    <script>
    var uploadedFileUrl = null;
    
    document.getElementById('receipt_file').addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        
        console.log('BanglaPay: File selected, uploading...', file.name);
        
        document.getElementById('upload-progress').style.display = 'block';
        document.getElementById('upload-status').textContent = '<?php echo esc_js(__('Uploading...', 'banglapay-vendor-payments')); ?>';
        
        var formData = new FormData();
        formData.append('action', 'banglapay_upload_receipt');
        formData.append('receipt_file', file);
        formData.append('order_id', '<?php echo esc_js($order_id); ?>');
        formData.append('method', '<?php echo esc_js($method_id); ?>');
        formData.append('nonce', '<?php echo wp_create_nonce('banglapay_upload_receipt'); ?>');
        
        jQuery.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percent = (e.loaded / e.total) * 100;
                        document.getElementById('upload-progress-bar').style.width = percent + '%';
                    }
                });
                return xhr;
            },
            success: function(response) {
                console.log('BanglaPay: Upload response', response);
                if (response.success) {
                    uploadedFileUrl = response.data.file_url;
                    document.getElementById('upload-status').textContent = '<?php echo esc_js(__('Upload complete!', 'banglapay-vendor-payments')); ?>';
                    document.getElementById('upload-status').style.color = '#4CAF50';
                    setTimeout(function() {
                        document.getElementById('upload-progress').style.display = 'none';
                    }, 2000);
                } else {
                    document.getElementById('upload-status').textContent = '<?php echo esc_js(__('Upload failed:', 'banglapay-vendor-payments')); ?> ' + response.data.message;
                    document.getElementById('upload-status').style.color = '#dc3545';
                }
            },
            error: function(xhr, status, error) {
                console.error('BanglaPay: Upload error', xhr, status, error);
                document.getElementById('upload-status').textContent = '<?php echo esc_js(__('Upload error. Please try again.', 'banglapay-vendor-payments')); ?>';
                document.getElementById('upload-status').style.color = '#dc3545';
            }
        });
    });
    </script>
    <?php
}