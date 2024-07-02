<?php

class Test_Checkview_Admin_Logs extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->logs = new Checkview_Admin_Logs();
	}

	public function test_get_uploads_folder() {
		$uploads_folder = $this->logs->get_uploads_folder();
		$this->assertNotEmpty( $uploads_folder );
	}

	public function test_get_logs_folder() {
		$logs_folder = $this->logs->get_logs_folder();
		$this->assertNotEmpty( $logs_folder );
	}

	public function test_create_logs_folder() {
		$this->logs->create_logs_folder();
		$this->assertTrue( file_exists( $this->logs->get_logs_folder() ) );
	}

	public function test_read_lines() {
		$handle = 'test_log';
		$this->logs->add( $handle, 'Test log message' );
		$lines = $this->logs->read_lines( $handle );
		$this->assertNotEmpty( $lines );
		$this->assertTrue( str_contains( $lines[0], 'Test log message' ) );
	}

	public function test_add() {
		$handle  = 'test_log';
		$message = 'Test log message';
		$this->logs->add( $handle, $message );
		$this->assertTrue( file_exists( $this->logs->get_logs_folder() . $handle . '.log' ) );
	}


	public function test_checkview_admin_logs_settings_save() {
		$_POST['checkview_admin_logs_settings'] = 'test_nonce';
		$_POST['checkview_see_log']             = 'true';
		$this->logs->checkview_admin_logs_settings_save();
		$this->assertNotEmpty( get_option( 'checkview_log_options' ) );
	}

	public function test_clear() {
		$handle = 'test_log';
		$this->logs->add( $handle, 'Test log message' );
		$this->logs->clear( $handle );
		$this->assertEmpty( file_get_contents( $this->logs->get_logs_folder() . $handle . '.log' ) );
	}
}
