<?php
/**
 * Base Module Class
 *
 * @package SemigApp
 */

namespace SemigApp\Modules;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Base_Module
 *
 * Abstract base class for all modules
 */
abstract class Base_Module {

    /**
     * Module ID
     *
     * @var string
     */
    protected $id = '';

    /**
     * Module name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Module description
     *
     * @var string
     */
    protected $description = '';

    /**
     * Database instance
     *
     * @var \SemigApp\Database
     */
    protected $db;

    /**
     * Settings instance
     *
     * @var \SemigApp\Settings
     */
    protected $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = \SemigApp\Database::get_instance();
        $this->settings = \SemigApp\Settings::get_instance();
    }

    /**
     * Initialize the module
     */
    abstract public function init();

    /**
     * Get module ID
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get module name
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get module description
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Get module setting
     *
     * @param string $key     Setting key.
     * @param mixed  $default Default value.
     * @return mixed Setting value.
     */
    protected function get_setting($key, $default = null) {
        return $this->settings->get($this->id . '.' . $key, $default);
    }

    /**
     * Log activity
     *
     * @param string $object_type Object type.
     * @param int    $object_id   Object ID.
     * @param string $action      Action performed.
     * @param string $description Description.
     * @param mixed  $old_value   Old value.
     * @param mixed  $new_value   New value.
     */
    protected function log_activity($object_type, $object_id, $action, $description = '', $old_value = null, $new_value = null) {
        $this->db->insert('activity_log', array(
            'user_id' => get_current_user_id(),
            'object_type' => $object_type,
            'object_id' => $object_id,
            'action' => $action,
            'description' => $description,
            'old_value' => is_array($old_value) || is_object($old_value) ? wp_json_encode($old_value) : $old_value,
            'new_value' => is_array($new_value) || is_object($new_value) ? wp_json_encode($new_value) : $new_value,
            'ip_address' => $this->get_client_ip(),
        ));
    }

    /**
     * Get client IP address
     *
     * Validates IP addresses and handles proxy headers securely.
     * Only trusts X-Forwarded-For if site is configured behind a trusted proxy.
     *
     * @return string IP address.
     */
    protected function get_client_ip() {
        $ip = '';

        // Check trusted proxy headers only if configured
        $trusted_proxies = apply_filters('semigapp_trusted_proxy_ips', array());
        $is_behind_trusted_proxy = !empty($trusted_proxies) &&
            isset($_SERVER['REMOTE_ADDR']) &&
            in_array($_SERVER['REMOTE_ADDR'], $trusted_proxies, true);

        if ($is_behind_trusted_proxy) {
            // Only trust forwarded headers from known proxies
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                // X-Forwarded-For can contain multiple IPs, get the first (client) IP
                $forwarded_ips = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
                $ip = trim($forwarded_ips[0]);
            } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_IP']));
            }
        }

        // Fall back to REMOTE_ADDR (most secure, cannot be spoofed)
        if (empty($ip) && !empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        // Validate the IP address format
        $ip = $this->validate_ip_address($ip);

        return $ip;
    }

    /**
     * Validate IP address format
     *
     * Ensures the IP address is valid IPv4 or IPv6.
     *
     * @param string $ip IP address to validate.
     * @return string Valid IP or empty string if invalid.
     */
    private function validate_ip_address($ip) {
        if (empty($ip)) {
            return '';
        }

        // Strip port number if present (e.g., "192.168.1.1:8080")
        if (strpos($ip, ':') !== false && substr_count($ip, ':') === 1) {
            $ip = explode(':', $ip)[0];
        }

        // Validate IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }

        // Validate IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip;
        }

        // Invalid IP address
        return '';
    }

    /**
     * Sanitize data array
     *
     * @param array $data   Data to sanitize.
     * @param array $schema Schema with field types.
     * @return array Sanitized data.
     */
    protected function sanitize_data($data, $schema) {
        $sanitized = array();

        foreach ($schema as $field => $type) {
            if (!isset($data[$field])) {
                continue;
            }

            switch ($type) {
                case 'int':
                    $sanitized[$field] = absint($data[$field]);
                    break;
                case 'float':
                    $sanitized[$field] = floatval($data[$field]);
                    break;
                case 'email':
                    $sanitized[$field] = sanitize_email($data[$field]);
                    break;
                case 'url':
                    $sanitized[$field] = esc_url_raw($data[$field]);
                    break;
                case 'html':
                    $sanitized[$field] = wp_kses_post($data[$field]);
                    break;
                case 'textarea':
                    $sanitized[$field] = sanitize_textarea_field($data[$field]);
                    break;
                case 'array':
                    $sanitized[$field] = is_array($data[$field]) ? $data[$field] : array();
                    break;
                case 'json':
                    $sanitized[$field] = wp_json_encode($data[$field]);
                    break;
                case 'datetime':
                    $sanitized[$field] = sanitize_text_field($data[$field]);
                    break;
                case 'bool':
                    $sanitized[$field] = (bool) $data[$field];
                    break;
                default:
                    $sanitized[$field] = sanitize_text_field($data[$field]);
            }
        }

        return $sanitized;
    }

    /**
     * Validate required fields
     *
     * @param array $data     Data to validate.
     * @param array $required Required field names.
     * @return array Array of missing fields.
     */
    protected function validate_required($data, $required) {
        $missing = array();

        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Send notification
     *
     * @param string $template Email template.
     * @param string $to       Recipient.
     * @param array  $data     Email data.
     * @return bool True on success.
     */
    protected function send_notification($template, $to, $data = array()) {
        return \SemigApp\Emails::get_instance()->send($to, $template, $data);
    }

    /**
     * Format date
     *
     * @param string $date   Date string.
     * @param string $format Format (optional).
     * @return string Formatted date.
     */
    protected function format_date($date, $format = null) {
        if (!$format) {
            $format = get_option('date_format') . ' ' . get_option('time_format');
        }

        return date_i18n($format, strtotime($date));
    }

    /**
     * Check user capability
     *
     * @param string $capability Capability to check.
     * @return bool True if user has capability.
     */
    protected function can($capability) {
        return current_user_can('manage_semigapp_' . $this->id) || current_user_can($capability);
    }
}
