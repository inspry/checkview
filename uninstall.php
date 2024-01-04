<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    Checkview
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
global $wpdb;
$checkview_options = get_option( 'checkview_advance_options', array() );
$delete_all        = ! empty( $checkview_options['checkview_delete_data'] ) ? $checkview_options['checkview_delete_data'] : '';
if ( $delete_all ) {
	delete_option( 'checkview_advance_options' );
	delete_option( 'checkview_log_options' );
	delete_site_option( 'checkview_admin_menu_title' );
	// remove check view entry and entry meta tables.
	$cv_entry_table = $wpdb->prefix . 'cv_entry';
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %s', $cv_entry_table ) );
	$cv_entry_meta_table = $wpdb->prefix . 'cv_entry_meta';
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %s', $cv_entry_meta_table ) );
	$cv_session_table = $wpdb->prefix . 'cv_session';
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %s', $cv_session_table ) );


	// remove all check view options.
	$options_table = $wpdb->prefix . 'options';
	$wpdb->query( $wpdb->prepare( 'Delete from %s where option_name like %s', $options_table, '%CF_TEST_%' ) );
}
