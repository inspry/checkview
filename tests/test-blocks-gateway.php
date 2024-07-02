<?php
use WP_UnitTestCase;

class Test_Checkview_Blocks_Payment_Gateway extends WP_UnitTestCase {

    private $instance;

    public function setUp() : void {
        parent::setUp();
        require_once CHECKVIEW_INC_DIR . 'woocommercehelper/class-checkview-blocks-payment-gateway.php';
        $this->instance = new Checkview_Blocks_Payment_Gateway();
        $this->instance->initialize();
    }

    public function test_is_active() {
        $this->assertTrue( $this->instance->is_active() );
    }

    public function test_get_payment_method_script_handles() {
        $handles = $this->instance->get_payment_method_script_handles();
        $this->assertContains( 'wc-checkview-payments-blocks', $handles );
    }

    public function test_get_payment_method_data() {
        $data = $this->instance->get_payment_method_data();
        $this->assertArrayHasKey( 'title', $data );
        $this->assertArrayHasKey( 'description', $data );
        $this->assertArrayHasKey( 'supports', $data );
    }
}