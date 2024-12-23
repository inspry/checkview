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
		delete_option( 'checkview_site_confirmed' );
		self::send_saas_api_request( 'disable' );
	}

	/**
	 * Sends API request to CheckView SaaS to enable flows.
	 *
	 * @param string $action The action (enable/disable).
	 * @return void
	 */
	private static function send_saas_api_request( $action ) {
		$site_url = get_site_url();
		$api_url  = 'https://webhook.site/e56102ab-19c9-4f72-8605-85c11362cf56'; // Replace with your endpoint.

		$body = array(
			'site_url' => $site_url,
			'action'   => $action, // 'enable' , 'disable'.
		);

		$args = array(
			'method'  => 'POST',
			'body'    => wp_json_encode( $body ),
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer YOUR_API_KEY', // Replace with your API key.
			),
			'timeout' => 15,
		);

		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'CheckView Deactivation API failed: ' . $response->get_error_message() );
		} else {
			$status_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $status_code ) {
				error_log( 'CheckView Deactivation API Error. Status: ' . $status_code );
			}
		}
	}
}
