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
            return null;
        }

        try {
            $api_key = aben_decrypt_password($this->config["tosend_api_key"]);
            $this->api = new Api($api_key);
            return $this->api;
        } catch (ToSendException $e) {
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
     * Log outbound API payload for debugging when WP_DEBUG is enabled.
     * Writes to wp-content/aben-tosend-debug.log
     *
     * @param string $to Recipient email for log context
     * @param string $subject Subject for log context
     * @param array  $payload API payload
     * @param string $context single|batch
     * @return void
     */
    private function log_api_payload_debug($to, $subject, array $payload, $context = "single")
    {
        if (!(defined("WP_DEBUG") && WP_DEBUG)) {
            return;
        }

        $prepared = $this->prepare_payload_for_debug($payload);
        $payload_json = wp_json_encode($prepared, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (false === $payload_json) {
            $payload_json = "Payload JSON encoding failed";
        }

        $this->write_debug_log_file($to, $subject, $payload_json, $context);
    }

    /**
     * Write ToSend payload logs to a dedicated file.
     *
     * @param string $to
     * @param string $subject
     * @param string $payload_json
     * @param string $context
     * @return void
     */
    private function write_debug_log_file($to, $subject, $payload_json, $context = "single")
    {
        $log_file = trailingslashit(WP_CONTENT_DIR) . "aben-tosend-debug.log";
        $timestamp = current_time("mysql");
        $line =
            "[" .
            $timestamp .
            "] [ToSend][" .
            sanitize_text_field($context) .
            "] to=" .
            sanitize_email($to) .
            " subject=" .
            sanitize_text_field($subject) .
            " payload=" .
            $payload_json .
            PHP_EOL;
        error_log($line, 3, $log_file);
    }

    /**
     * Prepare payload for debug logs (truncate large content and strip attachment body).
     *
     * @param array $payload
     * @return array
     */
    private function prepare_payload_for_debug(array $payload)
    {
        $truncate = static function ($value, $limit = 2000) {
            $value = (string) $value;
            if (strlen($value) <= $limit) {
                return $value;
            }
            return substr($value, 0, $limit) . "... [truncated]";
        };

        $sanitize_email_payload = static function (array &$email) use ($truncate) {
            if (isset($email["html"])) {
                $email["html_length"] = strlen((string) $email["html"]);
                $email["html"] = $truncate($email["html"]);
            }

            if (isset($email["text"])) {
                $email["text_length"] = strlen((string) $email["text"]);
                $email["text"] = $truncate($email["text"]);
            }

            if (!empty($email["attachments"]) && is_array($email["attachments"])) {
                foreach ($email["attachments"] as &$attachment) {
                    if (isset($attachment["content"])) {
                        $attachment["content_length"] = strlen((string) $attachment["content"]);
                        $attachment["content"] = "[omitted for debug log]";
                    }
                }
                unset($attachment);
            }
        };

        if (isset($payload["emails"]) && is_array($payload["emails"])) {
            foreach ($payload["emails"] as &$email_payload) {
                if (is_array($email_payload)) {
                    $sanitize_email_payload($email_payload);
                }
            }
            unset($email_payload);
        } else {
            $sanitize_email_payload($payload);
        }

        return $payload;
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
            $this->log_api_payload_debug($to, $subject, $params, "single");
            $response = $api->send($params);

            if (isset($response["message_id"])) {
                $message_id = sanitize_text_field((string) $response["message_id"]);
                $this->logger->log_email($to, $subject, $message, "sent", "message_id: " . $message_id);
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

            $batch_to = isset($emails[0]["to"]) ? $emails[0]["to"] : "";
            $batch_subject = "Batch emails (" . count($emails) . ")";
            $this->log_api_payload_debug($batch_to, $batch_subject, ["emails" => $batch_emails], "batch");

            // Send batch
            $response = $api->batch($batch_emails);

            // Process results
            $results = [];
            if (isset($response["results"]) && is_array($response["results"])) {
                foreach ($response["results"] as $index => $result) {
                    $email = $emails[$index];
                    $success = isset($result["message_id"]) || (isset($result["status"]) && $result["status"] === "success");

                    if ($success) {
                        $message_id = isset($result["message_id"]) ? sanitize_text_field((string) $result["message_id"]) : "";
                        $success_note = !empty($message_id) ? "message_id: " . $message_id : "Batch send";
                        $this->logger->log_email($email["to"], $email["subject"], $email["message"], "sent", $success_note);
                        $results[] = true;
                    } else {
                        $error_msg = isset($result["error"]) ? json_encode($result["error"]) : "Unknown error";
                        $this->logger->log_email($email["to"], $email["subject"], $email["message"], "failed", "Batch send failed: " . $error_msg);
                        $results[] = false;
                    }
                }
            } else {
                // Unexpected response format - assume all succeeded if no error was thrown
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
