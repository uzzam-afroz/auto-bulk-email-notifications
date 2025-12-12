<?php

    if (! defined('ABSPATH')) {
        exit;
    }

?>

<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="aben_events_test_form">
    <input type="hidden" name="action" value="aben_events_send_test_email_action">
    <input type="email" name="aben_events_test_email" id="aben_events_test_email" required
        placeholder="Enter email address">
    <input type="submit" class="button button-primary" value="Send Test Email" />
    <?php wp_nonce_field('aben_events_send_test_email_action', 'aben_events_send_test_email_nonce'); ?>
</form>