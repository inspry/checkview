<?php
/**
 * Checkview_Payment_Gateway class
 *
 * @link https://checkview.io
 * @since 1.0.0
 *
 * @package CheckView
 * @subpackage CheckView/includes/woocommercehelper
 */

if ( class_exists( 'WC_Payment_Gateway' ) ) {
	/**
	 * TODO: Grayson
	 *
	 * @since 1.0.0
	 * @package CheckView
	 * @subpackage CheckView/includes/woocommercehelper
	 * @author CheckView <checkview> https://checkview.io/
	 */
	class Checkview_Payment_Gateway extends WC_Payment_Gateway {

		/**
		 * TODO: Grayson
		 */
		public function __construct() {

			$this->id          = 'checkview';
			$this->title       = 'CheckView Testing';
			$this->description = 'Pay with CheckView test gateway';
			$this->enabled     = 'yes';
			$this->supports[]  = 'products';
			$this->supports[]  = 'subscriptions';
			$this->supports[]  = 'cancellation';
			$this->supports[]  = 'suspension';
			$this->supports[]  = 'refunds';
			$this->supports[]  = 'payment_method_change';
			$this->supports[]  = 'payment_method_change_customer';
			$this->supports[]  = 'payment_method_change_admin';
			$this->init_settings();
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/**
		 * TODO: Grayson
		 *
		 * @return void
		 */
		public function init_settings() {
			parent::init_settings();
			$this->enabled = 'yes';
		}

		/**
		 * TODO: Grayson
		 *
		 * @return void
		 */
		public function payment_fields() {
			echo '<p>' . esc_html( $this->description ) . '</p>';
		}
		/**
		 * TODO: Grayson
		 *
		 * @param integer $order_id WooCommerce order id.
		 * @return array
		 */
		public function process_payment( $order_id ) {

			global $woocommerce;

			// Get an instance of the order object.
			$order = new WC_Order( $order_id );
			if ( $order && $order_id ) {
				$order->update_status( 'completed' );

				$order->payment_complete();
			} else {
				// Return thankyou redirect.
				return array(
					'result'   => 'failure',
					'redirect' => '',
				);
			}

			// Remove cart.
			$woocommerce->cart->empty_cart();

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
	}
}
