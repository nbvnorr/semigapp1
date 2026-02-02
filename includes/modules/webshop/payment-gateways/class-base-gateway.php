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
     * Make API request
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     Request data.
     * @param string $method   HTTP method.
     * @return array|WP_Error Response or error.
     */
    protected function api_request($endpoint, $data = array(), $method = 'POST') {
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => $this->get_api_headers(),
        );

        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($endpoint, $args);

        if (is_wp_error($response)) {
            $this->log('API error', array('error' => $response->get_error_message()));
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        $this->log('API response', array(
            'endpoint' => $endpoint,
            'status' => wp_remote_retrieve_response_code($response),
            'body' => $decoded,
        ));

        return $decoded ?: array();
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
}
