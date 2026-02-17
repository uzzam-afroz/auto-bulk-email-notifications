<?php

/**
 * Fired during plugin activation
 *
 * @link       https://rehan.work
 * @since      2.3.0
 *
 * @package    Aben
 * @subpackage Aben/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.3.0
 * @package    Aben
 * @subpackage Aben/includes
 * @author     Rehan Khan <hello@rehan.work>
 */
class Aben_Activator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    2.3.0
     */
    public static function activate()
    {
        $default_settings = aben_options_default();
        $default_settings["aben_key"] = aben_generate_encryption_key();
        add_option("aben_options", $default_settings);

        // $wp_config_path = ABSPATH . 'wp-config.php';

        // if (file_exists($wp_config_path)) {
        //     $config_content = file_get_contents($wp_config_path);

        //     // Check if ALTERNATE_WP_CRON is already defined
        //     if (strpos($config_content, 'ALTERNATE_WP_CRON') === false) {
        //         $new_constant = "\ndefine('ALTERNATE_WP_CRON', true);";

        //         // Insert before "That's all, stop editing! Happy publishing."
        //         if (strpos($config_content, "/* That's all, stop editing!") !== false) {
        //             $config_content = str_replace("/* That's all, stop editing!", "$new_constant\n/* That's all, stop editing!", $config_content);
        //             file_put_contents($wp_config_path, $config_content);
        //         }
        //     }
        // }
    }
}
