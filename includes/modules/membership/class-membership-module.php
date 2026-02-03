<?php
/**
 * Membership Module
 *
 * @package SemigApp
 */

namespace SemigApp\Modules\Membership;

use SemigApp\Modules\Base_Module;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Membership_Module
 *
 * Handles membership management and payments
 */
class Membership_Module extends Base_Module {

    /**
     * Module ID
     *
     * @var string
     */
    protected $id = 'membership';

    /**
     * Module name
     *
     * @var string
     */
    protected $name = 'Membership Management';

    /**
     * Member statuses
     *
     * @var array
     */
    private $statuses = array();

    /**
     * Initialize the module
     */
    public function init() {
        $this->setup_statuses();

        // Register hooks
        add_action('semigapp_register_shortcodes', array($this, 'register_shortcodes'));
        add_action('wp_ajax_semigapp_membership_action', array($this, 'handle_ajax'));
        add_action('wp_ajax_nopriv_semigapp_membership_signup', array($this, 'handle_signup'));
        add_action('wp_ajax_semigapp_membership_signup', array($this, 'handle_signup'));

        // Scheduled events
        add_action('semigapp_check_membership_expiry', array($this, 'check_expiry'));

        // Content restriction
        add_filter('the_content', array($this, 'restrict_content'));

        // Email notifications
        add_action('semigapp_member_created', array($this, 'notify_welcome'), 10, 2);
        add_action('semigapp_membership_expiring', array($this, 'notify_expiring'), 10, 2);
    }

    /**
     * Setup member statuses
     */
    private function setup_statuses() {
        $this->statuses = array(
            'active' => __('Active', 'semigapp'),
            'pending' => __('Pending', 'semigapp'),
            'expired' => __('Expired', 'semigapp'),
            'cancelled' => __('Cancelled', 'semigapp'),
            'suspended' => __('Suspended', 'semigapp'),
        );
    }

    /**
     * Get member statuses
     *
     * @return array
     */
    public function get_statuses() {
        return apply_filters('semigapp_member_statuses', $this->statuses);
    }

    /**
     * Create membership level
     *
     * @param array $data Level data.
     * @return int|false Level ID or false.
     */
    public function create_level($data) {
        $schema = array(
            'name' => 'text',
            'slug' => 'text',
            'description' => 'html',
            'price' => 'float',
            'duration' => 'int',
            'duration_unit' => 'text',
            'trial_period' => 'int',
            'trial_unit' => 'text',
            'features' => 'json',
            'permissions' => 'json',
            'status' => 'text',
            'sort_order' => 'int',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        // Validate required fields
        $missing = $this->validate_required($sanitized, array('name'));
        if (!empty($missing)) {
            return false;
        }

        // Generate slug if not provided
        if (empty($sanitized['slug'])) {
            $sanitized['slug'] = sanitize_title($sanitized['name']);
        }

        // Set defaults
        $sanitized['status'] = $sanitized['status'] ?? 'active';
        $sanitized['duration'] = $sanitized['duration'] ?? 365;
        $sanitized['duration_unit'] = $sanitized['duration_unit'] ?? 'days';

        $level_id = $this->db->insert('membership_levels', $sanitized);

        if ($level_id) {
            $this->clear_levels_cache();
            $this->log_activity('membership_level', $level_id, 'created', 'Membership level created');
            do_action('semigapp_membership_level_created', $level_id, $sanitized);
        }

        return $level_id;
    }

    /**
     * Update membership level
     *
     * @param int   $level_id Level ID.
     * @param array $data     Level data.
     * @return bool True on success.
     */
    public function update_level($level_id, $data) {
        $old_level = $this->get_level($level_id);

        if (!$old_level) {
            return false;
        }

        $schema = array(
            'name' => 'text',
            'slug' => 'text',
            'description' => 'html',
            'price' => 'float',
            'duration' => 'int',
            'duration_unit' => 'text',
            'trial_period' => 'int',
            'trial_unit' => 'text',
            'features' => 'json',
            'permissions' => 'json',
            'status' => 'text',
            'sort_order' => 'int',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        $result = $this->db->update('membership_levels', $sanitized, array('id' => $level_id));

        if ($result !== false) {
            $this->clear_levels_cache();
            $this->log_activity('membership_level', $level_id, 'updated', 'Membership level updated');
            do_action('semigapp_membership_level_updated', $level_id, $sanitized, $old_level);
        }

        return $result !== false;
    }

    /**
     * Delete membership level
     *
     * @param int $level_id Level ID.
     * @return bool True on success.
     */
    public function delete_level($level_id) {
        // Check if any members use this level
        $member_count = $this->db->count('members', array('membership_level_id' => $level_id));
        if ($member_count > 0) {
            return false;
        }

        $result = $this->db->delete('membership_levels', array('id' => $level_id));

        if ($result) {
            $this->clear_levels_cache();
            $this->log_activity('membership_level', $level_id, 'deleted', 'Membership level deleted');
            do_action('semigapp_membership_level_deleted', $level_id);
        }

        return (bool) $result;
    }

    /**
     * Get membership level
     *
     * @param int $level_id Level ID.
     * @return object|null Level or null.
     */
    public function get_level($level_id) {
        $level = $this->db->get_row('membership_levels', array('id' => $level_id));

        if ($level) {
            $level->features = json_decode($level->features, true) ?: array();
            $level->permissions = json_decode($level->permissions, true) ?: array();
            $level->member_count = $this->db->count('members', array('membership_level_id' => $level_id));
        }

        return $level;
    }

    /**
     * Get membership level by slug
     *
     * @param string $slug Level slug.
     * @return object|null Level or null.
     */
    public function get_level_by_slug($slug) {
        $level = $this->db->get_row('membership_levels', array('slug' => $slug));

        if ($level) {
            $level->features = json_decode($level->features, true) ?: array();
            $level->permissions = json_decode($level->permissions, true) ?: array();
        }

        return $level;
    }

    /**
     * Get all membership levels
     *
     * @param array $args Query arguments.
     * @return array Levels.
     */
    public function get_levels($args = array()) {
        $defaults = array(
            'where' => array('status' => 'active'),
            'orderby' => 'sort_order',
            'order' => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);

        // Generate cache key based on args
        $cache_key = 'semigapp_levels_' . md5(serialize($args));

        // Try to get from cache first
        $levels = get_transient($cache_key);

        if ($levels === false) {
            $levels = $this->db->get_results('membership_levels', $args);

            if (!empty($levels)) {
                // Get all member counts in one query to avoid N+1
                $level_ids = wp_list_pluck($levels, 'id');
                $member_counts = $this->get_member_counts_by_level($level_ids);

                foreach ($levels as &$level) {
                    $level->features = json_decode($level->features, true) ?: array();
                    $level->permissions = json_decode($level->permissions, true) ?: array();
                    $level->member_count = $member_counts[$level->id] ?? 0;
                }
            }

            // Cache for 1 hour
            set_transient($cache_key, $levels, HOUR_IN_SECONDS);
        }

        return $levels;
    }

    /**
     * Get member counts grouped by level (fixes N+1 query)
     *
     * @param array $level_ids Array of level IDs.
     * @return array Associative array of level_id => count.
     */
    private function get_member_counts_by_level($level_ids) {
        if (empty($level_ids)) {
            return array();
        }

        global $wpdb;
        $table = $this->db->get_table('members');
        $placeholders = implode(',', array_fill(0, count($level_ids), '%d'));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT membership_level_id, COUNT(*) as count
             FROM $table
             WHERE membership_level_id IN ($placeholders)
             GROUP BY membership_level_id",
            $level_ids
        ));

        $counts = array();
        foreach ($results as $row) {
            $counts[$row->membership_level_id] = (int) $row->count;
        }

        return $counts;
    }

    /**
     * Clear membership levels cache
     *
     * Called when levels are created/updated/deleted.
     */
    public function clear_levels_cache() {
        global $wpdb;

        // Delete all level transients using prepared statements
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE %s
             OR option_name LIKE %s",
            $wpdb->esc_like('_transient_semigapp_levels_') . '%',
            $wpdb->esc_like('_transient_timeout_semigapp_levels_') . '%'
        ));
    }

    /**
     * Create member
     *
     * @param int   $user_id  User ID.
     * @param int   $level_id Membership level ID.
     * @param array $data     Additional data.
     * @return int|false Member ID or false.
     */
    public function create_member($user_id, $level_id, $data = array()) {
        $level = $this->get_level($level_id);
        $user = get_userdata($user_id);

        if (!$level || !$user) {
            return false;
        }

        // Check if already a member
        $existing = $this->get_member_by_user($user_id, $level_id);
        if ($existing && $existing->status === 'active') {
            return false;
        }

        // Calculate dates
        $start_date = current_time('mysql');
        $end_date = $this->calculate_end_date($level, $start_date);

        $member_data = array(
            'user_id' => $user_id,
            'membership_level_id' => $level_id,
            'status' => 'active',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'renewal_date' => $end_date,
            'auto_renew' => isset($data['auto_renew']) ? (bool) $data['auto_renew'] : false,
            'payment_method' => isset($data['payment_method']) ? sanitize_text_field($data['payment_method']) : '',
            'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '',
        );

        // Handle trial
        if ($level->trial_period > 0 && !$this->has_used_trial($user_id, $level_id)) {
            $trial_end = $this->calculate_trial_end($level, $start_date);
            $member_data['end_date'] = $trial_end;
            $member_data['status'] = 'trial';
        }

        $member_id = $this->db->insert('members', $member_data);

        if ($member_id) {
            // Add user role
            $user->add_role('semigapp_member');

            $this->log_activity('member', $member_id, 'created', 'Member created');
            do_action('semigapp_member_created', $member_id, $level_id);
        }

        return $member_id;
    }

    /**
     * Calculate end date
     *
     * @param object $level      Level object.
     * @param string $start_date Start date.
     * @return string End date.
     */
    private function calculate_end_date($level, $start_date) {
        $duration = $level->duration;
        $unit = $level->duration_unit;

        switch ($unit) {
            case 'days':
                return date('Y-m-d H:i:s', strtotime($start_date . " +{$duration} days"));
            case 'weeks':
                return date('Y-m-d H:i:s', strtotime($start_date . " +{$duration} weeks"));
            case 'months':
                return date('Y-m-d H:i:s', strtotime($start_date . " +{$duration} months"));
            case 'years':
                return date('Y-m-d H:i:s', strtotime($start_date . " +{$duration} years"));
            default:
                return date('Y-m-d H:i:s', strtotime($start_date . " +{$duration} days"));
        }
    }

    /**
     * Calculate trial end date
     *
     * @param object $level      Level object.
     * @param string $start_date Start date.
     * @return string Trial end date.
     */
    private function calculate_trial_end($level, $start_date) {
        $duration = $level->trial_period;
        $unit = $level->trial_unit ?: 'days';

        switch ($unit) {
            case 'days':
                return date('Y-m-d H:i:s', strtotime($start_date . " +{$duration} days"));
            case 'weeks':
                return date('Y-m-d H:i:s', strtotime($start_date . " +{$duration} weeks"));
            case 'months':
                return date('Y-m-d H:i:s', strtotime($start_date . " +{$duration} months"));
            default:
                return date('Y-m-d H:i:s', strtotime($start_date . " +{$duration} days"));
        }
    }

    /**
     * Check if user has used trial
     *
     * @param int $user_id  User ID.
     * @param int $level_id Level ID.
     * @return bool True if trial was used.
     */
    private function has_used_trial($user_id, $level_id) {
        global $wpdb;
        $table = $this->db->get_table('members');

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND membership_level_id = %d",
            $user_id,
            $level_id
        ));

        return $count > 0;
    }

    /**
     * Update member
     *
     * @param int   $member_id Member ID.
     * @param array $data      Member data.
     * @return bool True on success.
     */
    public function update_member($member_id, $data) {
        $old_member = $this->get_member($member_id);

        if (!$old_member) {
            return false;
        }

        $schema = array(
            'status' => 'text',
            'end_date' => 'datetime',
            'renewal_date' => 'datetime',
            'auto_renew' => 'bool',
            'payment_method' => 'text',
            'notes' => 'textarea',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        $result = $this->db->update('members', $sanitized, array('id' => $member_id));

        if ($result !== false) {
            $this->log_activity('member', $member_id, 'updated', 'Member updated', $old_member, $sanitized);
            do_action('semigapp_member_updated', $member_id, $sanitized, $old_member);
        }

        return $result !== false;
    }

    /**
     * Cancel membership
     *
     * @param int $member_id Member ID.
     * @return bool True on success.
     */
    public function cancel_member($member_id) {
        $member = $this->get_member($member_id);

        if (!$member) {
            return false;
        }

        $result = $this->update_member($member_id, array('status' => 'cancelled'));

        if ($result) {
            do_action('semigapp_member_cancelled', $member_id, $member);
        }

        return $result;
    }

    /**
     * Renew membership
     *
     * @param int $member_id Member ID.
     * @return bool True on success.
     */
    public function renew_member($member_id) {
        $member = $this->get_member($member_id);

        if (!$member) {
            return false;
        }

        $level = $this->get_level($member->membership_level_id);

        if (!$level) {
            return false;
        }

        // Calculate new end date from current end date or now
        $start = max($member->end_date, current_time('mysql'));
        $new_end_date = $this->calculate_end_date($level, $start);

        $result = $this->update_member($member_id, array(
            'status' => 'active',
            'end_date' => $new_end_date,
            'renewal_date' => $new_end_date,
        ));

        if ($result) {
            $this->send_notification('membership_renewed', get_userdata($member->user_id)->user_email, array(
                'first_name' => get_userdata($member->user_id)->first_name,
                'level_name' => $level->name,
                'end_date' => $this->format_date($new_end_date),
            ));

            do_action('semigapp_member_renewed', $member_id, $member);
        }

        return $result;
    }

    /**
     * Get member
     *
     * @param int $member_id Member ID.
     * @return object|null Member or null.
     */
    public function get_member($member_id) {
        $member = $this->db->get_row('members', array('id' => $member_id));

        if ($member) {
            $member->user = get_userdata($member->user_id);
            $member->level = $this->get_level($member->membership_level_id);
            $member->is_expired = $member->end_date && $member->end_date < current_time('mysql');
            $member->days_until_expiry = $member->end_date ? max(0, floor((strtotime($member->end_date) - time()) / 86400)) : null;
        }

        return $member;
    }

    /**
     * Get member by user
     *
     * @param int $user_id  User ID.
     * @param int $level_id Level ID (optional).
     * @return object|null Member or null.
     */
    public function get_member_by_user($user_id, $level_id = null) {
        $where = array('user_id' => $user_id);

        if ($level_id) {
            $where['membership_level_id'] = $level_id;
        }

        $member = $this->db->get_row('members', $where);

        if ($member) {
            $member->level = $this->get_level($member->membership_level_id);
        }

        return $member;
    }

    /**
     * Get user's active memberships
     *
     * @param int $user_id User ID.
     * @return array Active memberships.
     */
    public function get_user_memberships($user_id) {
        return $this->db->get_results('members', array(
            'where' => array('user_id' => $user_id),
            'orderby' => 'created_at',
            'order' => 'DESC',
        ));
    }

    /**
     * Check if user is active member
     *
     * @param int $user_id  User ID.
     * @param int $level_id Level ID (optional).
     * @return bool True if active member.
     */
    public function is_active_member($user_id, $level_id = null) {
        $member = $this->get_member_by_user($user_id, $level_id);

        if (!$member) {
            return false;
        }

        if ($member->status !== 'active' && $member->status !== 'trial') {
            return false;
        }

        if ($member->end_date && $member->end_date < current_time('mysql')) {
            return false;
        }

        return true;
    }

    /**
     * Get all members
     *
     * @param array $args Query arguments.
     * @return array Members.
     */
    public function get_members($args = array()) {
        $defaults = array(
            'where' => array(),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0,
            'search' => '',
        );

        $args = wp_parse_args($args, $defaults);

        $members = $this->db->get_results('members', $args);

        foreach ($members as &$member) {
            $member->user = get_userdata($member->user_id);
            $member->level = $this->get_level($member->membership_level_id);
        }

        return $members;
    }

    /**
     * Create payment
     *
     * @param int   $member_id Member ID.
     * @param array $data      Payment data.
     * @return int|false Payment ID or false.
     */
    public function create_payment($member_id, $data) {
        $member = $this->get_member($member_id);

        if (!$member) {
            return false;
        }

        $schema = array(
            'amount' => 'float',
            'currency' => 'text',
            'payment_method' => 'text',
            'transaction_id' => 'text',
            'status' => 'text',
            'notes' => 'textarea',
            'metadata' => 'json',
        );

        $sanitized = $this->sanitize_data($data, $schema);
        $sanitized['member_id'] = $member_id;
        $sanitized['currency'] = $sanitized['currency'] ?? 'SEK';
        $sanitized['status'] = $sanitized['status'] ?? 'pending';

        $payment_id = $this->db->insert('membership_payments', $sanitized);

        if ($payment_id) {
            $this->log_activity('membership_payment', $payment_id, 'created', 'Payment created');
            do_action('semigapp_membership_payment_created', $payment_id, $member_id);
        }

        return $payment_id;
    }

    /**
     * Complete payment
     *
     * @param int $payment_id Payment ID.
     * @return bool True on success.
     */
    public function complete_payment($payment_id) {
        $payment = $this->get_payment($payment_id);

        if (!$payment || $payment->status === 'completed') {
            return false;
        }

        $result = $this->db->update('membership_payments', array(
            'status' => 'completed',
            'payment_date' => current_time('mysql'),
        ), array('id' => $payment_id));

        if ($result !== false) {
            // Activate or renew membership
            $member = $this->get_member($payment->member_id);
            if ($member) {
                if ($member->status === 'pending' || $member->status === 'expired') {
                    $this->renew_member($member->id);
                }
            }

            $this->send_notification('payment_received', get_userdata($member->user_id)->user_email, array(
                'first_name' => get_userdata($member->user_id)->first_name,
                'amount' => $this->settings->format_price($payment->amount, $payment->currency),
            ));

            do_action('semigapp_membership_payment_completed', $payment_id);
        }

        return $result !== false;
    }

    /**
     * Get payment
     *
     * @param int $payment_id Payment ID.
     * @return object|null Payment or null.
     */
    public function get_payment($payment_id) {
        $payment = $this->db->get_row('membership_payments', array('id' => $payment_id));

        if ($payment) {
            $payment->metadata = json_decode($payment->metadata, true) ?: array();
            $payment->member = $this->get_member($payment->member_id);
        }

        return $payment;
    }

    /**
     * Get member payments
     *
     * @param int $member_id Member ID.
     * @return array Payments.
     */
    public function get_member_payments($member_id) {
        return $this->db->get_results('membership_payments', array(
            'where' => array('member_id' => $member_id),
            'orderby' => 'created_at',
            'order' => 'DESC',
        ));
    }

    /**
     * Check membership expiry
     */
    public function check_expiry() {
        $grace_period = $this->get_setting('grace_period_days', 7);
        $reminder_days = $this->get_setting('expiry_reminder_days', array(7, 3, 1));

        // Expire memberships
        global $wpdb;
        $table = $this->db->get_table('members');
        $grace_date = date('Y-m-d H:i:s', strtotime("-{$grace_period} days"));

        $expired = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE status IN ('active', 'trial') AND end_date < %s",
            $grace_date
        ));

        foreach ($expired as $member) {
            $this->update_member($member->id, array('status' => 'expired'));

            $user = get_userdata($member->user_id);
            if ($user) {
                $this->send_notification('membership_expired', $user->user_email, array(
                    'first_name' => $user->first_name,
                ));
            }

            do_action('semigapp_membership_expired', $member->id);
        }

        // Send expiry reminders
        foreach ($reminder_days as $days) {
            $reminder_date = date('Y-m-d', strtotime("+{$days} days"));

            $expiring = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE status = 'active' AND DATE(end_date) = %s",
                $reminder_date
            ));

            foreach ($expiring as $member) {
                do_action('semigapp_membership_expiring', $member->id, $days);
            }
        }
    }

    /**
     * Restrict content
     *
     * @param string $content Post content.
     * @return string Modified content.
     */
    public function restrict_content($content) {
        if (!$this->get_setting('restrict_content', true)) {
            return $content;
        }

        global $post;

        if (!$post) {
            return $content;
        }

        $required_level = get_post_meta($post->ID, '_semigapp_membership_required', true);

        if (!$required_level) {
            return $content;
        }

        // Check if user has access
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();

            if ($required_level === 'any') {
                if ($this->is_active_member($user_id)) {
                    return $content;
                }
            } else {
                if ($this->is_active_member($user_id, intval($required_level))) {
                    return $content;
                }
            }
        }

        // Show restricted message
        $message = apply_filters('semigapp_restricted_content_message',
            '<div class="semigapp-restricted-content">' .
            '<p>' . __('This content is restricted to members only.', 'semigapp') . '</p>' .
            '<a href="' . esc_url($this->settings->get_page_url('membership')) . '" class="button">' .
            __('View Membership Options', 'semigapp') . '</a>' .
            '</div>'
        );

        return $message;
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('semigapp_membership_levels', array($this, 'levels_shortcode'));
        add_shortcode('semigapp_membership_account', array($this, 'account_shortcode'));
        add_shortcode('semigapp_member_content', array($this, 'member_content_shortcode'));
    }

    /**
     * Membership levels shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function levels_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 3,
        ), $atts);

        $levels = $this->get_levels();

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/membership/levels.php';
        return ob_get_clean();
    }

    /**
     * Account shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function account_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your membership.', 'semigapp') . '</p>';
        }

        $user_id = get_current_user_id();
        $memberships = $this->get_user_memberships($user_id);

        foreach ($memberships as &$membership) {
            $membership->level = $this->get_level($membership->membership_level_id);
            $membership->payments = $this->get_member_payments($membership->id);
        }

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/membership/account.php';
        return ob_get_clean();
    }

    /**
     * Member-only content shortcode
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @return string HTML output.
     */
    public function member_content_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'level' => '',
        ), $atts);

        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view this content.', 'semigapp') . '</p>';
        }

        $user_id = get_current_user_id();
        $has_access = false;

        if (empty($atts['level'])) {
            $has_access = $this->is_active_member($user_id);
        } else {
            $level = $this->get_level_by_slug($atts['level']);
            if ($level) {
                $has_access = $this->is_active_member($user_id, $level->id);
            }
        }

        if ($has_access) {
            return do_shortcode($content);
        }

        return '<p>' . __('This content is restricted to members.', 'semigapp') . '</p>';
    }

    /**
     * Notify welcome email
     *
     * @param int $member_id Member ID.
     * @param int $level_id  Level ID.
     */
    public function notify_welcome($member_id, $level_id) {
        $member = $this->get_member($member_id);
        $level = $this->get_level($level_id);

        if (!$member || !$level) {
            return;
        }

        $user = get_userdata($member->user_id);

        $this->send_notification('membership_welcome', $user->user_email, array(
            'first_name' => $user->first_name ?: $user->display_name,
            'level_name' => $level->name,
            'end_date' => $this->format_date($member->end_date),
        ));
    }

    /**
     * Notify expiring membership
     *
     * @param int $member_id     Member ID.
     * @param int $days_until    Days until expiry.
     */
    public function notify_expiring($member_id, $days_until) {
        $member = $this->get_member($member_id);

        if (!$member) {
            return;
        }

        $user = get_userdata($member->user_id);

        $this->send_notification('membership_expiring', $user->user_email, array(
            'first_name' => $user->first_name ?: $user->display_name,
            'days_until' => $days_until,
            'end_date' => $this->format_date($member->end_date),
            'renewal_url' => $this->settings->get_page_url('account'),
        ));
    }

    /**
     * Handle AJAX requests
     */
    public function handle_ajax() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in.', 'semigapp')));
        }

        $action = isset($_POST['membership_action']) ? sanitize_text_field($_POST['membership_action']) : '';

        switch ($action) {
            case 'cancel':
                $member = $this->get_member_by_user(get_current_user_id());
                if ($member) {
                    $result = $this->cancel_member($member->id);
                    if ($result) {
                        wp_send_json_success(array('message' => __('Membership cancelled.', 'semigapp')));
                    }
                }
                wp_send_json_error(array('message' => __('Could not cancel membership.', 'semigapp')));
                break;

            default:
                wp_send_json_error(array('message' => __('Invalid action', 'semigapp')));
        }
    }

    /**
     * Handle signup AJAX
     */
    public function handle_signup() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        // Rate limit: 3 signup attempts per hour per IP
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $rate_key = 'semigapp_signup_rate_' . md5($ip);
        $rate_count = get_transient($rate_key);

        if ($rate_count !== false && $rate_count >= 3) {
            wp_send_json_error(array('message' => __('Too many signup attempts. Please try again later.', 'semigapp')));
        }

        set_transient($rate_key, ($rate_count ?: 0) + 1, HOUR_IN_SECONDS);

        $level_id = isset($_POST['level_id']) ? absint($_POST['level_id']) : 0;
        if (!$level_id) {
            wp_send_json_error(array('message' => __('Please select a membership level.', 'semigapp')));
        }

        $level = $this->get_level($level_id);

        if (!$level) {
            wp_send_json_error(array('message' => __('Invalid membership level.', 'semigapp')));
        }

        $user_id = get_current_user_id();

        // If not logged in, create account
        if (!$user_id) {
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

            // Validate email
            if (!is_email($email)) {
                wp_send_json_error(array('message' => __('Please provide a valid email address.', 'semigapp')));
            }

            // Check if email already exists
            if (email_exists($email)) {
                wp_send_json_error(array('message' => __('An account with this email already exists. Please log in.', 'semigapp')));
            }

            // Generate or use provided password
            $password = isset($_POST['password']) && !empty($_POST['password'])
                ? $_POST['password']
                : wp_generate_password(12, true, true);

            // Validate password strength if provided
            if (isset($_POST['password']) && strlen($_POST['password']) < 8) {
                wp_send_json_error(array('message' => __('Password must be at least 8 characters long.', 'semigapp')));
            }

            $user_id = wp_create_user($email, $password, $email);

            if (is_wp_error($user_id)) {
                wp_send_json_error(array('message' => $user_id->get_error_message()));
            }

            // Log user in
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
        }

        // Create membership
        $member_id = $this->create_member($user_id, $level_id, array(
            'payment_method' => isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '',
        ));

        if ($member_id) {
            // If paid membership, redirect to payment
            if ($level->price > 0) {
                $payment_id = $this->create_payment($member_id, array(
                    'amount' => $level->price,
                    'currency' => 'SEK',
                    'payment_method' => isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '',
                ));

                wp_send_json_success(array(
                    'member_id' => $member_id,
                    'payment_id' => $payment_id,
                    'redirect' => add_query_arg(array(
                        'payment_id' => $payment_id,
                        'type' => 'membership',
                    ), $this->settings->get_page_url('checkout')),
                ));
            }

            wp_send_json_success(array(
                'member_id' => $member_id,
                'message' => __('Membership activated!', 'semigapp'),
                'redirect' => $this->settings->get_page_url('account'),
            ));
        }

        wp_send_json_error(array('message' => __('Could not create membership.', 'semigapp')));
    }
}
