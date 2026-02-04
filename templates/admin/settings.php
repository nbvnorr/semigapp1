<?php
/**
 * Admin Settings Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$settings = \SemigApp\Settings::get_instance();
?>
<div class="wrap semigapp-admin-wrap">
    <h1><?php esc_html_e('SemigApp Settings', 'semigapp'); ?></h1>

    <nav class="semigapp-settings-tabs">
        <?php foreach ($tabs as $tab_id => $tab_name) : ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-settings&tab=' . $tab_id)); ?>"
               class="<?php echo $tab === $tab_id ? 'active' : ''; ?>">
                <?php echo esc_html($tab_name); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="semigapp-settings-content">
        <form method="post" action="options.php" class="semigapp-admin-form">
            <?php settings_fields('semigapp_' . $tab . '_settings'); ?>

            <?php if ($tab === 'general') : ?>
                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Currency Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label for="currency"><?php esc_html_e('Currency', 'semigapp'); ?></label>
                        <select name="semigapp_general_settings[currency]" id="currency">
                            <option value="SEK" <?php selected($settings->get('general.currency'), 'SEK'); ?>>SEK - Swedish Krona</option>
                            <option value="EUR" <?php selected($settings->get('general.currency'), 'EUR'); ?>>EUR - Euro</option>
                            <option value="USD" <?php selected($settings->get('general.currency'), 'USD'); ?>>USD - US Dollar</option>
                            <option value="NOK" <?php selected($settings->get('general.currency'), 'NOK'); ?>>NOK - Norwegian Krone</option>
                            <option value="DKK" <?php selected($settings->get('general.currency'), 'DKK'); ?>>DKK - Danish Krone</option>
                        </select>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="currency_symbol"><?php esc_html_e('Currency Symbol', 'semigapp'); ?></label>
                        <input type="text" name="semigapp_general_settings[currency_symbol]" id="currency_symbol"
                               value="<?php echo esc_attr($settings->get('general.currency_symbol', 'kr')); ?>">
                    </div>

                    <div class="semigapp-form-row">
                        <label for="currency_position"><?php esc_html_e('Symbol Position', 'semigapp'); ?></label>
                        <select name="semigapp_general_settings[currency_position]" id="currency_position">
                            <option value="before" <?php selected($settings->get('general.currency_position'), 'before'); ?>><?php esc_html_e('Before', 'semigapp'); ?></option>
                            <option value="after" <?php selected($settings->get('general.currency_position'), 'after'); ?>><?php esc_html_e('After', 'semigapp'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Date & Time', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label for="timezone"><?php esc_html_e('Timezone', 'semigapp'); ?></label>
                        <select name="semigapp_general_settings[timezone]" id="timezone">
                            <option value="Europe/Stockholm" <?php selected($settings->get('general.timezone'), 'Europe/Stockholm'); ?>>Europe/Stockholm</option>
                            <option value="Europe/London" <?php selected($settings->get('general.timezone'), 'Europe/London'); ?>>Europe/London</option>
                            <option value="Europe/Berlin" <?php selected($settings->get('general.timezone'), 'Europe/Berlin'); ?>>Europe/Berlin</option>
                        </select>
                    </div>
                </div>

            <?php elseif ($tab === 'payments') : ?>
                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Klarna Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_payments_settings[klarna_enabled]" value="1"
                                   <?php checked($settings->get('payments.klarna_enabled')); ?>>
                            <?php esc_html_e('Enable Klarna', 'semigapp'); ?>
                        </label>
                    </div>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_payments_settings[klarna_test_mode]" value="1"
                                   <?php checked($settings->get('payments.klarna_test_mode', true)); ?>>
                            <?php esc_html_e('Test Mode', 'semigapp'); ?>
                        </label>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="klarna_api_username"><?php esc_html_e('API Username', 'semigapp'); ?></label>
                        <input type="text" name="semigapp_payments_settings[klarna_api_username]" id="klarna_api_username"
                               value="<?php echo esc_attr($settings->get('payments.klarna_api_username')); ?>">
                    </div>

                    <div class="semigapp-form-row">
                        <label for="klarna_api_password"><?php esc_html_e('API Password', 'semigapp'); ?></label>
                        <input type="password" name="semigapp_payments_settings[klarna_api_password]" id="klarna_api_password"
                               value="<?php echo esc_attr($settings->get('payments.klarna_api_password')); ?>">
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Swish Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_payments_settings[swish_enabled]" value="1"
                                   <?php checked($settings->get('payments.swish_enabled')); ?>>
                            <?php esc_html_e('Enable Swish', 'semigapp'); ?>
                        </label>
                    </div>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_payments_settings[swish_test_mode]" value="1"
                                   <?php checked($settings->get('payments.swish_test_mode', true)); ?>>
                            <?php esc_html_e('Test Mode', 'semigapp'); ?>
                        </label>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="swish_merchant_number"><?php esc_html_e('Merchant Number', 'semigapp'); ?></label>
                        <input type="text" name="semigapp_payments_settings[swish_merchant_number]" id="swish_merchant_number"
                               value="<?php echo esc_attr($settings->get('payments.swish_merchant_number')); ?>">
                        <p class="description"><?php esc_html_e('Your Swish merchant number (10 digits)', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="swish_certificate_path"><?php esc_html_e('Certificate Path', 'semigapp'); ?></label>
                        <input type="text" name="semigapp_payments_settings[swish_certificate_path]" id="swish_certificate_path"
                               value="<?php echo esc_attr($settings->get('payments.swish_certificate_path')); ?>">
                        <p class="description"><?php esc_html_e('Full path to your Swish certificate file (.pem)', 'semigapp'); ?></p>
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Stripe Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_payments_settings[stripe_enabled]" value="1"
                                   <?php checked($settings->get('payments.stripe_enabled')); ?>>
                            <?php esc_html_e('Enable Stripe', 'semigapp'); ?>
                        </label>
                    </div>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_payments_settings[stripe_test_mode]" value="1"
                                   <?php checked($settings->get('payments.stripe_test_mode', true)); ?>>
                            <?php esc_html_e('Test Mode', 'semigapp'); ?>
                        </label>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="stripe_publishable_key"><?php esc_html_e('Publishable Key', 'semigapp'); ?></label>
                        <input type="text" name="semigapp_payments_settings[stripe_publishable_key]" id="stripe_publishable_key"
                               value="<?php echo esc_attr($settings->get('payments.stripe_publishable_key')); ?>">
                    </div>

                    <div class="semigapp-form-row">
                        <label for="stripe_secret_key"><?php esc_html_e('Secret Key', 'semigapp'); ?></label>
                        <input type="password" name="semigapp_payments_settings[stripe_secret_key]" id="stripe_secret_key"
                               value="<?php echo esc_attr($settings->get('payments.stripe_secret_key')); ?>">
                    </div>
                </div>

            <?php elseif ($tab === 'newsletter') : ?>
                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Sender Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label for="sender_name"><?php esc_html_e('Sender Name', 'semigapp'); ?></label>
                        <input type="text" name="semigapp_newsletter_settings[sender_name]" id="sender_name"
                               value="<?php echo esc_attr($settings->get('newsletter.sender_name', get_bloginfo('name'))); ?>">
                    </div>

                    <div class="semigapp-form-row">
                        <label for="sender_email"><?php esc_html_e('Sender Email', 'semigapp'); ?></label>
                        <input type="email" name="semigapp_newsletter_settings[sender_email]" id="sender_email"
                               value="<?php echo esc_attr($settings->get('newsletter.sender_email', get_option('admin_email'))); ?>">
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Subscription Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_newsletter_settings[double_optin]" value="1"
                                   <?php checked($settings->get('newsletter.double_optin', true)); ?>>
                            <?php esc_html_e('Enable Double Opt-in', 'semigapp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Subscribers must confirm their email address before being added.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="gdpr_consent_text"><?php esc_html_e('GDPR Consent Text', 'semigapp'); ?></label>
                        <textarea name="semigapp_newsletter_settings[gdpr_consent_text]" id="gdpr_consent_text" rows="3"><?php echo esc_textarea($settings->get('newsletter.gdpr_consent_text')); ?></textarea>
                    </div>
                </div>

            <?php elseif ($tab === 'events') : ?>
                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('General Event Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_events_settings[enable_registration]" value="1"
                                   <?php checked($settings->get('events.enable_registration', true)); ?>>
                            <?php esc_html_e('Enable Event Registration', 'semigapp'); ?>
                        </label>
                    </div>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_events_settings[enable_payments]" value="1"
                                   <?php checked($settings->get('events.enable_payments', true)); ?>>
                            <?php esc_html_e('Enable Paid Events', 'semigapp'); ?>
                        </label>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="default_reminder_hours"><?php esc_html_e('Reminder Hours Before Event', 'semigapp'); ?></label>
                        <input type="number" name="semigapp_events_settings[default_reminder_hours]" id="default_reminder_hours"
                               value="<?php echo esc_attr($settings->get('events.default_reminder_hours', 24)); ?>" min="1" max="168">
                        <p class="description"><?php esc_html_e('Send reminders this many hours before the event starts.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="google_maps_api_key"><?php esc_html_e('Google Maps API Key', 'semigapp'); ?></label>
                        <input type="text" name="semigapp_events_settings[google_maps_api_key]" id="google_maps_api_key"
                               value="<?php echo esc_attr($settings->get('events.google_maps_api_key')); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Optional. Used for displaying event location maps.', 'semigapp'); ?></p>
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Application Settings', 'semigapp'); ?></h3>
                    <p class="description"><?php esc_html_e('Configure how event applications are handled. Applications allow you to review and approve attendees before confirming their registration.', 'semigapp'); ?></p>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_events_settings[enable_applications]" value="1"
                                   <?php checked($settings->get('events.enable_applications', true)); ?>>
                            <?php esc_html_e('Enable Event Applications', 'semigapp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Allow events to require applications instead of direct registration.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_events_settings[auto_approve_applications]" value="1"
                                   <?php checked($settings->get('events.auto_approve_applications', false)); ?>>
                            <?php esc_html_e('Auto-Approve Applications', 'semigapp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Automatically approve all applications (disables manual review).', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_events_settings[waitlist_enabled]" value="1"
                                   <?php checked($settings->get('events.waitlist_enabled', true)); ?>>
                            <?php esc_html_e('Enable Waitlist', 'semigapp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Allow applicants to be added to a waitlist when events are full.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="application_notification_email"><?php esc_html_e('Application Notification Email', 'semigapp'); ?></label>
                        <input type="email" name="semigapp_events_settings[application_notification_email]" id="application_notification_email"
                               value="<?php echo esc_attr($settings->get('events.application_notification_email', get_option('admin_email'))); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Email address to receive notifications for new applications.', 'semigapp'); ?></p>
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Application Messages', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label for="application_confirmation_text"><?php esc_html_e('Confirmation Message', 'semigapp'); ?></label>
                        <textarea name="semigapp_events_settings[application_confirmation_text]" id="application_confirmation_text" rows="3" class="large-text"><?php echo esc_textarea($settings->get('events.application_confirmation_text', __('Thank you for your application. We will review it and get back to you soon.', 'semigapp'))); ?></textarea>
                        <p class="description"><?php esc_html_e('Message shown after an application is submitted.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="application_approved_text"><?php esc_html_e('Approval Message', 'semigapp'); ?></label>
                        <textarea name="semigapp_events_settings[application_approved_text]" id="application_approved_text" rows="3" class="large-text"><?php echo esc_textarea($settings->get('events.application_approved_text', __('Your application has been approved! We look forward to seeing you at the event.', 'semigapp'))); ?></textarea>
                        <p class="description"><?php esc_html_e('Message included in the approval notification email.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="application_rejected_text"><?php esc_html_e('Rejection Message', 'semigapp'); ?></label>
                        <textarea name="semigapp_events_settings[application_rejected_text]" id="application_rejected_text" rows="3" class="large-text"><?php echo esc_textarea($settings->get('events.application_rejected_text', __('Unfortunately, your application was not approved for this event.', 'semigapp'))); ?></textarea>
                        <p class="description"><?php esc_html_e('Message included in the rejection notification email.', 'semigapp'); ?></p>
                    </div>
                </div>

            <?php elseif ($tab === 'projects') : ?>
                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Project Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_projects_settings[enable_time_tracking]" value="1"
                                   <?php checked($settings->get('projects.enable_time_tracking', true)); ?>>
                            <?php esc_html_e('Enable Time Tracking', 'semigapp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Allow users to log time spent on tasks.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="default_task_status"><?php esc_html_e('Default Task Status', 'semigapp'); ?></label>
                        <select name="semigapp_projects_settings[default_task_status]" id="default_task_status">
                            <option value="pending" <?php selected($settings->get('projects.default_task_status'), 'pending'); ?>><?php esc_html_e('Pending', 'semigapp'); ?></option>
                            <option value="in_progress" <?php selected($settings->get('projects.default_task_status'), 'in_progress'); ?>><?php esc_html_e('In Progress', 'semigapp'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Task Priorities', 'semigapp'); ?></h3>
                    <p class="description"><?php esc_html_e('Available priority levels for tasks (comma-separated).', 'semigapp'); ?></p>

                    <div class="semigapp-form-row">
                        <label for="priorities"><?php esc_html_e('Priorities', 'semigapp'); ?></label>
                        <input type="text" name="semigapp_projects_settings[priorities_text]" id="priorities"
                               value="<?php echo esc_attr(implode(', ', $settings->get('projects.priorities', array('low', 'medium', 'high', 'urgent')))); ?>" class="regular-text">
                    </div>
                </div>

            <?php elseif ($tab === 'membership') : ?>
                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Membership Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_membership_settings[enable_trial]" value="1"
                                   <?php checked($settings->get('membership.enable_trial', false)); ?>>
                            <?php esc_html_e('Enable Trial Periods', 'semigapp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Allow membership levels to have trial periods.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="grace_period_days"><?php esc_html_e('Grace Period (Days)', 'semigapp'); ?></label>
                        <input type="number" name="semigapp_membership_settings[grace_period_days]" id="grace_period_days"
                               value="<?php echo esc_attr($settings->get('membership.grace_period_days', 7)); ?>" min="0" max="30">
                        <p class="description"><?php esc_html_e('Number of days after expiration before access is revoked.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_membership_settings[restrict_content]" value="1"
                                   <?php checked($settings->get('membership.restrict_content', true)); ?>>
                            <?php esc_html_e('Enable Content Restriction', 'semigapp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Allow restricting content to specific membership levels.', 'semigapp'); ?></p>
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Expiry Notifications', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label for="expiry_reminder_days"><?php esc_html_e('Reminder Days Before Expiry', 'semigapp'); ?></label>
                        <input type="text" name="semigapp_membership_settings[expiry_reminder_days_text]" id="expiry_reminder_days"
                               value="<?php echo esc_attr(implode(', ', $settings->get('membership.expiry_reminder_days', array(7, 3, 1)))); ?>">
                        <p class="description"><?php esc_html_e('Send reminders this many days before expiry (comma-separated, e.g., 7, 3, 1).', 'semigapp'); ?></p>
                    </div>
                </div>

            <?php elseif ($tab === 'webshop') : ?>
                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Shop Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label for="order_number_prefix"><?php esc_html_e('Order Number Prefix', 'semigapp'); ?></label>
                        <input type="text" name="semigapp_webshop_settings[order_number_prefix]" id="order_number_prefix"
                               value="<?php echo esc_attr($settings->get('webshop.order_number_prefix', 'ORD-')); ?>">
                    </div>

                    <div class="semigapp-form-row">
                        <label for="low_stock_threshold"><?php esc_html_e('Low Stock Threshold', 'semigapp'); ?></label>
                        <input type="number" name="semigapp_webshop_settings[low_stock_threshold]" id="low_stock_threshold"
                               value="<?php echo esc_attr($settings->get('webshop.low_stock_threshold', 5)); ?>" min="0">
                        <p class="description"><?php esc_html_e('Products with stock at or below this level will be marked as low stock.', 'semigapp'); ?></p>
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Tax Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_webshop_settings[enable_tax]" value="1"
                                   <?php checked($settings->get('webshop.enable_tax', true)); ?>>
                            <?php esc_html_e('Enable Tax', 'semigapp'); ?>
                        </label>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="tax_rate"><?php esc_html_e('Default Tax Rate (%)', 'semigapp'); ?></label>
                        <input type="number" name="semigapp_webshop_settings[tax_rate]" id="tax_rate"
                               value="<?php echo esc_attr($settings->get('webshop.tax_rate', 25)); ?>" min="0" max="100" step="0.01">
                    </div>

                    <div class="semigapp-form-row">
                        <label for="tax_display"><?php esc_html_e('Display Prices', 'semigapp'); ?></label>
                        <select name="semigapp_webshop_settings[tax_display]" id="tax_display">
                            <option value="incl" <?php selected($settings->get('webshop.tax_display'), 'incl'); ?>><?php esc_html_e('Including Tax', 'semigapp'); ?></option>
                            <option value="excl" <?php selected($settings->get('webshop.tax_display'), 'excl'); ?>><?php esc_html_e('Excluding Tax', 'semigapp'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Shipping Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_webshop_settings[enable_shipping]" value="1"
                                   <?php checked($settings->get('webshop.enable_shipping', true)); ?>>
                            <?php esc_html_e('Enable Shipping', 'semigapp'); ?>
                        </label>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="free_shipping_threshold"><?php esc_html_e('Free Shipping Threshold', 'semigapp'); ?></label>
                        <input type="number" name="semigapp_webshop_settings[free_shipping_threshold]" id="free_shipping_threshold"
                               value="<?php echo esc_attr($settings->get('webshop.free_shipping_threshold', 500)); ?>" min="0">
                        <p class="description"><?php esc_html_e('Orders above this amount qualify for free shipping. Set to 0 to disable.', 'semigapp'); ?></p>
                    </div>
                </div>

            <?php elseif ($tab === 'emails') : ?>
                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Email Template Settings', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label for="header_logo"><?php esc_html_e('Header Logo URL', 'semigapp'); ?></label>
                        <input type="url" name="semigapp_emails_settings[header_logo]" id="header_logo"
                               value="<?php echo esc_attr($settings->get('emails.header_logo')); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('URL to your logo image for email headers.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label for="header_color"><?php esc_html_e('Header Background Color', 'semigapp'); ?></label>
                        <input type="color" name="semigapp_emails_settings[header_color]" id="header_color"
                               value="<?php echo esc_attr($settings->get('emails.header_color', '#2563eb')); ?>">
                    </div>

                    <div class="semigapp-form-row">
                        <label for="footer_text"><?php esc_html_e('Email Footer Text', 'semigapp'); ?></label>
                        <textarea name="semigapp_emails_settings[footer_text]" id="footer_text" rows="3" class="large-text"><?php echo esc_textarea($settings->get('emails.footer_text')); ?></textarea>
                        <p class="description"><?php esc_html_e('Text shown at the bottom of all emails.', 'semigapp'); ?></p>
                    </div>
                </div>

                <div class="semigapp-settings-section">
                    <h3><?php esc_html_e('Email Notifications', 'semigapp'); ?></h3>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_emails_settings[notify_admin_new_order]" value="1"
                                   <?php checked($settings->get('emails.notify_admin_new_order', true)); ?>>
                            <?php esc_html_e('New Order Notification', 'semigapp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Send email to admin when a new order is placed.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_emails_settings[notify_admin_new_member]" value="1"
                                   <?php checked($settings->get('emails.notify_admin_new_member', true)); ?>>
                            <?php esc_html_e('New Member Notification', 'semigapp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Send email to admin when a new member signs up.', 'semigapp'); ?></p>
                    </div>

                    <div class="semigapp-form-row">
                        <label>
                            <input type="checkbox" name="semigapp_emails_settings[notify_admin_new_registration]" value="1"
                                   <?php checked($settings->get('emails.notify_admin_new_registration', true)); ?>>
                            <?php esc_html_e('New Event Registration Notification', 'semigapp'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Send email to admin when someone registers for an event.', 'semigapp'); ?></p>
                    </div>
                </div>

            <?php endif; ?>

            <?php submit_button(); ?>
        </form>
    </div>
</div>
