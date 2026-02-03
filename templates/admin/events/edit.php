<?php
/**
 * Admin Event Edit Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$is_new = empty($event->id);
$title = $is_new ? __('Add New Event', 'semigapp') : __('Edit Event', 'semigapp');
?>

<div class="wrap semigapp-admin-event-edit">
    <h1><?php echo esc_html($title); ?></h1>

    <?php if (!empty($error)) : ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" id="event-form" enctype="multipart/form-data">
        <?php wp_nonce_field('semigapp_save_event', 'event_nonce'); ?>
        <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id ?? 0); ?>">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <input type="text"
                               name="title"
                               id="title"
                               value="<?php echo esc_attr($event->title ?? ''); ?>"
                               placeholder="<?php esc_attr_e('Event Title', 'semigapp'); ?>"
                               required
                               class="large-text">
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Description', 'semigapp'); ?></h2>
                        <div class="inside">
                            <?php
                            wp_editor(
                                $event->description ?? '',
                                'event_description',
                                array(
                                    'textarea_name' => 'description',
                                    'textarea_rows' => 10,
                                    'media_buttons' => true,
                                )
                            );
                            ?>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Event Details', 'semigapp'); ?></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><label for="start_date"><?php esc_html_e('Start Date & Time', 'semigapp'); ?></label></th>
                                    <td>
                                        <input type="datetime-local"
                                               name="start_date"
                                               id="start_date"
                                               value="<?php echo esc_attr($event->start_date ? date('Y-m-d\TH:i', strtotime($event->start_date)) : ''); ?>"
                                               required>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="end_date"><?php esc_html_e('End Date & Time', 'semigapp'); ?></label></th>
                                    <td>
                                        <input type="datetime-local"
                                               name="end_date"
                                               id="end_date"
                                               value="<?php echo esc_attr($event->end_date ? date('Y-m-d\TH:i', strtotime($event->end_date)) : ''); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="location"><?php esc_html_e('Location', 'semigapp'); ?></label></th>
                                    <td>
                                        <input type="text"
                                               name="location"
                                               id="location"
                                               value="<?php echo esc_attr($event->location ?? ''); ?>"
                                               class="large-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="max_attendees"><?php esc_html_e('Max Attendees', 'semigapp'); ?></label></th>
                                    <td>
                                        <input type="number"
                                               name="max_attendees"
                                               id="max_attendees"
                                               value="<?php echo esc_attr($event->max_attendees ?? 0); ?>"
                                               min="0">
                                        <p class="description"><?php esc_html_e('Set to 0 for unlimited.', 'semigapp'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="price"><?php esc_html_e('Price', 'semigapp'); ?></label></th>
                                    <td>
                                        <input type="number"
                                               name="price"
                                               id="price"
                                               value="<?php echo esc_attr($event->price ?? 0); ?>"
                                               min="0"
                                               step="0.01">
                                        <select name="currency">
                                            <option value="SEK" <?php selected($event->currency ?? 'SEK', 'SEK'); ?>>SEK</option>
                                            <option value="EUR" <?php selected($event->currency ?? '', 'EUR'); ?>>EUR</option>
                                            <option value="USD" <?php selected($event->currency ?? '', 'USD'); ?>>USD</option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Set to 0 for free events.', 'semigapp'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Event Settings', 'semigapp'); ?></h2>
                        <div class="inside">
                            <p>
                                <label for="status"><?php esc_html_e('Status', 'semigapp'); ?></label>
                                <select name="status" id="status" class="widefat">
                                    <option value="draft" <?php selected($event->status ?? 'draft', 'draft'); ?>><?php esc_html_e('Draft', 'semigapp'); ?></option>
                                    <option value="published" <?php selected($event->status ?? '', 'published'); ?>><?php esc_html_e('Published', 'semigapp'); ?></option>
                                    <option value="cancelled" <?php selected($event->status ?? '', 'cancelled'); ?>><?php esc_html_e('Cancelled', 'semigapp'); ?></option>
                                </select>
                            </p>

                            <p>
                                <label>
                                    <input type="checkbox"
                                           name="registration_enabled"
                                           value="1"
                                           <?php checked($event->registration_enabled ?? true); ?>>
                                    <?php esc_html_e('Enable registration', 'semigapp'); ?>
                                </label>
                            </p>

                            <p>
                                <label for="featured_image"><?php esc_html_e('Featured Image', 'semigapp'); ?></label>
                                <input type="hidden" name="featured_image" id="featured_image" value="<?php echo esc_attr($event->featured_image ?? ''); ?>">
                                <button type="button" class="button" id="upload-image-btn"><?php esc_html_e('Select Image', 'semigapp'); ?></button>
                                <div id="image-preview">
                                    <?php if (!empty($event->featured_image)) : ?>
                                        <?php echo wp_get_attachment_image($event->featured_image, 'thumbnail'); ?>
                                    <?php endif; ?>
                                </div>
                            </p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Actions', 'semigapp'); ?></h2>
                        <div class="inside">
                            <p>
                                <button type="submit" name="save" class="button button-primary button-large">
                                    <?php echo $is_new ? esc_html__('Create Event', 'semigapp') : esc_html__('Update Event', 'semigapp'); ?>
                                </button>
                            </p>
                            <p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events')); ?>" class="button">
                                    <?php esc_html_e('Cancel', 'semigapp'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
