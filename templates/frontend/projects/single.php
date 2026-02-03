<?php
/**
 * Single Project Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="semigapp-project-single">
    <article class="semigapp-project" data-project-id="<?php echo esc_attr($project->id); ?>">
        <header class="project-header">
            <div class="project-title-row">
                <h1 class="project-title"><?php echo esc_html($project->title); ?></h1>
                <span class="project-status semigapp-status semigapp-status-<?php echo esc_attr($project->status); ?>">
                    <?php echo esc_html(ucfirst(str_replace('-', ' ', $project->status))); ?>
                </span>
            </div>

            <?php if (!empty($project->description)) : ?>
                <div class="project-description">
                    <?php echo wp_kses_post($project->description); ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="project-meta-bar">
            <div class="meta-item">
                <span class="meta-label"><?php esc_html_e('Progress', 'semigapp'); ?></span>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo esc_attr($project->progress); ?>%;"></div>
                </div>
                <span class="progress-text"><?php echo esc_html($project->progress); ?>%</span>
            </div>

            <div class="meta-item">
                <span class="meta-label"><?php esc_html_e('Tasks', 'semigapp'); ?></span>
                <span class="meta-value">
                    <?php printf(
                        esc_html__('%d / %d completed', 'semigapp'),
                        $project->completed_tasks,
                        $project->task_count
                    ); ?>
                </span>
            </div>

            <?php if (!empty($project->start_date)) : ?>
                <div class="meta-item">
                    <span class="meta-label"><?php esc_html_e('Started', 'semigapp'); ?></span>
                    <span class="meta-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($project->start_date))); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($project->end_date)) : ?>
                <div class="meta-item">
                    <span class="meta-label"><?php esc_html_e('Due Date', 'semigapp'); ?></span>
                    <span class="meta-value <?php echo strtotime($project->end_date) < time() && $project->status !== 'completed' ? 'overdue' : ''; ?>">
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($project->end_date))); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div class="project-content">
            <div class="tasks-section">
                <div class="tasks-header">
                    <h2><?php esc_html_e('Tasks', 'semigapp'); ?></h2>
                    <?php if ($can_edit) : ?>
                        <button type="button" class="semigapp-btn semigapp-btn-small add-task-btn">
                            <?php esc_html_e('+ Add Task', 'semigapp'); ?>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (empty($tasks)) : ?>
                    <div class="no-tasks">
                        <p><?php esc_html_e('No tasks have been added to this project yet.', 'semigapp'); ?></p>
                    </div>
                <?php else : ?>
                    <ul class="tasks-list">
                        <?php foreach ($tasks as $task) : ?>
                            <li class="task-item status-<?php echo esc_attr($task->status); ?>"
                                data-task-id="<?php echo esc_attr($task->id); ?>">
                                <div class="task-checkbox">
                                    <?php if ($can_edit) : ?>
                                        <input type="checkbox"
                                               <?php checked($task->status, 'completed'); ?>
                                               class="task-toggle"
                                               data-task-id="<?php echo esc_attr($task->id); ?>">
                                    <?php else : ?>
                                        <span class="status-icon <?php echo $task->status === 'completed' ? 'completed' : ''; ?>"></span>
                                    <?php endif; ?>
                                </div>

                                <div class="task-content">
                                    <span class="task-title"><?php echo esc_html($task->title); ?></span>
                                    <?php if (!empty($task->description)) : ?>
                                        <span class="task-description"><?php echo esc_html(wp_trim_words($task->description, 15)); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="task-meta">
                                    <?php if (!empty($task->due_date)) : ?>
                                        <span class="task-due <?php echo strtotime($task->due_date) < time() && $task->status !== 'completed' ? 'overdue' : ''; ?>">
                                            <?php echo esc_html(date_i18n('M j', strtotime($task->due_date))); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($task->priority)) : ?>
                                        <span class="task-priority priority-<?php echo esc_attr($task->priority); ?>">
                                            <?php echo esc_html(ucfirst($task->priority)); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($task->assignee_name)) : ?>
                                        <span class="task-assignee" title="<?php echo esc_attr($task->assignee_name); ?>">
                                            <?php echo esc_html(substr($task->assignee_name, 0, 2)); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($can_edit) : ?>
                                    <div class="task-actions">
                                        <button type="button" class="task-edit" data-task-id="<?php echo esc_attr($task->id); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button type="button" class="task-delete" data-task-id="<?php echo esc_attr($task->id); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if (!empty($project->team_members)) : ?>
                <div class="team-section">
                    <h3><?php esc_html_e('Team Members', 'semigapp'); ?></h3>
                    <ul class="team-list">
                        <?php foreach ($project->team_members as $member) : ?>
                            <li class="team-member">
                                <?php echo get_avatar($member->ID, 40); ?>
                                <span class="member-name"><?php echo esc_html($member->display_name); ?></span>
                                <span class="member-role"><?php echo esc_html($member->role ?? ''); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <footer class="project-footer">
            <a href="<?php echo esc_url(remove_query_arg('project_id')); ?>" class="semigapp-btn semigapp-btn-secondary">
                <?php esc_html_e('&larr; Back to Projects', 'semigapp'); ?>
            </a>

            <?php if ($can_edit) : ?>
                <a href="<?php echo esc_url(add_query_arg('action', 'edit')); ?>" class="semigapp-btn semigapp-btn-primary">
                    <?php esc_html_e('Edit Project', 'semigapp'); ?>
                </a>
            <?php endif; ?>
        </footer>
    </article>
</div>

<?php if ($can_edit) : ?>
<div id="add-task-modal" class="semigapp-modal" style="display:none;">
    <div class="modal-content">
        <h3><?php esc_html_e('Add New Task', 'semigapp'); ?></h3>
        <form method="post" class="semigapp-form" id="add-task-form">
            <?php wp_nonce_field('semigapp_add_task', 'task_nonce'); ?>
            <input type="hidden" name="project_id" value="<?php echo esc_attr($project->id); ?>">

            <div class="form-group">
                <label for="task_title"><?php esc_html_e('Task Title', 'semigapp'); ?> <span class="required">*</span></label>
                <input type="text" id="task_title" name="title" required>
            </div>

            <div class="form-group">
                <label for="task_description"><?php esc_html_e('Description', 'semigapp'); ?></label>
                <textarea id="task_description" name="description" rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group form-group-half">
                    <label for="task_due_date"><?php esc_html_e('Due Date', 'semigapp'); ?></label>
                    <input type="date" id="task_due_date" name="due_date">
                </div>

                <div class="form-group form-group-half">
                    <label for="task_priority"><?php esc_html_e('Priority', 'semigapp'); ?></label>
                    <select id="task_priority" name="priority">
                        <option value="low"><?php esc_html_e('Low', 'semigapp'); ?></option>
                        <option value="medium" selected><?php esc_html_e('Medium', 'semigapp'); ?></option>
                        <option value="high"><?php esc_html_e('High', 'semigapp'); ?></option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="semigapp-btn semigapp-btn-primary"><?php esc_html_e('Add Task', 'semigapp'); ?></button>
                <button type="button" class="semigapp-btn semigapp-btn-secondary close-modal"><?php esc_html_e('Cancel', 'semigapp'); ?></button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
