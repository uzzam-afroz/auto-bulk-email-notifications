<?php

/**
 * Runs this code when user unsubscribe
 * for email notifications.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle user unsubscribe action via URL query parameter.
 *
 * URL example: https://example.com/?aben-unsubscribe=user@example.com
 *
 * Updates:
 * - aben_notification = 0
 * - aben_unsubscribe_date = current timestamp
 *
 * @return void
 */
function aben_unsubscribe_user()
{

    if (! isset($_GET['aben-unsubscribe'])) {
        return;
    }

    $user_email = sanitize_email(wp_unslash($_GET['aben-unsubscribe']));

    echo '<div class="unsubscribe-message">';

    if (! is_email($user_email)) {
        echo '<p>' . esc_html__('Invalid email address format.', 'aben') . '</p></div>';
        return;
    }

    $user = get_user_by('email', $user_email);

    if (! $user) {
        echo '<p>' . esc_html__('No user found with that email address.', 'aben') . '</p></div>';
        return;
    }

    $aben_notification = get_user_meta($user->ID, 'aben_notification', true);

    if ('1' !== (string) $aben_notification) {
        echo '<p>' . esc_html__('You have already unsubscribed.', 'aben') . '</p></div>';
        return;
    }

    // Prepare unsubscribe date (WP-localized)
    $unsubscribe_date = current_time('mysql');

    $updated_notification = update_user_meta($user->ID, 'aben_notification', '0');
    $updated_date        = update_user_meta($user->ID, 'aben_unsubscribe_date', $unsubscribe_date);

    if ($updated_notification && $updated_date) {
        echo '<p>' . esc_html__('You have successfully unsubscribed!', 'aben') . '</p>';
    } else {
        echo '<p>' . esc_html__('There was an issue updating your subscription. Please try again later.', 'aben') . '</p>';
    }

    echo '</div>';
}
add_action('wp', 'aben_unsubscribe_user');
