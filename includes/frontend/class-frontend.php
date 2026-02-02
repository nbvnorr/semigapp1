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
        $product_id = intval($_GET['add-to-cart']);
        $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        if ($webshop->add_to_cart($product_id, $quantity)) {
            // Redirect to cart or back to product
            if (isset($_GET['redirect'])) {
                wp_redirect(esc_url($_GET['redirect']));
            } else {
                wp_redirect($this->settings->get_page_url('cart'));
            }
            exit;
        }
    }

    /**
     * Handle payment return
     */
    private function handle_payment_return() {
        $order_id = intval($_GET['order_id']);
        $status = sanitize_text_field($_GET['payment_status']);

        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        $order = $webshop->get_order($order_id);

        if (!$order) {
            return;
        }

        // Handle based on payment status
        if ($status === 'success' && $order->payment_status !== 'completed') {
            // Verify with payment gateway
            $gateway = $webshop->get_gateway($order->payment_method);

            if ($gateway) {
                // Gateway-specific verification would happen here
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
