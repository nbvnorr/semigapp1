<?php
/**
 * Admin Class
 *
 * @package SemigApp
 */

namespace SemigApp\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Admin
 *
 * Handles all admin functionality
 */
class Admin {

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
     * Initialize admin
     */
    public function init() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_semigapp_admin_action', array($this, 'handle_ajax'));
    }

    /**
     * Register admin menu
     */
    public function register_menu() {
        // Main menu
        add_menu_page(
            __('SemigApp', 'semigapp'),
            __('SemigApp', 'semigapp'),
            'manage_options',
            'semigapp',
            array($this, 'render_dashboard'),
            'dashicons-building',
            25
        );

        // Dashboard submenu
        add_submenu_page(
            'semigapp',
            __('Dashboard', 'semigapp'),
            __('Dashboard', 'semigapp'),
            'manage_options',
            'semigapp',
            array($this, 'render_dashboard')
        );

        // Projects
        add_submenu_page(
            'semigapp',
            __('Projects', 'semigapp'),
            __('Projects', 'semigapp'),
            'manage_semigapp_projects',
            'semigapp-projects',
            array($this, 'render_projects')
        );

        // Events
        add_submenu_page(
            'semigapp',
            __('Events', 'semigapp'),
            __('Events', 'semigapp'),
            'manage_semigapp_events',
            'semigapp-events',
            array($this, 'render_events')
        );

        // Members
        add_submenu_page(
            'semigapp',
            __('Members', 'semigapp'),
            __('Members', 'semigapp'),
            'manage_semigapp_members',
            'semigapp-members',
            array($this, 'render_members')
        );

        // Shop
        add_submenu_page(
            'semigapp',
            __('Products', 'semigapp'),
            __('Products', 'semigapp'),
            'manage_semigapp_shop',
            'semigapp-shop',
            array($this, 'render_products')
        );

        // Orders
        add_submenu_page(
            'semigapp',
            __('Orders', 'semigapp'),
            __('Orders', 'semigapp'),
            'manage_semigapp_orders',
            'semigapp-orders',
            array($this, 'render_orders')
        );

        // Newsletter
        add_submenu_page(
            'semigapp',
            __('Newsletter', 'semigapp'),
            __('Newsletter', 'semigapp'),
            'manage_semigapp_newsletter',
            'semigapp-newsletter',
            array($this, 'render_newsletter')
        );

        // Settings
        add_submenu_page(
            'semigapp',
            __('Settings', 'semigapp'),
            __('Settings', 'semigapp'),
            'manage_semigapp_settings',
            'semigapp-settings',
            array($this, 'render_settings')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting('semigapp_general_settings', 'semigapp_general_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        // Projects settings
        register_setting('semigapp_projects_settings', 'semigapp_projects_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        // Events settings
        register_setting('semigapp_events_settings', 'semigapp_events_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        // Membership settings
        register_setting('semigapp_membership_settings', 'semigapp_membership_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        // Webshop settings
        register_setting('semigapp_webshop_settings', 'semigapp_webshop_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        // Payment settings
        register_setting('semigapp_payments_settings', 'semigapp_payments_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        // Newsletter settings
        register_setting('semigapp_newsletter_settings', 'semigapp_newsletter_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        // Email settings
        register_setting('semigapp_emails_settings', 'semigapp_emails_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    /**
     * Sanitize settings
     *
     * @param array $input Input settings.
     * @return array Sanitized settings.
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_settings($value);
            } elseif (is_bool($value) || in_array($value, array('0', '1', 'true', 'false'))) {
                $sanitized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = floatval($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Render dashboard
     */
    public function render_dashboard() {
        $stats = $this->get_dashboard_stats();
        include SEMIGAPP_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Get dashboard statistics
     *
     * @return array Statistics.
     */
    private function get_dashboard_stats() {
        $db = \SemigApp\Database::get_instance();

        return array(
            'projects' => array(
                'total' => $db->count('projects'),
                'active' => $db->count('projects', array('status' => 'active')),
            ),
            'tasks' => array(
                'total' => $db->count('tasks'),
                'completed' => $db->count('tasks', array('status' => 'completed')),
                'pending' => $db->count('tasks', array('status' => 'pending')),
            ),
            'events' => array(
                'total' => $db->count('events'),
                'upcoming' => $this->count_upcoming_events(),
            ),
            'members' => array(
                'total' => $db->count('members'),
                'active' => $db->count('members', array('status' => 'active')),
            ),
            'orders' => array(
                'total' => $db->count('orders'),
                'pending' => $db->count('orders', array('status' => 'pending')),
                'revenue' => $this->get_total_revenue(),
            ),
            'subscribers' => array(
                'total' => $db->count('subscribers'),
                'active' => $db->count('subscribers', array('status' => 'active')),
            ),
        );
    }

    /**
     * Count upcoming events
     *
     * @return int Count.
     */
    private function count_upcoming_events() {
        global $wpdb;
        $table = $wpdb->prefix . 'semigapp_events';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE start_date > %s AND status = 'published'",
            current_time('mysql')
        ));
    }

    /**
     * Get total revenue
     *
     * @return float Revenue.
     */
    private function get_total_revenue() {
        global $wpdb;
        $table = $wpdb->prefix . 'semigapp_orders';

        return (float) $wpdb->get_var(
            "SELECT SUM(total) FROM $table WHERE payment_status = 'completed'"
        );
    }

    /**
     * Render projects page
     */
    public function render_projects() {
        $plugin = \SemigApp\Plugin::get_instance();
        $projects_module = $plugin->get_module('projects');

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

        switch ($action) {
            case 'new':
            case 'edit':
                $project = $project_id ? $projects_module->get_project($project_id) : null;
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/projects/edit.php';
                break;

            case 'view':
                $project = $projects_module->get_project($project_id);
                $tasks = $projects_module->get_tasks($project_id);
                $stats = $projects_module->get_project_stats($project_id);
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/projects/view.php';
                break;

            default:
                $projects = $projects_module->get_projects();
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/projects/list.php';
        }
    }

    /**
     * Render events page
     */
    public function render_events() {
        $plugin = \SemigApp\Plugin::get_instance();
        $events_module = $plugin->get_module('events');

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

        switch ($action) {
            case 'new':
            case 'edit':
                $event = $event_id ? $events_module->get_event($event_id) : null;
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/events/edit.php';
                break;

            case 'view':
                $event = $events_module->get_event($event_id);
                $registrations = $events_module->get_registrations($event_id);
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/events/view.php';
                break;

            default:
                $events = $events_module->get_events();
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/events/list.php';
        }
    }

    /**
     * Render members page
     */
    public function render_members() {
        $plugin = \SemigApp\Plugin::get_instance();
        $membership_module = $plugin->get_module('membership');

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;

        switch ($action) {
            case 'levels':
                $levels = $membership_module->get_levels(array('where' => array()));
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/members/levels.php';
                break;

            case 'edit-level':
                $level_id = isset($_GET['level_id']) ? intval($_GET['level_id']) : 0;
                $level = $level_id ? $membership_module->get_level($level_id) : null;
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/members/edit-level.php';
                break;

            case 'view':
                $member = $membership_module->get_member($member_id);
                $payments = $membership_module->get_member_payments($member_id);
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/members/view.php';
                break;

            default:
                $members = $membership_module->get_members();
                $levels = $membership_module->get_levels(array('where' => array()));
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/members/list.php';
        }
    }

    /**
     * Render products page
     */
    public function render_products() {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop_module = $plugin->get_module('webshop');

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

        switch ($action) {
            case 'new':
            case 'edit':
                $product = $product_id ? $webshop_module->get_product($product_id) : null;
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/shop/edit-product.php';
                break;

            default:
                $products = $webshop_module->get_products(array('where' => array()));
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/shop/products.php';
        }
    }

    /**
     * Render orders page
     */
    public function render_orders() {
        $plugin = \SemigApp\Plugin::get_instance();
        $webshop_module = $plugin->get_module('webshop');

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

        switch ($action) {
            case 'view':
                $order = $webshop_module->get_order($order_id);
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/shop/order.php';
                break;

            default:
                $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
                $args = array();
                if ($status) {
                    $args['where']['status'] = $status;
                }
                $orders = $webshop_module->get_orders($args);
                $statuses = $webshop_module->get_order_statuses();
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/shop/orders.php';
        }
    }

    /**
     * Render newsletter page
     */
    public function render_newsletter() {
        $plugin = \SemigApp\Plugin::get_instance();
        $newsletter_module = $plugin->get_module('newsletter');

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'subscribers';

        switch ($action) {
            case 'campaigns':
                $campaigns = $newsletter_module->get_campaigns();
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/newsletter/campaigns.php';
                break;

            case 'new-campaign':
            case 'edit-campaign':
                $campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
                $campaign = $campaign_id ? $newsletter_module->get_campaign($campaign_id) : null;
                $lists = $newsletter_module->get_lists();
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/newsletter/edit-campaign.php';
                break;

            case 'lists':
                $lists = $newsletter_module->get_lists();
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/newsletter/lists.php';
                break;

            default:
                $subscribers = $newsletter_module->get_subscribers();
                $lists = $newsletter_module->get_lists();
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/newsletter/subscribers.php';
        }
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        $tabs = array(
            'general' => __('General', 'semigapp'),
            'projects' => __('Projects', 'semigapp'),
            'events' => __('Events', 'semigapp'),
            'membership' => __('Membership', 'semigapp'),
            'webshop' => __('Webshop', 'semigapp'),
            'payments' => __('Payments', 'semigapp'),
            'newsletter' => __('Newsletter', 'semigapp'),
            'emails' => __('Emails', 'semigapp'),
        );

        include SEMIGAPP_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Handle AJAX requests
     */
    public function handle_ajax() {
        check_ajax_referer('semigapp_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'semigapp')));
        }

        $action = isset($_POST['admin_action']) ? sanitize_text_field($_POST['admin_action']) : '';
        $module = isset($_POST['module']) ? sanitize_text_field($_POST['module']) : '';

        $plugin = \SemigApp\Plugin::get_instance();
        $target_module = $plugin->get_module($module);

        if (!$target_module) {
            wp_send_json_error(array('message' => __('Invalid module.', 'semigapp')));
        }

        switch ($action) {
            case 'save':
                $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                $data = isset($_POST['data']) ? $_POST['data'] : array();

                if ($id) {
                    $method = 'update_' . rtrim($module, 's');
                    if (method_exists($target_module, $method)) {
                        $result = $target_module->$method($id, $data);
                    }
                } else {
                    $method = 'create_' . rtrim($module, 's');
                    if (method_exists($target_module, $method)) {
                        $result = $target_module->$method($data);
                    }
                }

                if (!empty($result)) {
                    wp_send_json_success(array('id' => $result));
                } else {
                    wp_send_json_error(array('message' => __('Operation failed.', 'semigapp')));
                }
                break;

            case 'delete':
                $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                $method = 'delete_' . rtrim($module, 's');

                if (method_exists($target_module, $method)) {
                    $result = $target_module->$method($id);
                    if ($result) {
                        wp_send_json_success();
                    }
                }

                wp_send_json_error(array('message' => __('Could not delete.', 'semigapp')));
                break;

            default:
                wp_send_json_error(array('message' => __('Invalid action.', 'semigapp')));
        }
    }
}
