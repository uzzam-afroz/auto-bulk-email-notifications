<?php
/**
 * Email Provider Factory
 *
 * Factory class to create appropriate email provider instances
 *
 * @link       https://rehan.work
 * @since      2.3.0
 * @package    Aben
 * @subpackage Aben/admin/partials/providers
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aben_Provider_Factory
{
    /**
     * Create email provider instance based on settings
     *
     * @param array|null $settings Plugin settings (uses aben_get_options() if null)
     * @param Aben_Email_Logs|null $logger Logger instance
     * @return Aben_Email_Provider|null Provider instance or null on error
     */
    public static function create($settings = null, $logger = null)
    {
        if ($settings === null) {
            $settings = aben_get_options();
        }

        if (!is_array($settings)) {
            error_log('ABEN Provider Factory: Invalid settings provided');
            return null;
        }

        $provider_type = isset($settings['email_provider']) ? $settings['email_provider'] : 'smtp';

        switch ($provider_type) {
            case 'tosend':
                return new Aben_ToSend_Provider($settings, $logger);

            case 'smtp':
            default:
                return new Aben_SMTP_Provider($settings, $logger);
        }
    }

    /**
     * Get list of available providers
     *
     * @return array Array of provider_key => provider_name
     */
    public static function get_available_providers()
    {
        $providers = [
            'smtp'   => __('Custom SMTP', 'auto-bulk-email-notifications'),
            'tosend' => __('ToSend API', 'auto-bulk-email-notifications'),
        ];

        /**
         * Filter available email providers
         *
         * Allows adding custom email providers
         *
         * @param array $providers Array of provider_key => provider_name
         */
        return apply_filters('aben_email_providers', $providers);
    }

    /**
     * Check if a provider is configured
     *
     * @param string $provider_type Provider type (smtp, tosend, etc.)
     * @param array|null $settings Plugin settings
     * @return bool True if configured
     */
    public static function is_provider_configured($provider_type, $settings = null)
    {
        if ($settings === null) {
            $settings = aben_get_options();
        }

        if (!is_array($settings)) {
            return false;
        }

        $temp_settings = array_merge($settings, ['email_provider' => $provider_type]);
        $provider = self::create($temp_settings);

        if (!$provider) {
            return false;
        }

        return $provider->is_configured();
    }

    /**
     * Get current provider instance
     *
     * @return Aben_Email_Provider|null Current provider or null
     */
    public static function get_current_provider()
    {
        return self::create();
    }

    /**
     * Get provider display name
     *
     * @param string $provider_type Provider type
     * @return string Provider display name
     */
    public static function get_provider_name($provider_type)
    {
        $providers = self::get_available_providers();
        return isset($providers[$provider_type]) ? $providers[$provider_type] : $provider_type;
    }
}
