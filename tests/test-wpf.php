<?php
class TestCheckviewWpformsHelper extends WP_UnitTestCase {

	protected $helper;

	public function setUp(): void {
		parent::setUp();
		if ( is_plugin_active( 'wpforms/wpforms.php' ) || is_plugin_active( 'wpforms-lite/wpforms.php' ) ) {
			require_once CHECKVIEW_INC_DIR . 'formhelpers/class-checkview-wpforms-helper.php';
		}
		$this->helper = new Checkview_Wpforms_Helper();
	}

	public function testCheckviewInjectEmail() {
		$email  = array(
			'address' => array( 'old@example.com' ),
		);
		$result = $this->helper->checkview_inject_email( $email );
		$this->assertEquals( 'verify@test-mail.checkview.io', $result['address'][0] );
	}

	public function testCheckviewLogWpformTestEntry() {
		global $wpdb;

		// Set up the necessary data for the test
		$form_fields = array(
			array(
				'id'    => rand( 2, 100 ),
				'value' => 'Test Value',
			),
		);
		$entry       = array(
			'id' => rand( 2, 100 ),
		);
		$form_data   = array(
			'id' => rand( 2, 100 ),
		);
		$entry_id    = rand( 2, 100 );

		// Call the method to test
		$this->helper->checkview_log_wpform_test_entry( $form_fields, $entry, $form_data, $entry_id );

		// Check if the entry was inserted into the database
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cv_entry WHERE form_id = {$form_data['id']}" );
		$this->assertNotEmpty( $results );
	}
}
