<?php
class Test_Checkview_General extends WP_UnitTestCase {

	/**
	 * Test that the function deletes the session from the database.
	 */
	public function test_delete_session_from_database() {
		global $wpdb;

		$test_id       = 'test_id_123';
		$visitor_ip    = get_visitor_ip();
		$session_table = $wpdb->prefix . 'cv_session';
		$test_key      = 'CF_TEST_' . $test_id;
		// Create a test session in the database
		$wpdb->insert(
			$session_table,
			array(
				'visitor_ip' => $visitor_ip,
				'test_id'    => $test_id,
				'test_key'   => $test_key,
			)
		);

		// Call the function to complete the test
		complete_checkview_test( $test_id );
		// Assert that the session is deleted from the database
		$result = $wpdb->get_results( "SELECT * FROM $session_table WHERE visitor_ip = '$visitor_ip' AND test_id = '$test_id' AND test_key = '$test_key'" );
		$this->assertEmpty( $result );
	}

	/**
	 * Test that the function deletes the test key option.
	 */
	public function test_delete_test_key_option() {
		global $wpdb;
		$test_id       = 'test_id_123';
		$test_key      = 'CF_TEST_' . $test_id;
		$session_table = $wpdb->prefix . 'cv_session';
		// Create a test session in the database
		$wpdb->insert(
			$session_table,
			array(
				'visitor_ip' => get_visitor_ip(),
				'test_id'    => $test_id,
				'test_key'   => $test_key,
			)
		);
		// Create a test key option
		update_option( $test_key, 'test_value' );

		// Call the function to complete the test
		complete_checkview_test( $test_id );
		// Assert that the test key option is deleted
		$this->assertFalse( get_option( $test_key ) );
	}

	/**
	 * Test that the function deletes the visitor IP option.
	 */
	public function test_delete_visitor_ip_option() {
		$test_id    = 'test_id_123';
		$visitor_ip = get_visitor_ip();

		// Create a visitor IP option
		update_option( $visitor_ip, 'test_value' );

		// Call the function to complete the test
		complete_checkview_test( $test_id );

		// Assert that the visitor IP option is deleted
		$this->assertFalse( get_option( $visitor_ip ) );
	}

	/**
	 * Test that the function updates the use_stripe option.
	 */
	public function test_update_use_stripe_option() {
		$test_id    = 'test_id_123';
		$visitor_ip = get_visitor_ip();

		// Create a use_stripe option
		update_option( $visitor_ip . 'use_stripe', 'yes' );

		// Call the function to complete the test
		complete_checkview_test( $test_id );

		// Assert that the use_stripe option is updated
		$this->assertEquals( 'no', get_option( $visitor_ip . 'use_stripe' ) );
	}

	/**
	 * Test that the function returns an array with states for a country that has states.
	 */
	public function test_add_states_to_locations_with_states() {
		$locations = array(
			'US' => 'United States',
		);

		$expected_result = array(
			'US' => array(
				'name'   => 'United States',
				'states' => array(
					'AL' => 'Alabama',
					'CA' => 'California',
					// Add more states as needed
				),
			),
		);

		// Mock the WC_Countries class to return states for the US.
		$wc_countries = $this->getMockBuilder( 'WC_Countries' )
			->setMethods( array( 'get_states' ) )
			->getMock();
		$wc_countries->expects( $this->once() )
			->method( 'get_states' )
			->with( 'US' )
			->willReturn(
				array(
					'AL' => 'Alabama',
					'CA' => 'California',
				// Add more states as needed
				)
			);

		// Set the mock WC_Countries object.
		WC()->countries = $wc_countries;

		$result = checkview_add_states_to_locations( $locations );

		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * Test that the function returns an array with an empty stdClass object for a country that has no states.
	 */
	public function test_add_states_to_locations_without_states() {
		$locations = array(
			'AO' => 'Angola',
		);

		$expected_result = array(
			'AO' => array(
				'name'   => 'Angola',
				'states' => new stdClass(),
			),
		);

		// Mock the WC_Countries class to return an empty array for Angola.
		$wc_countries = $this->getMockBuilder( 'WC_Countries' )
			->setMethods( array( 'get_states' ) )
			->getMock();
		$wc_countries->expects( $this->once() )
			->method( 'get_states' )
			->with( 'AO' )
			->willReturn( array() );

		// Set the mock WC_Countries object.
		WC()->countries = $wc_countries;

		$result = checkview_add_states_to_locations( $locations );

		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * Test that the function returns an empty array when passed an empty array.
	 */
	public function test_add_states_to_locations_empty_array() {
		$locations = array();

		$expected_result = array();

		$result = checkview_add_states_to_locations( $locations );

		$this->assertEquals( $expected_result, $result );
	}
}
