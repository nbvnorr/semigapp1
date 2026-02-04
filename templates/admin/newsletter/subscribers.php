<?php
/**
 * Admin Newsletter Subscribers Template
 *
 * @package SemigApp
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap semigapp-admin-subscribers">
    <h1 class="wp-heading-inline"><?php esc_html_e('Newsletter Subscribers', 'semigapp'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-newsletter&action=add')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'semigapp'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-newsletter&action=export')); ?>" class="page-title-action">
        <?php esc_html_e('Export', 'semigapp'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'added':
                        esc_html_e('Subscriber added successfully.', 'semigapp');
                        break;
                    case 'deleted':
                        esc_html_e('Subscriber deleted.', 'semigapp');
                        break;
                    case 'imported':
                        esc_html_e('Subscribers imported successfully.', 'semigapp');
                        break;
                    default:
                        esc_html_e('Action completed.', 'semigapp');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <ul class="subsubsub">
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-newsletter')); ?>" class="<?php echo empty($_GET['status']) ? 'current' : ''; ?>"><?php esc_html_e('All', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-newsletter&status=active')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'current' : ''; ?>"><?php esc_html_e('Active', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-newsletter&status=pending')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'current' : ''; ?>"><?php esc_html_e('Pending', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-newsletter&status=unsubscribed')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'unsubscribed') ? 'current' : ''; ?>"><?php esc_html_e('Unsubscribed', 'semigapp'); ?></a></li>
    </ul>

    <form method="get">
        <input type="hidden" name="page" value="semigapp-newsletter">
        <p class="search-box">
            <label class="screen-reader-text" for="subscriber-search"><?php esc_html_e('Search Subscribers', 'semigapp'); ?></label>
            <input type="search" id="subscriber-search" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>">
            <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search', 'semigapp'); ?>">
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-cb check-column"><input type="checkbox"></th>
                <th scope="col" class="manage-column column-primary"><?php esc_html_e('Email', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Name', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Status', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Source', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Subscribed', 'semigapp'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (empty($subscribers)) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('No subscribers found.', 'semigapp'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($subscribers as $subscriber) : ?>
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="subscriber[]" value="<?php echo esc_attr($subscriber->id); ?>"></th>
                        <td class="column-primary">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-newsletter&action=edit&id=' . $subscriber->id)); ?>">
                                    <?php echo esc_html($subscriber->email); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-newsletter&action=edit&id=' . $subscriber->id)); ?>">
                                        <?php esc_html_e('Edit', 'semigapp'); ?>
                                    </a> |
                                </span>
                                <span class="delete">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=semigapp-newsletter&action=delete&id=' . $subscriber->id), 'delete_subscriber_' . $subscriber->id)); ?>"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure?', 'semigapp'); ?>');">
                                        <?php esc_html_e('Delete', 'semigapp'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            $name = trim(($subscriber->first_name ?? '') . ' ' . ($subscriber->last_name ?? ''));
                            echo esc_html($name ?: '-');
                            ?>
                        </td>
                        <td>
                            <span class="semigapp-status semigapp-status-<?php echo esc_attr($subscriber->status); ?>">
                                <?php echo esc_html(ucfirst($subscriber->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($subscriber->source ?? '-'); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscriber->created_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
