<?php
/**
 * Plugin Name: SemigApp - Business Management Suite
 * Plugin URI: https://semigapp.com
 * Description: Comprehensive business management plugin featuring Project Management, Event Calendar, Membership Management, Webshop with Swedish Payment Gateways, and Newsletter Management.
 * Version: 1.0.0
 * Author: SemigApp Team
 * Author URI: https://semigapp.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: semigapp
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin version
define('SEMIGAPP_VERSION', '1.0.0');

// Plugin paths
define('SEMIGAPP_PLUGIN_FILE', __FILE__);
define('SEMIGAPP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEMIGAPP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SEMIGAPP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum requirements
define('SEMIGAPP_MIN_PHP_VERSION', '7.4');
define('SEMIGAPP_MIN_WP_VERSION', '5.8');

/**
 * Check minimum requirements before loading plugin
 * Note: Don't use __() here as textdomain isn't loaded yet
 */
function semigapp_check_requirements() {
    $errors = array();

    if (version_compare(PHP_VERSION, SEMIGAPP_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
            'SemigApp requires PHP version %s or higher. You are running version %s.',
            SEMIGAPP_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }

    if (version_compare(get_bloginfo('version'), SEMIGAPP_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
            'SemigApp requires WordPress version %s or higher. You are running version %s.',
            SEMIGAPP_MIN_WP_VERSION,
            get_bloginfo('version')
        );
    }

    return $errors;
}

/**
 * Display admin notice for requirement errors
 */
function semigapp_requirements_notice() {
    $errors = semigapp_check_requirements();

    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
        }
    }
}

/**
 * Initialize the plugin
 */
function semigapp_init() {
    $errors = semigapp_check_requirements();

    if (!empty($errors)) {
        add_action('admin_notices', 'semigapp_requirements_notice');
        return;
    }

    // Load autoloader
    require_once SEMIGAPP_PLUGIN_DIR . 'includes/class-autoloader.php';

    // Initialize autoloader
    SemigApp\Autoloader::register();

    // Load the main plugin class
    $plugin = SemigApp\Plugin::get_instance();
    $plugin->init();
}

/**
 * Plugin activation hook
 */
function semigapp_activate() {
    $errors = semigapp_check_requirements();

    if (!empty($errors)) {
        wp_die(implode('<br>', $errors));
    }

    require_once SEMIGAPP_PLUGIN_DIR . 'includes/class-autoloader.php';
    SemigApp\Autoloader::register();

    require_once SEMIGAPP_PLUGIN_DIR . 'includes/class-activator.php';
    SemigApp\Activator::activate();
}

/**
 * Plugin deactivation hook
 */
function semigapp_deactivate() {
    require_once SEMIGAPP_PLUGIN_DIR . 'includes/class-autoloader.php';
    SemigApp\Autoloader::register();

    require_once SEMIGAPP_PLUGIN_DIR . 'includes/class-deactivator.php';
    SemigApp\Deactivator::deactivate();
}

/**
 * Plugin uninstall hook
 * Note: Uninstall logic is in uninstall.php
 */

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'semigapp_activate');
register_deactivation_hook(__FILE__, 'semigapp_deactivate');

// Initialize plugin after WordPress is loaded
add_action('plugins_loaded', 'semigapp_init');
