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
 * Handles REST API endpoints
 */
class Rest_Controller {

    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'semigapp/v1';

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
        // Projects
        register_rest_route($this->namespace, '/projects', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_projects'),
                'permission_callback' => array($this, 'check_read_permission'),
            ),
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_project'),
                'permission_callback' => array($this, 'check_write_permission'),
            ),
        ));

        register_rest_route($this->namespace, '/projects/(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_project'),
                'permission_callback' => array($this, 'check_read_permission'),
            ),
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_project'),
                'permission_callback' => array($this, 'check_write_permission'),
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_project'),
                'permission_callback' => array($this, 'check_write_permission'),
            ),
        ));

        // Tasks
        register_rest_route($this->namespace, '/tasks', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_tasks'),
                'permission_callback' => array($this, 'check_read_permission'),
            ),
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_task'),
                'permission_callback' => array($this, 'check_write_permission'),
            ),
        ));

        register_rest_route($this->namespace, '/tasks/(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_task'),
                'permission_callback' => array($this, 'check_read_permission'),
            ),
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_task'),
                'permission_callback' => array($this, 'check_write_permission'),
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_task'),
                'permission_callback' => array($this, 'check_write_permission'),
            ),
        ));

        // Events
        register_rest_route($this->namespace, '/events', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_events'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_event'),
                'permission_callback' => array($this, 'check_write_permission'),
            ),
        ));

        register_rest_route($this->namespace, '/events/(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_event'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_event'),
                'permission_callback' => array($this, 'check_write_permission'),
            ),
        ));

        register_rest_route($this->namespace, '/events/(?P<id>\d+)/register', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'register_for_event'),
            'permission_callback' => '__return_true',
        ));

        // Calendar
        register_rest_route($this->namespace, '/calendar/(?P<year>\d+)/(?P<month>\d+)', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_calendar_events'),
            'permission_callback' => '__return_true',
        ));

        // Products
        register_rest_route($this->namespace, '/products', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_products'),
                'permission_callback' => '__return_true',
            ),
        ));

        register_rest_route($this->namespace, '/products/(?P<id>\d+)', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_product'),
            'permission_callback' => '__return_true',
        ));

        // Cart
        register_rest_route($this->namespace, '/cart', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_cart'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array($this, 'add_to_cart'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array($this, 'clear_cart'),
                'permission_callback' => '__return_true',
            ),
        ));

        register_rest_route($this->namespace, '/cart/(?P<key>[a-zA-Z0-9_]+)', array(
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_cart_item'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array($this, 'remove_cart_item'),
                'permission_callback' => '__return_true',
            ),
        ));

        // Checkout
        register_rest_route($this->namespace, '/checkout', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'process_checkout'),
            'permission_callback' => '__return_true',
        ));

        // Orders
        register_rest_route($this->namespace, '/orders', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_orders'),
            'permission_callback' => array($this, 'check_read_permission'),
        ));

        register_rest_route($this->namespace, '/orders/(?P<id>\d+)', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_order'),
            'permission_callback' => array($this, 'check_order_permission'),
        ));

        // Newsletter
        register_rest_route($this->namespace, '/newsletter/subscribe', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'newsletter_subscribe'),
            'permission_callback' => '__return_true',
        ));

        // Membership
        register_rest_route($this->namespace, '/membership/levels', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_membership_levels'),
            'permission_callback' => '__return_true',
        ));

        // User account
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
            'permission_callback' => '__return_true',
        ));

        register_rest_route($this->namespace, '/events/(?P<id>\d+)/applications', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_event_applications'),
            'permission_callback' => array($this, 'check_events_permission'),
        ));

        register_rest_route($this->namespace, '/applications', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'get_all_applications'),
            'permission_callback' => array($this, 'check_events_permission'),
        ));

        register_rest_route($this->namespace, '/applications/(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'get_application'),
                'permission_callback' => array($this, 'check_application_permission'),
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array($this, 'cancel_application'),
                'permission_callback' => array($this, 'check_application_permission'),
            ),
        ));

        register_rest_route($this->namespace, '/applications/(?P<id>\d+)/approve', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'approve_application'),
            'permission_callback' => array($this, 'check_events_permission'),
        ));

        register_rest_route($this->namespace, '/applications/(?P<id>\d+)/reject', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'reject_application'),
            'permission_callback' => array($this, 'check_events_permission'),
        ));

        register_rest_route($this->namespace, '/applications/(?P<id>\d+)/waitlist', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array($this, 'waitlist_application'),
            'permission_callback' => array($this, 'check_events_permission'),
        ));
    }

    /**
     * Check read permission
     *
     * @return bool
     */
    public function check_read_permission() {
        return is_user_logged_in();
    }

    /**
     * Check write permission
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
     * Check order permission
     *
     * @param \WP_REST_Request $request Request.
     * @return bool
     */
    public function check_order_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        $order_id = $request->get_param('id');
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
        return current_user_can('manage_semigapp_events');
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

        $application_id = $request->get_param('id');
        $plugin = \SemigApp\Plugin::get_instance();
        $events = $plugin->get_module('events');
        $application = $events->get_application($application_id);

        if (!$application) {
            return false;
        }

        return $application->user_id == get_current_user_id();
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
