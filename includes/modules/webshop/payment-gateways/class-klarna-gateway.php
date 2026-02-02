<?php
/**
 * Klarna Payment Gateway
 *
 * @package SemigApp
 */

namespace SemigApp\Modules\Webshop\Payment_Gateways;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Klarna_Gateway
 *
 * Klarna payment gateway for Swedish e-commerce
 */
class Klarna_Gateway extends Base_Gateway {

    /**
     * Gateway ID
     *
     * @var string
     */
    protected $id = 'klarna';

    /**
     * Gateway name
     *
     * @var string
     */
    protected $name = 'Klarna';

    /**
     * Gateway description
     *
     * @var string
     */
    protected $description = 'Pay smoothly with Klarna';

    /**
     * API base URL
     *
     * @var string
     */
    private $api_url;

    /**
     * Constructor
     *
     * @param \SemigApp\Settings $settings Settings instance.
     */
    public function __construct($settings) {
        parent::__construct($settings);

        $this->icon = SEMIGAPP_PLUGIN_URL . 'assets/images/klarna-logo.svg';

        // Set API URL based on test mode
        $this->api_url = $this->is_test_mode()
            ? 'https://api.playground.klarna.com'
            : 'https://api.klarna.com';
    }

    /**
     * Validate credentials
     *
     * @return bool True if valid.
     */
    protected function validate_credentials() {
        $username = $this->get_setting('api_username');
        $password = $this->get_setting('api_password');

        return !empty($username) && !empty($password);
    }

    /**
     * Get API headers
     *
     * @return array Headers.
     */
    protected function get_api_headers() {
        $username = $this->get_setting('api_username');
        $password = $this->get_setting('api_password');

        return array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
        );
    }

    /**
     * Process payment
     *
     * @param object $order Order object.
     * @return array Result array.
     */
    public function process_payment($order) {
        // Create Klarna session
        $session = $this->create_session($order);

        if (is_wp_error($session) || empty($session['session_id'])) {
            return array(
                'success' => false,
                'message' => __('Could not create Klarna session.', 'semigapp'),
            );
        }

        // Store session ID
        update_post_meta($order->id, '_klarna_session_id', $session['session_id']);

        // Return client token for Klarna Widget
        return array(
            'success' => true,
            'redirect' => add_query_arg(array(
                'klarna' => '1',
                'session_id' => $session['session_id'],
                'order_id' => $order->id,
            ), $this->get_return_url($order->id)),
            'client_token' => $session['client_token'] ?? '',
            'payment_method_categories' => $session['payment_method_categories'] ?? array(),
        );
    }

    /**
     * Create Klarna session
     *
     * @param object $order Order object.
     * @return array|WP_Error Session data or error.
     */
    private function create_session($order) {
        $order_lines = array();

        foreach ($order->items as $item) {
            $order_lines[] = array(
                'type' => 'physical',
                'reference' => $item->metadata['sku'] ?? 'PROD-' . $item->product_id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $this->format_amount($item->unit_price),
                'tax_rate' => 2500, // 25% VAT in Klarna format (25 * 100)
                'total_amount' => $this->format_amount($item->total),
                'total_tax_amount' => $this->format_amount($item->tax),
            );
        }

        // Add shipping if applicable
        if ($order->shipping_total > 0) {
            $order_lines[] = array(
                'type' => 'shipping_fee',
                'reference' => 'SHIPPING',
                'name' => __('Shipping', 'semigapp'),
                'quantity' => 1,
                'unit_price' => $this->format_amount($order->shipping_total),
                'tax_rate' => 2500,
                'total_amount' => $this->format_amount($order->shipping_total),
                'total_tax_amount' => $this->format_amount($order->shipping_total * 0.2),
            );
        }

        $billing = $order->billing_address;

        $data = array(
            'purchase_country' => 'SE',
            'purchase_currency' => 'SEK',
            'locale' => 'sv-SE',
            'order_amount' => $this->format_amount($order->total),
            'order_tax_amount' => $this->format_amount($order->tax_total),
            'order_lines' => $order_lines,
            'merchant_urls' => array(
                'terms' => home_url('/terms/'),
                'checkout' => $this->settings->get_page_url('checkout'),
                'confirmation' => add_query_arg('order_id', $order->id, $this->settings->get_page_url('checkout')),
                'push' => $this->get_callback_url('push'),
            ),
        );

        if (!empty($billing)) {
            $data['billing_address'] = array(
                'given_name' => $billing['first_name'] ?? '',
                'family_name' => $billing['last_name'] ?? '',
                'email' => $billing['email'] ?? '',
                'phone' => $billing['phone'] ?? '',
                'street_address' => $billing['address_1'] ?? '',
                'postal_code' => $billing['postcode'] ?? '',
                'city' => $billing['city'] ?? '',
                'country' => 'SE',
            );
        }

        return $this->api_request($this->api_url . '/payments/v1/sessions', $data);
    }

    /**
     * Create Klarna order
     *
     * @param object $order         Order object.
     * @param string $authorization Authorization token.
     * @return array|WP_Error Response or error.
     */
    public function create_klarna_order($order, $authorization) {
        $order_lines = array();

        foreach ($order->items as $item) {
            $order_lines[] = array(
                'type' => 'physical',
                'reference' => $item->metadata['sku'] ?? 'PROD-' . $item->product_id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $this->format_amount($item->unit_price),
                'tax_rate' => 2500,
                'total_amount' => $this->format_amount($item->total),
                'total_tax_amount' => $this->format_amount($item->tax),
            );
        }

        if ($order->shipping_total > 0) {
            $order_lines[] = array(
                'type' => 'shipping_fee',
                'reference' => 'SHIPPING',
                'name' => __('Shipping', 'semigapp'),
                'quantity' => 1,
                'unit_price' => $this->format_amount($order->shipping_total),
                'tax_rate' => 2500,
                'total_amount' => $this->format_amount($order->shipping_total),
                'total_tax_amount' => $this->format_amount($order->shipping_total * 0.2),
            );
        }

        $data = array(
            'purchase_country' => 'SE',
            'purchase_currency' => 'SEK',
            'locale' => 'sv-SE',
            'order_amount' => $this->format_amount($order->total),
            'order_tax_amount' => $this->format_amount($order->tax_total),
            'order_lines' => $order_lines,
            'merchant_reference1' => $order->order_number,
        );

        return $this->api_request(
            $this->api_url . '/payments/v1/authorizations/' . $authorization . '/order',
            $data
        );
    }

    /**
     * Handle callback
     */
    public function handle_callback() {
        $callback_action = isset($_GET['callback_action']) ? sanitize_text_field($_GET['callback_action']) : '';

        switch ($callback_action) {
            case 'push':
                $this->handle_push_callback();
                break;

            case 'authorize':
                $this->handle_authorize_callback();
                break;

            default:
                wp_die('Invalid callback action');
        }
    }

    /**
     * Handle push callback from Klarna
     */
    private function handle_push_callback() {
        $klarna_order_id = isset($_GET['klarna_order_id']) ? sanitize_text_field($_GET['klarna_order_id']) : '';

        if (!$klarna_order_id) {
            wp_die('Missing Klarna order ID');
        }

        // Get order details from Klarna
        $klarna_order = $this->api_request(
            $this->api_url . '/ordermanagement/v1/orders/' . $klarna_order_id,
            array(),
            'GET'
        );

        if (is_wp_error($klarna_order)) {
            wp_die('Could not retrieve Klarna order');
        }

        // Find local order
        global $wpdb;
        $db = \SemigApp\Database::get_instance();
        $order_number = $klarna_order['merchant_reference1'] ?? '';

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . $db->get_table('orders') . " WHERE order_number = %s",
            $order_number
        ));

        if ($order) {
            // Update payment status
            $db->update('orders', array(
                'payment_status' => 'completed',
                'transaction_id' => $klarna_order_id,
            ), array('id' => $order->id));

            // Update order status
            $db->update('orders', array(
                'status' => 'processing',
            ), array('id' => $order->id));

            // Acknowledge the order
            $this->api_request(
                $this->api_url . '/ordermanagement/v1/orders/' . $klarna_order_id . '/acknowledge',
                array(),
                'POST'
            );
        }

        wp_die('OK', 'OK', array('response' => 200));
    }

    /**
     * Handle authorize callback
     */
    private function handle_authorize_callback() {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $authorization = isset($_POST['authorization_token']) ? sanitize_text_field($_POST['authorization_token']) : '';

        if (!$order_id || !$authorization) {
            wp_send_json_error(array('message' => __('Invalid request', 'semigapp')));
        }

        $db = \SemigApp\Database::get_instance();
        $order = $db->get_row('orders', array('id' => $order_id));

        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found', 'semigapp')));
        }

        $order->items = $db->get_results('order_items', array('where' => array('order_id' => $order_id)));

        // Create Klarna order
        $result = $this->create_klarna_order($order, $authorization);

        if (is_wp_error($result) || empty($result['order_id'])) {
            wp_send_json_error(array('message' => __('Could not complete payment', 'semigapp')));
        }

        // Update order
        $db->update('orders', array(
            'payment_status' => 'completed',
            'transaction_id' => $result['order_id'],
            'status' => 'processing',
        ), array('id' => $order_id));

        wp_send_json_success(array(
            'redirect' => add_query_arg('order_id', $order_id, $this->settings->get_page_url('checkout')),
        ));
    }

    /**
     * Process refund
     *
     * @param object $order  Order object.
     * @param float  $amount Refund amount.
     * @param string $reason Refund reason.
     * @return bool True on success.
     */
    public function process_refund($order, $amount, $reason = '') {
        if (empty($order->transaction_id)) {
            return false;
        }

        $data = array(
            'refunded_amount' => $this->format_amount($amount),
            'description' => $reason,
        );

        $result = $this->api_request(
            $this->api_url . '/ordermanagement/v1/orders/' . $order->transaction_id . '/refunds',
            $data
        );

        return !is_wp_error($result);
    }

    /**
     * Format amount for Klarna (in minor units)
     *
     * @param float $amount Amount.
     * @return int Amount in minor units.
     */
    private function format_amount($amount) {
        return (int) round($amount * 100);
    }

    /**
     * Capture order
     *
     * @param object $order Order object.
     * @return bool True on success.
     */
    public function capture($order) {
        if (empty($order->transaction_id)) {
            return false;
        }

        $order_lines = array();

        foreach ($order->items as $item) {
            $order_lines[] = array(
                'type' => 'physical',
                'reference' => $item->metadata['sku'] ?? 'PROD-' . $item->product_id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $this->format_amount($item->unit_price),
                'tax_rate' => 2500,
                'total_amount' => $this->format_amount($item->total),
                'total_tax_amount' => $this->format_amount($item->tax),
            );
        }

        $data = array(
            'captured_amount' => $this->format_amount($order->total),
            'order_lines' => $order_lines,
        );

        $result = $this->api_request(
            $this->api_url . '/ordermanagement/v1/orders/' . $order->transaction_id . '/captures',
            $data
        );

        return !is_wp_error($result);
    }
}
