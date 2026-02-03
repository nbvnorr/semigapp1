<?php
/**
 * Admin Membership List Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap semigapp-admin-membership">
    <h1 class="wp-heading-inline"><?php esc_html_e('Members', 'semigapp'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-membership&action=add')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'semigapp'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'created':
                        esc_html_e('Member added successfully.', 'semigapp');
                        break;
                    case 'updated':
                        esc_html_e('Member updated successfully.', 'semigapp');
                        break;
                    case 'deleted':
                        esc_html_e('Member removed successfully.', 'semigapp');
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <ul class="subsubsub">
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-membership')); ?>" <?php echo empty($_GET['status']) ? 'class="current"' : ''; ?>><?php esc_html_e('All', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-membership&status=active')); ?>" <?php echo ($_GET['status'] ?? '') === 'active' ? 'class="current"' : ''; ?>><?php esc_html_e('Active', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-membership&status=expired')); ?>" <?php echo ($_GET['status'] ?? '') === 'expired' ? 'class="current"' : ''; ?>><?php esc_html_e('Expired', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-membership&status=pending')); ?>" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'class="current"' : ''; ?>><?php esc_html_e('Pending', 'semigapp'); ?></a></li>
    </ul>

    <form method="get">
        <input type="hidden" name="page" value="semigapp-membership">
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
                <th scope="col" class="manage-column"><?php esc_html_e('Email', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Level', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Status', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Joined', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Expires', 'semigapp'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($members)) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('No members found.', 'semigapp'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($members as $member) : ?>
                    <tr>
                        <td class="column-primary">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-membership&action=edit&id=' . $member->id)); ?>">
                                    <?php echo esc_html($member->display_name ?? $member->user_login); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-membership&action=edit&id=' . $member->id)); ?>">
                                        <?php esc_html_e('Edit', 'semigapp'); ?>
                                    </a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $member->user_id)); ?>">
                                        <?php esc_html_e('View User', 'semigapp'); ?>
                                    </a> |
                                </span>
                                <span class="trash">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=semigapp-membership&action=delete&id=' . $member->id), 'delete_member_' . $member->id)); ?>"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to remove this member?', 'semigapp'); ?>');">
                                        <?php esc_html_e('Remove', 'semigapp'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td><?php echo esc_html($member->user_email); ?></td>
                        <td><?php echo esc_html($member->level_name ?? __('None', 'semigapp')); ?></td>
                        <td>
                            <span class="semigapp-status semigapp-status-<?php echo esc_attr($member->status); ?>">
                                <?php echo esc_html(ucfirst($member->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($member->created_at))); ?></td>
                        <td>
                            <?php
                            if (!empty($member->expires_at)) {
                                $is_expired = strtotime($member->expires_at) < time();
                                echo '<span class="' . ($is_expired ? 'expired' : '') . '">';
                                echo esc_html(date_i18n(get_option('date_format'), strtotime($member->expires_at)));
                                echo '</span>';
                            } else {
                                esc_html_e('Never', 'semigapp');
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($pagination)) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo wp_kses_post($pagination); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
