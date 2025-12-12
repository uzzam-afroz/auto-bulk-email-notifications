<?php // Set ABEN Cron
if (!defined('ABSPATH')) {

    exit;

}

add_action('aben_cron_event', 'aben_send_email');

function aben_register_cron()
{
    // error_log('aben_register_cron called');

    $cron_settings = aben_get_cron_settings()['sending_frequency'];
    $day_of_week = intval(aben_get_cron_settings()['day_of_week']);
    $email_time = intval(aben_get_cron_settings()['email_time']); // UNIX timestamp for time
    $timezone = wp_timezone_string(); // Get site's timezone

    // Create DateTime object for the email time in the site's timezone
    $email_datetime = new DateTime('@' . $email_time);
    $email_datetime->setTimezone(new DateTimeZone($timezone)); // Set the timezone

    // Get the current time in UNIX timestamp
    $current_time = time();

    // Schedule for Daily
    if ($cron_settings === 'daily') {
        if (!wp_next_scheduled('aben_cron_event')) {
            // Set the email time in the user's timezone
            $today_timestamp = (new DateTime('now', new DateTimeZone($timezone)))
                ->setTime($email_datetime->format('H'), $email_datetime->format('i'))
                ->getTimestamp();

            // If the time has already passed for today, schedule for tomorrow
            if ($current_time >= $today_timestamp) {
                $today_timestamp += DAY_IN_SECONDS; // Move to the next day
            }

            wp_schedule_event($today_timestamp, 'daily', 'aben_cron_event');
            // error_log('Daily aben_cron_event scheduled at ' . date('Y-m-d H:i:s', $today_timestamp));
        }
    }

    // Schedule for Weekly
    else if ($cron_settings === 'weekly') {
        if (!wp_next_scheduled('aben_cron_event')) {
            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            // Ensure valid day of the week
            $day_name = isset($days[$day_of_week]) ? $days[$day_of_week] : 'Saturday'; // Default to Saturday if invalid

            // Create a DateTime for the next occurrence of the specified day
            $next_occurrence = new DateTime('next ' . $day_name, new DateTimeZone($timezone));
            $next_occurrence->setTime($email_datetime->format('H'), $email_datetime->format('i')); // Set time

            // Get the timestamp for the next occurrence
            $timestamp_weekly = $next_occurrence->getTimestamp();

            // If the time has already passed for the next occurrence, schedule for the next week
            if ($current_time >= $timestamp_weekly) {
                $timestamp_weekly += WEEK_IN_SECONDS; // Move to the next week
            }

            wp_schedule_event($timestamp_weekly, 'weekly', 'aben_cron_event');
            // error_log('Weekly aben_cron_event scheduled at ' . date('Y-m-d H:i:s', $timestamp_weekly));
        }
    }
}

function aben_deregister_cron()
{
    // error_log('aben_deregister_cron called');
    wp_clear_scheduled_hook('aben_cron_event');
}