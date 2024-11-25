<?php
/**
 * Checkview_Public class
 *
 * @link https://checkview.io
 * @since 1.0.0
 *
 * @package Checkview
 * @subpackage Checkview/public
 */

/**
 * TODO: Grayson
 *
 * @package Checkview
 * @subpackage Checkview/public
 * @author Check View <support@checkview.io>
 */
class Checkview_Public {

	/**
	 * TODO: Grayson
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * TODO: Grayson
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * TODO: Grayson
	 *
	 * @since 1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * TODO: Grayson
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * TODO: Grayson
		 */

		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/checkview-public.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * TODO: Grayson
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * TODO: Grayson
		 */

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/checkview-public.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		// Current Vsitor IP.
		$visitor_ip = checkview_get_visitor_ip();
		// Check view Bot IP.
		$cv_bot_ip = checkview_get_api_ip();
		// procceed if visitor ip is equal to cv bot ip.
		if ( is_array( $cv_bot_ip ) && in_array( $visitor_ip, $cv_bot_ip ) ) {
			wp_dequeue_script( 'contact-form-7' );
			wp_dequeue_style( 'contact-form-7' );
			wp_dequeue_script( 'wpcf7-recaptcha' );
			wp_dequeue_style( 'wpcf7-recaptcha' );
			// wp_dequeue_script( 'google-recaptcha' );
		}
	}
}
