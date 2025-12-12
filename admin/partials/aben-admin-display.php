<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://rehan.work
 * @since      2.1.0
 *
 * @package    Aben
 * @subpackage Aben/admin/partials
 */

if (! defined('ABSPATH')) {
    exit;
}

include_once dirname(__FILE__) . '/menu/admin-menu.php';
include_once dirname(__FILE__) . '/settings/settings-register.php';
include_once dirname(__FILE__) . '/settings/settings-default.php';
include_once dirname(__FILE__) . '/settings/settings-callbacks.php';
include_once dirname(__FILE__) . '/settings/settings-validate.php';
include_once dirname(__FILE__) . '/user/add-user-settings.php';
include_once dirname(__FILE__) . '/user/add-user-meta.php';
include_once dirname(__FILE__) . '/smtp/smtp-setup.php';
include_once dirname(__FILE__) . '/email/send-email.php';
include_once dirname(__FILE__) . '/email/email-build.php';
include_once dirname(__FILE__) . '/email/class-email-logs.php';
include_once dirname(__FILE__) . '/email/class-aben-email.php';
include_once dirname(__FILE__) . '/cron/cron-setup.php';
include_once dirname(__FILE__) . '/cron/register-cron.php';
include_once dirname(__FILE__) . '/cron/update-cron.php';
include_once dirname(__FILE__) . '/event/class-aben-events.php';
