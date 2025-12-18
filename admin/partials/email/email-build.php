<?php // Build Email

if (!defined('ABSPATH')) {

    exit;
}

function aben_get_options() //Fetching Plugin Settings and Returning in Array
{

    $options = get_option('aben_options', 'aben_options_default');
    $setting = array();

    if (is_array($options)) {
        foreach ($options as $key => $value) {
            $setting[$key] = $value;
        }
    }
    return $setting;
}

// Function to get users email with user meta "aben_notification" set to true
function aben_get_users_email()
{
    static $cached_emails = null;

    if (null !== $cached_emails) {
        return $cached_emails;
    }

    $settings  = aben_get_options();
    $user_role = $settings['user_roles'];

    $user_ids = get_users([
        'role'       => $user_role,
        'meta_query' => [
            [
                'key'     => 'aben_notification',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
        'fields'     => 'ID',
    ]);

    if (empty($user_ids)) {
        return $cached_emails = [];
    }

    $emails = [];

    foreach ($user_ids as $user_id) {
        $user = get_user_by('ID', $user_id);

        if ($user && is_email($user->user_email)) {
            $emails[] = $user->user_email;
        }
    }

    return $cached_emails = $emails;
}

function aben_get_today_posts()
{
    $get_settings = aben_get_options();
    $post_type = $get_settings['post_type'];

    $today = getdate(); // Get Today's Date

    $year = $today['year'];
    $month = $today['mon'];
    $day = $today['mday'];

    $args = array(
        'numberposts' => -1,
        'date_query' => array(
            array(
                'year' => $year,
                'month' => $month,
                'day' => $day,
            ),
        ),
        'post_type' => $post_type,
    );

    $posts = get_posts($args);

    $posts_published_today = count($posts);

    $posts_to_email = array();

    if (!empty($posts_published_today)) {

        foreach ($posts as $post) {

            $id = $post->ID;

            $title = $post->post_title;

            $content = $post->post_content;

            $content_excerpt = wp_trim_words(wp_strip_all_tags($content), 15, '...');

            $excerpt = empty($post->post_excerpt) ? $content_excerpt : wp_trim_words($post->post_excerpt, 15, '...');
            $featured_image_url = get_the_post_thumbnail_url($id);

            $link = get_permalink($id);

            $author_id = $post->post_author;

            $author = get_the_author_meta('display_name', $author_id);

            $taxonomies = aben_get_post_tax($id, 'country'); //Default taxonomy is 'category'

            $posts_to_email[] = array(
                'id' => $id,
                'title' => $title,
                'link' => $link,
                'excerpt' => $excerpt,
                'featured_image_url' => $featured_image_url,
                'author' => $author,
                'category' => $taxonomies,
            );
        }
        return array(

            'posts_to_email' => $posts_to_email,
            'posts_published' => $posts_published_today,
        );
    }
}

function aben_get_weekly_posts($selected_day_num)
{
    // Ensure the selected day number is between 0 and 6
    if ($selected_day_num < 0 || $selected_day_num > 6) {
        return 'Invalid day number. Please provide a number between 0 (Sunday) and 6 (Saturday).';
    }

    // Get today's date
    $today = getdate();
    $current_day_of_week = $today['wday']; // 0 (for Sunday) through 6 (for Saturday)

    // Calculate the difference to get back to the last occurrence of the selected day
    $days_since_selected = ($current_day_of_week >= $selected_day_num) ?
        $current_day_of_week - $selected_day_num :
        7 - ($selected_day_num - $current_day_of_week);

    // Find the date of the last occurrence of the selected day (start of the week)
    $start_of_week = strtotime("-$days_since_selected days");

    // End of the week would be 6 days after the start of the week
    $end_of_week = strtotime("+6 days", $start_of_week);

    // Format the dates for the query
    $start_date = gmdate('Y-m-d', $start_of_week); // Start date (e.g., last Saturday)
    $end_date = gmdate('Y-m-d', $end_of_week); // End date (e.g., following Friday)

    $get_settings = aben_get_options();
    $post_type = $get_settings['post_type'];
    // Query posts within the week range
    $args = array(
        'numberposts' => -1,
        'date_query' => array(
            array(
                'after' => $start_date,
                'before' => $end_date,
                'inclusive' => true, // Include start and end dates
            ),
        ),
        'post_type' => $post_type, // Add the post type here
    );

    $posts = get_posts($args);
    $posts_published_this_week = count($posts);

    $posts_to_email = array();

    if (!empty($posts_published_this_week)) {
        foreach ($posts as $post) {

            $id = $post->ID;

            $title = $post->post_title;

            $content = $post->post_content;

            $content_excerpt = wp_trim_words(wp_strip_all_tags($content), 15, '...');

            $excerpt = empty($post->post_excerpt) ? $content_excerpt : wp_trim_words($post->post_excerpt, 15, '...');

            $featured_image_url = get_the_post_thumbnail_url($id);

            $link = get_permalink($id);

            $author_id = $post->post_author;

            $taxonomies = aben_get_post_tax($id, 'country');

            $posts_to_email[] = array(
                'id' => $id,
                'title' => $title,
                'link' => $link,
                'excerpt' => $excerpt,
                'featured_image_url' => $featured_image_url,
                'author' => $author,
                'category' => $taxonomies,

            );
        }

        return array(
            'posts_to_email' => $posts_to_email,
            'posts_published' => $posts_published_this_week,
        );
    }

    return null; // No posts found for the week
}

function aben_get_post_tax($post_id, $custom_tax)
{

    $tax = taxonomy_exists($custom_tax) ? $custom_tax : 'category';

    $taxonomy_objects = get_the_terms($post_id, $tax);

    $taxonomies = [];

    if (!empty($taxonomy_objects)) {
        foreach ($taxonomy_objects as $index => $taxonomy) {
            $taxonomies[$index] = $taxonomy->name;
        }
    }
    return $taxonomies;
}

function aben_get_test_posts()
{
    $postsArr = get_posts($args = [
        'numberposts' => 10,
        'post_type' => aben_get_options()['post_type'],
    ]);

    $posts = [];

    foreach ($postsArr as $post) {
        $post_id = $post->ID;
        $post_title = $post->post_title;
        $post_link = get_permalink($post_id);
        $post_image = get_the_post_thumbnail_url($post_id);
        $post_author = $post->post_author;
        $post_categories = aben_get_post_tax($post_id, 'category');
        $post_content = $post->post_content;
        $post_content_excerpt = wp_trim_words(wp_strip_all_tags($post_content), 15, '...');
        $post_excerpt = empty($post->post_excerpt) ? $post_content_excerpt : wp_trim_words($post->post_excerpt, 15, '...');

        $posts[] = [
            'id' => $post_id,
            'title' => $post_title,
            'link' => $post_link,
            'excerpt' => $post_excerpt,
            'featured_image_url' => empty($post_image) ? ABEN_FEATURED_IMAGE : $post_image, // ABEN_FEATURED_IMAGE is a constant
            'author' => $post_author,
            'category' => $post_categories,
        ];
    }
    return $posts;
}
