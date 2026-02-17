<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://rehan.work
 * @since      2.3.0
 *
 * @package    Aben
 * @subpackage Aben/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Aben
 * @subpackage Aben/admin
 * @author     Rehan Khan <hello@rehan.work>
 */

class Aben_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    2.3.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    2.3.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.3.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    2.3.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Aben_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Aben_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . "css/aben-admin.css", [], $this->version, "all");
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    2.3.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Aben_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Aben_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_media();
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . "js/aben-admin.js", ["jquery"], $this->version, true);

        $current_user = ucfirst(wp_get_current_user()->display_name);
        wp_localize_script($this->plugin_name, "currentUserData", [
            "user_name" => $current_user,
        ]);
    }

    public static function is_license_active()
    {
        return get_option("aben_license_status");
    }
}
