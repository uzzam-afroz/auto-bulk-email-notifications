<?php // ABEN Settings Page

if (!defined("ABSPATH")) {
    exit();
}

add_action("admin_init", "aben_register_settings");

function aben_display_settings_page()
{
    if (!current_user_can("manage_options")) {
        return;
    }

    $tabs = [
        "general" => "General",
        "email" => "Email Template",
        "unsubscribe" => "Unsubscribe",
    ];

    $current_tab = "general";

    if (isset($_GET["tab"])) {
        // Unslash the input and sanitize it
        $tab = sanitize_text_field(wp_unslash($_GET["tab"]));

        // Check if the tab exists in the $tabs array
        if (isset($tabs[$tab])) {
            $current_tab = $tab;
        }
    }
    ?>

    <div id="aben-app">
        <?php if (isset($_GET["settings-updated"])) {
            echo '<div id="aben-notice" class="notice notice-success is-dismissible"><p>Saved.</p></div>';
        } ?>
        <div id="aben-header">

            <div id="aben-logo"><img src="<?php echo esc_url(ABEN_PLUGIN_LOGO); ?>" alt="Aben plugin logo" /></div>

            <nav class="nav-tab-wrapper" id="aben-nav">
                <div id="aben-nav-menu">
                    <?php foreach ($tabs as $tab => $name): ?>
                        <a href="?page=auto-bulk-email-notifications&tab=<?php echo esc_html($tab); ?>"
                            class="nav-tab<?php echo $current_tab === $tab ? " nav-tab-active" : ""; ?>">
                            <?php echo esc_html($name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        </div>

        <div id="aben-body">

            <form action="options.php" method="post">
                <?php
                settings_fields("aben_options");

                // Display only the relevant settings based on the active tab
                if ($current_tab === "general") {
                    echo '<div class = "aben-app__subheading">
        <p>General Settings</p>
        </div>';
                    echo '<div id ="aben-general-settings">';
                    do_settings_sections("aben_section_general_setting");
                    echo "</div>";
                } elseif ($current_tab === "email") {
                    echo '<div class = "aben-app__subheading">
        <p>Template Settings </p>
        <p>Email Preview</p>
        </div>';
                    echo '<div id = "aben-email-tab-grid" style="display:grid; grid-template-columns:4fr 6fr; grid-gap:1rem;">';
                    do_settings_sections("aben_section_email_setting");

                    $site_logo = isset(aben_get_options()["site_logo"]) ? aben_get_options()["site_logo"] : "";
                    $show_view_post = aben_get_options()["show_view_post"];
                    $featured_image = ABEN_FEATURED_IMAGE;

                    $aben_email_dashboard = new Aben_Email(
                        "https://rehan.work/blog/", //archive_page_slug
                        10, //number_of_posts
                        "#f0eeff", //body_bg
                        "Hi Rehan", //header_text
                        "#f0eeff", //header_bg
                        "Check out our daily posts and send your feedback.", //header_subtext
                        "Copyright 2024 | Aben Inc.", //footer_text
                        $site_logo, //site_logo
                        true, //show_view_all
                        "View All Posts", //view_all_posts_text
                        $show_view_post, //show_view_post
                        "Read", //view_post_text
                        true, //show_unsubscribe
                        aben_get_test_posts(),
                    );
                    $aben_email_dashboard->aben_email_template();
                    echo "</div>";
                }
                if ($current_tab !== "unsubscribe") {
                    submit_button();
                }
                ?>
            </form>
        </div>
        <?php if ($current_tab == "email"):
            aben_send_test_email();
        endif; ?>

        <?php if ($current_tab === "email_logs"):
        endif; ?>

        <?php if ($current_tab === "unsubscribe"): ?>

            <div id="aben-unsubscribe-tab">
                <div class="aben-app__subheading">
                    <p>Unsubscribed Users</p>
                </div>
                <div class="unsubscribe-header">
                    <!-- Add to Unsubscribed Form -->
                    <form method="post" action="">
                        <input type="email" name="aben_unsubscribe_email" placeholder="Enter email address" required>
                        <input type="submit" name="aben_add_unsubscribed" class="button action" value="Add to Unsubscribed">
                    </form>
                </div>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="manage-column column-columnname" width="20px;" scope="col">#</th>
                            <th class="manage-column column-columnname" scope="col">Email</th>
                            <th class="manage-column column-columnname" scope="col">Name</th>
                            <th class="manage-column column-columnname" scope="col">Role</th>
                            <th class="manage-column column-columnname" scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query to fetch all users with 'aben_notification' meta set to '0'
                        $args = [
                            "meta_key" => "aben_notification",
                            "meta_value" => "0",
                            "meta_compare" => "=",
                        ];

                        // Get the users based on the query
                        $unsubscribed_users = get_users($args);

                        $serial_number = 1;

                        if (!empty($unsubscribed_users)) {
                            // Loop through each unsubscribed user
                            foreach ($unsubscribed_users as $user) {

                                // Get user roles (WordPress users can have multiple roles)
                                $roles = $user->roles;
                                $role_display = implode(", ", $roles); // Display roles as comma-separated

                                // Generate the URL for subscribing the user again
                                $subscribe_url = add_query_arg(
                                    [
                                        "action" => "aben_subscribe_user",
                                        "user_id" => $user->ID,
                                    ],
                                    admin_url("admin.php"),
                                );
                                ?>
                                <tr>
                                    <td><?php echo esc_html($serial_number); ?></td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo esc_html(ucwords($user->display_name)); ?></td>
                                    <td><?php echo esc_html(ucwords($role_display)); ?></td>
                                    <td>
                                        <form method="post" action="<?php echo esc_url($subscribe_url); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
                                            <input type="submit" class="button action" value="Subscribe Again">
                                        </form>
                                    </td>
                                </tr>
                            <?php $serial_number++;
                            }
                        } else {
                             ?>
                            <tr>
                                <td colspan="4">No unsubscribed users found.</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php
}

//Hide Other Plugin Admin Notices
add_action("admin_head", "aben_hide_other_plugin_notices");
function aben_hide_other_plugin_notices()
{
    $screen = get_current_screen();
    if ($screen->id == "toplevel_page_aben" || $screen->id == "toplevel_page_aben-events") {
        remove_all_actions("admin_notices");
        remove_all_actions("all_admin_notices");
    }
}

//ABEN Register Settings
function aben_register_settings()
{
    register_setting("aben_options", "aben_options", "aben_callback_validate_options");

    register_setting("aben_event_options_group", "aben_event_options", "aben_callback_validate_event_options");

    // General Tab
    add_settings_section("aben_section_general_setting", "", "aben_callback_section_general_setting", "aben_section_general_setting");

    add_settings_field("email_subject", "Email Subject", "aben_callback_field_text", "aben_section_general_setting", "aben_section_general_setting", [
        "id" => "email_subject",
    ]);

    add_settings_field("user_roles", "Send To", "aben_callback_field_select", "aben_section_general_setting", "aben_section_general_setting", [
        "id" => "user_roles",
    ]);

    add_settings_field("post_type", "Post Type", "aben_callback_field_select", "aben_section_general_setting", "aben_section_general_setting", [
        "id" => "post_type",
    ]);

    add_settings_field(
        "email_frequency",
        "Email Delivery",
        "aben_callback_field_select",
        "aben_section_general_setting",
        "aben_section_general_setting",
        ["id" => "email_frequency"],
    );

    add_settings_field("day_of_week", "Schedule Day", "aben_callback_field_select", "aben_section_general_setting", "aben_section_general_setting", [
        "id" => "day_of_week",
    ]);

    add_settings_field("email_time", "Schedule Time", "aben_callback_field_time", "aben_section_general_setting", "aben_section_general_setting", [
        "id" => "email_time",
    ]);

    // SMTP Tab
    add_settings_section("aben_section_smtp_setting", "", "aben_callback_section_smtp_setting", "aben_section_smtp_setting");

    // Email Provider Selection
    add_settings_field("email_provider", "Email Provider", "aben_callback_email_provider", "aben_section_smtp_setting", "aben_section_smtp_setting", [
        "id" => "email_provider",
        "label" => "Choose how you want to send emails",
    ]);

    add_settings_field("smtp_host", "SMTP Host", "aben_callback_field_text", "aben_section_smtp_setting", "aben_section_smtp_setting", [
        "id" => "smtp_host",
        "label" => "e.g., smtp.server.com",
    ]);

    add_settings_field("smtp_port", "SMTP Port", "aben_callback_field_text", "aben_section_smtp_setting", "aben_section_smtp_setting", [
        "id" => "smtp_port",
        "label" => "e.g., 465 for SSL / 587 for TLS",
    ]);

    add_settings_field("smtp_encryption", "Encryption Type", "aben_callback_field_select", "aben_section_smtp_setting", "aben_section_smtp_setting", [
        "id" => "smtp_encryption",
        "label" => "Select encryption type",
    ]);

    add_settings_field("smtp_username", "Username", "aben_callback_field_text", "aben_section_smtp_setting", "aben_section_smtp_setting", [
        "id" => "smtp_username",
        "label" => "e.g., user@website.com",
    ]);

    add_settings_field("smtp_password", "Password", "aben_callback_field_password", "aben_section_smtp_setting", "aben_section_smtp_setting", [
        "id" => "smtp_password",
        "label" => "Your SMTP password",
    ]);

    // ToSend Settings
    add_settings_field("tosend_api_key", "ToSend API Key", "aben_callback_tosend_api_key", "aben_section_smtp_setting", "aben_section_smtp_setting", [
        "id" => "tosend_api_key",
        "label" => "Your ToSend API key (get it from tosend.com)",
    ]);

    add_settings_field(
        "tosend_from_email",
        "ToSend From Email",
        "aben_callback_tosend_from_email",
        "aben_section_smtp_setting",
        "aben_section_smtp_setting",
        ["id" => "tosend_from_email", "label" => "Must be a verified domain in your ToSend account"],
    );

    add_settings_field("from_name", "From Name", "aben_callback_field_text", "aben_section_smtp_setting", "aben_section_smtp_setting", [
        "id" => "from_name",
        "label" => "Sender name for all providers",
    ]);

    add_settings_field("from_email", "Reply to Email", "aben_callback_field_text", "aben_section_smtp_setting", "aben_section_smtp_setting", [
        "id" => "from_email",
        "label" => "e.g., email@website.com",
    ]);

    // Email Template Tab
    add_settings_section("aben_section_email_setting", "", "aben_callback_section_email_setting", "aben_section_email_setting");

    add_settings_field("header_text", "Header Text", "aben_callback_field_text", "aben_section_email_setting", "aben_section_email_setting", [
        "id" => "header_text",
        "label" => 'Use {{USERNAME}} to display user\'s name',
    ]);

    add_settings_field("header_subtext", "Header Subtext", "aben_callback_field_text", "aben_section_email_setting", "aben_section_email_setting", [
        "id" => "header_subtext",
    ]);

    add_settings_field("body_bg", "Body Background Color", "aben_callback_field_color", "aben_section_email_setting", "aben_section_email_setting", [
        "id" => "body_bg",
    ]);

    add_settings_field(
        "header_bg",
        "Post Tile Background Color",
        "aben_callback_field_color",
        "aben_section_email_setting",
        "aben_section_email_setting",
        ["id" => "header_bg"],
    );

    add_settings_field(
        "number_of_posts",
        "Number of Posts",
        "aben_callback_field_select",
        "aben_section_email_setting",
        "aben_section_email_setting",
        ["id" => "number_of_posts", "label" => "Posts are showing for demonstration only"],
    );

    add_settings_field(
        "show_view_post",
        "Show Featured Image",
        "aben_callback_field_checkbox",
        "aben_section_email_setting",
        "aben_section_email_setting",
        ["id" => "show_view_post", "label" => "Yes"],
    );

    add_settings_field("show_view_all", "Show Button", "aben_callback_field_checkbox", "aben_section_email_setting", "aben_section_email_setting", [
        "id" => "show_view_all",
        "label" => "Yes",
    ]);

    add_settings_field("view_all_posts_text", "Button Text", "aben_callback_field_text", "aben_section_email_setting", "aben_section_email_setting", [
        "id" => "view_all_posts_text",
    ]);

    add_settings_field("archive_page_slug", "Button Link", "aben_callback_field_text", "aben_section_email_setting", "aben_section_email_setting", [
        "id" => "archive_page_slug",
        "label" => "e.g., https://website.com/blogs",
    ]);

    add_settings_field("site_logo", "Footer Logo", "aben_callback_field_media", "aben_section_email_setting", "aben_section_email_setting", [
        "id" => "site_logo",
    ]);

    add_settings_field("footer_text", "Footer Text", "aben_callback_field_text", "aben_section_email_setting", "aben_section_email_setting", [
        "id" => "footer_text",
    ]);

    add_settings_field(
        "show_unsubscribe",
        "Show Unsubscribe",
        "aben_callback_field_checkbox",
        "aben_section_email_setting",
        "aben_section_email_setting",
        ["id" => "show_unsubscribe", "label" => "Yes"],
    );
    if (!Aben_Email::is_pro()) {
        add_settings_field(
            "remove_branding",
            '<a id ="aben_remove_branding" href="/wp-admin/admin.php?page=aben-license">Remove Branding "Powered by Aben"</a>',
            "aben_callback_remove_branding",
            "aben_section_email_setting",
            "aben_section_email_setting",
            ["id" => "remove_branding"],
        );
    }
    add_settings_section("aben_section_email_template", "", "aben_callback_section_email_template", "aben_section_email_template");

    add_settings_field("email_body", "", "aben_callback_field_textarea", "aben_section_email_template", "aben_section_email_template", [
        "id" => "email_body",
        "label" => "Email body (Text/Markup)",
    ]);

    //License Tab
    add_settings_section("aben_section_license_setting", "", "__return_true", "aben_section_license_setting");

    add_option("aben_event_options", [
        "role" => "subscriber",
        "email_subject" => "Send customized email to anyone instantly - Aben Events",
        "template" => [
            "body_bg" => "#f0f0f0",
            "header_text" => "Hi {{USERNAME}},",
            "header_subtext" => "We have some exciting news for you.",
            "header_bg" => "#5ea6de",
            "content" => "<h2>Don't Miss Out on This Exclusive Web</h2>
Are you ready to take your WordPress skills to the next level? Our expert team is hosting an exclusive webinar focused on best practices, advanced techniques, and tips for WordPress development!

During this live session, you will learn:
<ul>
 	<li>How to set up and optimize WordPress for high performance</li>
 	<li>Essential plugins and tools for WordPress developers</li>
 	<li>How to implement advanced customizations and themes</li>
 	<li>Best practices for securing and maintaining your WordPress sites</li>
</ul>
Don't miss out! Click the button below to register now:",
            "content_bg" => "#ffffff",
            "button_text" => "Register Now",
            "button_text_color" => "#000000",
            "button_bg" => "#f0eeff",
            "button_url" => "https://rehan.work",
            "show_button" => true,
            "site_logo" => "",
            "footer_text" => "Copyright 2024 | Aben Inc.",
            "footer_bg" => "#f5f5f5",
        ],
    ]);
}

// Hook to sanitize_option to add timezone automatically
add_filter("pre_update_option_aben_options", "aben_save_timezone_option", 10, 2);

function aben_save_timezone_option($new_value, $old_value)
{
    // Automatically get the site's timezone
    $timezone = wp_timezone_string(); // e.g., 'America/New_York'

    // Add the timezone to the settings array
    $new_value["timezone"] = $timezone;

    return $new_value;
}

function aben_send_test_email()
{
    ?>
    <!-- Display success or error message if available -->
    <?php if (isset($_GET["test_email_sent"])): ?>
        <div id="aben-notice--<?php echo $_GET["test_email_sent"] === "success" ? "success" : "error"; ?>"
            class="notice notice-<?php echo $_GET["test_email_sent"] === "success" ? "success" : "error"; ?> is-dismissible">
            <p><?php echo $_GET["test_email_sent"] === "success"
                ? "Test email sent successfully."
                : "SMTP connection failed. Please check your credentials and try again"; ?>
            </p>
        </div>
    <?php endif; ?>
    <!-- Test Email Form -->
    <form action="<?php echo esc_url(admin_url("admin-post.php")); ?>" method="post" id="aben-test-form">
        <p style="float: right;">
            <input type="email" id="test_email_address" placeholder="Enter Email Address" name="test_email_address"
                class="regular-text" required />
            <input type="hidden" name="action" value="aben_send_test_email" />
            <input type="submit" class="button button-primary" value="Send Test Email" />
        </p>
        <?php wp_nonce_field("aben_send_test_email", "aben_test_email_nonce"); ?>
    </form>

    <?php
}

// Dashboard Page
function aben_display_dashboard_page()
{
    if (!class_exists("\ABEN_PRO\Pro_Loader")) { ?>
        <div class="wrap aben-pro-page">
            <div class="aben-analytics-overlay" style="background-image:url(<?php echo esc_url(
                ABEN_PLUGIN_URL . "/assets/images/aben-analytics.webp",
            ); ?>)"></div>
            <div class="aben-pro-card">
                <div class="header">
                    <img src="<?php echo esc_url(ABEN_PLUGIN_LOGO); ?>" alt="">
                </div>
                <div class="body">
                    <h1>Aben Pro Features</h1>
                    <ul>
                        <li><strong>Email Analytics:</strong> Track number of emails sent, opens, clicks & CTR.</li>
                        <li><strong>Subscriber Insights:</strong> Subscribe/Unsubscribe rates.</li>
                        <li><strong>Device Analytics:</strong> Mobile, Desktop & Tablet performance.</li>
                        <li><strong>Email Logs:</strong> View detailed log of every email sent.</li>
                        <li><strong>White-Labeling:</strong> Remove Aben branding from email footer.</li>
                    </ul>
                </div>
                <div class="footer">
                    <a href="https://abenplugin.com" class="button button-primary" target="_blank">Buy Aben Pro ($7.00) &#10138;</a>
                </div>
            </div>
        </div>
    <?php } elseif (class_exists("\ABEN_PRO\Pro_Loader") && "inactive" === Aben_Admin::is_license_active()) { ?>
        <div class="wrap aben-pro-page">
            <div class="aben-analytics-overlay" style="background-image:url(<?php echo esc_url(
                ABEN_PLUGIN_URL . "/assets/images/aben-analytics.webp",
            ); ?>)"></div>
            <div class="aben-pro-card">
                <div class="header">
                    <img src="<?php echo esc_url(ABEN_PLUGIN_LOGO); ?>" alt="">
                    <!-- <h1>License not activated</h1> -->
                </div>
                <div class="body">
                    <h3>Please activate your license.
                        <a href="/wp-admin/admin.php?page=aben-license">Go to license page</a>
                    </h3>
                </div>
                <!-- <div class="footer">
                    <a href="" class="button button-primary" target="_blank">Go to License page &#10138;</a>
                </div> -->
            </div>
        </div>
    <?php } else {do_action("aben_pro_analytics");}
}

// Events Page
function aben_display_events_page()
{
    //No admin notices on this page
    if (!current_user_can("manage_options")) {
        return;
    }
    $tabs = [
        "general" => "General",
        "template" => "Template",
    ];

    $current_tab = "general";
    if (isset($_GET["tab"])) {
        $tab = sanitize_text_field(wp_unslash($_GET["tab"]));
        if (isset($tabs[$tab])) {
            $current_tab = $tab;
        }
    }
    $aben_events = Aben_Events::get_instance();
    ?>
    <div id="aben-app">
        <?php if (isset($_GET["message"])):
            $message = sanitize_text_field(wp_unslash($_GET["message"])); ?>
            <div id="aben-notice--<?php echo esc_html($message === "success" || $message === "test-success" ? "success" : "error"); ?>"
                class="notice notice-<?php esc_html($message === "success" ? "success" : "error"); ?> is-dismissible">
                <p><?php switch ($message) {
                    case "success":
                        echo esc_html("Emails sending started");
                        break;
                    case "test-success":
                        echo esc_html("Test mail sent");
                        break;
                    case "error":
                        echo esc_html("SMTP connection failed. Please check your credentials and try again");
                        break;
                } ?></p>
            </div>
        <?php
        endif; ?>
        <div id="aben-header">

            <div id="aben-logo"><img src="<?php echo esc_url(ABEN_PLUGIN_LOGO); ?>" alt="Aben plugin logo" /></div>

            <nav class="nav-tab-wrapper" id="aben-nav">
                <div id="aben-nav-menu" class="aben-nav-menu--events">
                    <?php foreach ($tabs as $tab => $name) { ?>
                        <a href="?page=aben-events&tab=<?php echo esc_html($tab); ?>"
                            class="nav-tab<?php echo $current_tab === $tab ? " nav-tab-active" : ""; ?>">
                            <?php echo esc_html($name); ?>
                        </a>
                    <?php } ?>
                    <form method="post" action="<?php echo esc_attr(admin_url("admin-post.php")); ?>"
                        id="aben_events_email_btn">
                        <?php wp_nonce_field("aben_send_event_emails_action", "aben_send_event_emails_nonce"); ?>
                        <input type="hidden" name="action" value="aben_send_event_emails_action">
                        <input type="submit" class="button button-primary" value="Send Emails Now" />
                    </form>
                    <?php echo $current_tab === "template" ? esc_html($aben_events->test_email_form()) : ""; ?>
                </div>
            </nav>
        </div>
        <div id="aben-body">
            <?php switch ($current_tab) {
                case "general": //General Settings
                    echo '<div class = "aben-app__subheading">
                    <p>General Settings</p>
                    </div>';
                    echo '<div id ="aben-general-settings">';
                    $aben_events->display_email_form();
                    echo "</div></div>";
                    break;

                case "template": //Template Settings
                    echo '<div class = "aben-app__subheading">
                    <p>Settings</p>
                    <p>Preview</p>
                    </div>';
                    echo '<div id="aben-email-tab-grid" style="display:grid; grid-template-columns:5fr 5fr; grid-gap:1rem; align-items: start;">';
                    $aben_events->display_template_settings_form();
                    echo '<div id="aben-events-template-preview">';
                    $aben_events->display_template();
                    echo "</div></div>";
                    break;
            } ?>
        </div>
    </div>
<?php
}
//SMTP

function aben_display_smtp_page()
{
    if (isset($_GET["settings-updated"])) {
        echo '<div id="aben-notice" class="notice notice-success is-dismissible"><p>Saved.</p></div>';
    } ?>
    <div id="aben-app">
        <div id="aben-header">
            <div id="aben-logo"><img src="<?php echo esc_url(ABEN_PLUGIN_LOGO); ?>" alt="Aben plugin logo" /></div>
            <nav class="nav-tab-wrapper" id="aben-nav">
                <div id="aben-nav-menu" class="aben-nav-menu--events">
                    <a href="?page=aben-license" class="nav-tab nav-tab-active">SMTP</a>
                </div>
                <?php aben_display_smtp_connection_btn(); ?>
            </nav>
        </div>
        <div id="aben-body">
            <div class="aben-app__subheading">
                <p>Configure SMTP Settings</p>
            </div>
            <form action="options.php" method="post">
                <?php settings_fields("aben_options"); ?>
                <div id="aben-smtp-settings">
                    <?php do_settings_sections("aben_section_smtp_setting"); ?>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
<?php
}
//Logs
function aben_display_email_logs_page()
{
    $logger = new Aben_Email_Logs();
    $per_page = 100;
    $current_page = isset($_GET["paged"]) ? absint($_GET["paged"]) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Fetch logs with limit and offset
    $logs = $logger->get_logs($per_page, $offset);
    $total_logs = $logger->get_logs_count();

    if (!empty($logs)) {
        echo '<table class="widefat fixed aben-email-logs">';
        echo "<thead><tr>";
        echo "<th width = 5%>#</th><th width = 30%>Subject</th><th width = 30%>To</th><th width = 10%>Status</th><th width = 20%>Date/Time</th>";
        echo "</tr></thead><tbody>";

        $count = $offset + 1; // Start count based on the offset
        foreach ($logs as $log) {
            $date = new DateTime($log->sent_at);
            $sent_at = $date->format("j F Y / H:i A");

            echo "<tr>";
            echo "<td>" . esc_html($count++) . "</td>";
            echo "<td>" . esc_html($log->subject) . "</td>";
            echo "<td>" . esc_html($log->email_to) . "</td>";
            echo "<td>" . esc_html($log->status) . "</td>";
            echo "<td>" . esc_html($sent_at) . "</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";

        // Add pagination
        $pagination_args = [
            "base" => add_query_arg("paged", "%#%"),
            "format" => "",
            "current" => max(1, $current_page),
            "total" => ceil($total_logs / $per_page),
            "prev_text" => "&laquo; Previous",
            "next_text" => "Next &raquo;",
        ];

        echo '<div class="pagination-wrap">';
        echo $count > $per_page ? wp_kses_post(paginate_links($pagination_args)) : "";
        echo "</div>";
    } else {
        echo '<table class="widefat fixed aben-email-logs">';
        echo "<thead><tr>";
        echo "<th width = 5%>#</th><th width = 30%>Subject</th><th width = 30%>To</th><th width = 10%>Status</th><th width = 20%>Date/Time</th>";
        echo "</tr></thead><tbody>";
        echo '<tr><td colspan="5">No email logs found.</td></tr>';
        echo "</tbody></table>";
    }?>
<?php
}
