<?php
/**
 * Dokan Integration for BanglaPay
 * Adds BanglaPay settings to Dokan vendor dashboard
 *
 * @package BanglaPay
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Add BanglaPay menu to Dokan dashboard
 *
 * @param array $urls Dashboard menu URLs
 * @return array Modified menu URLs
 */
add_filter('dokan_get_dashboard_nav', 'banglapay_add_dokan_menu', 20);
function banglapay_add_dokan_menu($urls) {
    // Check if Dokan functions exist
    if (!function_exists('dokan_get_navigation_url')) {
        return $urls;
    }
    
    $urls['banglapay-vendor-payments'] = array(
        'title' => __('Payment Settings', 'banglapay-vendor-payments'),
        'icon'  => '<i class="fas fa-money-bill-wave"></i>',
        'url'   => dokan_get_navigation_url('banglapay-vendor-payments'),
        'pos'   => 80,
    );
    
    return $urls;
}

/**
 * Register the query var for Dokan
 *
 * @param array $query_vars Query variables
 * @return array Modified query variables
 */
add_filter('dokan_query_var_filter', 'banglapay_add_dokan_query_var', 20);
function banglapay_add_dokan_query_var($query_vars) {
    $query_vars['banglapay-vendor-payments'] = 'banglapay-vendor-payments';
    return $query_vars;
}

/**
 * Load template for the BanglaPay page in Dokan
 *
 * @param array $query_vars Query variables
 */
add_action('dokan_load_custom_template', 'banglapay_load_dokan_template', 20);
function banglapay_load_dokan_template($query_vars) {
    if (isset($query_vars['banglapay-vendor-payments'])) {
        // Check if Dokan template functions exist
        if (!function_exists('dokan_get_template_part')) {
            echo '<p>' . esc_html__('Dokan is not active or template functions are not available.', 'banglapay-vendor-payments') . '</p>';
            return;
        }
        
        // Include Dokan header
        dokan_get_template_part('global/header', 'dashboard');
        
        // Render our settings page
        banglapay_render_dokan_settings_page();
        
        // Include Dokan footer
        dokan_get_template_part('global/footer', 'dashboard');
    }
}

/**
 * Register endpoint for rewrite rules
 *
 * @param array $query_vars Query variables
 * @return array Query variables
 */
add_filter('dokan_rewrite_rules_loaded', 'banglapay_register_dokan_endpoint', 20);
function banglapay_register_dokan_endpoint($query_vars) {
    add_rewrite_endpoint('banglapay-vendor-payments', EP_PAGES);
    return $query_vars;
}

/**
 * Render the settings page for vendors in Dokan dashboard
 */
function banglapay_render_dokan_settings_page() {
    // Check if Dokan function exists
    if (!function_exists('dokan_get_current_user_id')) {
        echo '<p>' . esc_html__('Dokan is not active.', 'banglapay-vendor-payments') . '</p>';
        return;
    }
    
    // Get current vendor ID
    $vendor_id = dokan_get_current_user_id();
    
    // Security check
    if (!$vendor_id) {
        echo '<p>' . esc_html__('Access denied.', 'banglapay-vendor-payments') . '</p>';
        return;
    }
    
    // Handle form submission
    $message = '';
    if (isset($_POST['banglapay_save_settings'])) {
        if (!wp_verify_nonce($_POST['banglapay_nonce'], 'banglapay_settings_' . $vendor_id)) {
            wp_die(__('Security check failed.', 'banglapay-vendor-payments'));
        }
        
        banglapay_save_vendor_settings_dokan($vendor_id);
        $message = '<div class="dokan-alert dokan-alert-success">' . esc_html__('Payment settings saved successfully!', 'banglapay-vendor-payments') . '</div>';
    }
    
    // Get current settings
    $settings = banglapay_get_vendor_settings_dokan($vendor_id);
    
    ?>
    <style>
    .banglapay-settings-form .dokan-form-group {
        margin-bottom: 20px;
    }
    .banglapay-settings-form .dokan-form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .banglapay-settings-form .help-block {
        font-size: 13px;
        color: #666;
        margin-top: 5px;
    }
    .banglapay-method-section {
        background: #fff;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e5e5e5;
        border-radius: 5px;
    }
    .banglapay-method-section h2 {
        font-size: 20px;
        margin-top: 0;
        padding-bottom: 10px;
    }
    .info-box {
        background: #e7f5fe;
        padding: 15px;
        border-left: 4px solid #2196F3;
        margin-bottom: 20px;
    }
    </style>
    
    <article class="dokan-banglapay-settings-area">
        <header class="dokan-dashboard-header">
            <h1 class="entry-title"><?php esc_html_e('Payment Settings', 'banglapay-vendor-payments'); ?></h1>
        </header>

        <div class="dokan-banglapay-settings-content">
            <?php echo $message; ?>
            
            <div class="info-box">
                <strong><?php esc_html_e('Configure your payment accounts.', 'banglapay-vendor-payments'); ?></strong> 
                <?php esc_html_e('Customers will send payments directly to these accounts when they buy your products.', 'banglapay-vendor-payments'); ?>
            </div>

            <form method="post" action="" class="banglapay-settings-form">
                <?php wp_nonce_field('banglapay_settings_' . $vendor_id, 'banglapay_nonce'); ?>
                
                <!-- bKash Settings -->
                <div class="banglapay-method-section">
                    <h2 style="color: #E2136E; border-bottom: 2px solid #E2136E;">
                        <?php esc_html_e('bKash Settings', 'banglapay-vendor-payments'); ?>
                    </h2>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <input type="checkbox" name="bkash_enabled" value="1" 
                                <?php checked($settings['bkash_enabled'], 1); ?> />
                            <?php esc_html_e('Enable bKash Payments', 'banglapay-vendor-payments'); ?>
                        </label>
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <?php esc_html_e('bKash Account Number', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="bkash_account" 
                            value="<?php echo esc_attr($settings['bkash_account']); ?>" 
                            class="dokan-form-control" placeholder="01XXXXXXXXX" />
                        <p class="help-block"><?php esc_html_e('Your bKash personal or merchant account number', 'banglapay-vendor-payments'); ?></p>
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label"><?php esc_html_e('Account Type', 'banglapay-vendor-payments'); ?></label>
                        <select name="bkash_type" class="dokan-form-control">
                            <option value="personal" <?php selected($settings['bkash_type'], 'personal'); ?>><?php esc_html_e('Personal', 'banglapay-vendor-payments'); ?></option>
                            <option value="merchant" <?php selected($settings['bkash_type'], 'merchant'); ?>><?php esc_html_e('Merchant', 'banglapay-vendor-payments'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Nagad Settings -->
                <div class="banglapay-method-section">
                    <h2 style="color: #EB5628; border-bottom: 2px solid #EB5628;">
                        <?php esc_html_e('Nagad Settings', 'banglapay-vendor-payments'); ?>
                    </h2>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <input type="checkbox" name="nagad_enabled" value="1" 
                                <?php checked($settings['nagad_enabled'], 1); ?> />
                            <?php esc_html_e('Enable Nagad Payments', 'banglapay-vendor-payments'); ?>
                        </label>
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <?php esc_html_e('Nagad Account Number', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="nagad_account" 
                            value="<?php echo esc_attr($settings['nagad_account']); ?>" 
                            class="dokan-form-control" placeholder="01XXXXXXXXX" />
                        <p class="help-block"><?php esc_html_e('Your Nagad account number', 'banglapay-vendor-payments'); ?></p>
                    </div>
                </div>
                
                <!-- Rocket Settings -->
                <div class="banglapay-method-section">
                    <h2 style="color: #8C3A9B; border-bottom: 2px solid #8C3A9B;">
                        <?php esc_html_e('Rocket Settings', 'banglapay-vendor-payments'); ?>
                    </h2>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <input type="checkbox" name="rocket_enabled" value="1" 
                                <?php checked($settings['rocket_enabled'], 1); ?> />
                            <?php esc_html_e('Enable Rocket Payments', 'banglapay-vendor-payments'); ?>
                        </label>
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <?php esc_html_e('Rocket Account Number', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="rocket_account" 
                            value="<?php echo esc_attr($settings['rocket_account']); ?>" 
                            class="dokan-form-control" placeholder="01XXXXXXXXX" />
                        <p class="help-block"><?php esc_html_e('Your Rocket account number', 'banglapay-vendor-payments'); ?></p>
                    </div>
                </div>
                
                <!-- Upay Settings -->
                <div class="banglapay-method-section">
                    <h2 style="color: #0078BE; border-bottom: 2px solid #0078BE;">
                        <?php esc_html_e('Upay Settings', 'banglapay-vendor-payments'); ?>
                    </h2>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <input type="checkbox" name="upay_enabled" value="1" 
                                <?php checked($settings['upay_enabled'], 1); ?> />
                            <?php esc_html_e('Enable Upay Payments', 'banglapay-vendor-payments'); ?>
                        </label>
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <?php esc_html_e('Upay Account Number', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="upay_account" 
                            value="<?php echo esc_attr($settings['upay_account']); ?>" 
                            class="dokan-form-control" placeholder="01XXXXXXXXX" />
                        <p class="help-block"><?php esc_html_e('Your Upay account number', 'banglapay-vendor-payments'); ?></p>
                    </div>
                </div>
                
                <!-- Bank Transfer Settings -->
                <div class="banglapay-method-section">
                    <h2 style="color: #0066CC; border-bottom: 2px solid #0066CC;">
                        <?php esc_html_e('Bank Transfer Settings', 'banglapay-vendor-payments'); ?>
                    </h2>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <input type="checkbox" name="bank_enabled" value="1" 
                                <?php checked($settings['bank_enabled'], 1); ?> />
                            <?php esc_html_e('Enable Bank Transfer Payments', 'banglapay-vendor-payments'); ?>
                        </label>
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <?php esc_html_e('Bank Name', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="bank_name" 
                            value="<?php echo esc_attr($settings['bank_name']); ?>" 
                            class="dokan-form-control" placeholder="<?php esc_attr_e('e.g., Dutch Bangla Bank', 'banglapay-vendor-payments'); ?>" />
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <?php esc_html_e('Account Holder Name', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="bank_account_name" 
                            value="<?php echo esc_attr($settings['bank_account_name']); ?>" 
                            class="dokan-form-control" placeholder="<?php esc_attr_e('Your name as per bank records', 'banglapay-vendor-payments'); ?>" />
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label">
                            <?php esc_html_e('Account Number', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="bank_account_number" 
                            value="<?php echo esc_attr($settings['bank_account_number']); ?>" 
                            class="dokan-form-control" placeholder="<?php esc_attr_e('Bank account number', 'banglapay-vendor-payments'); ?>" />
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label"><?php esc_html_e('Branch Name', 'banglapay-vendor-payments'); ?></label>
                        <input type="text" name="bank_branch" 
                            value="<?php echo esc_attr($settings['bank_branch']); ?>" 
                            class="dokan-form-control" placeholder="<?php esc_attr_e('Branch name (optional)', 'banglapay-vendor-payments'); ?>" />
                    </div>
                    
                    <div class="dokan-form-group">
                        <label class="dokan-form-label"><?php esc_html_e('Routing Number', 'banglapay-vendor-payments'); ?></label>
                        <input type="text" name="bank_routing" 
                            value="<?php echo esc_attr($settings['bank_routing']); ?>" 
                            class="dokan-form-control" placeholder="<?php esc_attr_e('Routing number (optional)', 'banglapay-vendor-payments'); ?>" />
                    </div>
                </div>
                
                <div class="dokan-form-group">
                    <input type="submit" name="banglapay_save_settings" 
                        class="dokan-btn dokan-btn-success dokan-btn-lg" 
                        value="<?php esc_attr_e('Save Payment Settings', 'banglapay-vendor-payments'); ?>" />
                </div>
            </form>
            
            <!-- Payment Statistics -->
            <div class="banglapay-method-section" style="margin-top: 30px;">
                <h2 style="margin-top: 0;"><?php esc_html_e('My Payment Statistics', 'banglapay-vendor-payments'); ?></h2>
                <?php banglapay_display_payment_stats_dokan($vendor_id); ?>
            </div>
        </div>
    </article>
    <?php
}

/**
 * Save vendor settings for Dokan
 *
 * @param int $vendor_id Vendor user ID
 */
function banglapay_save_vendor_settings_dokan($vendor_id) {
    global $wpdb;
    
    $vendor_id = absint($vendor_id);
    $methods = array('bkash', 'nagad', 'rocket', 'upay', 'bank');
    
    foreach ($methods as $method) {
        $enabled = isset($_POST[$method . '_enabled']) ? 1 : 0;
        $account = isset($_POST[$method . '_account']) ? sanitize_text_field($_POST[$method . '_account']) : '';
        
        // Check if record exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}banglapay_vendor_settings 
             WHERE vendor_id = %d AND payment_method = %s",
            $vendor_id,
            $method
        ));
        
        $settings_data = array();
        
        // Add method-specific settings
        if ($method === 'bkash') {
            $settings_data['type'] = isset($_POST['bkash_type']) ? sanitize_text_field($_POST['bkash_type']) : 'personal';
        } elseif ($method === 'bank') {
            $settings_data['bank_name'] = isset($_POST['bank_name']) ? sanitize_text_field($_POST['bank_name']) : '';
            $settings_data['account_name'] = isset($_POST['bank_account_name']) ? sanitize_text_field($_POST['bank_account_name']) : '';
            $settings_data['branch'] = isset($_POST['bank_branch']) ? sanitize_text_field($_POST['bank_branch']) : '';
            $settings_data['routing'] = isset($_POST['bank_routing']) ? sanitize_text_field($_POST['bank_routing']) : '';
            $account = isset($_POST['bank_account_number']) ? sanitize_text_field($_POST['bank_account_number']) : '';
        }
        
        $data = array(
            'vendor_id' => $vendor_id,
            'payment_method' => $method,
            'account_number' => $account,
            'enabled' => $enabled,
            'settings' => wp_json_encode($settings_data)
        );
        
        if ($exists) {
            $wpdb->update(
                $wpdb->prefix . 'banglapay_vendor_settings',
                $data,
                array('id' => $exists),
                array('%d', '%s', '%s', '%d', '%s'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'banglapay_vendor_settings',
                $data,
                array('%d', '%s', '%s', '%d', '%s')
            );
        }
    }
}

/**
 * Get vendor settings for Dokan
 *
 * @param int $vendor_id Vendor user ID
 * @return array Settings array
 */
function banglapay_get_vendor_settings_dokan($vendor_id) {
    // Check if standard function exists
    if (function_exists('banglapay_get_vendor_settings')) {
        return banglapay_get_vendor_settings($vendor_id);
    }
    
    // Fallback implementation
    global $wpdb;
    
    $vendor_id = absint($vendor_id);
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}banglapay_vendor_settings WHERE vendor_id = %d",
        $vendor_id
    ), ARRAY_A);
    
    $settings = array();
    
    if (!empty($results)) {
        foreach ($results as $row) {
            $method = sanitize_key($row['payment_method']);
            $settings[$method . '_enabled'] = !empty($row['enabled']) ? 1 : 0;
            $settings[$method . '_account'] = sanitize_text_field($row['account_number']);
            
            if (!empty($row['settings'])) {
                $extra_settings = json_decode($row['settings'], true);
                if (is_array($extra_settings)) {
                    if ($method === 'bkash' && isset($extra_settings['type'])) {
                        $settings['bkash_type'] = sanitize_text_field($extra_settings['type']);
                    } elseif ($method === 'bank') {
                        $settings['bank_name'] = isset($extra_settings['bank_name']) ? sanitize_text_field($extra_settings['bank_name']) : '';
                        $settings['bank_account_name'] = isset($extra_settings['account_name']) ? sanitize_text_field($extra_settings['account_name']) : '';
                        $settings['bank_branch'] = isset($extra_settings['branch']) ? sanitize_text_field($extra_settings['branch']) : '';
                        $settings['bank_routing'] = isset($extra_settings['routing']) ? sanitize_text_field($extra_settings['routing']) : '';
                    }
                }
            }
        }
    }
    
    // Set defaults
    $defaults = array(
        'bkash_enabled' => 0, 'bkash_account' => '', 'bkash_type' => 'personal',
        'nagad_enabled' => 0, 'nagad_account' => '',
        'rocket_enabled' => 0, 'rocket_account' => '',
        'upay_enabled' => 0, 'upay_account' => '',
        'bank_enabled' => 0, 'bank_account_number' => '', 'bank_name' => '', 
        'bank_account_name' => '', 'bank_branch' => '', 'bank_routing' => ''
    );
    
    return array_merge($defaults, $settings);
}

/**
 * Display payment statistics for Dokan vendors
 *
 * @param int $vendor_id Vendor user ID
 */
function banglapay_display_payment_stats_dokan($vendor_id) {
    global $wpdb;
    
    $vendor_id = absint($vendor_id);
    
    $stats = $wpdb->get_results($wpdb->prepare(
        "SELECT payment_method, COUNT(*) as count, SUM(amount) as total 
         FROM {$wpdb->prefix}banglapay_transactions 
         WHERE vendor_id = %d 
         GROUP BY payment_method",
        $vendor_id
    ), ARRAY_A);
    
    if (empty($stats)) {
        echo '<p>' . esc_html__('No transactions yet.', 'banglapay-vendor-payments') . '</p>';
        return;
    }
    
    echo '<table class="dokan-table" style="width:100%;">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('Payment Method', 'banglapay-vendor-payments') . '</th>';
    echo '<th>' . esc_html__('Transactions', 'banglapay-vendor-payments') . '</th>';
    echo '<th>' . esc_html__('Total Amount', 'banglapay-vendor-payments') . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    
    foreach ($stats as $stat) {
        echo '<tr>';
        echo '<td>' . esc_html(ucfirst($stat['payment_method'])) . '</td>';
        echo '<td>' . esc_html($stat['count']) . '</td>';
        echo '<td>' . wc_price($stat['total']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
}

/**
 * Flush rewrite rules on plugin activation
 */
register_activation_hook(BANGLAPAY_PLUGIN_FILE, 'banglapay_flush_dokan_rewrite');
function banglapay_flush_dokan_rewrite() {
    flush_rewrite_rules();
}