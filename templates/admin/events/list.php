<?php
/**
 * Admin Events List Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap semigapp-admin-events">
    <h1 class="wp-heading-inline"><?php esc_html_e('Events', 'semigapp'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events&action=add')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'semigapp'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'created':
                        esc_html_e('Event created successfully.', 'semigapp');
                        break;
                    case 'updated':
                        esc_html_e('Event updated successfully.', 'semigapp');
                        break;
                    case 'deleted':
                        esc_html_e('Event deleted successfully.', 'semigapp');
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <ul class="subsubsub">
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events')); ?>" <?php echo empty($_GET['status']) ? 'class="current"' : ''; ?>><?php esc_html_e('All', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events&status=upcoming')); ?>" <?php echo ($_GET['status'] ?? '') === 'upcoming' ? 'class="current"' : ''; ?>><?php esc_html_e('Upcoming', 'semigapp'); ?></a> |</li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events&status=past')); ?>" <?php echo ($_GET['status'] ?? '') === 'past' ? 'class="current"' : ''; ?>><?php esc_html_e('Past', 'semigapp'); ?></a></li>
    </ul>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Event', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Date', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Location', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Registrations', 'semigapp'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Status', 'semigapp'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($events)) : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No events found.', 'semigapp'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($events as $event) : ?>
                    <tr>
                        <td class="column-title column-primary">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events&action=edit&id=' . $event->id)); ?>">
                                    <?php echo esc_html($event->title); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events&action=edit&id=' . $event->id)); ?>">
                                        <?php esc_html_e('Edit', 'semigapp'); ?>
                                    </a> |
                                </span>
                                <span class="registrations">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=semigapp-events&action=registrations&id=' . $event->id)); ?>">
                                        <?php esc_html_e('Registrations', 'semigapp'); ?>
                                    </a> |
                                </span>
                                <span class="trash">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=semigapp-events&action=delete&id=' . $event->id), 'delete_event_' . $event->id)); ?>"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this event?', 'semigapp'); ?>');">
                                        <?php esc_html_e('Delete', 'semigapp'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->start_date)));
                            ?>
                        </td>
                        <td><?php echo esc_html($event->location ?? '—'); ?></td>
                        <td>
                            <?php
                            if ($event->max_attendees > 0) {
                                printf(
                                    '%d / %d',
                                    $event->registration_count ?? 0,
                                    $event->max_attendees
                                );
                            } else {
                                echo esc_html($event->registration_count ?? 0);
                            }
                            ?>
                        </td>
                        <td>
                            <span class="semigapp-status semigapp-status-<?php echo esc_attr($event->status); ?>">
                                <?php echo esc_html(ucfirst($event->status)); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
