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
	 *
	 * @param Class $loader loader class object.
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;
		$this->loader->add_action(
			'init',
			$this,
			'checkview_track_versions_and_send_api_request',
			10,
		);

		$this->loader->add_action(
			'user_register',
			$this,
			'checkview_send_alert_new_admin_user',
			10,
			1,
		);

		$this->loader->add_action(
			'upgrader_process_complete',
			$this,
			'checkview_track_updates_notification',
			10,
			2,
		);

		$this->loader->add_filter(
			'file_mod_allowed',
			$this,
			'checkview_check_wp_config_changes',
			10,
			2
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
		$current_php_version = phpversion();
		global $wpdb;
		$current_mysql_version = $wpdb->db_version();

		// Check if there's a change in any version.
		$version_changed = false;
		$changes         = array();

		if ( ! empty( $previous_version_data ) ) {
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
				'php_version'   => $current_php_version,
				'mysql_version' => $current_mysql_version,
			)
		);
	}

	/**
	 * Checks if a new admin user is created.
	 *
	 * @param int $user_id wp user id.
	 * @return void
	 */
	public function send_alert_new_admin_user( $user_id ) {
		$user = get_userdata( $user_id );

		// Send alert to SaaS via API.
		// Check if the user has the 'administrator' role.
		if ( in_array( 'administrator', $user->roles ) ) {
			// Send alert to SaaS via API.
			wp_remote_post(
				'https://example.com/api/new_admin_user_alert',
				array(
					'body'    => wp_json_encode(
						array(
							'user_id'    => $user_id,
							'user_email' => $user->user_email,
							'time'       => current_time( 'mysql' ),
						)
					),
					'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				)
			);
		}
	}

	/**
	 * Tracks core version updates.
	 *
	 * @param [object] $upgrader_object class upgrader.
	 * @param [array]  $options array.
	 * @return void
	 */
	public function checkview_track_updates_notification( $upgrader_object, $options ) {

		$current_time    = current_time( 'mysql' );
		$update_required = false;

		// Check for updates only.
		if ( 'update' === $options['action'] ) {
			switch ( $options['type'] ) {
				case 'plugin':
					$plugin_info      = $upgrader_object->plugin_info(); // Get updated plugin info.
					$previous_version = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_info['destination_name'] )['Version'];
					if ( $previous_version !== $plugin_info['Version'] ) {
						$update_required = true;
					}
					break;
				case 'theme':
					$previous_version = wp_get_theme( $options['theme'] )->get( 'Version' );
					if ( $previous_version !== $options['version'] ) {
						$update_required = true;
					}
					break;
				case 'core':
					$previous_version = get_bloginfo( 'version' );
					if ( $previous_version !== $options['version'] ) {
						$update_required = true;
					}
					break;
			}

			// If update occurred, send alert to SaaS via API.
			if ( $update_required ) {
				wp_remote_post(
					'https://checkview.io/version_changes',
					array(
						'body'    => wp_json_encode(
							array(
								'type'    => $options['type'],
								'action'  => $options['action'],
								'name'    => isset( $plugin_info ) ? $plugin_info['plugin'] : ( isset( $options['theme'] ) ? $options['theme'] : 'WordPress' ),
								'version' => isset( $plugin_info ) ? $plugin_info['Version'] : $options['version'],
								'time'    => $current_time,
							)
						),
						'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
					)
				);
			}
		}
	}

	/**
	 * Check for WP-Config files updates.
	 *
	 * @param bool   $allowed allowed or not.
	 * @param string $file_path file path,
	 * @return bool$
	 */
	public function checkview_check_wp_config_changes( $allowed, $file_path ) {
		if ( strpos( $file_path, 'wp-config.php' ) !== false ) {
			// Send alert to SaaS via API.
			wp_remote_post(
				'https://example.com/api/wp_config_change_alert',
				array(
					'body'    => json_encode(
						array(
							'time' => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ), // Convert timestamp to a readable date-time format.
						)
					),
					'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				)
			);
		}

		return $allowed;
	}
}
