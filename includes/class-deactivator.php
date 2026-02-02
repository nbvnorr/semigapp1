<?php
/**
 * Plugin Deactivator
 *
 * @package SemigApp
 */

namespace SemigApp;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks
 */
class Deactivator {

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clear scheduled cron events
        self::clear_scheduled_events();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set deactivation flag
        update_option('semigapp_deactivated', current_time('mysql'));
    }

    /**
     * Clear scheduled cron events
     */
    private static function clear_scheduled_events() {
        $events = array(
            'semigapp_newsletter_send',
            'semigapp_check_membership_expiry',
            'semigapp_event_reminders',
            'semigapp_cleanup_logs',
        );

        foreach ($events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }
    }
}
