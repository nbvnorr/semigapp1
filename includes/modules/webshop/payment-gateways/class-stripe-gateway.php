<?php
/**
 * Stripe Payment Gateway
 *
 * @package SemigApp
 */

namespace SemigApp\Modules\Webshop\Payment_Gateways;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Stripe_Gateway
 *
 * Stripe payment gateway for card payments
 */
class Stripe_Gateway extends Base_Gateway {

    /**
     * Gateway ID
     *
     * @var string
     */
    protected $id = 'stripe';

    /**
     * Gateway name
     *
     * @var string
     */
    protected $name = 'Card Payment';

    /**
     * Gateway description
     *
     * @var string
     */
    protected $description = 'Pay securely with credit or debit card';

    /**
     * API base URL
     *
     * @var string
     */
    private $api_url = 'https://api.stripe.com/v1';

    /**
     * Constructor
     *
     * @param \SemigApp\Settings $settings Settings instance.
     */
    public function __construct($settings) {
        parent::__construct($settings);

        $this->icon = SEMIGAPP_PLUGIN_URL . 'assets/images/cards.svg';
    }

    /**
     * Validate credentials
     *
     * @return bool True if valid.
     */
    protected function validate_credentials() {
        $secret_key = $this->get_secret_key();
        $publishable_key = $this->get_publishable_key();

        return !empty($secret_key) && !empty($publishable_key);
    }

    /**
     * Get secret key
     *
     * @return string Secret key.
     */
    private function get_secret_key() {
        return $this->get_setting('secret_key', '');
    }

    /**
     * Get publishable key
     *
     * @return string Publishable key.
     */
    public function get_publishable_key() {
        return $this->get_setting('publishable_key', '');
    }

    /**
     * Get API headers
     *
     * @return array Headers.
     */
    protected function get_api_headers() {
        return array(
            'Authorization' => 'Bearer ' . $this->get_secret_key(),
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Stripe-Version' => '2023-10-16',
        );
    }

    /**
     * Make Stripe API request with error handling
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     Request data.
     * @param string $method   HTTP method.
     * @return array|WP_Error Response or error.
     */
    private function stripe_api_request($endpoint, $data = array(), $method = 'POST') {
        try {
            $args = array(
                'method' => $method,
                'timeout' => 30,
                'headers' => $this->get_api_headers(),
            );

            if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
                $args['body'] = $data;
            }

            $response = wp_remote_request($this->api_url . $endpoint, $args);

            if (is_wp_error($response)) {
                $this->log('Stripe API error', array(
                    'endpoint' => $endpoint,
                    'error' => $response->get_error_message(),
                    'error_code' => $response->get_error_code(),
                ));
                return $response;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $decoded = json_decode($body, true);

            // Log for debugging (remove sensitive data in production)
            $this->log('Stripe API response', array(
                'endpoint' => $endpoint,
                'status' => $status_code,
                'success' => !isset($decoded['error']),
            ));

            // Handle HTTP errors
            if ($status_code >= 400) {
                $error_message = isset($decoded['error']['message'])
                    ? $decoded['error']['message']
                    : sprintf(__('Stripe API error (HTTP %d)', 'semigapp'), $status_code);

                return new \WP_Error(
                    'stripe_http_error',
                    $error_message,
                    array('status_code' => $status_code)
                );
            }

            if (isset($decoded['error'])) {
                return new \WP_Error(
                    'stripe_error',
                    $decoded['error']['message'],
                    array('type' => $decoded['error']['type'] ?? 'unknown')
                );
            }

            return $decoded ?: array();

        } catch (\Exception $e) {
            $this->log('Stripe API exception', array(
                'endpoint' => $endpoint,
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ));

            return new \WP_Error(
                'stripe_exception',
                __('An unexpected error occurred while processing payment.', 'semigapp'),
                array('exception' => $e->getMessage())
            );
        }
    }

    /**
     * Process payment
     *
     * @param object $order Order object.
     * @return array Result array.
     */
    public function process_payment($order) {
        // Create a PaymentIntent
        $payment_intent = $this->create_payment_intent($order);

        if (is_wp_error($payment_intent)) {
            return array(
                'success' => false,
                'message' => $payment_intent->get_error_message(),
            );
        }

        // Store PaymentIntent ID
        $db = \SemigApp\Database::get_instance();
        $db->update('orders', array(
            'transaction_id' => $payment_intent['id'],
        ), array('id' => $order->id));

        return array(
            'success' => true,
            'client_secret' => $payment_intent['client_secret'],
            'publishable_key' => $this->get_publishable_key(),
            'redirect' => add_query_arg(array(
                'stripe' => '1',
                'order_id' => $order->id,
                'client_secret' => $payment_intent['client_secret'],
            ), $this->get_return_url($order->id)),
        );
    }

    /**
     * Create PaymentIntent
     *
     * @param object $order Order object.
     * @return array|WP_Error PaymentIntent or error.
     */
    private function create_payment_intent($order) {
        $data = array(
            'amount' => $this->format_amount($order->total),
            'currency' => strtolower($order->currency ?: 'sek'),
            'automatic_payment_methods[enabled]' => 'true',
            'metadata[order_id]' => $order->id,
            'metadata[order_number]' => $order->order_number,
            'description' => sprintf(__('Order %s', 'semigapp'), $order->order_number),
        );

        // Add customer email if available
        $email = $order->billing_address['email'] ?? '';
        if ($email) {
            $data['receipt_email'] = $email;
        }

        // Add billing details
        if (!empty($order->billing_address)) {
            $billing = $order->billing_address;
            $data['shipping[name]'] = ($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '');
            $data['shipping[address][line1]'] = $billing['address_1'] ?? '';
            $data['shipping[address][city]'] = $billing['city'] ?? '';
            $data['shipping[address][postal_code]'] = $billing['postcode'] ?? '';
            $data['shipping[address][country]'] = 'SE';
        }

        return $this->stripe_api_request('/payment_intents', $data);
    }

    /**
     * Handle callback
     */
    public function handle_callback() {
        $callback_action = isset($_GET['callback_action']) ? sanitize_text_field($_GET['callback_action']) : '';

        switch ($callback_action) {
            case 'webhook':
                $this->handle_webhook();
                break;

            case 'confirm':
                $this->handle_confirm();
                break;

            default:
                wp_die('Invalid callback action');
        }
    }

    /**
     * Handle Stripe webhook
     */
    private function handle_webhook() {
        $payload = file_get_contents('php://input');
        $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
        $webhook_secret = $this->get_setting('webhook_secret', '');

        // Verify webhook signature if secret is set
        if ($webhook_secret) {
            $verified = $this->verify_webhook_signature($payload, $sig_header, $webhook_secret);

            if (!$verified) {
                wp_die('Invalid signature', 'Forbidden', array('response' => 403));
            }
        }

        $event = json_decode($payload, true);

        if (!$event) {
            wp_die('Invalid payload', 'Bad Request', array('response' => 400));
        }

        $this->log('Stripe webhook received', $event);

        switch ($event['type']) {
            case 'payment_intent.succeeded':
                $this->handle_payment_succeeded($event['data']['object']);
                break;

            case 'payment_intent.payment_failed':
                $this->handle_payment_failed($event['data']['object']);
                break;

            case 'charge.refunded':
                $this->handle_refund($event['data']['object']);
                break;
        }

        wp_die('OK', 'OK', array('response' => 200));
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload    Request payload.
     * @param string $sig_header Signature header.
     * @param string $secret     Webhook secret.
     * @return bool True if valid.
     */
    private function verify_webhook_signature($payload, $sig_header, $secret) {
        $parts = explode(',', $sig_header);
        $timestamp = null;
        $signature = null;

        foreach ($parts as $part) {
            $kv = explode('=', $part, 2);
            if ($kv[0] === 't') {
                $timestamp = $kv[1];
            } elseif ($kv[0] === 'v1') {
                $signature = $kv[1];
            }
        }

        if (!$timestamp || !$signature) {
            return false;
        }

        // Check timestamp (allow 5 minute tolerance)
        if (abs(time() - intval($timestamp)) > 300) {
            return false;
        }

        $signed_payload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signed_payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Handle successful payment
     *
     * @param array $payment_intent PaymentIntent data.
     */
    private function handle_payment_succeeded($payment_intent) {
        $order_id = $payment_intent['metadata']['order_id'] ?? 0;

        if (!$order_id) {
            return;
        }

        $db = \SemigApp\Database::get_instance();

        $db->update('orders', array(
            'payment_status' => 'completed',
            'status' => 'processing',
        ), array('id' => $order_id));

        do_action('semigapp_order_paid', $order_id);
    }

    /**
     * Handle failed payment
     *
     * @param array $payment_intent PaymentIntent data.
     */
    private function handle_payment_failed($payment_intent) {
        $order_id = $payment_intent['metadata']['order_id'] ?? 0;

        if (!$order_id) {
            return;
        }

        $db = \SemigApp\Database::get_instance();

        $db->update('orders', array(
            'payment_status' => 'failed',
            'status' => 'failed',
        ), array('id' => $order_id));
    }

    /**
     * Handle refund
     *
     * @param array $charge Charge data.
     */
    private function handle_refund($charge) {
        // Find order by transaction ID
        $db = \SemigApp\Database::get_instance();

        global $wpdb;
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . $db->get_table('orders') . " WHERE transaction_id LIKE %s",
            '%' . $charge['payment_intent'] . '%'
        ));

        if ($order) {
            $db->update('orders', array(
                'status' => 'refunded',
            ), array('id' => $order->id));
        }
    }

    /**
     * Handle confirm (client-side confirmation result)
     */
    private function handle_confirm() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $payment_intent_id = isset($_POST['payment_intent_id']) ? sanitize_text_field($_POST['payment_intent_id']) : '';

        if (!$order_id || !$payment_intent_id) {
            wp_send_json_error(array('message' => __('Invalid request', 'semigapp')));
        }

        // Retrieve PaymentIntent to check status
        $payment_intent = $this->stripe_api_request('/payment_intents/' . $payment_intent_id, array(), 'GET');

        if (is_wp_error($payment_intent)) {
            wp_send_json_error(array('message' => $payment_intent->get_error_message()));
        }

        $db = \SemigApp\Database::get_instance();

        if ($payment_intent['status'] === 'succeeded') {
            $db->update('orders', array(
                'payment_status' => 'completed',
                'status' => 'processing',
            ), array('id' => $order_id));

            wp_send_json_success(array(
                'redirect' => add_query_arg('order_id', $order_id, $this->settings->get_page_url('checkout')),
            ));
        } elseif ($payment_intent['status'] === 'requires_action') {
            wp_send_json_success(array(
                'requires_action' => true,
                'client_secret' => $payment_intent['client_secret'],
            ));
        } else {
            wp_send_json_error(array('message' => __('Payment failed. Please try again.', 'semigapp')));
        }
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
            'payment_intent' => $order->transaction_id,
            'amount' => $this->format_amount($amount),
        );

        if ($reason) {
            $data['reason'] = 'requested_by_customer';
            $data['metadata[reason]'] = $reason;
        }

        $result = $this->stripe_api_request('/refunds', $data);

        return !is_wp_error($result) && isset($result['id']);
    }

    /**
     * Format amount for Stripe (in cents)
     *
     * @param float $amount Amount.
     * @return int Amount in cents.
     */
    private function format_amount($amount) {
        return (int) round($amount * 100);
    }

    /**
     * Create customer
     *
     * @param array $data Customer data.
     * @return array|WP_Error Customer or error.
     */
    public function create_customer($data) {
        return $this->stripe_api_request('/customers', array(
            'email' => $data['email'] ?? '',
            'name' => $data['name'] ?? '',
            'metadata[user_id]' => $data['user_id'] ?? '',
        ));
    }

    /**
     * Get or create customer
     *
     * @param int $user_id WordPress user ID.
     * @return string|null Stripe customer ID.
     */
    public function get_or_create_customer($user_id) {
        $customer_id = get_user_meta($user_id, '_stripe_customer_id', true);

        if ($customer_id) {
            return $customer_id;
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return null;
        }

        $customer = $this->create_customer(array(
            'email' => $user->user_email,
            'name' => $user->display_name,
            'user_id' => $user_id,
        ));

        if (!is_wp_error($customer) && isset($customer['id'])) {
            update_user_meta($user_id, '_stripe_customer_id', $customer['id']);
            return $customer['id'];
        }

        return null;
    }
}
