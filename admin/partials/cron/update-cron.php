<?php //Update Aben cron on Plugin settings update

if (!defined('ABSPATH')) {
    exit;
}

if (! function_exists('aben_as_available')) {
    function aben_as_available()
    {
        return function_exists('as_schedule_recurring_action')
            && function_exists('as_unschedule_all_actions');
    }
}

add_action('update_option_aben_options', 'aben_update_cron');

function aben_update_cron()
{
    if (! aben_as_available()) {
        return;
    }
    $cron_settings = aben_get_cron_settings();
    $interval      = (int) $cron_settings['interval'];
    $day_of_week   = intval($cron_settings['day_of_week']);
    $email_time    = intval($cron_settings['email_time']); // UNIX timestamp for time
    $timezone = wp_timezone_string(); // Get site's timezone

    // Create DateTime object for the email time in the site's timezone
    $email_datetime = new DateTime('@' . $email_time);
    $email_datetime->setTimezone(new DateTimeZone($timezone)); // Set the timezone

    // Get the current time in UNIX timestamp
    $current_time = time();

    as_unschedule_all_actions('aben_send_email_action', [], 'aben');

    // Schedule for Daily
    if ($interval === DAY_IN_SECONDS) {
        // Set the timestamp for today in user's timezone
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

        // Schedule for Weekly
    } elseif ($interval === WEEK_IN_SECONDS) {
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
    }
}
