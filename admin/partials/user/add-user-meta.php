<?php // Add user meta to subscribe to Aben Notifications

if (!defined('ABSPATH')) {

    exit;
}

//Fetch Users

function aben_get_users()
{

    $users = get_users(); // All Users

    return $users;
}

// Add user meta on new user registration
add_action('user_register', 'aben_add_user_meta', 10, 1);

function aben_add_user_meta($user_id)
{
    add_user_meta($user_id, 'aben_notification', '1');
    add_user_meta($user_id, 'aben_unsubscribe_date', null);
}

// Adds user meta for existing users
function aben_add_user_meta_to_existing_users()
{
    $users = aben_get_users();

    foreach ($users as $user) {

        $user_id = $user->ID;

        if (!metadata_exists('user', $user_id, 'aben_notification')) {

            add_user_meta($user_id, 'aben_notification', '1');
        }
        if (!metadata_exists('user', $user_id, 'aben_unsubscribe_date')) {

            add_user_meta($user_id, 'aben_unsubscribe_date', null);
        }
    }
}

// Handle the "Subscribe Again" button click for unsubscribed users
function aben_subscribe_user_action()
{
    // Check if the action and user ID are set
    if (isset($_GET['action']) && $_GET['action'] === 'aben_subscribe_user' && isset($_GET['user_id'])) {
        $user_id = intval(sanitize_text_field(wp_unslash($_GET['user_id'])));

        // Verify if the user exists
        if ($user_id && get_userdata($user_id)) {
            // Update the user's 'aben_notification' meta back to '1' (subscribed)
            update_user_meta($user_id, 'aben_notification', '1');

            // show notice
            wp_redirect(add_query_arg('subscribed', 'true', wp_get_referer()));
            exit;
        }
    }
}
add_action('admin_init', 'aben_subscribe_user_action');

// Add a notice after subscribing the user
function aben_admin_notice()
{
    if (isset($_GET['subscribed']) && $_GET['subscribed'] == 'true') {
        echo '<div class="updated notice is-dismissible"><p>User successfully resubscribed.</p></div>';
    }
    if (isset($_GET['unsubscribed']) && $_GET['unsubscribed'] == 'true') {
        echo '<div class="updated notice is-dismissible"><p>User successfully added to unsubscribed list.</p></div>';
    }
}
add_action('admin_notices', 'aben_admin_notice');

// Function to handle manually adding an email to the unsubscribed list
function aben_handle_manual_unsubscribe()
{
    if (isset($_POST['aben_add_unsubscribed'])) {
        if (isset($_POST['aben_unsubscribe_email'])) {
            $email = sanitize_email(wp_unslash($_POST['aben_unsubscribe_email']));

            if (is_email($email)) {
                $user = get_user_by('email', $email);

                if ($user) {

                    update_user_meta($user->ID, 'aben_notification', '0');
                    wp_redirect(add_query_arg('unsubscribed', 'true', wp_get_referer()));
                    exit;
                } else {
                    echo '<div class="error notice"><p>No user found with that email address.</p></div>';
                }
            } else {
                echo '<div class="error notice"><p>Invalid email format. Please try again.</p></div>';
            }
        } else {
            echo '<div class="error notice"><p>Email address is required.</p></div>';
        }
    }
}
add_action('admin_init', 'aben_handle_manual_unsubscribe');
