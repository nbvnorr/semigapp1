<?php
/**
 * Events List Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="semigapp-events-list">
    <?php if (empty($events)) : ?>
        <div class="semigapp-no-events">
            <p><?php esc_html_e('No events found.', 'semigapp'); ?></p>
        </div>
    <?php else : ?>
        <div class="semigapp-events-grid columns-<?php echo esc_attr($columns ?? 2); ?>">
            <?php foreach ($events as $event) : ?>
                <article class="semigapp-event-card" data-event-id="<?php echo esc_attr($event->id); ?>">
                    <?php if (!empty($event->featured_image)) : ?>
                        <div class="event-image">
                            <?php echo wp_get_attachment_image($event->featured_image, 'medium'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="event-content">
                        <div class="event-date">
                            <span class="event-month"><?php echo esc_html(date_i18n('M', strtotime($event->start_date))); ?></span>
                            <span class="event-day"><?php echo esc_html(date_i18n('d', strtotime($event->start_date))); ?></span>
                        </div>

                        <h3 class="event-title">
                            <a href="<?php echo esc_url(add_query_arg('event_id', $event->id)); ?>">
                                <?php echo esc_html($event->title); ?>
                            </a>
                        </h3>

                        <div class="event-meta">
                            <span class="event-time">
                                <?php echo esc_html(date_i18n('H:i', strtotime($event->start_date))); ?>
                                <?php if ($event->end_date) : ?>
                                    - <?php echo esc_html(date_i18n('H:i', strtotime($event->end_date))); ?>
                                <?php endif; ?>
                            </span>

                            <?php if (!empty($event->location)) : ?>
                                <span class="event-location"><?php echo esc_html($event->location); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($event->excerpt)) : ?>
                            <div class="event-excerpt">
                                <?php echo wp_kses_post($event->excerpt); ?>
                            </div>
                        <?php endif; ?>

                        <div class="event-footer">
                            <?php if ($event->max_attendees > 0) : ?>
                                <span class="event-capacity">
                                    <?php
                                    $remaining = $event->max_attendees - $event->registration_count;
                                    if ($remaining > 0) {
                                        printf(esc_html__('%d spots left', 'semigapp'), $remaining);
                                    } else {
                                        esc_html_e('Fully booked', 'semigapp');
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>

                            <a href="<?php echo esc_url(add_query_arg('event_id', $event->id)); ?>"
                               class="semigapp-btn semigapp-btn-small">
                                <?php esc_html_e('View Details', 'semigapp'); ?>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
