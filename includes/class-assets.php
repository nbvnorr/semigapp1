<?php
/**
 * Assets Handler
 *
 * @package SemigApp
 */

namespace SemigApp;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Assets
 *
 * Handles CSS and JavaScript assets
 */
class Assets {

    /**
     * Instance
     *
     * @var Assets
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Assets
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Main frontend styles
        wp_enqueue_style(
            'semigapp-frontend',
            SEMIGAPP_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SEMIGAPP_VERSION
        );

        // Main frontend script
        wp_enqueue_script(
            'semigapp-frontend',
            SEMIGAPP_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            SEMIGAPP_VERSION,
            true
        );

        // Localize script
        wp_localize_script('semigapp-frontend', 'semigappFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('semigapp/v1/'),
            'nonce' => wp_create_nonce('semigapp_frontend'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'currency' => Settings::get_instance()->get_currency(),
            'i18n' => array(
                'loading' => __('Loading...', 'semigapp'),
                'error' => __('An error occurred. Please try again.', 'semigapp'),
                'addedToCart' => __('Added to cart', 'semigapp'),
                'removeConfirm' => __('Are you sure you want to remove this item?', 'semigapp'),
            ),
        ));

        // Conditionally load module-specific assets
        $this->enqueue_conditional_assets();
    }

    /**
     * Enqueue conditional frontend assets
     */
    private function enqueue_conditional_assets() {
        $settings = Settings::get_instance();

        // Calendar assets (if on events page)
        if (is_page($settings->get_page_id('events')) || is_singular('semigapp_event')) {
            wp_enqueue_style(
                'semigapp-calendar',
                SEMIGAPP_PLUGIN_URL . 'assets/css/calendar.css',
                array('semigapp-frontend'),
                SEMIGAPP_VERSION
            );

            wp_enqueue_script(
                'semigapp-calendar',
                SEMIGAPP_PLUGIN_URL . 'assets/js/calendar.js',
                array('semigapp-frontend'),
                SEMIGAPP_VERSION,
                true
            );
        }

        // Shop assets
        if (is_page($settings->get_page_id('shop')) ||
            is_page($settings->get_page_id('cart')) ||
            is_page($settings->get_page_id('checkout')) ||
            is_singular('semigapp_product')) {

            wp_enqueue_style(
                'semigapp-shop',
                SEMIGAPP_PLUGIN_URL . 'assets/css/shop.css',
                array('semigapp-frontend'),
                SEMIGAPP_VERSION
            );

            wp_enqueue_script(
                'semigapp-shop',
                SEMIGAPP_PLUGIN_URL . 'assets/js/shop.js',
                array('semigapp-frontend'),
                SEMIGAPP_VERSION,
                true
            );
        }

        // Account assets
        if (is_page($settings->get_page_id('account'))) {
            wp_enqueue_style(
                'semigapp-account',
                SEMIGAPP_PLUGIN_URL . 'assets/css/account.css',
                array('semigapp-frontend'),
                SEMIGAPP_VERSION
            );

            wp_enqueue_script(
                'semigapp-account',
                SEMIGAPP_PLUGIN_URL . 'assets/js/account.js',
                array('semigapp-frontend'),
                SEMIGAPP_VERSION,
                true
            );
        }
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on SemigApp admin pages
        $semigapp_pages = array(
            'toplevel_page_semigapp',
            'semigapp_page_semigapp-projects',
            'semigapp_page_semigapp-events',
            'semigapp_page_semigapp-members',
            'semigapp_page_semigapp-shop',
            'semigapp_page_semigapp-orders',
            'semigapp_page_semigapp-newsletter',
            'semigapp_page_semigapp-settings',
        );

        if (!in_array($hook, $semigapp_pages) && strpos($hook, 'semigapp') === false) {
            return;
        }

        // Admin styles
        wp_enqueue_style(
            'semigapp-admin',
            SEMIGAPP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SEMIGAPP_VERSION
        );

        // Admin script
        wp_enqueue_script(
            'semigapp-admin',
            SEMIGAPP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker', 'wp-color-picker'),
            SEMIGAPP_VERSION,
            true
        );

        // Enqueue media uploader
        wp_enqueue_media();

        // Color picker
        wp_enqueue_style('wp-color-picker');

        // Localize admin script
        wp_localize_script('semigapp-admin', 'semigappAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('semigapp/v1/'),
            'nonce' => wp_create_nonce('semigapp_admin'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'currency' => Settings::get_instance()->get_currency(),
            'i18n' => array(
                'loading' => __('Loading...', 'semigapp'),
                'saving' => __('Saving...', 'semigapp'),
                'saved' => __('Saved!', 'semigapp'),
                'error' => __('An error occurred.', 'semigapp'),
                'confirmDelete' => __('Are you sure you want to delete this?', 'semigapp'),
                'selectImage' => __('Select Image', 'semigapp'),
                'useImage' => __('Use Image', 'semigapp'),
            ),
        ));

        // Chart.js for statistics
        if (strpos($hook, 'semigapp') !== false) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                array(),
                '4.4.0',
                true
            );
        }
    }

    /**
     * Get asset URL
     *
     * @param string $path Asset path relative to assets directory.
     * @return string Full asset URL.
     */
    public function get_url($path) {
        return SEMIGAPP_PLUGIN_URL . 'assets/' . ltrim($path, '/');
    }

    /**
     * Get asset path
     *
     * @param string $path Asset path relative to assets directory.
     * @return string Full asset path.
     */
    public function get_path($path) {
        return SEMIGAPP_PLUGIN_DIR . 'assets/' . ltrim($path, '/');
    }

    /**
     * Inline CSS
     *
     * @param string $handle Style handle.
     * @param string $css    CSS to add.
     */
    public function add_inline_css($handle, $css) {
        wp_add_inline_style($handle, $css);
    }

    /**
     * Inline JS
     *
     * @param string $handle Script handle.
     * @param string $js     JavaScript to add.
     */
    public function add_inline_js($handle, $js) {
        wp_add_inline_script($handle, $js);
    }
}
