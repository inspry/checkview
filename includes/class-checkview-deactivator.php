<?php
/**
 * Checkview_Deactivator class
 *
 * @since 1.0.0
 *
 * @package Checkview
 * @subpackage Checkview/includes
 */

/**
 * Handles plugin deactivation.
 *
 * @since 1.0.0
 * @package Checkview
 * @subpackage Checkview/includes
 * @author Check View <support@checkview.io>
 */
class Checkview_Deactivator {

	/**
	 * Deactivation sequence.
	 *
	 * Clears scheduled jobs and events.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Clear the cron job.
		wp_clear_scheduled_hook( 'checkview_delete_orders_action' );
		$timestamp = wp_next_scheduled( 'checkview_nonce_cleanup_cron' );
		wp_unschedule_event( $timestamp, 'checkview_nonce_cleanup_cron' );
		$timestamp = wp_next_scheduled( 'checkview_delete_table_cron_hook' );
		wp_unschedule_event( $timestamp, 'checkview_delete_table_cron_hook' );
		$timestamp = wp_next_scheduled( 'checkview_options_cleanup_cron' );
		wp_unschedule_event( $timestamp, 'checkview_options_cleanup_cron' );
		self::checkview_notify_saas( 'deactivated' );
	}

	/**
	 * Notifies SaaS of activation.
	 *
	 * @param string $status The status to notify the SaaS of.
	 */
	private static function checkview_notify_saas( $status ) {
		$api_key  = 'your_api_key_here'; // Replace with your actual API key
		$endpoint = 'https://example.com/xyz/helper-status'; // Replace with your SaaS endpoint

		$response = wp_remote_post(
			$endpoint,
			array(
				'body'    => json_encode( array( 'status' => $status ) ),
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Checkview: Failed to notify SaaS. ' . $response->get_error_message() );
		} else {
			error_log( 'Checkview: SaaS notified of ' . $status . ' status.' );
		}
	}
}
