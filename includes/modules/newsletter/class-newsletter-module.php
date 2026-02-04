<?php
/**
 * Newsletter Module
 *
 * @package SemigApp
 */

namespace SemigApp\Modules\Newsletter;

use SemigApp\Modules\Base_Module;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Newsletter_Module
 *
 * Handles newsletter subscriptions and email campaigns
 */
class Newsletter_Module extends Base_Module {

    /**
     * Module ID
     *
     * @var string
     */
    protected $id = 'newsletter';

    /**
     * Module name
     *
     * @var string
     */
    protected $name = 'Newsletter Management';

    /**
     * Subscriber statuses
     *
     * @var array
     */
    private $statuses = array();

    /**
     * Campaign statuses
     *
     * @var array
     */
    private $campaign_statuses = array();

    /**
     * Initialize the module
     */
    public function init() {
        $this->setup_statuses();

        // Register hooks
        add_action('semigapp_register_shortcodes', array($this, 'register_shortcodes'));
        add_action('wp_ajax_semigapp_newsletter_subscribe', array($this, 'handle_subscribe'));
        add_action('wp_ajax_nopriv_semigapp_newsletter_subscribe', array($this, 'handle_subscribe'));
        add_action('wp_ajax_semigapp_newsletter_unsubscribe', array($this, 'handle_unsubscribe'));
        add_action('wp_ajax_nopriv_semigapp_newsletter_unsubscribe', array($this, 'handle_unsubscribe'));

        // Scheduled events
        add_action('semigapp_newsletter_send', array($this, 'process_scheduled_campaigns'));

        // Track email opens and clicks
        add_action('init', array($this, 'handle_tracking'));

        // Widget
        add_action('widgets_init', array($this, 'register_widget'));
    }

    /**
     * Setup statuses
     */
    private function setup_statuses() {
        $this->statuses = array(
            'pending' => __('Pending Confirmation', 'semigapp'),
            'active' => __('Active', 'semigapp'),
            'unsubscribed' => __('Unsubscribed', 'semigapp'),
            'bounced' => __('Bounced', 'semigapp'),
        );

        $this->campaign_statuses = array(
            'draft' => __('Draft', 'semigapp'),
            'scheduled' => __('Scheduled', 'semigapp'),
            'sending' => __('Sending', 'semigapp'),
            'sent' => __('Sent', 'semigapp'),
            'paused' => __('Paused', 'semigapp'),
        );
    }

    /**
     * Get subscriber statuses
     *
     * @return array
     */
    public function get_statuses() {
        return apply_filters('semigapp_subscriber_statuses', $this->statuses);
    }

    /**
     * Get campaign statuses
     *
     * @return array
     */
    public function get_campaign_statuses() {
        return apply_filters('semigapp_campaign_statuses', $this->campaign_statuses);
    }

    // =========================================================================
    // SUBSCRIBER METHODS
    // =========================================================================

    /**
     * Subscribe email
     *
     * @param string $email Email address.
     * @param array  $data  Additional data.
     * @return int|false Subscriber ID or false.
     */
    public function subscribe($email, $data = array()) {
        $email = sanitize_email($email);

        if (!is_email($email)) {
            return false;
        }

        // Check if already subscribed
        $existing = $this->get_subscriber_by_email($email);

        if ($existing) {
            if ($existing->status === 'active') {
                return $existing->id;
            }

            // Reactivate
            if ($existing->status === 'unsubscribed') {
                return $this->resubscribe($existing->id);
            }

            return $existing->id;
        }

        $subscriber_data = array(
            'email' => $email,
            'first_name' => isset($data['first_name']) ? sanitize_text_field($data['first_name']) : '',
            'last_name' => isset($data['last_name']) ? sanitize_text_field($data['last_name']) : '',
            'user_id' => isset($data['user_id']) ? intval($data['user_id']) : (is_user_logged_in() ? get_current_user_id() : null),
            'source' => isset($data['source']) ? sanitize_text_field($data['source']) : 'website',
            'ip_address' => $this->get_client_ip(),
            'metadata' => isset($data['metadata']) ? wp_json_encode($data['metadata']) : null,
        );

        // Determine status based on double opt-in setting
        $double_optin = $this->get_setting('double_optin', true);

        if ($double_optin) {
            $subscriber_data['status'] = 'pending';
            $subscriber_data['confirmation_token'] = $this->generate_token();
        } else {
            $subscriber_data['status'] = 'active';
            $subscriber_data['confirmed_at'] = current_time('mysql');
        }

        $subscriber_id = $this->db->insert('subscribers', $subscriber_data);

        if ($subscriber_id) {
            // Add to default list if specified
            if (isset($data['list_id'])) {
                $this->add_to_list($subscriber_id, intval($data['list_id']));
            }

            // Send confirmation email if double opt-in
            if ($double_optin) {
                $this->send_confirmation_email($subscriber_id);
            } else {
                $this->send_notification('newsletter_welcome', $email, array(
                    'first_name' => $subscriber_data['first_name'],
                ));
            }

            $this->log_activity('subscriber', $subscriber_id, 'subscribed', 'New subscription');
            do_action('semigapp_subscriber_created', $subscriber_id);
        }

        return $subscriber_id;
    }

    /**
     * Confirm subscription
     *
     * @param string $token Confirmation token.
     * @return bool True on success.
     */
    public function confirm($token) {
        $subscriber = $this->db->get_row('subscribers', array('confirmation_token' => $token));

        if (!$subscriber) {
            return false;
        }

        if ($subscriber->status === 'active') {
            return true;
        }

        $result = $this->db->update('subscribers', array(
            'status' => 'active',
            'confirmed_at' => current_time('mysql'),
            'confirmation_token' => null,
        ), array('id' => $subscriber->id));

        if ($result !== false) {
            $this->send_notification('newsletter_welcome', $subscriber->email, array(
                'first_name' => $subscriber->first_name,
            ));

            $this->log_activity('subscriber', $subscriber->id, 'confirmed', 'Subscription confirmed');
            do_action('semigapp_subscriber_confirmed', $subscriber->id);
        }

        return $result !== false;
    }

    /**
     * Unsubscribe
     *
     * @param int    $subscriber_id Subscriber ID.
     * @param string $reason        Unsubscribe reason.
     * @return bool True on success.
     */
    public function unsubscribe($subscriber_id, $reason = '') {
        $subscriber = $this->get_subscriber($subscriber_id);

        if (!$subscriber) {
            return false;
        }

        $result = $this->db->update('subscribers', array(
            'status' => 'unsubscribed',
            'unsubscribed_at' => current_time('mysql'),
        ), array('id' => $subscriber_id));

        if ($result !== false) {
            $this->log_activity('subscriber', $subscriber_id, 'unsubscribed', $reason ?: 'Unsubscribed');
            do_action('semigapp_subscriber_unsubscribed', $subscriber_id);
        }

        return $result !== false;
    }

    /**
     * Unsubscribe by email
     *
     * @param string $email Email address.
     * @return bool True on success.
     */
    public function unsubscribe_by_email($email) {
        $subscriber = $this->get_subscriber_by_email($email);

        if (!$subscriber) {
            return false;
        }

        return $this->unsubscribe($subscriber->id);
    }

    /**
     * Resubscribe
     *
     * @param int $subscriber_id Subscriber ID.
     * @return bool|int True/ID on success, false on failure.
     */
    public function resubscribe($subscriber_id) {
        $subscriber = $this->get_subscriber($subscriber_id);

        if (!$subscriber) {
            return false;
        }

        $double_optin = $this->get_setting('double_optin', true);

        if ($double_optin) {
            $token = $this->generate_token();

            $this->db->update('subscribers', array(
                'status' => 'pending',
                'confirmation_token' => $token,
                'unsubscribed_at' => null,
            ), array('id' => $subscriber_id));

            $this->send_confirmation_email($subscriber_id);
        } else {
            $this->db->update('subscribers', array(
                'status' => 'active',
                'confirmed_at' => current_time('mysql'),
                'unsubscribed_at' => null,
            ), array('id' => $subscriber_id));
        }

        return $subscriber_id;
    }

    /**
     * Get subscriber
     *
     * @param int $subscriber_id Subscriber ID.
     * @return object|null Subscriber or null.
     */
    public function get_subscriber($subscriber_id) {
        $subscriber = $this->db->get_row('subscribers', array('id' => $subscriber_id));

        if ($subscriber) {
            $subscriber->metadata = json_decode($subscriber->metadata, true) ?: array();
            $subscriber->lists = $this->get_subscriber_lists($subscriber_id);
        }

        return $subscriber;
    }

    /**
     * Get subscriber by email
     *
     * @param string $email Email address.
     * @return object|null Subscriber or null.
     */
    public function get_subscriber_by_email($email) {
        return $this->db->get_row('subscribers', array('email' => sanitize_email($email)));
    }

    /**
     * Get subscribers
     *
     * @param array $args Query arguments.
     * @return array Subscribers.
     */
    public function get_subscribers($args = array()) {
        $defaults = array(
            'where' => array(),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
            'search' => '',
        );

        $args = wp_parse_args($args, $defaults);

        if (!empty($args['search'])) {
            $args['search_columns'] = array('email', 'first_name', 'last_name');
        }

        return $this->db->get_results('subscribers', $args);
    }

    /**
     * Count subscribers
     *
     * @param string $status Status filter.
     * @return int Count.
     */
    public function count_subscribers($status = '') {
        $where = array();

        if ($status) {
            $where['status'] = $status;
        }

        return $this->db->count('subscribers', $where);
    }

    /**
     * Update subscriber
     *
     * @param int   $subscriber_id Subscriber ID.
     * @param array $data          Data to update.
     * @return bool True on success.
     */
    public function update_subscriber($subscriber_id, $data) {
        $schema = array(
            'first_name' => 'text',
            'last_name' => 'text',
            'status' => 'text',
            'metadata' => 'json',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        return $this->db->update('subscribers', $sanitized, array('id' => $subscriber_id)) !== false;
    }

    /**
     * Delete subscriber
     *
     * @param int $subscriber_id Subscriber ID.
     * @return bool True on success.
     */
    public function delete_subscriber($subscriber_id) {
        // Remove from all lists
        $this->db->delete('subscriber_list_relations', array('subscriber_id' => $subscriber_id));

        // Delete tracking data
        $this->db->delete('campaign_tracking', array('subscriber_id' => $subscriber_id));

        return $this->db->delete('subscribers', array('id' => $subscriber_id)) !== false;
    }

    /**
     * Send confirmation email
     *
     * @param int $subscriber_id Subscriber ID.
     */
    private function send_confirmation_email($subscriber_id) {
        $subscriber = $this->get_subscriber($subscriber_id);

        if (!$subscriber || !$subscriber->confirmation_token) {
            return;
        }

        $confirmation_url = add_query_arg(array(
            'semigapp_action' => 'confirm_subscription',
            'token' => $subscriber->confirmation_token,
        ), home_url());

        $this->send_notification('newsletter_confirm', $subscriber->email, array(
            'first_name' => $subscriber->first_name,
            'confirmation_url' => $confirmation_url,
        ));
    }

    /**
     * Generate secure token
     *
     * @return string Token.
     */
    private function generate_token() {
        return wp_generate_password(32, false);
    }

    // =========================================================================
    // LIST METHODS
    // =========================================================================

    /**
     * Create list
     *
     * @param array $data List data.
     * @return int|false List ID or false.
     */
    public function create_list($data) {
        $schema = array(
            'name' => 'text',
            'slug' => 'text',
            'description' => 'textarea',
            'status' => 'text',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        if (empty($sanitized['name'])) {
            return false;
        }

        if (empty($sanitized['slug'])) {
            $sanitized['slug'] = sanitize_title($sanitized['name']);
        }

        $sanitized['status'] = $sanitized['status'] ?? 'active';

        return $this->db->insert('subscriber_lists', $sanitized);
    }

    /**
     * Get list
     *
     * @param int $list_id List ID.
     * @return object|null List or null.
     */
    public function get_list($list_id) {
        $list = $this->db->get_row('subscriber_lists', array('id' => $list_id));

        if ($list) {
            $list->subscriber_count = $this->db->count('subscriber_list_relations', array('list_id' => $list_id));
        }

        return $list;
    }

    /**
     * Get all lists
     *
     * @return array Lists.
     */
    public function get_lists() {
        $lists = $this->db->get_results('subscriber_lists', array(
            'orderby' => 'name',
            'order' => 'ASC',
        ));

        foreach ($lists as &$list) {
            $list->subscriber_count = $this->db->count('subscriber_list_relations', array('list_id' => $list->id));
        }

        return $lists;
    }

    /**
     * Add subscriber to list
     *
     * @param int $subscriber_id Subscriber ID.
     * @param int $list_id       List ID.
     * @return bool True on success.
     */
    public function add_to_list($subscriber_id, $list_id) {
        // Check if already in list
        $existing = $this->db->get_row('subscriber_list_relations', array(
            'subscriber_id' => $subscriber_id,
            'list_id' => $list_id,
        ));

        if ($existing) {
            return true;
        }

        return $this->db->insert('subscriber_list_relations', array(
            'subscriber_id' => $subscriber_id,
            'list_id' => $list_id,
        )) !== false;
    }

    /**
     * Remove subscriber from list
     *
     * @param int $subscriber_id Subscriber ID.
     * @param int $list_id       List ID.
     * @return bool True on success.
     */
    public function remove_from_list($subscriber_id, $list_id) {
        return $this->db->delete('subscriber_list_relations', array(
            'subscriber_id' => $subscriber_id,
            'list_id' => $list_id,
        )) !== false;
    }

    /**
     * Get subscriber's lists
     *
     * @param int $subscriber_id Subscriber ID.
     * @return array Lists.
     */
    public function get_subscriber_lists($subscriber_id) {
        global $wpdb;

        $relations_table = $this->db->get_table('subscriber_list_relations');
        $lists_table = $this->db->get_table('subscriber_lists');

        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.* FROM $lists_table l
            INNER JOIN $relations_table r ON l.id = r.list_id
            WHERE r.subscriber_id = %d",
            $subscriber_id
        ));
    }

    /**
     * Get list subscribers
     *
     * @param int   $list_id List ID.
     * @param array $args    Query arguments.
     * @return array Subscribers.
     */
    public function get_list_subscribers($list_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => 'active',
            'limit' => 0,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $relations_table = $this->db->get_table('subscriber_list_relations');
        $subscribers_table = $this->db->get_table('subscribers');

        $sql = $wpdb->prepare(
            "SELECT s.* FROM $subscribers_table s
            INNER JOIN $relations_table r ON s.id = r.subscriber_id
            WHERE r.list_id = %d AND s.status = %s
            ORDER BY s.created_at DESC",
            $list_id,
            $args['status']
        );

        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }

        return $wpdb->get_results($sql);
    }

    // =========================================================================
    // CAMPAIGN METHODS
    // =========================================================================

    /**
     * Create campaign
     *
     * @param array $data Campaign data.
     * @return int|false Campaign ID or false.
     */
    public function create_campaign($data) {
        $schema = array(
            'name' => 'text',
            'subject' => 'text',
            'content' => 'html',
            'template' => 'text',
            'list_ids' => 'json',
            'scheduled_at' => 'datetime',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        if (empty($sanitized['name']) || empty($sanitized['subject']) || empty($sanitized['content'])) {
            return false;
        }

        $sanitized['status'] = 'draft';
        $sanitized['created_by'] = get_current_user_id();

        $campaign_id = $this->db->insert('campaigns', $sanitized);

        if ($campaign_id) {
            $this->log_activity('campaign', $campaign_id, 'created', 'Campaign created');
            do_action('semigapp_campaign_created', $campaign_id);
        }

        return $campaign_id;
    }

    /**
     * Update campaign
     *
     * @param int   $campaign_id Campaign ID.
     * @param array $data        Campaign data.
     * @return bool True on success.
     */
    public function update_campaign($campaign_id, $data) {
        $campaign = $this->get_campaign($campaign_id);

        if (!$campaign) {
            return false;
        }

        // Don't allow editing sent campaigns
        if ($campaign->status === 'sent') {
            return false;
        }

        $schema = array(
            'name' => 'text',
            'subject' => 'text',
            'content' => 'html',
            'template' => 'text',
            'list_ids' => 'json',
            'status' => 'text',
            'scheduled_at' => 'datetime',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        return $this->db->update('campaigns', $sanitized, array('id' => $campaign_id)) !== false;
    }

    /**
     * Delete campaign
     *
     * @param int $campaign_id Campaign ID.
     * @return bool True on success.
     */
    public function delete_campaign($campaign_id) {
        // Delete tracking data
        $this->db->delete('campaign_tracking', array('campaign_id' => $campaign_id));

        return $this->db->delete('campaigns', array('id' => $campaign_id)) !== false;
    }

    /**
     * Get campaign
     *
     * @param int $campaign_id Campaign ID.
     * @return object|null Campaign or null.
     */
    public function get_campaign($campaign_id) {
        $campaign = $this->db->get_row('campaigns', array('id' => $campaign_id));

        if ($campaign) {
            $campaign->list_ids = json_decode($campaign->list_ids, true) ?: array();
            $campaign->open_rate = $campaign->sent_count > 0 ? round(($campaign->open_count / $campaign->sent_count) * 100, 1) : 0;
            $campaign->click_rate = $campaign->sent_count > 0 ? round(($campaign->click_count / $campaign->sent_count) * 100, 1) : 0;
        }

        return $campaign;
    }

    /**
     * Get campaigns
     *
     * @param array $args Query arguments.
     * @return array Campaigns.
     */
    public function get_campaigns($args = array()) {
        $defaults = array(
            'where' => array(),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $campaigns = $this->db->get_results('campaigns', $args);

        foreach ($campaigns as &$campaign) {
            $campaign->list_ids = json_decode($campaign->list_ids, true) ?: array();
        }

        return $campaigns;
    }

    /**
     * Schedule campaign
     *
     * @param int    $campaign_id   Campaign ID.
     * @param string $scheduled_at  Scheduled datetime.
     * @return bool True on success.
     */
    public function schedule_campaign($campaign_id, $scheduled_at) {
        return $this->update_campaign($campaign_id, array(
            'status' => 'scheduled',
            'scheduled_at' => $scheduled_at,
        ));
    }

    /**
     * Send campaign
     *
     * @param int $campaign_id Campaign ID.
     * @return bool True on success.
     */
    public function send_campaign($campaign_id) {
        $campaign = $this->get_campaign($campaign_id);

        if (!$campaign || $campaign->status === 'sent') {
            return false;
        }

        // Update status to sending
        $this->update_campaign($campaign_id, array('status' => 'sending'));

        // Get recipients
        $recipients = $this->get_campaign_recipients($campaign);

        $sent_count = 0;

        foreach ($recipients as $subscriber) {
            $result = $this->send_campaign_email($campaign, $subscriber);

            if ($result) {
                $sent_count++;

                // Track send
                $this->track_event($campaign_id, $subscriber->id, 'sent');
            }
        }

        // Update campaign
        $this->db->update('campaigns', array(
            'status' => 'sent',
            'sent_count' => $sent_count,
            'sent_at' => current_time('mysql'),
        ), array('id' => $campaign_id));

        $this->log_activity('campaign', $campaign_id, 'sent', "Sent to {$sent_count} subscribers");
        do_action('semigapp_campaign_sent', $campaign_id, $sent_count);

        return true;
    }

    /**
     * Get campaign recipients
     *
     * @param object $campaign Campaign object.
     * @return array Recipients.
     */
    private function get_campaign_recipients($campaign) {
        if (empty($campaign->list_ids)) {
            // Send to all active subscribers
            return $this->get_subscribers(array(
                'where' => array('status' => 'active'),
                'limit' => 0,
            ));
        }

        global $wpdb;

        $relations_table = $this->db->get_table('subscriber_list_relations');
        $subscribers_table = $this->db->get_table('subscribers');

        // Use prepared statement with proper placeholders
        $list_ids = array_map('intval', $campaign->list_ids);
        $placeholders = implode(',', array_fill(0, count($list_ids), '%d'));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT s.* FROM $subscribers_table s
            INNER JOIN $relations_table r ON s.id = r.subscriber_id
            WHERE r.list_id IN ($placeholders) AND s.status = 'active'",
            $list_ids
        ));
    }

    /**
     * Send campaign email to subscriber
     *
     * @param object $campaign   Campaign object.
     * @param object $subscriber Subscriber object.
     * @return bool True on success.
     */
    private function send_campaign_email($campaign, $subscriber) {
        $content = $this->personalize_content($campaign->content, $subscriber);
        $subject = $this->personalize_content($campaign->subject, $subscriber);

        // Add tracking pixel
        $tracking_pixel = $this->get_tracking_pixel($campaign->id, $subscriber->id);
        $content .= $tracking_pixel;

        // Replace links with tracked links
        $content = $this->add_link_tracking($content, $campaign->id, $subscriber->id);

        // Add unsubscribe link
        $unsubscribe_url = $this->get_unsubscribe_url($subscriber);
        $content .= '<p style="font-size: 12px; color: #666;"><a href="' . esc_url($unsubscribe_url) . '">' . __('Unsubscribe', 'semigapp') . '</a></p>';

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_setting('sender_name', get_bloginfo('name')) . ' <' . $this->get_setting('sender_email', get_option('admin_email')) . '>',
            'List-Unsubscribe: <' . $unsubscribe_url . '>',
        );

        return wp_mail($subscriber->email, $subject, $content, $headers);
    }

    /**
     * Personalize content
     *
     * @param string $content    Content.
     * @param object $subscriber Subscriber.
     * @return string Personalized content.
     */
    private function personalize_content($content, $subscriber) {
        $placeholders = array(
            '{email}' => $subscriber->email,
            '{first_name}' => $subscriber->first_name ?: __('Subscriber', 'semigapp'),
            '{last_name}' => $subscriber->last_name,
            '{full_name}' => trim($subscriber->first_name . ' ' . $subscriber->last_name) ?: __('Subscriber', 'semigapp'),
        );

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    /**
     * Get tracking pixel HTML
     *
     * @param int $campaign_id   Campaign ID.
     * @param int $subscriber_id Subscriber ID.
     * @return string Tracking pixel HTML.
     */
    private function get_tracking_pixel($campaign_id, $subscriber_id) {
        $url = add_query_arg(array(
            'semigapp_action' => 'track_open',
            'c' => $campaign_id,
            's' => $subscriber_id,
            't' => wp_create_nonce('semigapp_track'),
        ), home_url());

        return '<img src="' . esc_url($url) . '" width="1" height="1" style="display:none;" />';
    }

    /**
     * Add link tracking to content
     *
     * @param string $content       Content.
     * @param int    $campaign_id   Campaign ID.
     * @param int    $subscriber_id Subscriber ID.
     * @return string Modified content.
     */
    private function add_link_tracking($content, $campaign_id, $subscriber_id) {
        return preg_replace_callback(
            '/<a\s+([^>]*?)href=["\']([^"\']+)["\']([^>]*)>/i',
            function($matches) use ($campaign_id, $subscriber_id) {
                $url = $matches[2];

                // Don't track unsubscribe links
                if (strpos($url, 'unsubscribe') !== false) {
                    return $matches[0];
                }

                $tracked_url = add_query_arg(array(
                    'semigapp_action' => 'track_click',
                    'c' => $campaign_id,
                    's' => $subscriber_id,
                    'url' => urlencode($url),
                    't' => wp_create_nonce('semigapp_track'),
                ), home_url());

                return '<a ' . $matches[1] . 'href="' . esc_url($tracked_url) . '"' . $matches[3] . '>';
            },
            $content
        );
    }

    /**
     * Get unsubscribe URL
     *
     * @param object $subscriber Subscriber.
     * @return string URL.
     */
    private function get_unsubscribe_url($subscriber) {
        // Use HMAC with site secret for secure token generation
        $token = hash_hmac('sha256', $subscriber->email . '|' . $subscriber->id, wp_salt('auth'));

        return add_query_arg(array(
            'semigapp_action' => 'unsubscribe',
            'email' => urlencode($subscriber->email),
            'token' => $token,
        ), home_url());
    }

    // =========================================================================
    // TRACKING
    // =========================================================================

    /**
     * Handle tracking requests
     */
    public function handle_tracking() {
        if (!isset($_GET['semigapp_action'])) {
            return;
        }

        $action = sanitize_text_field($_GET['semigapp_action']);

        switch ($action) {
            case 'track_open':
                $this->handle_track_open();
                break;

            case 'track_click':
                $this->handle_track_click();
                break;

            case 'confirm_subscription':
                $this->handle_confirm_subscription();
                break;

            case 'unsubscribe':
                $this->handle_unsubscribe_link();
                break;
        }
    }

    /**
     * Handle open tracking
     */
    private function handle_track_open() {
        $campaign_id = isset($_GET['c']) ? intval($_GET['c']) : 0;
        $subscriber_id = isset($_GET['s']) ? intval($_GET['s']) : 0;

        if ($campaign_id && $subscriber_id) {
            $this->track_event($campaign_id, $subscriber_id, 'open');

            // Update campaign open count
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "UPDATE " . $this->db->get_table('campaigns') . " SET open_count = open_count + 1 WHERE id = %d",
                $campaign_id
            ));
        }

        // Return a 1x1 transparent GIF
        header('Content-Type: image/gif');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }

    /**
     * Handle click tracking
     */
    private function handle_track_click() {
        $campaign_id = isset($_GET['c']) ? intval($_GET['c']) : 0;
        $subscriber_id = isset($_GET['s']) ? intval($_GET['s']) : 0;
        $url = isset($_GET['url']) ? urldecode($_GET['url']) : '';

        if ($campaign_id && $subscriber_id && $url) {
            $this->track_event($campaign_id, $subscriber_id, 'click', $url);

            // Update campaign click count
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "UPDATE " . $this->db->get_table('campaigns') . " SET click_count = click_count + 1 WHERE id = %d",
                $campaign_id
            ));
        }

        // Redirect to target URL
        wp_redirect(esc_url_raw($url));
        exit;
    }

    /**
     * Handle confirm subscription
     */
    private function handle_confirm_subscription() {
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if ($this->confirm($token)) {
            wp_redirect(add_query_arg('confirmed', '1', home_url()));
        } else {
            wp_redirect(add_query_arg('error', 'invalid_token', home_url()));
        }
        exit;
    }

    /**
     * Handle unsubscribe link
     */
    private function handle_unsubscribe_link() {
        $email = isset($_GET['email']) ? sanitize_email(urldecode($_GET['email'])) : '';
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        $subscriber = $this->get_subscriber_by_email($email);

        if ($subscriber) {
            // Use HMAC for secure token verification (matches get_unsubscribe_url)
            $expected_token = hash_hmac('sha256', $subscriber->email . '|' . $subscriber->id, wp_salt('auth'));

            if (hash_equals($expected_token, $token)) {
                $this->unsubscribe($subscriber->id, 'Clicked unsubscribe link');
                wp_redirect(add_query_arg('unsubscribed', '1', $this->settings->get_page_url('newsletter_unsubscribe')));
                exit;
            }
        }

        wp_redirect(add_query_arg('error', 'invalid_request', home_url()));
        exit;
    }

    /**
     * Track event
     *
     * @param int    $campaign_id   Campaign ID.
     * @param int    $subscriber_id Subscriber ID.
     * @param string $event_type    Event type.
     * @param string $link_url      Link URL (for clicks).
     */
    private function track_event($campaign_id, $subscriber_id, $event_type, $link_url = '') {
        $this->db->insert('campaign_tracking', array(
            'campaign_id' => $campaign_id,
            'subscriber_id' => $subscriber_id,
            'event_type' => $event_type,
            'link_url' => $link_url,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
        ));
    }

    /**
     * Process scheduled campaigns
     */
    public function process_scheduled_campaigns() {
        $campaigns = $this->get_campaigns(array(
            'where' => array('status' => 'scheduled'),
        ));

        foreach ($campaigns as $campaign) {
            if ($campaign->scheduled_at && $campaign->scheduled_at <= current_time('mysql')) {
                $this->send_campaign($campaign->id);
            }
        }
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('semigapp_newsletter_form', array($this, 'form_shortcode'));
        add_shortcode('semigapp_unsubscribe', array($this, 'unsubscribe_shortcode'));
    }

    /**
     * Newsletter form shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'list_id' => '',
            'show_name' => 'yes',
            'button_text' => __('Subscribe', 'semigapp'),
            'success_message' => __('Thank you for subscribing!', 'semigapp'),
        ), $atts);

        $gdpr_text = $this->get_setting('gdpr_consent_text', '');

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/newsletter/form.php';
        return ob_get_clean();
    }

    /**
     * Unsubscribe shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function unsubscribe_shortcode($atts) {
        $unsubscribed = isset($_GET['unsubscribed']) && $_GET['unsubscribed'] === '1';

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/newsletter/unsubscribe.php';
        return ob_get_clean();
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * Handle subscribe AJAX
     */
    public function handle_subscribe() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'semigapp')));
        }

        $data = array(
            'first_name' => isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '',
            'last_name' => isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '',
            'list_id' => isset($_POST['list_id']) ? intval($_POST['list_id']) : null,
            'source' => 'form',
        );

        $result = $this->subscribe($email, $data);

        if ($result) {
            $double_optin = $this->get_setting('double_optin', true);

            $message = $double_optin
                ? __('Please check your email to confirm your subscription.', 'semigapp')
                : __('Thank you for subscribing!', 'semigapp');

            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => __('You are already subscribed.', 'semigapp')));
        }
    }

    /**
     * Handle unsubscribe AJAX
     */
    public function handle_unsubscribe() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'semigapp')));
        }

        if ($this->unsubscribe_by_email($email)) {
            wp_send_json_success(array('message' => __('You have been unsubscribed.', 'semigapp')));
        } else {
            wp_send_json_error(array('message' => __('Email not found.', 'semigapp')));
        }
    }

    /**
     * Register widget
     */
    public function register_widget() {
        register_widget('SemigApp\Modules\Newsletter\Newsletter_Widget');
    }
}

/**
 * Newsletter Widget
 */
class Newsletter_Widget extends \WP_Widget {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'semigapp_newsletter',
            __('SemigApp Newsletter', 'semigapp'),
            array('description' => __('Newsletter subscription form', 'semigapp'))
        );
    }

    /**
     * Widget output
     *
     * @param array $args     Widget arguments.
     * @param array $instance Widget instance.
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        echo do_shortcode('[semigapp_newsletter_form]');

        echo $args['after_widget'];
    }

    /**
     * Widget form
     *
     * @param array $instance Widget instance.
     */
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : __('Newsletter', 'semigapp');
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'semigapp'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    /**
     * Save widget settings
     *
     * @param array $new_instance New instance.
     * @param array $old_instance Old instance.
     * @return array Updated instance.
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}
