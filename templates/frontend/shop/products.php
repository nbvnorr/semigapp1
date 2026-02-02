<?php
/**
 * Shop Products Grid Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$columns_class = isset($atts['columns']) ? 'columns-' . intval($atts['columns']) : 'columns-3';
?>

<div class="semigapp-shop <?php echo esc_attr($columns_class); ?>">
    <?php if (empty($products)) : ?>
        <p class="semigapp-no-products"><?php esc_html_e('No products found.', 'semigapp'); ?></p>
    <?php else : ?>
        <div class="semigapp-products-grid">
            <?php foreach ($products as $product) : ?>
                <div class="semigapp-product-card" data-product-id="<?php echo esc_attr($product->id); ?>">
                    <?php if ($product->featured_image) : ?>
                        <div class="semigapp-product-image">
                            <?php echo wp_get_attachment_image($product->featured_image, 'medium'); ?>
                            <?php if ($product->is_on_sale) : ?>
                                <span class="semigapp-sale-badge"><?php esc_html_e('Sale', 'semigapp'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div class="semigapp-product-image semigapp-no-image">
                            <span class="placeholder-icon">📦</span>
                        </div>
                    <?php endif; ?>

                    <div class="semigapp-product-info">
                        <h3 class="semigapp-product-title">
                            <a href="<?php echo esc_url(add_query_arg('product', $product->slug, home_url('/shop'))); ?>">
                                <?php echo esc_html($product->name); ?>
                            </a>
                        </h3>

                        <?php if (!empty($product->short_description)) : ?>
                            <p class="semigapp-product-excerpt">
                                <?php echo esc_html(wp_trim_words($product->short_description, 15)); ?>
                            </p>
                        <?php endif; ?>

                        <div class="semigapp-product-price">
                            <?php if ($product->is_on_sale) : ?>
                                <span class="price-original"><?php echo esc_html($product->price_formatted); ?></span>
                                <span class="price-sale"><?php echo esc_html($product->sale_price_formatted); ?></span>
                            <?php else : ?>
                                <span class="price-regular"><?php echo esc_html($product->price_formatted); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="semigapp-product-actions">
                            <?php if ($product->is_in_stock) : ?>
                                <button type="button"
                                        class="semigapp-btn semigapp-btn-primary semigapp-add-to-cart"
                                        data-product-id="<?php echo esc_attr($product->id); ?>">
                                    <?php esc_html_e('Add to Cart', 'semigapp'); ?>
                                </button>
                            <?php else : ?>
                                <span class="semigapp-out-of-stock"><?php esc_html_e('Out of Stock', 'semigapp'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
