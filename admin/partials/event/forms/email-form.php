<?php

    if (! defined('ABSPATH')) {
        exit;
    }

    $aben_events = Aben_Events::get_instance();
    $options     = $aben_events->get_options();

    global $wp_roles;
    $roles = array_keys($wp_roles->roles);
?>

<form method="post" action="options.php">
    <?php
        // Security fields for the Options API
        settings_fields('aben_event_options_group');
    ?>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="aben_event_options_role">Send To</label>
            </th>
            <td>
                <select name="aben_event_options[role]" id="aben_event_options_role">
                    <?php
                        // Loop through roles
                        foreach ($roles as $role) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($role),
                                selected($options['role'], $role, false),
                                esc_attr(ucwords($role) . 's')
                            );
                        }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="aben_event_options_email_subject">Email Subject</label>
            </th>
            <td>
                <input type="text" name="aben_event_options[email_subject]" id="aben_event_options_email_subject"
                    value="<?php echo esc_attr($options['email_subject']); ?>">
            </td>
        </tr>
    </table>

    <p class="submit">
        <input id="submit" type="submit" class="button button-primary" value="Save Changes">
    </p>
</form>