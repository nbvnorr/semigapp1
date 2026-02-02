<?php
/**
 * My Applications Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="semigapp-my-applications">
    <h3><?php esc_html_e('My Event Applications', 'semigapp'); ?></h3>

    <?php if (empty($applications)) : ?>
        <div class="semigapp-empty-state">
            <span class="dashicons dashicons-clipboard"></span>
            <p><?php esc_html_e('You have not submitted any event applications yet.', 'semigapp'); ?></p>
            <a href="<?php echo esc_url(get_post_type_archive_link('semigapp_event') ?: home_url('/events/')); ?>" class="semigapp-button">
                <?php esc_html_e('Browse Events', 'semigapp'); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="semigapp-applications-list">
            <?php foreach ($applications as $application) : ?>
                <div class="semigapp-application-card">
                    <div class="semigapp-application-header">
                        <h4><?php echo esc_html($application->event ? $application->event->title : __('Event Not Found', 'semigapp')); ?></h4>
                        <span class="semigapp-status semigapp-status-<?php echo esc_attr($application->status); ?>">
                            <?php echo esc_html($statuses[$application->status] ?? $application->status); ?>
                        </span>
                    </div>

                    <?php if ($application->event) : ?>
                        <div class="semigapp-application-meta">
                            <span class="semigapp-meta-item">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($application->event->start_date))); ?>
                            </span>
                            <?php if (!empty($application->event->location)) : ?>
                                <span class="semigapp-meta-item">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($application->event->location); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="semigapp-application-details">
                        <p class="semigapp-text-muted">
                            <?php printf(
                                esc_html__('Applied on %s for %d attendee(s)', 'semigapp'),
                                date_i18n(get_option('date_format'), strtotime($application->created_at)),
                                $application->attendees
                            ); ?>
                        </p>

                        <?php if ($application->status === 'waitlisted' && $application->waitlist_position) : ?>
                            <p class="semigapp-waitlist-position">
                                <?php printf(esc_html__('Waitlist position: #%d', 'semigapp'), $application->waitlist_position); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($application->status === 'rejected' && !empty($application->rejection_reason)) : ?>
                            <div class="semigapp-rejection-reason">
                                <strong><?php esc_html_e('Reason:', 'semigapp'); ?></strong>
                                <p><?php echo esc_html($application->rejection_reason); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($application->status === 'approved' && $application->event && $application->event->price > 0) : ?>
                            <?php if ($application->payment_status === 'pending') : ?>
                                <div class="semigapp-payment-notice">
                                    <p><?php esc_html_e('Your application has been approved! Please complete payment to confirm your spot.', 'semigapp'); ?></p>
                                    <a href="<?php echo esc_url(add_query_arg('pay_application', $application->id, home_url('/checkout/'))); ?>" class="semigapp-button semigapp-button-primary">
                                        <?php printf(esc_html__('Pay %s', 'semigapp'), \SemigApp\Settings::get_instance()->format_price($application->event->price)); ?>
                                    </a>
                                </div>
                            <?php else : ?>
                                <p class="semigapp-payment-complete">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Payment completed', 'semigapp'); ?>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($application->status === 'pending') : ?>
                        <div class="semigapp-application-actions">
                            <button type="button" class="semigapp-button semigapp-button-outline semigapp-button-sm semigapp-cancel-application"
                                    data-application-id="<?php echo esc_attr($application->id); ?>">
                                <?php esc_html_e('Cancel Application', 'semigapp'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.semigapp-applications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.semigapp-application-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
}

.semigapp-application-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.semigapp-application-header h4 {
    margin: 0;
    font-size: 1.1rem;
}

.semigapp-application-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    color: #6b7280;
    font-size: 0.9rem;
}

.semigapp-meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.semigapp-meta-item .dashicons {
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

.semigapp-waitlist-position {
    color: #f59e0b;
    font-weight: 500;
}

.semigapp-rejection-reason {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 4px;
    padding: 0.75rem;
    margin-top: 0.5rem;
}

.semigapp-rejection-reason strong {
    color: #dc2626;
}

.semigapp-payment-notice {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 4px;
    padding: 1rem;
    margin-top: 0.5rem;
}

.semigapp-payment-complete {
    color: #059669;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.semigapp-application-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.semigapp-cancel-application').on('click', function() {
        if (!confirm('<?php esc_attr_e('Are you sure you want to cancel this application?', 'semigapp'); ?>')) {
            return;
        }

        var $button = $(this);
        var applicationId = $button.data('application-id');

        $button.prop('disabled', true).text('<?php esc_attr_e('Cancelling...', 'semigapp'); ?>');

        $.ajax({
            url: semigapp_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'semigapp_event_apply',
                nonce: semigapp_frontend.nonce,
                cancel_application: applicationId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    $button.prop('disabled', false).text('<?php esc_attr_e('Cancel Application', 'semigapp'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_attr_e('An error occurred. Please try again.', 'semigapp'); ?>');
                $button.prop('disabled', false).text('<?php esc_attr_e('Cancel Application', 'semigapp'); ?>');
            }
        });
    });
});
</script>
