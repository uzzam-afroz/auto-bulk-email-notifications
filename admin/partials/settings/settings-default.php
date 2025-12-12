<?php //Default Plugin Settings

if (! defined('ABSPATH')) {

    exit;

}

function aben_options_default()
{
    $site_title   = get_bloginfo('name');
    $current_year = gmdate('Y');
    return [
        'post_type'            => 'post',
        'user_roles'           => 'administrator',
        'email_subject'        => 'New post published',
        'archive_page_slug'    => home_url('blogs'),
        'number_of_posts'      => 5,
        'unsubscribe_link'     => home_url('unsubscribe'),
        'email_frequency'      => 'once_in_a_week',
        'smtp_host'            => '',
        'smtp_port'            => 25,
        'smtp_encryption'      => 'none',
        'smtp_username'        => '',
        'smtp_password'        => '',
        'from_name'            => $site_title,
        'from_email'           => '',
        'body_bg'              => '#eef6fb',
        'header_text'          => 'Hello {{USERNAME}}',
        'header_bg'            => '#ffffff',
        'header_subtext'       => "Check out latest posts from {$site_title}. You can read the posts, share to friends and give your feedback.",
        'footer_text'          => "{$site_title} &copy; {$current_year} | All Rights Reserved",
        'site-logo'            => get_site_icon_url(),
        'show_view_all'        => 1,
        'view_all_posts_text'  => 'View All',
        'show_number_view_all' => 1,
        'show_view_post'       => 1,
        'view_post_text'       => 'Read now',
        'show_unsubscribe'     => 1,
        'day_of_week'          => 6,
        'email_time'           => strtotime('23:00'),
        'pro'                  => false,
    ];

}
