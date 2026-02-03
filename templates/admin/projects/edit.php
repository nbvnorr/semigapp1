<?php
/**
 * Admin Project Edit Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$is_new = empty($project->id);
$title = $is_new ? __('Add New Project', 'semigapp') : __('Edit Project', 'semigapp');
?>

<div class="wrap semigapp-admin-project-edit">
    <h1><?php echo esc_html($title); ?></h1>

    <?php if (!empty($error)) : ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" id="project-form">
        <?php wp_nonce_field('semigapp_save_project', 'project_nonce'); ?>
        <input type="hidden" name="project_id" value="<?php echo esc_attr($project->id ?? 0); ?>">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <input type="text"
                               name="title"
                               id="title"
                               value="<?php echo esc_attr($project->title ?? ''); ?>"
                               placeholder="<?php esc_attr_e('Project Title', 'semigapp'); ?>"
                               required
                               class="large-text">
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Description', 'semigapp'); ?></h2>
                        <div class="inside">
                            <?php
                            wp_editor(
                                $project->description ?? '',
                                'project_description',
                                array(
                                    'textarea_name' => 'description',
                                    'textarea_rows' => 10,
                                    'media_buttons' => true,
                                )
                            );
                            ?>
                        </div>
                    </div>

                    <?php if (!$is_new) : ?>
                        <div class="postbox">
                            <h2 class="hndle"><?php esc_html_e('Tasks', 'semigapp'); ?></h2>
                            <div class="inside">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Task', 'semigapp'); ?></th>
                                            <th><?php esc_html_e('Status', 'semigapp'); ?></th>
                                            <th><?php esc_html_e('Assignee', 'semigapp'); ?></th>
                                            <th><?php esc_html_e('Due Date', 'semigapp'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($tasks)) : ?>
                                            <tr>
                                                <td colspan="4"><?php esc_html_e('No tasks yet.', 'semigapp'); ?></td>
                                            </tr>
                                        <?php else : ?>
                                            <?php foreach ($tasks as $task) : ?>
                                                <tr>
                                                    <td><?php echo esc_html($task->title); ?></td>
                                                    <td>
                                                        <span class="semigapp-status semigapp-status-<?php echo esc_attr($task->status); ?>">
                                                            <?php echo esc_html(ucfirst(str_replace('-', ' ', $task->status))); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo esc_html($task->assignee_name ?? '—'); ?></td>
                                                    <td><?php echo $task->due_date ? esc_html(date_i18n(get_option('date_format'), strtotime($task->due_date))) : '—'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Project Settings', 'semigapp'); ?></h2>
                        <div class="inside">
                            <p>
                                <label for="status"><?php esc_html_e('Status', 'semigapp'); ?></label>
                                <select name="status" id="status" class="widefat">
                                    <option value="active" <?php selected($project->status ?? 'active', 'active'); ?>><?php esc_html_e('Active', 'semigapp'); ?></option>
                                    <option value="on-hold" <?php selected($project->status ?? '', 'on-hold'); ?>><?php esc_html_e('On Hold', 'semigapp'); ?></option>
                                    <option value="completed" <?php selected($project->status ?? '', 'completed'); ?>><?php esc_html_e('Completed', 'semigapp'); ?></option>
                                    <option value="cancelled" <?php selected($project->status ?? '', 'cancelled'); ?>><?php esc_html_e('Cancelled', 'semigapp'); ?></option>
                                </select>
                            </p>

                            <p>
                                <label for="owner_id"><?php esc_html_e('Owner', 'semigapp'); ?></label>
                                <?php
                                wp_dropdown_users(array(
                                    'name' => 'owner_id',
                                    'id' => 'owner_id',
                                    'class' => 'widefat',
                                    'selected' => $project->owner_id ?? get_current_user_id(),
                                    'show_option_none' => __('Select Owner', 'semigapp'),
                                ));
                                ?>
                            </p>

                            <p>
                                <label for="start_date"><?php esc_html_e('Start Date', 'semigapp'); ?></label>
                                <input type="date"
                                       name="start_date"
                                       id="start_date"
                                       value="<?php echo esc_attr($project->start_date ?? ''); ?>"
                                       class="widefat">
                            </p>

                            <p>
                                <label for="end_date"><?php esc_html_e('Due Date', 'semigapp'); ?></label>
                                <input type="date"
                                       name="end_date"
                                       id="end_date"
                                       value="<?php echo esc_attr($project->end_date ?? ''); ?>"
                                       class="widefat">
                            </p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Actions', 'semigapp'); ?></h2>
                        <div class="inside">
                            <p>
                                <button type="submit" name="save" class="button button-primary button-large">
                                    <?php echo $is_new ? esc_html__('Create Project', 'semigapp') : esc_html__('Update Project', 'semigapp'); ?>
                                </button>
                            </p>
                            <p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-projects')); ?>" class="button">
                                    <?php esc_html_e('Cancel', 'semigapp'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
