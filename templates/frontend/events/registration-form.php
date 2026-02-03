<?php
/**
 * Event Registration Form Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();
?>

<div class="semigapp-event-registration">
    <div class="registration-header">
        <h2><?php esc_html_e('Register for Event', 'semigapp'); ?></h2>
        <p class="event-name"><?php echo esc_html($event->title); ?></p>
    </div>

    <div class="event-summary">
        <div class="summary-item">
            <span class="summary-label"><?php esc_html_e('Date:', 'semigapp'); ?></span>
            <span class="summary-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event->start_date))); ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label"><?php esc_html_e('Time:', 'semigapp'); ?></span>
            <span class="summary-value"><?php echo esc_html(date_i18n('H:i', strtotime($event->start_date))); ?></span>
        </div>
        <?php if (!empty($event->location)) : ?>
            <div class="summary-item">
                <span class="summary-label"><?php esc_html_e('Location:', 'semigapp'); ?></span>
                <span class="summary-value"><?php echo esc_html($event->location); ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($event->price) && $event->price > 0) : ?>
            <div class="summary-item">
                <span class="summary-label"><?php esc_html_e('Price:', 'semigapp'); ?></span>
                <span class="summary-value"><?php echo esc_html(number_format($event->price, 2) . ' ' . ($event->currency ?? 'SEK')); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($error) && $error) : ?>
        <div class="semigapp-notice semigapp-notice-error">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($success) && $success) : ?>
        <div class="semigapp-notice semigapp-notice-success">
            <p><?php echo esc_html($success); ?></p>
        </div>
    <?php else : ?>
        <form method="post" class="semigapp-form registration-form" id="event-registration-form">
            <?php wp_nonce_field('semigapp_event_registration', 'registration_nonce'); ?>
            <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
            <input type="hidden" name="action" value="register_event">

            <div class="form-section">
                <h3><?php esc_html_e('Your Information', 'semigapp'); ?></h3>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="first_name"><?php esc_html_e('First Name', 'semigapp'); ?> <span class="required">*</span></label>
                        <input type="text"
                               id="first_name"
                               name="first_name"
                               value="<?php echo esc_attr($user->first_name ?? ''); ?>"
                               required>
                    </div>

                    <div class="form-group form-group-half">
                        <label for="last_name"><?php esc_html_e('Last Name', 'semigapp'); ?> <span class="required">*</span></label>
                        <input type="text"
                               id="last_name"
                               name="last_name"
                               value="<?php echo esc_attr($user->last_name ?? ''); ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email"><?php esc_html_e('Email Address', 'semigapp'); ?> <span class="required">*</span></label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="<?php echo esc_attr($user->user_email ?? ''); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="phone"><?php esc_html_e('Phone Number', 'semigapp'); ?></label>
                    <input type="tel"
                           id="phone"
                           name="phone"
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'phone', true)); ?>"
                           pattern="[0-9+\-\s()]*">
                </div>
            </div>

            <?php if (!empty($event->custom_fields) && is_array($event->custom_fields)) : ?>
                <div class="form-section">
                    <h3><?php esc_html_e('Additional Information', 'semigapp'); ?></h3>

                    <?php foreach ($event->custom_fields as $field) : ?>
                        <div class="form-group">
                            <label for="custom_<?php echo esc_attr($field['id']); ?>">
                                <?php echo esc_html($field['label']); ?>
                                <?php if (!empty($field['required'])) : ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </label>

                            <?php if ($field['type'] === 'textarea') : ?>
                                <textarea
                                    id="custom_<?php echo esc_attr($field['id']); ?>"
                                    name="custom_fields[<?php echo esc_attr($field['id']); ?>]"
                                    <?php echo !empty($field['required']) ? 'required' : ''; ?>
                                    rows="4"></textarea>

                            <?php elseif ($field['type'] === 'select') : ?>
                                <select
                                    id="custom_<?php echo esc_attr($field['id']); ?>"
                                    name="custom_fields[<?php echo esc_attr($field['id']); ?>]"
                                    <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                    <option value=""><?php esc_html_e('Select...', 'semigapp'); ?></option>
                                    <?php foreach ($field['options'] as $option) : ?>
                                        <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($field['type'] === 'checkbox') : ?>
                                <label class="checkbox-label">
                                    <input type="checkbox"
                                           id="custom_<?php echo esc_attr($field['id']); ?>"
                                           name="custom_fields[<?php echo esc_attr($field['id']); ?>]"
                                           value="1"
                                           <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                    <?php echo esc_html($field['checkbox_label'] ?? ''); ?>
                                </label>

                            <?php else : ?>
                                <input
                                    type="<?php echo esc_attr($field['type'] ?? 'text'); ?>"
                                    id="custom_<?php echo esc_attr($field['id']); ?>"
                                    name="custom_fields[<?php echo esc_attr($field['id']); ?>]"
                                    <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                            <?php endif; ?>

                            <?php if (!empty($field['description'])) : ?>
                                <p class="field-description"><?php echo esc_html($field['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($event->terms_conditions)) : ?>
                <div class="form-section">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="accept_terms" required>
                            <?php
                            printf(
                                esc_html__('I accept the %sterms and conditions%s', 'semigapp'),
                                '<a href="#terms-modal" class="terms-link">',
                                '</a>'
                            );
                            ?>
                        </label>
                    </div>
                </div>

                <div id="terms-modal" class="semigapp-modal" style="display:none;">
                    <div class="modal-content">
                        <h3><?php esc_html_e('Terms and Conditions', 'semigapp'); ?></h3>
                        <?php echo wp_kses_post($event->terms_conditions); ?>
                        <button type="button" class="close-modal semigapp-btn"><?php esc_html_e('Close', 'semigapp'); ?></button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="semigapp-btn semigapp-btn-primary">
                    <?php
                    if (!empty($event->price) && $event->price > 0) {
                        printf(
                            esc_html__('Complete Registration - %s', 'semigapp'),
                            number_format($event->price, 2) . ' ' . ($event->currency ?? 'SEK')
                        );
                    } else {
                        esc_html_e('Complete Registration', 'semigapp');
                    }
                    ?>
                </button>

                <a href="<?php echo esc_url(add_query_arg('event_id', $event->id, remove_query_arg('action'))); ?>"
                   class="semigapp-btn semigapp-btn-secondary">
                    <?php esc_html_e('Cancel', 'semigapp'); ?>
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>
