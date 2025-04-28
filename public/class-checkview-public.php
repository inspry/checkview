<?php
/**
 * Checkview_Public class
 *
 * @since 1.0.0
 *
 * @package Checkview
 * @subpackage Checkview/public
 */

/**
 * Handles various public area features.
 *
 * @package Checkview
 * @subpackage Checkview/public
 * @author Check View <support@checkview.io>
 */
class Checkview_Public {

	/**
	 * Plugin name.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @var string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @var string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * Sets class properties.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueues public facing scripts, dequeues CF7 scripts and styles for tests.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// Current Vsitor IP.
		$visitor_ip = checkview_get_visitor_ip();
		// Check view Bot IP.
		$cv_bot_ip = checkview_get_api_ip();

		// Procceed if visitor IP is a CheckView bot IP.
		if ( is_array( $cv_bot_ip ) && in_array( $visitor_ip, $cv_bot_ip ) ) {
			wp_dequeue_script( 'contact-form-7' );
			wp_dequeue_style( 'contact-form-7' );
			wp_dequeue_script( 'wpcf7-recaptcha' );
			wp_dequeue_style( 'wpcf7-recaptcha' );
		}
	}
}
