<?php
/**
 * Vendor Payment Settings Page
 * Each vendor can manage their own payment accounts
 *
 * @package BanglaPay
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Add BanglaPay menu to WordPress admin
 */
add_action('admin_menu', 'banglapay_add_vendor_menu');
function banglapay_add_vendor_menu() {
    add_menu_page(
        __('BanglaPay Settings', 'banglapay-vendor-payments'),
        __('banglapay-vendor-payments', 'banglapay-vendor-payments'),
        'edit_posts', // Allow vendors/shop managers to access
        'banglapay-settings',
        'banglapay_render_settings_page',
        'dashicons-money-alt',
        56
    );
}

/**
 * Render vendor settings page
 */
function banglapay_render_settings_page() {
    // Security check
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'banglapay-vendor-payments'));
    }
    
    // Get current user ID (the vendor/shop owner who is logged in)
    $vendor_id = get_current_user_id();
    
    // Handle form submission
    if (isset($_POST['banglapay_save_settings'])) {
        if (!check_admin_referer('banglapay_settings', 'banglapay_nonce')) {
            wp_die(__('Security check failed.', 'banglapay-vendor-payments'));
        }
        
        banglapay_save_vendor_settings($vendor_id);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'banglapay-vendor-payments') . '</p></div>';
    }
    
    // Get current settings for THIS vendor
    $settings = banglapay_get_vendor_settings($vendor_id);
    
    $current_user = wp_get_current_user();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('BanglaPay Payment Settings', 'banglapay-vendor-payments'); ?></h1>
        <p>
            <strong><?php esc_html_e('Vendor:', 'banglapay-vendor-payments'); ?></strong> 
            <?php echo esc_html($current_user->display_name); ?> 
            <?php 
            /* translators: %d: user ID */
            printf(esc_html__('(User ID: %d)', 'banglapay-vendor-payments'), absint($vendor_id)); 
            ?>
        </p>
        <p><?php esc_html_e('Configure your payment accounts. Customers will send payments directly to these accounts when they buy YOUR products.', 'banglapay-vendor-payments'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('banglapay_settings', 'banglapay_nonce'); ?>
            
            <table class="form-table" role="presentation">
                <!-- bKash Settings -->
                <tr>
                    <th colspan="2">
                        <h2 style="color:#E2136E; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <?php esc_html_e('bKash Settings', 'banglapay-vendor-payments'); ?>
                        </h2>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bkash_enabled"><?php esc_html_e('Enable bKash', 'banglapay-vendor-payments'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="bkash_enabled" id="bkash_enabled" value="1" 
                            <?php checked($settings['bkash_enabled'], 1); ?> />
                        <label for="bkash_enabled"><?php esc_html_e('Accept bKash payments', 'banglapay-vendor-payments'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bkash_account">
                            <?php esc_html_e('bKash Account Number', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="bkash_account" id="bkash_account" 
                            value="<?php echo esc_attr($settings['bkash_account']); ?>" 
                            class="regular-text" placeholder="01XXXXXXXXX" />
                        <p class="description"><?php esc_html_e('Your bKash personal or merchant account number', 'banglapay-vendor-payments'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bkash_type"><?php esc_html_e('Account Type', 'banglapay-vendor-payments'); ?></label>
                    </th>
                    <td>
                        <select name="bkash_type" id="bkash_type">
                            <option value="personal" <?php selected($settings['bkash_type'], 'personal'); ?>><?php esc_html_e('Personal', 'banglapay-vendor-payments'); ?></option>
                            <option value="merchant" <?php selected($settings['bkash_type'], 'merchant'); ?>><?php esc_html_e('Merchant', 'banglapay-vendor-payments'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <!-- Nagad Settings -->
                <tr>
                    <th colspan="2">
                        <h2 style="color:#EB5628; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <?php esc_html_e('Nagad Settings', 'banglapay-vendor-payments'); ?>
                        </h2>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="nagad_enabled"><?php esc_html_e('Enable Nagad', 'banglapay-vendor-payments'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="nagad_enabled" id="nagad_enabled" value="1" 
                            <?php checked($settings['nagad_enabled'], 1); ?> />
                        <label for="nagad_enabled"><?php esc_html_e('Accept Nagad payments', 'banglapay-vendor-payments'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="nagad_account">
                            <?php esc_html_e('Nagad Account Number', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="nagad_account" id="nagad_account" 
                            value="<?php echo esc_attr($settings['nagad_account']); ?>" 
                            class="regular-text" placeholder="01XXXXXXXXX" />
                        <p class="description"><?php esc_html_e('Your Nagad account number', 'banglapay-vendor-payments'); ?></p>
                    </td>
                </tr>
                
                <!-- Rocket Settings -->
                <tr>
                    <th colspan="2">
                        <h2 style="color:#8C3A9B; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <?php esc_html_e('Rocket Settings', 'banglapay-vendor-payments'); ?>
                        </h2>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="rocket_enabled"><?php esc_html_e('Enable Rocket', 'banglapay-vendor-payments'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="rocket_enabled" id="rocket_enabled" value="1" 
                            <?php checked($settings['rocket_enabled'], 1); ?> />
                        <label for="rocket_enabled"><?php esc_html_e('Accept Rocket payments', 'banglapay-vendor-payments'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="rocket_account">
                            <?php esc_html_e('Rocket Account Number', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="rocket_account" id="rocket_account" 
                            value="<?php echo esc_attr($settings['rocket_account']); ?>" 
                            class="regular-text" placeholder="01XXXXXXXXX" />
                        <p class="description"><?php esc_html_e('Your Rocket account number', 'banglapay-vendor-payments'); ?></p>
                    </td>
                </tr>
                
                <!-- Upay Settings -->
                <tr>
                    <th colspan="2">
                        <h2 style="color:#0078BE; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <?php esc_html_e('Upay Settings', 'banglapay-vendor-payments'); ?>
                        </h2>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="upay_enabled"><?php esc_html_e('Enable Upay', 'banglapay-vendor-payments'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="upay_enabled" id="upay_enabled" value="1" 
                            <?php checked($settings['upay_enabled'], 1); ?> />
                        <label for="upay_enabled"><?php esc_html_e('Accept Upay payments', 'banglapay-vendor-payments'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="upay_account">
                            <?php esc_html_e('Upay Account Number', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="upay_account" id="upay_account" 
                            value="<?php echo esc_attr($settings['upay_account']); ?>" 
                            class="regular-text" placeholder="01XXXXXXXXX" />
                        <p class="description"><?php esc_html_e('Your Upay account number', 'banglapay-vendor-payments'); ?></p>
                    </td>
                </tr>
                
                <!-- Bank Transfer Settings -->
                <tr>
                    <th colspan="2">
                        <h2 style="color:#0066CC; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <?php esc_html_e('Bank Transfer Settings', 'banglapay-vendor-payments'); ?>
                        </h2>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bank_enabled"><?php esc_html_e('Enable Bank Transfer', 'banglapay-vendor-payments'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="bank_enabled" id="bank_enabled" value="1" 
                            <?php checked($settings['bank_enabled'], 1); ?> />
                        <label for="bank_enabled"><?php esc_html_e('Accept bank transfer payments', 'banglapay-vendor-payments'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bank_name">
                            <?php esc_html_e('Bank Name', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="bank_name" id="bank_name" 
                            value="<?php echo esc_attr($settings['bank_name']); ?>" 
                            class="regular-text" placeholder="<?php esc_attr_e('e.g., Dutch Bangla Bank', 'banglapay-vendor-payments'); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bank_account_name">
                            <?php esc_html_e('Account Name', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="bank_account_name" id="bank_account_name" 
                            value="<?php echo esc_attr($settings['bank_account_name']); ?>" 
                            class="regular-text" placeholder="<?php esc_attr_e('Account holder name', 'banglapay-vendor-payments'); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bank_account_number">
                            <?php esc_html_e('Account Number', 'banglapay-vendor-payments'); ?> 
                            <span style="color:red;">*</span>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="bank_account_number" id="bank_account_number" 
                            value="<?php echo esc_attr($settings['bank_account_number']); ?>" 
                            class="regular-text" placeholder="<?php esc_attr_e('Bank account number', 'banglapay-vendor-payments'); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bank_branch"><?php esc_html_e('Branch Name', 'banglapay-vendor-payments'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="bank_branch" id="bank_branch" 
                            value="<?php echo esc_attr($settings['bank_branch']); ?>" 
                            class="regular-text" placeholder="<?php esc_attr_e('Branch name (optional)', 'banglapay-vendor-payments'); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bank_routing"><?php esc_html_e('Routing Number', 'banglapay-vendor-payments'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="bank_routing" id="bank_routing" 
                            value="<?php echo esc_attr($settings['bank_routing']); ?>" 
                            class="regular-text" placeholder="<?php esc_attr_e('Routing number (optional)', 'banglapay-vendor-payments'); ?>" />
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="banglapay_save_settings" class="button button-primary" 
                    value="<?php esc_attr_e('Save My Settings', 'banglapay-vendor-payments'); ?>" />
            </p>
        </form>
        
        <div class="card" style="max-width:800px; margin-top:30px; padding: 20px;">
            <h2><?php esc_html_e('My Payment Statistics', 'banglapay-vendor-payments'); ?></h2>
            <?php banglapay_display_payment_stats($vendor_id); ?>
        </div>
    </div>
    <?php
}

/**
 * Save vendor payment settings
 *
 * @param int $vendor_id Vendor user ID
 */
function banglapay_save_vendor_settings($vendor_id) {
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
 * Get vendor payment settings
 *
 * @param int $vendor_id Vendor user ID
 * @return array Payment settings array
 */
function banglapay_get_vendor_settings($vendor_id) {
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
 * Display payment statistics for vendor
 *
 * @param int $vendor_id Vendor user ID
 */
function banglapay_display_payment_stats($vendor_id) {
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
    
    echo '<table class="wp-list-table widefat fixed striped">';
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