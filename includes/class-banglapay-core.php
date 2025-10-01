<?php
/**
 * BanglaPay Core Class
 * Handles core functionality and script enqueuing
 *
 * @package BanglaPay
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class BanglaPay_Core {
    /**
     * Single instance of the class
     *
     * @var BanglaPay_Core
     */
    private static $instance = null;
    
    /**
     * Get single instance
     *
     * @return BanglaPay_Core
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue frontend scripts if needed in future versions
        // Example:
        // wp_enqueue_style('banglapay-frontend', BANGLAPAY_PLUGIN_URL . 'assets/css/frontend.css', array(), BANGLAPAY_VERSION);
    }
}