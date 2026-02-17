<?php

class Aben_Events
{
    private static $instance = null;
    public $options;

    public function __construct()
    {
        $this->options = $this->get_options();

        // Hook to handle the form submissions
        add_action("admin_post_aben_send_event_emails_action", [$this, "handle_send_email_form"]);
        add_action("admin_post_aben_events_send_test_email_action", [$this, "handle_send_test_email_form"]);

        // Background Processing Hook (Action Scheduler)
        add_action("aben_process_individual_email_job", [$this, "execute_background_email"], 10, 2);
        add_action("aben_process_event_email_batch", [$this, "process_event_email_batch"], 10, 2);
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Display methods for Admin UI
     */
    public function display_email_form()
    {
        $this->email_form();
    }
    public function display_template_settings_form()
    {
        $this->template_settings_form();
    }
    public function display_template()
    {
        $this->template();
    }

    /**
     * Test email form
     */
    public function test_email_form()
    {
        require_once ABEN_PLUGIN_PATH . "admin/partials/event/forms/test-email-form.php";
    }

    /**
     * Schedule batch email jobs based on provider capability
     */
    public function handle_send_email_form()
    {
        if (
            !isset($_POST["aben_send_event_emails_nonce"]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST["aben_send_event_emails_nonce"])), "aben_send_event_emails_action")
        ) {
            wp_die("Security check failed.");
        }

        error_log("ABEN EVENT: handle_send_email_form() called");

        // Check if provider supports batch
        $logger = new Aben_Email_Logs();
        $provider = Aben_Provider_Factory::create(null, $logger);
        $supports_batch = $provider && $provider->supports_batch();

        $provider_name = $provider ? $provider->get_name() : "None";
        error_log("ABEN EVENT: Provider: {$provider_name}, Supports batch: " . ($supports_batch ? "Yes" : "No"));

        if ($supports_batch) {
            // Use batch processing
            $batch_id = uniqid("aben_event_", true);
            set_transient("aben_event_options_{$batch_id}", $this->options, HOUR_IN_SECONDS);

            error_log("ABEN EVENT: Using batch mode, batch_id: {$batch_id}");

            if (function_exists("as_enqueue_async_action")) {
                as_enqueue_async_action("aben_process_event_email_batch", [$batch_id, 0], "aben-events");
                error_log("ABEN EVENT: Scheduled batch worker with offset 0");
            }
        } else {
            // Fallback to individual jobs (SMTP)
            error_log("ABEN EVENT: Using individual mode (SMTP)");

            $users = get_users([
                "role" => $this->options["role"],
                "fields" => "ID",
            ]);

            error_log("ABEN EVENT: Found " . count($users) . " users for role: " . $this->options["role"]);

            if (!empty($users)) {
                foreach ($users as $user_id) {
                    if (function_exists("as_enqueue_async_action")) {
                        as_enqueue_async_action(
                            "aben_process_individual_email_job",
                            [
                                "user_id" => $user_id,
                                "email_subject" => $this->options["email_subject"],
                            ],
                            "aben-events",
                        );
                    }
                }
                error_log("ABEN EVENT: Scheduled " . count($users) . " individual workers");
            } else {
                error_log("ABEN EVENT: No users found, redirecting with error");
                wp_redirect(add_query_arg("message", "error", admin_url("admin.php?page=aben-events")));
                exit();
            }
        }

        error_log("ABEN EVENT: Redirecting with success message");
        wp_redirect(add_query_arg("message", "success", admin_url("admin.php?page=aben-events")));
        exit();
    }

    /**
     * Process event email batch (for batch-capable providers)
     */
    public function process_event_email_batch($batch_id, $offset)
    {
        global $wpdb;

        error_log("ABEN EVENT BATCH: Worker called - Batch ID: {$batch_id}, Offset: {$offset}");

        // Get options from transient
        $options = get_transient("aben_event_options_{$batch_id}");
        if (false === $options || empty($options["role"])) {
            error_log("ABEN EVENT BATCH: Options not found or no role configured for batch_id: {$batch_id}");
            return;
        }

        error_log("ABEN EVENT BATCH: Target role: " . $options["role"]);

        // Check if provider supports batch
        $logger = new Aben_Email_Logs();
        $provider = Aben_Provider_Factory::create(null, $logger);
        $supports_batch = $provider && $provider->supports_batch();

        if (!$supports_batch) {
            error_log("ABEN EVENT BATCH: Provider does not support batch, should not reach here");
            return; // Should not reach here, but safety check
        }

        $provider_name = $provider->get_name();
        error_log("ABEN EVENT BATCH: Using provider: {$provider_name}");

        $limit = 100; // Batch size for ToSend
        error_log("ABEN EVENT BATCH: Batch limit: {$limit}");

        // Query users by role with offset
        $role = $options["role"];
        $users = get_users([
            "role" => $role,
            "fields" => ["ID", "user_email", "display_name"],
            "number" => $limit,
            "offset" => $offset,
        ]);

        error_log("ABEN EVENT BATCH: Found " . count($users) . " users at offset {$offset}");

        if (empty($users)) {
            error_log("ABEN EVENT BATCH: No more users, batch processing complete");
            return; // Done
        }

        // Build batch emails
        $batch_emails = [];
        $user_map = [];

        foreach ($users as $user) {
            $email_body = $this->personalized_email($user->user_email);
            $tracking_id = apply_filters("aben_before_email_sent_filter", null);

            $email_body = apply_filters("aben_email_template_html_filter", $email_body, $tracking_id, $user->ID);

            $batch_emails[] = [
                "to" => $user->user_email,
                "subject" => $options["email_subject"],
                "message" => $email_body,
                "headers" => [],
            ];

            $user_map[] = [
                "user_id" => $user->ID,
                "tracking_id" => $tracking_id,
            ];
        }

        if (empty($batch_emails)) {
            error_log("ABEN EVENT BATCH: No valid emails to send");
            return;
        }

        error_log("ABEN EVENT BATCH: Prepared " . count($batch_emails) . " emails, calling provider->send_batch()");

        // Send batch
        $results = $provider->send_batch($batch_emails);

        error_log("ABEN EVENT BATCH: Batch send completed, processing results");

        // Process results
        foreach ($results as $index => $success) {
            $user_info = $user_map[$index];
            if ($success) {
                do_action("aben_after_email_sent_action", $user_info["tracking_id"], $user_info["user_id"]);
            }
        }

        $success_count = array_sum($results);
        $total_count = count($results);
        error_log("ABEN EVENT BATCH: Sent {$success_count}/{$total_count} emails successfully");

        // Schedule next batch if more users exist
        if (count($users) >= $limit) {
            $next_offset = $offset + $limit;
            error_log("ABEN EVENT BATCH: Scheduling next batch with offset {$next_offset}");
            if (function_exists("as_enqueue_async_action")) {
                as_enqueue_async_action("aben_process_event_email_batch", [$batch_id, $next_offset], "aben-events");
            }
        } else {
            error_log("ABEN EVENT BATCH: Last batch processed, no more users");
        }
    }

    /**
     * Background processor per user to prevent site slowdown (fallback for SMTP)
     */
    public function execute_background_email($user_id, $email_subject)
    {
        error_log("ABEN EVENT WORKER: Processing user_id: {$user_id}");

        $user = get_userdata($user_id);
        if (!$user) {
            error_log("ABEN EVENT WORKER: User not found for user_id: {$user_id}");
            return;
        }

        error_log("ABEN EVENT WORKER: Sending to {$user->user_email}");

        $logger = new Aben_Email_Logs();

        $email_body = $this->personalized_email($user->user_email);
        $tracking_id = apply_filters("aben_before_email_sent_filter", null);

        $email_body = apply_filters("aben_email_template_html_filter", $email_body, $tracking_id, $user->ID);

        if (aben_send_smtp_email($user->user_email, $email_subject, $email_body, $logger)) {
            error_log("ABEN EVENT WORKER: Email sent successfully to {$user->user_email}");
            do_action("aben_after_email_sent_action", $tracking_id, $user->ID);
        } else {
            error_log("ABEN EVENT WORKER: Email failed to {$user->user_email}");
        }

        aben_close_smtp_mailer();
    }

    /**
     * Test email remains synchronous for immediate feedback
     */
    public function handle_send_test_email_form()
    {
        if (
            !isset($_POST["aben_events_send_test_email_nonce"]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST["aben_events_send_test_email_nonce"])), "aben_events_send_test_email_action")
        ) {
            wp_die("Security check failed.");
        }

        $email_id = isset($_POST["aben_events_test_email"]) ? sanitize_email(wp_unslash($_POST["aben_events_test_email"])) : "";

        if (!empty($email_id)) {
            $email_subject = $this->options["email_subject"];
            $test_email_body = $this->build_email();

            if (aben_send_smtp_email($email_id, $email_subject, $test_email_body)) {
                aben_close_smtp_mailer();
                wp_redirect(add_query_arg("message", "test-success", wp_get_referer()));
            } else {
                wp_redirect(add_query_arg("message", "error", wp_get_referer()));
            }
        }
        exit();
    }

    private function get_options()
    {
        return get_option("aben_event_options");
    }

    private function email_form()
    {
        require_once ABEN_PLUGIN_PATH . "admin/partials/event/forms/email-form.php";
    }

    private function template_settings_form()
    {
        require_once ABEN_PLUGIN_PATH . "admin/partials/event/forms/template-form.php";
    }

    private function template()
    {
        require ABEN_PLUGIN_PATH . "admin/partials/event/templates/email.php";
    }

    private function build_email()
    {
        ob_start();
        $this->template();
        return ob_get_clean();
    }

    private function personalized_email($user_email)
    {
        $email_content = $this->build_email();
        $user = get_user_by("email", $user_email);
        $user_display_name = ucfirst($user->display_name);
        $user_display_name = explode(" ", $user_display_name);
        $user_firstname = $user_display_name[0];
        return str_replace("{{USERNAME}}", $user_firstname, $email_content);
    }
}
