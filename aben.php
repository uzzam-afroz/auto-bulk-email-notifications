<?php

/**
 * @link              https://rehan.work
 * @since             2.1.0
 * @package           Aben
 *
 * @wordpress-plugin
 * Plugin Name:       Aben - Auto Bulk Email Notifications
 * Plugin URI:        https://abenplugin.com
 * Description:       The simplest way to engage your subscribers or customers by scheduling and sending emails for your latest blogs, products, news etc. Just automate and send bulk emails directly from your website.
 * Version:           2.1.0
 * Author:            Rehan Khan
 * Author URI:        https://rehan.work/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       auto-bulk-email-notifications
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

define('ABEN_VERSION', '2.1.0');
define('ABEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ABEN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ABEN_BRAND_TEXT', 'Powered by');
define('ABEN_BRAND_LINK', 'https://abenplugin.com');
define('ABEN_BRANDING', ABEN_PLUGIN_URL . '/assets/images/branding.png');
define('ABEN_FEATURED_IMAGE', ABEN_PLUGIN_URL . '/assets/images/featured-image.png');
define('ABEN_PLUGIN_LOGO', ABEN_PLUGIN_URL . '/assets/images/logo.png');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-aben-activator.php
 */
function aben_activate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-aben-activator.php';
    Aben_Activator::activate();
    aben_create_email_logs_table();

    aben_add_user_meta_to_existing_users(); //Refer add-user-meta.php
    aben_register_cron();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-aben-deactivator.php
 */
function aben_deactivate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-aben-deactivator.php';
    Aben_Deactivator::deactivate();

    aben_deregister_cron();
}

register_activation_hook(__FILE__, 'aben_activate');

register_deactivation_hook(__FILE__, 'aben_deactivate');

// Include files for public facing side
if (! is_admin()) {
    include_once dirname(__FILE__) . '/public/partials/aben-public-display.php';
}

// Include files for admin dashboard sife
if (is_admin()) {
    include_once dirname(__FILE__) . '/admin/partials/aben-admin-display.php';
}

// Display plugin settings link with deactivate link
function aben_show_plugin_settings_link($links, $file)
{
    if (plugin_basename(__FILE__) == $file) {
        $settings_link = '<a href="admin.php?page=aben-settings">' . __('Settings', 'auto-bulk-email-notifications') . '</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter('plugin_action_links', 'aben_show_plugin_settings_link', 10, 2);

/** Function add add custom email logs table to the database */
function aben_create_email_logs_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'aben_email_logs';

    // SQL statement to create the table
    $charset_collate = $wpdb->get_charset_collate();
    $sql             = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        email_to VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status  VARCHAR(255) NOT NULL,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-aben.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.1.0
 */
function aben_run()
{

    $plugin = new Aben();
    $plugin->run();
}
aben_run();
