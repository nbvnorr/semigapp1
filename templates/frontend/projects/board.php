<?php
/**
 * Project Kanban Board Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$columns = array(
    'pending' => __('To Do', 'semigapp'),
    'in-progress' => __('In Progress', 'semigapp'),
    'review' => __('In Review', 'semigapp'),
    'completed' => __('Done', 'semigapp'),
);

// Group tasks by status
$tasks_by_status = array();
foreach ($columns as $status => $label) {
    $tasks_by_status[$status] = array();
}

if (!empty($tasks)) {
    foreach ($tasks as $task) {
        $status = $task->status ?? 'pending';
        if (!isset($tasks_by_status[$status])) {
            $tasks_by_status[$status] = array();
        }
        $tasks_by_status[$status][] = $task;
    }
}
?>

<div class="semigapp-project-board" data-project-id="<?php echo esc_attr($project->id); ?>">
    <header class="board-header">
        <div class="board-title">
            <h2><?php echo esc_html($project->title); ?></h2>
            <span class="task-count">
                <?php printf(esc_html__('%d tasks', 'semigapp'), count($tasks)); ?>
            </span>
        </div>

        <div class="board-actions">
            <a href="<?php echo esc_url(add_query_arg('view', 'list')); ?>" class="view-toggle" title="<?php esc_attr_e('List View', 'semigapp'); ?>">
                <span class="dashicons dashicons-list-view"></span>
            </a>
            <?php if ($can_edit) : ?>
                <button type="button" class="semigapp-btn semigapp-btn-small add-task-btn">
                    <?php esc_html_e('+ Add Task', 'semigapp'); ?>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="board-columns">
        <?php foreach ($columns as $status => $label) : ?>
            <div class="board-column" data-status="<?php echo esc_attr($status); ?>">
                <div class="column-header">
                    <h3 class="column-title"><?php echo esc_html($label); ?></h3>
                    <span class="column-count"><?php echo count($tasks_by_status[$status]); ?></span>
                </div>

                <div class="column-tasks" data-status="<?php echo esc_attr($status); ?>">
                    <?php if (empty($tasks_by_status[$status])) : ?>
                        <div class="empty-column">
                            <p><?php esc_html_e('No tasks', 'semigapp'); ?></p>
                        </div>
                    <?php else : ?>
                        <?php foreach ($tasks_by_status[$status] as $task) : ?>
                            <div class="board-task <?php echo $can_edit ? 'draggable' : ''; ?>"
                                 data-task-id="<?php echo esc_attr($task->id); ?>"
                                 <?php echo $can_edit ? 'draggable="true"' : ''; ?>>

                                <div class="task-header">
                                    <span class="task-title"><?php echo esc_html($task->title); ?></span>
                                    <?php if (!empty($task->priority)) : ?>
                                        <span class="task-priority priority-<?php echo esc_attr($task->priority); ?>"
                                              title="<?php echo esc_attr(ucfirst($task->priority) . ' priority'); ?>"></span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($task->description)) : ?>
                                    <div class="task-description">
                                        <?php echo esc_html(wp_trim_words($task->description, 10)); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="task-footer">
                                    <?php if (!empty($task->due_date)) : ?>
                                        <?php
                                        $is_overdue = strtotime($task->due_date) < time() && $status !== 'completed';
                                        ?>
                                        <span class="task-due <?php echo $is_overdue ? 'overdue' : ''; ?>">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                            <?php echo esc_html(date_i18n('M j', strtotime($task->due_date))); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($task->assignee_id)) : ?>
                                        <span class="task-assignee" title="<?php echo esc_attr($task->assignee_name ?? ''); ?>">
                                            <?php echo get_avatar($task->assignee_id, 24); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($task->comments_count) && $task->comments_count > 0) : ?>
                                        <span class="task-comments">
                                            <span class="dashicons dashicons-admin-comments"></span>
                                            <?php echo esc_html($task->comments_count); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($can_edit) : ?>
                    <div class="column-footer">
                        <button type="button" class="add-task-inline" data-status="<?php echo esc_attr($status); ?>">
                            <span class="dashicons dashicons-plus"></span>
                            <?php esc_html_e('Add task', 'semigapp'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($can_edit) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const board = document.querySelector('.semigapp-project-board');
    if (!board) return;

    const projectId = board.dataset.projectId;
    let draggedTask = null;

    // Drag and drop functionality
    board.querySelectorAll('.board-task.draggable').forEach(function(task) {
        task.addEventListener('dragstart', function(e) {
            draggedTask = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        task.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            draggedTask = null;
        });
    });

    board.querySelectorAll('.column-tasks').forEach(function(column) {
        column.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this.classList.add('drag-over');
        });

        column.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });

        column.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            if (!draggedTask) return;

            const newStatus = this.dataset.status;
            const taskId = draggedTask.dataset.taskId;

            // Move the element
            this.appendChild(draggedTask);

            // Update via AJAX
            const formData = new FormData();
            formData.append('action', 'semigapp_update_task_status');
            formData.append('task_id', taskId);
            formData.append('status', newStatus);
            formData.append('nonce', semigapp.nonce);

            fetch(semigapp.ajax_url, {
                method: 'POST',
                body: formData
            }).then(function(response) {
                return response.json();
            }).then(function(data) {
                if (!data.success) {
                    console.error('Failed to update task status');
                }
                // Update column counts
                updateColumnCounts();
            });
        });
    });

    function updateColumnCounts() {
        board.querySelectorAll('.board-column').forEach(function(column) {
            const count = column.querySelectorAll('.board-task').length;
            column.querySelector('.column-count').textContent = count;
        });
    }
});
</script>
<?php endif; ?>
