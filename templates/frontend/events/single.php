<?php
/**
 * Single Event Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="semigapp-event-single">
    <article class="semigapp-event" data-event-id="<?php echo esc_attr($event->id); ?>">
        <header class="event-header">
            <?php if (!empty($event->featured_image)) : ?>
                <div class="event-featured-image">
                    <?php echo wp_get_attachment_image($event->featured_image, 'large'); ?>
                </div>
            <?php endif; ?>

            <div class="event-header-content">
                <h1 class="event-title"><?php echo esc_html($event->title); ?></h1>

                <div class="event-status-badge status-<?php echo esc_attr($event->status); ?>">
                    <?php echo esc_html(ucfirst($event->status)); ?>
                </div>
            </div>
        </header>

        <div class="event-details">
            <div class="event-info-card">
                <div class="info-item">
                    <span class="info-icon dashicons dashicons-calendar-alt"></span>
                    <div class="info-content">
                        <span class="info-label"><?php esc_html_e('Date', 'semigapp'); ?></span>
                        <span class="info-value">
                            <?php
                            echo esc_html(date_i18n(get_option('date_format'), strtotime($event->start_date)));
                            if ($event->end_date && date('Y-m-d', strtotime($event->start_date)) !== date('Y-m-d', strtotime($event->end_date))) {
                                echo ' - ' . esc_html(date_i18n(get_option('date_format'), strtotime($event->end_date)));
                            }
                            ?>
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icon dashicons dashicons-clock"></span>
                    <div class="info-content">
                        <span class="info-label"><?php esc_html_e('Time', 'semigapp'); ?></span>
                        <span class="info-value">
                            <?php
                            echo esc_html(date_i18n('H:i', strtotime($event->start_date)));
                            if ($event->end_date) {
                                echo ' - ' . esc_html(date_i18n('H:i', strtotime($event->end_date)));
                            }
                            ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($event->location)) : ?>
                    <div class="info-item">
                        <span class="info-icon dashicons dashicons-location"></span>
                        <div class="info-content">
                            <span class="info-label"><?php esc_html_e('Location', 'semigapp'); ?></span>
                            <span class="info-value"><?php echo esc_html($event->location); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($event->max_attendees > 0) : ?>
                    <div class="info-item">
                        <span class="info-icon dashicons dashicons-groups"></span>
                        <div class="info-content">
                            <span class="info-label"><?php esc_html_e('Capacity', 'semigapp'); ?></span>
                            <span class="info-value">
                                <?php
                                $remaining = $event->max_attendees - $event->registration_count;
                                printf(
                                    esc_html__('%1$d / %2$d spots available', 'semigapp'),
                                    $remaining,
                                    $event->max_attendees
                                );
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($event->price) && $event->price > 0) : ?>
                    <div class="info-item">
                        <span class="info-icon dashicons dashicons-tickets-alt"></span>
                        <div class="info-content">
                            <span class="info-label"><?php esc_html_e('Price', 'semigapp'); ?></span>
                            <span class="info-value"><?php echo esc_html(number_format($event->price, 2) . ' ' . ($event->currency ?? 'SEK')); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($event->description)) : ?>
                <div class="event-description">
                    <h2><?php esc_html_e('About This Event', 'semigapp'); ?></h2>
                    <?php echo wp_kses_post($event->description); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($event->status === 'published' && $event->registration_enabled) : ?>
            <div class="event-registration">
                <?php
                $remaining = $event->max_attendees - $event->registration_count;
                $can_register = $event->max_attendees == 0 || $remaining > 0;
                $is_past = strtotime($event->start_date) < time();

                if ($is_past) :
                ?>
                    <div class="registration-closed">
                        <p><?php esc_html_e('This event has already taken place.', 'semigapp'); ?></p>
                    </div>
                <?php elseif (!$can_register) : ?>
                    <div class="registration-full">
                        <p><?php esc_html_e('This event is fully booked.', 'semigapp'); ?></p>
                    </div>
                <?php elseif ($user_registered) : ?>
                    <div class="already-registered">
                        <p><?php esc_html_e('You are registered for this event.', 'semigapp'); ?></p>
                        <a href="<?php echo esc_url(add_query_arg('action', 'cancel_registration')); ?>"
                           class="semigapp-btn semigapp-btn-secondary"
                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to cancel your registration?', 'semigapp'); ?>');">
                            <?php esc_html_e('Cancel Registration', 'semigapp'); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <a href="<?php echo esc_url(add_query_arg('action', 'register')); ?>"
                       class="semigapp-btn semigapp-btn-primary semigapp-btn-large">
                        <?php
                        if (!empty($event->price) && $event->price > 0) {
                            printf(
                                esc_html__('Register Now - %s', 'semigapp'),
                                number_format($event->price, 2) . ' ' . ($event->currency ?? 'SEK')
                            );
                        } else {
                            esc_html_e('Register Now - Free', 'semigapp');
                        }
                        ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($event->organizer)) : ?>
            <div class="event-organizer">
                <h3><?php esc_html_e('Organizer', 'semigapp'); ?></h3>
                <p><?php echo esc_html($event->organizer); ?></p>
            </div>
        <?php endif; ?>

        <footer class="event-footer">
            <a href="<?php echo esc_url(remove_query_arg('event_id')); ?>" class="semigapp-btn semigapp-btn-secondary">
                <?php esc_html_e('&larr; Back to Events', 'semigapp'); ?>
            </a>
        </footer>
    </article>
</div>
