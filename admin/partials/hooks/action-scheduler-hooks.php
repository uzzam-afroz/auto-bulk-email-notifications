<?php

/**
 * Action Scheduler Hook Registrations
 *
 * @package Aben
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all Action Scheduler callbacks
 * These must be registered early for Action Scheduler to find them
 */
function aben_register_scheduler_actions()
{
    // Main email campaign action
    add_action('aben_send_email_action', 'aben_send_email');

    // Individual email worker
    add_action('aben_send_single_email_worker', 'aben_send_single_email_worker');
}

// Hook registration must happen before Action Scheduler processes queue
add_action('plugins_loaded', 'aben_register_scheduler_actions', 5);
