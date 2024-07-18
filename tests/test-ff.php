<?php

class Test_Checkview_Fluent_Forms_Helper extends WP_UnitTestCase {

	private $helper;

	public function setUp(): void {
		parent::setUp();
		if ( is_plugin_active( 'fluentform/fluentform.php' ) ) {
			require_once CHECKVIEW_INC_DIR . 'formhelpers/class-checkview-fluent-forms-helper.php';
		}
		$this->helper = new Checkview_Fluent_Forms_Helper();
		$send_to      = 'verify@test-mail.checkview.io';
		if ( isset( $test_form['send_to'] ) && '' !== $test_form['send_to'] ) {
			$send_to = $test_form['send_to'];
		}

		if ( ! defined( 'TEST_EMAIL' ) ) {
			define( 'TEST_EMAIL', $send_to );
		}
	}

	public function test_constructor() {
		$this->assertInstanceOf( 'Checkview_Fluent_Forms_Helper', $this->helper );
		$this->assertInstanceOf( 'Checkview_Loader', $this->helper->loader );
	}

	public function test_checkview_inject_email() {
		$address        = 'verify@test-mail.checkview.io';
		$notification   = 'test notification';
		$submitted_data = array( 'key' => 'value' );
		$form           = new stdClass();
		$form->id       = 1;

		$result = $this->helper->checkview_inject_email( $address, $notification, $submitted_data, $form );
		$this->assertEquals( TEST_EMAIL, $result );
	}

	public function test_checkview_clone_fluentform_entry() {
		global $wpdb;

		$entry_id  = 1;
		$form_data = array( 'key' => 'value' );
		$form      = new stdClass();
		$form->id  = 1;

		$this->helper->checkview_clone_fluentform_entry( $entry_id, $form_data, $form );

		$table  = $wpdb->prefix . 'cv_entry';
		$result = $wpdb->get_results( "SELECT * FROM $table WHERE form_id = 1" );
		$this->assertNotEmpty( $result );

		$table  = $wpdb->prefix . 'cv_entry_meta';
		$result = $wpdb->get_results( "SELECT * FROM $table WHERE form_id = 1" );
		$this->assertNotEmpty( $result );
        // Test completed So Clear sessions.
	}

	public function test_filters() {
		$this->assertTrue( has_filter( 'fluentform_email_to' ) );
		$this->assertTrue( has_filter( 'fluentform/has_recaptcha' ) );
		$this->assertTrue( has_filter( 'fluentform/akismet_check_spam' ) );
		$this->assertTrue( has_filter( 'cfturnstile_whitelisted' ) );
	}

	public function test_actions() {
		$this->assertTrue( has_action( 'fluentform_submission_inserted' ) );
	}
}
