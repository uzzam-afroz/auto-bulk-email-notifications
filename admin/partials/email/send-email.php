<?php
// Send Email

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/email-build.php';

$aben_settings = aben_get_options();

function aben_send_email()
{
    // error_log('aben_send_email function was called at ' . current_time('mysql'));

    $aben_get_posts_result = aben_get_posts_for_email();

    if ($aben_get_posts_result) {

        global $aben_settings;

        $email_obj = new Aben_Email(
            $aben_settings['archive_page_slug'],
            $aben_settings['number_of_posts'],
            $aben_settings['body_bg'],
            $aben_settings['header_text'],
            $aben_settings['header_bg'],
            $aben_settings['header_subtext'],
            $aben_settings['footer_text'],
            $aben_settings['site-logo'],
            $aben_settings['show_view_all'],
            $aben_settings['view_all_posts_text'],
            $aben_settings['show_view_post'],
            $aben_settings['view_post_text'],
            $aben_settings['show_unsubscribe'],
            $aben_get_posts_result['posts_to_email'],
        );
        ob_start();
        $email_obj->aben_email_template();
        $email_template = ob_get_clean();
    }

    if (! empty($aben_get_posts_result)) {

        $posts_published_today = $aben_get_posts_result['posts_published'];

        $posts_to_send = $aben_get_posts_result['posts_to_email'];

        $post_count = count($posts_to_send);
    }

    if (! empty($posts_published_today)) {

        $post_archive_slug = $aben_settings['archive_page_slug'];

        $email_subject = $aben_settings['email_subject'];

        $email_body = $email_template;

        $admin_email = get_bloginfo('admin_email');

        $headers[] = 'Content-Type:text/html';

        $email_addresses = aben_get_users_email();

        if (! empty($email_addresses)) {

            foreach ($email_addresses as $email_address) {

                $user = get_user_by('email', $email_address);

                $user_display_name = ucfirst($user->display_name);

                $user_display_name = explode(' ', $user_display_name);

                $user_firstname = $user_display_name[0];

                if (function_exists('aben_generate_login_token')) {
                    $auto_login_token = aben_generate_login_token($email_address);
                } else {
                    $auto_login_token = '';
                }

                $personalized_email_body = str_replace(

                    ['{{USERNAME}}', '{{USER_EMAIL}}', '{{TOKEN}}'],
                    [$user_firstname, $email_address, $auto_login_token],
                    $email_body

                );
                $tracking_id = apply_filters('aben_before_email_sent_filter', null);
                $personalized_email_body = apply_filters('aben_email_template_html_filter', $personalized_email_body, $tracking_id, $user->ID);
                if (aben_send_smtp_email($email_address, $email_subject, $personalized_email_body)) {
                    do_action('aben_after_email_sent_action', $tracking_id, $user->ID);
                }
            }
        } else {
            // error_log('No user has opted for notification');
        }
    }
}

function aben_get_posts_for_email()
{

    global $aben_settings;
    $email_frequency = $aben_settings['email_frequency'];
    $day_of_week     = intval($aben_settings['day_of_week']);

    if ($email_frequency === 'once_in_a_week') {

        $aben_get_posts_result = aben_get_weekly_posts($day_of_week);
    } else {
        $aben_get_posts_result = aben_get_today_posts();
    }
    return ($aben_get_posts_result);
}
