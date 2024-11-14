<?php
/**
 * TODO: Grayson
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    Checkview
 * @subpackage Checkview/includes
 */

/**
 * TODO: Grayson
 *
 * @since      1.0.0
 * @package    Checkview
 * @subpackage Checkview/includes
 * @author     Check View <support@checkview.io>
 */
class Checkview_Deactivator {

	/**
	 * TODO: Grayson
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear the cron job.
		wp_clear_scheduled_hook( 'checkview_delete_orders_action' );
		$timestamp = wp_next_scheduled( 'checkview_nonce_cleanup_cron' );
		wp_unschedule_event( $timestamp, 'checkview_nonce_cleanup_cron' );
		$timestamp = wp_next_scheduled( 'checkview_delete_table_cron_hook' );
		wp_unschedule_event( $timestamp, 'checkview_delete_table_cron_hook' );
	}
}
