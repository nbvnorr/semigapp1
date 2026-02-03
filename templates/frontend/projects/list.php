<?php
/**
 * Projects List Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="semigapp-projects-list">
    <?php if (empty($projects)) : ?>
        <div class="semigapp-no-projects">
            <p><?php esc_html_e('No projects found.', 'semigapp'); ?></p>
        </div>
    <?php else : ?>
        <div class="semigapp-projects-grid">
            <?php foreach ($projects as $project) : ?>
                <article class="semigapp-project-card status-<?php echo esc_attr($project->status); ?>"
                         data-project-id="<?php echo esc_attr($project->id); ?>">
                    <div class="project-header">
                        <h3 class="project-title">
                            <a href="<?php echo esc_url(add_query_arg('project_id', $project->id)); ?>">
                                <?php echo esc_html($project->title); ?>
                            </a>
                        </h3>
                        <span class="project-status semigapp-status semigapp-status-<?php echo esc_attr($project->status); ?>">
                            <?php echo esc_html(ucfirst(str_replace('-', ' ', $project->status))); ?>
                        </span>
                    </div>

                    <?php if (!empty($project->description)) : ?>
                        <div class="project-description">
                            <?php echo wp_kses_post(wp_trim_words($project->description, 20)); ?>
                        </div>
                    <?php endif; ?>

                    <div class="project-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo esc_attr($project->progress); ?>%;"></div>
                        </div>
                        <span class="progress-text"><?php echo esc_html($project->progress); ?>%</span>
                    </div>

                    <div class="project-meta">
                        <span class="project-tasks">
                            <?php printf(
                                esc_html__('%d/%d tasks completed', 'semigapp'),
                                $project->completed_tasks,
                                $project->task_count
                            ); ?>
                        </span>

                        <?php if (!empty($project->end_date)) : ?>
                            <span class="project-deadline">
                                <?php printf(
                                    esc_html__('Due: %s', 'semigapp'),
                                    date_i18n(get_option('date_format'), strtotime($project->end_date))
                                ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
