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

use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\{ File, FileController, FileListTable, SearchListTable };
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
			'init',
			$this,
			'checkview_check_wp_config_changes',
			10,
		);

		$this->loader->add_action(
			'init',
			$this,
			'checkview_report_wc_logger_fatal',
			10,
		);
		add_filter(
			'option_woocommerce_stripe_settings',
			function ( $value ) {

				$value['logging'] = 'yes';

				return $value;
			}
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
					$plugin_info      = $upgrader_object->plugin_info();
					$plugin_info      = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_info );
					$previous_version = $plugin_info['Version'];
					if ( $previous_version !== $options['version'] ) {
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

				$options = array(
					'type'    => $options['type'],
					'action'  => $options['action'],
					'name'    => isset( $plugin_info ) ? $plugin_info['Name'] : ( isset( $options['theme'] ) ? $options['theme'] : 'WordPress' ),
					'version' => isset( $plugin_info ) ? $plugin_info['Version'] : $options['version'],
					'time'    => $current_time,
				);

						// wp_remote_post(
						// 'https://checkview.io/version_changes',
						// array(
						// 'body'    => wp_json_encode(
						// array(
						// 'type'    => $options['type'],
						// 'action'  => $options['action'],
						// 'name'    => isset( $plugin_info ) ? $plugin_info['plugin'] : ( isset( $options['theme'] ) ? $options['theme'] : 'WordPress' ),
						// 'version' => isset( $plugin_info ) ? $plugin_info['Version'] : $options['version'],
						// 'time'    => $current_time,
						// )
						// ),
						// 'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
						// )
						// );.
			}
		}
	}
	/**
	 * Check for WP-Config files updates.
	 *
	 * @return void
	 */
	public function checkview_check_wp_config_changes() {
		// Define the path to the wp-config.php file.
		$wp_config_file = ABSPATH . 'wp-config.php';

		// Get the last modified time of the wp-config.php file.
		$last_modified_time = filemtime( $wp_config_file );

		// Check if the file was modified within the last X minutes (adjust X as needed).
		$minutes_threshold = 10; // Change this according to your requirement.
		$current_time      = time();
		$threshold_time    = $current_time - ( $minutes_threshold * 60 );

		if ( $last_modified_time > $threshold_time ) {
			// The wp-config.php file was modified within the last X minutes
			// Perform actions such as logging, sending notifications, etc.
			//send to saas//
		}
	}

	/**
	 * Formats the line for SaaS.
	 *
	 * @param string  $line line .
	 * @param integer $line_number line number.
	 * @return string
	 */
	private function format_line( string $line, int $line_number ): string {
		$classes = array( 'line' );

		$line = esc_html( $line );
		if ( empty( $line ) ) {
			$line = '&nbsp;';
		}

		$segments      = explode( ' ', $line, 3 );
		$has_timestamp = false;
		$has_level     = false;

		if ( isset( $segments[0] ) && false !== strtotime( $segments[0] ) ) {
			$classes[]     = 'log-entry';
			$segments[0]   = sprintf(
				'<span class="log-timestamp">%s</span>',
				$segments[0]
			);
			$has_timestamp = true;
		}

		if ( isset( $segments[1] ) && WC_Log_Levels::is_valid_level( strtolower( $segments[1] ) ) ) {
			$segments[1] = sprintf(
				'<span class="%1$s">%2$s</span>',
				esc_attr( 'log-level log-level--' . strtolower( $segments[1] ) ),
				esc_html( WC_Log_Levels::get_level_label( strtolower( $segments[1] ) ) )
			);
			$has_level   = true;
		}

		if ( isset( $segments[2] ) && $has_timestamp && $has_level ) {
			$message_chunks = explode( 'CONTEXT:', $segments[2], 2 );
			if ( isset( $message_chunks[1] ) ) {
				try {
					$maybe_json = stripslashes( html_entity_decode( trim( $message_chunks[1] ) ) );
					$context    = json_decode( $maybe_json, false, 512, JSON_THROW_ON_ERROR );

					$message_chunks[1] = sprintf(
						'<details><summary>%1$s</summary>%2$s</details>',
						esc_html__( 'Additional context', 'woocommerce' ),
						wp_json_encode( $context, JSON_PRETTY_PRINT )
					);

					$segments[2] = implode( ' ', $message_chunks );
					$classes[]   = 'has-context';
				} catch ( \JsonException $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// It's not valid JSON so don't do anything with it.
				}
			}
		}

		if ( count( $segments ) > 1 ) {
			$line = implode( ' ', $segments );
		}

		$classes = implode( ' ', $classes );

		return sprintf(
			'<span id="L%1$d" class="%2$s">%3$s%4$s</span>',
			absint( $line_number ),
			esc_attr( $classes ),
			sprintf(
				'<a href="#L%1$d" class="line-anchor"></a>',
				absint( $line_number )
			),
			sprintf(
				'<span class="line-content">%s</span>',
				wp_kses_post( $line )
			)
		);
	}

	/**
	 * Reports Wc_logger latest errors.
	 *
	 * @param string $log_type log type.
	 * @return void
	 */
	private function checkview_report_wc_logger( $log_type = 'fatal-errors' ) {
		$is_enabled = get_option( 'woocommerce_logs_logging_enabled', '' );
		if ( empty( $is_enabled ) || 'yes' !== $is_enabled || null === $is_enabled ) {
			update_option( 'woocommerce_logs_logging_enabled', 'yes', true );
		}
		$file_controller = new FileController();
		$timestamp       = get_option( 'checkview_last_checked_time', '' );
		if ( empty( $timestamp ) || '' === $timestamp ) {
			$timestamp = strtotime( '-1 day' );
		}
		$current_timestamp = strtotime( current_time( 'mysql' ) );
		update_option( 'checkview_last_checked_time', $current_timestamp, true );
		$file_args = array(
			'order'          => 'desc',
			'posts_per_page' => 1,
			'date_start'     => $timestamp,
			'date_end'       => $current_timestamp,
			'date_filter'    => 'modified',
		);
		$args      = array(
			'per_page' => 20,
		);
		$results   = $file_controller->search_within_files( 'Critical', $args, $file_args );
		$logs      = array_merge( $results, $file_controller->search_within_files( 'Emergency', $args, $file_args ) );

		if ( ! is_array( $logs ) || empty( $logs ) ) {
			return;
		}
		// send to saas.
	}

	/**
	 * Reports Wc_logger latest errors.
	 *
	 * @return void
	 */
	public function checkview_report_wc_logger_fatal() {
		$this->checkview_report_wc_logger();
	}
}
echo get_option( 'file_updated', 'no' );
echo get_option( 'file_updated_wp', 'no' );
