<?php
/**
 * Events Module
 *
 * @package SemigApp
 */

namespace SemigApp\Modules\Events;

use SemigApp\Modules\Base_Module;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Events_Module
 *
 * Handles event calendar and management
 */
class Events_Module extends Base_Module {

    /**
     * Module ID
     *
     * @var string
     */
    protected $id = 'events';

    /**
     * Module name
     *
     * @var string
     */
    protected $name = 'Event Calendar';

    /**
     * Event statuses
     *
     * @var array
     */
    private $statuses = array();

    /**
     * Event types
     *
     * @var array
     */
    private $types = array();

    /**
     * Initialize the module
     */
    public function init() {
        $this->setup_statuses();

        // Register hooks
        add_action('semigapp_register_shortcodes', array($this, 'register_shortcodes'));
        add_action('wp_ajax_semigapp_event_action', array($this, 'handle_ajax'));
        add_action('wp_ajax_nopriv_semigapp_event_register', array($this, 'handle_registration'));
        add_action('wp_ajax_semigapp_event_register', array($this, 'handle_registration'));

        // Scheduled events
        add_action('semigapp_event_reminders', array($this, 'send_reminders'));

        // Email notifications
        add_action('semigapp_event_registration_created', array($this, 'notify_registration'), 10, 2);
    }

    /**
     * Setup statuses and types
     */
    private function setup_statuses() {
        $this->statuses = array(
            'draft' => __('Draft', 'semigapp'),
            'published' => __('Published', 'semigapp'),
            'cancelled' => __('Cancelled', 'semigapp'),
            'completed' => __('Completed', 'semigapp'),
        );

        $this->types = array(
            'single' => __('Single Event', 'semigapp'),
            'recurring' => __('Recurring Event', 'semigapp'),
            'multi_day' => __('Multi-Day Event', 'semigapp'),
        );
    }

    /**
     * Get event statuses
     *
     * @return array
     */
    public function get_statuses() {
        return apply_filters('semigapp_event_statuses', $this->statuses);
    }

    /**
     * Get event types
     *
     * @return array
     */
    public function get_types() {
        return apply_filters('semigapp_event_types', $this->types);
    }

    /**
     * Create an event
     *
     * @param array $data Event data.
     * @return int|false Event ID or false on failure.
     */
    public function create_event($data) {
        $schema = array(
            'title' => 'text',
            'description' => 'html',
            'location' => 'text',
            'location_details' => 'json',
            'event_type' => 'text',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'all_day' => 'bool',
            'recurrence_rule' => 'text',
            'recurrence_end' => 'datetime',
            'max_attendees' => 'int',
            'registration_required' => 'bool',
            'registration_deadline' => 'datetime',
            'price' => 'float',
            'organizer_id' => 'int',
            'status' => 'text',
            'featured_image' => 'int',
            'settings' => 'json',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        // Validate required fields
        $missing = $this->validate_required($sanitized, array('title', 'start_date', 'end_date'));
        if (!empty($missing)) {
            return false;
        }

        // Set defaults
        $sanitized['organizer_id'] = $sanitized['organizer_id'] ?? get_current_user_id();
        $sanitized['status'] = $sanitized['status'] ?? 'draft';
        $sanitized['event_type'] = $sanitized['event_type'] ?? 'single';

        $event_id = $this->db->insert('events', $sanitized);

        if ($event_id) {
            $this->log_activity('event', $event_id, 'created', 'Event created');
            do_action('semigapp_event_created', $event_id, $sanitized);
        }

        return $event_id;
    }

    /**
     * Update an event
     *
     * @param int   $event_id Event ID.
     * @param array $data     Event data.
     * @return bool True on success.
     */
    public function update_event($event_id, $data) {
        $old_event = $this->get_event($event_id);

        if (!$old_event) {
            return false;
        }

        $schema = array(
            'title' => 'text',
            'description' => 'html',
            'location' => 'text',
            'location_details' => 'json',
            'event_type' => 'text',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'all_day' => 'bool',
            'recurrence_rule' => 'text',
            'recurrence_end' => 'datetime',
            'max_attendees' => 'int',
            'registration_required' => 'bool',
            'registration_deadline' => 'datetime',
            'price' => 'float',
            'status' => 'text',
            'featured_image' => 'int',
            'settings' => 'json',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        $result = $this->db->update('events', $sanitized, array('id' => $event_id));

        if ($result !== false) {
            $this->log_activity('event', $event_id, 'updated', 'Event updated', $old_event, $sanitized);
            do_action('semigapp_event_updated', $event_id, $sanitized, $old_event);

            // Notify registrants if event was cancelled
            if (isset($sanitized['status']) && $sanitized['status'] === 'cancelled' && $old_event->status !== 'cancelled') {
                $this->notify_cancellation($event_id);
            }
        }

        return $result !== false;
    }

    /**
     * Delete an event
     *
     * @param int $event_id Event ID.
     * @return bool True on success.
     */
    public function delete_event($event_id) {
        $event = $this->get_event($event_id);

        if (!$event) {
            return false;
        }

        // Delete registrations
        $this->db->delete('event_registrations', array('event_id' => $event_id));

        $result = $this->db->delete('events', array('id' => $event_id));

        if ($result) {
            $this->log_activity('event', $event_id, 'deleted', 'Event deleted');
            do_action('semigapp_event_deleted', $event_id, $event);
        }

        return (bool) $result;
    }

    /**
     * Get an event
     *
     * @param int $event_id Event ID.
     * @return object|null Event or null.
     */
    public function get_event($event_id) {
        $event = $this->db->get_row('events', array('id' => $event_id));

        if ($event) {
            $event->settings = json_decode($event->settings, true) ?: array();
            $event->location_details = json_decode($event->location_details, true) ?: array();
            $event->organizer = get_userdata($event->organizer_id);
            $event->registration_count = $this->db->count('event_registrations', array(
                'event_id' => $event_id,
                'status' => 'confirmed',
            ));
            $event->spots_left = $event->max_attendees > 0 ? max(0, $event->max_attendees - $event->registration_count) : null;
        }

        return $event;
    }

    /**
     * Get events
     *
     * @param array $args Query arguments.
     * @return array Events.
     */
    public function get_events($args = array()) {
        $defaults = array(
            'where' => array(),
            'orderby' => 'start_date',
            'order' => 'ASC',
            'limit' => 0,
            'offset' => 0,
            'search' => '',
            'from_date' => '',
            'to_date' => '',
        );

        $args = wp_parse_args($args, $defaults);

        if (!empty($args['search'])) {
            $args['search_columns'] = array('title', 'description', 'location');
        }

        $events = $this->db->get_results('events', $args);

        // Filter by date range if specified
        if (!empty($args['from_date']) || !empty($args['to_date'])) {
            $events = array_filter($events, function($event) use ($args) {
                if (!empty($args['from_date']) && $event->end_date < $args['from_date']) {
                    return false;
                }
                if (!empty($args['to_date']) && $event->start_date > $args['to_date']) {
                    return false;
                }
                return true;
            });
        }

        foreach ($events as &$event) {
            $event->settings = json_decode($event->settings, true) ?: array();
            $event->registration_count = $this->db->count('event_registrations', array(
                'event_id' => $event->id,
                'status' => 'confirmed',
            ));
        }

        return $events;
    }

    /**
     * Get upcoming events
     *
     * @param int $limit Number of events.
     * @return array Events.
     */
    public function get_upcoming_events($limit = 10) {
        return $this->get_events(array(
            'where' => array('status' => 'published'),
            'from_date' => current_time('mysql'),
            'limit' => $limit,
        ));
    }

    /**
     * Get calendar events for a month
     *
     * @param int $year  Year.
     * @param int $month Month (1-12).
     * @return array Events.
     */
    public function get_calendar_events($year, $month) {
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-t 23:59:59', strtotime($start));

        return $this->get_events(array(
            'where' => array('status' => 'published'),
            'from_date' => $start,
            'to_date' => $end,
        ));
    }

    /**
     * Register for an event
     *
     * @param int   $event_id Event ID.
     * @param array $data     Registration data.
     * @return int|false Registration ID or false.
     */
    public function register($event_id, $data) {
        $event = $this->get_event($event_id);

        if (!$event) {
            return false;
        }

        // Check if registration is open
        if (!$this->can_register($event)) {
            return false;
        }

        $schema = array(
            'user_id' => 'int',
            'guest_name' => 'text',
            'guest_email' => 'email',
            'attendees' => 'int',
            'notes' => 'textarea',
        );

        $sanitized = $this->sanitize_data($data, $schema);
        $sanitized['event_id'] = $event_id;
        $sanitized['status'] = 'confirmed';
        $sanitized['attendees'] = max(1, $sanitized['attendees'] ?? 1);

        // Check capacity
        if ($event->max_attendees > 0) {
            $current_count = $event->registration_count;
            if (($current_count + $sanitized['attendees']) > $event->max_attendees) {
                return false;
            }
        }

        // Check if already registered
        $existing = $this->get_registration_by_user($event_id, $sanitized['user_id'] ?? null, $sanitized['guest_email'] ?? null);
        if ($existing) {
            return false;
        }

        // Set user ID if logged in
        if (is_user_logged_in() && empty($sanitized['user_id'])) {
            $sanitized['user_id'] = get_current_user_id();
        }

        // Handle paid events
        if ($event->price > 0) {
            $sanitized['payment_status'] = 'pending';
        } else {
            $sanitized['payment_status'] = 'completed';
        }

        $registration_id = $this->db->insert('event_registrations', $sanitized);

        if ($registration_id) {
            $this->log_activity('event_registration', $registration_id, 'created', 'Registration created');
            do_action('semigapp_event_registration_created', $registration_id, $event_id);
        }

        return $registration_id;
    }

    /**
     * Check if registration is possible
     *
     * @param object $event Event object.
     * @return bool True if can register.
     */
    public function can_register($event) {
        // Check if registration is required
        if (!$event->registration_required) {
            return false;
        }

        // Check status
        if ($event->status !== 'published') {
            return false;
        }

        // Check if event has passed
        if ($event->end_date < current_time('mysql')) {
            return false;
        }

        // Check registration deadline
        if ($event->registration_deadline && $event->registration_deadline < current_time('mysql')) {
            return false;
        }

        // Check capacity
        if ($event->max_attendees > 0 && $event->spots_left <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Cancel registration
     *
     * @param int $registration_id Registration ID.
     * @return bool True on success.
     */
    public function cancel_registration($registration_id) {
        $registration = $this->get_registration($registration_id);

        if (!$registration) {
            return false;
        }

        $result = $this->db->update('event_registrations', array(
            'status' => 'cancelled',
        ), array('id' => $registration_id));

        if ($result !== false) {
            $this->log_activity('event_registration', $registration_id, 'cancelled', 'Registration cancelled');
            do_action('semigapp_event_registration_cancelled', $registration_id);
        }

        return $result !== false;
    }

    /**
     * Get registration
     *
     * @param int $registration_id Registration ID.
     * @return object|null Registration or null.
     */
    public function get_registration($registration_id) {
        $registration = $this->db->get_row('event_registrations', array('id' => $registration_id));

        if ($registration) {
            $registration->event = $this->get_event($registration->event_id);
            $registration->user = $registration->user_id ? get_userdata($registration->user_id) : null;
        }

        return $registration;
    }

    /**
     * Get registration by user/email
     *
     * @param int    $event_id Event ID.
     * @param int    $user_id  User ID.
     * @param string $email    Email.
     * @return object|null Registration or null.
     */
    public function get_registration_by_user($event_id, $user_id = null, $email = null) {
        if ($user_id) {
            return $this->db->get_row('event_registrations', array(
                'event_id' => $event_id,
                'user_id' => $user_id,
                'status' => 'confirmed',
            ));
        }

        if ($email) {
            return $this->db->get_row('event_registrations', array(
                'event_id' => $event_id,
                'guest_email' => $email,
                'status' => 'confirmed',
            ));
        }

        return null;
    }

    /**
     * Get event registrations
     *
     * @param int   $event_id Event ID.
     * @param array $args     Query arguments.
     * @return array Registrations.
     */
    public function get_registrations($event_id, $args = array()) {
        $defaults = array(
            'where' => array('event_id' => $event_id),
            'orderby' => 'created_at',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $args['where']['event_id'] = $event_id;

        $registrations = $this->db->get_results('event_registrations', $args);

        foreach ($registrations as &$reg) {
            $reg->user = $reg->user_id ? get_userdata($reg->user_id) : null;
            $reg->display_name = $reg->user ? $reg->user->display_name : $reg->guest_name;
            $reg->display_email = $reg->user ? $reg->user->user_email : $reg->guest_email;
        }

        return $registrations;
    }

    /**
     * Check in attendee
     *
     * @param int $registration_id Registration ID.
     * @return bool True on success.
     */
    public function check_in($registration_id) {
        return $this->db->update('event_registrations', array(
            'check_in_time' => current_time('mysql'),
        ), array('id' => $registration_id)) !== false;
    }

    /**
     * Get user's registrations
     *
     * @param int $user_id User ID.
     * @return array Registrations.
     */
    public function get_user_registrations($user_id) {
        $registrations = $this->db->get_results('event_registrations', array(
            'where' => array('user_id' => $user_id),
            'orderby' => 'created_at',
            'order' => 'DESC',
        ));

        foreach ($registrations as &$reg) {
            $reg->event = $this->get_event($reg->event_id);
        }

        return $registrations;
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('semigapp_events', array($this, 'events_shortcode'));
        add_shortcode('semigapp_event', array($this, 'single_event_shortcode'));
        add_shortcode('semigapp_calendar', array($this, 'calendar_shortcode'));
        add_shortcode('semigapp_event_registration', array($this, 'registration_form_shortcode'));
    }

    /**
     * Events list shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'upcoming' => 'yes',
            'category' => '',
        ), $atts);

        if ($atts['upcoming'] === 'yes') {
            $events = $this->get_upcoming_events(intval($atts['limit']));
        } else {
            $events = $this->get_events(array(
                'where' => array('status' => 'published'),
                'limit' => intval($atts['limit']),
            ));
        }

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/events/list.php';
        return ob_get_clean();
    }

    /**
     * Single event shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function single_event_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        $event = $this->get_event(intval($atts['id']));

        if (!$event) {
            return '<p>' . __('Event not found.', 'semigapp') . '</p>';
        }

        $can_register = $this->can_register($event);
        $registrations = $this->get_registrations($event->id);

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/events/single.php';
        return ob_get_clean();
    }

    /**
     * Calendar shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function calendar_shortcode($atts) {
        $atts = shortcode_atts(array(
            'month' => date('n'),
            'year' => date('Y'),
        ), $atts);

        $year = intval($atts['year']);
        $month = intval($atts['month']);
        $events = $this->get_calendar_events($year, $month);

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/events/calendar.php';
        return ob_get_clean();
    }

    /**
     * Registration form shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function registration_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        $event = $this->get_event(intval($atts['id']));

        if (!$event) {
            return '<p>' . __('Event not found.', 'semigapp') . '</p>';
        }

        if (!$this->can_register($event)) {
            return '<p>' . __('Registration is not available for this event.', 'semigapp') . '</p>';
        }

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/events/registration-form.php';
        return ob_get_clean();
    }

    /**
     * Send event reminders
     */
    public function send_reminders() {
        $reminder_hours = $this->get_setting('default_reminder_hours', 24);
        $reminder_time = date('Y-m-d H:i:s', strtotime('+' . $reminder_hours . ' hours'));

        global $wpdb;
        $table = $this->db->get_table('events');
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE status = 'published' AND start_date BETWEEN NOW() AND %s",
            $reminder_time
        ));

        foreach ($events as $event) {
            $registrations = $this->get_registrations($event->id, array(
                'where' => array('status' => 'confirmed'),
            ));

            foreach ($registrations as $reg) {
                $email = $reg->display_email;
                if ($email) {
                    $this->send_notification('event_reminder', $email, array(
                        'event_title' => $event->title,
                        'event_date' => $this->format_date($event->start_date),
                        'event_location' => $event->location,
                        'first_name' => $reg->display_name,
                    ));
                }
            }
        }
    }

    /**
     * Notify registration
     *
     * @param int $registration_id Registration ID.
     * @param int $event_id        Event ID.
     */
    public function notify_registration($registration_id, $event_id) {
        $registration = $this->get_registration($registration_id);
        $event = $this->get_event($event_id);

        if (!$registration || !$event) {
            return;
        }

        $email = $registration->display_email;
        if ($email) {
            $this->send_notification('event_registration', $email, array(
                'event_title' => $event->title,
                'event_date' => $this->format_date($event->start_date),
                'event_location' => $event->location,
                'first_name' => $registration->display_name,
            ));
        }
    }

    /**
     * Notify event cancellation
     *
     * @param int $event_id Event ID.
     */
    private function notify_cancellation($event_id) {
        $event = $this->get_event($event_id);
        $registrations = $this->get_registrations($event_id, array(
            'where' => array('status' => 'confirmed'),
        ));

        foreach ($registrations as $reg) {
            $email = $reg->display_email;
            if ($email) {
                $this->send_notification('event_cancelled', $email, array(
                    'event_title' => $event->title,
                    'event_date' => $this->format_date($event->start_date),
                    'first_name' => $reg->display_name,
                ));
            }
        }
    }

    /**
     * Handle AJAX requests
     */
    public function handle_ajax() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        $action = isset($_POST['event_action']) ? sanitize_text_field($_POST['event_action']) : '';

        switch ($action) {
            case 'get_calendar':
                $year = intval($_POST['year']);
                $month = intval($_POST['month']);
                $events = $this->get_calendar_events($year, $month);
                wp_send_json_success(array('events' => $events));
                break;

            case 'get_event':
                $event = $this->get_event(intval($_POST['event_id']));
                wp_send_json_success(array('event' => $event));
                break;

            default:
                wp_send_json_error(array('message' => __('Invalid action', 'semigapp')));
        }
    }

    /**
     * Handle registration AJAX
     */
    public function handle_registration() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        $event_id = intval($_POST['event_id']);
        $result = $this->register($event_id, $_POST);

        if ($result) {
            wp_send_json_success(array(
                'registration_id' => $result,
                'message' => __('Registration successful!', 'semigapp'),
            ));
        } else {
            wp_send_json_error(array('message' => __('Registration failed. Please try again.', 'semigapp')));
        }
    }
}
