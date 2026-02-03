<?php
/**
 * My Tasks Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="semigapp-my-tasks">
    <header class="tasks-header">
        <h2><?php esc_html_e('My Tasks', 'semigapp'); ?></h2>

        <div class="tasks-filters">
            <select id="task-status-filter" class="filter-select">
                <option value=""><?php esc_html_e('All Statuses', 'semigapp'); ?></option>
                <option value="pending" <?php selected($current_status ?? '', 'pending'); ?>><?php esc_html_e('Pending', 'semigapp'); ?></option>
                <option value="in-progress" <?php selected($current_status ?? '', 'in-progress'); ?>><?php esc_html_e('In Progress', 'semigapp'); ?></option>
                <option value="completed" <?php selected($current_status ?? '', 'completed'); ?>><?php esc_html_e('Completed', 'semigapp'); ?></option>
            </select>

            <select id="task-priority-filter" class="filter-select">
                <option value=""><?php esc_html_e('All Priorities', 'semigapp'); ?></option>
                <option value="high" <?php selected($current_priority ?? '', 'high'); ?>><?php esc_html_e('High', 'semigapp'); ?></option>
                <option value="medium" <?php selected($current_priority ?? '', 'medium'); ?>><?php esc_html_e('Medium', 'semigapp'); ?></option>
                <option value="low" <?php selected($current_priority ?? '', 'low'); ?>><?php esc_html_e('Low', 'semigapp'); ?></option>
            </select>
        </div>
    </header>

    <div class="tasks-summary">
        <div class="summary-card">
            <span class="summary-number"><?php echo esc_html($task_counts['total'] ?? 0); ?></span>
            <span class="summary-label"><?php esc_html_e('Total Tasks', 'semigapp'); ?></span>
        </div>
        <div class="summary-card">
            <span class="summary-number"><?php echo esc_html($task_counts['pending'] ?? 0); ?></span>
            <span class="summary-label"><?php esc_html_e('Pending', 'semigapp'); ?></span>
        </div>
        <div class="summary-card">
            <span class="summary-number"><?php echo esc_html($task_counts['in_progress'] ?? 0); ?></span>
            <span class="summary-label"><?php esc_html_e('In Progress', 'semigapp'); ?></span>
        </div>
        <div class="summary-card">
            <span class="summary-number"><?php echo esc_html($task_counts['completed'] ?? 0); ?></span>
            <span class="summary-label"><?php esc_html_e('Completed', 'semigapp'); ?></span>
        </div>
        <div class="summary-card overdue">
            <span class="summary-number"><?php echo esc_html($task_counts['overdue'] ?? 0); ?></span>
            <span class="summary-label"><?php esc_html_e('Overdue', 'semigapp'); ?></span>
        </div>
    </div>

    <?php if (empty($tasks)) : ?>
        <div class="semigapp-no-tasks">
            <p><?php esc_html_e('You have no tasks assigned.', 'semigapp'); ?></p>
        </div>
    <?php else : ?>
        <div class="tasks-list-container">
            <?php
            // Group tasks by project
            $tasks_by_project = array();
            foreach ($tasks as $task) {
                $project_id = $task->project_id ?? 0;
                if (!isset($tasks_by_project[$project_id])) {
                    $tasks_by_project[$project_id] = array(
                        'name' => $task->project_name ?? __('No Project', 'semigapp'),
                        'tasks' => array(),
                    );
                }
                $tasks_by_project[$project_id]['tasks'][] = $task;
            }
            ?>

            <?php foreach ($tasks_by_project as $project_id => $project_data) : ?>
                <div class="project-tasks-group">
                    <h3 class="project-name">
                        <?php if ($project_id) : ?>
                            <a href="<?php echo esc_url(add_query_arg('project_id', $project_id, remove_query_arg(array('status', 'priority')))); ?>">
                                <?php echo esc_html($project_data['name']); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html($project_data['name']); ?>
                        <?php endif; ?>
                    </h3>

                    <ul class="tasks-list">
                        <?php foreach ($project_data['tasks'] as $task) : ?>
                            <li class="task-item status-<?php echo esc_attr($task->status); ?> priority-<?php echo esc_attr($task->priority ?? 'medium'); ?>"
                                data-task-id="<?php echo esc_attr($task->id); ?>">

                                <div class="task-checkbox">
                                    <input type="checkbox"
                                           <?php checked($task->status, 'completed'); ?>
                                           class="task-toggle"
                                           data-task-id="<?php echo esc_attr($task->id); ?>">
                                </div>

                                <div class="task-content">
                                    <span class="task-title"><?php echo esc_html($task->title); ?></span>
                                    <?php if (!empty($task->description)) : ?>
                                        <span class="task-description"><?php echo esc_html(wp_trim_words($task->description, 20)); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="task-meta">
                                    <?php if (!empty($task->due_date)) : ?>
                                        <?php
                                        $is_overdue = strtotime($task->due_date) < time() && $task->status !== 'completed';
                                        $is_today = date('Y-m-d', strtotime($task->due_date)) === date('Y-m-d');
                                        ?>
                                        <span class="task-due <?php echo $is_overdue ? 'overdue' : ($is_today ? 'today' : ''); ?>">
                                            <?php
                                            if ($is_today) {
                                                esc_html_e('Today', 'semigapp');
                                            } elseif ($is_overdue) {
                                                printf(
                                                    esc_html__('%d days overdue', 'semigapp'),
                                                    floor((time() - strtotime($task->due_date)) / DAY_IN_SECONDS)
                                                );
                                            } else {
                                                echo esc_html(date_i18n('M j', strtotime($task->due_date)));
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>

                                    <span class="task-priority priority-<?php echo esc_attr($task->priority ?? 'medium'); ?>">
                                        <?php echo esc_html(ucfirst($task->priority ?? 'medium')); ?>
                                    </span>

                                    <span class="task-status status-<?php echo esc_attr($task->status); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('-', ' ', $task->status))); ?>
                                    </span>
                                </div>

                                <div class="task-actions">
                                    <a href="<?php echo esc_url(add_query_arg(array('project_id' => $task->project_id, 'task_id' => $task->id))); ?>"
                                       class="task-view" title="<?php esc_attr_e('View Details', 'semigapp'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($pagination)) : ?>
            <nav class="semigapp-pagination">
                <?php echo wp_kses_post($pagination); ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
