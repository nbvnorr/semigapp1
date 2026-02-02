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

            <?php else : ?>
                <p><?php esc_html_e('Settings for this section.', 'semigapp'); ?></p>
            <?php endif; ?>

            <?php submit_button(); ?>
        </form>
    </div>
</div>
