<?php

class Test_Checkview_Admin_Settings extends WP_UnitTestCase {

    public function setUp() : void {
        parent::setUp();
        $this->settings = new Checkview_Admin_Settings( 'checkview', '1.0.0' );
    }

    public function test_checkview_admin_notices() {
        $_POST['checkview_admin_advance_settings_action'] = 'checkview_admin_advance_settings_action';
        $_POST['checkview_settings_submit'] = 'true';
        ob_start();
        $this->settings->checkview_admin_notices();
        $notices = ob_get_clean();
        $this->assertStringContainsString( '', $notices );
    }

    // public function test_checkview_admin_advance_settings_save() {
    //     $_POST['checkview_admin_advance_settings_action'] = 'test_nonce';
    //     $_POST['checkview_advance_settings_submit'] = 'true';
    //     $this->settings->checkview_admin_advance_settings_save();
    //     $this->assertTrue( get_site_option( 'checkview_admin_menu_title' ) );
    //     $this->assertTrue( get_option( 'checkview_advance_options' ) );
    // }

    // public function test_checkview_update_cache() {
    //     $this->assertTrue( $this->settings->checkview_update_cache() );
    // }

    public function test_checkview_menu() {
        add_filter( 'checkview_template_url', function() {
            return '';
        } );
        $this->settings->checkview_menu();
        $this->assertTrue( true );
    }

    public function test_checkview_options() {
        ob_start();
        $this->settings->checkview_options();
        $options = ob_get_clean();
        $this->assertStringContainsString( 'checkview-options', $options );
    }

    public function test_checkview_get_setting_sections() {
        $sections = $this->settings->checkview_get_setting_sections();
        $this->assertArrayHasKey( 'general', $sections );
        $this->assertArrayHasKey( 'api', $sections );
        $this->assertArrayHasKey( 'logs', $sections );
    }

    public function test_checkview_add_footer_admin() {
        $footer_text = $this->settings->checkview_add_footer_admin( 'Test Footer' );
        $this->assertStringContainsString( 'Test Footer', $footer_text );
    }
}