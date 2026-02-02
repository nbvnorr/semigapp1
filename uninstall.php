<?php
/**
 * Uninstall SemigApp
 *
 * This file is executed when the plugin is uninstalled via WordPress admin.
 * It removes all plugin data from the database.
 *
 * @package SemigApp
 */

// Exit if accessed directly or not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if we should remove data
$remove_data = get_option('semigapp_remove_data_on_uninstall', false);

if (!$remove_data) {
    return;
}

global $wpdb;

// Remove all custom tables
$tables = array(
    'semigapp_projects',
    'semigapp_tasks',
    'semigapp_task_comments',
    'semigapp_events',
    'semigapp_event_registrations',
    'semigapp_members',
    'semigapp_membership_levels',
    'semigapp_membership_payments',
    'semigapp_products',
    'semigapp_orders',
    'semigapp_order_items',
    'semigapp_subscribers',
    'semigapp_subscriber_lists',
    'semigapp_subscriber_list_relations',
    'semigapp_campaigns',
    'semigapp_campaign_tracking',
    'semigapp_activity_log',
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}

// Remove all options
$options = array(
    'semigapp_version',
    'semigapp_db_version',
    'semigapp_activated',
    'semigapp_deactivated',
    'semigapp_activation_time',
    'semigapp_pages',
    'semigapp_last_order_number',
    'semigapp_email_logs',
    'semigapp_general_settings',
    'semigapp_projects_settings',
    'semigapp_events_settings',
    'semigapp_membership_settings',
    'semigapp_webshop_settings',
    'semigapp_payments_settings',
    'semigapp_newsletter_settings',
    'semigapp_emails_settings',
    'semigapp_remove_data_on_uninstall',
);

foreach ($options as $option) {
    delete_option($option);
}

// Remove all post meta
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_semigapp_%'");

// Remove all user meta
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_semigapp_%'");
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'semigapp_%'");

// Remove custom roles
remove_role('semigapp_manager');
remove_role('semigapp_member');

// Remove capabilities from administrator
$admin_role = get_role('administrator');
if ($admin_role) {
    $capabilities = array(
        'manage_semigapp_projects',
        'edit_semigapp_projects',
        'delete_semigapp_projects',
        'manage_semigapp_events',
        'edit_semigapp_events',
        'delete_semigapp_events',
        'manage_semigapp_members',
        'edit_semigapp_members',
        'delete_semigapp_members',
        'manage_semigapp_shop',
        'edit_semigapp_products',
        'delete_semigapp_products',
        'manage_semigapp_orders',
        'manage_semigapp_newsletter',
        'send_semigapp_campaigns',
        'manage_semigapp_settings',
    );

    foreach ($capabilities as $cap) {
        $admin_role->remove_cap($cap);
    }
}

// Clear any scheduled cron events
$cron_events = array(
    'semigapp_newsletter_send',
    'semigapp_check_membership_expiry',
    'semigapp_event_reminders',
    'semigapp_cleanup_logs',
);

foreach ($cron_events as $event) {
    $timestamp = wp_next_scheduled($event);
    if ($timestamp) {
        wp_unschedule_event($timestamp, $event);
    }
}

// Delete created pages
$pages = get_option('semigapp_pages', array());
foreach ($pages as $page_id) {
    if ($page_id) {
        wp_delete_post($page_id, true);
    }
}

// Clear any transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_semigapp_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_semigapp_%'");

// Flush rewrite rules
flush_rewrite_rules();
