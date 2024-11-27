<?php
/**
 * Checkview_I18n class
 *
 * @since 1.0.0
 *
 * @package Checkview
 * @subpackage Checkview/includes
 */

/**
 * Plugin internationalization class.
 *
 * @since 1.0.0
 * @package Checkview
 * @subpackage Checkview/includes
 * @author Check View <support@checkview.io>
 */
class Checkview_I18n {


	/**
	 * Loads the CheckView text domain.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'checkview',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
