<?php
// Send Email

if (!defined('ABSPATH')) {
    exit;
}

// Safety check logging
if (defined('WP_DEBUG') && WP_DEBUG) {
    $context = defined('DOING_CRON') && DOING_CRON ? 'CRON' : (is_admin() ? 'ADMIN' : 'FRONTEND');
}

require_once __DIR__ . '/email-build.php';

// Hook the new batch processor
add_action('aben_process_email_batch', 'aben_process_email_batch_worker', 10, 2);

/**
 * Main function: Starts the first batch with Offset 0
 */
function aben_send_email()
{
    $posts_result = aben_get_posts_for_email();
    if (empty($posts_result['posts_to_email'])) return false;

    $batch_id = uniqid('aben_', true);
    set_transient("aben_posts_{$batch_id}", $posts_result, HOUR_IN_SECONDS);

    // Schedule only the FIRST batch worker (Offset 0)
    as_enqueue_async_action('aben_process_email_batch', [$batch_id, 0], 'aben-auto');
    return true;
}

/**
 * Worker: Queries 50 users at a time and schedules the next batch
 */
add_action('aben_process_email_batch', 'aben_process_email_batch_worker', 10, 2);
function aben_process_email_batch_worker($batch_id, $offset)
{
    global $wpdb;
    // Get options to see which role we are targeting
    $options = aben_get_options();
    $target_role = $options['user_roles'];
    if (empty($target_role)) return;

    $limit = 50; // Small chunk size

    /**
     * Optimized SQL:
     * - Filters by aben_notification = 1
     * - Filters by User Role (stored in the capabilities meta key)
     */
    $query = $wpdb->prepare(
        "SELECT u.user_email
         FROM {$wpdb->users} u
         INNER JOIN {$wpdb->usermeta} m_opt ON u.ID = m_opt.user_id
            AND m_opt.meta_key = 'aben_notification'
            AND m_opt.meta_value = '1'
         INNER JOIN {$wpdb->usermeta} m_role ON u.ID = m_role.user_id
            AND m_role.meta_key = '{$wpdb->prefix}capabilities'
         WHERE m_role.meta_value REGEXP %s
         LIMIT %d OFFSET %d",
        '"' . $target_role . '"',
        $limit,
        $offset
    );

    $results = $wpdb->get_col($query);

    if (empty($results)) return; // Done!

    // Enqueue individual email tasks for this chunk
    foreach ($results as $user_email) {
        as_enqueue_async_action('aben_send_single_email_worker', [[$user_email, $batch_id, 1]], 'aben-auto');
    }

    // Recurse: Schedule the next 50 users
    if (count($results) >= $limit) {
        as_enqueue_async_action('aben_process_email_batch', [$batch_id, $offset + $limit], 'aben-auto');
    }
}

/**
 * Worker function: Sends single email
 * (Standard logic, unchanged)
 */
function aben_send_single_email_worker($args)
{
    // Parse arguments
    if (is_array($args)) {
        if (isset($args[0]) && !isset($args['email'])) {
            $email_address = $args[0] ?? '';
            $batch_id = $args[1] ?? '';
            $attempt = $args[2] ?? 1;
        } else {
            $email_address = $args['email'] ?? '';
            $batch_id = $args['batch_id'] ?? '';
            $attempt = $args['attempt'] ?? 1;
        }
    } else {
        return;
    }

    if (empty($email_address) || empty($batch_id)) {
        return;
    }

    $email_address = sanitize_email($email_address);
    $batch_id = sanitize_text_field($batch_id);
    $attempt = (int)$attempt;

    // Get user
    $user = get_user_by('email', $email_address);
    if (!$user) {
        return;
    }

    // Check subscription
    $is_subscribed = get_user_meta($user->ID, 'aben_notification', true);
    if ('1' !== (string)$is_subscribed) {
        return;
    }

    // Get posts from transient
    $posts_result = get_transient("aben_posts_{$batch_id}");

    if (false === $posts_result || empty($posts_result['posts_to_email'])) {
        return;
    }

    // Get settings
    $settings = aben_get_options();
    if (empty($settings)) {
        return;
    }

    // Build email
    $email_obj = new Aben_Email(
        $settings['archive_page_slug'],
        $settings['number_of_posts'],
        $settings['body_bg'],
        $settings['header_text'],
        $settings['header_bg'],
        $settings['header_subtext'],
        $settings['footer_text'],
        $settings['site-logo'],
        $settings['show_view_all'],
        $settings['view_all_posts_text'],
        $settings['show_view_post'],
        $settings['view_post_text'],
        $settings['show_unsubscribe'],
        $posts_result['posts_to_email']
    );

    ob_start();
    $email_obj->aben_email_template();
    $email_body = ob_get_clean();

    // Personalize
    $user_name_parts = explode(' ', ucfirst($user->display_name));
    $user_firstname = $user_name_parts[0];

    $auto_login_token = function_exists('aben_generate_login_token')
        ? aben_generate_login_token($email_address)
        : '';

    $personalized_body = str_replace(
        ['{{USERNAME}}', '{{USER_EMAIL}}', '{{TOKEN}}'],
        [$user_firstname, $email_address, $auto_login_token],
        $email_body
    );

    // Apply filters
    $tracking_id = apply_filters('aben_before_email_sent_filter', null);
    $personalized_body = apply_filters(
        'aben_email_template_html_filter',
        $personalized_body,
        $tracking_id,
        $user->ID
    );

    $sent = aben_send_smtp_email(
        $email_address,
        $settings['email_subject'],
        $personalized_body
    );

    if ($sent) {
        do_action('aben_after_email_sent_action', $tracking_id, $user->ID);
    } else {
        do_action('aben_email_send_failed', $email_address, $user->ID, $attempt);

        if ($attempt < 3) {
            as_schedule_single_action(
                time() + 300,
                'aben_send_single_email_worker',
                [[$email_address, $batch_id, $attempt + 1]],
                'aben-auto'
            );
        }
    }
}

/**
 * Get posts for email based on frequency settings
 */
function aben_get_posts_for_email()
{
    $settings = aben_get_options();
    $email_frequency = $settings['email_frequency'];
    $day_of_week = intval($settings['day_of_week']);

    if ($email_frequency === 'once_in_a_week') {
        return aben_get_weekly_posts($day_of_week);
    }

    return aben_get_today_posts();
}
