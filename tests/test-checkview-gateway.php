<?php
use WP_Mock\Tools\TestCase;
class Checkview_Payment_Gateway_Test extends WP_UnitTestCase {

	public function test_construct() {
		// Load payment gateway.
		require_once CHECKVIEW_INC_DIR . 'woocommercehelper/class-checkview-payment-gateway.php';
		$instance = new Checkview_Payment_Gateway();
		$this->assertInstanceOf( 'Checkview_Payment_Gateway', $instance );
		$this->assertEquals( 'checkview', $instance->id );
		$this->assertEquals( 'CheckView Testing', $instance->title );
		$this->assertEquals( 'Pay with CheckView test gateway', $instance->description );
		$this->assertEquals( 'yes', $instance->enabled );
		$this->assertContains( 'products', $instance->supports );
		$this->assertContains( 'subscriptions', $instance->supports );
		$this->assertContains( 'cancellation', $instance->supports );
		$this->assertContains( 'suspension', $instance->supports );
		$this->assertContains( 'refunds', $instance->supports );
		$this->assertContains( 'payment_method_change', $instance->supports );
		$this->assertContains( 'payment_method_change_customer', $instance->supports );
		$this->assertContains( 'payment_method_change_admin', $instance->supports );
	}

	public function test_init_settings() {
		$instance = new Checkview_Payment_Gateway();
		$instance->init_settings();
		$this->assertEquals( 'yes', $instance->enabled );
	}

	public function test_payment_fields() {
		$instance = new Checkview_Payment_Gateway();
		ob_start();
		$instance->payment_fields();
		$output = ob_get_clean();
		$this->assertStringContainsString( 'Pay with CheckView test gateway', $output );
	}

	public function test_process_payment() {
		$instance = new Checkview_Payment_Gateway();
		$order_id = 167;
		$order    = new Mock_Order( $order_id );
		$order->update_status( 'completed' );
		$order->payment_complete();
		$woocommerce       = new Mock_WooCommerce();
		$woocommerce->cart = new Mock_Cart();
		$result            = $instance->process_payment( $order_id );
		$woocommerce->cart->empty_cart();
		$this->assertEquals( 'success', $result['result'] );
		$this->assertNotEmpty( $result['redirect'] );
		$this->assertTrue( $woocommerce->cart->is_empty );
	}

	public function test_process_payment_order_completed() {
		$instance = new Checkview_Payment_Gateway();
		$order_id = 167;
		$order    = new Mock_Order( $order_id );
		$order->update_status( 'completed' );
		$order->payment_complete();
		$woocommerce       = new Mock_WooCommerce();
		$woocommerce->cart = new Mock_Cart();
		$woocommerce->cart->empty_cart();
		$instance->process_payment( $order_id );
		$this->assertEquals( 'completed', $order->get_status() );
		$this->assertTrue( $woocommerce->cart->is_empty );
	}

	public function test_process_payment_order_not_found() {
		$instance          = new Checkview_Payment_Gateway();
		$order_id          = '';
		$woocommerce       = new Mock_WooCommerce();
		$woocommerce->cart = new Mock_Cart();
		$result            = $instance->process_payment( $order_id );
		$this->assertNotEquals( 'success', $result['result'] );
		$this->assertEmpty( $result['redirect'] );
		$this->assertFalse( $woocommerce->cart->is_empty );
	}
}

class Mock_WooCommerce {
	public $cart;
}

class Mock_Cart {
	public $is_empty = false;

	public function empty_cart() {
		$this->is_empty = true;
	}
}

class Mock_Order {
	private $status;

	public function update_status( $status ) {
		$this->status = $status;
	}

	public function get_status() {
		return $this->status;
	}

	public function payment_complete() {
		// Do nothing
	}
}
