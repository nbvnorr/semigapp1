<?php
/**
 * Admin Products List Template
 *
 * @package SemigApp
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap semigapp-admin-products">
    <h1 class="wp-heading-inline"><?php esc_html_e('Products', 'semigapp'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-products&action=add')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'semigapp'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Product saved successfully.', 'semigapp'); ?></p>
        </div>
    <?php endif; ?>

    <ul class="subsubsub">
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-products')); ?>" class="<?php echo empty($_GET['status']) ? 'current' : ''; ?>"><?php esc_html_e('All', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-products&status=published')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'published') ? 'current' : ''; ?>"><?php esc_html_e('Published', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-products&status=draft')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'draft') ? 'current' : ''; ?>"><?php esc_html_e('Draft', 'semigapp'); ?></a></li>
    </ul>

    <form method="get">
        <input type="hidden" name="page" value="semigapp-products">
        <p class="search-box">
            <label class="screen-reader-text" for="product-search"><?php esc_html_e('Search Products', 'semigapp'); ?></label>
            <input type="search" id="product-search" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>">
            <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search Products', 'semigapp'); ?>">
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-cb check-column"><input type="checkbox"></th>
                <th scope="col" class="manage-column column-primary"><?php esc_html_e('Product', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('SKU', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Price', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Stock', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Status', 'semigapp'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (empty($products)) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('No products found. Click "Add New" to create your first product.', 'semigapp'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($products as $product) : ?>
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="product[]" value="<?php echo esc_attr($product->id); ?>"></th>
                        <td class="column-primary">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-products&action=edit&id=' . $product->id)); ?>">
                                    <?php echo esc_html($product->name); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-products&action=edit&id=' . $product->id)); ?>">
                                        <?php esc_html_e('Edit', 'semigapp'); ?>
                                    </a> |
                                </span>
                                <span class="trash">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=semigapp-products&action=delete&id=' . $product->id), 'delete_product_' . $product->id)); ?>"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this product?', 'semigapp'); ?>');">
                                        <?php esc_html_e('Delete', 'semigapp'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td><?php echo esc_html($product->sku ?: '-'); ?></td>
                        <td>
                            <?php
                            if ($product->sale_price && $product->sale_price < $product->price) {
                                echo '<del>' . esc_html(number_format($product->price, 2)) . ' kr</del> ';
                                echo '<ins>' . esc_html(number_format($product->sale_price, 2)) . ' kr</ins>';
                            } else {
                                echo esc_html(number_format($product->price, 2)) . ' kr';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($product->manage_stock) : ?>
                                <span class="<?php echo $product->stock_quantity <= 0 ? 'low-stock' : ''; ?>">
                                    <?php echo esc_html($product->stock_quantity); ?>
                                </span>
                            <?php else : ?>
                                <?php echo esc_html($product->stock_status === 'instock' ? __('In stock', 'semigapp') : __('Out of stock', 'semigapp')); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="semigapp-status semigapp-status-<?php echo esc_attr($product->status); ?>">
                                <?php echo esc_html(ucfirst($product->status)); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
