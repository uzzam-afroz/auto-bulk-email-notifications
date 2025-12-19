<?php

class Aben_Events
{
    private static $instance = null;
    public $options;

    public function __construct()
    {
        $this->options = $this->get_options();

        // Hook to handle the form submissions
        add_action('admin_post_aben_send_event_emails_action', [$this, 'handle_send_email_form']);
        add_action('admin_post_aben_events_send_test_email_action', [$this, 'handle_send_test_email_form']);

        // Background Processing Hook (Action Scheduler)
        add_action('aben_process_individual_email_job', [$this, 'execute_background_email'], 10, 2);
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
        require_once ABEN_PLUGIN_PATH . 'admin/partials/event/forms/test-email-form.php';
    }

    /**
     * Optimized to queue IDs rather than processing them all here.
     */
    public function handle_send_email_form()
    {
        if (
            ! isset($_POST['aben_send_event_emails_nonce']) ||
            ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['aben_send_event_emails_nonce'])), 'aben_send_event_emails_action')
        ) {
            wp_die('Security check failed.');
        }

        // Fetch only IDs to prevent memory exhaustion with large user bases
        $users = get_users([
            'role'   => $this->options['role'],
            'fields' => 'ID'
        ]);

        if (!empty($users)) {
            foreach ($users as $user_id) {
                // Schedule each email as a background task via Action Scheduler
                if (function_exists('as_enqueue_async_action')) {
                    as_enqueue_async_action('aben_process_individual_email_job', [
                        'user_id'       => $user_id,
                        'email_subject' => $this->options['email_subject']
                    ], 'aben-events');
                }
            }
            wp_redirect(add_query_arg('message', 'success', admin_url('admin.php?page=aben-events')));
        } else {
            wp_redirect(add_query_arg('message', 'error', admin_url('admin.php?page=aben-events')));
        }
        exit;
    }

    /**
     * Background processor per user to prevent site slowdown.
     */
    public function execute_background_email($user_id, $email_subject)
    {
        $user = get_userdata($user_id);
        if (! $user) {
            return;
        }

        $logger = new Aben_Email_Logs();

        $email_body  = $this->personalized_email($user->user_email);
        $tracking_id = apply_filters('aben_before_email_sent_filter', null);

        $email_body = apply_filters(
            'aben_email_template_html_filter',
            $email_body,
            $tracking_id,
            $user->ID
        );

        if (aben_send_smtp_email(
            $user->user_email,
            $email_subject,
            $email_body,
            $logger
        )) {
            do_action('aben_after_email_sent_action', $tracking_id, $user->ID);
        }

        aben_close_smtp_mailer();
    }

    /**
     * Test email remains synchronous for immediate feedback
     */
    public function handle_send_test_email_form()
    {
        if (
            ! isset($_POST['aben_events_send_test_email_nonce']) ||
            ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['aben_events_send_test_email_nonce'])),
                'aben_events_send_test_email_action'
            )
        ) {
            wp_die('Security check failed.');
        }

        $email_id = isset($_POST['aben_events_test_email']) ? sanitize_email(wp_unslash($_POST['aben_events_test_email'])) : '';

        if (! empty($email_id)) {
            $email_subject = $this->options['email_subject'];
            $test_email_body = $this->build_email();

            if (aben_send_smtp_email($email_id, $email_subject, $test_email_body)) {
                aben_close_smtp_mailer();
                wp_redirect(add_query_arg('message', 'test-success', wp_get_referer()));
            } else {
                wp_redirect(add_query_arg('message', 'error', wp_get_referer()));
            }
        }
        exit;
    }

    private function get_options()
    {
        return get_option('aben_event_options');
    }

    private function email_form()
    {
        require_once ABEN_PLUGIN_PATH . 'admin/partials/event/forms/email-form.php';
    }

    private function template_settings_form()
    {
        require_once ABEN_PLUGIN_PATH . 'admin/partials/event/forms/template-form.php';
    }

    private function template()
    {
        require ABEN_PLUGIN_PATH . 'admin/partials/event/templates/email.php';
    }

    private function build_email()
    {
        ob_start();
        $this->template();
        return ob_get_clean();
    }

    private function personalized_email($user_email)
    {
        $email_content      = $this->build_email();
        $user               = get_user_by('email', $user_email);
        $user_display_name  = ucfirst($user->display_name);
        $user_display_name  = explode(' ', $user_display_name);
        $user_firstname     = $user_display_name[0];
        return str_replace('{{USERNAME}}', $user_firstname, $email_content);
    }
}
