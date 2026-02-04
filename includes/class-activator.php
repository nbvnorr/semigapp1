<?php
/**
 * Plugin Activator
 *
 * @package SemigApp
 */

namespace SemigApp;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Activator
 *
 * Handles plugin activation tasks
 */
class Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        // Create database tables
        self::create_tables();

        // Create default options
        self::create_options();

        // Create required pages
        self::create_pages();

        // Set up user roles and capabilities
        self::setup_roles();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set activation flag
        update_option('semigapp_activated', true);
        update_option('semigapp_version', SEMIGAPP_VERSION);
        update_option('semigapp_activation_time', current_time('mysql'));
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Projects table
        $projects_table = $wpdb->prefix . 'semigapp_projects';
        $sql_projects = "CREATE TABLE $projects_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description longtext,
            status varchar(50) DEFAULT 'active',
            priority varchar(50) DEFAULT 'medium',
            start_date datetime DEFAULT NULL,
            due_date datetime DEFAULT NULL,
            completed_date datetime DEFAULT NULL,
            owner_id bigint(20) unsigned NOT NULL,
            parent_id bigint(20) unsigned DEFAULT 0,
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY owner_id (owner_id),
            KEY parent_id (parent_id),
            KEY status (status)
        ) $charset_collate;";

        // Tasks table
        $tasks_table = $wpdb->prefix . 'semigapp_tasks';
        $sql_tasks = "CREATE TABLE $tasks_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            title varchar(255) NOT NULL,
            description longtext,
            status varchar(50) DEFAULT 'pending',
            priority varchar(50) DEFAULT 'medium',
            due_date datetime DEFAULT NULL,
            completed_date datetime DEFAULT NULL,
            assigned_to bigint(20) unsigned DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL,
            position int(11) DEFAULT 0,
            estimated_hours decimal(10,2) DEFAULT NULL,
            actual_hours decimal(10,2) DEFAULT NULL,
            parent_id bigint(20) unsigned DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY assigned_to (assigned_to),
            KEY status (status),
            KEY parent_id (parent_id)
        ) $charset_collate;";

        // Task comments table
        $task_comments_table = $wpdb->prefix . 'semigapp_task_comments';
        $sql_task_comments = "CREATE TABLE $task_comments_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            task_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            content longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY task_id (task_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Events table
        $events_table = $wpdb->prefix . 'semigapp_events';
        $sql_events = "CREATE TABLE $events_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description longtext,
            location varchar(255),
            location_details longtext,
            event_type varchar(50) DEFAULT 'single',
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            all_day tinyint(1) DEFAULT 0,
            recurrence_rule varchar(255),
            recurrence_end datetime DEFAULT NULL,
            max_attendees int(11) DEFAULT 0,
            registration_required tinyint(1) DEFAULT 0,
            registration_deadline datetime DEFAULT NULL,
            price decimal(10,2) DEFAULT 0.00,
            organizer_id bigint(20) unsigned NOT NULL,
            status varchar(50) DEFAULT 'published',
            featured_image bigint(20) unsigned DEFAULT NULL,
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY organizer_id (organizer_id),
            KEY start_date (start_date),
            KEY status (status)
        ) $charset_collate;";

        // Event registrations table
        $event_registrations_table = $wpdb->prefix . 'semigapp_event_registrations';
        $sql_event_registrations = "CREATE TABLE $event_registrations_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            guest_name varchar(255),
            guest_email varchar(255),
            attendees int(11) DEFAULT 1,
            status varchar(50) DEFAULT 'confirmed',
            payment_status varchar(50) DEFAULT 'pending',
            payment_id varchar(255),
            notes longtext,
            check_in_time datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Event applications table (for events requiring approval)
        $event_applications_table = $wpdb->prefix . 'semigapp_event_applications';
        $sql_event_applications = "CREATE TABLE $event_applications_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            applicant_name varchar(255) NOT NULL,
            applicant_email varchar(255) NOT NULL,
            applicant_phone varchar(50),
            attendees int(11) DEFAULT 1,
            status varchar(50) DEFAULT 'pending',
            motivation longtext,
            custom_fields longtext,
            admin_notes longtext,
            reviewed_by bigint(20) unsigned DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            rejection_reason longtext,
            waitlist_position int(11) DEFAULT NULL,
            payment_status varchar(50) DEFAULT 'pending',
            payment_id varchar(255),
            registration_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY waitlist_position (waitlist_position)
        ) $charset_collate;";

        // Event application fields (custom fields for application forms)
        $event_application_fields_table = $wpdb->prefix . 'semigapp_event_application_fields';
        $sql_event_application_fields = "CREATE TABLE $event_application_fields_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            field_name varchar(255) NOT NULL,
            field_label varchar(255) NOT NULL,
            field_type varchar(50) DEFAULT 'text',
            field_options longtext,
            is_required tinyint(1) DEFAULT 0,
            placeholder varchar(255),
            help_text varchar(500),
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY sort_order (sort_order)
        ) $charset_collate;";

        // Members table
        $members_table = $wpdb->prefix . 'semigapp_members';
        $sql_members = "CREATE TABLE $members_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            membership_level_id bigint(20) unsigned NOT NULL,
            status varchar(50) DEFAULT 'active',
            start_date datetime NOT NULL,
            end_date datetime DEFAULT NULL,
            renewal_date datetime DEFAULT NULL,
            auto_renew tinyint(1) DEFAULT 0,
            payment_method varchar(50),
            notes longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_membership (user_id, membership_level_id),
            KEY membership_level_id (membership_level_id),
            KEY status (status),
            KEY end_date (end_date)
        ) $charset_collate;";

        // Membership levels table
        $membership_levels_table = $wpdb->prefix . 'semigapp_membership_levels';
        $sql_membership_levels = "CREATE TABLE $membership_levels_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description longtext,
            price decimal(10,2) DEFAULT 0.00,
            duration int(11) DEFAULT 365,
            duration_unit varchar(20) DEFAULT 'days',
            trial_period int(11) DEFAULT 0,
            trial_unit varchar(20) DEFAULT 'days',
            features longtext,
            permissions longtext,
            status varchar(50) DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY status (status)
        ) $charset_collate;";

        // Membership payments table
        $membership_payments_table = $wpdb->prefix . 'semigapp_membership_payments';
        $sql_membership_payments = "CREATE TABLE $membership_payments_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            member_id bigint(20) unsigned NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) DEFAULT 'SEK',
            payment_method varchar(50),
            transaction_id varchar(255),
            status varchar(50) DEFAULT 'pending',
            payment_date datetime DEFAULT NULL,
            notes longtext,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY member_id (member_id),
            KEY status (status),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";

        // Products table
        $products_table = $wpdb->prefix . 'semigapp_products';
        $sql_products = "CREATE TABLE $products_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description longtext,
            short_description text,
            sku varchar(100),
            price decimal(10,2) NOT NULL,
            sale_price decimal(10,2) DEFAULT NULL,
            sale_start datetime DEFAULT NULL,
            sale_end datetime DEFAULT NULL,
            stock_quantity int(11) DEFAULT 0,
            stock_status varchar(50) DEFAULT 'instock',
            manage_stock tinyint(1) DEFAULT 0,
            weight decimal(10,2) DEFAULT NULL,
            dimensions varchar(100),
            tax_class varchar(50) DEFAULT 'standard',
            tax_rate decimal(5,2) DEFAULT 25.00,
            featured_image bigint(20) unsigned DEFAULT NULL,
            gallery longtext,
            categories longtext,
            tags longtext,
            attributes longtext,
            downloadable tinyint(1) DEFAULT 0,
            download_files longtext,
            virtual tinyint(1) DEFAULT 0,
            status varchar(50) DEFAULT 'published',
            featured tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY sku (sku),
            KEY status (status)
        ) $charset_collate;";

        // Orders table
        $orders_table = $wpdb->prefix . 'semigapp_orders';
        $sql_orders = "CREATE TABLE $orders_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            subtotal decimal(10,2) NOT NULL,
            tax_total decimal(10,2) DEFAULT 0.00,
            shipping_total decimal(10,2) DEFAULT 0.00,
            discount_total decimal(10,2) DEFAULT 0.00,
            total decimal(10,2) NOT NULL,
            currency varchar(10) DEFAULT 'SEK',
            payment_method varchar(50),
            payment_status varchar(50) DEFAULT 'pending',
            transaction_id varchar(255),
            billing_address longtext,
            shipping_address longtext,
            customer_note longtext,
            admin_note longtext,
            ip_address varchar(45),
            user_agent varchar(255),
            coupon_code varchar(100),
            completed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY order_number (order_number),
            KEY user_id (user_id),
            KEY status (status),
            KEY payment_status (payment_status)
        ) $charset_collate;";

        // Order items table
        $order_items_table = $wpdb->prefix . 'semigapp_order_items';
        $sql_order_items = "CREATE TABLE $order_items_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            product_name varchar(255) NOT NULL,
            quantity int(11) NOT NULL,
            unit_price decimal(10,2) NOT NULL,
            tax decimal(10,2) DEFAULT 0.00,
            total decimal(10,2) NOT NULL,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY product_id (product_id)
        ) $charset_collate;";

        // Newsletter subscribers table
        $subscribers_table = $wpdb->prefix . 'semigapp_subscribers';
        $sql_subscribers = "CREATE TABLE $subscribers_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            first_name varchar(100),
            last_name varchar(100),
            user_id bigint(20) unsigned DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            source varchar(100),
            ip_address varchar(45),
            confirmation_token varchar(255),
            confirmed_at datetime DEFAULT NULL,
            unsubscribed_at datetime DEFAULT NULL,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Subscriber lists table
        $subscriber_lists_table = $wpdb->prefix . 'semigapp_subscriber_lists';
        $sql_subscriber_lists = "CREATE TABLE $subscriber_lists_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description longtext,
            status varchar(50) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Subscriber list relations
        $subscriber_list_relations_table = $wpdb->prefix . 'semigapp_subscriber_list_relations';
        $sql_subscriber_list_relations = "CREATE TABLE $subscriber_list_relations_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscriber_id bigint(20) unsigned NOT NULL,
            list_id bigint(20) unsigned NOT NULL,
            subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY subscriber_list (subscriber_id, list_id),
            KEY list_id (list_id)
        ) $charset_collate;";

        // Newsletter campaigns table
        $campaigns_table = $wpdb->prefix . 'semigapp_campaigns';
        $sql_campaigns = "CREATE TABLE $campaigns_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            content longtext NOT NULL,
            template varchar(100) DEFAULT 'default',
            status varchar(50) DEFAULT 'draft',
            list_ids longtext,
            sent_count int(11) DEFAULT 0,
            open_count int(11) DEFAULT 0,
            click_count int(11) DEFAULT 0,
            scheduled_at datetime DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_by (created_by)
        ) $charset_collate;";

        // Campaign tracking table
        $campaign_tracking_table = $wpdb->prefix . 'semigapp_campaign_tracking';
        $sql_campaign_tracking = "CREATE TABLE $campaign_tracking_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            subscriber_id bigint(20) unsigned NOT NULL,
            event_type varchar(50) NOT NULL,
            link_url varchar(500),
            ip_address varchar(45),
            user_agent varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY subscriber_id (subscriber_id),
            KEY event_type (event_type)
        ) $charset_collate;";

        // Activity log table
        $activity_log_table = $wpdb->prefix . 'semigapp_activity_log';
        $sql_activity_log = "CREATE TABLE $activity_log_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) unsigned NOT NULL,
            action varchar(50) NOT NULL,
            description text,
            old_value longtext,
            new_value longtext,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY object_type (object_type),
            KEY object_id (object_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Execute all SQL
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql_projects);
        dbDelta($sql_tasks);
        dbDelta($sql_task_comments);
        dbDelta($sql_events);
        dbDelta($sql_event_registrations);
        dbDelta($sql_event_applications);
        dbDelta($sql_event_application_fields);
        dbDelta($sql_members);
        dbDelta($sql_membership_levels);
        dbDelta($sql_membership_payments);
        dbDelta($sql_products);
        dbDelta($sql_orders);
        dbDelta($sql_order_items);
        dbDelta($sql_subscribers);
        dbDelta($sql_subscriber_lists);
        dbDelta($sql_subscriber_list_relations);
        dbDelta($sql_campaigns);
        dbDelta($sql_campaign_tracking);
        dbDelta($sql_activity_log);

        // Store db version
        update_option('semigapp_db_version', SEMIGAPP_VERSION);
    }

    /**
     * Create default options
     */
    private static function create_options() {
        $default_settings = array(
            // General settings
            'general' => array(
                'currency' => 'SEK',
                'currency_symbol' => 'kr',
                'currency_position' => 'after',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'timezone' => 'Europe/Stockholm',
            ),
            // Projects settings
            'projects' => array(
                'enable_time_tracking' => true,
                'default_task_status' => 'pending',
                'task_statuses' => array('pending', 'in_progress', 'review', 'completed', 'cancelled'),
                'priorities' => array('low', 'medium', 'high', 'urgent'),
            ),
            // Events settings
            'events' => array(
                'enable_registration' => true,
                'enable_payments' => true,
                'enable_applications' => true,
                'default_reminder_hours' => 24,
                'google_maps_api_key' => '',
                'application_notification_email' => get_option('admin_email'),
                'auto_approve_applications' => false,
                'waitlist_enabled' => true,
                'application_confirmation_text' => 'Thank you for your application. We will review it and get back to you soon.',
                'application_approved_text' => 'Your application has been approved! We look forward to seeing you at the event.',
                'application_rejected_text' => 'Unfortunately, your application was not approved for this event.',
            ),
            // Membership settings
            'membership' => array(
                'enable_trial' => false,
                'grace_period_days' => 7,
                'expiry_reminder_days' => array(7, 3, 1),
                'restrict_content' => true,
            ),
            // Webshop settings
            'webshop' => array(
                'enable_tax' => true,
                'tax_rate' => 25.00,
                'tax_display' => 'incl',
                'enable_shipping' => true,
                'free_shipping_threshold' => 500,
                'low_stock_threshold' => 5,
                'order_number_prefix' => 'ORD-',
            ),
            // Payment settings
            'payments' => array(
                'klarna_enabled' => false,
                'klarna_test_mode' => true,
                'klarna_api_username' => '',
                'klarna_api_password' => '',
                'swish_enabled' => false,
                'swish_test_mode' => true,
                'swish_merchant_number' => '',
                'swish_certificate_path' => '',
                'stripe_enabled' => false,
                'stripe_test_mode' => true,
                'stripe_publishable_key' => '',
                'stripe_secret_key' => '',
            ),
            // Newsletter settings
            'newsletter' => array(
                'sender_name' => get_bloginfo('name'),
                'sender_email' => get_option('admin_email'),
                'double_optin' => true,
                'unsubscribe_page' => 0,
                'gdpr_consent_text' => '',
            ),
            // Email settings
            'emails' => array(
                'header_logo' => '',
                'header_color' => '#2563eb',
                'footer_text' => '',
            ),
        );

        foreach ($default_settings as $group => $settings) {
            $option_name = 'semigapp_' . $group . '_settings';
            if (!get_option($option_name)) {
                add_option($option_name, $settings);
            }
        }
    }

    /**
     * Create required pages
     * Note: Using plain strings here to avoid early textdomain loading
     */
    private static function create_pages() {
        $pages = array(
            'account' => array(
                'title' => 'My Account',
                'content' => '[semigapp_account]',
            ),
            'checkout' => array(
                'title' => 'Checkout',
                'content' => '[semigapp_checkout]',
            ),
            'cart' => array(
                'title' => 'Cart',
                'content' => '[semigapp_cart]',
            ),
            'shop' => array(
                'title' => 'Shop',
                'content' => '[semigapp_shop]',
            ),
            'events' => array(
                'title' => 'Events',
                'content' => '[semigapp_events]',
            ),
            'membership' => array(
                'title' => 'Membership',
                'content' => '[semigapp_membership_levels]',
            ),
            'newsletter_subscribe' => array(
                'title' => 'Subscribe to Newsletter',
                'content' => '[semigapp_newsletter_form]',
            ),
            'newsletter_unsubscribe' => array(
                'title' => 'Unsubscribe',
                'content' => '[semigapp_unsubscribe]',
            ),
        );

        $page_ids = array();

        foreach ($pages as $key => $page) {
            $page_id = wp_insert_post(array(
                'post_title' => $page['title'],
                'post_content' => $page['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => get_current_user_id() ?: 1,
            ));

            if (!is_wp_error($page_id)) {
                $page_ids[$key] = $page_id;
            }
        }

        update_option('semigapp_pages', $page_ids);
    }

    /**
     * Set up user roles and capabilities
     */
    private static function setup_roles() {
        // Add custom capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $capabilities = array(
                // Project capabilities
                'manage_semigapp_projects',
                'edit_semigapp_projects',
                'delete_semigapp_projects',
                // Event capabilities
                'manage_semigapp_events',
                'edit_semigapp_events',
                'delete_semigapp_events',
                // Membership capabilities
                'manage_semigapp_members',
                'edit_semigapp_members',
                'delete_semigapp_members',
                // Webshop capabilities
                'manage_semigapp_shop',
                'edit_semigapp_products',
                'delete_semigapp_products',
                'manage_semigapp_orders',
                // Newsletter capabilities
                'manage_semigapp_newsletter',
                'send_semigapp_campaigns',
                // Settings
                'manage_semigapp_settings',
            );

            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }

        // Create SemigApp Manager role
        // Note: Using plain strings to avoid early textdomain loading
        add_role('semigapp_manager', 'SemigApp Manager', array(
            'read' => true,
            'manage_semigapp_projects' => true,
            'edit_semigapp_projects' => true,
            'manage_semigapp_events' => true,
            'edit_semigapp_events' => true,
            'manage_semigapp_members' => true,
            'edit_semigapp_members' => true,
            'manage_semigapp_shop' => true,
            'edit_semigapp_products' => true,
            'manage_semigapp_orders' => true,
            'manage_semigapp_newsletter' => true,
            'send_semigapp_campaigns' => true,
        ));

        // Create Member role
        add_role('semigapp_member', 'SemigApp Member', array(
            'read' => true,
        ));
    }
}
