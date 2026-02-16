<?php

/**
 * ToSend PHP SDK
 *
 * A simple PHP SDK for the ToSend email API.
 *
 * @version 1.0.0
 * @license MIT
 */

namespace ToSend;

class Api
{
    private string $apiKey;
    private string $baseUrl = "https://api.tosend.com";

    /**
     * Create a new Api instance.
     *
     * @param string $apiKey Your ToSend API key
     * @throws ToSendException If API key is empty
     */
    public function __construct(string $apiKey)
    {
        $apiKey = trim($apiKey);

        if (empty($apiKey)) {
            throw new ToSendException("API key is required", 401);
        }

        $this->apiKey = $apiKey;
    }

    /**
     * Set a custom base URL (useful for testing).
     *
     * @param string $baseUrl The base URL for API requests
     * @return self
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, "/");
        return $this;
    }

    /**
     * Send a single email.
     *
     * @param array $params Email parameters:
     *   - from: array ['email' => string, 'name' => string (optional)]
     *   - to: array of ['email' => string, 'name' => string (optional)]
     *   - subject: string
     *   - html: string (optional, required if no text)
     *   - text: string (optional, required if no html)
     *   - cc: array (optional)
     *   - bcc: array (optional)
     *   - reply_to: array (optional) ['email' => string, 'name' => string (optional)]
     *   - headers: array (optional)
     *   - attachments: array (optional) of ['type' => string, 'name' => string, 'content' => string (base64)]
     * @return array Response with message_id on success
     * @throws ToSendException
     */
    public function send(array $params): array
    {
        $this->validateEmailParams($params);

        return $this->request("POST", "/v2/emails", $params);
    }

    /**
     * Send multiple emails in a single request.
     *
     * @param array $emails Array of email objects (same format as send())
     * @return array Response with results array
     * @throws ToSendException
     */
    public function batch(array $emails): array
    {
        if (empty($emails)) {
            throw new ToSendException("Emails array is required and cannot be empty", 422, [
                "emails" => ["required" => "At least one email is required"],
            ]);
        }

        if (!is_array($emails)) {
            throw new ToSendException("Emails must be an array", 422, [
                "emails" => ["invalid" => "Emails must be an array"],
            ]);
        }

        foreach ($emails as $index => $email) {
            try {
                $this->validateEmailParams($email);
            } catch (ToSendException $e) {
                throw new ToSendException("Email at index {$index}: " . $e->getMessage(), $e->getCode(), $e->getErrors());
            }
        }

        return $this->request("POST", "/v2/emails/batch", ["emails" => $emails]);
    }

    /**
     * Get account information.
     *
     * @return array Account and domains information
     * @throws ToSendException
     */
    public function getAccountInfo(): array
    {
        return $this->request("GET", "/v2/info");
    }

    /**
     * Validate email parameters.
     *
     * @param array $params Email parameters
     * @throws ToSendException If validation fails
     */
    private function validateEmailParams(array $params): void
    {
        $errors = [];

        // Validate 'from'
        if (empty($params["from"])) {
            $errors["from"] = ["required" => "From is required"];
        } elseif (!is_array($params["from"])) {
            $errors["from"] = ["invalid" => "From must be an array with email and optional name"];
        } elseif (empty($params["from"]["email"])) {
            $errors["from"] = ["email_required" => "From email is required"];
        } elseif (!filter_var($params["from"]["email"], FILTER_VALIDATE_EMAIL)) {
            $errors["from"] = ["email_invalid" => "From email is invalid"];
        }

        // Validate 'to'
        if (empty($params["to"])) {
            $errors["to"] = ["required" => "To is required"];
        } elseif (!is_array($params["to"])) {
            $errors["to"] = ["invalid" => "To must be an array of recipients"];
        } else {
            $hasValidRecipient = false;
            foreach ($params["to"] as $recipient) {
                if (is_array($recipient) && !empty($recipient["email"]) && filter_var($recipient["email"], FILTER_VALIDATE_EMAIL)) {
                    $hasValidRecipient = true;
                    break;
                }
            }
            if (!$hasValidRecipient) {
                $errors["to"] = ["invalid" => "At least one valid recipient email is required"];
            }
        }

        // Validate 'subject'
        if (empty($params["subject"])) {
            $errors["subject"] = ["required" => "Subject is required"];
        }

        // Validate 'html' or 'text'
        if (empty($params["html"]) && empty($params["text"])) {
            $errors["content"] = ["required" => "Either html or text content is required"];
        }

        if (!empty($errors)) {
            $firstError = reset($errors);
            $message = is_array($firstError) ? reset($firstError) : $firstError;
            throw new ToSendException($message, 422, $errors);
        }
    }

    /**
     * Make an API request.
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     * @throws ToSendException
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        $headers = ["Authorization: Bearer " . $this->apiKey, "Content-Type: application/json", "Accept: application/json"];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new ToSendException("cURL error: " . $error);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $message = $decoded["message"] ?? "Unknown error";
            $errors = $decoded["errors"] ?? [];
            throw new ToSendException($message, $httpCode, $errors);
        }

        return $decoded;
    }
}

class ToSendException extends \Exception
{
    private array $errors;

    public function __construct(string $message, int $code = 0, array $errors = [])
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    /**
     * Get validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
