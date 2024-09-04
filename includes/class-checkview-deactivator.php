<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    Checkview
 * @subpackage Checkview/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Checkview
 * @subpackage Checkview/includes
 * @author     Check View <support@checkview.io>
 */
class Checkview_Deactivator {

	/**
	 * Clear the cron job and remove the IP from the whitelist.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear the cron job.
		wp_clear_scheduled_hook( 'checkview_delete_orders_action' );
		$ip_to_remove = checkview_get_api_ip();
		if ( substr( $ip_to_remove, 0, $settings['whitelisted_ips'] ) !== false ) {
			// Replace the IP with an empty string (effectively removing it). Remove SaaSIP from hCaptcha settings.
			$settings['whitelisted_ips'] = str_replace( $ip_to_remove, '', $settings['whitelisted_ips'] );

			// Clean up any leftover newline characters.
			$settings['whitelisted_ips'] = trim( preg_replace( '/\s+/', "\n", $settings['whitelisted_ips'] ) );
			// Update the option with the IP removed.
			update_option( 'hcaptcha_settings', $settings );
		}
		$timestamp = wp_next_scheduled( 'checkview_nonce_cleanup_cron' );
		wp_unschedule_event( $timestamp, 'checkview_nonce_cleanup_cron' );
	}
}
