<?php //Setting Callbacks

if (!defined('ABSPATH')) {

    exit;

}

// Section Callbacks

function aben_callback_section_general_setting()
{
}

function aben_callback_section_smtp_setting()
{
}

function aben_callback_section_email_setting()
{

}

function aben_callback_section_email_template()
{

}


//Fields Callbacks

function aben_callback_field_text($args)
{

    $options = get_option('aben_options', aben_options_default());

    $id = isset($args['id']) ? $args['id'] : '';
    $label = isset($args['label']) ? $args['label'] : '';

    $value = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';

    echo '<input id="aben_options_' . esc_attr($id) . '"
                name="aben_options[' . esc_attr($id) . ']"
                type="text"
                size="40"
                value="' . esc_html($value) . '"><br />';

    echo '<label for="aben_options_' . esc_attr($id) . '">' . esc_html($label) . '</label>';
}

function aben_callback_field_textarea($args)
{

    $options = get_option('aben_options', aben_options_default());

    $id = isset($args['id']) ? $args['id'] : '';
    $label = isset($args['label']) ? $args['label'] : '';

    // Get the allowed tags for this textarea
    $allowed_tags = wp_kses_allowed_html('post');

    // Get the value for this textarea
    $value = isset($options[$id]) ? wp_kses(stripslashes_deep($options[$id]), $allowed_tags) : '';

    echo '<textarea id="aben_options_' . esc_attr($id) . '"
    name="aben_options[' . esc_attr($id) . ']"
    rows="10"
    cols="100">' . esc_html($value) . '</textarea><br />';
    echo '<label for="aben_options_' . esc_attr($id) . '">' . esc_html($label) . '</label>';

}

function aben_callback_field_select($args)
{

    $options = get_option('aben_options', aben_options_default());

    $id = isset($args['id']) ? $args['id'] : '';
    $label = isset($args['label']) ? $args['label'] : '';

    $selected_option = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';

    $select_options = [];

    if ($id === 'post_type') {

        $post_types = get_post_types(array('public' => true), 'names');

        $select_options = $post_types;

    } else if ($id === 'user_roles') {

        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        $roles = $wp_roles->roles;

        $role_names = array_keys($roles);

        foreach ($role_names as $role_name) {

            $select_options[$role_name] = ucwords($role_name);

        }

    } else if ($id === 'smtp_encryption') {

        $select_options = array(
            'none' => 'None',
            'tls' => 'TLS',
            'ssl' => 'SSL',
        );

    } else if ($id === 'number_of_posts') {

        $select_options = array(
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5',
            6 => '6',
            7 => '7',
            8 => '8',
            9 => '9',
            10 => '10',

        );

    } else if ($id === 'day_of_week') {

        $select_options = array(
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        );

    } else if ($id === 'email_frequency') {

        $select_options = array(
            'once_in_a_day' => 'Once in a Day',
            'once_in_a_week' => 'Once in a Week',
        );

    }

    echo '<select id="aben_options_' . esc_attr($id) . '"
                  name="aben_options[' . esc_attr($id) . ']">';

    foreach ($select_options as $value => $option) {

        $selected = selected($selected_option, $value, false);

        echo '<option value="' . esc_html($value) . '" ' . esc_attr($selected) . '>' . esc_html(ucwords($option)) . '</option>';
    }

    echo '</select> <br /><label for="aben_options_' . esc_attr($id) . '">' . esc_html($label) . '</label>';

}

function aben_callback_field_checkbox($args)
{
    // Retrieve the current options from the database, or use default values
    $options = get_option('aben_options', array());

    // Get the ID and label for the field from the arguments
    $id = isset($args['id']) ? $args['id'] : '';
    $label = isset($args['label']) ? $args['label'] : '';

    // Check if the checkbox should be checked
    $checked = isset($options[$id]) && $options[$id] == 1 ? 'checked' : '';

    echo '<input type="hidden" name="aben_options[' . esc_attr($id) . ']" value="0">';

    // Render the checkbox input field
    echo '<input id="aben_options_' . esc_attr($id) . '"
                name="aben_options[' . esc_attr($id) . ']"
                type="checkbox"
                value="1"
                ' . esc_attr($checked) . '>';

    // Render the label for the checkbox
    echo '<label for="aben_options_' . esc_attr($id) . '">' . esc_html($label) . '</label>';
}

function aben_callback_field_password($args)
{
    // Retrieve the current options from the database, or use default values
    $options = get_option('aben_options', array());

    // Get the ID and label for the field from the arguments
    $id = isset($args['id']) ? $args['id'] : '';
    $label = isset($args['label']) ? $args['label'] : '';

    // Retrieve the current value for the field, if set
    $value = aben_decrypt_password($options[$id]);

    // Render the password input field
    echo '<input id="aben_options_' . esc_attr($id) . '"
              name="aben_options[' . esc_attr($id) . ']"
              type="password"
              size="40"
              value="' . esc_html($value) . '"><br />';

    // Render the label for the password field
    echo '<label for="aben_options_' . esc_attr($id) . '">' . esc_html($label) . '</label>';
}

function aben_callback_field_color($args)
{
    // Retrieve the current options from the database, or use default values
    $options = get_option('aben_options', array());

    // Get the ID and label for the field from the arguments
    $id = isset($args['id']) ? $args['id'] : '';
    $label = isset($args['label']) ? $args['label'] : '';

    // Retrieve the current value for the field, if set
    $value = isset($options[$id]) ? esc_attr($options[$id]) : '#000000'; // Default to black if no color is set

    // Render the color input field
    echo '<input id="aben_options_' . esc_attr($id) . '"
              name="aben_options[' . esc_attr($id) . ']"
              type="color"
              value="' . esc_html($value) . '"><br />';

    // Render the label for the color field
    echo '<label for="aben_options_' . esc_attr($id) . '">' . esc_html($label) . '</label>';
}

function aben_callback_field_media($args)
{
    $options = get_option('aben_options', aben_options_default());

    $id = isset($args['id']) ? $args['id'] : '';
    $label = isset($args['label']) ? $args['label'] : '';

    $value = isset($options[$id]) ? esc_url($options[$id]) : '';

    echo '<input type="hidden" id="aben_options_' . esc_attr($id) . '"
                name="aben_options[' . esc_attr($id) . ']"
                value="' . esc_attr($value) . '">';
    echo '<button type="button" class="button aben-media-upload-button">Upload Image</button>';
    echo '<img id="aben_' . esc_attr($id) . '_preview" src="' . esc_url($value) . '" style="max-width:100px;margin-top:10px;' . ($value ? 'display:block;' : 'display:none;') . '">';
    echo '<button type="button" class="button aben-media-remove-button" style="' . ($value ? 'display:block;' : 'display:none;') . '">Remove Image</button>';
    echo '<br><label for="aben_options_' . esc_attr($id) . '">' . esc_html($label) . '</label>';
}

function aben_callback_field_time($args)
{
    $options = get_option('aben_options', aben_options_default());

    // Get the field id and label
    $id = isset($args['id']) ? esc_attr($args['id']) : '';
    $label = isset($args['label']) ? esc_html($args['label']) : '';

    // Get the saved timestamp (defaults to 23:00 if not set)
    $timestamp = isset($options[$id]) ? intval($options[$id]) : strtotime('23:00');

    // Create a DateTime object for the saved timestamp in the site's timezone
    $timezone = wp_timezone_string(); // Get the site's timezone
    $dateTime = new DateTime();
    $dateTime->setTimestamp($timestamp);
    $dateTime->setTimezone(new DateTimeZone($timezone)); // Set to site's timezone

    // Convert the timestamp to local time for display (H:i format)
    $local_time = $dateTime->format('H:i');

    // Output the time input field with the local time value
    echo '<input id="aben_options_' . esc_attr($id) . '"
                name="aben_options[' . esc_attr($id) . ']"
                type="time"
                value="' . esc_attr($local_time) . '">';

    // Output the label
    echo '<br><label for="aben_options_' . esc_attr($id) . '">' . esc_html($label) . '</label>';
}

function aben_callback_remove_branding($args) {
    echo '<label for="aben_options_'. esc_attr($args['id']) .'"><a href="'. esc_url(ABEN_BRAND_LINK) .'" target="_blank"><img style="max-width:150px; margin-top:-2px;"id="aben_branding" src="'. esc_attr($args['label']) .'"/></label>';
}