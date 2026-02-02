<?php
/**
 * Order Confirmation Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="semigapp-order-confirmation">
    <div class="semigapp-order-success">
        <span class="success-icon">✓</span>
        <h1><?php esc_html_e('Thank you for your order!', 'semigapp'); ?></h1>
        <p class="order-number">
            <?php
            printf(
                esc_html__('Order number: %s', 'semigapp'),
                '<strong>' . esc_html($order->order_number) . '</strong>'
            );
            ?>
        </p>
    </div>

    <div class="semigapp-order-details">
        <h2><?php esc_html_e('Order Details', 'semigapp'); ?></h2>

        <table class="semigapp-order-items">
            <thead>
                <tr>
                    <th><?php esc_html_e('Product', 'semigapp'); ?></th>
                    <th><?php esc_html_e('Quantity', 'semigapp'); ?></th>
                    <th><?php esc_html_e('Total', 'semigapp'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->items as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item->product_name); ?></td>
                        <td><?php echo esc_html($item->quantity); ?></td>
                        <td><?php echo esc_html(number_format($item->total, 2) . ' SEK'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2"><?php esc_html_e('Subtotal', 'semigapp'); ?></th>
                    <td><?php echo esc_html(number_format($order->subtotal, 2) . ' SEK'); ?></td>
                </tr>
                <?php if ($order->shipping_total > 0) : ?>
                    <tr>
                        <th colspan="2"><?php esc_html_e('Shipping', 'semigapp'); ?></th>
                        <td><?php echo esc_html(number_format($order->shipping_total, 2) . ' SEK'); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($order->tax_total > 0) : ?>
                    <tr>
                        <th colspan="2"><?php esc_html_e('VAT', 'semigapp'); ?></th>
                        <td><?php echo esc_html(number_format($order->tax_total, 2) . ' SEK'); ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <th colspan="2"><?php esc_html_e('Total', 'semigapp'); ?></th>
                    <td><strong><?php echo esc_html(number_format($order->total, 2) . ' ' . $order->currency); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="semigapp-order-addresses">
        <div class="billing-address">
            <h3><?php esc_html_e('Billing Address', 'semigapp'); ?></h3>
            <?php if (!empty($order->billing_address)) : ?>
                <address>
                    <?php echo esc_html($order->billing_address['first_name'] ?? ''); ?> <?php echo esc_html($order->billing_address['last_name'] ?? ''); ?><br>
                    <?php if (!empty($order->billing_address['company'])) : ?>
                        <?php echo esc_html($order->billing_address['company']); ?><br>
                    <?php endif; ?>
                    <?php echo esc_html($order->billing_address['address_1'] ?? ''); ?><br>
                    <?php echo esc_html($order->billing_address['postcode'] ?? ''); ?> <?php echo esc_html($order->billing_address['city'] ?? ''); ?><br>
                    <?php echo esc_html($order->billing_address['country'] ?? ''); ?>
                </address>
            <?php endif; ?>
        </div>

        <?php if (!empty($order->shipping_address)) : ?>
            <div class="shipping-address">
                <h3><?php esc_html_e('Shipping Address', 'semigapp'); ?></h3>
                <address>
                    <?php echo esc_html($order->shipping_address['first_name'] ?? ''); ?> <?php echo esc_html($order->shipping_address['last_name'] ?? ''); ?><br>
                    <?php if (!empty($order->shipping_address['company'])) : ?>
                        <?php echo esc_html($order->shipping_address['company']); ?><br>
                    <?php endif; ?>
                    <?php echo esc_html($order->shipping_address['address_1'] ?? ''); ?><br>
                    <?php echo esc_html($order->shipping_address['postcode'] ?? ''); ?> <?php echo esc_html($order->shipping_address['city'] ?? ''); ?><br>
                    <?php echo esc_html($order->shipping_address['country'] ?? ''); ?>
                </address>
            </div>
        <?php endif; ?>
    </div>

    <div class="semigapp-order-status">
        <h3><?php esc_html_e('Order Status', 'semigapp'); ?></h3>
        <p>
            <span class="status-label"><?php esc_html_e('Status:', 'semigapp'); ?></span>
            <span class="status-value semigapp-status-<?php echo esc_attr($order->status); ?>">
                <?php echo esc_html(ucfirst($order->status)); ?>
            </span>
        </p>
        <p>
            <span class="status-label"><?php esc_html_e('Payment:', 'semigapp'); ?></span>
            <span class="status-value semigapp-status-<?php echo esc_attr($order->payment_status); ?>">
                <?php echo esc_html(ucfirst($order->payment_status)); ?>
            </span>
        </p>
    </div>

    <div class="semigapp-order-actions">
        <a href="<?php echo esc_url(home_url('/shop')); ?>" class="semigapp-btn semigapp-btn-primary">
            <?php esc_html_e('Continue Shopping', 'semigapp'); ?>
        </a>
        <?php if (is_user_logged_in()) : ?>
            <a href="<?php echo esc_url(home_url('/my-orders')); ?>" class="semigapp-btn semigapp-btn-secondary">
                <?php esc_html_e('View All Orders', 'semigapp'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
