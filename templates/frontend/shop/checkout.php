<?php
/**
 * Checkout Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="semigapp-checkout">
    <h1 class="semigapp-page-title"><?php esc_html_e('Checkout', 'semigapp'); ?></h1>

    <form id="semigapp-checkout-form" class="semigapp-checkout-form">
        <?php wp_nonce_field('semigapp_frontend', 'nonce'); ?>

        <div class="semigapp-checkout-columns">
            <div class="semigapp-checkout-billing">
                <h2><?php esc_html_e('Billing Details', 'semigapp'); ?></h2>

                <div class="form-row form-row-wide">
                    <label for="billing_email"><?php esc_html_e('Email', 'semigapp'); ?> <span class="required">*</span></label>
                    <input type="email"
                           id="billing_email"
                           name="billing_address[email]"
                           value="<?php echo esc_attr($current_user->user_email ?? ''); ?>"
                           required>
                </div>

                <div class="form-row form-row-first">
                    <label for="billing_first_name"><?php esc_html_e('First Name', 'semigapp'); ?> <span class="required">*</span></label>
                    <input type="text"
                           id="billing_first_name"
                           name="billing_address[first_name]"
                           value="<?php echo esc_attr($current_user->first_name ?? ''); ?>"
                           required>
                </div>

                <div class="form-row form-row-last">
                    <label for="billing_last_name"><?php esc_html_e('Last Name', 'semigapp'); ?> <span class="required">*</span></label>
                    <input type="text"
                           id="billing_last_name"
                           name="billing_address[last_name]"
                           value="<?php echo esc_attr($current_user->last_name ?? ''); ?>"
                           required>
                </div>

                <div class="form-row form-row-wide">
                    <label for="billing_company"><?php esc_html_e('Company (optional)', 'semigapp'); ?></label>
                    <input type="text" id="billing_company" name="billing_address[company]">
                </div>

                <div class="form-row form-row-wide">
                    <label for="billing_address_1"><?php esc_html_e('Street Address', 'semigapp'); ?> <span class="required">*</span></label>
                    <input type="text"
                           id="billing_address_1"
                           name="billing_address[address_1]"
                           placeholder="<?php esc_attr_e('Street address', 'semigapp'); ?>"
                           required>
                </div>

                <div class="form-row form-row-first">
                    <label for="billing_postcode"><?php esc_html_e('Postcode', 'semigapp'); ?> <span class="required">*</span></label>
                    <input type="text" id="billing_postcode" name="billing_address[postcode]" required>
                </div>

                <div class="form-row form-row-last">
                    <label for="billing_city"><?php esc_html_e('City', 'semigapp'); ?> <span class="required">*</span></label>
                    <input type="text" id="billing_city" name="billing_address[city]" required>
                </div>

                <div class="form-row form-row-wide">
                    <label for="billing_country"><?php esc_html_e('Country', 'semigapp'); ?> <span class="required">*</span></label>
                    <select id="billing_country" name="billing_address[country]" required>
                        <option value="SE" selected><?php esc_html_e('Sweden', 'semigapp'); ?></option>
                        <option value="NO"><?php esc_html_e('Norway', 'semigapp'); ?></option>
                        <option value="DK"><?php esc_html_e('Denmark', 'semigapp'); ?></option>
                        <option value="FI"><?php esc_html_e('Finland', 'semigapp'); ?></option>
                    </select>
                </div>

                <div class="form-row form-row-wide">
                    <label for="billing_phone"><?php esc_html_e('Phone', 'semigapp'); ?> <span class="required">*</span></label>
                    <input type="tel" id="billing_phone" name="billing_address[phone]" required>
                </div>

                <div class="form-row form-row-wide">
                    <label for="customer_note"><?php esc_html_e('Order Notes (optional)', 'semigapp'); ?></label>
                    <textarea id="customer_note" name="customer_note" rows="3"
                              placeholder="<?php esc_attr_e('Notes about your order, e.g. special notes for delivery.', 'semigapp'); ?>"></textarea>
                </div>
            </div>

            <div class="semigapp-checkout-order">
                <h2><?php esc_html_e('Your Order', 'semigapp'); ?></h2>

                <table class="semigapp-order-review">
                    <thead>
                        <tr>
                            <th class="product-name"><?php esc_html_e('Product', 'semigapp'); ?></th>
                            <th class="product-total"><?php esc_html_e('Total', 'semigapp'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $cart_key => $item) : ?>
                            <tr>
                                <td class="product-name">
                                    <?php echo esc_html($item['product']->name); ?>
                                    <span class="quantity">× <?php echo esc_html($item['quantity']); ?></span>
                                </td>
                                <td class="product-total">
                                    <?php echo esc_html(number_format($item['subtotal'], 2) . ' SEK'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="subtotal-row">
                            <th><?php esc_html_e('Subtotal', 'semigapp'); ?></th>
                            <td><?php echo esc_html($totals['subtotal_formatted']); ?></td>
                        </tr>
                        <tr class="shipping-row">
                            <th><?php esc_html_e('Shipping', 'semigapp'); ?></th>
                            <td><?php echo $totals['shipping'] > 0 ? esc_html($totals['shipping_formatted']) : esc_html__('Free', 'semigapp'); ?></td>
                        </tr>
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
                    </tfoot>
                </table>

                <div class="semigapp-payment-methods">
                    <h3><?php esc_html_e('Payment Method', 'semigapp'); ?></h3>

                    <?php if (empty($gateways)) : ?>
                        <p class="no-gateways"><?php esc_html_e('No payment methods available.', 'semigapp'); ?></p>
                    <?php else : ?>
                        <ul class="payment-methods-list">
                            <?php foreach ($gateways as $gateway_id => $gateway) : ?>
                                <li class="payment-method">
                                    <label>
                                        <input type="radio"
                                               name="payment_method"
                                               value="<?php echo esc_attr($gateway_id); ?>"
                                               <?php checked($gateway_id, 'klarna'); ?>>
                                        <?php if ($gateway->get_icon()) : ?>
                                            <img src="<?php echo esc_url($gateway->get_icon()); ?>"
                                                 alt="<?php echo esc_attr($gateway->get_name()); ?>"
                                                 class="payment-icon">
                                        <?php endif; ?>
                                        <span class="payment-name"><?php echo esc_html($gateway->get_name()); ?></span>
                                    </label>
                                    <?php if ($gateway->get_description()) : ?>
                                        <p class="payment-description"><?php echo esc_html($gateway->get_description()); ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="semigapp-checkout-submit">
                    <button type="submit" class="semigapp-btn semigapp-btn-primary semigapp-btn-large">
                        <?php esc_html_e('Place Order', 'semigapp'); ?>
                    </button>
                </div>

                <p class="semigapp-privacy-notice">
                    <?php
                    printf(
                        esc_html__('Your personal data will be used to process your order. See our %s.', 'semigapp'),
                        '<a href="' . esc_url(get_privacy_policy_url()) . '">' . esc_html__('privacy policy', 'semigapp') . '</a>'
                    );
                    ?>
                </p>
            </div>
        </div>
    </form>
</div>
