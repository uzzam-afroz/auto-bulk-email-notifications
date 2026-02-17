<?php
/**
 * SMTP Email Provider
 *
 * Handles email sending via SMTP using PHPMailer
 *
 * @link       https://rehan.work
 * @since      2.3.0
 * @package    Aben
 * @subpackage Aben/admin/partials/providers
 */

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (!defined('ABSPATH')) {
    exit;
}

class Aben_SMTP_Provider extends Aben_Email_Provider
{
    /**
     * PHPMailer instance
     *
     * @var PHPMailer|null
     */
    private $mailer = null;

    /**
     * Get provider name
     *
     * @return string Provider name
     */
    public function get_name()
    {
        return 'SMTP';
    }

    /**
     * Check if SMTP is configured
     *
     * @return bool True if configured
     */
    public function is_configured()
    {
        return !empty($this->config['smtp_host'])
            && !empty($this->config['smtp_port'])
            && !empty($this->config['smtp_username']);
    }

    /**
     * Get configured PHPMailer instance
     *
     * @return PHPMailer|null Configured PHPMailer instance or null on failure
     */
    private function get_mailer()
    {
        if ($this->mailer instanceof PHPMailer) {
            return $this->mailer;
        }

        if (!$this->is_configured()) {
            return null;
        }

        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

        $password = aben_decrypt_password($this->config['smtp_password']);

        try {
            $this->mailer = new PHPMailer(true);

            $this->mailer->isSMTP();
            $this->mailer->Host       = $this->config['smtp_host'];
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $this->config['smtp_username'];
            $this->mailer->Password   = $password;
            $this->mailer->SMTPSecure = $this->config['smtp_encryption'];
            $this->mailer->Port       = $this->config['smtp_port'];

            $this->mailer->CharSet        = 'UTF-8';
            $this->mailer->Timeout        = 15;
            $this->mailer->SMTPKeepAlive  = true;

            $from_name = $this->get_config('from_name', get_bloginfo('name'));
            $this->mailer->setFrom($this->config['smtp_username'], $from_name);

            if (!empty($this->config['from_email'])) {
                $this->mailer->addReplyTo($this->config['from_email'], $from_name);
            }

            return $this->mailer;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Send email via SMTP
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param array $headers Optional headers
     * @return bool True on success, false on failure
     */
    public function send($to, $subject, $message, $headers = [])
    {
        $mail = $this->get_mailer();

        if (!$mail instanceof PHPMailer) {
            $this->logger->log_email(
                $to,
                $subject,
                $message,
                'failed',
                'SMTP mailer initialization failed'
            );
            return false;
        }

        try {
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = wp_strip_all_tags($message);

            $sent = $mail->send();

            if ($sent) {
                $this->logger->log_email($to, $subject, $message, 'sent');
            } else {
                $this->logger->log_email($to, $subject, $message, 'failed', $mail->ErrorInfo);
            }

            $mail->clearAddresses();
            return $sent;
        } catch (Exception $e) {
            $this->logger->log_email($to, $subject, $message, 'failed', $e->getMessage());
            $mail->clearAddresses();
            return false;
        }
    }

    /**
     * Test SMTP connection
     *
     * @return bool True if connection successful
     */
    public function test_connection()
    {
        $mailer = $this->get_mailer();

        if (!$mailer instanceof PHPMailer) {
            return false;
        }

        try {
            $mailer->smtpConnect();
            $mailer->smtpClose();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Close SMTP connection
     *
     * @return void
     */
    public function close()
    {
        if ($this->mailer instanceof PHPMailer) {
            try {
                $this->mailer->smtpClose();
            } catch (Exception $e) {
            }
        }
    }
}
