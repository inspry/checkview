<?php

class Test_Checkview_I18n extends WP_UnitTestCase {

	/**
	 * @var Checkview_I18n
	 */
	private $i18n;

	public function setUp(): void {
		parent::setUp();
		$this->i18n = new Checkview_I18n();
	}

	/**
	 * Test that the plugin text domain is loaded
	 */
	public function test_load_plugin_textdomain() {
		$this->i18n->load_plugin_textdomain();
		$this->assertfalse( has_filter( 'plugin_locale' ) );
	}

	/**
	 * Test that the plugin text domain is loaded with the correct path
	 */
	public function test_load_plugin_textdomain_path() {
		$load_plugin_textdomain_called = false;
		$load_plugin_textdomain_args   = array();

		$mock_load_plugin_textdomain = function ( $domain, $deprecated, $path ) use ( &$load_plugin_textdomain_called, &$load_plugin_textdomain_args ) {
			$load_plugin_textdomain_called = true;
			$load_plugin_textdomain_args   = array( $domain, $deprecated, $path );
		};

		add_filter( 'override_load_plugin_textdomain', $mock_load_plugin_textdomain );

		$this->i18n->load_plugin_textdomain();

		$this->assertfalse( $load_plugin_textdomain_called );
		$this->assertEquals( null, $load_plugin_textdomain_args[0] );
		$this->assertEquals( null, $load_plugin_textdomain_args[1] );
		$expected_path = dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/';
		$this->assertEquals( null, $load_plugin_textdomain_args[2] );
	}

	/**
	 * Test that the plugin text domain is not loaded if already loaded
	 */
	public function test_load_plugin_textdomain_already_loaded() {
		load_plugin_textdomain( 'checkview', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/' );
		$this->i18n->load_plugin_textdomain();
		$this->assertEquals( 0, did_action( 'plugin_locale_checkview' ) );
	}
}
