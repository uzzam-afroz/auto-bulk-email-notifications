<?php // User Meta Callbacks

if (!defined('ABSPATH')) {
    exit;
}

// Display User Meta Fields

add_action('edit_user_profile', 'aben_show_user_meta');
add_action('show_user_profile', 'aben_show_user_meta');
add_action('edit_user_profile_update', 'aben_update_user_meta');
add_action('personal_options_update', 'aben_update_user_meta');

function aben_show_user_meta($user)
{
    $aben_notification = get_user_meta($user->ID, 'aben_notification', true);
    ?>

<h2>Auto Bulk Email Notification</h2>
<table class="form-table">
    <tr>
        <th><label for="aben_notification">Enable Email Notification</label></th>
        <td>
            <input type="checkbox" name="aben_notification" id="aben_notification" value="1"
                <?php checked($aben_notification, '1');?> />Check to get new post notifications by email <br />
        </td>
    </tr>
</table>

<?php
}

// Update User Meta Fields

function aben_update_user_meta($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['aben_notification'])) {
        $aben_notification = sanitize_text_field(wp_unslash($_POST['aben_notification']));
    	update_user_meta($user_id, 'aben_notification', $aben_notification);
    } else {
        update_user_meta($user_id, 'aben_notification', '0');
    }
}