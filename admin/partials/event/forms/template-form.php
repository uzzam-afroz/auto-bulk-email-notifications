<?php

if (! defined('ABSPATH')) {
    exit;
}

$aben_events = Aben_Events::get_instance();
$options     = $aben_events->get_options();
?>
<form method="post" action="options.php" style="max-height:unset">
    <?php
    // Security fields for the Options API
    settings_fields('aben_event_options_group');
    ?>

    <table class="form-table" id="aben-event-settings-table">
        <!-- Template Options -->
        <?php
        // Settings for the wp_editor
        $content_editor_settings = [
            'textarea_name'    => 'aben_event_options[template][content]',
            'textarea_rows'    => 10,
            'tinymce'          => [
                'toolbar1'      => 'formatselect,bold,italic,underline,alignleft,aligncenter,alignright,link',
                'block_formats' => 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6;',
            ],
            'quicktags'        => true,
            'drag_drop_upload' => false,
            'media_buttons'    => true,
            'wpautop'          => true,
        ];

        $template_fields = [
            'header_text'       => 'Header Text',
            'content'           => 'Body',
            'show_button'       => 'Show Button',
            'button_text'       => 'Button Text',
            'button_url'        => 'Button URL',
            'site_logo'         => 'Site Logo',
            'footer_text'       => 'Footer Text',
            'header_bg'         => 'Header BG',
            'body_bg'           => 'Email BG',
            'content_bg'        => 'Body BG',
            'button_bg'         => 'Button BG',
            'button_text_color' => 'Button Text Color',
            'footer_bg'         => 'Footer BG',
        ];

        $color_fields_html = ''; // Placeholder for all color fields
        foreach ($template_fields as $field => $label) {
            $value = isset($options['template'][$field]) ? $options['template'][$field] : '';

            if ($field === 'show_button') {
                // Checkbox field for "show_button"
        ?>
                <tr>
                    <th scope="row">
                        <label
                            for="aben_event_options_template_<?php echo esc_attr($field); ?>"><?php echo esc_attr($label); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="aben_event_options[template][<?php echo esc_attr($field); ?>]"
                            id="aben_event_options_template_<?php echo esc_attr($field); ?>" value="1"
                            <?php checked($value, true); ?>>
                        <span>Yes</span>
                    </td>
                </tr>
            <?php
            } elseif (strpos($field, 'bg') !== false || strpos($field, 'color') !== false) {
                ob_start(); // Start output buffering
            ?>
                <tr>
                    <td style="padding: 0;">
                        <input type="color" name="aben_event_options[template][<?php echo esc_attr($field); ?>]"
                            id="aben_event_options_template_<?php echo esc_attr($field); ?>"
                            value="<?php echo esc_attr($value); ?>" class="color-picker"
                            data-default-color="<?php echo esc_attr($value); ?>">
                        <p><?php echo esc_attr($label); ?></p>

                    </td>
                </tr>
            <?php
                $color_fields_html .= ob_get_clean(); // Append the output to the color fields HTML
            } elseif ($field === 'content') {
                // Textarea field for "content"
            ?>
                <tr>
                    <th scope="row">
                        <label
                            for="aben_event_options_template_<?php echo esc_attr($field); ?>"><?php echo esc_attr($label); ?></label>
                    </th>
                    <td>
                        <?php
                        wp_editor(wp_kses_post($value), 'aben_event_options_template_' . $field, $content_editor_settings);
                        ?>
                    </td>
                </tr>
            <?php
            } elseif ($field === 'site_logo') {
                // Media uploader field for "site_logo"
            ?>
                <tr>
                    <th scope="row">
                        <label
                            for="aben_event_options_template_<?php echo esc_attr($field); ?>"><?php echo esc_attr($label); ?></label>
                    </th>
                    <td>
                        <input type="hidden" name="aben_event_options[template][<?php echo esc_attr($field); ?>]"
                            id="aben_event_options_template_<?php echo esc_attr($field); ?>"
                            value="<?php echo esc_attr($value); ?>" class="regular-text">
                        <button type="button" class="button aben-media-upload-button">Select/Upload</button>
                        <img id="aben_event_options_template_<?php echo esc_attr($field); ?>_preview"
                            src="<?php echo esc_attr($value); ?>"
                            style="width:100%;max-height:40px;object-fit:contain;margin-top:10px;<?php echo esc_attr($value ? 'display:block;' : 'display:none'); ?>">
                        <button type="button" class="button aben-media-remove-button"
                            style="<?php echo esc_attr($value ? 'display:block;' : 'display:none'); ?>">Remove</button>
                        <br>
                        <p class="description">Upload or select from media library.</p>
                    </td>
                </tr>
            <?php
            } else {
                // General input field for all other fields
            ?>
                <tr>
                    <th scope="row">
                        <label
                            for="aben_event_options_template_<?php echo esc_attr($field); ?>"><?php echo esc_attr($label); ?></label>
                    </th>
                    <td>
                        <input type="text" name="aben_event_options[template][<?php echo esc_attr($field); ?>]"
                            id="aben_event_options_template_<?php echo esc_attr($field); ?>"
                            value="<?php echo esc_attr($value); ?>">
                    </td>
                </tr>
            <?php
            }
        }

        // Output the collected color fields wrapped in a container
        if (! empty($color_fields_html)) {
            ?>
            <th scope="row">
                <label for="color-fields-table">Color Fields</label>
            </th>
            <tr>
                <td colspan="2" style="padding: 0;">
                    <table id="color-fields-table">
                        <?php $allowed_tags = [
                            'input' => [
                                'type'               => true,
                                'name'               => true,
                                'id'                 => true,
                                'value'              => true,
                                'class'              => true,
                                'data-default-color' => true,
                            ],
                            'p'     => [],
                            'tr'    => [],
                            'td'    => [
                                'style' => true,
                            ],
                        ];

                        echo wp_kses($color_fields_html, $allowed_tags); ?>
                    </table>
                </td>
            </tr>
        <?php
        }
        ?>
        <?php if (! Aben_Email::is_pro()) { ?>
            <tr>
                <th scope="row" colspan="2" title="Remove Powered by Aben from Email Footer"><a id="aben_remove_branding"
                        href="/wp-admin/admin.php?page=aben-license">Remove Branding "Powered by Aben"</a></th>
                <td><label for="aben_options_remove_branding"><a href="https://abenplugin.com" target="_blank"><img
                                style="max-width:150px; margin-top:-2px;" id="aben_branding" src=""></a></label></td>
            </tr> <?php } ?>
    </table>

    <p class="submit">
        <input id="submit" type="submit" class="button button-primary" value="Save Changes">
    </p>
</form>