<?php
/**
 * Projects Module
 *
 * @package SemigApp
 */

namespace SemigApp\Modules\Projects;

use SemigApp\Modules\Base_Module;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Projects_Module
 *
 * Handles project and task management
 */
class Projects_Module extends Base_Module {

    /**
     * Module ID
     *
     * @var string
     */
    protected $id = 'projects';

    /**
     * Module name
     *
     * @var string
     */
    protected $name = 'Project Management';

    /**
     * Task statuses
     *
     * @var array
     */
    private $task_statuses = array();

    /**
     * Priorities
     *
     * @var array
     */
    private $priorities = array();

    /**
     * Initialize the module
     */
    public function init() {
        // Defer status setup to init action to avoid early textdomain loading
        add_action('init', array($this, 'setup_statuses'), 5);

        // Register hooks
        add_action('semigapp_register_shortcodes', array($this, 'register_shortcodes'));
        add_action('wp_ajax_semigapp_project_action', array($this, 'handle_ajax'));
        add_action('wp_ajax_semigapp_task_action', array($this, 'handle_task_ajax'));

        // Email notifications
        add_action('semigapp_task_assigned', array($this, 'notify_task_assigned'), 10, 2);
        add_action('semigapp_task_completed', array($this, 'notify_task_completed'), 10, 2);
    }

    /**
     * Setup task statuses and priorities
     */
    public function setup_statuses() {
        $this->task_statuses = array(
            'pending' => __('Pending', 'semigapp'),
            'in_progress' => __('In Progress', 'semigapp'),
            'review' => __('In Review', 'semigapp'),
            'completed' => __('Completed', 'semigapp'),
            'cancelled' => __('Cancelled', 'semigapp'),
        );

        $this->priorities = array(
            'low' => __('Low', 'semigapp'),
            'medium' => __('Medium', 'semigapp'),
            'high' => __('High', 'semigapp'),
            'urgent' => __('Urgent', 'semigapp'),
        );
    }

    /**
     * Get task statuses
     *
     * @return array
     */
    public function get_task_statuses() {
        return apply_filters('semigapp_task_statuses', $this->task_statuses);
    }

    /**
     * Get priorities
     *
     * @return array
     */
    public function get_priorities() {
        return apply_filters('semigapp_task_priorities', $this->priorities);
    }

    /**
     * Create a project
     *
     * @param array $data Project data.
     * @return int|false Project ID or false on failure.
     */
    public function create_project($data) {
        $schema = array(
            'title' => 'text',
            'description' => 'html',
            'status' => 'text',
            'priority' => 'text',
            'start_date' => 'datetime',
            'due_date' => 'datetime',
            'owner_id' => 'int',
            'parent_id' => 'int',
            'settings' => 'json',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        // Validate required fields
        $missing = $this->validate_required($sanitized, array('title'));
        if (!empty($missing)) {
            return false;
        }

        // Set defaults
        $sanitized['owner_id'] = $sanitized['owner_id'] ?? get_current_user_id();
        $sanitized['status'] = $sanitized['status'] ?? 'active';
        $sanitized['priority'] = $sanitized['priority'] ?? 'medium';

        $project_id = $this->db->insert('projects', $sanitized);

        if ($project_id) {
            $this->log_activity('project', $project_id, 'created', 'Project created');
            do_action('semigapp_project_created', $project_id, $sanitized);
        }

        return $project_id;
    }

    /**
     * Update a project
     *
     * @param int   $project_id Project ID.
     * @param array $data       Project data.
     * @return bool True on success.
     */
    public function update_project($project_id, $data) {
        $old_project = $this->get_project($project_id);

        if (!$old_project) {
            return false;
        }

        $schema = array(
            'title' => 'text',
            'description' => 'html',
            'status' => 'text',
            'priority' => 'text',
            'start_date' => 'datetime',
            'due_date' => 'datetime',
            'completed_date' => 'datetime',
            'settings' => 'json',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        // If marking as completed
        if (isset($sanitized['status']) && $sanitized['status'] === 'completed' && $old_project->status !== 'completed') {
            $sanitized['completed_date'] = current_time('mysql');
        }

        $result = $this->db->update('projects', $sanitized, array('id' => $project_id));

        if ($result !== false) {
            $this->log_activity('project', $project_id, 'updated', 'Project updated', $old_project, $sanitized);
            do_action('semigapp_project_updated', $project_id, $sanitized, $old_project);
        }

        return $result !== false;
    }

    /**
     * Delete a project
     *
     * @param int  $project_id   Project ID.
     * @param bool $delete_tasks Whether to delete tasks too.
     * @return bool True on success.
     */
    public function delete_project($project_id, $delete_tasks = true) {
        $project = $this->get_project($project_id);

        if (!$project) {
            return false;
        }

        // Delete associated tasks
        if ($delete_tasks) {
            $tasks = $this->get_tasks($project_id);
            foreach ($tasks as $task) {
                $this->delete_task($task->id);
            }
        }

        $result = $this->db->delete('projects', array('id' => $project_id));

        if ($result) {
            $this->log_activity('project', $project_id, 'deleted', 'Project deleted');
            do_action('semigapp_project_deleted', $project_id, $project);
        }

        return (bool) $result;
    }

    /**
     * Get a project
     *
     * @param int $project_id Project ID.
     * @return object|null Project or null.
     */
    public function get_project($project_id) {
        $project = $this->db->get_row('projects', array('id' => $project_id));

        if ($project) {
            $project->settings = json_decode($project->settings, true) ?: array();
            $project->task_count = $this->db->count('tasks', array('project_id' => $project_id));
            $project->completed_tasks = $this->db->count('tasks', array('project_id' => $project_id, 'status' => 'completed'));
        }

        return $project;
    }

    /**
     * Get projects
     *
     * @param array $args Query arguments.
     * @return array Projects.
     */
    public function get_projects($args = array()) {
        $defaults = array(
            'where' => array(),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0,
            'search' => '',
        );

        $args = wp_parse_args($args, $defaults);

        if (!empty($args['search'])) {
            $args['search_columns'] = array('title', 'description');
        }

        $projects = $this->db->get_results('projects', $args);

        if (empty($projects)) {
            return array();
        }

        // Batch load task counts to avoid N+1 query
        $project_ids = wp_list_pluck($projects, 'id');
        $task_counts = $this->get_task_counts_by_project($project_ids);

        // Add task counts and calculate progress
        foreach ($projects as &$project) {
            $project->settings = json_decode($project->settings, true) ?: array();
            $project->task_count = $task_counts[$project->id]['total'] ?? 0;
            $project->completed_tasks = $task_counts[$project->id]['completed'] ?? 0;

            if ($project->task_count > 0) {
                $project->progress = round(($project->completed_tasks / $project->task_count) * 100);
            } else {
                $project->progress = 0;
            }
        }

        return $projects;
    }

    /**
     * Get task counts grouped by project (fixes N+1 query)
     *
     * @param array $project_ids Array of project IDs.
     * @return array Associative array of project_id => ['total' => count, 'completed' => count].
     */
    private function get_task_counts_by_project($project_ids) {
        if (empty($project_ids)) {
            return array();
        }

        global $wpdb;
        $table = $this->db->get_table('tasks');
        $placeholders = implode(',', array_fill(0, count($project_ids), '%d'));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT project_id,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
             FROM $table
             WHERE project_id IN ($placeholders)
             GROUP BY project_id",
            $project_ids
        ));

        $counts = array();
        foreach ($results as $row) {
            $counts[$row->project_id] = array(
                'total' => (int) $row->total,
                'completed' => (int) $row->completed,
            );
        }

        return $counts;
    }

    /**
     * Create a task
     *
     * @param array $data Task data.
     * @return int|false Task ID or false on failure.
     */
    public function create_task($data) {
        $schema = array(
            'project_id' => 'int',
            'title' => 'text',
            'description' => 'html',
            'status' => 'text',
            'priority' => 'text',
            'due_date' => 'datetime',
            'assigned_to' => 'int',
            'created_by' => 'int',
            'position' => 'int',
            'estimated_hours' => 'float',
            'parent_id' => 'int',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        // Validate required fields
        $missing = $this->validate_required($sanitized, array('project_id', 'title'));
        if (!empty($missing)) {
            return false;
        }

        // Set defaults
        $sanitized['created_by'] = $sanitized['created_by'] ?? get_current_user_id();
        $sanitized['status'] = $sanitized['status'] ?? 'pending';
        $sanitized['priority'] = $sanitized['priority'] ?? 'medium';

        $task_id = $this->db->insert('tasks', $sanitized);

        if ($task_id) {
            $this->log_activity('task', $task_id, 'created', 'Task created');
            do_action('semigapp_task_created', $task_id, $sanitized);

            // Notify assigned user
            if (!empty($sanitized['assigned_to'])) {
                do_action('semigapp_task_assigned', $task_id, $sanitized['assigned_to']);
            }
        }

        return $task_id;
    }

    /**
     * Update a task
     *
     * @param int   $task_id Task ID.
     * @param array $data    Task data.
     * @return bool True on success.
     */
    public function update_task($task_id, $data) {
        $old_task = $this->get_task($task_id);

        if (!$old_task) {
            return false;
        }

        $schema = array(
            'title' => 'text',
            'description' => 'html',
            'status' => 'text',
            'priority' => 'text',
            'due_date' => 'datetime',
            'completed_date' => 'datetime',
            'assigned_to' => 'int',
            'position' => 'int',
            'estimated_hours' => 'float',
            'actual_hours' => 'float',
        );

        $sanitized = $this->sanitize_data($data, $schema);

        // If marking as completed
        if (isset($sanitized['status']) && $sanitized['status'] === 'completed' && $old_task->status !== 'completed') {
            $sanitized['completed_date'] = current_time('mysql');
            do_action('semigapp_task_completed', $task_id, $old_task);
        }

        // Check if assignment changed
        $assignment_changed = isset($sanitized['assigned_to']) && $sanitized['assigned_to'] != $old_task->assigned_to;

        $result = $this->db->update('tasks', $sanitized, array('id' => $task_id));

        if ($result !== false) {
            $this->log_activity('task', $task_id, 'updated', 'Task updated', $old_task, $sanitized);
            do_action('semigapp_task_updated', $task_id, $sanitized, $old_task);

            // Notify new assignee
            if ($assignment_changed && !empty($sanitized['assigned_to'])) {
                do_action('semigapp_task_assigned', $task_id, $sanitized['assigned_to']);
            }
        }

        return $result !== false;
    }

    /**
     * Delete a task
     *
     * @param int $task_id Task ID.
     * @return bool True on success.
     */
    public function delete_task($task_id) {
        $task = $this->get_task($task_id);

        if (!$task) {
            return false;
        }

        // Delete comments
        $this->db->delete('task_comments', array('task_id' => $task_id));

        // Delete subtasks
        $subtasks = $this->db->get_results('tasks', array('where' => array('parent_id' => $task_id)));
        foreach ($subtasks as $subtask) {
            $this->delete_task($subtask->id);
        }

        $result = $this->db->delete('tasks', array('id' => $task_id));

        if ($result) {
            $this->log_activity('task', $task_id, 'deleted', 'Task deleted');
            do_action('semigapp_task_deleted', $task_id, $task);
        }

        return (bool) $result;
    }

    /**
     * Get a task
     *
     * @param int $task_id Task ID.
     * @return object|null Task or null.
     */
    public function get_task($task_id) {
        $task = $this->db->get_row('tasks', array('id' => $task_id));

        if ($task) {
            $task->project = $this->get_project($task->project_id);
            $task->assignee = $task->assigned_to ? get_userdata($task->assigned_to) : null;
            $task->creator = get_userdata($task->created_by);
            $task->comments = $this->get_task_comments($task_id);
            $task->subtasks = $this->get_subtasks($task_id);
        }

        return $task;
    }

    /**
     * Get tasks for a project
     *
     * @param int   $project_id Project ID.
     * @param array $args       Query arguments.
     * @return array Tasks.
     */
    public function get_tasks($project_id, $args = array()) {
        $defaults = array(
            'where' => array('project_id' => $project_id, 'parent_id' => 0),
            'orderby' => 'position',
            'order' => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);
        $args['where']['project_id'] = $project_id;

        $tasks = $this->db->get_results('tasks', $args);

        foreach ($tasks as &$task) {
            $task->assignee = $task->assigned_to ? get_userdata($task->assigned_to) : null;
            $task->subtasks = $this->get_subtasks($task->id);
        }

        return $tasks;
    }

    /**
     * Get subtasks
     *
     * @param int $parent_id Parent task ID.
     * @return array Subtasks.
     */
    public function get_subtasks($parent_id) {
        return $this->db->get_results('tasks', array(
            'where' => array('parent_id' => $parent_id),
            'orderby' => 'position',
            'order' => 'ASC',
        ));
    }

    /**
     * Add task comment
     *
     * @param int    $task_id Task ID.
     * @param string $content Comment content.
     * @param int    $user_id User ID (optional).
     * @return int|false Comment ID or false.
     */
    public function add_task_comment($task_id, $content, $user_id = null) {
        $task = $this->get_task($task_id);

        if (!$task) {
            return false;
        }

        $comment_id = $this->db->insert('task_comments', array(
            'task_id' => $task_id,
            'user_id' => $user_id ?? get_current_user_id(),
            'content' => wp_kses_post($content),
        ));

        if ($comment_id) {
            do_action('semigapp_task_comment_added', $comment_id, $task_id);
        }

        return $comment_id;
    }

    /**
     * Get task comments
     *
     * @param int $task_id Task ID.
     * @return array Comments.
     */
    public function get_task_comments($task_id) {
        $comments = $this->db->get_results('task_comments', array(
            'where' => array('task_id' => $task_id),
            'orderby' => 'created_at',
            'order' => 'ASC',
        ));

        foreach ($comments as &$comment) {
            $comment->user = get_userdata($comment->user_id);
        }

        return $comments;
    }

    /**
     * Log time on task
     *
     * @param int   $task_id Task ID.
     * @param float $hours   Hours to log.
     * @return bool True on success.
     */
    public function log_time($task_id, $hours) {
        $task = $this->get_task($task_id);

        if (!$task) {
            return false;
        }

        $new_hours = floatval($task->actual_hours) + floatval($hours);

        return $this->update_task($task_id, array('actual_hours' => $new_hours));
    }

    /**
     * Get user tasks
     *
     * @param int   $user_id User ID.
     * @param array $args    Query arguments.
     * @return array Tasks.
     */
    public function get_user_tasks($user_id, $args = array()) {
        $defaults = array(
            'where' => array('assigned_to' => $user_id),
            'orderby' => 'due_date',
            'order' => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);
        $args['where']['assigned_to'] = $user_id;

        $tasks = $this->db->get_results('tasks', $args);

        foreach ($tasks as &$task) {
            $task->project = $this->get_project($task->project_id);
        }

        return $tasks;
    }

    /**
     * Get overdue tasks
     *
     * @param int $user_id User ID (optional).
     * @return array Overdue tasks.
     */
    public function get_overdue_tasks($user_id = null) {
        global $wpdb;

        $table = $this->db->get_table('tasks');
        $sql = "SELECT * FROM $table WHERE due_date < %s AND status NOT IN ('completed', 'cancelled')";
        $values = array(current_time('mysql'));

        if ($user_id) {
            $sql .= " AND assigned_to = %d";
            $values[] = $user_id;
        }

        $sql .= " ORDER BY due_date ASC";

        return $wpdb->get_results($wpdb->prepare($sql, $values));
    }

    /**
     * Get project statistics
     *
     * @param int $project_id Project ID.
     * @return array Statistics.
     */
    public function get_project_stats($project_id) {
        $tasks = $this->get_tasks($project_id, array('where' => array('project_id' => $project_id)));

        $stats = array(
            'total_tasks' => count($tasks),
            'completed' => 0,
            'in_progress' => 0,
            'pending' => 0,
            'overdue' => 0,
            'estimated_hours' => 0,
            'actual_hours' => 0,
        );

        $now = current_time('mysql');

        foreach ($tasks as $task) {
            $stats[$task->status] = ($stats[$task->status] ?? 0) + 1;
            $stats['estimated_hours'] += floatval($task->estimated_hours);
            $stats['actual_hours'] += floatval($task->actual_hours);

            if ($task->due_date && $task->due_date < $now && !in_array($task->status, array('completed', 'cancelled'))) {
                $stats['overdue']++;
            }
        }

        if ($stats['total_tasks'] > 0) {
            $stats['progress'] = round(($stats['completed'] / $stats['total_tasks']) * 100);
        } else {
            $stats['progress'] = 0;
        }

        return $stats;
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('semigapp_projects', array($this, 'projects_shortcode'));
        add_shortcode('semigapp_my_tasks', array($this, 'my_tasks_shortcode'));
        add_shortcode('semigapp_project_board', array($this, 'project_board_shortcode'));
    }

    /**
     * Projects list shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function projects_shortcode($atts) {
        $atts = shortcode_atts(array(
            'status' => 'active',
            'limit' => 10,
            'owner' => '',
        ), $atts);

        $args = array(
            'where' => array(),
            'limit' => intval($atts['limit']),
        );

        if ($atts['status']) {
            $args['where']['status'] = $atts['status'];
        }

        if ($atts['owner'] === 'current') {
            $args['where']['owner_id'] = get_current_user_id();
        }

        $projects = $this->get_projects($args);

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/projects/list.php';
        return ob_get_clean();
    }

    /**
     * My tasks shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function my_tasks_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your tasks.', 'semigapp') . '</p>';
        }

        $atts = shortcode_atts(array(
            'status' => '',
            'limit' => 20,
        ), $atts);

        $args = array(
            'limit' => intval($atts['limit']),
        );

        if ($atts['status']) {
            $args['where']['status'] = $atts['status'];
        }

        $tasks = $this->get_user_tasks(get_current_user_id(), $args);

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/projects/my-tasks.php';
        return ob_get_clean();
    }

    /**
     * Project board shortcode (Kanban-style)
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function project_board_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        $project = $this->get_project(intval($atts['id']));

        if (!$project) {
            return '<p>' . __('Project not found.', 'semigapp') . '</p>';
        }

        $tasks = $this->get_tasks($project->id);
        $statuses = $this->get_task_statuses();

        ob_start();
        include SEMIGAPP_PLUGIN_DIR . 'templates/frontend/projects/board.php';
        return ob_get_clean();
    }

    /**
     * Notify when task is assigned
     *
     * @param int $task_id Task ID.
     * @param int $user_id Assigned user ID.
     */
    public function notify_task_assigned($task_id, $user_id) {
        $task = $this->get_task($task_id);
        $user = get_userdata($user_id);

        if (!$task || !$user) {
            return;
        }

        $this->send_notification('task_assigned', $user->user_email, array(
            'task_title' => $task->title,
            'project_name' => $task->project ? $task->project->title : '',
            'due_date' => $task->due_date ? $this->format_date($task->due_date) : '',
            'task_url' => add_query_arg('task_id', $task_id, home_url('/my-tasks/')),
            'first_name' => $user->first_name ?: $user->display_name,
        ));
    }

    /**
     * Notify when task is completed
     *
     * @param int    $task_id  Task ID.
     * @param object $old_task Old task data.
     */
    public function notify_task_completed($task_id, $old_task) {
        $task = $this->get_task($task_id);

        if (!$task || !$task->project) {
            return;
        }

        $owner = get_userdata($task->project->owner_id);

        if (!$owner || $owner->ID === get_current_user_id()) {
            return;
        }

        $this->send_notification('task_completed', $owner->user_email, array(
            'task_title' => $task->title,
            'project_name' => $task->project->title,
            'completed_by' => wp_get_current_user()->display_name,
            'first_name' => $owner->first_name ?: $owner->display_name,
        ));
    }

    /**
     * Handle AJAX requests for projects
     */
    public function handle_ajax() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        // Require user to be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action.', 'semigapp')));
        }

        // Require manage projects capability or fallback to edit_posts for project operations
        if (!current_user_can('manage_semigapp_projects') && !current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'semigapp')));
        }

        $action = isset($_POST['project_action']) ? sanitize_text_field($_POST['project_action']) : '';

        switch ($action) {
            case 'create':
                $result = $this->create_project($_POST);
                break;
            case 'update':
                $project_id = isset($_POST['project_id']) ? absint($_POST['project_id']) : 0;
                if (!$project_id || !$this->can_edit_project($project_id)) {
                    wp_send_json_error(array('message' => __('You cannot edit this project.', 'semigapp')));
                }
                $result = $this->update_project($project_id, $_POST);
                break;
            case 'delete':
                $project_id = isset($_POST['project_id']) ? absint($_POST['project_id']) : 0;
                if (!$project_id || !$this->can_edit_project($project_id)) {
                    wp_send_json_error(array('message' => __('You cannot delete this project.', 'semigapp')));
                }
                $result = $this->delete_project($project_id);
                break;
            default:
                wp_send_json_error(array('message' => __('Invalid action', 'semigapp')));
        }

        if ($result) {
            wp_send_json_success(array('id' => $result));
        } else {
            wp_send_json_error(array('message' => __('Operation failed', 'semigapp')));
        }
    }

    /**
     * Handle AJAX requests for tasks
     */
    public function handle_task_ajax() {
        check_ajax_referer('semigapp_frontend', 'nonce');

        // Require user to be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action.', 'semigapp')));
        }

        $action = isset($_POST['task_action']) ? sanitize_text_field($_POST['task_action']) : '';
        $task_id = isset($_POST['task_id']) ? absint($_POST['task_id']) : 0;

        // For actions that modify tasks, check permissions
        if (in_array($action, array('update', 'delete', 'update_status', 'add_comment', 'log_time'))) {
            if (!$task_id || !$this->can_edit_task($task_id)) {
                wp_send_json_error(array('message' => __('You cannot modify this task.', 'semigapp')));
            }
        }

        switch ($action) {
            case 'create':
                if (!current_user_can('edit_posts')) {
                    wp_send_json_error(array('message' => __('You do not have permission to create tasks.', 'semigapp')));
                }
                $result = $this->create_task($_POST);
                break;
            case 'update':
                $result = $this->update_task($task_id, $_POST);
                break;
            case 'delete':
                $result = $this->delete_task($task_id);
                break;
            case 'update_status':
                $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
                $result = $this->update_task($task_id, array('status' => $status));
                break;
            case 'add_comment':
                $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
                $result = $this->add_task_comment($task_id, $content);
                break;
            case 'log_time':
                $hours = isset($_POST['hours']) ? floatval($_POST['hours']) : 0;
                $result = $this->log_time($task_id, $hours);
                break;
            default:
                wp_send_json_error(array('message' => __('Invalid action', 'semigapp')));
        }

        if ($result) {
            wp_send_json_success(array('id' => $result));
        } else {
            wp_send_json_error(array('message' => __('Operation failed', 'semigapp')));
        }
    }

    /**
     * Check if current user can edit a project
     *
     * @param int $project_id Project ID.
     * @return bool True if user can edit.
     */
    private function can_edit_project($project_id) {
        if (current_user_can('manage_options')) {
            return true;
        }

        $project = $this->get_project($project_id);
        if (!$project) {
            return false;
        }

        return $project->owner_id == get_current_user_id();
    }

    /**
     * Check if current user can edit a task
     *
     * @param int $task_id Task ID.
     * @return bool True if user can edit.
     */
    private function can_edit_task($task_id) {
        if (current_user_can('manage_options')) {
            return true;
        }

        $task = $this->get_task($task_id);
        if (!$task) {
            return false;
        }

        // Can edit if assigned to or is project owner
        if ($task->assigned_to == get_current_user_id()) {
            return true;
        }

        $project = $this->get_project($task->project_id);
        return $project && $project->owner_id == get_current_user_id();
    }
}
