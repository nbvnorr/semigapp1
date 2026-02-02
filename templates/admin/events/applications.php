<?php
/**
 * Admin Event Applications Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$events_module = \SemigApp\Plugin::get_instance()->get_module('events');
$statuses = $events_module->get_application_statuses();
?>
<div class="wrap semigapp-admin-wrap">
    <div class="semigapp-admin-header">
        <h1>
            <?php if (!empty($event)) : ?>
                <?php printf(esc_html__('Applications for: %s', 'semigapp'), esc_html($event->title)); ?>
            <?php else : ?>
                <?php esc_html_e('Event Applications', 'semigapp'); ?>
            <?php endif; ?>
        </h1>
        <?php if (!empty($event)) : ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events&action=edit&event_id=' . $event->id)); ?>" class="page-title-action">
                <?php esc_html_e('Back to Event', 'semigapp'); ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($event)) : ?>
        <!-- Application Stats -->
        <div class="semigapp-application-stats">
            <?php $counts = $events_module->get_application_counts($event->id); ?>
            <div class="semigapp-stat-item">
                <span class="semigapp-stat-count"><?php echo esc_html($counts['total']); ?></span>
                <span class="semigapp-stat-label"><?php esc_html_e('Total', 'semigapp'); ?></span>
            </div>
            <div class="semigapp-stat-item pending">
                <span class="semigapp-stat-count"><?php echo esc_html($counts['pending']); ?></span>
                <span class="semigapp-stat-label"><?php esc_html_e('Pending', 'semigapp'); ?></span>
            </div>
            <div class="semigapp-stat-item approved">
                <span class="semigapp-stat-count"><?php echo esc_html($counts['approved']); ?></span>
                <span class="semigapp-stat-label"><?php esc_html_e('Approved', 'semigapp'); ?></span>
            </div>
            <div class="semigapp-stat-item waitlisted">
                <span class="semigapp-stat-count"><?php echo esc_html($counts['waitlisted']); ?></span>
                <span class="semigapp-stat-label"><?php esc_html_e('Waitlisted', 'semigapp'); ?></span>
            </div>
            <div class="semigapp-stat-item rejected">
                <span class="semigapp-stat-count"><?php echo esc_html($counts['rejected']); ?></span>
                <span class="semigapp-stat-label"><?php esc_html_e('Rejected', 'semigapp'); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="semigapp-admin-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="semigapp-events">
            <input type="hidden" name="action" value="applications">
            <?php if (!empty($event)) : ?>
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
            <?php endif; ?>

            <select name="status">
                <option value=""><?php esc_html_e('All Statuses', 'semigapp'); ?></option>
                <?php foreach ($statuses as $status_key => $status_label) : ?>
                    <option value="<?php echo esc_attr($status_key); ?>" <?php selected($current_status ?? '', $status_key); ?>>
                        <?php echo esc_html($status_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="search" placeholder="<?php esc_attr_e('Search by name or email...', 'semigapp'); ?>"
                   value="<?php echo esc_attr($search ?? ''); ?>">

            <button type="submit" class="button"><?php esc_html_e('Filter', 'semigapp'); ?></button>
        </form>
    </div>

    <!-- Applications Table -->
    <div class="semigapp-admin-card">
        <?php if (empty($applications)) : ?>
            <div class="semigapp-empty-state">
                <span class="dashicons dashicons-clipboard"></span>
                <p><?php esc_html_e('No applications found.', 'semigapp'); ?></p>
            </div>
        <?php else : ?>
            <table class="semigapp-admin-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Applicant', 'semigapp'); ?></th>
                        <?php if (empty($event)) : ?>
                            <th><?php esc_html_e('Event', 'semigapp'); ?></th>
                        <?php endif; ?>
                        <th><?php esc_html_e('Attendees', 'semigapp'); ?></th>
                        <th><?php esc_html_e('Status', 'semigapp'); ?></th>
                        <th><?php esc_html_e('Applied', 'semigapp'); ?></th>
                        <th><?php esc_html_e('Actions', 'semigapp'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $application) : ?>
                        <tr data-application-id="<?php echo esc_attr($application->id); ?>">
                            <td>
                                <strong><?php echo esc_html($application->applicant_name); ?></strong>
                                <br>
                                <a href="mailto:<?php echo esc_attr($application->applicant_email); ?>">
                                    <?php echo esc_html($application->applicant_email); ?>
                                </a>
                                <?php if (!empty($application->applicant_phone)) : ?>
                                    <br>
                                    <span class="semigapp-text-muted"><?php echo esc_html($application->applicant_phone); ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if (empty($event)) : ?>
                                <td>
                                    <?php if ($application->event) : ?>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events&action=applications&event_id=' . $application->event->id)); ?>">
                                            <?php echo esc_html($application->event->title); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="semigapp-text-muted"><?php esc_html_e('Event deleted', 'semigapp'); ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td><?php echo esc_html($application->attendees); ?></td>
                            <td>
                                <span class="semigapp-status semigapp-status-<?php echo esc_attr($application->status); ?>">
                                    <?php echo esc_html($statuses[$application->status] ?? $application->status); ?>
                                </span>
                                <?php if ($application->status === 'waitlisted' && $application->waitlist_position) : ?>
                                    <br><small>#<?php echo esc_html($application->waitlist_position); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($application->created_at))); ?>
                            </td>
                            <td class="semigapp-actions-cell">
                                <button type="button" class="button button-small semigapp-view-application"
                                        data-application-id="<?php echo esc_attr($application->id); ?>">
                                    <?php esc_html_e('View', 'semigapp'); ?>
                                </button>

                                <?php if ($application->status === 'pending') : ?>
                                    <button type="button" class="button button-small button-primary semigapp-approve-application"
                                            data-application-id="<?php echo esc_attr($application->id); ?>">
                                        <?php esc_html_e('Approve', 'semigapp'); ?>
                                    </button>
                                    <button type="button" class="button button-small semigapp-reject-application"
                                            data-application-id="<?php echo esc_attr($application->id); ?>">
                                        <?php esc_html_e('Reject', 'semigapp'); ?>
                                    </button>
                                    <button type="button" class="button button-small semigapp-waitlist-application"
                                            data-application-id="<?php echo esc_attr($application->id); ?>">
                                        <?php esc_html_e('Waitlist', 'semigapp'); ?>
                                    </button>
                                <?php elseif ($application->status === 'waitlisted') : ?>
                                    <button type="button" class="button button-small button-primary semigapp-approve-application"
                                            data-application-id="<?php echo esc_attr($application->id); ?>">
                                        <?php esc_html_e('Approve', 'semigapp'); ?>
                                    </button>
                                    <button type="button" class="button button-small semigapp-reject-application"
                                            data-application-id="<?php echo esc_attr($application->id); ?>">
                                        <?php esc_html_e('Reject', 'semigapp'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($pagination)) : ?>
                <div class="semigapp-pagination">
                    <?php echo $pagination; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Application Detail Modal -->
<div id="semigapp-application-modal" class="semigapp-modal" style="display: none;">
    <div class="semigapp-modal-content">
        <div class="semigapp-modal-header">
            <h2><?php esc_html_e('Application Details', 'semigapp'); ?></h2>
            <button type="button" class="semigapp-modal-close">&times;</button>
        </div>
        <div class="semigapp-modal-body">
            <!-- Content loaded via AJAX -->
        </div>
        <div class="semigapp-modal-footer">
            <button type="button" class="button semigapp-modal-close"><?php esc_html_e('Close', 'semigapp'); ?></button>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div id="semigapp-reject-modal" class="semigapp-modal" style="display: none;">
    <div class="semigapp-modal-content semigapp-modal-sm">
        <div class="semigapp-modal-header">
            <h2><?php esc_html_e('Reject Application', 'semigapp'); ?></h2>
            <button type="button" class="semigapp-modal-close">&times;</button>
        </div>
        <div class="semigapp-modal-body">
            <input type="hidden" id="reject-application-id" value="">
            <div class="semigapp-form-group">
                <label for="rejection-reason"><?php esc_html_e('Reason for Rejection (optional)', 'semigapp'); ?></label>
                <textarea id="rejection-reason" class="widefat" rows="4" placeholder="<?php esc_attr_e('Enter a reason to include in the notification email...', 'semigapp'); ?>"></textarea>
            </div>
        </div>
        <div class="semigapp-modal-footer">
            <button type="button" class="button semigapp-modal-close"><?php esc_html_e('Cancel', 'semigapp'); ?></button>
            <button type="button" class="button button-primary" id="confirm-reject"><?php esc_html_e('Reject Application', 'semigapp'); ?></button>
        </div>
    </div>
</div>

<style>
.semigapp-application-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.semigapp-stat-item {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    text-align: center;
    min-width: 100px;
}

.semigapp-stat-item.pending { border-left: 4px solid #f59e0b; }
.semigapp-stat-item.approved { border-left: 4px solid #10b981; }
.semigapp-stat-item.waitlisted { border-left: 4px solid #6366f1; }
.semigapp-stat-item.rejected { border-left: 4px solid #ef4444; }

.semigapp-stat-count {
    display: block;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
}

.semigapp-stat-label {
    display: block;
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.semigapp-admin-filters {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.semigapp-admin-filters form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.semigapp-admin-filters select,
.semigapp-admin-filters input[type="text"] {
    min-width: 200px;
}

.semigapp-actions-cell {
    white-space: nowrap;
}

.semigapp-actions-cell .button {
    margin-right: 4px;
}

/* Modal Styles */
.semigapp-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.semigapp-modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow: auto;
}

.semigapp-modal-sm {
    max-width: 400px;
}

.semigapp-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.semigapp-modal-header h2 {
    margin: 0;
    font-size: 1.1rem;
}

.semigapp-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    line-height: 1;
}

.semigapp-modal-body {
    padding: 1.5rem;
}

.semigapp-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
}
</style>

<script>
jQuery(document).ready(function($) {
    // View Application
    $('.semigapp-view-application').on('click', function() {
        var applicationId = $(this).data('application-id');
        var $modal = $('#semigapp-application-modal');
        var $body = $modal.find('.semigapp-modal-body');

        $body.html('<p style="text-align: center;"><?php esc_attr_e('Loading...', 'semigapp'); ?></p>');
        $modal.show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'semigapp_get_application_details',
                nonce: semigapp_admin.nonce,
                application_id: applicationId
            },
            success: function(response) {
                if (response.success) {
                    $body.html(response.data.html);
                } else {
                    $body.html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $body.html('<p class="error"><?php esc_attr_e('Failed to load application details.', 'semigapp'); ?></p>');
            }
        });
    });

    // Close Modal
    $('.semigapp-modal-close').on('click', function() {
        $(this).closest('.semigapp-modal').hide();
    });

    // Approve Application
    $('.semigapp-approve-application').on('click', function() {
        if (!confirm('<?php esc_attr_e('Are you sure you want to approve this application?', 'semigapp'); ?>')) {
            return;
        }

        var $button = $(this);
        var applicationId = $button.data('application-id');

        $button.prop('disabled', true).text('<?php esc_attr_e('Processing...', 'semigapp'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'semigapp_application_action',
                nonce: semigapp_admin.nonce,
                application_action: 'approve',
                application_id: applicationId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    $button.prop('disabled', false).text('<?php esc_attr_e('Approve', 'semigapp'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_attr_e('An error occurred. Please try again.', 'semigapp'); ?>');
                $button.prop('disabled', false).text('<?php esc_attr_e('Approve', 'semigapp'); ?>');
            }
        });
    });

    // Open Reject Modal
    $('.semigapp-reject-application').on('click', function() {
        var applicationId = $(this).data('application-id');
        $('#reject-application-id').val(applicationId);
        $('#rejection-reason').val('');
        $('#semigapp-reject-modal').show();
    });

    // Confirm Reject
    $('#confirm-reject').on('click', function() {
        var $button = $(this);
        var applicationId = $('#reject-application-id').val();
        var reason = $('#rejection-reason').val();

        $button.prop('disabled', true).text('<?php esc_attr_e('Processing...', 'semigapp'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'semigapp_application_action',
                nonce: semigapp_admin.nonce,
                application_action: 'reject',
                application_id: applicationId,
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    $button.prop('disabled', false).text('<?php esc_attr_e('Reject Application', 'semigapp'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_attr_e('An error occurred. Please try again.', 'semigapp'); ?>');
                $button.prop('disabled', false).text('<?php esc_attr_e('Reject Application', 'semigapp'); ?>');
            }
        });
    });

    // Waitlist Application
    $('.semigapp-waitlist-application').on('click', function() {
        if (!confirm('<?php esc_attr_e('Are you sure you want to add this application to the waitlist?', 'semigapp'); ?>')) {
            return;
        }

        var $button = $(this);
        var applicationId = $button.data('application-id');

        $button.prop('disabled', true).text('<?php esc_attr_e('Processing...', 'semigapp'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'semigapp_application_action',
                nonce: semigapp_admin.nonce,
                application_action: 'waitlist',
                application_id: applicationId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    $button.prop('disabled', false).text('<?php esc_attr_e('Waitlist', 'semigapp'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_attr_e('An error occurred. Please try again.', 'semigapp'); ?>');
                $button.prop('disabled', false).text('<?php esc_attr_e('Waitlist', 'semigapp'); ?>');
            }
        });
    });

    // Close modal on outside click
    $('.semigapp-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
});
</script>
