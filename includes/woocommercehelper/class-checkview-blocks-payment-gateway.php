<?php
/**
 * Checkview_Blocks_Payment_Gateway class
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    CheckView
 * @subpackage CheckView/includes/woocommercehelper
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * TODO: Grayson
 *
 * @since 1.0.3
 */
final class Checkview_Blocks_Payment_Gateway extends AbstractPaymentMethodType {

	/**
	 * TODO: Grayson
	 *
	 * @var Checkview_Blocks_Payment_Gateway
	 */
	private $gateway;

	/**
	 * TODO: Grayson
	 *
	 * @var string
	 */
	protected $name = 'checkview';

	/**
	 * TODO: Grayson
	 *
	 * @var array
	 */
	protected $supports = array( 'checkview' );

	/**
	 * TODO: Grayson
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_checkview_settings', array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = isset( $gateways[ $this->name ] ) ? $gateways[ $this->name ] : null;
	}

	/**
	 * TODO: Grayson
	 *
	 * @return boolean
	 */
	public function is_active() {
		return true;
	}

	/**
	 * TODO: Grayson
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/frontend/blocks.js';
		$script_asset_path = CHECKVIEW_PLUGIN_DIR . '/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => '2.0.1',
			);
		$script_url        = CHECKVIEW_URI . $script_path;

		wp_register_script(
			'wc-checkview-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-checkview-payments-blocks', 'woocommerce-gateway-checkview', CHECKVIEW_PLUGIN_DIR . '/languages/' );
		}

		return array( 'wc-checkview-payments-blocks' );
	}

	/**
	 * TODO: Grayson
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => isset( $this->gateway->supports ) ? array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ) : array(),
		);
	}
}
