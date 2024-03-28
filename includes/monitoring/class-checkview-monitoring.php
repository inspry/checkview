<?php
/**
 * Hanldes Checkview Monitoring options.
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    CheckView
 * @subpackage CheckView/includes/monitoring
 */


	/**
	 * Fired to inject custom payment gateway to WooCommerce.
	 *
	 * This class defines all code necessary to run for handling CheckView WooCommerce Operations.
	 *
	 * @since      1.0.0
	 * @package    CheckView
	 * @subpackage CheckView/includes/woocommercehelper
	 * @author     CheckView <checkview> https://checkview.io/
	 */
class Checkview_Monitoring {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Checkview_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;
	/**
	 * Class constructor.
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;
		$this->loader->add_action(
			'init',
			$this,
			'checkview_track_versions_and_send_api_request',
			10,
		);
	}
	/**
	 * Tracks version changes and sends to SaaS.
	 *
	 * @return void
	 */
	public function checkview_track_versions_and_send_api_request() {
		// Retrieve previous version data.
		$previous_version_data = get_option( 'checkview_version_tracker_data' );

		// Retrieve current versions.
		global $wp_version;
		$current_wordpress_version = $wp_version;
		$current_php_version       = phpversion();
		global $wpdb;
		$current_mysql_version = $wpdb->db_version();

		// Check if there's a change in any version.
		$version_changed = false;
		$changes         = array();

		if ( ! empty( $previous_version_data ) ) {
			if ( $current_wordpress_version !== $previous_version_data['wordpress_version'] ) {
				$version_changed = true;
				$changes[]       = "WordPress version changed from {$previous_version_data['wordpress_version']} to {$current_wordpress_version}";
			}
			if ( $current_php_version !== $previous_version_data['php_version'] ) {
				$version_changed = true;
				$changes[]       = "PHP version changed from {$previous_version_data['php_version']} to {$current_php_version}";
			}
			if ( $current_mysql_version !== $previous_version_data['mysql_version'] ) {
				$version_changed = true;
				$changes[]       = "MySQL version changed from {$previous_version_data['mysql_version']} to {$current_mysql_version}";
			}
		}

		// If version changed, send API request and log changes.
		if ( $version_changed ) {
			// Prepare data for API request.
			$data = array(
				'changes' => $changes,
				'date'    => gmdate( 'Y-m-d H:i:s' ),
			);

			// Send API request.
			$api_endpoint = 'https://checkview.io/version_changes';
			$response     = wp_remote_post(
				$api_endpoint,
				array(
					'body'    => wp_json_encode( $data ),
					'headers' => array(
						'Content-Type' => 'application/json',
					),
				)
			);

			// Log changes.
			$log_entry = 'Date: ' . gmdate( 'Y-m-d H:i:s' ) . "\n";
			foreach ( $changes as $change ) {
				$log_entry .= "- {$change}\n";
			}
			$log_entry    .= "\n";
			$log_dir       = wp_upload_dir()['basedir'] . '/checkview';
			$wp_filesystem = WP_Filesystem();
			if ( $wp_filesystem ) {
				$wp_filesystem->mkdir( $log_dir );
				$log_file = $log_dir . '/checkview_log_' . gmdate( 'Ymd' ) . '.log';
				$wp_filesystem->put_contents( $log_file, $log_entry, FILE_APPEND );
			}
		}

		// Update option with current version data.
		update_option(
			'checkview_version_tracker_data',
			array(
				'wordpress_version' => $current_wordpress_version,
				'php_version'       => $current_php_version,
				'mysql_version'     => $current_mysql_version,
			)
		);
	}
}
