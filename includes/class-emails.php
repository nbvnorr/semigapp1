<?php
/**
 * Email Handler
 *
 * @package SemigApp
 */

namespace SemigApp;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Emails
 *
 * Handles email sending and templates
 */
class Emails {

    /**
     * Instance
     *
     * @var Emails
     */
    private static $instance = null;

    /**
     * Email templates
     *
     * @var array
     */
    private $templates = array();

    /**
     * Get instance
     *
     * @return Emails
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
        $this->register_templates();
        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
    }

    /**
     * Register email templates
     */
    private function register_templates() {
        $this->templates = array(
            // Membership emails
            'membership_welcome' => array(
                'subject' => __('Welcome to {site_name}!', 'semigapp'),
                'description' => __('Sent when a new member joins.', 'semigapp'),
            ),
            'membership_expiring' => array(
                'subject' => __('Your membership is expiring soon', 'semigapp'),
                'description' => __('Sent before membership expiration.', 'semigapp'),
            ),
            'membership_expired' => array(
                'subject' => __('Your membership has expired', 'semigapp'),
                'description' => __('Sent when membership expires.', 'semigapp'),
            ),
            'membership_renewed' => array(
                'subject' => __('Your membership has been renewed', 'semigapp'),
                'description' => __('Sent when membership is renewed.', 'semigapp'),
            ),
            // Order emails
            'order_confirmation' => array(
                'subject' => __('Order Confirmation - #{order_number}', 'semigapp'),
                'description' => __('Sent after successful order.', 'semigapp'),
            ),
            'order_processing' => array(
                'subject' => __('Your order is being processed - #{order_number}', 'semigapp'),
                'description' => __('Sent when order is being processed.', 'semigapp'),
            ),
            'order_shipped' => array(
                'subject' => __('Your order has been shipped - #{order_number}', 'semigapp'),
                'description' => __('Sent when order is shipped.', 'semigapp'),
            ),
            'order_completed' => array(
                'subject' => __('Your order is complete - #{order_number}', 'semigapp'),
                'description' => __('Sent when order is completed.', 'semigapp'),
            ),
            'order_refunded' => array(
                'subject' => __('Your order has been refunded - #{order_number}', 'semigapp'),
                'description' => __('Sent when order is refunded.', 'semigapp'),
            ),
            // Event emails
            'event_registration' => array(
                'subject' => __('Registration Confirmation - {event_title}', 'semigapp'),
                'description' => __('Sent after event registration.', 'semigapp'),
            ),
            'event_reminder' => array(
                'subject' => __('Reminder: {event_title} is coming up!', 'semigapp'),
                'description' => __('Sent before the event.', 'semigapp'),
            ),
            'event_cancelled' => array(
                'subject' => __('Event Cancelled - {event_title}', 'semigapp'),
                'description' => __('Sent when event is cancelled.', 'semigapp'),
            ),
            // Newsletter emails
            'newsletter_confirm' => array(
                'subject' => __('Please confirm your subscription', 'semigapp'),
                'description' => __('Sent for double opt-in confirmation.', 'semigapp'),
            ),
            'newsletter_welcome' => array(
                'subject' => __('Welcome to our newsletter!', 'semigapp'),
                'description' => __('Sent after subscription is confirmed.', 'semigapp'),
            ),
            // Task emails
            'task_assigned' => array(
                'subject' => __('New task assigned: {task_title}', 'semigapp'),
                'description' => __('Sent when a task is assigned.', 'semigapp'),
            ),
            'task_due' => array(
                'subject' => __('Task due soon: {task_title}', 'semigapp'),
                'description' => __('Sent before task due date.', 'semigapp'),
            ),
            'task_completed' => array(
                'subject' => __('Task completed: {task_title}', 'semigapp'),
                'description' => __('Sent when a task is completed.', 'semigapp'),
            ),
            // Payment emails
            'payment_received' => array(
                'subject' => __('Payment Received - Thank you!', 'semigapp'),
                'description' => __('Sent when payment is received.', 'semigapp'),
            ),
            'payment_failed' => array(
                'subject' => __('Payment Failed', 'semigapp'),
                'description' => __('Sent when payment fails.', 'semigapp'),
            ),
        );
    }

    /**
     * Set HTML content type
     *
     * @return string Content type.
     */
    public function set_html_content_type() {
        return 'text/html';
    }

    /**
     * Send an email
     *
     * @param string $to       Recipient email.
     * @param string $template Template name.
     * @param array  $data     Email data.
     * @return bool True on success, false on failure.
     */
    public function send($to, $template, $data = array()) {
        $settings = Settings::get_instance();

        // Get template content
        $template_content = $this->get_template_content($template, $data);

        if (!$template_content) {
            return false;
        }

        // Parse subject
        $subject = $this->parse_placeholders($template_content['subject'], $data);

        // Build email body
        $body = $this->build_email($template_content['body'], $data);

        // Headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $settings->get('newsletter.sender_name', get_bloginfo('name')) .
                ' <' . $settings->get('newsletter.sender_email', get_option('admin_email')) . '>',
        );

        // Send
        $result = wp_mail($to, $subject, $body, $headers);

        // Log email
        $this->log_email($to, $template, $subject, $result);

        return $result;
    }

    /**
     * Get template content
     *
     * @param string $template Template name.
     * @param array  $data     Email data.
     * @return array|false Template content or false.
     */
    private function get_template_content($template, $data = array()) {
        if (!isset($this->templates[$template])) {
            return false;
        }

        $subject = $this->templates[$template]['subject'];

        // Check for custom template file
        $template_file = SEMIGAPP_PLUGIN_DIR . 'templates/emails/' . $template . '.php';

        if (file_exists($template_file)) {
            ob_start();
            // Pass data as $email_data to avoid extract() security issues
            $email_data = $data;
            include $template_file;
            $body = ob_get_clean();
        } else {
            // Use default body
            $body = $this->get_default_body($template, $data);
        }

        return array(
            'subject' => $subject,
            'body' => $body,
        );
    }

    /**
     * Get default email body
     *
     * @param string $template Template name.
     * @param array  $data     Email data.
     * @return string Email body.
     */
    private function get_default_body($template, $data) {
        switch ($template) {
            case 'membership_welcome':
                return sprintf(
                    __('Hello %s,<br><br>Welcome to %s! Your membership is now active.<br><br>Best regards,<br>%s', 'semigapp'),
                    isset($data['first_name']) ? $data['first_name'] : '',
                    get_bloginfo('name'),
                    get_bloginfo('name')
                );

            case 'order_confirmation':
                return sprintf(
                    __('Thank you for your order!<br><br>Your order #%s has been received and is being processed.<br><br>Order Total: %s<br><br>Best regards,<br>%s', 'semigapp'),
                    isset($data['order_number']) ? $data['order_number'] : '',
                    isset($data['order_total']) ? $data['order_total'] : '',
                    get_bloginfo('name')
                );

            case 'event_registration':
                return sprintf(
                    __('Your registration for %s has been confirmed!<br><br>Event Date: %s<br>Location: %s<br><br>Best regards,<br>%s', 'semigapp'),
                    isset($data['event_title']) ? $data['event_title'] : '',
                    isset($data['event_date']) ? $data['event_date'] : '',
                    isset($data['event_location']) ? $data['event_location'] : '',
                    get_bloginfo('name')
                );

            case 'newsletter_confirm':
                return sprintf(
                    __('Please confirm your subscription by clicking the link below:<br><br><a href="%s">Confirm Subscription</a><br><br>If you did not subscribe, you can ignore this email.<br><br>Best regards,<br>%s', 'semigapp'),
                    isset($data['confirmation_url']) ? $data['confirmation_url'] : '',
                    get_bloginfo('name')
                );

            case 'task_assigned':
                return sprintf(
                    __('A new task has been assigned to you:<br><br>Task: %s<br>Project: %s<br>Due Date: %s<br><br><a href="%s">View Task</a><br><br>Best regards,<br>%s', 'semigapp'),
                    isset($data['task_title']) ? $data['task_title'] : '',
                    isset($data['project_name']) ? $data['project_name'] : '',
                    isset($data['due_date']) ? $data['due_date'] : __('Not set', 'semigapp'),
                    isset($data['task_url']) ? $data['task_url'] : '',
                    get_bloginfo('name')
                );

            default:
                return isset($data['message']) ? $data['message'] : '';
        }
    }

    /**
     * Build email with wrapper
     *
     * @param string $content Email content.
     * @param array  $data    Email data.
     * @return string Complete email HTML.
     */
    private function build_email($content, $data) {
        $settings = Settings::get_instance();

        $header_color = $settings->get('emails.header_color', '#2563eb');
        $logo = $settings->get('emails.header_logo', '');
        $footer_text = $settings->get('emails.footer_text', '');

        // Parse placeholders in content
        $content = $this->parse_placeholders($content, $data);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html(get_bloginfo('name')); ?></title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                }
                .email-wrapper {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                }
                .email-header {
                    background-color: <?php echo esc_attr($header_color); ?>;
                    padding: 30px;
                    text-align: center;
                }
                .email-header img {
                    max-width: 200px;
                    height: auto;
                }
                .email-header h1 {
                    color: #ffffff;
                    margin: 0;
                    font-size: 24px;
                }
                .email-body {
                    padding: 40px 30px;
                }
                .email-footer {
                    background-color: #f8f9fa;
                    padding: 20px 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                }
                a {
                    color: <?php echo esc_attr($header_color); ?>;
                }
                .button {
                    display: inline-block;
                    background-color: <?php echo esc_attr($header_color); ?>;
                    color: #ffffff !important;
                    padding: 12px 24px;
                    text-decoration: none;
                    border-radius: 4px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="email-header">
                    <?php if ($logo) : ?>
                        <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    <?php else : ?>
                        <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
                    <?php endif; ?>
                </div>
                <div class="email-body">
                    <?php echo wp_kses_post($content); ?>
                </div>
                <div class="email-footer">
                    <?php if ($footer_text) : ?>
                        <p><?php echo wp_kses_post($footer_text); ?></p>
                    <?php endif; ?>
                    <p>&copy; <?php echo esc_html(date('Y') . ' ' . get_bloginfo('name')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Parse placeholders in text
     *
     * @param string $text Text with placeholders.
     * @param array  $data Data for placeholders.
     * @return string Parsed text.
     */
    private function parse_placeholders($text, $data) {
        // Default placeholders
        $defaults = array(
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'admin_email' => get_option('admin_email'),
            'date' => current_time(get_option('date_format')),
        );

        $data = wp_parse_args($data, $defaults);

        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $text = str_replace('{' . $key . '}', $value, $text);
            }
        }

        return $text;
    }

    /**
     * Log email
     *
     * @param string $to       Recipient.
     * @param string $template Template.
     * @param string $subject  Subject.
     * @param bool   $success  Whether email was sent.
     */
    private function log_email($to, $template, $subject, $success) {
        $log = array(
            'to' => $to,
            'template' => $template,
            'subject' => $subject,
            'success' => $success,
            'date' => current_time('mysql'),
        );

        $logs = get_option('semigapp_email_logs', array());
        array_unshift($logs, $log);

        // Keep only last 100 logs
        $logs = array_slice($logs, 0, 100);

        update_option('semigapp_email_logs', $logs);
    }

    /**
     * Get all templates
     *
     * @return array Templates.
     */
    public function get_templates() {
        return $this->templates;
    }

    /**
     * Send test email
     *
     * @param string $to       Recipient email.
     * @param string $template Template name.
     * @return bool True on success, false on failure.
     */
    public function send_test($to, $template) {
        $test_data = array(
            'first_name' => 'Test',
            'last_name' => 'User',
            'order_number' => 'TEST-001',
            'order_total' => '999,00 kr',
            'event_title' => 'Test Event',
            'event_date' => date('Y-m-d H:i'),
            'event_location' => 'Test Location',
            'task_title' => 'Test Task',
            'project_name' => 'Test Project',
            'due_date' => date('Y-m-d'),
            'task_url' => home_url(),
            'confirmation_url' => home_url('/confirm/'),
        );

        return $this->send($to, $template, $test_data);
    }
}
