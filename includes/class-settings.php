<?php
/**
 * Settings Handler
 *
 * @package SemigApp
 */

namespace SemigApp;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Settings
 *
 * Handles plugin settings
 */
class Settings {

    /**
     * Instance
     *
     * @var Settings
     */
    private static $instance = null;

    /**
     * Settings cache
     *
     * @var array
     */
    private $settings = array();

    /**
     * Settings groups
     *
     * @var array
     */
    private $groups = array(
        'general',
        'projects',
        'events',
        'membership',
        'webshop',
        'payments',
        'newsletter',
        'emails',
    );

    /**
     * Get instance
     *
     * @return Settings
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_settings();
    }

    /**
     * Load all settings
     */
    private function load_settings() {
        foreach ($this->groups as $group) {
            $this->settings[$group] = get_option('semigapp_' . $group . '_settings', array());
        }
    }

    /**
     * Get a setting value
     *
     * @param string $key     Setting key in format "group.key" or "group.nested.key".
     * @param mixed  $default Default value.
     * @return mixed Setting value.
     */
    public function get($key, $default = null) {
        $parts = explode('.', $key);
        $group = array_shift($parts);

        if (!isset($this->settings[$group])) {
            return $default;
        }

        $value = $this->settings[$group];

        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Set a setting value
     *
     * @param string $key   Setting key in format "group.key".
     * @param mixed  $value Setting value.
     * @return bool True on success, false on failure.
     */
    public function set($key, $value) {
        $parts = explode('.', $key);
        $group = array_shift($parts);

        if (!in_array($group, $this->groups)) {
            return false;
        }

        if (!isset($this->settings[$group])) {
            $this->settings[$group] = array();
        }

        $current = &$this->settings[$group];

        $last_key = array_pop($parts);

        foreach ($parts as $part) {
            if (!isset($current[$part]) || !is_array($current[$part])) {
                $current[$part] = array();
            }
            $current = &$current[$part];
        }

        $current[$last_key] = $value;

        return update_option('semigapp_' . $group . '_settings', $this->settings[$group]);
    }

    /**
     * Get all settings for a group
     *
     * @param string $group Group name.
     * @return array Settings.
     */
    public function get_group($group) {
        return isset($this->settings[$group]) ? $this->settings[$group] : array();
    }

    /**
     * Update all settings for a group
     *
     * @param string $group    Group name.
     * @param array  $settings Settings to update.
     * @return bool True on success, false on failure.
     */
    public function update_group($group, $settings) {
        if (!in_array($group, $this->groups)) {
            return false;
        }

        $this->settings[$group] = wp_parse_args($settings, $this->settings[$group]);

        return update_option('semigapp_' . $group . '_settings', $this->settings[$group]);
    }

    /**
     * Delete a setting
     *
     * @param string $key Setting key.
     * @return bool True on success, false on failure.
     */
    public function delete($key) {
        $parts = explode('.', $key);
        $group = array_shift($parts);

        if (!isset($this->settings[$group])) {
            return false;
        }

        $current = &$this->settings[$group];

        $last_key = array_pop($parts);

        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return false;
            }
            $current = &$current[$part];
        }

        unset($current[$last_key]);

        return update_option('semigapp_' . $group . '_settings', $this->settings[$group]);
    }

    /**
     * Get currency settings
     *
     * @return array Currency settings.
     */
    public function get_currency() {
        return array(
            'code' => $this->get('general.currency', 'SEK'),
            'symbol' => $this->get('general.currency_symbol', 'kr'),
            'position' => $this->get('general.currency_position', 'after'),
        );
    }

    /**
     * Format price with currency
     *
     * @param float  $price     Price value.
     * @param string $currency  Currency code (optional).
     * @return string Formatted price.
     */
    public function format_price($price, $currency = null) {
        $currency_settings = $this->get_currency();

        if ($currency) {
            $currency_settings['code'] = $currency;
        }

        $formatted = number_format($price, 2, ',', ' ');

        if ($currency_settings['position'] === 'before') {
            return $currency_settings['symbol'] . $formatted;
        }

        return $formatted . ' ' . $currency_settings['symbol'];
    }

    /**
     * Get available payment gateways
     *
     * @return array Enabled payment gateways.
     */
    public function get_enabled_gateways() {
        $gateways = array();

        if ($this->get('payments.klarna_enabled')) {
            $gateways['klarna'] = array(
                'id' => 'klarna',
                'name' => __('Klarna', 'semigapp'),
                'description' => __('Pay with Klarna', 'semigapp'),
                'test_mode' => $this->get('payments.klarna_test_mode'),
            );
        }

        if ($this->get('payments.swish_enabled')) {
            $gateways['swish'] = array(
                'id' => 'swish',
                'name' => __('Swish', 'semigapp'),
                'description' => __('Pay with Swish', 'semigapp'),
                'test_mode' => $this->get('payments.swish_test_mode'),
            );
        }

        if ($this->get('payments.stripe_enabled')) {
            $gateways['stripe'] = array(
                'id' => 'stripe',
                'name' => __('Stripe', 'semigapp'),
                'description' => __('Pay with card', 'semigapp'),
                'test_mode' => $this->get('payments.stripe_test_mode'),
            );
        }

        return apply_filters('semigapp_enabled_gateways', $gateways);
    }

    /**
     * Get all settings groups
     *
     * @return array Settings groups.
     */
    public function get_all_groups() {
        return $this->groups;
    }

    /**
     * Get plugin pages
     *
     * @return array Page IDs.
     */
    public function get_pages() {
        return get_option('semigapp_pages', array());
    }

    /**
     * Get a specific page ID
     *
     * @param string $page Page key.
     * @return int Page ID or 0.
     */
    public function get_page_id($page) {
        $pages = $this->get_pages();
        return isset($pages[$page]) ? (int) $pages[$page] : 0;
    }

    /**
     * Get page URL
     *
     * @param string $page Page key.
     * @return string Page URL.
     */
    public function get_page_url($page) {
        $page_id = $this->get_page_id($page);
        return $page_id ? get_permalink($page_id) : '';
    }
}
