<?php // Validation Callbacks

if (!defined("ABSPATH")) {
    exit();
}

function aben_callback_validate_options($input)
{
    // Retrieve existing options from the database
    $options = aben_get_options();

    // Loop through each key in the input and update values, including empty ones where appropriate
    foreach ($input as $key => $value) {
        // Handle validation based on field type
        switch ($key) {
            case "body_bg":
            case "header_bg":
                // Allow empty value to clear the color
                $options[$key] = empty($value) ? "" : sanitize_hex_color($value);
                break;

            case "header_text":
            case "header_subtext":
            case "footer_text":
            case "view_all_posts_text":
            case "view_post_text":
            case "post_type":
            case "user_roles":
            case "email_frequency":
            case "email_subject":
            case "smtp_host":
            case "smtp_encryption":
            case "smtp_username":
            case "from_name":
            case "day_of_week":
                // Allow empty value to clear text fields
                $options[$key] = sanitize_text_field($value);
                break;

            case "email_provider":
                // Validate provider selection
                $options[$key] = in_array($value, ["smtp", "tosend"]) ? $value : "smtp";
                break;

            case "email_time":
                if (!empty($value)) {
                    $time = sanitize_text_field($value); // e.g., "22:00"

                    // Get the current date and site timezone
                    $timezone = wp_timezone_string();
                    $date = new DateTime("now", new DateTimeZone($timezone)); // Current date in site timezone

                    // Set the time based on user input
                    [$hour, $minute] = explode(":", $time);
                    $date->setTime((int) $hour, (int) $minute); // Set the user-defined time

                    // Convert to UNIX timestamp and save
                    $timestamp = $date->getTimestamp();
                    $options[$key] = $timestamp;
                }
                break;

            case "site_logo":
                // Allow empty value to remove the logo
                $options[$key] = sanitize_url($value);
                break;

            case "archive_page_slug":
            case "unsubscribe_link":
                // Allow empty value to remove the URL
                $options[$key] = esc_url_raw($value);
                break;

            case "email_body":
                $options[$key] = wp_kses_post($value);
                break;

            case "smtp_port":
            case "number_of_posts":
                $options[$key] = intval($value);
                break;

            case "smtp_password":
                if (!empty($value)) {
                    $options[$key] = aben_encrypt_password($value);
                } else {
                    // If no password was provided, keep the existing one
                    $options[$key] = isset($options["smtp_password"]) ? $options["smtp_password"] : "";
                }
                break;

            case "tosend_api_key":
                if (!empty($value)) {
                    // Encrypt ToSend API key same as SMTP password
                    $options[$key] = aben_encrypt_password($value);
                } else {
                    // Keep existing key if not provided
                    $options[$key] = isset($options["tosend_api_key"]) ? $options["tosend_api_key"] : "";
                }
                break;

            case "from_email":
            case "tosend_from_email":
                $options[$key] = sanitize_email($value);
                break;

            case "show_view_all":
            case "show_unsubscribe":
            case "show_number_view_all":
            case "show_view_post":
                $options[$key] = !empty($value) ? 1 : 0;
                break;
        }
    }

    // Preserve or update PRO flag
    if (isset($input["pro"])) {
        $options["pro"] = !empty($input["pro"]) ? 1 : 0;
    } else {
        if (!isset($options["pro"])) {
            $options["pro"] = 0; // default
        }
    }

    return $options;
}

function aben_generate_encryption_key($length = 32)
{
    return bin2hex(random_bytes($length)); // Generates a random key
}

function aben_encrypt_password($password)
{
    $encryption_key = aben_get_options()["aben_key"];

    if (!$encryption_key) {
    } else {
        $iv_length = openssl_cipher_iv_length("aes-256-cbc");
        $iv = openssl_random_pseudo_bytes($iv_length);

        // Encrypt the password using AES-256-CBC
        $encrypted_password = openssl_encrypt($password, "aes-256-cbc", $encryption_key, 0, $iv);

        // Combine the encrypted password with the IV for storage
        return base64_encode($iv . $encrypted_password);
    }
}

function aben_decrypt_password($encrypted_password)
{
    $encryption_key = aben_get_options()["aben_key"];

    if (!$encryption_key) {
    } else {
        $iv_length = openssl_cipher_iv_length("aes-256-cbc");
        $decoded = base64_decode($encrypted_password);

        // Extract the IV and the encrypted password from the decoded string
        $iv = substr($decoded, 0, $iv_length);
        $encrypted_password = substr($decoded, $iv_length);

        // Ensure IV is exactly 16 bytes (aes-256-cbc expects 16 bytes for IV)
        if (strlen($iv) < $iv_length) {
            $iv = str_pad($iv, $iv_length, "\0"); // Padding with null bytes
        }

        // Decrypt the password using the same key and IV
        return openssl_decrypt($encrypted_password, "aes-256-cbc", $encryption_key, 0, $iv);
    }
}

function aben_callback_validate_event_options($input)
{
    $aben_events = Aben_Events::get_instance();
    $existing_options = is_array($aben_events->options) ? $aben_events->options : [];
    // Initialize the sanitized options with the existing ones.
    $sanitized = array_merge($existing_options, $input);

    // Sanitize 'role' field
    if (isset($input["role"])) {
        $sanitized["role"] = sanitize_text_field($input["role"]);
    }

    // Sanitize 'email_subject' field
    if (isset($input["email_subject"])) {
        $sanitized["email_subject"] = sanitize_text_field($input["email_subject"]);
    }

    // Sanitize 'template' fields if set
    if (isset($input["template"]) && is_array($input["template"])) {
        $sanitized["template"] = [];

        // Sanitize each field within the 'template' array
        $sanitized["template"]["body_bg"] = isset($input["template"]["body_bg"]) ? sanitize_hex_color($input["template"]["body_bg"]) : "";
        $sanitized["template"]["header_text"] = isset($input["template"]["header_text"])
            ? sanitize_text_field($input["template"]["header_text"])
            : "";
        $sanitized["template"]["header_bg"] = isset($input["template"]["header_bg"]) ? sanitize_hex_color($input["template"]["header_bg"]) : "";
        $sanitized["template"]["content"] = isset($input["template"]["content"]) ? wp_kses_post($input["template"]["content"]) : "";
        $sanitized["template"]["content_bg"] = isset($input["template"]["content_bg"]) ? sanitize_hex_color($input["template"]["content_bg"]) : "";
        $sanitized["template"]["button_text"] = isset($input["template"]["button_text"])
            ? sanitize_text_field($input["template"]["button_text"])
            : "";
        $sanitized["template"]["button_text_color"] = isset($input["template"]["button_text_color"])
            ? sanitize_hex_color($input["template"]["button_text_color"])
            : "";
        $sanitized["template"]["button_bg"] = isset($input["template"]["button_bg"]) ? sanitize_hex_color($input["template"]["button_bg"]) : "";
        $sanitized["template"]["button_url"] = isset($input["template"]["button_url"]) ? esc_url_raw($input["template"]["button_url"]) : "";
        $sanitized["template"]["show_button"] = isset($input["template"]["show_button"])
            ? filter_var($input["template"]["show_button"], FILTER_VALIDATE_BOOLEAN)
            : false;
        $sanitized["template"]["site_logo"] = isset($input["template"]["site_logo"]) ? esc_url_raw($input["template"]["site_logo"]) : "";
        $sanitized["template"]["footer_text"] = isset($input["template"]["footer_text"])
            ? sanitize_text_field($input["template"]["footer_text"])
            : "";
        $sanitized["template"]["footer_bg"] = isset($input["template"]["footer_bg"]) ? sanitize_hex_color($input["template"]["footer_bg"]) : "";
    }

    return $sanitized;
}
