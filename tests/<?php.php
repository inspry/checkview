<?php
class ExamplePluginTest extends WP_UnitTestCase {

	public function test_create_cv_session_should_return_void() {
		// Arrange
		$ip      = '127.0.0.1';
		$test_id = 1;

		// Act
		$result = create_cv_session( $ip, $test_id );

		// Assert
		$this->assertNull( $result );
	}

	public function test_create_cv_session_should_insert_session_data_into_database() {
		// Arrange
		$ip      = '127.0.0.1';
		$test_id = 1;

		// Act
		create_cv_session( $ip, $test_id );

		// Assert
		global $wpdb;
		$session_table = $wpdb->prefix . 'cv_session';
		$session_data  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $session_table WHERE visitor_ip = %s", $ip ) );
		$this->assertEquals( $ip, $session_data->visitor_ip );
		$this->assertEquals( 'CF_TEST_', $session_data->test_key );
		$this->assertEquals( $test_id, $session_data->test_id );
	}

	public function test_create_cv_session_should_not_insert_session_data_if_already_exists() {
		// Arrange
		$ip      = '127.0.0.1';
		$test_id = 1;

		// Act
		create_cv_session( $ip, $test_id );
		create_cv_session( $ip, $test_id );

		// Assert
		global $wpdb;
		$session_table = $wpdb->prefix . 'cv_session';
		$session_data  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $session_table WHERE visitor_ip = %s", $ip ) );
		$this->assertCount( 1, $session_data );
	}
}
