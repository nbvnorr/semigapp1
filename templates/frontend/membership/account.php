<?php
/**
 * Membership Account Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="semigapp-membership-account">
    <h2 class="semigapp-section-title"><?php esc_html_e('My Memberships', 'semigapp'); ?></h2>

    <?php if (empty($memberships)) : ?>
        <div class="semigapp-no-memberships">
            <p><?php esc_html_e('You do not have any active memberships.', 'semigapp'); ?></p>
            <a href="<?php echo esc_url(home_url('/membership')); ?>" class="semigapp-btn semigapp-btn-primary">
                <?php esc_html_e('View Membership Options', 'semigapp'); ?>
            </a>
        </div>
    <?php else : ?>
        <?php foreach ($memberships as $membership) : ?>
            <div class="semigapp-membership-card" data-member-id="<?php echo esc_attr($membership->id); ?>">
                <div class="semigapp-membership-header">
                    <h3><?php echo esc_html($membership->level->name ?? __('Unknown Level', 'semigapp')); ?></h3>
                    <span class="semigapp-status semigapp-status-<?php echo esc_attr($membership->status); ?>">
                        <?php echo esc_html(ucfirst($membership->status)); ?>
                    </span>
                </div>

                <div class="semigapp-membership-details">
                    <div class="detail-row">
                        <span class="detail-label"><?php esc_html_e('Start Date:', 'semigapp'); ?></span>
                        <span class="detail-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($membership->start_date))); ?></span>
                    </div>

                    <?php if ($membership->end_date) : ?>
                        <div class="detail-row">
                            <span class="detail-label"><?php esc_html_e('Expires:', 'semigapp'); ?></span>
                            <span class="detail-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($membership->end_date))); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($membership->auto_renew) : ?>
                        <div class="detail-row">
                            <span class="detail-label"><?php esc_html_e('Auto Renew:', 'semigapp'); ?></span>
                            <span class="detail-value"><?php esc_html_e('Enabled', 'semigapp'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($membership->payments)) : ?>
                    <div class="semigapp-membership-payments">
                        <h4><?php esc_html_e('Payment History', 'semigapp'); ?></h4>
                        <table class="semigapp-payments-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'semigapp'); ?></th>
                                    <th><?php esc_html_e('Amount', 'semigapp'); ?></th>
                                    <th><?php esc_html_e('Status', 'semigapp'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($membership->payments as $payment) : ?>
                                    <tr>
                                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($payment->created_at))); ?></td>
                                        <td><?php echo esc_html(number_format($payment->amount, 2) . ' ' . $payment->currency); ?></td>
                                        <td>
                                            <span class="semigapp-status semigapp-status-<?php echo esc_attr($payment->status); ?>">
                                                <?php echo esc_html(ucfirst($payment->status)); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($membership->status === 'active') : ?>
                    <div class="semigapp-membership-actions">
                        <button type="button"
                                class="semigapp-btn semigapp-btn-secondary semigapp-cancel-membership"
                                data-member-id="<?php echo esc_attr($membership->id); ?>">
                            <?php esc_html_e('Cancel Membership', 'semigapp'); ?>
                        </button>
                    </div>
                <?php elseif ($membership->status === 'expired') : ?>
                    <div class="semigapp-membership-actions">
                        <button type="button"
                                class="semigapp-btn semigapp-btn-primary semigapp-renew-membership"
                                data-member-id="<?php echo esc_attr($membership->id); ?>">
                            <?php esc_html_e('Renew Membership', 'semigapp'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
