<?php
/**
 * Checkview_I18n class
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
class Checkview_I18n {


	/**
	 * TODO: Grayson
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'checkview',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
