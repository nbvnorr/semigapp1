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
     * Application statuses
     *
     * @var array
     */
    private $application_statuses = array();

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

        // Application AJAX handlers
        add_action('wp_ajax_nopriv_semigapp_event_apply', array($this, 'handle_application'));
        add_action('wp_ajax_semigapp_event_apply', array($this, 'handle_application'));
        add_action('wp_ajax_semigapp_application_action', array($this, 'handle_application_admin_action'));

        // Scheduled events
        add_action('semigapp_event_reminders', array($this, 'send_reminders'));

        // Email notifications
        add_action('semigapp_event_registration_created', array($this, 'notify_registration'), 10, 2);
        add_action('semigapp_application_submitted', array($this, 'notify_application_submitted'), 10, 2);
        add_action('semigapp_application_approved', array($this, 'notify_application_approved'), 10, 2);
        add_action('semigapp_application_rejected', array($this, 'notify_application_rejected'), 10, 2);
        add_action('semigapp_application_waitlisted', array($this, 'notify_application_waitlisted'), 10, 2);
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

        $this->application_statuses = array(
            'pending' => __('Pending Review', 'semigapp'),
            'approved' => __('Approved', 'semigapp'),
            'rejected' => __('Rejected', 'semigapp'),
            'waitlisted' => __('Waitlisted', 'semigapp'),
            'cancelled' => __('Cancelled', 'semigapp'),
        );
    }

    /**
     * Get application statuses
     *
     * @return array
     */
    public function get_application_statuses() {
        return apply_filters('semigapp_application_statuses', $this->application_statuses);
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

        if (empty($events)) {
            return array();
        }

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
            $events = array_values($events); // Re-index array
        }

        // Batch load registration counts to avoid N+1 query
        $event_ids = wp_list_pluck($events, 'id');
        $registration_counts = $this->get_registration_counts_by_event($event_ids);

        foreach ($events as &$event) {
            $event->settings = json_decode($event->settings, true) ?: array();
            $event->registration_count = $registration_counts[$event->id] ?? 0;
        }

        return $events;
    }

    /**
     * Get registration counts grouped by event (fixes N+1 query)
     *
     * @param array $event_ids Array of event IDs.
     * @return array Associative array of event_id => count.
     */
    private function get_registration_counts_by_event($event_ids) {
        if (empty($event_ids)) {
            return array();
        }

        global $wpdb;
        $table = $this->db->get_table('event_registrations');
        $placeholders = implode(',', array_fill(0, count($event_ids), '%d'));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT event_id, COUNT(*) as count
             FROM $table
             WHERE event_id IN ($placeholders)
             AND status = 'confirmed'
             GROUP BY event_id",
            $event_ids
        ));

        $counts = array();
        foreach ($results as $row) {
            $counts[$row->event_id] = (int) $row->count;
        }

        return $counts;
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
        add_shortcode('semigapp_event_application', array($this, 'application_form_shortcode'));
        add_shortcode('semigapp_my_applications', array($this, 'my_applications_shortcode'));
    }

    /**
     * Application form shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function application_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        $event = $this->get_event(intval($atts['id']));

        if (!$event) {
            return '<p>' . __('Event not found.', 'semigapp') . '</p>';
        }

        if (!$this->can_apply($event)) {
            return '<p>' . __('Applications are not being accepted for this event.', 'semigapp') . '</p>';
        }

        // Check if user already applied
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $existing = $this->get_application_by_email($event->id, $user->user_email);
            if ($existing && $existing->status !== 'cancelled') {
                return '<div class="semigapp-notice semigapp-notice-info"><p>' .
                    sprintf(__('You have already applied for this event. Your application status: %s', 'semigapp'),
                    '<strong>' . $this->application_statuses[$existing->status] . '</strong>') .
                    '</p></div>';
            }
        }

        $custom_fields = $this->get_application_fields($event->id);

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/events/application-form.php';
        return ob_get_clean();
    }

    /**
     * My applications shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function my_applications_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your applications.', 'semigapp') . '</p>';
        }

        $applications = $this->get_user_applications(get_current_user_id());
        $statuses = $this->get_application_statuses();

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/events/my-applications.php';
        return ob_get_clean();
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
                $year = isset($_POST['year']) ? absint($_POST['year']) : 0;
                $month = isset($_POST['month']) ? absint($_POST['month']) : 0;
                if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
                    wp_send_json_error(array('message' => __('Invalid date parameters.', 'semigapp')));
                }
                $events = $this->get_calendar_events($year, $month);
                wp_send_json_success(array('events' => $events));
                break;

            case 'get_event':
                $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
                if (!$event_id) {
                    wp_send_json_error(array('message' => __('Invalid event ID.', 'semigapp')));
                }
                $event = $this->get_event($event_id);
                if (!$event) {
                    wp_send_json_error(array('message' => __('Event not found.', 'semigapp')));
                }
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

        // Rate limit: 5 registrations per hour per IP
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $rate_key = 'semigapp_reg_rate_' . md5($ip);
        $rate_count = get_transient($rate_key);

        if ($rate_count !== false && $rate_count >= 5) {
            wp_send_json_error(array('message' => __('Too many registration attempts. Please try again later.', 'semigapp')));
        }

        set_transient($rate_key, ($rate_count ?: 0) + 1, HOUR_IN_SECONDS);

        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID.', 'semigapp')));
        }

        // Validate required fields
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please provide a valid email address.', 'semigapp')));
        }

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Please provide your name.', 'semigapp')));
        }

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

    // =========================================================================
    // EVENT APPLICATION MANAGEMENT
    // =========================================================================

    /**
     * Submit an application for an event
     *
     * @param int   $event_id Event ID.
     * @param array $data     Application data.
     * @return int|false Application ID or false on failure.
     */
    public function submit_application($event_id, $data) {
        $event = $this->get_event($event_id);

        if (!$event) {
            return false;
        }

        // Check if applications are accepted
        if (!$this->can_apply($event)) {
            return false;
        }

        $schema = array(
            'user_id' => 'int',
            'applicant_name' => 'text',
            'applicant_email' => 'email',
            'applicant_phone' => 'text',
            'attendees' => 'int',
            'motivation' => 'textarea',
            'custom_fields' => 'json',
        );

        $sanitized = $this->sanitize_data($data, $schema);
        $sanitized['event_id'] = $event_id;
        $sanitized['status'] = 'pending';
        $sanitized['attendees'] = max(1, $sanitized['attendees'] ?? 1);

        // Validate required fields
        if (empty($sanitized['applicant_name']) || empty($sanitized['applicant_email'])) {
            return false;
        }

        // Check if already applied
        $existing = $this->get_application_by_email($event_id, $sanitized['applicant_email']);
        if ($existing && $existing->status !== 'cancelled') {
            return false;
        }

        // Set user ID if logged in
        if (is_user_logged_in() && empty($sanitized['user_id'])) {
            $sanitized['user_id'] = get_current_user_id();
        }

        $application_id = $this->db->insert('event_applications', $sanitized);

        if ($application_id) {
            $this->log_activity('event_application', $application_id, 'submitted', 'Application submitted');
            do_action('semigapp_application_submitted', $application_id, $event_id);

            // Auto-approve if configured
            $auto_approve = $this->get_setting('auto_approve_applications', false);
            if ($auto_approve) {
                $this->approve_application($application_id);
            }
        }

        return $application_id;
    }

    /**
     * Approve an application
     *
     * @param int    $application_id Application ID.
     * @param string $notes          Admin notes (optional).
     * @return bool|int Registration ID on success, false on failure.
     */
    public function approve_application($application_id, $notes = '') {
        $application = $this->get_application($application_id);

        if (!$application || $application->status === 'approved') {
            return false;
        }

        $event = $this->get_event($application->event_id);

        // Check capacity
        if ($event->max_attendees > 0) {
            $current_count = $event->registration_count;
            if (($current_count + $application->attendees) > $event->max_attendees) {
                // Add to waitlist instead
                return $this->add_to_waitlist($application_id);
            }
        }

        // Update application status
        $update_data = array(
            'status' => 'approved',
            'reviewed_by' => get_current_user_id(),
            'reviewed_at' => current_time('mysql'),
        );

        if (!empty($notes)) {
            $update_data['admin_notes'] = $notes;
        }

        $result = $this->db->update('event_applications', $update_data, array('id' => $application_id));

        if ($result !== false) {
            // Create registration from approved application
            $registration_data = array(
                'event_id' => $application->event_id,
                'user_id' => $application->user_id,
                'guest_name' => $application->applicant_name,
                'guest_email' => $application->applicant_email,
                'attendees' => $application->attendees,
                'status' => 'confirmed',
                'payment_status' => $event->price > 0 ? 'pending' : 'completed',
            );

            $registration_id = $this->db->insert('event_registrations', $registration_data);

            if ($registration_id) {
                $this->db->update('event_applications', array(
                    'registration_id' => $registration_id,
                ), array('id' => $application_id));
            }

            $this->log_activity('event_application', $application_id, 'approved', 'Application approved');
            do_action('semigapp_application_approved', $application_id, $application->event_id);

            return $registration_id;
        }

        return false;
    }

    /**
     * Reject an application
     *
     * @param int    $application_id Application ID.
     * @param string $reason         Rejection reason (optional).
     * @param string $notes          Admin notes (optional).
     * @return bool True on success.
     */
    public function reject_application($application_id, $reason = '', $notes = '') {
        $application = $this->get_application($application_id);

        if (!$application || $application->status === 'rejected') {
            return false;
        }

        $update_data = array(
            'status' => 'rejected',
            'reviewed_by' => get_current_user_id(),
            'reviewed_at' => current_time('mysql'),
        );

        if (!empty($reason)) {
            $update_data['rejection_reason'] = sanitize_textarea_field($reason);
        }

        if (!empty($notes)) {
            $update_data['admin_notes'] = sanitize_textarea_field($notes);
        }

        $result = $this->db->update('event_applications', $update_data, array('id' => $application_id));

        if ($result !== false) {
            $this->log_activity('event_application', $application_id, 'rejected', 'Application rejected');
            do_action('semigapp_application_rejected', $application_id, $application->event_id);
        }

        return $result !== false;
    }

    /**
     * Add application to waitlist
     *
     * @param int $application_id Application ID.
     * @return bool True on success.
     */
    public function add_to_waitlist($application_id) {
        $application = $this->get_application($application_id);

        if (!$application) {
            return false;
        }

        // Get next waitlist position
        $position = $this->get_next_waitlist_position($application->event_id);

        $result = $this->db->update('event_applications', array(
            'status' => 'waitlisted',
            'waitlist_position' => $position,
            'reviewed_by' => get_current_user_id(),
            'reviewed_at' => current_time('mysql'),
        ), array('id' => $application_id));

        if ($result !== false) {
            $this->log_activity('event_application', $application_id, 'waitlisted', 'Application waitlisted at position ' . $position);
            do_action('semigapp_application_waitlisted', $application_id, $application->event_id);
        }

        return $result !== false;
    }

    /**
     * Get next waitlist position for an event
     *
     * @param int $event_id Event ID.
     * @return int Next position.
     */
    private function get_next_waitlist_position($event_id) {
        global $wpdb;
        $table = $this->db->get_table('event_applications');

        $max_position = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(waitlist_position) FROM $table WHERE event_id = %d AND status = 'waitlisted'",
            $event_id
        ));

        return ($max_position ? intval($max_position) : 0) + 1;
    }

    /**
     * Promote from waitlist when spot becomes available
     *
     * @param int $event_id Event ID.
     * @return int|false Application ID promoted or false.
     */
    public function promote_from_waitlist($event_id) {
        $event = $this->get_event($event_id);

        if (!$event || $event->spots_left <= 0) {
            return false;
        }

        global $wpdb;
        $table = $this->db->get_table('event_applications');

        $next_application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE event_id = %d AND status = 'waitlisted' ORDER BY waitlist_position ASC LIMIT 1",
            $event_id
        ));

        if ($next_application) {
            return $this->approve_application($next_application->id);
        }

        return false;
    }

    /**
     * Cancel an application
     *
     * @param int $application_id Application ID.
     * @return bool True on success.
     */
    public function cancel_application($application_id) {
        $application = $this->get_application($application_id);

        if (!$application) {
            return false;
        }

        // If already approved, cancel the registration too
        if ($application->registration_id) {
            $this->cancel_registration($application->registration_id);
        }

        $result = $this->db->update('event_applications', array(
            'status' => 'cancelled',
        ), array('id' => $application_id));

        if ($result !== false) {
            $this->log_activity('event_application', $application_id, 'cancelled', 'Application cancelled');

            // Promote from waitlist if there was a spot freed
            if ($application->status === 'approved') {
                $this->promote_from_waitlist($application->event_id);
            }
        }

        return $result !== false;
    }

    /**
     * Check if applications are accepted for an event
     *
     * @param object $event Event object.
     * @return bool True if can apply.
     */
    public function can_apply($event) {
        // Check if event requires applications
        $settings = $event->settings ?? array();
        if (empty($settings['requires_application'])) {
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

        // Check application deadline
        $app_deadline = $settings['application_deadline'] ?? $event->registration_deadline;
        if ($app_deadline && $app_deadline < current_time('mysql')) {
            return false;
        }

        return true;
    }

    /**
     * Get an application
     *
     * @param int $application_id Application ID.
     * @return object|null Application or null.
     */
    public function get_application($application_id) {
        $application = $this->db->get_row('event_applications', array('id' => $application_id));

        if ($application) {
            $application->custom_fields = json_decode($application->custom_fields, true) ?: array();
            $application->event = $this->get_event($application->event_id);
            $application->user = $application->user_id ? get_userdata($application->user_id) : null;
            $application->reviewer = $application->reviewed_by ? get_userdata($application->reviewed_by) : null;
        }

        return $application;
    }

    /**
     * Get application by email
     *
     * @param int    $event_id Event ID.
     * @param string $email    Email address.
     * @return object|null Application or null.
     */
    public function get_application_by_email($event_id, $email) {
        return $this->db->get_row('event_applications', array(
            'event_id' => $event_id,
            'applicant_email' => $email,
        ));
    }

    /**
     * Get applications for an event
     *
     * @param int   $event_id Event ID.
     * @param array $args     Query arguments.
     * @return array Applications.
     */
    public function get_applications($event_id, $args = array()) {
        $defaults = array(
            'where' => array('event_id' => $event_id),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);
        $args['where']['event_id'] = $event_id;

        $applications = $this->db->get_results('event_applications', $args);

        foreach ($applications as &$app) {
            $app->custom_fields = json_decode($app->custom_fields, true) ?: array();
            $app->user = $app->user_id ? get_userdata($app->user_id) : null;
        }

        return $applications;
    }

    /**
     * Get all applications (admin view)
     *
     * @param array $args Query arguments.
     * @return array Applications.
     */
    public function get_all_applications($args = array()) {
        $defaults = array(
            'where' => array(),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
            'search' => '',
        );

        $args = wp_parse_args($args, $defaults);

        if (!empty($args['search'])) {
            $args['search_columns'] = array('applicant_name', 'applicant_email');
        }

        $applications = $this->db->get_results('event_applications', $args);

        foreach ($applications as &$app) {
            $app->custom_fields = json_decode($app->custom_fields, true) ?: array();
            $app->event = $this->get_event($app->event_id);
            $app->user = $app->user_id ? get_userdata($app->user_id) : null;
        }

        return $applications;
    }

    /**
     * Get user's applications
     *
     * @param int $user_id User ID.
     * @return array Applications.
     */
    public function get_user_applications($user_id) {
        $applications = $this->db->get_results('event_applications', array(
            'where' => array('user_id' => $user_id),
            'orderby' => 'created_at',
            'order' => 'DESC',
        ));

        foreach ($applications as &$app) {
            $app->event = $this->get_event($app->event_id);
        }

        return $applications;
    }

    /**
     * Get application counts for an event
     *
     * @param int $event_id Event ID.
     * @return array Counts by status.
     */
    public function get_application_counts($event_id) {
        global $wpdb;
        $table = $this->db->get_table('event_applications');

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM $table WHERE event_id = %d GROUP BY status",
            $event_id
        ));

        $counts = array(
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'waitlisted' => 0,
            'cancelled' => 0,
            'total' => 0,
        );

        foreach ($results as $row) {
            $counts[$row->status] = intval($row->count);
            $counts['total'] += intval($row->count);
        }

        return $counts;
    }

    // =========================================================================
    // APPLICATION FORM FIELDS
    // =========================================================================

    /**
     * Get custom application fields for an event
     *
     * @param int $event_id Event ID.
     * @return array Fields.
     */
    public function get_application_fields($event_id) {
        return $this->db->get_results('event_application_fields', array(
            'where' => array('event_id' => $event_id),
            'orderby' => 'sort_order',
            'order' => 'ASC',
        ));
    }

    /**
     * Save custom application field
     *
     * @param int   $event_id Event ID.
     * @param array $field    Field data.
     * @return int|bool Field ID or false.
     */
    public function save_application_field($event_id, $field) {
        $schema = array(
            'field_name' => 'text',
            'field_label' => 'text',
            'field_type' => 'text',
            'field_options' => 'json',
            'is_required' => 'bool',
            'placeholder' => 'text',
            'help_text' => 'text',
            'sort_order' => 'int',
        );

        $sanitized = $this->sanitize_data($field, $schema);
        $sanitized['event_id'] = $event_id;

        if (!empty($field['id'])) {
            return $this->db->update('event_application_fields', $sanitized, array('id' => $field['id']));
        }

        return $this->db->insert('event_application_fields', $sanitized);
    }

    /**
     * Delete application field
     *
     * @param int $field_id Field ID.
     * @return bool True on success.
     */
    public function delete_application_field($field_id) {
        return (bool) $this->db->delete('event_application_fields', array('id' => $field_id));
    }

    // =========================================================================
    // APPLICATION NOTIFICATIONS
    // =========================================================================

    /**
     * Notify when application is submitted
     *
     * @param int $application_id Application ID.
     * @param int $event_id       Event ID.
     */
    public function notify_application_submitted($application_id, $event_id) {
        $application = $this->get_application($application_id);
        $event = $this->get_event($event_id);

        if (!$application || !$event) {
            return;
        }

        // Notify applicant
        $this->send_notification('application_received', $application->applicant_email, array(
            'first_name' => $application->applicant_name,
            'event_title' => $event->title,
            'event_date' => $this->format_date($event->start_date),
            'application_status' => __('Pending Review', 'semigapp'),
            'confirmation_message' => $this->get_setting('application_confirmation_text'),
        ));

        // Notify admin
        $admin_email = $this->get_setting('application_notification_email', get_option('admin_email'));
        $this->send_notification('new_application_admin', $admin_email, array(
            'applicant_name' => $application->applicant_name,
            'applicant_email' => $application->applicant_email,
            'event_title' => $event->title,
            'event_date' => $this->format_date($event->start_date),
            'motivation' => $application->motivation,
            'review_url' => admin_url('admin.php?page=semigapp-events&action=applications&event_id=' . $event_id),
        ));
    }

    /**
     * Notify when application is approved
     *
     * @param int $application_id Application ID.
     * @param int $event_id       Event ID.
     */
    public function notify_application_approved($application_id, $event_id) {
        $application = $this->get_application($application_id);
        $event = $this->get_event($event_id);

        if (!$application || !$event) {
            return;
        }

        $this->send_notification('application_approved', $application->applicant_email, array(
            'first_name' => $application->applicant_name,
            'event_title' => $event->title,
            'event_date' => $this->format_date($event->start_date),
            'event_location' => $event->location,
            'approval_message' => $this->get_setting('application_approved_text'),
        ));
    }

    /**
     * Notify when application is rejected
     *
     * @param int $application_id Application ID.
     * @param int $event_id       Event ID.
     */
    public function notify_application_rejected($application_id, $event_id) {
        $application = $this->get_application($application_id);
        $event = $this->get_event($event_id);

        if (!$application || !$event) {
            return;
        }

        $rejection_message = $this->get_setting('application_rejected_text');
        if (!empty($application->rejection_reason)) {
            $rejection_message .= "\n\n" . __('Reason:', 'semigapp') . ' ' . $application->rejection_reason;
        }

        $this->send_notification('application_rejected', $application->applicant_email, array(
            'first_name' => $application->applicant_name,
            'event_title' => $event->title,
            'rejection_message' => $rejection_message,
        ));
    }

    /**
     * Notify when application is waitlisted
     *
     * @param int $application_id Application ID.
     * @param int $event_id       Event ID.
     */
    public function notify_application_waitlisted($application_id, $event_id) {
        $application = $this->get_application($application_id);
        $event = $this->get_event($event_id);

        if (!$application || !$event) {
            return;
        }

        $this->send_notification('application_waitlisted', $application->applicant_email, array(
            'first_name' => $application->applicant_name,
            'event_title' => $event->title,
            'waitlist_position' => $application->waitlist_position,
            'event_date' => $this->format_date($event->start_date),
        ));
    }

    // =========================================================================
    // APPLICATION AJAX HANDLERS
    // =========================================================================

    /**
     * Handle application submission AJAX
     */
    public function handle_application() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        $event_id = intval($_POST['event_id']);

        // Validate custom fields
        $event = $this->get_event($event_id);
        $custom_fields = $this->get_application_fields($event_id);
        $custom_field_values = array();

        foreach ($custom_fields as $field) {
            $value = isset($_POST['custom_' . $field->field_name]) ? sanitize_text_field($_POST['custom_' . $field->field_name]) : '';

            if ($field->is_required && empty($value)) {
                wp_send_json_error(array(
                    'message' => sprintf(__('%s is required.', 'semigapp'), esc_html($field->field_label)),
                ));
            }

            $custom_field_values[$field->field_name] = $value;
        }

        $_POST['custom_fields'] = json_encode($custom_field_values);

        $result = $this->submit_application($event_id, $_POST);

        if ($result) {
            $confirmation_text = $this->get_setting('application_confirmation_text', __('Thank you for your application. We will review it and get back to you soon.', 'semigapp'));

            wp_send_json_success(array(
                'application_id' => $result,
                'message' => $confirmation_text,
            ));
        } else {
            wp_send_json_error(array('message' => __('Application submission failed. You may have already applied for this event.', 'semigapp')));
        }
    }

    /**
     * Handle application admin actions (approve/reject/waitlist)
     */
    public function handle_application_admin_action() {
        check_ajax_referer('semigapp_admin', 'nonce');

        if (!current_user_can('manage_semigapp_events')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'semigapp')));
        }

        $action = sanitize_text_field($_POST['application_action']);
        $application_id = intval($_POST['application_id']);
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        $result = false;
        $message = '';

        switch ($action) {
            case 'approve':
                $result = $this->approve_application($application_id, $notes);
                $message = __('Application approved successfully.', 'semigapp');
                break;

            case 'reject':
                $result = $this->reject_application($application_id, $reason, $notes);
                $message = __('Application rejected.', 'semigapp');
                break;

            case 'waitlist':
                $result = $this->add_to_waitlist($application_id);
                $message = __('Application added to waitlist.', 'semigapp');
                break;

            case 'cancel':
                $result = $this->cancel_application($application_id);
                $message = __('Application cancelled.', 'semigapp');
                break;

            default:
                wp_send_json_error(array('message' => __('Invalid action.', 'semigapp')));
        }

        if ($result !== false) {
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => __('Action failed. Please try again.', 'semigapp')));
        }
    }
}
