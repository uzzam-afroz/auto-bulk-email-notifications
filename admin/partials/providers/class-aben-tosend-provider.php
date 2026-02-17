<?php
/**
 * ToSend Email Provider
 *
 * Handles email sending via ToSend API
 *
 * @link       https://rehan.work
 * @since      2.3.0
 * @package    Aben
 * @subpackage Aben/admin/partials/providers
 */

if (!defined("ABSPATH")) {
    exit();
}

// Load ToSend SDK
require_once ABEN_PLUGIN_PATH . "libs/tosendapi.php";

use ToSend\Api;
use ToSend\ToSendException;

class Aben_ToSend_Provider extends Aben_Email_Provider
{
    /**
     * ToSend API instance
     *
     * @var Api|null
     */
    private $api = null;

    /**
     * Get provider name
     *
     * @return string Provider name
     */
    public function get_name()
    {
        return "ToSend";
    }

    /**
     * Check if ToSend is configured
     *
     * @return bool True if configured
     */
    public function is_configured()
    {
        return !empty($this->config["tosend_api_key"]) && !empty($this->config["tosend_from_email"]);
    }

    /**
     * Get ToSend API instance
     *
     * @return Api|null API instance or null on failure
     */
    private function get_api()
    {
        if ($this->api instanceof Api) {
            return $this->api;
        }

        if (!$this->is_configured()) {
            error_log("ABEN ToSend: Not configured");
            return null;
        }

        try {
            $api_key = aben_decrypt_password($this->config["tosend_api_key"]);
            $this->api = new Api($api_key);
            return $this->api;
        } catch (ToSendException $e) {
            error_log("ABEN ToSend: Failed to initialize: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if provider supports batch sending
     *
     * @return bool True - ToSend supports batch sending
     */
    public function supports_batch()
    {
        return true;
    }

    /**
     * Build email parameters for ToSend API
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message HTML message
     * @return array Email parameters
     */
    private function build_email_params($to, $subject, $message)
    {
        $from_name = $this->get_config("from_name", get_bloginfo("name"));
        $from_email = $this->config["tosend_from_email"];

        $params = [
            "from" => [
                "email" => $from_email,
                "name" => $from_name,
            ],
            "to" => [
                [
                    "email" => $to,
                ],
            ],
            "subject" => $subject,
            "html" => $message,
            "text" => wp_strip_all_tags($message),
        ];

        // Add reply-to if configured
        if (!empty($this->config["from_email"]) && $this->config["from_email"] !== $from_email) {
            $params["reply_to"] = [
                "email" => $this->config["from_email"],
                "name" => $from_name,
            ];
        }

        return $params;
    }

    /**
     * Send email via ToSend API
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param array $headers Optional headers
     * @return bool True on success, false on failure
     */
    public function send($to, $subject, $message, $headers = [])
    {
        $api = $this->get_api();

        if (!$api instanceof Api) {
            $this->logger->log_email($to, $subject, $message, "failed", "ToSend API not initialized");
            return false;
        }

        try {
            $params = $this->build_email_params($to, $subject, $message);
            $response = $api->send($params);

            if (isset($response["message_id"])) {
                $this->logger->log_email($to, $subject, $message, "sent");
                return true;
            }

            $this->logger->log_email($to, $subject, $message, "failed", "No message_id in response");
            return false;
        } catch (ToSendException $e) {
            $error_msg = $e->getMessage();
            $errors = $e->getErrors();

            if (!empty($errors)) {
                $error_msg .= " | Errors: " . json_encode($errors);
            }

            $this->logger->log_email($to, $subject, $message, "failed", $error_msg);
            error_log("ABEN ToSend: Send failed: " . $error_msg);
            return false;
        }
    }

    /**
     * Send multiple emails in a batch via ToSend API
     *
     * @param array $emails Array of ['to' => '', 'subject' => '', 'message' => '', 'headers' => []]
     * @return array Array of results (true/false for each email)
     */
    public function send_batch(array $emails)
    {
        $api = $this->get_api();

        if (!$api instanceof Api) {
            error_log("ABEN ToSend Batch: API not initialized");
            // Log all as failed
            foreach ($emails as $email) {
                $this->logger->log_email($email["to"], $email["subject"], $email["message"], "failed", "ToSend API not initialized");
            }
            return array_fill(0, count($emails), false);
        }

        try {
            // Build batch email array
            $batch_emails = [];
            foreach ($emails as $email) {
                $batch_emails[] = $this->build_email_params($email["to"], $email["subject"], $email["message"]);
            }

            // Send batch
            $response = $api->batch($batch_emails);

            // Process results
            $results = [];
            if (isset($response["results"]) && is_array($response["results"])) {
                foreach ($response["results"] as $index => $result) {
                    $email = $emails[$index];
                    $success = isset($result["message_id"]) || (isset($result["status"]) && $result["status"] === "success");

                    if ($success) {
                        $this->logger->log_email($email["to"], $email["subject"], $email["message"], "sent", "Batch send");
                        $results[] = true;
                    } else {
                        $error_msg = isset($result["error"]) ? json_encode($result["error"]) : "Unknown error";
                        $this->logger->log_email($email["to"], $email["subject"], $email["message"], "failed", "Batch send failed: " . $error_msg);
                        $results[] = false;
                    }
                }
            } else {
                // Unexpected response format - assume all succeeded if no error was thrown
                error_log("ABEN ToSend Batch: Unexpected response format: " . json_encode($response));
                foreach ($emails as $email) {
                    $this->logger->log_email($email["to"], $email["subject"], $email["message"], "sent", "Batch send (response unclear)");
                    $results[] = true;
                }
            }

            return $results;
        } catch (ToSendException $e) {
            $error_msg = $e->getMessage();
            $errors = $e->getErrors();

            if (!empty($errors)) {
                $error_msg .= " | Errors: " . json_encode($errors);
            }

            error_log("ABEN ToSend Batch: Send failed: " . $error_msg);

            // Log all as failed
            foreach ($emails as $email) {
                $this->logger->log_email($email["to"], $email["subject"], $email["message"], "failed", $error_msg);
            }

            return array_fill(0, count($emails), false);
        }
    }

    /**
     * Test ToSend API connection
     *
     * @return bool True if connection successful
     */
    public function test_connection()
    {
        $api = $this->get_api();

        if (!$api instanceof Api) {
            return false;
        }

        try {
            $info = $api->getAccountInfo();
            return isset($info["account"]);
        } catch (ToSendException $e) {
            error_log("ABEN ToSend: Test connection failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Close connection (no-op for API)
     *
     * @return void
     */
    public function close()
    {
        // No persistent connection to close for API
    }
}
