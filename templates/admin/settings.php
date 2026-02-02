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

            <?php else : ?>
                <p><?php esc_html_e('Settings for this section.', 'semigapp'); ?></p>
            <?php endif; ?>

            <?php submit_button(); ?>
        </form>
    </div>
</div>
