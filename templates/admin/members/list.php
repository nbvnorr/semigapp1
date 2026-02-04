<?php
/**
 * Admin Members List Template
 *
 * @package SemigApp
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap semigapp-admin-members">
    <h1 class="wp-heading-inline"><?php esc_html_e('Members', 'semigapp'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-members&action=add')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'semigapp'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Member updated successfully.', 'semigapp'); ?></p>
        </div>
    <?php endif; ?>

    <ul class="subsubsub">
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-members')); ?>" class="<?php echo empty($_GET['status']) ? 'current' : ''; ?>"><?php esc_html_e('All', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-members&status=active')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'current' : ''; ?>"><?php esc_html_e('Active', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-members&status=expired')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'expired') ? 'current' : ''; ?>"><?php esc_html_e('Expired', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-members&status=pending')); ?>" class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'current' : ''; ?>"><?php esc_html_e('Pending', 'semigapp'); ?></a></li>
    </ul>

    <form method="get">
        <input type="hidden" name="page" value="semigapp-members">
        <p class="search-box">
            <label class="screen-reader-text" for="member-search"><?php esc_html_e('Search Members', 'semigapp'); ?></label>
            <input type="search" id="member-search" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>">
            <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search Members', 'semigapp'); ?>">
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-primary"><?php esc_html_e('Member', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Membership Level', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Status', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Start Date', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('End Date', 'semigapp'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (empty($members)) : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No members found.', 'semigapp'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($members as $member) : ?>
                    <?php $user = get_userdata($member->user_id); ?>
                    <tr>
                        <td class="column-primary">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-members&action=edit&id=' . $member->id)); ?>">
                                    <?php echo esc_html($user ? $user->display_name : __('Unknown User', 'semigapp')); ?>
                                </a>
                            </strong>
                            <?php if ($user) : ?>
                                <br><span class="description"><?php echo esc_html($user->user_email); ?></span>
                            <?php endif; ?>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-members&action=edit&id=' . $member->id)); ?>">
                                        <?php esc_html_e('Edit', 'semigapp'); ?>
                                    </a> |
                                </span>
                                <span class="delete">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=semigapp-members&action=delete&id=' . $member->id), 'delete_member_' . $member->id)); ?>"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure?', 'semigapp'); ?>');">
                                        <?php esc_html_e('Delete', 'semigapp'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td><?php echo esc_html($member->level_name ?? __('N/A', 'semigapp')); ?></td>
                        <td>
                            <span class="semigapp-status semigapp-status-<?php echo esc_attr($member->status); ?>">
                                <?php echo esc_html(ucfirst($member->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($member->start_date ? date_i18n(get_option('date_format'), strtotime($member->start_date)) : '-'); ?></td>
                        <td><?php echo esc_html($member->end_date ? date_i18n(get_option('date_format'), strtotime($member->end_date)) : __('Lifetime', 'semigapp')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
