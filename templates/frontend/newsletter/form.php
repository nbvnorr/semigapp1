<?php
/**
 * Newsletter Form Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="semigapp-newsletter-form-wrapper">
    <form class="semigapp-newsletter-form" method="post">
        <?php wp_nonce_field('semigapp_frontend', 'nonce'); ?>

        <?php if (!empty($atts['list_id'])) : ?>
            <input type="hidden" name="list_id" value="<?php echo esc_attr($atts['list_id']); ?>">
        <?php endif; ?>

        <?php if ($atts['show_name'] === 'yes') : ?>
            <div class="semigapp-form-group">
                <input type="text"
                       name="first_name"
                       class="semigapp-input"
                       placeholder="<?php esc_attr_e('First Name', 'semigapp'); ?>">
            </div>
        <?php endif; ?>

        <div class="semigapp-form-row">
            <input type="email"
                   name="email"
                   class="semigapp-input"
                   placeholder="<?php esc_attr_e('Email Address', 'semigapp'); ?>"
                   required>
            <button type="submit" class="semigapp-button semigapp-button-primary" data-text="<?php echo esc_attr($atts['button_text']); ?>">
                <?php echo esc_html($atts['button_text']); ?>
            </button>
        </div>

        <?php if (!empty($gdpr_text)) : ?>
            <div class="semigapp-form-group">
                <label class="semigapp-checkbox">
                    <input type="checkbox" name="gdpr_consent" required>
                    <span><?php echo wp_kses_post($gdpr_text); ?></span>
                </label>
            </div>
        <?php endif; ?>

        <div class="semigapp-newsletter-message" style="display: none;"></div>
    </form>
</div>
