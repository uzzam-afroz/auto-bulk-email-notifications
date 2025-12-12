<?php // ABEN Menu

if (! defined('ABSPATH')) {

    exit;
}

function aben_add_top_level_menu()
{
    add_menu_page(
        'Settings: Auto Bulk Email Notifications',
        'Aben',
        'manage_options',
        'auto-bulk-email-notifications',
        'aben_display_settings_page',
        'dashicons-email-alt2',
        26,
    );

    add_submenu_page(
        'auto-bulk-email-notifications',
        'Auto Emails',
        'Auto Emails',
        'manage_options',
        'auto-bulk-email-notifications',
        'aben_display_settings_page'
    );

    add_submenu_page(
        'auto-bulk-email-notifications',
        'Event Emails',
        'Event Emails',
        'manage_options',
        'aben-events',
        'aben_display_events_page'
    );

    add_submenu_page(
        'auto-bulk-email-notifications',
        'Analytics',
        'Analytics',
        'manage_options',
        'aben-analytics',
        'aben_display_dashboard_page'
    );


    add_submenu_page(
        'auto-bulk-email-notifications',
        'Settings',
        'Settings',
        'manage_options',
        'aben-settings',
        'aben_display_smtp_page'
    );
}
add_action('admin_menu', 'aben_add_top_level_menu');
