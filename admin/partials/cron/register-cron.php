<?php // Set ABEN Cron
if (!defined('ABSPATH')) {

    exit;
}

if (! function_exists('aben_as_available')) {
    function aben_as_available()
    {
        return function_exists('as_schedule_recurring_action') && function_exists('as_unschedule_all_actions');
    }
}

add_action('aben_send_email_action', 'aben_send_email');

function aben_register_cron()
{
    if (! aben_as_available()) {
        return;
    }

    // error_log('aben_register_cron called');

    $cron_settings = aben_get_cron_settings();
    $interval      = (int) $cron_settings['interval'];
    $day_of_week = intval($cron_settings['day_of_week']);
    $email_time = intval($cron_settings['email_time']); // UNIX timestamp for time
    $timezone = wp_timezone_string(); // Get site's timezone

    // Create DateTime object for the email time in the site's timezone
    $email_datetime = new DateTime('@' . $email_time);
    $email_datetime->setTimezone(new DateTimeZone($timezone)); // Set the timezone

    // Get the current time in UNIX timestamp
    $current_time = time();

    if ($cron_settings['sending_frequency'] === 'daily') {
        if (! as_next_scheduled_action('aben_send_email_action', [], 'aben')) {
            // Set the email time in the user's timezone
            $today_timestamp = (new DateTime('now', new DateTimeZone($timezone)))
                ->setTime($email_datetime->format('H'), $email_datetime->format('i'))
                ->getTimestamp();

            // If the time has already passed for today, schedule for tomorrow
            if ($current_time >= $today_timestamp) {
                $today_timestamp += DAY_IN_SECONDS; // Move to the next day
            }

            as_schedule_recurring_action(
                $today_timestamp,
                $interval,
                'aben_send_email_action',
                [],
                'aben'
            );
            // error_log('Daily aben_send_email_action scheduled at ' . date('Y-m-d H:i:s', $today_timestamp));
        }
    } elseif ($cron_settings['sending_frequency'] === 'weekly') {
        if (! as_next_scheduled_action('aben_send_email_action', [], 'aben')) {
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

            as_schedule_recurring_action(
                $timestamp_weekly,
                $interval,
                'aben_send_email_action',
                [],
                'aben'
            );
            // error_log('Weekly aben_send_email_action scheduled at ' . date('Y-m-d H:i:s', $timestamp_weekly));
        }
    }
}

function aben_deregister_cron()
{
    if (! aben_as_available()) {
        return;
    }

    as_unschedule_all_actions('aben_send_email_action', [], 'aben');
}
