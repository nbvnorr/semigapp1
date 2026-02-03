<?php
/**
 * Base Payment Gateway
 *
 * @package SemigApp
 */

namespace SemigApp\Modules\Webshop\Payment_Gateways;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Base_Gateway
 *
 * Abstract base class for payment gateways
 */
abstract class Base_Gateway {

    /**
     * Gateway ID
     *
     * @var string
     */
    protected $id = '';

    /**
     * Gateway name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Gateway description
     *
     * @var string
     */
    protected $description = '';

    /**
     * Gateway icon URL
     *
     * @var string
     */
    protected $icon = '';

    /**
     * Whether gateway supports test mode
     *
     * @var bool
     */
    protected $supports_test_mode = true;

    /**
     * Settings instance
     *
     * @var \SemigApp\Settings
     */
    protected $settings;

    /**
     * Constructor
     *
     * @param \SemigApp\Settings $settings Settings instance.
     */
    public function __construct($settings) {
        $this->settings = $settings;
    }

    /**
     * Get gateway ID
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get gateway name
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get gateway description
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Get gateway icon
     *
     * @return string
     */
    public function get_icon() {
        return $this->icon;
    }

    /**
     * Check if gateway is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        return (bool) $this->settings->get('payments.' . $this->id . '_enabled', false);
    }

    /**
     * Check if gateway is in test mode
     *
     * @return bool
     */
    public function is_test_mode() {
        return (bool) $this->settings->get('payments.' . $this->id . '_test_mode', true);
    }

    /**
     * Check if gateway is available
     *
     * @return bool
     */
    public function is_available() {
        if (!$this->is_enabled()) {
            return false;
        }

        return $this->validate_credentials();
    }

    /**
     * Get setting
     *
     * @param string $key     Setting key.
     * @param mixed  $default Default value.
     * @return mixed Setting value.
     */
    protected function get_setting($key, $default = null) {
        return $this->settings->get('payments.' . $this->id . '_' . $key, $default);
    }

    /**
     * Get callback URL
     *
     * @param string $action Callback action.
     * @return string Callback URL.
     */
    protected function get_callback_url($action = '') {
        return add_query_arg(array(
            'action' => 'semigapp_payment_callback',
            'gateway' => $this->id,
            'callback_action' => $action,
        ), admin_url('admin-ajax.php'));
    }

    /**
     * Get return URL
     *
     * @param int $order_id Order ID.
     * @return string Return URL.
     */
    protected function get_return_url($order_id) {
        return add_query_arg('order_id', $order_id, $this->settings->get_page_url('checkout'));
    }

    /**
     * Log gateway activity
     *
     * @param string $message Log message.
     * @param array  $data    Additional data.
     */
    protected function log($message, $data = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[SemigApp ' . $this->id . '] ' . $message . ' ' . wp_json_encode($data));
        }
    }

    /**
     * Make API request with comprehensive error handling
     *
     * Wraps the HTTP request in try/catch to handle any unexpected errors
     * and provides detailed error logging for debugging.
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     Request data.
     * @param string $method   HTTP method.
     * @return array|WP_Error Response or error.
     */
    protected function api_request($endpoint, $data = array(), $method = 'POST') {
        try {
            $args = array(
                'method' => $method,
                'timeout' => 30,
                'headers' => $this->get_api_headers(),
                'sslverify' => true,
            );

            if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
                $args['body'] = wp_json_encode($data);
            }

            $response = wp_remote_request($endpoint, $args);

            // Handle WP_Error from WordPress HTTP API
            if (is_wp_error($response)) {
                $this->log('API connection error', array(
                    'endpoint' => $endpoint,
                    'error_code' => $response->get_error_code(),
                    'error_message' => $response->get_error_message(),
                ));
                return new \WP_Error(
                    'gateway_connection_error',
                    sprintf(
                        __('Payment gateway connection failed: %s', 'semigapp'),
                        $response->get_error_message()
                    )
                );
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $decoded = json_decode($body, true);

            // Handle JSON decode errors
            if (json_last_error() !== JSON_ERROR_NONE && !empty($body)) {
                $this->log('API JSON decode error', array(
                    'endpoint' => $endpoint,
                    'status' => $status_code,
                    'body' => substr($body, 0, 500),
                    'json_error' => json_last_error_msg(),
                ));
                return new \WP_Error(
                    'gateway_response_error',
                    __('Invalid response from payment gateway.', 'semigapp')
                );
            }

            // Log response (without sensitive data in production)
            $this->log('API response', array(
                'endpoint' => $endpoint,
                'status' => $status_code,
                'success' => $status_code >= 200 && $status_code < 300,
            ));

            // Handle HTTP error status codes
            if ($status_code >= 400) {
                $error_message = $this->extract_error_message($decoded, $status_code);
                return new \WP_Error(
                    'gateway_api_error',
                    $error_message,
                    array('status_code' => $status_code, 'response' => $decoded)
                );
            }

            return $decoded ?: array();

        } catch (\Exception $e) {
            // Catch any unexpected exceptions
            $this->log('API exception', array(
                'endpoint' => $endpoint,
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ));

            return new \WP_Error(
                'gateway_exception',
                __('An unexpected error occurred while processing payment. Please try again.', 'semigapp'),
                array('exception' => $e->getMessage())
            );
        }
    }

    /**
     * Extract error message from API response
     *
     * @param array|null $decoded     Decoded response body.
     * @param int        $status_code HTTP status code.
     * @return string Error message.
     */
    private function extract_error_message($decoded, $status_code) {
        // Try common error response formats
        if (is_array($decoded)) {
            if (isset($decoded['error_message'])) {
                return $decoded['error_message'];
            }
            if (isset($decoded['error']['message'])) {
                return $decoded['error']['message'];
            }
            if (isset($decoded['message'])) {
                return $decoded['message'];
            }
            if (isset($decoded['error_description'])) {
                return $decoded['error_description'];
            }
        }

        // Return generic message based on status code
        $status_messages = array(
            400 => __('Invalid request to payment gateway.', 'semigapp'),
            401 => __('Payment gateway authentication failed.', 'semigapp'),
            403 => __('Payment gateway access denied.', 'semigapp'),
            404 => __('Payment resource not found.', 'semigapp'),
            500 => __('Payment gateway server error. Please try again.', 'semigapp'),
            502 => __('Payment gateway temporarily unavailable.', 'semigapp'),
            503 => __('Payment gateway is currently unavailable.', 'semigapp'),
        );

        return $status_messages[$status_code]
            ?? sprintf(__('Payment gateway error (HTTP %d).', 'semigapp'), $status_code);
    }

    /**
     * Get API headers
     *
     * @return array Headers.
     */
    abstract protected function get_api_headers();

    /**
     * Validate credentials
     *
     * @return bool True if valid.
     */
    abstract protected function validate_credentials();

    /**
     * Process payment
     *
     * @param object $order Order object.
     * @return array Result array with 'success', 'redirect', and 'message'.
     */
    abstract public function process_payment($order);

    /**
     * Handle callback
     */
    abstract public function handle_callback();

    /**
     * Process refund
     *
     * @param object $order  Order object.
     * @param float  $amount Refund amount.
     * @param string $reason Refund reason.
     * @return bool True on success.
     */
    public function process_refund($order, $amount, $reason = '') {
        return false;
    }

    /**
     * Verify payment status
     *
     * Checks with the payment provider to verify the current status of a payment.
     * Used for payment confirmation callbacks and order status verification.
     *
     * @param object $order Order object with transaction details.
     * @return array {
     *     @type bool   $success  Whether verification was successful.
     *     @type string $status   Payment status: 'completed', 'pending', 'failed', 'refunded'.
     *     @type string $message  Human-readable status message.
     *     @type array  $data     Additional payment data from the provider.
     * }
     */
    public function verify_payment($order) {
        // Default implementation returns pending status
        // Subclasses should override this to check with their payment provider
        return array(
            'success' => false,
            'status' => 'pending',
            'message' => __('Payment verification not implemented for this gateway.', 'semigapp'),
            'data' => array(),
        );
    }

    /**
     * Check if a payment is complete
     *
     * Helper method to verify if a payment has been successfully completed.
     *
     * @param object $order Order object.
     * @return bool True if payment is confirmed complete.
     */
    public function is_payment_complete($order) {
        $verification = $this->verify_payment($order);
        return $verification['success'] && $verification['status'] === 'completed';
    }
}
