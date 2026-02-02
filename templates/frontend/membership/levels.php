<?php
/**
 * Membership Levels Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$columns_class = isset($atts['columns']) ? 'columns-' . intval($atts['columns']) : 'columns-3';
?>

<div class="semigapp-membership-levels <?php echo esc_attr($columns_class); ?>">
    <?php if (empty($levels)) : ?>
        <p class="semigapp-no-levels"><?php esc_html_e('No membership levels available.', 'semigapp'); ?></p>
    <?php else : ?>
        <div class="semigapp-levels-grid">
            <?php foreach ($levels as $level) : ?>
                <div class="semigapp-level-card" data-level-id="<?php echo esc_attr($level->id); ?>">
                    <div class="semigapp-level-header">
                        <h3 class="semigapp-level-name"><?php echo esc_html($level->name); ?></h3>
                        <div class="semigapp-level-price">
                            <?php if ($level->price > 0) : ?>
                                <span class="price-amount"><?php echo esc_html(number_format($level->price, 0)); ?></span>
                                <span class="price-currency"><?php esc_html_e('SEK', 'semigapp'); ?></span>
                                <span class="price-period">
                                    / <?php echo esc_html($level->duration . ' ' . $level->duration_unit); ?>
                                </span>
                            <?php else : ?>
                                <span class="price-free"><?php esc_html_e('Free', 'semigapp'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="semigapp-level-body">
                        <?php if (!empty($level->description)) : ?>
                            <div class="semigapp-level-description">
                                <?php echo wp_kses_post($level->description); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($level->features)) : ?>
                            <ul class="semigapp-level-features">
                                <?php foreach ($level->features as $feature) : ?>
                                    <li class="feature-item">
                                        <span class="feature-icon">✓</span>
                                        <span class="feature-text"><?php echo esc_html($feature); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($level->trial_period > 0) : ?>
                            <p class="semigapp-trial-info">
                                <?php
                                printf(
                                    esc_html__('%d %s free trial', 'semigapp'),
                                    intval($level->trial_period),
                                    esc_html($level->trial_unit ?: 'days')
                                );
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="semigapp-level-footer">
                        <?php if (is_user_logged_in()) : ?>
                            <button type="button"
                                    class="semigapp-btn semigapp-btn-primary semigapp-signup-btn"
                                    data-level-id="<?php echo esc_attr($level->id); ?>">
                                <?php esc_html_e('Join Now', 'semigapp'); ?>
                            </button>
                        <?php else : ?>
                            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>"
                               class="semigapp-btn semigapp-btn-primary">
                                <?php esc_html_e('Login to Join', 'semigapp'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
