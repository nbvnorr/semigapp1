<?php
/**
 * Main Plugin Class
 *
 * @package SemigApp
 */

namespace SemigApp;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin
 *
 * Main plugin orchestrator class
 */
class Plugin {

    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Modules registry
     *
     * @var array
     */
    private $modules = array();

    /**
     * Get plugin instance
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Constructor is private
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        $this->load_textdomain();

        // Initialize core components
        $this->init_core();

        // Initialize modules
        $this->init_modules();

        // Initialize REST API
        $this->init_api();

        // Initialize admin
        if (is_admin()) {
            $this->init_admin();
        }

        // Initialize frontend
        if (!is_admin()) {
            $this->init_frontend();
        }

        // Hook into WordPress
        $this->register_hooks();
    }

    /**
     * Load plugin text domain
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            'semigapp',
            false,
            dirname(SEMIGAPP_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Initialize core components
     */
    private function init_core() {
        // Initialize database handler
        Database::get_instance();

        // Initialize settings
        Settings::get_instance();

        // Initialize assets handler
        Assets::get_instance();

        // Initialize email handler
        Emails::get_instance();
    }

    /**
     * Initialize modules
     */
    private function init_modules() {
        // Project Management Module
        $this->modules['projects'] = new Modules\Projects\Projects_Module();

        // Event Calendar Module
        $this->modules['events'] = new Modules\Events\Events_Module();

        // Membership Module
        $this->modules['membership'] = new Modules\Membership\Membership_Module();

        // Webshop Module
        $this->modules['webshop'] = new Modules\Webshop\Webshop_Module();

        // Newsletter Module
        $this->modules['newsletter'] = new Modules\Newsletter\Newsletter_Module();

        // Initialize all modules
        foreach ($this->modules as $module) {
            $module->init();
        }
    }

    /**
     * Initialize REST API
     */
    private function init_api() {
        $api = new Api\Rest_Controller();
        $api->init();
    }

    /**
     * Initialize admin area
     */
    private function init_admin() {
        $admin = new Admin\Admin();
        $admin->init();
    }

    /**
     * Initialize frontend
     */
    private function init_frontend() {
        $frontend = new Frontend\Frontend();
        $frontend->init();
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Add plugin action links
        add_filter('plugin_action_links_' . SEMIGAPP_PLUGIN_BASENAME, array($this, 'add_action_links'));

        // Add plugin row meta
        add_filter('plugin_row_meta', array($this, 'add_row_meta'), 10, 2);

        // Register custom post types
        add_action('init', array($this, 'register_post_types'));

        // Register taxonomies
        add_action('init', array($this, 'register_taxonomies'));

        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));

        // Register widgets
        add_action('widgets_init', array($this, 'register_widgets'));

        // Schedule cron events
        add_action('init', array($this, 'schedule_events'));
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links.
     * @return array Modified links.
     */
    public function add_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=semigapp-settings') . '">' . __('Settings', 'semigapp') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }

    /**
     * Add plugin row meta
     *
     * @param array  $links Existing links.
     * @param string $file  Plugin file.
     * @return array Modified links.
     */
    public function add_row_meta($links, $file) {
        if (SEMIGAPP_PLUGIN_BASENAME === $file) {
            $row_meta = array(
                'docs' => '<a href="https://docs.semigapp.com" target="_blank">' . __('Documentation', 'semigapp') . '</a>',
                'support' => '<a href="https://support.semigapp.com" target="_blank">' . __('Support', 'semigapp') . '</a>',
            );
            return array_merge($links, $row_meta);
        }
        return $links;
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Post types are registered by individual modules
        do_action('semigapp_register_post_types');
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Taxonomies are registered by individual modules
        do_action('semigapp_register_taxonomies');
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        // Shortcodes are registered by individual modules
        do_action('semigapp_register_shortcodes');
    }

    /**
     * Register widgets
     */
    public function register_widgets() {
        // Widgets are registered by individual modules
        do_action('semigapp_register_widgets');
    }

    /**
     * Schedule cron events
     */
    public function schedule_events() {
        // Newsletter send schedule
        if (!wp_next_scheduled('semigapp_newsletter_send')) {
            wp_schedule_event(time(), 'hourly', 'semigapp_newsletter_send');
        }

        // Membership expiry check
        if (!wp_next_scheduled('semigapp_check_membership_expiry')) {
            wp_schedule_event(time(), 'daily', 'semigapp_check_membership_expiry');
        }

        // Event reminders
        if (!wp_next_scheduled('semigapp_event_reminders')) {
            wp_schedule_event(time(), 'hourly', 'semigapp_event_reminders');
        }
    }

    /**
     * Get a module instance
     *
     * @param string $module Module name.
     * @return object|null Module instance or null.
     */
    public function get_module($module) {
        return isset($this->modules[$module]) ? $this->modules[$module] : null;
    }

    /**
     * Get all modules
     *
     * @return array All modules.
     */
    public function get_modules() {
        return $this->modules;
    }
}
