<?php

if (! defined('ABSPATH')) {
    exit;
}

$aben_events = Aben_Events::get_instance();

$template_options  = $aben_events->get_options()['template'];
$header_text       = $template_options['header_text'];
$header_bg         = $template_options['header_bg'];
$body_bg           = $template_options['body_bg'];
$content           = $template_options['content'];
$content_bg        = $template_options['content_bg'];
$button_text       = $template_options['button_text'];
$button_bg         = $template_options['button_bg'];
$button_text_color = $template_options['button_text_color'];
$footer_text       = $template_options['footer_text'];
$footer_bg         = $template_options['footer_bg'];
$site_logo         = $template_options['site_logo'];
$button_url        = $template_options['button_url'];
$show_button       = $template_options['show_button'];
?>

<!------------------------------------- Email Template --------------------------------------->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>

<body>

    <table role="presentation" id="email-container" cellspacing="0" cellpadding="0"
        style="width:100%;max-width:600px;margin:0 auto;padding:20px; background-color:<?php echo esc_attr($body_bg); ?>;">
        <!-- Header Section -->
        <tbody>
            <tr>
                <td id="email-header"
                    style="background-color:<?php echo esc_attr($header_bg) ?>;padding:30px;border-radius:8px 8px 0 0;">
                    <h1 id="header-text-event" style="font-size:28px;margin:0;font-weight:600;">
                        <?php echo esc_html($header_text) ?></h1>
                </td>
            </tr>
            <tr>
                <td id="email-body" style="background-color:<?php echo esc_attr($content_bg) ?>;padding:20px;">
                    <!-- Body Section -->
                    <?php echo wp_kses_post($content);
                    ?>
                    <!-- Button -->
                    <?php if ($show_button): ?>
                        <a id="button-text-event" href="<?php echo esc_url($button_url) ?>" class="cta-button"
                            style="margin-top:20px;padding:12px 25px;width:fit-content;display:block;background-color:<?php echo esc_attr($button_bg) ?>;color:<?php echo esc_attr($button_text_color) ?>;border-radius:5px;text-decoration:none;font-weight:600;text-align:center;"><?php echo esc_html($button_text) ?></a>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- Footer Section -->
            <tr>
                <td id="email-footer"
                    style="background-color:<?php echo esc_attr($footer_bg) ?>;color:#888;text-align:center;padding:15px;font-size:14px;border-radius:0 0 8px 8px;">

                    <a style="display: inline-block;" href="<?php echo esc_url(site_url('/')) ?>">
                        <img src="<?php echo esc_url($site_logo) ?>"
                            style="width:100%;max-height:40px; object-fit:contain; margin-top: 10px;"
                            alt="<?php echo bloginfo('name') ?>"></a>

                    <p id="footer-text-event"><?php echo esc_html($footer_text) ?></p>
                    <!-- Branding -->
                    <?php if (! Aben_Email::is_pro()): ?>
                        <p><?php echo esc_html(ABEN_BRAND_TEXT) ?>
                            <a href="<?php echo esc_url(ABEN_BRAND_LINK) ?>"
                                style="text-decoration:none; display:inline-block;">
                                <img src="<?php echo esc_url(ABEN_PLUGIN_LOGO) ?>" width="60px" alt="Aben"
                                    style="margin-bottom:-4px" />
                            </a>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>


</body>

</html>