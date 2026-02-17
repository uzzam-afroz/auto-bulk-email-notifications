<?php
// User Meta Hooks - Load globally for frontend and admin

if (!defined("ABSPATH")) {
    exit();
}

// Add user meta on new user registration
add_action("user_register", "aben_add_user_meta", 10, 1);

function aben_add_user_meta($user_id)
{
    add_user_meta($user_id, "aben_notification", "1");
    add_user_meta($user_id, "aben_unsubscribe_date", null);
}
