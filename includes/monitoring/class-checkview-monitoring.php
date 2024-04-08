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
			'file_mod_allowed',
			$this,
			'checkview_check_wp_config_changes',
			10,
			2
		);

		$this->loader->add_action(
			'init',
			$this,
			'checkview_report_wc_logger',
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
	 * @param bool   $allowed allowed or not.
	 * @param string $file_path file path.
	 * @return bool$
	 */
	public function checkview_check_wp_config_changes( $allowed, $file_path ) {
		if ( strpos( $file_path, 'wp-config.php' ) !== false ) {
			// Send alert to SaaS via API.
			wp_remote_post(
				'https://example.com/api/wp_config_change_alert',
				array(
					'body'    => wp_json_encode(
						array(
							'time' => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) ), // Convert timestamp to a readable date-time format.
						)
					),
					'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				)
			);
		}

		return $allowed;
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
	public function checkview_report_wc_logger( $log_type = 'fatal-errors' ) {
		$is_enabled = get_option( 'woocommerce_logs_logging_enabled', '' );
		if ( empty( $is_enabled ) || 'yes' !== $is_enabled || null === $is_enabled ) {
			update_option( 'woocommerce_logs_logging_enabled', 'yes', true );
		}
		$file_controller = new FileController();
		$file_args       = array(
			'source'         => $log_type, // Use the log type parameter here.
			'per_page'       => 1,
			'offset'         => 0,
			'order'          => 'desc',
			'orderby'        => 'modified',
			'posts_per_page' => 1,
		);
		$logs            = $file_controller->get_files( $file_args, false );
		if ( ! is_array( $logs ) || empty( $logs ) ) {
			return;
		}
		// Take the first item as the latest log after sorting.
		$latest_log = reset( $logs );
		$file_id    = $latest_log->get_file_id();

		// Update the regular expression to match the log type dynamically.
		if ( preg_match( '/' . preg_quote( $log_type, '/' ) . '-(\d{4}-\d{2}-\d{2})/', $file_id, $matches ) ) {
			$date = $matches[1]; // The first captured group, which is the date.
			// Get the current date in the site's timezone in 'Y-m-d' format.
			$today = current_time( 'Y-m-d' );
			if ( $date !== $today ) {
				return;
			}
		}
		$file        = $file_controller->get_file_by_id( $latest_log->get_file_id() );
		$stream      = $file->get_stream();
		$line_number = 1;
		$errors      = array();
		$logged      = get_option( $latest_log->get_file_id() );
		while ( ! feof( $stream ) ) {
			$line = fgets( $stream );
			if ( is_string( $line ) ) {
				if ( $logged && $line_number > $logged ) {
					$errors[] = $this->format_line( $line, $line_number );
				} elseif ( ! $logged || empty( $logged ) ) {
					$errors[] = $this->format_line( $line, $line_number );
				}
				++$line_number;
			}
		}
		update_option( $latest_log->get_file_id(), $line_number - 1, true );
		if ( ! empty( $errors ) ) {
			update_option( $log_type . '_errors_tracked', $errors ); // Prefix the option name with the log type.
		}
	}
}
