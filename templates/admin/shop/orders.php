<?php
/**
 * Admin Orders List Template
 *
 * @package SemigApp
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap semigapp-admin-orders">
    <h1 class="wp-heading-inline"><?php esc_html_e('Orders', 'semigapp'); ?></h1>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Order updated successfully.', 'semigapp'); ?></p>
        </div>
    <?php endif; ?>

    <ul class="subsubsub">
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-orders')); ?>" class="<?php echo empty($_GET['status']) ? 'current' : ''; ?>"><?php esc_html_e('All', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-orders&status=pending')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'current' : ''; ?>"><?php esc_html_e('Pending', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-orders&status=processing')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'processing') ? 'current' : ''; ?>"><?php esc_html_e('Processing', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-orders&status=completed')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'current' : ''; ?>"><?php esc_html_e('Completed', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-orders&status=cancelled')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelled') ? 'current' : ''; ?>"><?php esc_html_e('Cancelled', 'semigapp'); ?></a></li>
    </ul>

    <form method="get">
        <input type="hidden" name="page" value="semigapp-orders">
        <p class="search-box">
            <label class="screen-reader-text" for="order-search"><?php esc_html_e('Search Orders', 'semigapp'); ?></label>
            <input type="search" id="order-search" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>" placeholder="<?php esc_attr_e('Order # or email', 'semigapp'); ?>">
            <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search', 'semigapp'); ?>">
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-primary"><?php esc_html_e('Order', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Customer', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Status', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Payment', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Total', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Date', 'semigapp'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (empty($orders)) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('No orders found.', 'semigapp'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($orders as $order) : ?>
                    <?php
                    $billing = json_decode($order->billing_address, true) ?: array();
                    $customer_name = isset($billing['first_name']) ? $billing['first_name'] . ' ' . ($billing['last_name'] ?? '') : '';
                    $customer_email = $billing['email'] ?? '';
                    ?>
                    <tr>
                        <td class="column-primary">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-orders&action=view&id=' . $order->id)); ?>">
                                    #<?php echo esc_html($order->order_number); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="view">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-orders&action=view&id=' . $order->id)); ?>">
                                        <?php esc_html_e('View', 'semigapp'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?php if ($customer_name) : ?>
                                <?php echo esc_html($customer_name); ?><br>
                            <?php endif; ?>
                            <?php if ($customer_email) : ?>
                                <a href="mailto:<?php echo esc_attr($customer_email); ?>"><?php echo esc_html($customer_email); ?></a>
                            <?php elseif ($order->user_id) : ?>
                                <?php $user = get_userdata($order->user_id); ?>
                                <?php echo esc_html($user ? $user->display_name : __('Guest', 'semigapp')); ?>
                            <?php else : ?>
                                <?php esc_html_e('Guest', 'semigapp'); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="semigapp-status semigapp-status-<?php echo esc_attr($order->status); ?>">
                                <?php echo esc_html(ucfirst($order->status)); ?>
                            </span>
                        </td>
                        <td>
                            <span class="semigapp-payment-status semigapp-payment-<?php echo esc_attr($order->payment_status); ?>">
                                <?php echo esc_html(ucfirst($order->payment_status)); ?>
                            </span>
                            <?php if ($order->payment_method) : ?>
                                <br><small><?php echo esc_html($order->payment_method); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(number_format($order->total, 2) . ' ' . ($order->currency ?? 'SEK')); ?></td>
                        <td>
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order->created_at))); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
