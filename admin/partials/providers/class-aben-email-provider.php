<?php
/**
 * Abstract Email Provider
 *
 * Base class for all email providers (SMTP, ToSend, etc.)
 *
 * @link       https://rehan.work
 * @since      2.3.0
 * @package    Aben
 * @subpackage Aben/admin/partials/providers
 */

if (!defined("ABSPATH")) {
    exit();
}

abstract class Aben_Email_Provider
{
    /**
     * Provider configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     * Email logger instance
     *
     * @var Aben_Email_Logs
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param array $config Provider configuration
     * @param Aben_Email_Logs|null $logger Logger instance
     */
    public function __construct(array $config = [], $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?: new Aben_Email_Logs();
    }

    /**
     * Send an email
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param array $headers Optional headers
     * @return bool True on success, false on failure
     */
    abstract public function send($to, $subject, $message, $headers = []);

    /**
     * Test connection/configuration
     *
     * @return bool True if configuration is valid
     */
    abstract public function test_connection();

    /**
     * Check if provider is properly configured
     *
     * @return bool True if configured
     */
    abstract public function is_configured();

    /**
     * Get provider name
     *
     * @return string Provider name
     */
    abstract public function get_name();

    /**
     * Close connection (if applicable)
     *
     * @return void
     */
    public function close()
    {
        // Default: do nothing. Override if needed.
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Configuration value
     */
    protected function get_config($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    /**
     * Check if provider supports batch sending
     *
     * @return bool True if batch sending is supported
     */
    public function supports_batch()
    {
        return false; // Default: no batch support
    }

    /**
     * Send multiple emails in a batch
     *
     * @param array $emails Array of email data ['to' => '', 'subject' => '', 'message' => '', 'headers' => []]
     * @return array Results with success/failure for each email
     */
    public function send_batch(array $emails)
    {
        // Default implementation: send individually
        $results = [];
        foreach ($emails as $email) {
            $results[] = $this->send($email["to"], $email["subject"], $email["message"], $email["headers"] ?? []);
        }
        return $results;
    }
}
