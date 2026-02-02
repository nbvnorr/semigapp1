<?php
/**
 * Admin Event Application Fields Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$field_types = array(
    'text' => __('Text', 'semigapp'),
    'textarea' => __('Textarea', 'semigapp'),
    'select' => __('Dropdown', 'semigapp'),
    'checkbox' => __('Checkbox', 'semigapp'),
    'radio' => __('Radio Buttons', 'semigapp'),
    'date' => __('Date', 'semigapp'),
    'number' => __('Number', 'semigapp'),
);
?>
<div class="wrap semigapp-admin-wrap">
    <div class="semigapp-admin-header">
        <h1><?php printf(esc_html__('Application Fields for: %s', 'semigapp'), esc_html($event->title)); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events&action=edit&event_id=' . $event->id)); ?>" class="page-title-action">
            <?php esc_html_e('Back to Event', 'semigapp'); ?>
        </a>
    </div>

    <div class="semigapp-admin-card">
        <div class="semigapp-admin-card-header">
            <h2><?php esc_html_e('Custom Application Fields', 'semigapp'); ?></h2>
            <button type="button" class="button button-primary" id="add-field">
                <?php esc_html_e('Add Field', 'semigapp'); ?>
            </button>
        </div>
        <div class="semigapp-admin-card-body">
            <p class="description">
                <?php esc_html_e('Add custom fields to collect additional information from applicants. These fields will appear on the application form.', 'semigapp'); ?>
            </p>

            <div id="application-fields-list" class="semigapp-fields-list">
                <?php if (empty($fields)) : ?>
                    <div class="semigapp-empty-state semigapp-no-fields-message">
                        <p><?php esc_html_e('No custom fields added yet. Click "Add Field" to create one.', 'semigapp'); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ($fields as $field) : ?>
                        <div class="semigapp-field-item" data-field-id="<?php echo esc_attr($field->id); ?>">
                            <div class="semigapp-field-header">
                                <span class="semigapp-field-drag-handle dashicons dashicons-menu"></span>
                                <span class="semigapp-field-label"><?php echo esc_html($field->field_label); ?></span>
                                <span class="semigapp-field-type"><?php echo esc_html($field_types[$field->field_type] ?? $field->field_type); ?></span>
                                <?php if ($field->is_required) : ?>
                                    <span class="semigapp-field-required"><?php esc_html_e('Required', 'semigapp'); ?></span>
                                <?php endif; ?>
                                <div class="semigapp-field-actions">
                                    <button type="button" class="button button-small semigapp-edit-field"><?php esc_html_e('Edit', 'semigapp'); ?></button>
                                    <button type="button" class="button button-small button-link-delete semigapp-delete-field"><?php esc_html_e('Delete', 'semigapp'); ?></button>
                                </div>
                            </div>
                            <div class="semigapp-field-details" style="display: none;">
                                <input type="hidden" class="field-id" value="<?php echo esc_attr($field->id); ?>">
                                <input type="hidden" class="field-name" value="<?php echo esc_attr($field->field_name); ?>">
                                <div class="semigapp-form-row">
                                    <label><?php esc_html_e('Label', 'semigapp'); ?></label>
                                    <input type="text" class="field-label widefat" value="<?php echo esc_attr($field->field_label); ?>">
                                </div>
                                <div class="semigapp-form-row">
                                    <label><?php esc_html_e('Field Type', 'semigapp'); ?></label>
                                    <select class="field-type">
                                        <?php foreach ($field_types as $type_key => $type_label) : ?>
                                            <option value="<?php echo esc_attr($type_key); ?>" <?php selected($field->field_type, $type_key); ?>><?php echo esc_html($type_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="semigapp-form-row field-options-row" <?php echo !in_array($field->field_type, array('select', 'radio')) ? 'style="display:none;"' : ''; ?>>
                                    <label><?php esc_html_e('Options (one per line)', 'semigapp'); ?></label>
                                    <textarea class="field-options widefat" rows="3"><?php
                                        $options = json_decode($field->field_options, true);
                                        echo $options ? esc_textarea(implode("\n", $options)) : '';
                                    ?></textarea>
                                </div>
                                <div class="semigapp-form-row">
                                    <label><?php esc_html_e('Placeholder', 'semigapp'); ?></label>
                                    <input type="text" class="field-placeholder widefat" value="<?php echo esc_attr($field->placeholder); ?>">
                                </div>
                                <div class="semigapp-form-row">
                                    <label><?php esc_html_e('Help Text', 'semigapp'); ?></label>
                                    <input type="text" class="field-help-text widefat" value="<?php echo esc_attr($field->help_text); ?>">
                                </div>
                                <div class="semigapp-form-row">
                                    <label>
                                        <input type="checkbox" class="field-required" <?php checked($field->is_required); ?>>
                                        <?php esc_html_e('Required field', 'semigapp'); ?>
                                    </label>
                                </div>
                                <div class="semigapp-field-footer">
                                    <button type="button" class="button button-primary semigapp-save-field"><?php esc_html_e('Save', 'semigapp'); ?></button>
                                    <button type="button" class="button semigapp-cancel-edit"><?php esc_html_e('Cancel', 'semigapp'); ?></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- New Field Template -->
<script type="text/html" id="tmpl-new-field">
    <div class="semigapp-field-item semigapp-field-new" data-field-id="new">
        <div class="semigapp-field-header">
            <span class="semigapp-field-drag-handle dashicons dashicons-menu"></span>
            <span class="semigapp-field-label"><?php esc_html_e('New Field', 'semigapp'); ?></span>
        </div>
        <div class="semigapp-field-details" style="display: block;">
            <input type="hidden" class="field-id" value="">
            <input type="hidden" class="field-name" value="">
            <div class="semigapp-form-row">
                <label><?php esc_html_e('Label', 'semigapp'); ?> <span class="required">*</span></label>
                <input type="text" class="field-label widefat" required>
            </div>
            <div class="semigapp-form-row">
                <label><?php esc_html_e('Field Type', 'semigapp'); ?></label>
                <select class="field-type">
                    <?php foreach ($field_types as $type_key => $type_label) : ?>
                        <option value="<?php echo esc_attr($type_key); ?>"><?php echo esc_html($type_label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="semigapp-form-row field-options-row" style="display: none;">
                <label><?php esc_html_e('Options (one per line)', 'semigapp'); ?></label>
                <textarea class="field-options widefat" rows="3"></textarea>
            </div>
            <div class="semigapp-form-row">
                <label><?php esc_html_e('Placeholder', 'semigapp'); ?></label>
                <input type="text" class="field-placeholder widefat">
            </div>
            <div class="semigapp-form-row">
                <label><?php esc_html_e('Help Text', 'semigapp'); ?></label>
                <input type="text" class="field-help-text widefat">
            </div>
            <div class="semigapp-form-row">
                <label>
                    <input type="checkbox" class="field-required">
                    <?php esc_html_e('Required field', 'semigapp'); ?>
                </label>
            </div>
            <div class="semigapp-field-footer">
                <button type="button" class="button button-primary semigapp-save-field"><?php esc_html_e('Save', 'semigapp'); ?></button>
                <button type="button" class="button semigapp-cancel-edit"><?php esc_html_e('Cancel', 'semigapp'); ?></button>
            </div>
        </div>
    </div>
</script>

<style>
.semigapp-fields-list {
    margin-top: 1rem;
}

.semigapp-field-item {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.semigapp-field-header {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    gap: 0.75rem;
}

.semigapp-field-drag-handle {
    cursor: move;
    color: #9ca3af;
}

.semigapp-field-label {
    font-weight: 500;
    flex: 1;
}

.semigapp-field-type {
    background: #e5e7eb;
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    font-size: 0.8rem;
    color: #4b5563;
}

.semigapp-field-required {
    background: #fef2f2;
    color: #dc2626;
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    font-size: 0.8rem;
}

.semigapp-field-actions {
    display: flex;
    gap: 0.25rem;
}

.semigapp-field-details {
    padding: 1rem;
    background: #fff;
    border-top: 1px solid #e5e7eb;
}

.semigapp-field-footer {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 0.5rem;
}

.semigapp-no-fields-message {
    padding: 2rem;
    text-align: center;
    background: #f9fafb;
    border-radius: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
    var eventId = <?php echo intval($event->id); ?>;

    // Add new field
    $('#add-field').on('click', function() {
        var template = $('#tmpl-new-field').html();
        $('.semigapp-no-fields-message').remove();
        $('#application-fields-list').append(template);
    });

    // Edit field
    $(document).on('click', '.semigapp-edit-field', function() {
        var $item = $(this).closest('.semigapp-field-item');
        $item.find('.semigapp-field-details').slideDown();
    });

    // Cancel edit
    $(document).on('click', '.semigapp-cancel-edit', function() {
        var $item = $(this).closest('.semigapp-field-item');
        if ($item.hasClass('semigapp-field-new')) {
            $item.remove();
        } else {
            $item.find('.semigapp-field-details').slideUp();
        }
    });

    // Toggle options field based on type
    $(document).on('change', '.field-type', function() {
        var $row = $(this).closest('.semigapp-field-details').find('.field-options-row');
        if ($(this).val() === 'select' || $(this).val() === 'radio') {
            $row.slideDown();
        } else {
            $row.slideUp();
        }
    });

    // Save field
    $(document).on('click', '.semigapp-save-field', function() {
        var $item = $(this).closest('.semigapp-field-item');
        var $button = $(this);
        var fieldId = $item.find('.field-id').val();
        var fieldLabel = $item.find('.field-label').val();

        if (!fieldLabel.trim()) {
            alert('<?php esc_attr_e('Please enter a field label.', 'semigapp'); ?>');
            return;
        }

        var fieldName = $item.find('.field-name').val();
        if (!fieldName) {
            fieldName = fieldLabel.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
        }

        var options = $item.find('.field-options').val().trim().split('\n').filter(function(o) { return o.trim(); });

        var data = {
            action: 'semigapp_admin_action',
            nonce: semigapp_admin.nonce,
            admin_action: 'save_application_field',
            event_id: eventId,
            field: {
                id: fieldId,
                field_name: fieldName,
                field_label: fieldLabel,
                field_type: $item.find('.field-type').val(),
                field_options: JSON.stringify(options),
                placeholder: $item.find('.field-placeholder').val(),
                help_text: $item.find('.field-help-text').val(),
                is_required: $item.find('.field-required').is(':checked') ? 1 : 0,
                sort_order: $item.index()
            }
        };

        $button.prop('disabled', true).text('<?php esc_attr_e('Saving...', 'semigapp'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php esc_attr_e('Failed to save field.', 'semigapp'); ?>');
                    $button.prop('disabled', false).text('<?php esc_attr_e('Save', 'semigapp'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_attr_e('An error occurred.', 'semigapp'); ?>');
                $button.prop('disabled', false).text('<?php esc_attr_e('Save', 'semigapp'); ?>');
            }
        });
    });

    // Delete field
    $(document).on('click', '.semigapp-delete-field', function() {
        if (!confirm('<?php esc_attr_e('Are you sure you want to delete this field?', 'semigapp'); ?>')) {
            return;
        }

        var $item = $(this).closest('.semigapp-field-item');
        var fieldId = $item.data('field-id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'semigapp_admin_action',
                nonce: semigapp_admin.nonce,
                admin_action: 'delete_application_field',
                field_id: fieldId
            },
            success: function(response) {
                if (response.success) {
                    $item.fadeOut(function() { $(this).remove(); });
                } else {
                    alert(response.data.message || '<?php esc_attr_e('Failed to delete field.', 'semigapp'); ?>');
                }
            }
        });
    });

    // Make fields sortable
    if (typeof $.fn.sortable !== 'undefined') {
        $('#application-fields-list').sortable({
            handle: '.semigapp-field-drag-handle',
            placeholder: 'semigapp-field-placeholder',
            update: function(event, ui) {
                var order = [];
                $('#application-fields-list .semigapp-field-item').each(function(index) {
                    var fieldId = $(this).data('field-id');
                    if (fieldId && fieldId !== 'new') {
                        order.push({id: fieldId, order: index});
                    }
                });

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'semigapp_admin_action',
                        nonce: semigapp_admin.nonce,
                        admin_action: 'reorder_application_fields',
                        event_id: eventId,
                        order: order
                    }
                });
            }
        });
    }
});
</script>
