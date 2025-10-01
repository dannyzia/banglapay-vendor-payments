<?php
/**
 * BanglaPay Error Handler
 * Handles error logging and reporting
 *
 * @package BanglaPay
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class BanglaPay_Error_Handler {
    /**
     * Single instance of the class
     *
     * @var BanglaPay_Error_Handler
     */
    private static $instance = null;
    
    /**
     * Get single instance
     *
     * @return BanglaPay_Error_Handler
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
     * Log error message
     *
     * @param string $message Error message
     * @param array $context Additional context data
     */
    public function log_error($message, $context = array()) {
        // Only log to file when WP_DEBUG is enabled, never output to screen
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $log_message = '[BanglaPay Error] ' . $message;
            if (!empty($context)) {
                $log_message .= ' | Context: ' . wp_json_encode($context);
            }
            error_log($log_message);
        }
    }
    
    /**
     * Log info message
     *
     * @param string $message Info message
     * @param array $context Additional context data
     */
    public function log_info($message, $context = array()) {
        // Only log to file when WP_DEBUG is enabled, never output to screen
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $log_message = '[BanglaPay Info] ' . $message;
            if (!empty($context)) {
                $log_message .= ' | Context: ' . wp_json_encode($context);
            }
            error_log($log_message);
        }
    }
    
    /**
     * Handle and log error, return WP_Error object
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param array $context Additional context data
     * @return WP_Error
     */
    public function handle_error($code, $message, $context = array()) {
        $this->log_error($code . ': ' . $message, $context);
        return new WP_Error($code, $message);
    }
}