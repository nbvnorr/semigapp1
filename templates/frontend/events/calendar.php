<?php
/**
 * Events Calendar Template
 *
 * @package SemigApp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Calculate calendar variables
$current_month = isset($month) ? absint($month) : (int) date('n');
$current_year = isset($year) ? absint($year) : (int) date('Y');

$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = (int) date('t', $first_day);
$start_weekday = (int) date('w', $first_day);

$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Group events by date
$events_by_date = array();
if (!empty($events)) {
    foreach ($events as $event) {
        $event_date = date('Y-m-d', strtotime($event->start_date));
        if (!isset($events_by_date[$event_date])) {
            $events_by_date[$event_date] = array();
        }
        $events_by_date[$event_date][] = $event;
    }
}
?>

<div class="semigapp-events-calendar">
    <div class="calendar-header">
        <a href="<?php echo esc_url(add_query_arg(array('month' => $prev_month, 'year' => $prev_year))); ?>"
           class="calendar-nav calendar-prev">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <span class="screen-reader-text"><?php esc_html_e('Previous Month', 'semigapp'); ?></span>
        </a>

        <h2 class="calendar-title">
            <?php echo esc_html(date_i18n('F Y', $first_day)); ?>
        </h2>

        <a href="<?php echo esc_url(add_query_arg(array('month' => $next_month, 'year' => $next_year))); ?>"
           class="calendar-nav calendar-next">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
            <span class="screen-reader-text"><?php esc_html_e('Next Month', 'semigapp'); ?></span>
        </a>
    </div>

    <table class="calendar-grid">
        <thead>
            <tr>
                <?php
                $week_starts = get_option('start_of_week', 0);
                $days = array(
                    __('Sun', 'semigapp'),
                    __('Mon', 'semigapp'),
                    __('Tue', 'semigapp'),
                    __('Wed', 'semigapp'),
                    __('Thu', 'semigapp'),
                    __('Fri', 'semigapp'),
                    __('Sat', 'semigapp'),
                );
                for ($i = 0; $i < 7; $i++) {
                    $day_index = ($i + $week_starts) % 7;
                    echo '<th>' . esc_html($days[$day_index]) . '</th>';
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $adjusted_start = ($start_weekday - $week_starts + 7) % 7;
            $day = 1;
            $today = date('Y-m-d');

            for ($row = 0; $row < 6; $row++) {
                if ($day > $days_in_month) {
                    break;
                }

                echo '<tr>';

                for ($col = 0; $col < 7; $col++) {
                    if (($row === 0 && $col < $adjusted_start) || $day > $days_in_month) {
                        echo '<td class="calendar-day empty"></td>';
                    } else {
                        $date_str = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                        $is_today = ($date_str === $today);
                        $has_events = isset($events_by_date[$date_str]);
                        $day_events = $has_events ? $events_by_date[$date_str] : array();

                        $classes = array('calendar-day');
                        if ($is_today) {
                            $classes[] = 'today';
                        }
                        if ($has_events) {
                            $classes[] = 'has-events';
                        }
                        ?>
                        <td class="<?php echo esc_attr(implode(' ', $classes)); ?>">
                            <span class="day-number"><?php echo esc_html($day); ?></span>
                            <?php if ($has_events) : ?>
                                <div class="day-events">
                                    <?php foreach ($day_events as $event) : ?>
                                        <a href="<?php echo esc_url(add_query_arg('event_id', $event->id)); ?>"
                                           class="day-event"
                                           title="<?php echo esc_attr($event->title); ?>">
                                            <span class="event-time"><?php echo esc_html(date_i18n('H:i', strtotime($event->start_date))); ?></span>
                                            <span class="event-name"><?php echo esc_html(wp_trim_words($event->title, 3)); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php
                        $day++;
                    }
                }

                echo '</tr>';
            }
            ?>
        </tbody>
    </table>

    <div class="calendar-legend">
        <span class="legend-item">
            <span class="legend-dot today"></span>
            <?php esc_html_e('Today', 'semigapp'); ?>
        </span>
        <span class="legend-item">
            <span class="legend-dot has-events"></span>
            <?php esc_html_e('Has Events', 'semigapp'); ?>
        </span>
    </div>

    <?php if (!empty($events)) : ?>
        <div class="calendar-events-list">
            <h3><?php esc_html_e('Upcoming Events This Month', 'semigapp'); ?></h3>
            <ul>
                <?php foreach ($events as $event) : ?>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('event_id', $event->id)); ?>">
                            <span class="event-date">
                                <?php echo esc_html(date_i18n('M j', strtotime($event->start_date))); ?>
                            </span>
                            <span class="event-title"><?php echo esc_html($event->title); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
