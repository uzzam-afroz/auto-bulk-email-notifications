<?php

/**
 * Plugin Name:       Aben - Auto Bulk Email Notifications
 * Plugin URI:        https://abenplugin.com
 * Description:       The simplest way to engage your subscribers or customers by scheduling and sending emails for your latest blogs, products, news etc. Just automate and send bulk emails directly from your website.
 * Version:           2.2.0
 * Author:            Rehan Khan
 * Author URI:        https://rehan.work/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       auto-bulk-email-notifications
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined("WPINC")) {
    die();
}

define("ABEN_VERSION", "2.2.0");
define("ABEN_PLUGIN_URL", plugin_dir_url(__FILE__));
define("ABEN_PLUGIN_PATH", plugin_dir_path(__FILE__));
define("ABEN_BRAND_TEXT", "Powered by");
define("ABEN_BRAND_LINK", "https://abenplugin.com");
define("ABEN_BRANDING", ABEN_PLUGIN_URL . "/assets/images/branding.png");
define("ABEN_FEATURED_IMAGE", ABEN_PLUGIN_URL . "/assets/images/featured-image.png");
define("ABEN_PLUGIN_LOGO", ABEN_PLUGIN_URL . "/assets/images/logo.png");

/**
 * Load Action Scheduler (bundled or via WooCommerce)
 */
if (!class_exists("ActionScheduler")) {
    if (file_exists(ABEN_PLUGIN_PATH . "libs/action-scheduler/action-scheduler.php")) {
        require_once ABEN_PLUGIN_PATH . "libs/action-scheduler/action-scheduler.php";
    }
}

// ========== CRITICAL: Load core files ALWAYS (not just in admin) ==========

// Load settings functions (needed for aben_get_options())
require_once ABEN_PLUGIN_PATH . "admin/partials/settings/settings-default.php";
require_once ABEN_PLUGIN_PATH . "admin/partials/settings/settings-validate.php";

// Load email classes and functions
require_once ABEN_PLUGIN_PATH . "admin/partials/email/class-aben-email.php";
require_once ABEN_PLUGIN_PATH . "admin/partials/email/class-email-logs.php";
require_once ABEN_PLUGIN_PATH . "admin/partials/email/email-build.php";
require_once ABEN_PLUGIN_PATH . "admin/partials/email/send-email.php";

// Load email provider system
require_once ABEN_PLUGIN_PATH . "admin/partials/providers/class-aben-email-provider.php";
require_once ABEN_PLUGIN_PATH . "admin/partials/providers/class-aben-smtp-provider.php";
require_once ABEN_PLUGIN_PATH . "admin/partials/providers/class-aben-tosend-provider.php";
require_once ABEN_PLUGIN_PATH . "admin/partials/providers/aben-provider-factory.php";

// Load SMTP functions
require_once ABEN_PLUGIN_PATH . "admin/partials/smtp/smtp-setup.php";

// Load cron functions
require_once ABEN_PLUGIN_PATH . "admin/partials/cron/cron-setup.php";

// Load Events class (needed for Action Scheduler callbacks)
require_once ABEN_PLUGIN_PATH . "admin/partials/event/class-aben-events.php";

/**
 * Register Action Scheduler callbacks
 * MUST run before 'init' hook (priority < 10)
 */
add_action("plugins_loaded", "aben_register_action_scheduler_hooks", 5);

function aben_register_action_scheduler_hooks()
{
    // Main scheduled email campaign
    add_action("aben_send_email_action", "aben_send_email");

    // Individual email worker
    add_action("aben_send_single_email_worker", "aben_send_single_email_worker");

    // Initialize Events class to register its Action Scheduler hooks
    // This ensures aben_process_event_email_batch callback is available
    Aben_Events::get_instance();
}

// ===========================================================================

/**
 * The code that runs during plugin activation.
 */
function aben_activate()
{
    require_once plugin_dir_path(__FILE__) . "includes/class-aben-activator.php";
    Aben_Activator::activate();
    aben_create_email_logs_table();
    aben_maybe_add_error_message_column();
    aben_add_user_meta_to_existing_users();
    aben_register_cron();
}

/**
 * The code that runs during plugin deactivation.
 */
function aben_deactivate()
{
    require_once plugin_dir_path(__FILE__) . "includes/class-aben-deactivator.php";
    Aben_Deactivator::deactivate();
    aben_deregister_cron();
}

register_activation_hook(__FILE__, "aben_activate");
register_deactivation_hook(__FILE__, "aben_deactivate");

// Include files for public facing side
if (!is_admin()) {
    include_once dirname(__FILE__) . "/public/partials/aben-public-display.php";
}

// Include files for admin dashboard side (UI only)
if (is_admin()) {
    include_once dirname(__FILE__) . "/admin/partials/aben-admin-display.php";
}

// Display plugin settings link
function aben_show_plugin_settings_link($links, $file)
{
    if (plugin_basename(__FILE__) == $file) {
        $settings_link = '<a href="admin.php?page=aben-settings">' . __("Settings", "auto-bulk-email-notifications") . "</a>";
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter("plugin_action_links", "aben_show_plugin_settings_link", 10, 2);

/** Function to add custom email logs table to the database */
function aben_create_email_logs_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "aben_email_logs";

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        email_to VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(255) NOT NULL,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . "wp-admin/includes/upgrade.php";
    dbDelta($sql);
}
/**
 * Ensure the email logs table contains the `error_message` column.
 *
 * This function performs a safe, idempotent schema check and adds the
 * `error_message` column only if it does not already exist.
 *
 * - Designed for production environments
 * - Backward compatible with existing data
 * - Safe to run on every request until the column exists
 * - No table recreation or data loss
 *
 * IMPORTANT:
 * This function must only perform additive schema changes.
 * Do NOT use it for destructive operations (DROP, RENAME, MODIFY).
 *
 * @return void
 */
function aben_maybe_add_error_message_column()
{
    global $wpdb;

    $table = $wpdb->prefix . "aben_email_logs";

    // Check if column already exists
    $column_exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", "error_message"));

    if (!$column_exists) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN error_message TEXT NULL");
    }
}

add_action("aben_cleanup_email_logs", function () {
    $logger = new Aben_Email_Logs();
    $logger->clear_old_logs();
});

/**
 * The core plugin class
 */
require plugin_dir_path(__FILE__) . "includes/class-aben.php";

/**
 * Begins execution of the plugin.
 */
function aben_run()
{
    $plugin = new Aben();
    $plugin->run();
}
aben_run();
