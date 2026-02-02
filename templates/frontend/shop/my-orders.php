<?php
/**
 * My Orders Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="semigapp-my-orders">
    <h1 class="semigapp-page-title"><?php esc_html_e('My Orders', 'semigapp'); ?></h1>

    <?php if (empty($orders)) : ?>
        <div class="semigapp-no-orders">
            <p><?php esc_html_e('You have not placed any orders yet.', 'semigapp'); ?></p>
            <a href="<?php echo esc_url(home_url('/shop')); ?>" class="semigapp-btn semigapp-btn-primary">
                <?php esc_html_e('Start Shopping', 'semigapp'); ?>
            </a>
        </div>
    <?php else : ?>
        <table class="semigapp-orders-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Order', 'semigapp'); ?></th>
                    <th><?php esc_html_e('Date', 'semigapp'); ?></th>
                    <th><?php esc_html_e('Status', 'semigapp'); ?></th>
                    <th><?php esc_html_e('Total', 'semigapp'); ?></th>
                    <th><?php esc_html_e('Actions', 'semigapp'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : ?>
                    <tr class="order-row" data-order-id="<?php echo esc_attr($order->id); ?>">
                        <td class="order-number">
                            <a href="<?php echo esc_url(add_query_arg('order_id', $order->id, home_url('/order-confirmation'))); ?>">
                                <?php echo esc_html($order->order_number); ?>
                            </a>
                        </td>
                        <td class="order-date">
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($order->created_at))); ?>
                        </td>
                        <td class="order-status">
                            <span class="semigapp-status semigapp-status-<?php echo esc_attr($order->status); ?>">
                                <?php echo esc_html(ucfirst($order->status)); ?>
                            </span>
                        </td>
                        <td class="order-total">
                            <?php echo esc_html(number_format($order->total, 2) . ' ' . $order->currency); ?>
                        </td>
                        <td class="order-actions">
                            <a href="<?php echo esc_url(add_query_arg('order_id', $order->id, home_url('/order-confirmation'))); ?>"
                               class="semigapp-btn semigapp-btn-small">
                                <?php esc_html_e('View', 'semigapp'); ?>
                            </a>
                        </td>
                    </tr>
                    <tr class="order-items-row">
                        <td colspan="5">
                            <div class="order-items-summary">
                                <?php
                                $item_names = array();
                                foreach ($order->items as $item) {
                                    $item_names[] = esc_html($item->product_name) . ' × ' . esc_html($item->quantity);
                                }
                                echo implode(', ', $item_names);
                                ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
