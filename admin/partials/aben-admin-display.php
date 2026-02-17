<?php

/**
 * Provide a admin area view for the plugin
 *
 * @link       https://rehan.work
 * @since      2.3.0
 *
 * @package    Aben
 * @subpackage Aben/admin/partials
 */

if (!defined("ABSPATH")) {
    exit();
}

// Admin UI files only
include_once dirname(__FILE__) . "/menu/admin-menu.php";
include_once dirname(__FILE__) . "/settings/settings-register.php";
include_once dirname(__FILE__) . "/settings/settings-callbacks.php";

// User management UI
include_once dirname(__FILE__) . "/user/add-user-settings.php";
include_once dirname(__FILE__) . "/user/add-user-meta.php";

// Events UI
include_once dirname(__FILE__) . "/event/class-aben-events.php";

// Cron management
include_once dirname(__FILE__) . "/cron/register-cron.php";
include_once dirname(__FILE__) . "/cron/update-cron.php";
