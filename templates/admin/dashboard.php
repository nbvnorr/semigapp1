<?php
/**
 * Admin Dashboard Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap semigapp-admin-wrap">
    <div class="semigapp-admin-header">
        <h1><?php esc_html_e('SemigApp Dashboard', 'semigapp'); ?></h1>
    </div>

    <!-- Stats Grid -->
    <div class="semigapp-dashboard-stats">
        <div class="semigapp-stat-card primary">
            <h3><?php esc_html_e('Active Projects', 'semigapp'); ?></h3>
            <div class="semigapp-stat-value"><?php echo esc_html($stats['projects']['active']); ?></div>
            <div class="semigapp-stat-subtitle">
                <?php printf(esc_html__('%d total projects', 'semigapp'), $stats['projects']['total']); ?>
            </div>
        </div>

        <div class="semigapp-stat-card success">
            <h3><?php esc_html_e('Tasks Completed', 'semigapp'); ?></h3>
            <div class="semigapp-stat-value"><?php echo esc_html($stats['tasks']['completed']); ?></div>
            <div class="semigapp-stat-subtitle">
                <?php printf(esc_html__('%d pending', 'semigapp'), $stats['tasks']['pending']); ?>
            </div>
        </div>

        <div class="semigapp-stat-card warning">
            <h3><?php esc_html_e('Upcoming Events', 'semigapp'); ?></h3>
            <div class="semigapp-stat-value"><?php echo esc_html($stats['events']['upcoming']); ?></div>
            <div class="semigapp-stat-subtitle">
                <?php printf(esc_html__('%d total events', 'semigapp'), $stats['events']['total']); ?>
            </div>
        </div>

        <div class="semigapp-stat-card">
            <h3><?php esc_html_e('Active Members', 'semigapp'); ?></h3>
            <div class="semigapp-stat-value"><?php echo esc_html($stats['members']['active']); ?></div>
            <div class="semigapp-stat-subtitle">
                <?php printf(esc_html__('%d total members', 'semigapp'), $stats['members']['total']); ?>
            </div>
        </div>

        <div class="semigapp-stat-card success">
            <h3><?php esc_html_e('Total Revenue', 'semigapp'); ?></h3>
            <div class="semigapp-stat-value">
                <?php echo esc_html(\SemigApp\Settings::get_instance()->format_price($stats['orders']['revenue'])); ?>
            </div>
            <div class="semigapp-stat-subtitle">
                <?php printf(esc_html__('%d orders', 'semigapp'), $stats['orders']['total']); ?>
            </div>
        </div>

        <div class="semigapp-stat-card primary">
            <h3><?php esc_html_e('Newsletter Subscribers', 'semigapp'); ?></h3>
            <div class="semigapp-stat-value"><?php echo esc_html($stats['subscribers']['active']); ?></div>
            <div class="semigapp-stat-subtitle">
                <?php printf(esc_html__('%d total', 'semigapp'), $stats['subscribers']['total']); ?>
            </div>
        </div>
    </div>

    <div class="semigapp-dashboard-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Quick Actions -->
        <div class="semigapp-admin-card">
            <div class="semigapp-admin-card-header">
                <h2><?php esc_html_e('Quick Actions', 'semigapp'); ?></h2>
            </div>
            <div class="semigapp-admin-card-body">
                <div class="semigapp-quick-stats">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-projects&action=new')); ?>" class="semigapp-quick-stat">
                        <div class="semigapp-quick-stat-icon projects">
                            <span class="dashicons dashicons-clipboard"></span>
                        </div>
                        <div class="semigapp-quick-stat-info">
                            <span><?php esc_html_e('New Project', 'semigapp'); ?></span>
                        </div>
                    </a>

                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events&action=new')); ?>" class="semigapp-quick-stat">
                        <div class="semigapp-quick-stat-icon events">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="semigapp-quick-stat-info">
                            <span><?php esc_html_e('New Event', 'semigapp'); ?></span>
                        </div>
                    </a>

                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-shop&action=new')); ?>" class="semigapp-quick-stat">
                        <div class="semigapp-quick-stat-icon orders">
                            <span class="dashicons dashicons-cart"></span>
                        </div>
                        <div class="semigapp-quick-stat-info">
                            <span><?php esc_html_e('New Product', 'semigapp'); ?></span>
                        </div>
                    </a>

                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-newsletter&action=new-campaign')); ?>" class="semigapp-quick-stat">
                        <div class="semigapp-quick-stat-icon members">
                            <span class="dashicons dashicons-email"></span>
                        </div>
                        <div class="semigapp-quick-stat-info">
                            <span><?php esc_html_e('New Campaign', 'semigapp'); ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="semigapp-admin-card">
            <div class="semigapp-admin-card-header">
                <h2><?php esc_html_e('Recent Orders', 'semigapp'); ?></h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-orders')); ?>" class="button button-small">
                    <?php esc_html_e('View All', 'semigapp'); ?>
                </a>
            </div>
            <div class="semigapp-admin-card-body">
                <?php
                $plugin = \SemigApp\Plugin::get_instance();
                $webshop = $plugin->get_module('webshop');
                $recent_orders = $webshop->get_orders(array('limit' => 5));

                if (empty($recent_orders)) :
                ?>
                    <p class="semigapp-empty-state-simple"><?php esc_html_e('No orders yet.', 'semigapp'); ?></p>
                <?php else : ?>
                    <table class="semigapp-admin-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Order', 'semigapp'); ?></th>
                                <th><?php esc_html_e('Total', 'semigapp'); ?></th>
                                <th><?php esc_html_e('Status', 'semigapp'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order) : ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-orders&action=view&order_id=' . $order->id)); ?>">
                                            #<?php echo esc_html($order->order_number); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html(\SemigApp\Settings::get_instance()->format_price($order->total)); ?></td>
                                    <td>
                                        <span class="semigapp-status semigapp-status-<?php echo esc_attr($order->status); ?>">
                                            <?php echo esc_html(ucfirst($order->status)); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
