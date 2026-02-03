<?php
/**
 * REST API Controller
 *
 * @package SemigApp
 */

namespace SemigApp\Api;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Rest_Controller
 *
 * Handles REST API endpoints with proper security
 */
class Rest_Controller {

    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'semigapp/v1';

    /**
     * Rate limit transient prefix
     *
     * @var string
     */
    private $rate_limit_prefix = 'semigapp_rate_';

    /**
     * Initialize REST API
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST routes
     */
    public function register_routes() {
        // Projects (authenticated only)
        register_rest_route($this->namespace, '/projects', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_projects'),
                'permission_callback' => array($this, 'check_read_permission'),
                'args' => $this->get_pagination_args(),
            ),
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_project'),
                'permission_callback' => array($this, 'check_write_permission'),
                'args' => $this->get_project_args(),
            ),
        ));

        register_rest_route($this->namespace, '/projects/(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_project'),
                'permission_callback' => array($this, 'check_read_permission'),
                'args' => array('id' => $this->get_id_arg()),
            ),
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_project'),
                'permission_callback' => array($this, 'check_write_permission'),
                'args' => array_merge(array('id' => $this->get_id_arg()), $this->get_project_args()),
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_project'),
                'permission_callback' => array($this, 'check_write_permission'),
                'args' => array('id' => $this->get_id_arg()),
            ),
        ));

        // Tasks (authenticated only)
        register_rest_route($this->namespace, '/tasks', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_tasks'),
                'permission_callback' => array($this, 'check_read_permission'),
                'args' => array_merge($this->get_pagination_args(), array(
                    'project_id' => array(
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                )),
            ),
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_task'),
                'permission_callback' => array($this, 'check_write_permission'),
                'args' => $this->get_task_args(),
            ),
        ));

        register_rest_route($this->namespace, '/tasks/(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_task'),
                'permission_callback' => array($this, 'check_read_permission'),
                'args' => array('id' => $this->get_id_arg()),
            ),
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_task'),
                'permission_callback' => array($this, 'check_write_permission'),
                'args' => array_merge(array('id' => $this->get_id_arg()), $this->get_task_args()),
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_task'),
                'permission_callback' => array($this, 'check_write_permission'),
                'args' => array('id' => $this->get_id_arg()),
            ),
        ));

        // Events (public read, authenticated write)
        register_rest_route($this->namespace, '/events', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_events'),
                'permission_callback' => array($this, 'check_public_read_permission'),
                'args' => array_merge($this->get_pagination_args(), array(
                    'upcoming' => array(
                        'type' => 'boolean',
                        'default' => false,
                    ),
                )),
            ),
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_event'),
                'permission_callback' => array($this, 'check_events_permission'),
                'args' => $this->get_event_args(),
            ),
        ));

        register_rest_route($this->namespace, '/events/(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_event'),
                'permission_callback' => array($this, 'check_public_read_permission'),
                'args' => array('id' => $this->get_id_arg()),
            ),
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_event'),
                'permission_callback' => array($this, 'check_events_permission'),
                'args' => array_merge(array('id' => $this->get_id_arg()), $this->get_event_args()),
            ),
        ));

        register_rest_route($this->namespace, '/events/(?P<id>\d+)/register', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'register_for_event'),
            'permission_callback' => array($this, 'check_public_write_permission'),
            'args' => array_merge(array('id' => $this->get_id_arg()), $this->get_registration_args()),
        ));

        // Calendar (public read)
        register_rest_route($this->namespace, '/calendar/(?P<year>\d+)/(?P<month>\d+)', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_calendar_events'),
            'permission_callback' => array($this, 'check_public_read_permission'),
            'args' => array(
                'year' => array(
                    'type' => 'integer',
                    'required' => true,
                    'minimum' => 2000,
                    'maximum' => 2100,
                    'sanitize_callback' => 'absint',
                ),
                'month' => array(
                    'type' => 'integer',
                    'required' => true,
                    'minimum' => 1,
                    'maximum' => 12,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));

        // Products (public read)
        register_rest_route($this->namespace, '/products', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_products'),
                'permission_callback' => array($this, 'check_public_read_permission'),
                'args' => $this->get_pagination_args(),
            ),
        ));

        register_rest_route($this->namespace, '/products/(?P<id>\d+)', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_product'),
            'permission_callback' => array($this, 'check_public_read_permission'),
            'args' => array('id' => $this->get_id_arg()),
        ));

        // Cart (session-based, rate limited)
        register_rest_route($this->namespace, '/cart', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_cart'),
                'permission_callback' => array($this, 'check_cart_permission'),
            ),
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array($this, 'add_to_cart'),
                'permission_callback' => array($this, 'check_cart_permission'),
                'args' => array(
                    'product_id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => array($this, 'validate_product_exists'),
                    ),
                    'quantity' => array(
                        'type' => 'integer',
                        'default' => 1,
                        'minimum' => 1,
                        'maximum' => 99,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array($this, 'clear_cart'),
                'permission_callback' => array($this, 'check_cart_permission'),
            ),
        ));

        register_rest_route($this->namespace, '/cart/(?P<key>[a-zA-Z0-9_]+)', array(
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_cart_item'),
                'permission_callback' => array($this, 'check_cart_permission'),
                'args' => array(
                    'key' => array(
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'quantity' => array(
                        'type' => 'integer',
                        'required' => true,
                        'minimum' => 0,
                        'maximum' => 99,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array($this, 'remove_cart_item'),
                'permission_callback' => array($this, 'check_cart_permission'),
                'args' => array(
                    'key' => array(
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            ),
        ));

        // Checkout (rate limited, validated)
        register_rest_route($this->namespace, '/checkout', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'process_checkout'),
            'permission_callback' => array($this, 'check_checkout_permission'),
            'args' => $this->get_checkout_args(),
        ));

        // Orders (authenticated)
        register_rest_route($this->namespace, '/orders', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_orders'),
            'permission_callback' => array($this, 'check_read_permission'),
            'args' => $this->get_pagination_args(),
        ));

        register_rest_route($this->namespace, '/orders/(?P<id>\d+)', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_order'),
            'permission_callback' => array($this, 'check_order_permission'),
            'args' => array('id' => $this->get_id_arg()),
        ));

        // Newsletter (rate limited)
        register_rest_route($this->namespace, '/newsletter/subscribe', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'newsletter_subscribe'),
            'permission_callback' => array($this, 'check_newsletter_permission'),
            'args' => array(
                'email' => array(
                    'type' => 'string',
                    'required' => true,
                    'format' => 'email',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => array($this, 'validate_email'),
                ),
                'first_name' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'last_name' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'hp_field' => array(
                    'type' => 'string',
                    'description' => 'Honeypot field - must be empty',
                ),
            ),
        ));

        // Membership (public read)
        register_rest_route($this->namespace, '/membership/levels', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_membership_levels'),
            'permission_callback' => array($this, 'check_public_read_permission'),
        ));

        // User account (authenticated)
        register_rest_route($this->namespace, '/account', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_account'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($this->namespace, '/account/tasks', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_user_tasks'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        register_rest_route($this->namespace, '/account/applications', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_user_applications'),
            'permission_callback' => array($this, 'check_user_logged_in'),
        ));

        // Event Applications
        register_rest_route($this->namespace, '/events/(?P<id>\d+)/apply', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'apply_for_event'),
            'permission_callback' => array($this, 'check_application_submit_permission'),
            'args' => array_merge(array('id' => $this->get_id_arg()), $this->get_application_args()),
        ));

        register_rest_route($this->namespace, '/events/(?P<id>\d+)/applications', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_event_applications'),
            'permission_callback' => array($this, 'check_events_permission'),
            'args' => array_merge(array('id' => $this->get_id_arg()), $this->get_pagination_args()),
        ));

        register_rest_route($this->namespace, '/applications', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_all_applications'),
            'permission_callback' => array($this, 'check_events_permission'),
            'args' => $this->get_pagination_args(),
        ));

        register_rest_route($this->namespace, '/applications/(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_application'),
                'permission_callback' => array($this, 'check_application_permission'),
                'args' => array('id' => $this->get_id_arg()),
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array($this, 'cancel_application'),
                'permission_callback' => array($this, 'check_application_permission'),
                'args' => array('id' => $this->get_id_arg()),
            ),
        ));

        register_rest_route($this->namespace, '/applications/(?P<id>\d+)/approve', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'approve_application'),
            'permission_callback' => array($this, 'check_events_permission'),
            'args' => array(
                'id' => $this->get_id_arg(),
                'notes' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));

        register_rest_route($this->namespace, '/applications/(?P<id>\d+)/reject', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'reject_application'),
            'permission_callback' => array($this, 'check_events_permission'),
            'args' => array(
                'id' => $this->get_id_arg(),
                'reason' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'notes' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));

        register_rest_route($this->namespace, '/applications/(?P<id>\d+)/waitlist', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'waitlist_application'),
            'permission_callback' => array($this, 'check_events_permission'),
            'args' => array('id' => $this->get_id_arg()),
        ));
    }

    // =========================================================================
    // PERMISSION CALLBACKS
    // =========================================================================

    /**
     * Check read permission (must be logged in)
     *
     * @return bool
     */
    public function check_read_permission() {
        return is_user_logged_in();
    }

    /**
     * Check write permission (must be able to edit posts)
     *
     * @return bool
     */
    public function check_write_permission() {
        return current_user_can('edit_posts');
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Check public read permission with rate limiting
     *
     * @param \WP_REST_Request $request Request.
     * @return bool|\WP_Error
     */
    public function check_public_read_permission($request) {
        // Rate limit: 100 requests per minute for reads
        return $this->check_rate_limit('public_read', 100, 60);
    }

    /**
     * Check public write permission with stricter rate limiting
     *
     * @param \WP_REST_Request $request Request.
     * @return bool|\WP_Error
     */
    public function check_public_write_permission($request) {
        // Rate limit: 10 requests per minute for writes
        return $this->check_rate_limit('public_write', 10, 60);
    }

    /**
     * Check cart permission (session-based)
     *
     * @param \WP_REST_Request $request Request.
     * @return bool|\WP_Error
     */
    public function check_cart_permission($request) {
        // Rate limit: 30 cart operations per minute
        return $this->check_rate_limit('cart', 30, 60);
    }

    /**
     * Check checkout permission with strict rate limiting
     *
     * @param \WP_REST_Request $request Request.
     * @return bool|\WP_Error
     */
    public function check_checkout_permission($request) {
        // Rate limit: 5 checkout attempts per minute
        return $this->check_rate_limit('checkout', 5, 60);
    }

    /**
     * Check newsletter subscription permission
     *
     * @param \WP_REST_Request $request Request.
     * @return bool|\WP_Error
     */
    public function check_newsletter_permission($request) {
        // Check honeypot field
        $honeypot = $request->get_param('hp_field');
        if (!empty($honeypot)) {
            // Bot detected, silently reject
            return new \WP_Error(
                'bot_detected',
                __('Subscription failed.', 'semigapp'),
                array('status' => 400)
            );
        }

        // Rate limit: 3 subscriptions per hour per IP
        return $this->check_rate_limit('newsletter', 3, 3600);
    }

    /**
     * Check application submission permission
     *
     * @param \WP_REST_Request $request Request.
     * @return bool|\WP_Error
     */
    public function check_application_submit_permission($request) {
        // Rate limit: 5 applications per hour
        return $this->check_rate_limit('application', 5, 3600);
    }

    /**
     * Check order permission (owner or admin)
     *
     * @param \WP_REST_Request $request Request.
     * @return bool
     */
    public function check_order_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        $order_id = absint($request->get_param('id'));
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');
        $order = $webshop->get_order($order_id);

        if (!$order) {
            return false;
        }

        return $order->user_id == get_current_user_id() || current_user_can('manage_options');
    }

    /**
     * Check events management permission
     *
     * @return bool
     */
    public function check_events_permission() {
        return current_user_can('manage_semigapp_events') || current_user_can('manage_options');
    }

    /**
     * Check application permission (owner or admin)
     *
     * @param \WP_REST_Request $request Request.
     * @return bool
     */
    public function check_application_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        if (current_user_can('manage_semigapp_events')) {
            return true;
        }

        $application_id = absint($request->get_param('id'));
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');
        $application = $events->get_application($application_id);

        if (!$application) {
            return false;
        }

        return $application->user_id == get_current_user_id();
    }

    /**
     * Check rate limit
     *
     * @param string $action  Action identifier.
     * @param int    $limit   Maximum requests.
     * @param int    $window  Time window in seconds.
     * @return bool|\WP_Error
     */
    private function check_rate_limit($action, $limit, $window) {
        $ip = $this->get_client_ip();
        $key = $this->rate_limit_prefix . $action . '_' . md5($ip);

        $current = get_transient($key);

        if ($current === false) {
            set_transient($key, 1, $window);
            return true;
        }

        if ($current >= $limit) {
            return new \WP_Error(
                'rate_limit_exceeded',
                __('Too many requests. Please try again later.', 'semigapp'),
                array('status' => 429)
            );
        }

        set_transient($key, $current + 1, $window);
        return true;
    }

    /**
     * Get client IP for rate limiting
     *
     * @return string
     */
    private function get_client_ip() {
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return '127.0.0.1';
    }

    // =========================================================================
    // ARGUMENT SCHEMAS
    // =========================================================================

    /**
     * Get ID argument schema
     *
     * @return array
     */
    private function get_id_arg() {
        return array(
            'type' => 'integer',
            'required' => true,
            'minimum' => 1,
            'sanitize_callback' => 'absint',
        );
    }

    /**
     * Get pagination arguments
     *
     * @return array
     */
    private function get_pagination_args() {
        return array(
            'per_page' => array(
                'type' => 'integer',
                'default' => 20,
                'minimum' => 1,
                'maximum' => 100,
                'sanitize_callback' => 'absint',
            ),
            'offset' => array(
                'type' => 'integer',
                'default' => 0,
                'minimum' => 0,
                'sanitize_callback' => 'absint',
            ),
            'status' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    /**
     * Get project arguments
     *
     * @return array
     */
    private function get_project_args() {
        return array(
            'name' => array(
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($value) {
                    return !empty(trim($value));
                },
            ),
            'description' => array(
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ),
            'status' => array(
                'type' => 'string',
                'enum' => array('planning', 'active', 'on-hold', 'completed', 'cancelled'),
                'default' => 'planning',
            ),
            'start_date' => array(
                'type' => 'string',
                'format' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'end_date' => array(
                'type' => 'string',
                'format' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    /**
     * Get task arguments
     *
     * @return array
     */
    private function get_task_args() {
        return array(
            'project_id' => array(
                'type' => 'integer',
                'required' => true,
                'sanitize_callback' => 'absint',
            ),
            'name' => array(
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'description' => array(
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ),
            'status' => array(
                'type' => 'string',
                'enum' => array('todo', 'in-progress', 'review', 'done'),
                'default' => 'todo',
            ),
            'priority' => array(
                'type' => 'string',
                'enum' => array('low', 'medium', 'high', 'urgent'),
                'default' => 'medium',
            ),
            'assigned_to' => array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'due_date' => array(
                'type' => 'string',
                'format' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    /**
     * Get event arguments
     *
     * @return array
     */
    private function get_event_args() {
        return array(
            'title' => array(
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'description' => array(
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ),
            'start_date' => array(
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'end_date' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'location' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'max_attendees' => array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ),
        );
    }

    /**
     * Get registration arguments
     *
     * @return array
     */
    private function get_registration_args() {
        return array(
            'name' => array(
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'email' => array(
                'type' => 'string',
                'required' => true,
                'format' => 'email',
                'sanitize_callback' => 'sanitize_email',
            ),
            'phone' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    /**
     * Get checkout arguments
     *
     * @return array
     */
    private function get_checkout_args() {
        return array(
            'payment_method' => array(
                'type' => 'string',
                'required' => true,
                'enum' => array('klarna', 'swish', 'stripe'),
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'billing_address' => array(
                'type' => 'object',
                'required' => true,
                'properties' => array(
                    'email' => array(
                        'type' => 'string',
                        'format' => 'email',
                        'required' => true,
                    ),
                    'first_name' => array(
                        'type' => 'string',
                        'required' => true,
                    ),
                    'last_name' => array(
                        'type' => 'string',
                        'required' => true,
                    ),
                    'address_1' => array(
                        'type' => 'string',
                        'required' => true,
                    ),
                    'postcode' => array(
                        'type' => 'string',
                        'required' => true,
                    ),
                    'city' => array(
                        'type' => 'string',
                        'required' => true,
                    ),
                    'country' => array(
                        'type' => 'string',
                        'default' => 'SE',
                    ),
                    'phone' => array(
                        'type' => 'string',
                    ),
                ),
            ),
            'customer_note' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
        );
    }

    /**
     * Get application arguments
     *
     * @return array
     */
    private function get_application_args() {
        return array(
            'name' => array(
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'email' => array(
                'type' => 'string',
                'required' => true,
                'format' => 'email',
                'sanitize_callback' => 'sanitize_email',
            ),
            'phone' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'message' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'custom_fields' => array(
                'type' => 'object',
            ),
        );
    }

    // =========================================================================
    // VALIDATION CALLBACKS
    // =========================================================================

    /**
     * Validate email format
     *
     * @param string $email Email address.
     * @return bool
     */
    public function validate_email($email) {
        return is_email($email);
    }

    /**
     * Validate product exists
     *
     * @param int $product_id Product ID.
     * @return bool
     */
    public function validate_product_exists($product_id) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');
        $product = $webshop->get_product(absint($product_id));
        return !empty($product);
    }

    // =========================================================================
    // PROJECT ENDPOINTS
    // =========================================================================

    public function get_projects($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        $args = array(
            'limit' => $request->get_param('per_page') ?: 20,
            'offset' => $request->get_param('offset') ?: 0,
        );

        if ($request->get_param('status')) {
            $args['where']['status'] = $request->get_param('status');
        }

        return rest_ensure_response($projects->get_projects($args));
    }

    public function get_project($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        $project = $projects->get_project($request->get_param('id'));

        if (!$project) {
            return new \WP_Error('not_found', __('Project not found.', 'semigapp'), array('status' => 404));
        }

        return rest_ensure_response($project);
    }

    public function create_project($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        $project_id = $projects->create_project($request->get_params());

        if (!$project_id) {
            return new \WP_Error('create_failed', __('Could not create project.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response($projects->get_project($project_id));
    }

    public function update_project($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        $result = $projects->update_project($request->get_param('id'), $request->get_params());

        if (!$result) {
            return new \WP_Error('update_failed', __('Could not update project.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response($projects->get_project($request->get_param('id')));
    }

    public function delete_project($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        $result = $projects->delete_project($request->get_param('id'));

        if (!$result) {
            return new \WP_Error('delete_failed', __('Could not delete project.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response(array('deleted' => true));
    }

    // =========================================================================
    // TASK ENDPOINTS
    // =========================================================================

    public function get_tasks($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        $project_id = $request->get_param('project_id');

        if ($project_id) {
            return rest_ensure_response($projects->get_tasks($project_id));
        }

        // Get user's tasks
        if (is_user_logged_in()) {
            return rest_ensure_response($projects->get_user_tasks(get_current_user_id()));
        }

        return rest_ensure_response(array());
    }

    public function get_task($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        $task = $projects->get_task($request->get_param('id'));

        if (!$task) {
            return new \WP_Error('not_found', __('Task not found.', 'semigapp'), array('status' => 404));
        }

        return rest_ensure_response($task);
    }

    public function create_task($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        $task_id = $projects->create_task($request->get_params());

        if (!$task_id) {
            return new \WP_Error('create_failed', __('Could not create task.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response($projects->get_task($task_id));
    }

    public function update_task($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        $result = $projects->update_task($request->get_param('id'), $request->get_params());

        if (!$result) {
            return new \WP_Error('update_failed', __('Could not update task.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response($projects->get_task($request->get_param('id')));
    }

    public function delete_task($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        $result = $projects->delete_task($request->get_param('id'));

        if (!$result) {
            return new \WP_Error('delete_failed', __('Could not delete task.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response(array('deleted' => true));
    }

    // =========================================================================
    // EVENT ENDPOINTS
    // =========================================================================

    public function get_events($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $args = array(
            'where' => array('status' => 'published'),
            'limit' => $request->get_param('per_page') ?: 20,
        );

        if ($request->get_param('upcoming')) {
            return rest_ensure_response($events->get_upcoming_events($args['limit']));
        }

        return rest_ensure_response($events->get_events($args));
    }

    public function get_event($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $event = $events->get_event($request->get_param('id'));

        if (!$event) {
            return new \WP_Error('not_found', __('Event not found.', 'semigapp'), array('status' => 404));
        }

        return rest_ensure_response($event);
    }

    public function create_event($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $event_id = $events->create_event($request->get_params());

        if (!$event_id) {
            return new \WP_Error('create_failed', __('Could not create event.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response($events->get_event($event_id));
    }

    public function update_event($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $result = $events->update_event($request->get_param('id'), $request->get_params());

        if (!$result) {
            return new \WP_Error('update_failed', __('Could not update event.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response($events->get_event($request->get_param('id')));
    }

    public function register_for_event($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $registration_id = $events->register($request->get_param('id'), $request->get_params());

        if (!$registration_id) {
            return new \WP_Error('registration_failed', __('Could not register for event.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response(array(
            'registration_id' => $registration_id,
            'message' => __('Registration successful!', 'semigapp'),
        ));
    }

    public function get_calendar_events($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $year = $request->get_param('year');
        $month = $request->get_param('month');

        return rest_ensure_response($events->get_calendar_events($year, $month));
    }

    // =========================================================================
    // SHOP ENDPOINTS
    // =========================================================================

    public function get_products($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        $args = array(
            'limit' => $request->get_param('per_page') ?: 20,
            'offset' => $request->get_param('offset') ?: 0,
        );

        return rest_ensure_response($webshop->get_products($args));
    }

    public function get_product($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        $product = $webshop->get_product($request->get_param('id'));

        if (!$product) {
            return new \WP_Error('not_found', __('Product not found.', 'semigapp'), array('status' => 404));
        }

        return rest_ensure_response($product);
    }

    public function get_cart($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        return rest_ensure_response(array(
            'items' => $webshop->get_cart(),
            'totals' => $webshop->get_cart_totals(),
            'count' => $webshop->get_cart_count(),
        ));
    }

    public function add_to_cart($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        $result = $webshop->add_to_cart(
            $request->get_param('product_id'),
            $request->get_param('quantity') ?: 1
        );

        if (!$result) {
            return new \WP_Error('add_failed', __('Could not add to cart.', 'semigapp'), array('status' => 400));
        }

        return $this->get_cart($request);
    }

    public function update_cart_item($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        $result = $webshop->update_cart_item(
            $request->get_param('key'),
            $request->get_param('quantity')
        );

        if (!$result) {
            return new \WP_Error('update_failed', __('Could not update cart.', 'semigapp'), array('status' => 400));
        }

        return $this->get_cart($request);
    }

    public function remove_cart_item($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        $result = $webshop->remove_from_cart($request->get_param('key'));

        if (!$result) {
            return new \WP_Error('remove_failed', __('Could not remove from cart.', 'semigapp'), array('status' => 400));
        }

        return $this->get_cart($request);
    }

    public function clear_cart($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        $webshop->clear_cart();

        return rest_ensure_response(array('cleared' => true));
    }

    public function process_checkout($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        $order_id = $webshop->create_order($request->get_params());

        if (!$order_id) {
            return new \WP_Error('checkout_failed', __('Could not process checkout.', 'semigapp'), array('status' => 400));
        }

        $order = $webshop->get_order($order_id);
        $gateway = $webshop->get_gateway($order->payment_method);

        if ($gateway) {
            $payment_result = $gateway->process_payment($order);
            return rest_ensure_response($payment_result);
        }

        return rest_ensure_response(array(
            'order_id' => $order_id,
            'order_number' => $order->order_number,
        ));
    }

    public function get_orders($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        if (current_user_can('manage_options')) {
            return rest_ensure_response($webshop->get_orders());
        }

        return rest_ensure_response($webshop->get_user_orders(get_current_user_id()));
    }

    public function get_order($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop = $plugin->get_module('webshop');

        $order = $webshop->get_order($request->get_param('id'));

        if (!$order) {
            return new \WP_Error('not_found', __('Order not found.', 'semigapp'), array('status' => 404));
        }

        return rest_ensure_response($order);
    }

    // =========================================================================
    // NEWSLETTER ENDPOINTS
    // =========================================================================

    public function newsletter_subscribe($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $newsletter = $plugin->get_module('newsletter');

        $result = $newsletter->subscribe(
            $request->get_param('email'),
            $request->get_params()
        );

        if (!$result) {
            return new \WP_Error('subscribe_failed', __('Could not subscribe.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Thank you for subscribing!', 'semigapp'),
        ));
    }

    // =========================================================================
    // MEMBERSHIP ENDPOINTS
    // =========================================================================

    public function get_membership_levels($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $membership = $plugin->get_module('membership');

        return rest_ensure_response($membership->get_levels());
    }

    // =========================================================================
    // ACCOUNT ENDPOINTS
    // =========================================================================

    public function get_account($request) {
        $user = wp_get_current_user();
        $plugin = \SemigApp\Plugin::get_instance();

        $membership = $plugin->get_module('membership');
        $webshop = $plugin->get_module('webshop');
        $events = $plugin->get_module('events');

        return rest_ensure_response(array(
            'user' => array(
                'id' => $user->ID,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ),
            'membership' => $membership->get_member_by_user($user->ID),
            'orders_count' => count($webshop->get_user_orders($user->ID)),
            'events_count' => count($events->get_user_registrations($user->ID)),
        ));
    }

    public function get_user_tasks($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects = $plugin->get_module('projects');

        return rest_ensure_response($projects->get_user_tasks(get_current_user_id()));
    }

    public function get_user_applications($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        return rest_ensure_response($events->get_user_applications(get_current_user_id()));
    }

    // =========================================================================
    // APPLICATION ENDPOINTS
    // =========================================================================

    /**
     * Submit application for event
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error Response.
     */
    public function apply_for_event($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $event_id = $request->get_param('id');
        $event = $events->get_event($event_id);

        if (!$event) {
            return new \WP_Error('not_found', __('Event not found.', 'semigapp'), array('status' => 404));
        }

        if (!$events->can_apply($event)) {
            return new \WP_Error('applications_closed', __('Applications are not being accepted for this event.', 'semigapp'), array('status' => 400));
        }

        $application_id = $events->submit_application($event_id, $request->get_params());

        if (!$application_id) {
            return new \WP_Error('application_failed', __('Could not submit application. You may have already applied.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response(array(
            'application_id' => $application_id,
            'message' => __('Application submitted successfully!', 'semigapp'),
        ));
    }

    /**
     * Get applications for an event
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response Response.
     */
    public function get_event_applications($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $event_id = $request->get_param('id');
        $args = array();

        if ($request->get_param('status')) {
            $args['where']['status'] = $request->get_param('status');
        }

        $applications = $events->get_applications($event_id, $args);
        $counts = $events->get_application_counts($event_id);

        return rest_ensure_response(array(
            'applications' => $applications,
            'counts' => $counts,
        ));
    }

    /**
     * Get all applications (admin)
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response Response.
     */
    public function get_all_applications($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $args = array(
            'limit' => $request->get_param('per_page') ?: 20,
            'offset' => $request->get_param('offset') ?: 0,
        );

        if ($request->get_param('status')) {
            $args['where']['status'] = $request->get_param('status');
        }

        if ($request->get_param('event_id')) {
            $args['where']['event_id'] = $request->get_param('event_id');
        }

        return rest_ensure_response($events->get_all_applications($args));
    }

    /**
     * Get single application
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error Response.
     */
    public function get_application($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $application = $events->get_application($request->get_param('id'));

        if (!$application) {
            return new \WP_Error('not_found', __('Application not found.', 'semigapp'), array('status' => 404));
        }

        return rest_ensure_response($application);
    }

    /**
     * Cancel application
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error Response.
     */
    public function cancel_application($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $result = $events->cancel_application($request->get_param('id'));

        if (!$result) {
            return new \WP_Error('cancel_failed', __('Could not cancel application.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response(array(
            'cancelled' => true,
            'message' => __('Application cancelled.', 'semigapp'),
        ));
    }

    /**
     * Approve application
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error Response.
     */
    public function approve_application($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $notes = $request->get_param('notes') ?: '';
        $result = $events->approve_application($request->get_param('id'), $notes);

        if ($result === false) {
            return new \WP_Error('approve_failed', __('Could not approve application.', 'semigapp'), array('status' => 400));
        }

        $application = $events->get_application($request->get_param('id'));

        return rest_ensure_response(array(
            'approved' => true,
            'registration_id' => $result,
            'application' => $application,
            'message' => __('Application approved successfully.', 'semigapp'),
        ));
    }

    /**
     * Reject application
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error Response.
     */
    public function reject_application($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $reason = $request->get_param('reason') ?: '';
        $notes = $request->get_param('notes') ?: '';
        $result = $events->reject_application($request->get_param('id'), $reason, $notes);

        if (!$result) {
            return new \WP_Error('reject_failed', __('Could not reject application.', 'semigapp'), array('status' => 400));
        }

        return rest_ensure_response(array(
            'rejected' => true,
            'message' => __('Application rejected.', 'semigapp'),
        ));
    }

    /**
     * Add application to waitlist
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error Response.
     */
    public function waitlist_application($request) {
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');

        $result = $events->add_to_waitlist($request->get_param('id'));

        if (!$result) {
            return new \WP_Error('waitlist_failed', __('Could not add to waitlist.', 'semigapp'), array('status' => 400));
        }

        $application = $events->get_application($request->get_param('id'));

        return rest_ensure_response(array(
            'waitlisted' => true,
            'position' => $application->waitlist_position,
            'message' => __('Application added to waitlist.', 'semigapp'),
        ));
    }
}
