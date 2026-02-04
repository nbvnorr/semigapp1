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
        add_action('wp_ajax_semigapp_get_application_details', array($this, 'ajax_get_application_details'));
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
            $wpdb->prepare(
                "SELECT SUM(total) FROM $table WHERE payment_status = %s",
                'completed'
            )
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

            case 'applications':
                $event = $event_id ? $events_module->get_event($event_id) : null;
                $current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
                $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

                $args = array();
                if ($event_id) {
                    $args['where']['event_id'] = $event_id;
                }
                if ($current_status) {
                    $args['where']['status'] = $current_status;
                }
                if ($search) {
                    $args['search'] = $search;
                    $args['search_columns'] = array('applicant_name', 'applicant_email');
                }

                if ($event_id) {
                    $applications = $events_module->get_applications($event_id, $args);
                } else {
                    $applications = $events_module->get_all_applications($args);
                }

                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/events/applications.php';
                break;

            case 'application-fields':
                $event = $events_module->get_event($event_id);
                $fields = $events_module->get_application_fields($event_id);
                include SEMIGAPP_PLUGIN_DIR . 'templates/admin/events/application-fields.php';
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

        // Handle application field actions
        if ($action === 'save_application_field') {
            $this->handle_save_application_field();
            return;
        }

        if ($action === 'delete_application_field') {
            $this->handle_delete_application_field();
            return;
        }

        if ($action === 'reorder_application_fields') {
            $this->handle_reorder_application_fields();
            return;
        }

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

    /**
     * AJAX handler for getting application details
     */
    public function ajax_get_application_details() {
        check_ajax_referer('semigapp_admin', 'nonce');

        if (!current_user_can('manage_semigapp_events')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'semigapp')));
        }

        $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;

        if (!$application_id) {
            wp_send_json_error(array('message' => __('Invalid application ID.', 'semigapp')));
        }

        $plugin = \SemigApp\Plugin::get_instance();
        $events_module = $plugin->get_module('events');
        $application = $events_module->get_application($application_id);

        if (!$application) {
            wp_send_json_error(array('message' => __('Application not found.', 'semigapp')));
        }

        $statuses = $events_module->get_application_statuses();
        $custom_fields = $events_module->get_application_fields($application->event_id);

        ob_start();
        ?>
        <div class="semigapp-application-details">
            <div class="semigapp-detail-row">
                <label><?php esc_html_e('Status', 'semigapp'); ?></label>
                <span class="semigapp-status semigapp-status-<?php echo esc_attr($application->status); ?>">
                    <?php echo esc_html($statuses[$application->status] ?? $application->status); ?>
                </span>
            </div>

            <div class="semigapp-detail-row">
                <label><?php esc_html_e('Applicant Name', 'semigapp'); ?></label>
                <span><?php echo esc_html($application->applicant_name); ?></span>
            </div>

            <div class="semigapp-detail-row">
                <label><?php esc_html_e('Email', 'semigapp'); ?></label>
                <span><a href="mailto:<?php echo esc_attr($application->applicant_email); ?>"><?php echo esc_html($application->applicant_email); ?></a></span>
            </div>

            <?php if (!empty($application->applicant_phone)) : ?>
                <div class="semigapp-detail-row">
                    <label><?php esc_html_e('Phone', 'semigapp'); ?></label>
                    <span><?php echo esc_html($application->applicant_phone); ?></span>
                </div>
            <?php endif; ?>

            <div class="semigapp-detail-row">
                <label><?php esc_html_e('Number of Attendees', 'semigapp'); ?></label>
                <span><?php echo esc_html($application->attendees); ?></span>
            </div>

            <?php if (!empty($application->motivation)) : ?>
                <div class="semigapp-detail-row">
                    <label><?php esc_html_e('Motivation', 'semigapp'); ?></label>
                    <div class="semigapp-detail-content"><?php echo nl2br(esc_html($application->motivation)); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($application->custom_fields) && !empty($custom_fields)) : ?>
                <h4><?php esc_html_e('Additional Information', 'semigapp'); ?></h4>
                <?php foreach ($custom_fields as $field) : ?>
                    <?php $value = $application->custom_fields[$field->field_name] ?? ''; ?>
                    <?php if (!empty($value)) : ?>
                        <div class="semigapp-detail-row">
                            <label><?php echo esc_html($field->field_label); ?></label>
                            <span><?php echo esc_html($value); ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="semigapp-detail-row">
                <label><?php esc_html_e('Applied On', 'semigapp'); ?></label>
                <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($application->created_at))); ?></span>
            </div>

            <?php if ($application->reviewed_at) : ?>
                <div class="semigapp-detail-row">
                    <label><?php esc_html_e('Reviewed On', 'semigapp'); ?></label>
                    <span>
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($application->reviewed_at))); ?>
                        <?php if ($application->reviewer) : ?>
                            <?php printf(esc_html__('by %s', 'semigapp'), esc_html($application->reviewer->display_name)); ?>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($application->status === 'waitlisted' && $application->waitlist_position) : ?>
                <div class="semigapp-detail-row">
                    <label><?php esc_html_e('Waitlist Position', 'semigapp'); ?></label>
                    <span>#<?php echo esc_html($application->waitlist_position); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($application->rejection_reason)) : ?>
                <div class="semigapp-detail-row">
                    <label><?php esc_html_e('Rejection Reason', 'semigapp'); ?></label>
                    <div class="semigapp-detail-content"><?php echo nl2br(esc_html($application->rejection_reason)); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($application->admin_notes)) : ?>
                <div class="semigapp-detail-row">
                    <label><?php esc_html_e('Admin Notes', 'semigapp'); ?></label>
                    <div class="semigapp-detail-content"><?php echo nl2br(esc_html($application->admin_notes)); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .semigapp-application-details .semigapp-detail-row {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .semigapp-application-details .semigapp-detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .semigapp-application-details label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        .semigapp-application-details .semigapp-detail-content {
            background: #f9fafb;
            padding: 0.75rem;
            border-radius: 4px;
            margin-top: 0.25rem;
        }
        .semigapp-application-details h4 {
            margin: 1.5rem 0 1rem;
            padding-top: 1rem;
            border-top: 2px solid #e5e7eb;
        }
        </style>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Handle saving application field
     */
    private function handle_save_application_field() {
        if (!current_user_can('manage_semigapp_events')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'semigapp')));
        }

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $field = isset($_POST['field']) ? $_POST['field'] : array();

        if (!$event_id || empty($field['field_label'])) {
            wp_send_json_error(array('message' => __('Invalid data.', 'semigapp')));
        }

        $plugin = \SemigApp\Plugin::get_instance();
        $events_module = $plugin->get_module('events');

        $result = $events_module->save_application_field($event_id, $field);

        if ($result !== false) {
            wp_send_json_success(array('field_id' => $result));
        } else {
            wp_send_json_error(array('message' => __('Failed to save field.', 'semigapp')));
        }
    }

    /**
     * Handle deleting application field
     */
    private function handle_delete_application_field() {
        if (!current_user_can('manage_semigapp_events')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'semigapp')));
        }

        $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;

        if (!$field_id) {
            wp_send_json_error(array('message' => __('Invalid field ID.', 'semigapp')));
        }

        $plugin = \SemigApp\Plugin::get_instance();
        $events_module = $plugin->get_module('events');

        $result = $events_module->delete_application_field($field_id);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Failed to delete field.', 'semigapp')));
        }
    }

    /**
     * Handle reordering application fields
     */
    private function handle_reorder_application_fields() {
        if (!current_user_can('manage_semigapp_events')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'semigapp')));
        }

        $order = isset($_POST['order']) ? $_POST['order'] : array();

        if (empty($order)) {
            wp_send_json_success();
            return;
        }

        $db = \SemigApp\Database::get_instance();

        foreach ($order as $item) {
            $db->update('event_application_fields', array(
                'sort_order' => intval($item['order']),
            ), array('id' => intval($item['id'])));
        }

        wp_send_json_success();
    }
}
