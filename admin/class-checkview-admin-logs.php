<?php
/**
 * Logs important actions of our plugin
 *
 * @link       https://inspry.com
 * @since      1.0.0
 *
 * @package    CheckView
 * @subpackage CheckView/admin/
 */

/**
 * Logs important actions of our plugin
 *
 * It is important that we save some events that take place on the plugin, this class
 * handles the addition of messages to our log file
 *
 * @author      CheckView
 * @category    Incldues
 * @package     CheckView/admin/
 * @version     1.0.0
 */
class Checkview_Admin_Logs {

	/**
	 * Stores open file _handles.
	 *
	 * @var array
	 * @access private
	 */
	private static $_handles;

	/**
	 * Constructor for the logger.
	 */
	public function __construct() {
		self::$_handles = array();
	}

	/**
	 * Destructor.
	 */
	public function __destruct() {
		foreach ( self::$_handles as $handle ) {
			if ( is_resource( $handle ) ) {
				@fclose( $handle );
			}
		}
	}

	/**
	 * Returns the uplaods directory
	 *
	 * @return string
	 */
	public static function get_uploads_folder() {

		$uploads = wp_upload_dir( null, false );

		return isset( $uploads['basedir'] ) && $uploads['basedir'] ? $uploads['basedir'] : '';
	} // end get_uplaods_folder;

	/**
	 * Save Plugin Settings Admin ajax formsa
	 *
	 * @return void
	 */
	public function checkview_admin_logs_settings_save() {
		$nonce  = isset( $_POST['checkview_admin_logs_settings'] ) ? sanitize_text_field( wp_unslash( $_POST['checkview_admin_logs_settings'] ) ) : '';
		$action = 'checkview_admin_logs_settings';
		if ( isset( $_POST['checkview_see_log'] ) && wp_verify_nonce( $nonce, $action ) ) {
			$checkview_options = array();
			$log_path          = isset( $_POST['checkview_log_select'] ) ? sanitize_text_field( wp_unslash( $_POST['checkview_log_select'] ) ) : '';
			$uploads           = 'false';
			if ( $log_path && '' !== $log_path ) {
				$log_path                                  = checkview_deslash( $log_path );
				$checkview_options['checkview_log_select'] = $log_path;
				$checkview_options                         = apply_filters( 'checkview_save_log_options', $checkview_options );
				update_option( 'checkview_log_options', $checkview_options );
				$uploads = 'true';

			}
			wp_safe_redirect( add_query_arg( 'logs-settings-updated', $uploads, isset( $_POST['_wp_http_referer'] ) ? sanitize_url( wp_unslash( $_POST['_wp_http_referer'] ) ) : '' ) );
			exit;
		}
	}

	/**
	 * Returns the logs folder
	 *
	 * @return string
	 */
	public static function get_logs_folder() {

		$path = apply_filters( 'checkview_get_logs_folder', self::get_uploads_folder() . '/checkview-logs/' );

		return $path;
	} // end get_logs_folder;

	/**
	 * Creates Logs folder.
	 *
	 * @return void
	 */
	public static function create_logs_folder() {

		// Creates the Folder.
		wp_mkdir_p( self::get_logs_folder() );

		// Creates htaccess.
		$htaccess = self::get_logs_folder() . '.htaccess';

		if ( ! file_exists( $htaccess ) ) {

			$fp = @fopen( $htaccess, 'w' );

			@fputs( $fp, 'deny from all' );

			@fclose( $fp );

		}

		// Creates index.
		$index = self::get_logs_folder() . 'index.html';

		if ( ! file_exists( $index ) ) {

			$fp = @fopen( $index, 'w' );

			@fputs( $fp, '' );

			@fclose( $fp );

		}
	} // end create_logs_folder;

	/**
	 * Get the log contents
	 *
	 * @since  1.6.0
	 * @param  string  $handle file handle.
	 * @param  integer $lines number of line to enter.
	 * @return array
	 */
	public static function read_lines( $handle, $lines = 10 ) {

		$results = array();

		// Open the file for reading.
		if ( self::open( $handle, 'r' ) && is_resource( self::$_handles[ $handle ] ) ) {

			while ( ! feof( self::$_handles[ $handle ] ) ) {

				$line = fgets( self::$_handles[ $handle ], 4096 );

				array_push( $results, $line );

				if ( count( $results ) > $lines + 1 ) {

					array_shift( $results );

				}
			}
		}

		// Close the file handle; when you are done using a
		// resource you should always close it immediately.

		return array_filter( $results );
	} // end read_lines;

	/**
	 * Open log file for writing.
	 *
	 * @since  1.2.0 Checks if the directory exists
	 * @since  0.0.1
	 *
	 * @access private
	 * @param mixed  $handle file handle.
	 * @param string $permission file permissions.
	 * @return bool success
	 */
	private static function open( $handle, $permission = 'a' ) {

		// Get the path for our logs.
		$path = self::get_logs_folder();

		if ( ! is_dir( $path ) ) {
			self::create_logs_folder();

			return false;
		}
		self::$_handles[ $handle ] = @fopen( $path . $handle . '.log', $permission );
		if ( self::$_handles[ $handle ] ) {

			return true;
		}

		return false;
	}

	/**
	 * Add a log entry to chosen file.
	 *
	 * @param string $handle file handle.
	 * @param string $message log to write.
	 */
	public static function add( $handle, $message ) {
		if ( self::open( $handle ) && is_resource( self::$_handles[ $handle ] ) ) {
			$time   = self::get_now()->format( 'm-d-Y @ H:i:s -' ); // Grab Time.
			$result = @fwrite( self::$_handles[ $handle ], $time . ' ' . $message . "\n" );
			@fclose( self::$_handles[ $handle ] );
		}

		do_action( 'checkview_log_add', $handle, $message );
	}

	/**
	 * Get the NOW relative to our timezone
	 *
	 * @since 1.5.1
	 * @param string $type type of date.
	 * @return date
	 */
	public static function get_now( $type = 'mysql' ) {

		return new DateTime( self::get_current_time( $type ) );
	}

	/**
	 * Returns the current time from the network
	 *
	 * @param string $type date type.
	 * @return date
	 */
	public static function get_current_time( $type = 'mysql' ) {
		if ( is_multisite() ) {

			switch_to_blog( get_current_site()->blog_id );

			$time = current_time( $type );

			restore_current_blog();
		} else {

			$time = current_time( $type );
		}

		return $time;
	} // end get_current_time;

	/**
	 * Clear entries from chosen file.
	 *
	 * @param mixed $handle file handle.
	 */
	public function clear( $handle ) {
		if ( self::open( $handle ) && is_resource( self::$_handles[ $handle ] ) ) {
			@ftruncate( self::$_handles[ $handle ], 0 );
		}

		do_action( 'checkview_log_clear', $handle );
	}
}
