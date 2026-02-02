<?php
/**
 * Single Product Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="semigapp-single-product" data-product-id="<?php echo esc_attr($product->id); ?>">
    <div class="semigapp-product-gallery">
        <?php if ($product->featured_image) : ?>
            <div class="semigapp-main-image">
                <?php echo wp_get_attachment_image($product->featured_image, 'large'); ?>
                <?php if ($product->is_on_sale) : ?>
                    <span class="semigapp-sale-badge"><?php esc_html_e('Sale', 'semigapp'); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($product->gallery)) : ?>
            <div class="semigapp-gallery-thumbnails">
                <?php if ($product->featured_image) : ?>
                    <div class="thumbnail active">
                        <?php echo wp_get_attachment_image($product->featured_image, 'thumbnail'); ?>
                    </div>
                <?php endif; ?>
                <?php foreach ($product->gallery as $image_id) : ?>
                    <div class="thumbnail">
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="semigapp-product-details">
        <h1 class="semigapp-product-title"><?php echo esc_html($product->name); ?></h1>

        <?php if ($product->sku) : ?>
            <p class="semigapp-product-sku">
                <span class="label"><?php esc_html_e('SKU:', 'semigapp'); ?></span>
                <span class="value"><?php echo esc_html($product->sku); ?></span>
            </p>
        <?php endif; ?>

        <div class="semigapp-product-price">
            <?php if ($product->is_on_sale) : ?>
                <span class="price-original"><?php echo esc_html($product->price_formatted); ?></span>
                <span class="price-sale"><?php echo esc_html($product->sale_price_formatted); ?></span>
            <?php else : ?>
                <span class="price-regular"><?php echo esc_html($product->price_formatted); ?></span>
            <?php endif; ?>
            <span class="price-tax-info"><?php esc_html_e('incl. VAT', 'semigapp'); ?></span>
        </div>

        <div class="semigapp-product-stock">
            <?php if ($product->is_in_stock) : ?>
                <span class="in-stock">✓ <?php esc_html_e('In Stock', 'semigapp'); ?></span>
                <?php if ($product->manage_stock && $product->stock_quantity <= 5) : ?>
                    <span class="low-stock">
                        <?php printf(esc_html__('Only %d left', 'semigapp'), $product->stock_quantity); ?>
                    </span>
                <?php endif; ?>
            <?php else : ?>
                <span class="out-of-stock"><?php esc_html_e('Out of Stock', 'semigapp'); ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($product->short_description)) : ?>
            <div class="semigapp-product-short-description">
                <?php echo wp_kses_post($product->short_description); ?>
            </div>
        <?php endif; ?>

        <?php if ($product->is_in_stock) : ?>
            <form class="semigapp-add-to-cart-form">
                <div class="quantity-wrapper">
                    <label for="quantity"><?php esc_html_e('Quantity:', 'semigapp'); ?></label>
                    <input type="number"
                           id="quantity"
                           name="quantity"
                           value="1"
                           min="1"
                           max="<?php echo $product->manage_stock ? esc_attr($product->stock_quantity) : 99; ?>"
                           class="semigapp-quantity-input">
                </div>
                <button type="submit"
                        class="semigapp-btn semigapp-btn-primary semigapp-btn-large semigapp-add-to-cart"
                        data-product-id="<?php echo esc_attr($product->id); ?>">
                    <?php esc_html_e('Add to Cart', 'semigapp'); ?>
                </button>
            </form>
        <?php endif; ?>

        <?php if (!empty($product->attributes)) : ?>
            <div class="semigapp-product-attributes">
                <?php foreach ($product->attributes as $attr_name => $attr_value) : ?>
                    <div class="attribute-row">
                        <span class="attribute-name"><?php echo esc_html($attr_name); ?>:</span>
                        <span class="attribute-value"><?php echo esc_html($attr_value); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($product->description)) : ?>
        <div class="semigapp-product-full-description">
            <h2><?php esc_html_e('Description', 'semigapp'); ?></h2>
            <?php echo wp_kses_post($product->description); ?>
        </div>
    <?php endif; ?>
</div>
