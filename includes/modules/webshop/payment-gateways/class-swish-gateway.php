<?php
/**
 * Swish Payment Gateway
 *
 * @package SemigApp
 */

namespace SemigApp\Modules\Webshop\Payment_Gateways;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Swish_Gateway
 *
 * Swish payment gateway for Swedish mobile payments
 */
class Swish_Gateway extends Base_Gateway {

    /**
     * Gateway ID
     *
     * @var string
     */
    protected $id = 'swish';

    /**
     * Gateway name
     *
     * @var string
     */
    protected $name = 'Swish';

    /**
     * Gateway description
     *
     * @var string
     */
    protected $description = 'Pay instantly with Swish';

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

        $this->icon = SEMIGAPP_PLUGIN_URL . 'assets/images/swish-logo.svg';

        // Set API URL based on test mode
        $this->api_url = $this->is_test_mode()
            ? 'https://mss.cpc.getswish.net/swish-cpcapi/api/v2'
            : 'https://cpc.getswish.net/swish-cpcapi/api/v2';
    }

    /**
     * Validate credentials
     *
     * @return bool True if valid.
     */
    protected function validate_credentials() {
        $merchant_number = $this->get_setting('merchant_number');
        $certificate_path = $this->get_setting('certificate_path');

        return !empty($merchant_number) && !empty($certificate_path);
    }

    /**
     * Get API headers
     *
     * @return array Headers.
     */
    protected function get_api_headers() {
        return array(
            'Content-Type' => 'application/json',
        );
    }

    /**
     * Make Swish API request with certificate authentication
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     Request data.
     * @param string $method   HTTP method.
     * @return array|WP_Error Response or error.
     */
    protected function swish_api_request($endpoint, $data = array(), $method = 'POST') {
        $certificate_path = $this->get_setting('certificate_path');
        $certificate_password = $this->get_setting('certificate_password', '');

        if (!file_exists($certificate_path)) {
            return new \WP_Error('certificate_not_found', __('Swish certificate not found.', 'semigapp'));
        }

        $curl = curl_init();

        $options = array(
            CURLOPT_URL => $this->api_url . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_SSLCERT => $certificate_path,
        );

        if ($certificate_password) {
            $options[CURLOPT_SSLCERTPASSWD] = $certificate_password;
        }

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = wp_json_encode($data);
        } elseif ($method === 'GET') {
            $options[CURLOPT_HTTPGET] = true;
        } elseif ($method === 'PUT') {
            $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $options[CURLOPT_POSTFIELDS] = wp_json_encode($data);
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log('Swish API error', array('error' => $error));
            return new \WP_Error('swish_api_error', $error);
        }

        $decoded = json_decode($response, true);

        $this->log('Swish API response', array(
            'endpoint' => $endpoint,
            'status' => $http_code,
            'body' => $decoded,
        ));

        // For payment requests, the response is in the Location header
        if ($http_code === 201 && $method === 'POST') {
            $decoded = array(
                'success' => true,
                'location' => curl_getinfo($curl, CURLINFO_REDIRECT_URL),
            );
        }

        return $decoded ?: array();
    }

    /**
     * Process payment
     *
     * @param object $order Order object.
     * @return array Result array.
     */
    public function process_payment($order) {
        $phone = $this->extract_phone($order);

        if (empty($phone)) {
            return array(
                'success' => false,
                'message' => __('Phone number is required for Swish payments.', 'semigapp'),
            );
        }

        // Create payment request
        $payment_result = $this->create_payment_request($order, $phone);

        if (is_wp_error($payment_result)) {
            return array(
                'success' => false,
                'message' => $payment_result->get_error_message(),
            );
        }

        // Store payment request ID
        $request_id = $this->generate_request_id();
        update_post_meta($order->id, '_swish_request_id', $request_id);

        // For M-commerce (mobile), show QR code or redirect
        // For E-commerce, user opens Swish app
        return array(
            'success' => true,
            'redirect' => add_query_arg(array(
                'swish' => '1',
                'order_id' => $order->id,
                'request_id' => $request_id,
            ), $this->get_return_url($order->id)),
            'request_id' => $request_id,
        );
    }

    /**
     * Create Swish payment request
     *
     * @param object $order Order object.
     * @param string $phone Phone number.
     * @return array|WP_Error Response or error.
     */
    private function create_payment_request($order, $phone) {
        $request_id = $this->generate_request_id();

        $data = array(
            'payeePaymentReference' => $order->order_number,
            'callbackUrl' => $this->get_callback_url('payment'),
            'payerAlias' => $this->format_phone($phone),
            'payeeAlias' => $this->get_setting('merchant_number'),
            'amount' => number_format($order->total, 2, '.', ''),
            'currency' => 'SEK',
            'message' => sprintf(__('Order %s', 'semigapp'), $order->order_number),
        );

        // Store request ID with order
        $db = \SemigApp\Database::get_instance();
        $db->update('orders', array(
            'transaction_id' => $request_id,
        ), array('id' => $order->id));

        return $this->swish_api_request('/paymentrequests/' . $request_id, $data, 'PUT');
    }

    /**
     * Generate unique request ID for Swish
     *
     * @return string UUID format request ID.
     */
    private function generate_request_id() {
        // Swish requires a specific UUID format - use cryptographically secure random
        if (function_exists('random_bytes')) {
            $bytes = random_bytes(16);
            // Set version to 4 (random) and variant bits
            $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
            $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
            return strtoupper(bin2hex($bytes));
        }

        // Fallback to WordPress secure function
        return strtoupper(str_replace('-', '', wp_generate_uuid4()));
    }

    /**
     * Format phone number for Swish
     *
     * @param string $phone Phone number.
     * @return string Formatted phone.
     */
    private function format_phone($phone) {
        // Remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert Swedish format to international
        if (strpos($phone, '07') === 0) {
            $phone = '46' . substr($phone, 1);
        } elseif (strpos($phone, '0') === 0) {
            $phone = '46' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Extract phone from order
     *
     * @param object $order Order object.
     * @return string Phone number.
     */
    private function extract_phone($order) {
        if (!empty($order->billing_address['phone'])) {
            return $order->billing_address['phone'];
        }

        if ($order->customer && $order->customer->billing_phone) {
            return $order->customer->billing_phone;
        }

        return '';
    }

    /**
     * Handle callback
     */
    public function handle_callback() {
        $callback_action = isset($_GET['callback_action']) ? sanitize_text_field($_GET['callback_action']) : '';

        switch ($callback_action) {
            case 'payment':
                $this->handle_payment_callback();
                break;

            case 'check':
                $this->handle_check_status();
                break;

            default:
                wp_die('Invalid callback action');
        }
    }

    /**
     * Handle payment callback from Swish
     */
    private function handle_payment_callback() {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        $this->log('Swish callback received', $data);

        if (empty($data['payeePaymentReference'])) {
            wp_die('Missing payment reference');
        }

        // Find order
        global $wpdb;
        $db = \SemigApp\Database::get_instance();

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . $db->get_table('orders') . " WHERE order_number = %s",
            $data['payeePaymentReference']
        ));

        if (!$order) {
            wp_die('Order not found');
        }

        $status = $data['status'] ?? '';

        switch ($status) {
            case 'PAID':
                $db->update('orders', array(
                    'payment_status' => 'completed',
                    'status' => 'processing',
                ), array('id' => $order->id));

                do_action('semigapp_order_paid', $order->id);
                break;

            case 'DECLINED':
            case 'ERROR':
                $db->update('orders', array(
                    'payment_status' => 'failed',
                    'status' => 'failed',
                ), array('id' => $order->id));
                break;

            case 'CANCELLED':
                $db->update('orders', array(
                    'payment_status' => 'cancelled',
                    'status' => 'cancelled',
                ), array('id' => $order->id));
                break;
        }

        wp_die('OK', 'OK', array('response' => 200));
    }

    /**
     * Handle status check (AJAX polling)
     */
    private function handle_check_status() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid order', 'semigapp')));
        }

        $db = \SemigApp\Database::get_instance();
        $order = $db->get_row('orders', array('id' => $order_id));

        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found', 'semigapp')));
        }

        // Also check with Swish API
        if (!empty($order->transaction_id)) {
            $status = $this->get_payment_status($order->transaction_id);

            if (!is_wp_error($status) && isset($status['status'])) {
                if ($status['status'] === 'PAID' && $order->payment_status !== 'completed') {
                    $db->update('orders', array(
                        'payment_status' => 'completed',
                        'status' => 'processing',
                    ), array('id' => $order->id));

                    $order->payment_status = 'completed';
                }
            }
        }

        wp_send_json_success(array(
            'payment_status' => $order->payment_status,
            'order_status' => $order->status,
            'redirect' => $order->payment_status === 'completed'
                ? add_query_arg('order_id', $order_id, $this->settings->get_page_url('checkout'))
                : null,
        ));
    }

    /**
     * Get payment status from Swish
     *
     * @param string $request_id Payment request ID.
     * @return array|WP_Error Status or error.
     */
    public function get_payment_status($request_id) {
        return $this->swish_api_request('/paymentrequests/' . $request_id, array(), 'GET');
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

        $refund_id = $this->generate_request_id();

        $data = array(
            'originalPaymentReference' => $order->transaction_id,
            'callbackUrl' => $this->get_callback_url('refund'),
            'payerAlias' => $this->get_setting('merchant_number'),
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'SEK',
            'message' => $reason ?: sprintf(__('Refund for order %s', 'semigapp'), $order->order_number),
        );

        $result = $this->swish_api_request('/refunds/' . $refund_id, $data, 'PUT');

        return !is_wp_error($result);
    }

    /**
     * Generate QR code for Swish payment
     *
     * @param string $token Payment token.
     * @return string QR code image URL.
     */
    public function generate_qr_code($token) {
        // Swish provides QR codes through their API
        $qr_url = $this->is_test_mode()
            ? 'https://mpc.getswish.net/qrg-swish/api/v1/prefilled'
            : 'https://mpc.getswish.net/qrg-swish/api/v1/prefilled';

        return add_query_arg(array(
            'token' => $token,
            'format' => 'png',
            'size' => 300,
        ), $qr_url);
    }
}
