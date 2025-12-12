<?php

class Aben_Events
{
    private static $instance = null;
    private static $slug     = 'aben-events';
    public $options;

    public function __construct()
    {
        $this->options = $this->get_options();

        // Hook to handle the form submission
        add_action('admin_post_aben_send_event_emails_action', [$this, 'handle_send_email_form']);
        add_action('admin_post_aben_events_send_test_email_action', [$this, 'handle_send_test_email_form']);
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

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

    public function handle_send_email_form()
    {
        if (
            ! isset($_POST['aben_send_event_emails_nonce']) ||
            ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['aben_send_event_emails_nonce'])), 'aben_send_event_emails_action')
        ) {
            wp_die('Security check failed.');
        }
        $this->send_email();
        exit;
    }

    public function test_email_form()
    {
        require_once ABEN_PLUGIN_PATH . 'admin/partials/event/forms/test-email-form.php';
    }

    public function handle_send_test_email_form()
    {
        if (
            ! isset($_POST['aben_events_send_test_email_nonce']) ||
            ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['aben_events_send_test_email_nonce'])), 'aben_events_send_test_email_action')
        ) {
            wp_die('Security check failed.');
        }

        $email_id = isset($_POST['aben_events_test_email']) ? sanitize_email(wp_unslash($_POST['aben_events_test_email'])) : '';

        if (! empty($email_id)) {
            $this->send_email($email_id);
        }
        exit;
    }

    public function send_email($email_id = '')
    {
        $email_subject   = $this->options['email_subject'];
        $test_email_body = $this->build_email();

        if (empty($email_id)) {

            $users = get_users(['role' => $this->options['role']]);

            //Return if no users found to the matching role
            if (empty($users)) {
                // error_log('No users found for the role ');
                return;
            }

            //Loop through the users and send email
            foreach ($users as $user) {

                $user_display_name = ucfirst($user->display_name);
                $user_display_name = explode(' ', $user_display_name);
                $user_firstname = $user_display_name[0];

                if (function_exists('aben_generate_login_token')) {
                    $auto_login_token = aben_generate_login_token($email_address);
                } else {
                    $auto_login_token = '';
                }

                $email_body = $this->personalized_email($user->user_email);
                $tracking_id = apply_filters('aben_before_email_sent_filter', null);
                $email_body = apply_filters('aben_email_template_html_filter', $email_body, $tracking_id, $user->ID);
                if (aben_send_smtp_email($user->user_email, $email_subject, $email_body)) {
                    do_action('aben_after_email_sent_action', $tracking_id, $user->ID);
                    wp_redirect(add_query_arg('message', 'success', admin_url('admin.php?page=aben-events')));
                } else {
                    wp_redirect(add_query_arg('message', 'error', admin_url('admin.php?page=aben-events')));
                }
            }
        } else {
            if (aben_send_smtp_email($email_id, $email_subject, $test_email_body)) {
                wp_redirect(add_query_arg('message', 'test-success', wp_get_referer()));
            } else {
                wp_redirect(add_query_arg('message', 'error', wp_get_referer()));
            }
        }
        return true;
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
        $personalized_email = str_replace('{{USERNAME}}', $user_firstname, $email_content);
        return $personalized_email;
    }
}
