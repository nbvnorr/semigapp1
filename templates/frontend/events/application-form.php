<?php
/**
 * Event Application Form Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
?>
<div class="semigapp-application-form-wrapper">
    <div class="semigapp-event-header">
        <h3><?php echo esc_html($event->title); ?></h3>
        <div class="semigapp-event-meta">
            <span class="semigapp-event-date">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->start_date))); ?>
            </span>
            <?php if (!empty($event->location)) : ?>
                <span class="semigapp-event-location">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html($event->location); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <form class="semigapp-application-form" method="post" id="semigapp-application-form">
        <?php wp_nonce_field('semigapp_frontend', 'nonce'); ?>
        <input type="hidden" name="action" value="semigapp_event_apply">
        <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">

        <div class="semigapp-form-section">
            <h4><?php esc_html_e('Personal Information', 'semigapp'); ?></h4>

            <div class="semigapp-form-row">
                <div class="semigapp-form-group semigapp-form-group-half">
                    <label for="applicant_name"><?php esc_html_e('Full Name', 'semigapp'); ?> <span class="required">*</span></label>
                    <input type="text"
                           name="applicant_name"
                           id="applicant_name"
                           class="semigapp-input"
                           value="<?php echo is_user_logged_in() ? esc_attr($current_user->display_name) : ''; ?>"
                           required>
                </div>

                <div class="semigapp-form-group semigapp-form-group-half">
                    <label for="applicant_email"><?php esc_html_e('Email Address', 'semigapp'); ?> <span class="required">*</span></label>
                    <input type="email"
                           name="applicant_email"
                           id="applicant_email"
                           class="semigapp-input"
                           value="<?php echo is_user_logged_in() ? esc_attr($current_user->user_email) : ''; ?>"
                           required>
                </div>
            </div>

            <div class="semigapp-form-row">
                <div class="semigapp-form-group semigapp-form-group-half">
                    <label for="applicant_phone"><?php esc_html_e('Phone Number', 'semigapp'); ?></label>
                    <input type="tel"
                           name="applicant_phone"
                           id="applicant_phone"
                           class="semigapp-input"
                           placeholder="<?php esc_attr_e('+46 70 123 4567', 'semigapp'); ?>">
                </div>

                <div class="semigapp-form-group semigapp-form-group-half">
                    <label for="attendees"><?php esc_html_e('Number of Attendees', 'semigapp'); ?></label>
                    <input type="number"
                           name="attendees"
                           id="attendees"
                           class="semigapp-input"
                           value="1"
                           min="1"
                           max="<?php echo $event->max_attendees > 0 ? esc_attr($event->max_attendees) : 10; ?>">
                </div>
            </div>
        </div>

        <div class="semigapp-form-section">
            <h4><?php esc_html_e('Application Details', 'semigapp'); ?></h4>

            <div class="semigapp-form-group">
                <label for="motivation"><?php esc_html_e('Why do you want to attend this event?', 'semigapp'); ?></label>
                <textarea name="motivation"
                          id="motivation"
                          class="semigapp-textarea"
                          rows="4"
                          placeholder="<?php esc_attr_e('Tell us about your interest in this event...', 'semigapp'); ?>"></textarea>
            </div>

            <?php if (!empty($custom_fields)) : ?>
                <?php foreach ($custom_fields as $field) : ?>
                    <div class="semigapp-form-group">
                        <label for="custom_<?php echo esc_attr($field->field_name); ?>">
                            <?php echo esc_html($field->field_label); ?>
                            <?php if ($field->is_required) : ?>
                                <span class="required">*</span>
                            <?php endif; ?>
                        </label>

                        <?php
                        $field_options = json_decode($field->field_options, true) ?: array();

                        switch ($field->field_type) :
                            case 'textarea':
                                ?>
                                <textarea name="custom_<?php echo esc_attr($field->field_name); ?>"
                                          id="custom_<?php echo esc_attr($field->field_name); ?>"
                                          class="semigapp-textarea"
                                          rows="3"
                                          placeholder="<?php echo esc_attr($field->placeholder); ?>"
                                          <?php echo $field->is_required ? 'required' : ''; ?>></textarea>
                                <?php
                                break;

                            case 'select':
                                ?>
                                <select name="custom_<?php echo esc_attr($field->field_name); ?>"
                                        id="custom_<?php echo esc_attr($field->field_name); ?>"
                                        class="semigapp-select"
                                        <?php echo $field->is_required ? 'required' : ''; ?>>
                                    <option value=""><?php esc_html_e('Select an option', 'semigapp'); ?></option>
                                    <?php foreach ($field_options as $option) : ?>
                                        <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php
                                break;

                            case 'checkbox':
                                ?>
                                <label class="semigapp-checkbox">
                                    <input type="checkbox"
                                           name="custom_<?php echo esc_attr($field->field_name); ?>"
                                           value="1"
                                           <?php echo $field->is_required ? 'required' : ''; ?>>
                                    <span><?php echo esc_html($field->placeholder ?: $field->field_label); ?></span>
                                </label>
                                <?php
                                break;

                            case 'radio':
                                foreach ($field_options as $option) :
                                    ?>
                                    <label class="semigapp-radio">
                                        <input type="radio"
                                               name="custom_<?php echo esc_attr($field->field_name); ?>"
                                               value="<?php echo esc_attr($option); ?>"
                                               <?php echo $field->is_required ? 'required' : ''; ?>>
                                        <span><?php echo esc_html($option); ?></span>
                                    </label>
                                    <?php
                                endforeach;
                                break;

                            case 'date':
                                ?>
                                <input type="date"
                                       name="custom_<?php echo esc_attr($field->field_name); ?>"
                                       id="custom_<?php echo esc_attr($field->field_name); ?>"
                                       class="semigapp-input"
                                       <?php echo $field->is_required ? 'required' : ''; ?>>
                                <?php
                                break;

                            case 'number':
                                ?>
                                <input type="number"
                                       name="custom_<?php echo esc_attr($field->field_name); ?>"
                                       id="custom_<?php echo esc_attr($field->field_name); ?>"
                                       class="semigapp-input"
                                       placeholder="<?php echo esc_attr($field->placeholder); ?>"
                                       <?php echo $field->is_required ? 'required' : ''; ?>>
                                <?php
                                break;

                            default: // text
                                ?>
                                <input type="text"
                                       name="custom_<?php echo esc_attr($field->field_name); ?>"
                                       id="custom_<?php echo esc_attr($field->field_name); ?>"
                                       class="semigapp-input"
                                       placeholder="<?php echo esc_attr($field->placeholder); ?>"
                                       <?php echo $field->is_required ? 'required' : ''; ?>>
                                <?php
                        endswitch;

                        if (!empty($field->help_text)) :
                            ?>
                            <p class="semigapp-field-help"><?php echo esc_html($field->help_text); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($event->price > 0) : ?>
            <div class="semigapp-form-section semigapp-notice semigapp-notice-info">
                <p>
                    <strong><?php esc_html_e('Event Fee:', 'semigapp'); ?></strong>
                    <?php echo esc_html(\SemigApp\Settings::get_instance()->format_price($event->price)); ?>
                </p>
                <p class="semigapp-text-muted">
                    <?php esc_html_e('Payment will be requested after your application is approved.', 'semigapp'); ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="semigapp-form-actions">
            <button type="submit" class="semigapp-button semigapp-button-primary semigapp-button-lg">
                <?php esc_html_e('Submit Application', 'semigapp'); ?>
            </button>
        </div>

        <div class="semigapp-application-message" style="display: none;"></div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#semigapp-application-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $message = $form.find('.semigapp-application-message');

        $button.prop('disabled', true).text('<?php esc_attr_e('Submitting...', 'semigapp'); ?>');

        $.ajax({
            url: semigapp_frontend.ajax_url,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    $form.find('.semigapp-form-section, .semigapp-form-actions').hide();
                    $message.removeClass('semigapp-notice-error').addClass('semigapp-notice semigapp-notice-success')
                        .html('<p>' + response.data.message + '</p>').show();
                } else {
                    $message.removeClass('semigapp-notice-success').addClass('semigapp-notice semigapp-notice-error')
                        .html('<p>' + response.data.message + '</p>').show();
                    $button.prop('disabled', false).text('<?php esc_attr_e('Submit Application', 'semigapp'); ?>');
                }
            },
            error: function() {
                $message.addClass('semigapp-notice semigapp-notice-error')
                    .html('<p><?php esc_attr_e('An error occurred. Please try again.', 'semigapp'); ?></p>').show();
                $button.prop('disabled', false).text('<?php esc_attr_e('Submit Application', 'semigapp'); ?>');
            }
        });
    });
});
</script>
