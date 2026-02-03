<?php
/**
 * Frontend Class
 *
 * @package SemigApp
 */

namespace SemigApp\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Frontend
 *
 * Handles all frontend functionality
 */
class Frontend {

    /**
     * Settings instance
     *
     * @var \SemigApp\Settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = \SemigApp\Settings::get_instance();
    }

    /**
     * Initialize frontend
     */
    public function init() {
        add_action('wp', array($this, 'handle_actions'));
        add_filter('body_class', array($this, 'body_class'));
        add_action('wp_head', array($this, 'output_header'));
    }

    /**
     * Handle frontend actions
     */
    public function handle_actions() {
        // Handle cart actions
        if (isset($_GET['add-to-cart'])) {
            $this->handle_add_to_cart();
        }

        // Handle checkout return
        if (isset($_GET['order_id']) && isset($_GET['payment_status'])) {
            $this->handle_payment_return();
        }
    }

    /**
     * Handle add to cart
     */
    private function handle_add_to_cart() {
        // Sanitize product ID
        $product_id = isset($_GET['add-to-cart']) ? absint($_GET['add-to-cart']) : 0;

        if (!$product_id) {
            return;
        }

        // Validate quantity (1-99)
        $quantity = isset($_GET['quantity']) ? absint($_GET['quantity']) : 1;
        $quantity = max(1, min(99, $quantity));

        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        // Verify product exists
        $product = $webshop->get_product($product_id);
        if (!$product) {
            return;
        }

        if ($webshop->add_to_cart($product_id, $quantity)) {
            // Validate redirect URL - only allow same-site redirects
            if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                $redirect_url = esc_url_raw($_GET['redirect']);

                // Only allow redirects to the same site
                if ($this->is_safe_redirect($redirect_url)) {
                    wp_safe_redirect($redirect_url);
                    exit;
                }
            }

            // Default redirect to cart
            wp_safe_redirect($this->settings->get_page_url('cart'));
            exit;
        }
    }

    /**
     * Check if redirect URL is safe (same site)
     *
     * @param string $url URL to check.
     * @return bool True if safe.
     */
    private function is_safe_redirect($url) {
        if (empty($url)) {
            return false;
        }

        $site_url = home_url();
        $site_host = wp_parse_url($site_url, PHP_URL_HOST);
        $redirect_host = wp_parse_url($url, PHP_URL_HOST);

        // Allow relative URLs
        if (empty($redirect_host)) {
            return true;
        }

        // Check if redirect host matches site host
        return $redirect_host === $site_host;
    }

    /**
     * Handle payment return
     */
    private function handle_payment_return() {
        // Sanitize and validate inputs
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $status = isset($_GET['payment_status']) ? sanitize_text_field($_GET['payment_status']) : '';

        if (!$order_id || empty($status)) {
            return;
        }

        // Validate status is one of expected values
        $valid_statuses = array('success', 'cancelled', 'failed', 'pending');
        if (!in_array($status, $valid_statuses, true)) {
            return;
        }

        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        $order = $webshop->get_order($order_id);

        if (!$order) {
            return;
        }

        // Verify order belongs to current user (if logged in) or session
        if (is_user_logged_in()) {
            if ($order->user_id && $order->user_id !== get_current_user_id()) {
                return;
            }
        }

        // Handle based on payment status
        if ($status === 'success' && $order->payment_status !== 'completed') {
            // Verify with payment gateway
            $gateway = $webshop->get_gateway($order->payment_method);

            if ($gateway) {
                // Gateway-specific verification would happen here
                $gateway->verify_payment($order);
            }
        }
    }

    /**
     * Add body classes
     *
     * @param array $classes Existing classes.
     * @return array Modified classes.
     */
    public function body_class($classes) {
        // Add class for SemigApp pages
        $pages = $this->settings->get_pages();

        if (is_page()) {
            $page_id = get_the_ID();

            foreach ($pages as $key => $id) {
                if ($page_id == $id) {
                    $classes[] = 'semigapp-page';
                    $classes[] = 'semigapp-' . $key;
                }
            }
        }

        return $classes;
    }

    /**
     * Output header content
     */
    public function output_header() {
        // Output cart count for JavaScript
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        echo '<script>var semigappCartCount = ' . $webshop->get_cart_count() . ';</script>';
    }

    /**
     * Get account navigation
     *
     * @return array Navigation items.
     */
    public function get_account_navigation() {
        $nav = array(
            'dashboard' => array(
                'label' => __('Dashboard', 'semigapp'),
                'url' => $this->settings->get_page_url('account'),
            ),
            'orders' => array(
                'label' => __('Orders', 'semigapp'),
                'url' => add_query_arg('section', 'orders', $this->settings->get_page_url('account')),
            ),
            'membership' => array(
                'label' => __('Membership', 'semigapp'),
                'url' => add_query_arg('section', 'membership', $this->settings->get_page_url('account')),
            ),
            'events' => array(
                'label' => __('My Events', 'semigapp'),
                'url' => add_query_arg('section', 'events', $this->settings->get_page_url('account')),
            ),
            'tasks' => array(
                'label' => __('My Tasks', 'semigapp'),
                'url' => add_query_arg('section', 'tasks', $this->settings->get_page_url('account')),
            ),
            'profile' => array(
                'label' => __('Profile', 'semigapp'),
                'url' => add_query_arg('section', 'profile', $this->settings->get_page_url('account')),
            ),
        );

        return apply_filters('semigapp_account_navigation', $nav);
    }
}
