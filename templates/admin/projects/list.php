<?php
/**
 * Admin Projects List Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap semigapp-admin-projects">
    <h1 class="wp-heading-inline"><?php esc_html_e('Projects', 'semigapp'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-projects&action=add')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'semigapp'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'created':
                        esc_html_e('Project created successfully.', 'semigapp');
                        break;
                    case 'updated':
                        esc_html_e('Project updated successfully.', 'semigapp');
                        break;
                    case 'deleted':
                        esc_html_e('Project deleted successfully.', 'semigapp');
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="filter_status" id="filter-status">
                <option value=""><?php esc_html_e('All Statuses', 'semigapp'); ?></option>
                <option value="active" <?php selected($current_status ?? '', 'active'); ?>><?php esc_html_e('Active', 'semigapp'); ?></option>
                <option value="on-hold" <?php selected($current_status ?? '', 'on-hold'); ?>><?php esc_html_e('On Hold', 'semigapp'); ?></option>
                <option value="completed" <?php selected($current_status ?? '', 'completed'); ?>><?php esc_html_e('Completed', 'semigapp'); ?></option>
                <option value="cancelled" <?php selected($current_status ?? '', 'cancelled'); ?>><?php esc_html_e('Cancelled', 'semigapp'); ?></option>
            </select>
            <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'semigapp'); ?>">
        </div>

        <div class="tablenav-pages">
            <?php if (!empty($pagination)) echo wp_kses_post($pagination); ?>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Project', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Owner', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Status', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Progress', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Tasks', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Due Date', 'semigapp'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($projects)) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('No projects found.', 'semigapp'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($projects as $project) : ?>
                    <tr>
                        <td class="column-title column-primary">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-projects&action=edit&id=' . $project->id)); ?>">
                                    <?php echo esc_html($project->title); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-projects&action=edit&id=' . $project->id)); ?>">
                                        <?php esc_html_e('Edit', 'semigapp'); ?>
                                    </a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-projects&action=view&id=' . $project->id)); ?>">
                                        <?php esc_html_e('View', 'semigapp'); ?>
                                    </a> |
                                </span>
                                <span class="trash">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=semigapp-projects&action=delete&id=' . $project->id), 'delete_project_' . $project->id)); ?>"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this project?', 'semigapp'); ?>');">
                                        <?php esc_html_e('Delete', 'semigapp'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            if ($project->owner_id) {
                                $owner = get_user_by('id', $project->owner_id);
                                echo esc_html($owner ? $owner->display_name : __('Unknown', 'semigapp'));
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="semigapp-status semigapp-status-<?php echo esc_attr($project->status); ?>">
                                <?php echo esc_html(ucfirst(str_replace('-', ' ', $project->status))); ?>
                            </span>
                        </td>
                        <td>
                            <div class="progress-bar-mini">
                                <div class="progress-fill" style="width: <?php echo esc_attr($project->progress); ?>%;"></div>
                            </div>
                            <span class="progress-text"><?php echo esc_html($project->progress); ?>%</span>
                        </td>
                        <td>
                            <?php printf(
                                esc_html__('%d / %d', 'semigapp'),
                                $project->completed_tasks ?? 0,
                                $project->task_count ?? 0
                            ); ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($project->end_date)) {
                                $is_overdue = strtotime($project->end_date) < time() && $project->status !== 'completed';
                                echo '<span class="' . ($is_overdue ? 'overdue' : '') . '">';
                                echo esc_html(date_i18n(get_option('date_format'), strtotime($project->end_date)));
                                echo '</span>';
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
