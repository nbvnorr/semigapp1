<?php
/**
 * Shopping Cart Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="semigapp-cart">
    <h1 class="semigapp-page-title"><?php esc_html_e('Shopping Cart', 'semigapp'); ?></h1>

    <?php if (empty($cart)) : ?>
        <div class="semigapp-empty-cart">
            <p><?php esc_html_e('Your cart is empty.', 'semigapp'); ?></p>
            <a href="<?php echo esc_url(home_url('/shop')); ?>" class="semigapp-btn semigapp-btn-primary">
                <?php esc_html_e('Continue Shopping', 'semigapp'); ?>
            </a>
        </div>
    <?php else : ?>
        <form class="semigapp-cart-form">
            <?php wp_nonce_field('semigapp_frontend', 'nonce'); ?>

            <table class="semigapp-cart-table">
                <thead>
                    <tr>
                        <th class="product-thumbnail">&nbsp;</th>
                        <th class="product-name"><?php esc_html_e('Product', 'semigapp'); ?></th>
                        <th class="product-price"><?php esc_html_e('Price', 'semigapp'); ?></th>
                        <th class="product-quantity"><?php esc_html_e('Quantity', 'semigapp'); ?></th>
                        <th class="product-subtotal"><?php esc_html_e('Subtotal', 'semigapp'); ?></th>
                        <th class="product-remove">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $cart_key => $item) : ?>
                        <tr class="semigapp-cart-item" data-cart-key="<?php echo esc_attr($cart_key); ?>">
                            <td class="product-thumbnail">
                                <?php if ($item['product']->featured_image) : ?>
                                    <?php echo wp_get_attachment_image($item['product']->featured_image, 'thumbnail'); ?>
                                <?php else : ?>
                                    <span class="placeholder-icon">📦</span>
                                <?php endif; ?>
                            </td>
                            <td class="product-name">
                                <a href="<?php echo esc_url(add_query_arg('product', $item['product']->slug, home_url('/shop'))); ?>">
                                    <?php echo esc_html($item['product']->name); ?>
                                </a>
                            </td>
                            <td class="product-price">
                                <?php echo esc_html($item['product']->current_price_formatted); ?>
                            </td>
                            <td class="product-quantity">
                                <input type="number"
                                       class="semigapp-quantity-input semigapp-update-quantity"
                                       data-cart-key="<?php echo esc_attr($cart_key); ?>"
                                       value="<?php echo esc_attr($item['quantity']); ?>"
                                       min="1"
                                       max="99">
                            </td>
                            <td class="product-subtotal">
                                <?php echo esc_html(number_format($item['subtotal'], 2) . ' SEK'); ?>
                            </td>
                            <td class="product-remove">
                                <button type="button"
                                        class="semigapp-remove-item"
                                        data-cart-key="<?php echo esc_attr($cart_key); ?>"
                                        aria-label="<?php esc_attr_e('Remove item', 'semigapp'); ?>">
                                    ✕
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="semigapp-cart-totals">
                <table class="semigapp-totals-table">
                    <tr class="subtotal-row">
                        <th><?php esc_html_e('Subtotal', 'semigapp'); ?></th>
                        <td><?php echo esc_html($totals['subtotal_formatted']); ?></td>
                    </tr>
                    <?php if ($totals['shipping'] > 0) : ?>
                        <tr class="shipping-row">
                            <th><?php esc_html_e('Shipping', 'semigapp'); ?></th>
                            <td><?php echo esc_html($totals['shipping_formatted']); ?></td>
                        </tr>
                    <?php else : ?>
                        <tr class="shipping-row free-shipping">
                            <th><?php esc_html_e('Shipping', 'semigapp'); ?></th>
                            <td><?php esc_html_e('Free', 'semigapp'); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($totals['tax_total'] > 0) : ?>
                        <tr class="tax-row">
                            <th><?php esc_html_e('VAT (25%)', 'semigapp'); ?></th>
                            <td><?php echo esc_html($totals['tax_total_formatted']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <th><?php esc_html_e('Total', 'semigapp'); ?></th>
                        <td class="total-amount"><?php echo esc_html($totals['total_formatted']); ?></td>
                    </tr>
                </table>
            </div>

            <div class="semigapp-cart-actions">
                <a href="<?php echo esc_url(home_url('/shop')); ?>" class="semigapp-btn semigapp-btn-secondary">
                    <?php esc_html_e('Continue Shopping', 'semigapp'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/checkout')); ?>" class="semigapp-btn semigapp-btn-primary">
                    <?php esc_html_e('Proceed to Checkout', 'semigapp'); ?>
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>
