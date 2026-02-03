<?php
/**
 * Admin Membership Levels Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap semigapp-admin-membership-levels">
    <h1 class="wp-heading-inline"><?php esc_html_e('Membership Levels', 'semigapp'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-membership-levels&action=add')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'semigapp'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'created':
                        esc_html_e('Level created successfully.', 'semigapp');
                        break;
                    case 'updated':
                        esc_html_e('Level updated successfully.', 'semigapp');
                        break;
                    case 'deleted':
                        esc_html_e('Level deleted successfully.', 'semigapp');
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-primary"><?php esc_html_e('Level Name', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Price', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Duration', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Members', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Status', 'semigapp'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (empty($levels)) : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No membership levels found.', 'semigapp'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($levels as $level) : ?>
                    <tr data-level-id="<?php echo esc_attr($level->id); ?>">
                        <td class="column-primary">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-membership-levels&action=edit&id=' . $level->id)); ?>">
                                    <?php echo esc_html($level->name); ?>
                                </a>
                            </strong>
                            <?php if (!empty($level->description)) : ?>
                                <p class="description"><?php echo esc_html(wp_trim_words($level->description, 15)); ?></p>
                            <?php endif; ?>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-membership-levels&action=edit&id=' . $level->id)); ?>">
                                        <?php esc_html_e('Edit', 'semigapp'); ?>
                                    </a> |
                                </span>
                                <span class="trash">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=semigapp-membership-levels&action=delete&id=' . $level->id), 'delete_level_' . $level->id)); ?>"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this level?', 'semigapp'); ?>');">
                                        <?php esc_html_e('Delete', 'semigapp'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            if ($level->price > 0) {
                                echo esc_html(number_format($level->price, 2) . ' ' . ($level->currency ?? 'SEK'));
                            } else {
                                esc_html_e('Free', 'semigapp');
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($level->duration_value > 0) {
                                $duration_units = array(
                                    'days' => __('days', 'semigapp'),
                                    'weeks' => __('weeks', 'semigapp'),
                                    'months' => __('months', 'semigapp'),
                                    'years' => __('years', 'semigapp'),
                                );
                                printf(
                                    '%d %s',
                                    $level->duration_value,
                                    $duration_units[$level->duration_unit] ?? $level->duration_unit
                                );
                            } else {
                                esc_html_e('Lifetime', 'semigapp');
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($level->member_count ?? 0); ?></td>
                        <td>
                            <span class="semigapp-status semigapp-status-<?php echo $level->active ? 'active' : 'inactive'; ?>">
                                <?php echo $level->active ? esc_html__('Active', 'semigapp') : esc_html__('Inactive', 'semigapp'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
