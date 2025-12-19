<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (! defined('ABSPATH')) {
    exit;
}

function aben_get_phpmailer_instance()
{
    static $phpmailer = null;

    if ($phpmailer instanceof PHPMailer) {
        return $phpmailer;
    }

    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

    $phpmailer = new PHPMailer(true);

    return $phpmailer;
}

function aben_check_smtp_connection()
{
    $aben_smtp     = aben_get_smtp_settings();
    $password      = $aben_smtp['smtp_password'];
    $smtp_password = aben_decrypt_password($password);

    $mailer = aben_get_configured_smtp_mailer();
    $mailer->isSMTP();
    $mailer->Host       = $aben_smtp['smtp_host'];
    $mailer->SMTPAuth   = true;
    $mailer->Username   = $aben_smtp['smtp_username'];
    $mailer->Password   = $smtp_password;
    $mailer->SMTPSecure = $aben_smtp['smtp_encryption'];
    $mailer->Port       = $aben_smtp['smtp_port'];

    try {
        $mailer->smtpConnect();
        echo '<div id="aben-notice" class="notice notice-success is-dismissible"><p>SMTP connection successfull.</p></div>';
    } catch (Exception $e) {
        echo '<div id="aben-notice--error" class="notice is-dismissible"><p>SMTP connection failed</p></div>';
    }
}

function aben_display_smtp_connection_btn()
{
    if (isset($_POST['submit'])) {
        aben_check_smtp_connection();
    }

?>
    <form method="POST" id="aben_smtp_connection_btn">
        <input type="submit" name="submit" class="button button-primary" value="Test SMTP">
    </form>
<?php
}

function aben_get_smtp_settings()
{
    $settings = aben_get_options();

    return [
        'smtp_host'       => isset($settings['smtp_host']) ? $settings['smtp_host'] : '',
        'smtp_port'       => isset($settings['smtp_port']) ? $settings['smtp_port'] : '',
        'smtp_encryption' => isset($settings['smtp_encryption']) ? $settings['smtp_encryption'] : '',
        'smtp_username'   => isset($settings['smtp_username']) ? $settings['smtp_username'] : '',
        'smtp_password'   => isset($settings['smtp_password']) ? $settings['smtp_password'] : '',
        'from_name'       => isset($settings['from_name']) ? $settings['from_name'] : '',
        'from_email'      => isset($settings['from_email']) ? $settings['from_email'] : '',
    ];
}

/**
 * Get configured SMTP mailer (singleton per request / batch)
 */
function aben_get_configured_smtp_mailer()
{
    static $mailer = null;

    if ($mailer instanceof PHPMailer) {
        return $mailer;
    }

    $smtp     = aben_get_smtp_settings();
    $password = aben_decrypt_password($smtp['smtp_password']);

    $mailer = aben_get_phpmailer_instance();

    $mailer->isSMTP();
    $mailer->Host       = $smtp['smtp_host'];
    $mailer->SMTPAuth   = true;
    $mailer->Username   = $smtp['smtp_username'];
    $mailer->Password   = $password;
    $mailer->SMTPSecure = $smtp['smtp_encryption'];
    $mailer->Port       = $smtp['smtp_port'];

    $mailer->CharSet        = 'UTF-8';
    $mailer->Timeout        = 15;
    $mailer->SMTPKeepAlive  = true;

    $mailer->setFrom($smtp['smtp_username'], $smtp['from_name']);

    if (! empty($smtp['from_email'])) {
        $mailer->addReplyTo($smtp['from_email'], $smtp['from_email']);
    }

    return $mailer;
}

/**
 * Send email using reused SMTP connection (bulk-safe)
 */
function aben_send_smtp_email($to, $subject, $message, Aben_Email_Logs $logger = null)
{
    $mail   = aben_get_configured_smtp_mailer();
    $logger = $logger ?: new Aben_Email_Logs();

    try {
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = wp_strip_all_tags($message);
        $sent = $mail->send();
        $logger->log_email($to, $subject, $message, $sent ? 'sent' : 'failed');
        $mail->clearAddresses();
        return $sent;
    } catch (Exception $e) {
        $logger->log_email($to, $subject, $message, 'failed');
        return false;
    }
}

/**
 * Close SMTP connection after batch processing
 */
function aben_close_smtp_mailer()
{
    $mailer = aben_get_configured_smtp_mailer();
    if ($mailer instanceof PHPMailer) {
        $mailer->smtpClose();
    }
}

add_action('admin_post_aben_send_test_email', 'aben_handle_test_email');

function aben_handle_test_email()
{
    // Check if the user has permissions
    if (! current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Verify nonce for security
    if (
        ! isset($_POST['aben_test_email_nonce']) ||
        ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['aben_test_email_nonce'])), 'aben_send_test_email')
    ) {
        wp_die('Security check failed.');
    }

    // Get the email address from the form submission, ensuring it's unslashed before sanitization
    $to = isset($_POST['test_email_address']) ? sanitize_email(wp_unslash($_POST['test_email_address'])) : '';

    // Validate email address
    if (empty($to) || ! is_email($to)) {
        wp_die('Invalid email address provided.');
    }

    $aben_settings  = aben_get_options();
    $featured_image = ABEN_FEATURED_IMAGE;

    $email_obj = new Aben_Email(
        $aben_settings['archive_page_slug'],
        $aben_settings['number_of_posts'],
        $aben_settings['body_bg'],
        $aben_settings['header_text'],
        $aben_settings['header_bg'],
        $aben_settings['header_subtext'],
        $aben_settings['footer_text'],
        $aben_settings['site-logo'],
        $aben_settings['show_view_all'],
        $aben_settings['view_all_posts_text'],
        $aben_settings['show_view_post'],
        $aben_settings['view_post_text'],
        $aben_settings['show_unsubscribe'],
        aben_get_test_posts(),
    );

    ob_start();
    $email_obj->aben_email_template();
    $message = ob_get_clean();

    // Get and format the current user's first name
    $current_user = ucfirst(wp_get_current_user()->display_name);
    $current_user = explode(' ', $current_user)[0];

    // Replace placeholders in the email message
    $message = str_replace('{{USERNAME}}', $current_user, $message);

    // Define the email subject
    $subject = 'Test Email';

    // Send the test email
    if (aben_send_smtp_email($to, $subject, $message)) {
        aben_close_smtp_mailer();
        wp_redirect(add_query_arg('test_email_sent', 'success', wp_get_referer()));
    } else {
        wp_redirect(add_query_arg('test_email_sent', 'failure', wp_get_referer()));
    }
    exit;
}
